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
    :root {
        --primary: #2C6E8A;
        --primary-dark: #1B4A5E;
        --primary-light: #B3E0F2;
        --secondary: #4A3B2B;
        --secondary-light: #FFF8E7;
        --secondary-lighter: #FFE8C2;
        --white: #FFFFFF;
        --black: #1A1A1A;
        --accent: #ffb74a;
        --dark: #1a1310;
        --shadow-light: 0 4px 12px rgba(74, 59, 43, 0.15);
        --shadow-medium: 0 6px 16px rgba(44, 110, 138, 0.2);
        --shadow-dark: 0 8px 24px rgba(74, 59, 43, 0.3);
        --border-radius: 12px;
        --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Poppins', sans-serif;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }

    body {
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        position: relative;
        overflow-x: hidden;
    }

    /* Background Image */
    .image-container {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 120%;
        z-index: -1;
    }

    #getstarted {
        width: 100%;
        height: 100%;
        object-fit: cover;
        filter: brightness(60%); /* Slightly darker for better contrast */
    }

    /* Overlay for better text readability */
    .image-container::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.3); /* Subtle dark overlay */
    }

    /* Header */
    .header {
        display: flex;
        justify-content: center;
        padding: 1rem 2rem;
        background: linear-gradient(135deg, var(--secondary-light), var(--secondary-lighter));
        box-shadow: var(--shadow-light);
        position: sticky;
        top: 0;
        z-index: 1000;
    }

    .logo-section img {
        width: 200px;
        transition: var(--transition);
    }

    .logo-section img:hover {
        transform: scale(1.1);
    }
.back-button {
    position: absolute;
    top: 1rem;
    left: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background-color: var(--primary);
    color: var(--white);
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 500;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-light);
    z-index: 1001; /* Above header and background */
    transition: var(--transition);
}

.back-button:hover {
    background-color: var(--primary-dark);
    transform: translateY(-2px);
    box-shadow: var(--shadow-medium);
}

.back-button i {
    font-size: 1rem;
}

.image-container {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 120%;
    z-index: -1;
}

#getstarted {
    width: 100%;
    height: 100%;
    object-fit: cover;
    filter: brightness(60%); /* Slightly darker for better contrast */
}

/* Overlay for better text readability */
.image-container::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.3); /* Subtle dark overlay */
}

/* Header */
.header {
    display: flex;
    justify-content: center;
    padding: 1rem 2rem;
    background: linear-gradient(135deg, var(--secondary-light), var(--secondary-lighter));
    box-shadow: var(--shadow-light);
    position: sticky;
    top: 0;
    z-index: 1000;
}

.logo-section img {
    width: 200px;
    transition: var(--transition);
}

.logo-section img:hover {
    transform: scale(1.1);
}

/* Main Content */
main {
    flex: 1;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 2rem;
    min-height: calc(100vh - 60px); /* Adjust for header height */
}

/* Login Container */
.login-container {
    width: 100%;
    max-width: 450px;
    padding: 2rem;
    background: rgba(255, 255, 255, 0.9); /* Semi-transparent white background */
    backdrop-filter: blur(10px); /* Glassmorphism effect */
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-dark);
    margin: 2rem 0;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.login-container h2 {
    font-size: 1.8rem;
    color: var(--primary);
    margin-bottom: 1.5rem;
    text-align: center;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    font-weight: 600;
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
    color: var(--secondary);
    font-weight: 500;
}

.input-wrapper {
    position: relative;
}

.edit-form input[type="email"],
.edit-form input[type="password"] {
    padding: 0.8rem 1rem;
    border: 1px solid var(--primary-light);
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.8);
    color: var(--secondary);
    font-size: 0.95rem;
    width: 100%;
    transition: var(--transition);
}

.edit-form input:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(44, 110, 138, 0.2);
    background: var(--white);
}

