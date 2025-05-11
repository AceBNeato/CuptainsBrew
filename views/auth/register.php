<?php
session_start();

require '../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'cafe_db';

$email = '';
$errors = [];

// Connect to MySQL server
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to generate a unique username
function generateUsername($email, $conn) {
    $base = explode('@', $email)[0];
    $base = preg_replace('/[^a-zA-Z0-9]/', '', $base);
    $username = $base;
    $counter = 1;

    while (true) {
        $sql = "SELECT id FROM users WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 0) {
            $stmt->close();
            return $username;
        }

        $username = $base . $counter;
        $counter++;
        $stmt->close();
    }
}

// Function to send verification email using PHPMailer
function sendVerificationEmail($email, $verification_code) {
    $mail = new PHPMailer(true);
    try {
        // Enable debug output (set to 0 in production)
        $mail->SMTPDebug = 2;
        $mail->Debugoutput = function($str, $level) {
            error_log("PHPMailer Debug [$level]: $str\n", 3, '../../logs/phpmailer_debug.log');
        };

        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'gdgarcia00410@usep.edu.ph';
        $mail->Password = 'lkhwwwsvuygopoxs';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('gdgarcia00410@usep.edu.ph', "Cuptain's Brew");
        $mail->addAddress($email);
        $mail->addReplyTo('gdgarcia00410@usep.edu.ph', "Cuptain's Brew Support");

        // Content
        $mail->isHTML(true);
        $mail->Subject = "Cuptain's Brew - Email Verification";
        $mail->Body = "
        <html>
        <head>
            <title>Email Verification</title>
            <style>
                body { font-family: 'Poppins', sans-serif; line-height: 1.6; color: #4a3b2b; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #2C6E8A; color: white; padding: 10px; text-align: center; }
                .content { padding: 20px; background-color: #FFFAEE; }
                .footer { margin-top: 20px; text-align: center; font-size: 0.8em; color: #4a3b2b; }
                .verification-code { 
                    font-size: 24px; 
                    font-weight: bold; 
                    letter-spacing: 3px; 
                    color: #2C6E8A;
                    text-align: center;
                    margin: 20px 0;
                    padding: 10px;
                    background-color: #A9D6E5;
                    border-radius: 5px;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Cuptain's Brew</h2>
                </div>
                <div class='content'>
                    <h3>Email Verification</h3>
                    <p>Thank you for registering with Cuptain's Brew. Please use the following verification code to complete your registration:</p>
                    <div class='verification-code'>$verification_code</div>
                    <p>This code will expire in 30 minutes. If you didn't request this, please ignore this email.</p>
                </div>
                <div class='footer'>
                    <p>© " . date('Y') . " Cuptain's Brew. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";

        $mail->send();
        error_log("Verification email sent to $email", 3, '../../logs/phpmailer_success.log');
        return true;
    } catch (Exception $e) {
        error_log("PHPMailer Error for $email: {$mail->ErrorInfo}", 3, '../../logs/phpmailer_errors.log');
        return false;
    }
}

// Process verification code if submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['verify_code'])) {
    $submitted_code = trim($_POST['verification_code'] ?? '');
    $email = $_SESSION['register_email'] ?? '';
    
    if (empty($submitted_code)) {
        $errors[] = "Verification code is required";
    } elseif (strlen($submitted_code) !== 6 || !ctype_digit($submitted_code)) {
        $errors[] = "Verification code must be 6 digits";
    }
    
    if (empty($errors) && !empty($email)) {
        $sql = "SELECT verification_code, verification_sent_at FROM users WHERE email = ? AND is_verified = FALSE";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $stored_code = $user['verification_code'];
            $sent_at = strtotime($user['verification_sent_at']);
            
            if (time() - $sent_at > 1800) {
                $errors[] = "Verification code has expired. Please request a new one.";
            } elseif ($submitted_code !== $stored_code) {
                $errors[] = "Invalid verification code";
            } else {
                $sql = "UPDATE users SET is_verified = TRUE, verification_code = NULL, verification_sent_at = NULL WHERE email = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("s", $email);
                
                if ($stmt->execute()) {
                    unset($_SESSION['register_email']);
                    error_log("User $email verified successfully", 3, '../../logs/registration_success.log');
                    echo "<script>
                        document.addEventListener('DOMContentLoaded', function() {
                            Swal.fire({
                                icon: 'success',
                                title: 'Registration Successful!',
                                text: 'Your account has been verified. You will be redirected to the login page.',
                                showConfirmButton: false,
                                timer: 2000,
                                customClass: {
                                    confirmButton: 'swal2-confirm'
                                }
                            }).then(() => {
                                window.location.href = '/views/auth/login.php';
                            }).catch((error) => {
                                console.error('SweetAlert error:', error);
                                window.location.href = '/views/auth/login.php';
                            });
                        });
                    </script>";
                    // Fallback PHP redirect
                    header("Location: /views/auth/login.php");
                    exit();
                } else {
                    $errors[] = "Something went wrong. Please try again later.";
                    error_log("Failed to verify user $email: " . $conn->error, 3, '../../logs/registration_errors.log');
                }
            }
        } else {
            $errors[] = "Email not found or already verified.";
            error_log("Verification failed for $email: Email not found or already verified", 3, '../../logs/registration_errors.log');
        }
        $stmt->close();
    } else {
        $errors[] = "Session expired or invalid email. Please register again.";
        error_log("Verification failed: Empty email or session for code $submitted_code", 3, '../../logs/registration_errors.log');
    }
    
    // Show errors if any
    if (!empty($errors)) {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Verification Failed',
                    html: '" . implode("<br>", array_map("htmlspecialchars", $errors)) . "',
                    customClass: {
                        confirmButton: 'swal2-confirm'
                    }
                }).then(() => {
                    document.getElementById('registration-form').style.display = 'none';
                    document.getElementById('verification-form').style.display = 'block';
                    setupVerificationInputs();
                });
            });
        </script>";
    }
}

