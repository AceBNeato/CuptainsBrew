<?php
ob_start();
session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/mail.php';

// PHPMailer imports (will be used for password reset)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


$db_host = 'localhost';
$db_user = 'root'; 
$db_pass = ''; 
$db_name = 'cafe_db';

// Initialize variables
$email = '';
$remember = false;
$errors = [];

// Connect to MySQL server
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check for remember me cookie
if (empty($_SESSION['user_id']) && !empty($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    
    // Get token from database using prepared statement helper
    $sql = "SELECT user_id, token FROM remember_tokens WHERE token = ? AND expires_at > NOW()";
    $stmt = prepareAndExecute($sql, [$token]);
    
    if ($stmt && $result = $stmt->get_result()) {
        if ($result->num_rows == 1) {
            $tokenData = $result->fetch_assoc();
            
            // Get user data
            $userSql = "SELECT id, username FROM users WHERE id = ?";
            $userStmt = prepareAndExecute($userSql, [$tokenData['user_id']], 'i');
            
            if ($userStmt && $userResult = $userStmt->get_result()) {
                if ($userResult->num_rows == 1) {
                    $user = $userResult->fetch_assoc();
                    
                    // Log user in
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['loggedin'] = true;
                    
                    // Redirect to home page
                    header("Location: /views/users/User-Home.php");
                    exit();
                }
            }
        }
    }
    
    // Invalid token, clear cookie
    setcookie('remember_token', '', time() - 3600, '/');
}

// Process form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $errors[] = "Invalid request";
    } else {
        // Validate and sanitize inputs
        $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);
        
        // Validation
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        }
        
        if (empty($password)) {
            $errors[] = "Password is required";
        }
        
        // If no errors, proceed with login
        if (empty($errors)) {
            // Use prepared statement helper to get user with role
            $sql = "SELECT u.id, u.username, u.password, u.is_verified, r.name as role 
                   FROM users u 
                   JOIN roles r ON u.role_id = r.id 
                   WHERE u.email = ?";
            $stmt = prepareAndExecute($sql, [$email]);
            
            if ($stmt && $result = $stmt->get_result()) {
                if ($result->num_rows == 1) {
                    $user = $result->fetch_assoc();
                    
                    // Verify password
                    if (password_verify($password, $user['password'])) {
                        // Check if user is verified
                        if (!$user['is_verified']) {
                            // Instead of redirecting to verify.php, store user ID and show SweetAlert
                            $_SESSION['unverified_user_id'] = $user['id'];
                            $_SESSION['unverified_email'] = $email;

                            // Set a flag to show SweetAlert for unverified account
                            $show_verification_alert = true;
                            $unverified_email = $email;
                        } else {
                        // Password is correct, start session
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['user_type'] = $user['role'];
                        $_SESSION['loggedin'] = true;
                        
                        // Handle remember me
                        if ($remember) {
                            $token = bin2hex(random_bytes(64));
                            $expires = time() + 60 * 60 * 24 * 30; // 30 days
                            
                            // Store token using prepared statement
                            $insertToken = "INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY))";
                            prepareAndExecute($insertToken, [$user['id'], $token], 'is');
                            
                            // Set secure cookie
                            setcookie('remember_token', $token, [
                                'expires' => $expires,
                                'path' => '/',
                                'secure' => true,
                                'httponly' => true,
                                'samesite' => 'Strict'
                            ]);
                        }
                        
                        // Regenerate session ID for security
                        session_regenerate_id(true);
                        
                        // Redirect based on role
                        if ($user['role'] === 'admin') {
                            header("Location: /views/admin/Admin-Menu.php");
                        } else {
                            header("Location: /views/users/User-Home.php");
                        }
                        exit();
                        }
                    } else {
                        $errors[] = "Invalid credentials";
                    }
                } else {
                    $errors[] = "Invalid credentials";
                }
            }
        }
    }
}