.password-toggle {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--secondary);
    cursor: pointer;
    font-size: 1rem;
    opacity: 0.7;
    transition: var(--transition);
}

.password-toggle:hover {
    opacity: 1;
    color: var(--primary);
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
    font-size: 0.9rem;
    color: var(--secondary);
}

.remember-me input {
    cursor: pointer;
    accent-color: var(--primary);
}

.forgot-password {
    color: var(--primary);
    text-decoration: none;
    font-size: 0.9rem;
    transition: var(--transition);
}

.forgot-password:hover {
    color: var(--primary-dark);
    text-decoration: underline;
}

.edit-form button {
    padding: 0.9rem;
    border: none;
    border-radius: var(--border-radius);
    cursor: pointer;
    font-size: 1rem;
    transition: var(--transition);
    background: var(--primary);
    color: var(--white);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.edit-form button:hover {
    background: var(--primary-dark);
    transform: translateY(-2px);
    box-shadow: var(--shadow-medium);
}

.create-account {
    text-align: center;
    margin-top: 1.5rem;
}

.create-account p {
    font-size: 0.9rem;
    color: var(--secondary);
}

.create-account a {
    color: var(--primary);
    text-decoration: none;
    font-weight: 500;
    transition: var(--transition);
}

.create-account a:hover {
    color: var(--primary-dark);
    text-decoration: underline;
}

/* SweetAlert Custom Styles */
.swal2-confirm {
    background-color: var(--primary) !important;
    color: var(--white) !important;
    border-radius: var(--border-radius) !important;
    padding: 0.5rem 1.5rem !important;
}

.swal2-confirm:hover {
    background-color: var(--primary-dark) !important;
}

.swal2-cancel {
    margin-right: 10px !important;
}

.swal2-input {
    border: 1px solid var(--primary-light) !important;
    box-shadow: none !important;
}

/* Responsive Design */
@media (max-width: 768px) {
    .header {
        padding: 0.75rem 1rem;
    }

    .logo-section img {
        width: 150px;
    }

    main {
        padding: 1rem;
    }

    .login-container {
        padding: 1.5rem;
        max-width: 90%;
    }

    .login-container h2 {
        font-size: 1.5rem;
    }

    .edit-form input[type="email"],
    .edit-form input[type="password"] {
        padding: 0.7rem 1rem;
        font-size: 0.9rem;
    }

    .edit-form button {
        padding: 0.8rem;
        font-size: 0.95rem;
    }

    .back-button {
        top: 0.75rem;
        left: 0.75rem;
        padding: 0.4rem 0.8rem;
        font-size: 0.85rem;
    }

    .back-button i {
        font-size: 0.9rem;
    }
}

@media (max-width: 480px) {
    .logo-section img {
        width: 120px;
    }

    .login-container {
        padding: 1rem;
        max-width: 95%;
    }

    .login-container h2 {
        font-size: 1.3rem;
    }

    .edit-form input[type="email"],
    .edit-form input[type="password"] {
        padding: 0.6rem 0.8rem;
        font-size: 0.85rem;
    }

    .edit-form button {
        padding: 0.7rem;
        font-size: 0.9rem;
    }

    .form-options {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
    }

    .remember-me, .forgot-password {
        font-size: 0.85rem;
    }

    .create-account p {
        font-size: 0.85rem;
    }

    .back-button {
        top: 0.5rem;
        left: 0.5rem;
        padding: 0.3rem 0.6rem;
        font-size: 0.8rem;
    }

    .back-button i {
        font-size: 0.8rem;
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
    <!-- Background Image -->
    <div class="image-container">
        <img src="/public/images/background/login.jpg" alt="Login Background" id="getstarted">
    </div>

    <!-- Back Button -->
    <a href="/views/index.php" class="back-button">
        <i class="fas fa-arrow-left"></i> Back
    </a>

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

    <?php
    // Close database connection
    $conn->close();
    ob_end_flush();
    ?>
</body>