// Process initial registration form data
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['verify_code'])) {
    $email = trim($conn->real_escape_string($_POST['email'] ?? ''));
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['password_confirmation'] ?? '');
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters";
    } elseif (!preg_match("/[A-Z]/", $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    } elseif (!preg_match("/[0-9]/", $password)) {
        $errors[] = "Password must contain at least one number";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    if (empty($errors)) {
        // Check if email exists
        $sql = "SELECT email, is_verified FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if ($user['is_verified'] == FALSE) {
                // Unverified account: Resend verification code
                $verification_code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                
                $sql = "UPDATE users SET verification_code = ?, verification_sent_at = NOW() WHERE email = ?";
                $stmt_update = $conn->prepare($sql);
                $stmt_update->bind_param("ss", $verification_code, $email);
                
                if ($stmt_update->execute()) {
                    if (sendVerificationEmail($email, $verification_code)) {
                        $_SESSION['register_email'] = $email;
                        error_log("Resent verification code $verification_code to unverified email $email", 3, '../../logs/registration_errors.log');
                        echo "<script>
                            document.addEventListener('DOMContentLoaded', function() {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Verification Email Resent',
                                    html: 'A new 6-digit verification code has been sent to <strong>$email</strong>. Please check your inbox and enter the code below.',
                                    confirmButtonText: 'Continue to Verification',
                                    customClass: {
                                        confirmButton: 'swal2-confirm'
                                    }
                                }).then(() => {
                                    document.getElementById('registration-form').style.display = 'none';
                                    document.getElementById('verification-form').style.display = 'block';
                                    setupVerificationInputs();
                                });
                            });
                        </script>";
                    } else {
                        $errors[] = "Failed to resend verification email. Please try again later.";
                        error_log("Failed to resend verification email to $email", 3, '../../logs/registration_errors.log');
                    }
                } else {
                    $errors[] = "Failed to update verification code. Please try again later.";
                    error_log("Failed to update verification code for $email: " . $conn->error, 3, '../../logs/registration_errors.log');
                }
                $stmt_update->close();
            } else {
                $errors[] = "Email already taken and verified.";
            }
        } else {
            // New registration
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $verification_code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $username = generateUsername($email, $conn);
            
            error_log("Generated verification code: $verification_code for $email");
            
            $sql = "INSERT INTO users (username, password, email, verification_code, verification_sent_at) 
                    VALUES (?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $username, $hashed_password, $email, $verification_code);
            
            if ($stmt->execute()) {
                error_log("Stored verification code $verification_code for $email in database");
                if (sendVerificationEmail($email, $verification_code)) {
                    $_SESSION['register_email'] = $email;
                    error_log("Session email set to: $email");
                    
                    echo "<script>
                        document.addEventListener('DOMContentLoaded', function() {
                            Swal.fire({
                                icon: 'success',
                                title: 'Verification Email Sent',
                                html: 'We\'ve sent a 6-digit verification code to <strong>$email</strong>. Please check your inbox and enter the code below.',
                                confirmButtonText: 'Continue to Verification',
                                customClass: {
                                    confirmButton: 'swal2-confirm'
                                }
                            }).then(() => {
                                document.getElementById('registration-form').style.display = 'none';
                                document.getElementById('verification-form').style.display = 'block';
                                setupVerificationInputs();
                            });
                        });
                    </script>";
                } else {
                    $errors[] = "Failed to send verification email. Please check your email address or try again later.";
                    error_log("Failed to send verification email to $email", 3, '../../logs/registration_errors.log');
                }
            } else {
                $errors[] = "Registration failed. Please try again later.";
                error_log("Database insertion failed for $email: " . $conn->error, 3, '../../logs/registration_errors.log');
            }
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
    <title>Cuptain's Brew | Register</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

        /* Form Container */
        .register-container {
            max-width: 450px;
            margin: 3rem auto;
            padding: 2rem;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(74, 59, 43, 0.5);
        }

        .register-container h2 {
            font-size: 1.5rem;
            color: #2C6E8A;
            margin-bottom: 1rem;
            text-align: center;
        }

        .edit-form {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .edit-form label {
            font-size: 0.9rem;
            color: #4a3b2b;
        }

        .edit-form input[type="email"],
        .edit-form input[type="password"] {
            padding: 0.5rem;
            border: none;
            border-radius: 5px;
            background: #A9D6E5;
            color: #4a3b2b;
            font-size: 0.9rem;
            width: 100%;
        }

        .edit-form button {
            padding: 0.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background-color 0.3s;
            background: #2C6E8A;
            color: #fff;
        }

        .edit-form button:hover {
            background: #235A73;
        }

        .options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 1rem 0;
        }

        .options-container {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .options a {
            color: #2C6E8A;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .options a:hover {
            text-decoration: underline;
        }

        .login-link {
            text-align: center;
            margin-top: 1rem;
        }

        .login-link p {
            font-size: 0.9rem;
            color: #4a3b2b;
        }

        .login-link a {
            color: #2C6E8A;
            text-decoration: none;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        /* Verification Form */
        #verification-form {
            display: none;
            animation: fadeIn 0.5s;
        }

        .verification-container {
            text-align: center;
        }

        .verification-instructions {
            margin-bottom: 1.5rem;
            color: #4a3b2b;
        }

        .verification-instructions h3 {
            font-size: 1.2rem;
            color: #2C6E8A;
            margin-bottom: 0.5rem;
        }

        .verification-instructions p {
            font-size: 0.9rem;
        }

        .verification-inputs {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 1.5rem 0;
        }

        .verification-inputs input {
            width: 50px;
            height: 50px;
            text-align: center;
            font-size: 1.2rem;
            border: none;
            border-radius: 5px;
            background: #A9D6E5;
            color: #4a3b2b;
            transition: all 0.3s;
        }

        .verification-inputs input:focus {
            outline: none;
            background: #e9f7fe;
            transform: scale(1.05);
        }

        .verification-actions {
            margin-top: 1.5rem;
        }

        #verify-button {
            padding: 0.5rem 2rem;
            font-size: 0.9rem;
            background: #2C6E8A;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .resend-code {
            margin-top: 1rem;
            font-size: 0.9rem;
            color: #4a3b2b;
        }

        .resend-code a {
            color: #2C6E8A;
            text-decoration: none;
            font-weight: 500;
            cursor: pointer;
        }

        .resend-code a:hover {
            text-decoration: underline;
        }

        .back-to-register {
            margin-top: 1rem;
        }

        .back-to-register a {
            color: #2C6E8A;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .back-to-register a:hover {
            text-decoration: underline;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Footer */
        .footer-container {
            background-color: #2C6E8A;
            color: white;
            padding: 2rem;
            display: flex;
            justify-content: space-between;
        }

        .footer-links a, .footer-contact p {
            color: white;
            text-decoration: none;
        }

        .footer-social img {
            width: 30px;
            margin-right: 10px;
        }

        .footer-bottom {
            background-color: #FFFAEE;
            display: flex;
            flex-direction: column;
            text-align: center;
            padding: 10px;
        }

        /* SweetAlert2 Custom Styling */
        .swal2-confirm {
            background-color: #2C6E8A !important;
            color: #fff !important;
            border-radius: 5px !important;
        }

        .swal2-confirm:hover {
            background-color: #235A73 !important;
        }

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                padding: 2vw;
                text-align: center;
            }

            .logo-section img {
                width: 40vw;
                margin: 0 0 2vw 0;
            }

            .nav-menu {
                flex-direction: column;
                gap: 1vw;
                width: 100%;
            }

            .nav-button {
                padding: 1vw;
                width: 100%;
                font-size: 3vw;
            }

            .register-container {
                width: 95%;
                padding: 2vw;
                margin: 2rem auto;
            }

            .edit-form label {
                font-size: 2.5vw;
            }

            .edit-form input {
                font-size: 2.5vw;
            }

            .edit-form button {
                font-size: 2.5vw;
            }

            .verification-inputs input {
                width: 40px;
                height: 40px;
                font-size: 1rem;
            }

            .footer-container {
                flex-direction: column;
                gap: 2rem;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="logo-section">
            <img src="/public/images/LOGO.png" alt="Cuptain's Brew Logo">
        </div>
        <nav class="nav-menu">
            <button class="nav-button" onclick="window.location.href='/views/home.html'">Home</button>
            <button class="nav-button" onclick="window.location.href='/views/menu.html'">Menu</button>
            <button class="nav-button" onclick="window.location.href='/views/career.html'">Career</button>
            <button class="nav-button" onclick="window.location.href='/views/aboutus.html'">About Us</button>
        </nav>
    </header>

    <div class="register-container">
        <h2>REGISTER</h2>
        
        <!-- Registration Form -->
        <form id="registration-form" class="edit-form" action="register.php" method="POST">
            <label for="email">Email Address</label>
            <input id="email" type="email" name="email" placeholder="Enter Email" value="<?php echo htmlspecialchars($email); ?>" required>

            <label for="password">Password</label>
            <input id="password" type="password" name="password" placeholder="Enter Password" required>

            <label for="password-confirm">Confirm Password</label>
            <input id="password-confirm" type="password" name="password_confirmation" placeholder="Confirm Password" required>
            
            <div class="options">
                <div class="options-container">
                    <input type="checkbox" id="showPassword" onclick="togglePassword()">
                    <label for="showPassword">Show Password</label>
                </div>
                <a href="/views/auth/forgot-password.php">Forgot Password</a>
            </div>

            <button type="submit">Register</button>
        </form>
        
        <!-- Verification Form -->
        <div id="verification-form">
            <div class="verification-container">
                <div class="verification-instructions">
                    <h3>Verify Your Email</h3>
                    <p>We've sent a 6-digit verification code to <strong><?php echo htmlspecialchars($_SESSION['register_email'] ?? ''); ?></strong></p>
                    <p>Please enter the code below to complete your registration.</p>
                </div>
                
                <form action="register.php" method="POST" onsubmit="return combineVerificationCode()">
                    <div class="verification-inputs">
                        <input type="text" maxlength="1" pattern="[0-9]" required>
                        <input type="text" maxlength="1" pattern="[0-9]" required>
                        <input type="text" maxlength="1" pattern="[0-9]" required>
                        <input type="text" maxlength="1" pattern="[0-9]" required>
                        <input type="text" maxlength="1" pattern="[0-9]" required>
                        <input type="text" maxlength="1" pattern="[0-9]" required>
                    </div>
                    
                    <input type="hidden" id="verification_code" name="verification_code">
                    <input type="hidden" name="verify_code" value="1">
                    
                    <div class="verification-actions">
                        <button id="verify-button" type="submit">Verify Account</button>
                    </div>
                </form>
                
                <div class="resend-code">
                    Didn't receive the code? <a href="#" onclick="resendVerificationCode()">Resend Code</a>
                </div>
                
                <div class="back-to-register">
                    <a href="#" onclick="backToRegistration()">← Back to registration</a>
                </div>
            </div>
        </div>
        
        <div class="login-link">
            <p>Already have an account? <a href="/views/auth/login.php">Login here</a></p>
        </div>
    </div>
 
    <footer>
        <div class="footer-container">
            <div class="footer-left">
                <div class="footer-links">
                    <ul class="space-y-2">
                        <li><a href="/views/home.html">Home</a></li>
                        <li><a href="/views/aboutus.html">About Us</a></li>
                    </ul>
                </div>
                <div class="footer-social mt-4">
                    <a href="#"><img src="/public/images/facebook.png" alt="facebook"></a>
                    <a href="#"><img src="/public/images/twitter.png" alt="twitter"></a>
                    <a href="#"><img src="/public/images/instagram.png" alt="instagram"></a>
                </div>
            </div>
            
            <div class="footer-right">
                <div class="footer-contact">
                    <h3 class="text-lg font-bold">CONTACT US</h3>
                    <p>123 Coffee Street, City Name</p>
                    <p><strong>Phone:</strong> +1 800 555 6789</p>
                    <p><strong>E-mail:</strong> support@cuptainsbrew.com</p>
                    <p><strong>Website:</strong> www.cuptainsbrew.com</p>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <p>© Copyright 2025 Cuptain's Brew Cafe. All Rights Reserved.</p>
        </div>
    </footer>

    <script>
        function togglePassword() {
            const password = document.getElementById('password');
            const confirm = document.getElementById('password-confirm');
            const type = password.type === 'password' ? 'text' : 'password';
            password.type = type;
            confirm.type = type;
        }
        
        function setupVerificationInputs() {
            const inputs = document.querySelectorAll('.verification-inputs input');
            
            inputs.forEach((input, index) => {
                if (index === 0) {
                    input.focus();
                }
                
                input.addEventListener('input', function() {
                    if (this.value.length === 1) {
                        if (index < inputs.length - 1) {
                            inputs[index + 1].focus();
                        } else {
                            this.blur();
                            document.getElementById('verify-button').focus();
                        }
                    }
                });
                
                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Backspace' && this.value.length === 0) {
                        if (index > 0) {
                            inputs[index - 1].focus();
                        }
                    }
                });
                
                input.addEventListener('keypress', function(e) {
                    if (e.key < '0' || e.key > '9') {
                        e.preventDefault();
                    }
                });
            });
        }
        
        function combineVerificationCode() {
            const inputs = document.querySelectorAll('.verification-inputs input');
            let code = '';
            inputs.forEach(input => {
                code += input.value;
            });
            
            if (code.length !== 6) {
                Swal.fire({
                    icon: 'error',
                    title: 'Incomplete Code',
                    text: 'Please enter all 6 digits of the verification code.',
                    customClass: {
                        confirmButton: 'swal2-confirm'
                    }
                });
                return false;
            }
            
            document.getElementById('verification_code').value = code;
            return true;
        }
        
        function resendVerificationCode() {
            Swal.fire({
                title: 'Resend Verification Code',
                text: 'Sending a new verification code to your email...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                    
                    fetch('resend_verification.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'email=' + encodeURIComponent('<?php echo $_SESSION['register_email'] ?? ''; ?>')
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Code Resent',
                                text: 'A new verification code has been sent to your email.',
                                customClass: {
                                    confirmButton: 'swal2-confirm'
                                }
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Failed to Resend',
                                text: data.message || 'Failed to resend verification code. Please try again.',
                                customClass: {
                                    confirmButton: 'swal2-confirm'
                                }
                            });
                        }
                    })
                    .catch(error => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'An error occurred while trying to resend the code.',
                            customClass: {
                                confirmButton: 'swal2-confirm'
                            }
                        });
                    });
                }
            });
        }
        
        function backToRegistration() {
            document.getElementById('registration-form').style.display = 'block';
            document.getElementById('verification-form').style.display = 'none';
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('#registration-form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const email = document.getElementById('email').value.trim();
                    const password = document.getElementById('password').value;
                    const confirm = document.getElementById('password-confirm').value;
        
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
                    if (password.length < 8) {
                        e.preventDefault();
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Password must be at least 8 characters!',
                            customClass: {
                                confirmButton: 'swal2-confirm'
                            }
                        });
                        return;
                    }
                    if (!/[A-Z]/.test(password)) {
                        e.preventDefault();
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Password must contain at least one uppercase letter!',
                            customClass: {
                                confirmButton: 'swal2-confirm'
                            }
                        });
                        return;
                    }
                    if (!/[0-9]/.test(password)) {
                        e.preventDefault();
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Password must contain at least one number!',
                            customClass: {
                                confirmButton: 'swal2-confirm'
                            }
                        });
                        return;
                    }
                    if (password !== confirm) {
                        e.preventDefault();
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Passwords do not match!',
                            customClass: {
                                confirmButton: 'swal2-confirm'
                            }
                        });
                        return;
                    }
                });
            }
        
            <?php if (!empty($errors)): ?>
                Swal.fire({
                    icon: 'error',
                    title: '<?php echo (isset($_POST['verify_code']) ? "Verification Failed" : "Registration Failed"); ?>',
                    html: '<?php echo implode("<br>", array_map("htmlspecialchars", $errors)); ?>',
                    customClass: {
                        confirmButton: 'swal2-confirm'
                    }
                });
                
                <?php if (isset($_POST['verify_code'])): ?>
                    document.getElementById('registration-form').style.display = 'none';
                    document.getElementById('verification-form').style.display = 'block';
                    setupVerificationInputs();
                <?php endif; ?>
            <?php endif; ?>
        });
    </script>
    
    <?php
    $conn->close();
    ?>
</body>
</html>