// Handle forgot password request
if (isset($_GET['forgot'])) {
    $email = trim($conn->real_escape_string($_GET['email'] ?? ''));
    
    if (empty($email)) {
        $errors[] = "Email is required for password reset";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    } else {
        // Check if email exists and is not an admin account
        $sql = "SELECT u.id, r.name as role FROM users u JOIN roles r ON u.role_id = r.id WHERE u.email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            // Check if user is an admin
            if ($user['role'] === 'admin') {
                $errors[] = "Password reset is not available for admin accounts. Please contact system support.";
            } else {
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour expiration
            
            // Store token in database
            $updateSql = "UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param("ssi", $token, $expires, $user['id']);
            $updateStmt->execute();
            $updateStmt->close();
            
                // Create reset link
                $resetLink = "http://" . $_SERVER['HTTP_HOST'] . "/views/auth/reset-password.php?token=" . $token;
                
                // For development purposes only - store the link in session
                $_SESSION['reset_link'] = $resetLink;
                
                try {
                    // Send email using PHPMailer
                    $mail = new PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host = $mail_config['smtp_host'];
                    $mail->SMTPAuth = $mail_config['smtp_auth'];
                    $mail->Username = $mail_config['smtp_username'];
                    $mail->Password = $mail_config['smtp_password'];
                    $mail->SMTPSecure = $mail_config['smtp_secure'];
                    $mail->Port = $mail_config['smtp_port'];
                    
                    $mail->setFrom($mail_config['from_email'], $mail_config['from_name']);
                    $mail->addAddress($email);
                    
                    $mail->isHTML(true);
                    $mail->Subject = "Captain's Brew - Password Reset Request";
                    $mail->Body = "
                    <html>
                    <head>
                        <style>
                            body { font-family: 'Poppins', sans-serif; line-height: 1.6; color: #4a3b2b; }
                            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                            .header { background-color: #2C6E8A; color: white; padding: 10px; text-align: center; }
                            .content { padding: 20px; background-color: #FFFAEE; }
                            .button {
                                display: inline-block;
                                padding: 10px 20px;
                                background-color: #2C6E8A;
                                color: white;
                                text-decoration: none;
                                border-radius: 5px;
                                margin: 20px 0;
                            }
                        </style>
                    </head>
                    <body>
                        <div class='container'>
                            <div class='header'>
                                <h2>Password Reset Request</h2>
                            </div>
                            <div class='content'>
                                <p>Hello,</p>
                                <p>We received a request to reset your password. Click the button below to reset it:</p>
                                <p style='text-align: center;'>
                                    <a href='$resetLink' class='button'>Reset Password</a>
                                </p>
                                <p>This link will expire in 1 hour.</p>
                                <p>If you didn't request this, please ignore this email.</p>
                                <p>Best regards,<br>Captain's Brew Team</p>
                            </div>
                        </div>
                    </body>
                    </html>";
                    
                    $mail->send();
                    
                    // Show success message
                    echo "<script>
                        document.addEventListener('DOMContentLoaded', function() {
                            Swal.fire({
                                icon: 'success',
                                title: 'Reset Link Sent',
                                text: 'A password reset link has been sent to your email address.',
                                customClass: {
                                    confirmButton: 'swal2-confirm'
                                }
                            });
                        });
                    </script>";
                    
                } catch (Exception $e) {
                    // For development purposes, show the link in SweetAlert if email sending fails
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'info',
                                title: 'Reset Link Generated',
                                
                        customClass: {
                            confirmButton: 'swal2-confirm'
                        }
                    });
                });
            </script>";
                    
                    error_log("Failed to send password reset email to $email: " . $e->getMessage());
                }
            }
        } else {
            $errors[] = "No account found with that email";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Captain's Brew | Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="icon" href="/public/images/LOGO.png" sizes="any">
    <style>
        :root {
            --primary: #2C6E8A;
            --primary-dark: #235A73;
            --primary-light: #A9D6E5;
            --secondary: #4A3B2B;
            --accent: #ffb74a;
            --white: #fff;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-600: #4b5563;
            --error: #ef4444;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            padding: 1rem;
        }

        /* Background Image */
        .image-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }

        #getstarted {
            width: 100%;
            height: 100%;
            object-fit: cover;
            filter: brightness(50%);
        }

        /* Back Button */
        .back-button {
            position: fixed;
            top: 2rem;
            left: 2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background-color: var(--primary);
            color: var(--white);
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 500;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            z-index: 10;
        }

        .back-button:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15);
        }

        .back-button i {
            font-size: 1rem;
        }


        .rider-button{
            position: fixed;
            top: .8rem;
            right: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background-color: var(--primary);
            color: var(--white);
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 500;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            z-index: 10;


        }


        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 2.5rem;
            border-radius: 1rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 420px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-header h1 {
            color: var(--primary);
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            letter-spacing: 1px;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            color: var(--gray-600);
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 1px solid var(--gray-300);
            border-radius: 0.5rem;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(44, 110, 138, 0.1);
            background: var(--white);
        }

        .password-field {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-600);
            cursor: pointer;
            font-size: 1.25rem;
            opacity: 0.7;
            transition: opacity 0.3s ease;
        }

        .password-toggle:hover {
            opacity: 1;
        }

        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .remember-me input[type="checkbox"] {
            width: 1.125rem;
            height: 1.125rem;
            accent-color: var(--primary);
            cursor: pointer;
        }

        .remember-me label {
            color: var(--gray-600);
            font-size: 0.95rem;
            cursor: pointer;
        }

        .forgot-password {
            color: var(--primary);
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .forgot-password:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }



        .input-field{
            height: 2.625rem;
            margin: 0;
            padding: 1rem;
        }

        .login-button {
            width: 100%;
            padding: 0.875rem;
            background: var(--primary);
            color: var(--white);
            border: none;
            border-radius: 0.5rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .login-button:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .create-account {
            text-align: center;
            margin-top: 1.5rem;
            color: var(--gray-600);
            font-size: 0.95rem;
        }

        .create-account a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .create-account a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        /* Loading Spinner */
        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid var(--white);
            border-top: 4px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsive Design */
        @media (max-width: 640px) {
            .login-card {
                padding: 2rem;
                margin: 1rem;
            }

            .back-button {
                top: 1rem;
                left: 1rem;
                padding: 0.5rem 1rem;
                font-size: 0.875rem;
            }

            .login-header h1 {
                font-size: 1.75rem;
            }

            .form-group label {
                font-size: 0.875rem;
            }

            .form-group input {
                padding: 0.75rem;
                font-size: 0.95rem;
            }
        }
    </style>
</head>
<body>
    <!-- Background Image -->
    <div class="image-container">
        <img src="/public/images/background/login.jpg" alt="Login Background" id="getstarted">
    </div>

    <!-- Back Button -->
    <a href="/views/index.php" class="back-button">
        <i class="fas fa-arrow-left"></i> Back
        
    </a>

        
    <a href="/views/riders/login.php" class="rider-button">Rider Login</a>


    <div class="login-card">
        <div class="login-header">
            <h1>LOGIN</h1>
        </div>

        <form id="login-form" method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

            <div class="form-group">
                <label for="email">Email Address</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    value="<?php echo htmlspecialchars($email); ?>" 
                    placeholder="Enter your email"
                    required
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-field">
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        placeholder="Enter your password"
                        required
                    >
                    <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                </div>
            </div>

            <div class="remember-forgot">
                <div class="remember-me">
                    <input 
                        type="checkbox" 
                        id="remember" 
                        name="remember"
                        <?php echo $remember ? 'checked' : ''; ?>
                    >
                    <label for="remember">Remember me</label>
                </div>
                <a href="/views/auth/forgot-password.php" class="forgot-password">Forgot password?</a>
            </div>

            <button type="submit" class="login-button">Login</button>

            <div class="create-account">
                <p>Don't have an account? <a href="/views/auth/register.php">Create one</a></p>
            </div>
        </form>
    </div>

    <div class="loading-overlay">
        <div class="spinner"></div>
    </div>

    <script>
        // Toggle password visibility
        const togglePassword = document.getElementById('togglePassword');
        const password = document.getElementById('password');

        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });

        // Form submission handling
        document.getElementById('login-form').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;

            if (!email || !password) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: 'Please fill in all fields',
                    confirmButtonColor: '#2C6E8A'
                });
                return;
            }

            // Show loading overlay
            document.querySelector('.loading-overlay').style.display = 'flex';
        });

        // Display error messages if any
        <?php if (!empty($errors)): ?>
        Swal.fire({
            icon: 'error',
            title: 'Login Failed',
            text: '<?php echo htmlspecialchars($errors[0]); ?>',
            confirmButtonColor: '#2C6E8A'
        });
        <?php endif; ?>

        // Show verification alert for unverified users
        <?php if (isset($show_verification_alert) && $show_verification_alert): ?>
        Swal.fire({
            icon: 'warning',
            title: 'Email Not Verified',
            html: `
                <p>Your email address has not been verified yet.</p>
                <p>Please enter the verification code sent to your email:</p>
                <input type="text" id="verification-code" class="input-field" placeholder="Enter 6-digit code">
                <p class="mt-3 text-sm">Didn't receive a code? Click "Resend Code" below.</p>
            `,
            showCancelButton: true,
            confirmButtonText: 'Verify Email',
            cancelButtonText: 'Resend Code',
            confirmButtonColor: '#2C6E8A',
            cancelButtonColor: '#4a3b2b',
            showCloseButton: true,
            preConfirm: () => {
                const code = Swal.getPopup().querySelector('#verification-code').value;
                if (!code) {
                    Swal.showValidationMessage('Please enter verification code');
                    return false;
                }
                return { code };
            },
            allowOutsideClick: false
        }).then((result) => {
            if (result.isConfirmed) {
                const { code } = result.value;
                
                // Show loading indicator
                Swal.fire({
                    title: 'Verifying...',
                    html: 'Please wait while we verify your email',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Send AJAX request to verify code
                const formData = new FormData();
                formData.append('code', code);

                fetch('/controllers/verify-user.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message and redirect
                        Swal.fire({
                            icon: 'success',
                            title: 'Email Verified!',
                            text: data.message,
                            confirmButtonColor: '#2C6E8A',
                            allowOutsideClick: false
                        }).then(() => {
                            window.location.href = data.redirect;
                        });
                    } else {
                        // Show error message
                        Swal.fire({
                            icon: 'error',
                            title: 'Verification Failed',
                            text: data.message || 'Invalid verification code.',
                            confirmButtonColor: '#2C6E8A',
                            showCancelButton: true,
                            confirmButtonText: 'Try Again',
                            cancelButtonText: 'Resend Code',
                        }).then((result) => {
                            if (result.isDismissed && result.dismiss === Swal.DismissReason.cancel) {
                                // User clicked "Resend Code"
                                handleResendCode();
                            }
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Verification Failed',
                        text: 'An unexpected error occurred. Please try again later.',
                        confirmButtonColor: '#2C6E8A'
                    });
                });
            } else if (result.dismiss === Swal.DismissReason.cancel) {
                // User clicked "Resend Code"
                handleResendCode();
            }
        });

        // Function to handle resending verification code
        function handleResendCode() {
            // Show loading indicator
            Swal.fire({
                title: 'Sending...',
                html: 'Please wait while we send a new verification code',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Send AJAX request to resend verification code
            const formData = new FormData();
            formData.append('email', '<?php echo htmlspecialchars($unverified_email); ?>');

            fetch('/views/auth/resend-code.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    Swal.fire({
                        icon: 'success',
                        title: 'Code Sent!',
                        text: 'A new verification code has been sent to your email.',
                        confirmButtonColor: '#2C6E8A'
                    });
                } else {
                    // Show error message
                    Swal.fire({
                        icon: 'error',
                        title: 'Failed to Send Code',
                        text: data.message || 'An error occurred while sending the verification code.',
                        confirmButtonColor: '#2C6E8A'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Failed to Send Code',
                    text: 'An unexpected error occurred. Please try again later.',
                    confirmButtonColor: '#2C6E8A'
                });
            });
        }
        <?php endif; ?>
    </script>
</body>
</html>