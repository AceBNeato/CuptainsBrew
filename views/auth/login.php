<?php
ob_start();
session_start();

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
    
    // Get token from database
    $sql = "SELECT user_id, token FROM remember_tokens WHERE token = ? AND expires_at > NOW()";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $tokenData = $result->fetch_assoc();
        
        // Get user data
        $userSql = "SELECT id, username FROM users WHERE id = ?";
        $userStmt = $conn->prepare($userSql);
        $userStmt->bind_param("i", $tokenData['user_id']);
        $userStmt->execute();
        $userResult = $userStmt->get_result();
        
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
    
    // Invalid token, clear cookie
    setcookie('remember_token', '', time() - 3600, '/');
}

// Process form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize inputs
    $email = trim($conn->real_escape_string($_POST['email'] ?? ''));
    $password = trim($_POST['password'] ?? '');
    $remember = isset($_POST['remember']);
    
    // Simple validation
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    }
    
    // If no errors, proceed with login
    if (empty($errors)) {
        // Prepare SQL to get user data
        $sql = "SELECT id, username, password FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Password is correct, start session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['loggedin'] = true;
                
                // Handle remember me
                if ($remember) {
                    // Generate random token
                    $token = bin2hex(random_bytes(64));
                    $expires = time() + 60 * 60 * 24 * 30; // 30 days
                    
                    // Store token in database
                    $insertToken = "INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY))";
                    $tokenStmt = $conn->prepare($insertToken);
                    $tokenStmt->bind_param("is", $user['id'], $token);
                    $tokenStmt->execute();
                    $tokenStmt->close();
                    
                    // Set cookie
                    setcookie('remember_token', $token, $expires, '/', '', true, true);
                }
                
                // PHP redirect as fallback
                header("Location: /views/users/User-Home.php");
                
                // Show SweetAlert and attempt JavaScript redirect
                echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            icon: 'success',
                            title: 'Login Successful!',
                            text: 'Welcome back, {$user['username']}!',
                            showConfirmButton: false,
                            timer: 2000,
                            customClass: {
                                confirmButton: 'swal2-confirm'
                            }
                        }).then(() => {
                            try {
                                window.location.href = '/views/users/User-Home.php';
                            } catch (e) {
                                console.error('Redirect failed:', e);
                            }
                        }).catch(e => {
                            console.error('SweetAlert2 error:', e);
                        });
                    });
                </script>";
                exit();
            } else {
                $errors[] = "Incorrect password";
            }
        } else {
            $errors[] = "No account found with that email";
        }
        $stmt->close();
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
        // Check if email exists
        $sql = "SELECT id FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour expiration
            
            // Store token in database
            $updateSql = "UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param("ssi", $token, $expires, $user['id']);
            $updateStmt->execute();
            $updateStmt->close();
            
            // Send email (in a real implementation)
            $resetLink = "http://yourdomain.com/views/auth/reset-password.php?token=$token";
            
            // For demo purposes, we'll just show the link
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'info',
                        title: 'Password Reset Link',
                        html: 'A password reset link has been generated:<br><br><a href=\"$resetLink\">$resetLink</a>',
                        customClass: {
                            confirmButton: 'swal2-confirm'
                        }
                    });
                });
            </script>";
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
    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Font Awesome CDN for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="icon" href="/public/images/LOGO.png" sizes="any">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: #fff;
            color: #4a3b2b;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Header */
        .header {
            display: flex;
            align-items: center;
            padding: 1rem 2rem;
            background: linear-gradient(135deg, #FFFAEE, #FFDBB5);
            box-shadow: 0 2px 5px rgba(74, 59, 43, 0.3);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .logo-section img {
            width: 200px;
            margin: 0px 100px 0px 100px;
            transition: transform 0.3s;
        }

        .logo-section img:hover {
            transform: scale(1.1);
        }

        .nav-menu {
            display: flex;
            gap: 3rem;
        }

        .nav-button {
            background: none;
            border: none;
            color: #4a3b2b;
            font-size: 1rem;
            padding: 1rem 2rem;
            cursor: pointer;
            border-radius: 10px;
            transition: all 0.3s;
        }

        .nav-button:hover, .nav-button.active {
            background-color: #2C6E8A;
            color: #fff;
        }

        /* Main Content */
        main {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
        }

        /* Form Container */
        .login-container {
            width: 100%;
            max-width: 450px;
            padding: 2rem;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(74, 59, 43, 0.5);
            margin: 2rem 0;
        }

        .login-container h2 {
            font-size: 1.5rem;
            color: #2C6E8A;
            margin-bottom: 1.5rem;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .edit-form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .edit-form label {
            font-size: 0.9rem;
            color: #4a3b2b;
            font-weight: 500;
        }

        .input-wrapper {
            position: relative;
        }

        .edit-form input[type="email"],
        .edit-form input[type="password"] {
            padding: 0.8rem 1rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: #f9f9f9;
            color: #4a3b2b;
            font-size: 0.9rem;
            width: 100%;
            transition: all 0.3s;
        }

        .edit-form input:focus {
            outline: none;
            border-color: #2C6E8A;
            box-shadow: 0 0 0 2px rgba(44, 110, 138, 0.2);
            background: #fff;
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #4a3b2b;
            cursor: pointer;
            font-size: 1rem;
            opacity: 0.7;
            transition: opacity 0.3s;
        }

        .password-toggle:hover {
            opacity: 1;
        }

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 0.5rem 0;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
        }

        .remember-me input {
            cursor: pointer;
        }

        .forgot-password {
            color: #2C6E8A;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s;
        }

        .forgot-password:hover {
            color: #235A73;
            text-decoration: underline;
        }

        .edit-form button {
            padding: 0.8rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s;
            background: #2C6E8A;
            color: #fff;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .edit-form button:hover {
            background: #235A73;
            transform: translateY(-2px);
            box-shadow: 0 3px 10px rgba(44, 110, 138, 0.3);
        }

        .create-account {
            text-align: center;
            margin-top: 1.5rem;
        }

        .create-account p {
            font-size: 0.9rem;
            color: #4a3b2b;
        }

        .create-account a {
            color: #2C6E8A;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }

        .create-account a:hover {
            color: #235A73;
            text-decoration: underline;
        }

        /* Footer */
        footer {
            margin-top: auto;
        }

        .footer-container {
            background-color: #2C6E8A;
            color: white;
            padding: 2rem;
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 2rem;
        }

        .footer-left, .footer-right {
            flex: 1;
            min-width: 250px;
        }

        .footer-links ul {
            list-style: none;
            padding: 0;
        }

        .footer-links li {
            margin-bottom: 0.5rem;
        }

        .footer-links a {
            color: white;
            text-decoration: none;
            transition: color 0.3s;
        }

        .footer-links a:hover {
            color: #FFDBB5;
            text-decoration: underline;
        }

        .footer-social {
            margin-top: 1.5rem;
            display: flex;
            gap: 1rem;
        }

        .footer-social img {
            width: 30px;
            transition: transform 0.3s;
        }

        .footer-social img:hover {
            transform: scale(1.1);
        }

        .footer-contact h3 {
            font-size: 1.2rem;
            margin-bottom: 1rem;
        }

        .footer-contact p {
            margin-bottom: 0.5rem;
            line-height: 1.5;
        }

        .footer-bottom {
            background-color: #FFFAEE;
            text-align: center;
            padding: 1rem;
            color: #4a3b2b;
            font-size: 0.9rem;
        }

        /* SweetAlert Custom Styles */
        .swal2-confirm {
            background-color: #2C6E8A !important;
            color: #fff !important;
            border-radius: 5px !important;
            padding: 0.5rem 1.5rem !important;
        }

        .swal2-confirm:hover {
            background-color: #235A73 !important;
        }

        .swal2-cancel {
            margin-right: 10px !important;
        }

        .swal2-input {
            border: 1px solid #ddd !important;
            box-shadow: none !important;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                padding: 1rem;
            }

            .logo-section img {
                width: 150px;
                margin: 0 0 1rem 0;
            }

            .nav-menu {
                width: 100%;
                justify-content: center;
                gap: 0.5rem;
            }

            .nav-button {
                padding: 0.5rem 1rem;
                font-size: 0.9rem;
            }

            .login-container {
                padding: 1.5rem;
                margin: 1rem;
            }

            .footer-container {
                flex-direction: column;
                gap: 2rem;
                padding: 1.5rem;
            }
        }

        @media (max-width: 480px) {
            .form-options {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .edit-form input[type="email"],
            .edit-form input[type="password"] {
                padding: 0.7rem 1rem;
            }

            .edit-form button {
                padding: 0.7rem;
            }
        }
    </style>
    <script>
        // Toggle Password Visibility
        function togglePassword() {
            const password = document.getElementById('password');
            const toggle = document.getElementById('togglePassword');
            const type = password.type === 'password' ? 'text' : 'password';
            password.type = type;
            toggle.classList.toggle('fa-eye');
            toggle.classList.toggle('fa-eye-slash');
        }

        // Forgot password handler
        function handleForgotPassword(e) {
            e.preventDefault();
            const email = document.getElementById('email').value.trim();
            
            Swal.fire({
                title: 'Reset Password',
                html: `Enter your email to receive a reset link:<br><br>
                       <input id="swal-email" class="swal2-input" placeholder="Email" value="${email}">`,
                showCancelButton: true,
                confirmButtonText: 'Send Link',
                cancelButtonText: 'Cancel',
                focusConfirm: false,
                customClass: {
                    confirmButton: 'swal2-confirm',
                    cancelButton: 'swal2-cancel'
                },
                preConfirm: () => {
                    const email = document.getElementById('swal-email').value.trim();
                    if (!email) {
                        Swal.showValidationMessage('Email is required');
                    } else if (!/^\S+@\S+\.\S+$/.test(email)) {
                        Swal.showValidationMessage('Enter a valid email');
                    }
                    return { email: email };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `login.php?forgot=1&email=${encodeURIComponent(result.value.email)}`;
                }
            });
        }

        // Client-side validation with SweetAlert
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('.edit-form');
            form.addEventListener('submit', function(e) {
                const email = document.getElementById('email').value.trim();
                const password = document.getElementById('password').value;

                if (!email) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Email is required!',
                        customClass: {
                            confirmButton: 'swal2-confirm'
                        }
                    });
                    return;
                }
                if (!password) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Password is required!',
                        customClass: {
                            confirmButton: 'swal2-confirm'
                        }
                    });
                    return;
                }
            });

            // Display server-side errors with SweetAlert
            <?php if (!empty($errors)): ?>
                Swal.fire({
                    icon: 'error',
                    title: '<?php echo isset($_GET['forgot']) ? "Password Reset Failed" : "Login Failed" ?>',
                    html: '<?php echo implode("<br>", array_map("htmlspecialchars", $errors)); ?>',
                    customClass: {
                        confirmButton: 'swal2-confirm'
                    }
                });
            <?php endif; ?>

            // Add click handler to forgot password link
            document.querySelector('.forgot-password').addEventListener('click', function(e) {
                e.preventDefault();
                handleForgotPassword(e);
            });
        });
    </script>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="logo-section">
            <a href="/">
                <img src="/public/images/LOGO.png" alt="Captain's Brew Cafe">
            </a>
        </div>
        <nav class="nav-menu">
            <button class="nav-button" onclick="window.location.href='/views/auth/register.php'">Sign Up</button>
        </nav>
    </header>

    <!-- Main Content -->
    <main>
        <div class="login-container">
            <h2>LOGIN</h2>

            <form class="edit-form" method="POST" action="login.php">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-wrapper">
                        <input type="email" name="email" id="email" placeholder="Enter Email" value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <input type="password" name="password" id="password" placeholder="Enter Password" required>
                        <i class="fas fa-eye password-toggle" id="togglePassword" onclick="togglePassword()"></i>
                    </div>
                </div>

                <div class="form-options">
                    <div class="remember-me">
                        <input type="checkbox" id="remember" name="remember" <?php echo $remember ? 'checked' : ''; ?>>
                        <label for="remember">Remember me</label>
                    </div>
                    <a href="#" class="forgot-password">Forgot password?</a>
                </div>

                <button type="submit">Login</button>

                <div class="create-account">
                    <p>Don't have an account? <a href="/views/auth/register.php">Create one</a></p>
                </div>
            </form>
        </div>
    </main>

    <!-- Footer -->
    <footer>
        <div class="footer-container">
            <div class="footer-left">
                <div class="footer-links">
                    <ul>
                        <li><a href="/views/index.php">Home</a></li>
                        <li><a href="/views/menu.html">Menu</a></li>
                        <li><a href="/views/aboutus.html">About Us</a></li>
                    </ul>
                </div>
                <div class="footer-social">
                    <a href="#"><img src="/public/images/facebook.png" alt="facebook"></a>
                    <a href="#"><img src="/public/images/twitter.png" alt="twitter"></a>
                    <a href="#"><img src="/public/images/instagram.png" alt="instagram"></a>
                </div>
            </div>
            
            <div class="footer-right">
                <div class="footer-contact">
                    <h3>CONTACT US</h3>
                    <p>123 Coffee Street, City Name</p>
                    <p><strong>Phone:</strong> +1 800 555 6789</p>
                    <p><strong>E-mail:</strong> support@captainsbrew.com</p>
                    <p><strong>Website:</strong> www.captainsbrew.com</p>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <p>© Copyright 2025 Captain's Brew Cafe. All Rights Reserved.</p>
        </div>
    </footer>

    <?php
    // Close database connection
    $conn->close();
    ob_end_flush();
    ?>
</body>
</html>