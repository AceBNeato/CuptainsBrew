<?php
session_start();

require '../../vendor/autoload.php';
require_once '../../config/mail.php'; // Include mail configuration

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
    global $mail_config; // Access the mail configuration
    
    $mail = new PHPMailer(true);
    try {
        // Enable debug output
        $mail->SMTPDebug = $mail_config['debug_level'];
        $mail->Debugoutput = function($str, $level) {
            error_log("PHPMailer Debug [$level]: $str\n", 3, '../../logs/phpmailer_debug.log');
        };

        // Server settings
        $mail->isSMTP();
        $mail->Host = $mail_config['smtp_host'];
        $mail->SMTPAuth = $mail_config['smtp_auth'];
        $mail->Username = $mail_config['smtp_username'];
        $mail->Password = $mail_config['smtp_password'];
        $mail->SMTPSecure = $mail_config['smtp_secure'];
        $mail->Port = $mail_config['smtp_port'];

        // Recipients
        $mail->setFrom($mail_config['from_email'], $mail_config['from_name']);
        $mail->addAddress($email);
        $mail->addReplyTo($mail_config['from_email'], $mail_config['from_name'] . " Support");

        // Content
        $mail->isHTML(true);
        $mail->Subject = "Cuptain's Brew - Email Verification";
        $mail->Body = "
        <html>
        <head>
            <title>Email Verification</title>
            <style>
                body { font-family: 'Poppins', Arial, sans-serif; line-height: 1.6; color: #4a3b2b; margin: 0; padding: 0; background-color: #f9f9f9; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #2C6E8A; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { padding: 30px; background-color: #FFFAEE; border-left: 1px solid #e0e0e0; border-right: 1px solid #e0e0e0; }
                .footer { margin-top: 0; text-align: center; font-size: 0.8em; color: #777; background-color: #f5f5f5; padding: 15px; border-radius: 0 0 10px 10px; }
                .verification-code-container { 
                    text-align: center;
                    margin: 25px 0;
                    padding: 0;
                }
                .verification-code { 
                    font-size: 32px; 
                    font-weight: bold; 
                    letter-spacing: 5px; 
                    color: #2C6E8A;
                    text-align: center;
                    margin: 0;
                    padding: 15px;
                    background-color: #A9D6E5;
                    border-radius: 10px;
                    border: 2px dashed #2C6E8A;
                    display: inline-block;
                }
                .important-note {
                    background-color: #FFECB3;
                    border-left: 4px solid #FFC107;
                    padding: 10px 15px;
                    margin: 20px 0;
                    border-radius: 4px;
                }
                h3 { color: #2C6E8A; }
                p { margin-bottom: 15px; }
                .btn {
                    display: inline-block;
                    padding: 10px 20px;
                    background-color: #2C6E8A;
                    color: white;
                    text-decoration: none;
                    border-radius: 5px;
                    font-weight: bold;
                    margin-top: 10px;
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
                    <p>Thank you for registering with Cuptain's Brew. To ensure the security of your account, please use the verification code below to complete your registration:</p>
                    
                    <div class='verification-code-container'>
                    <div class='verification-code'>$verification_code</div>
                    </div>
                    
                    <div class='important-note'>
                        <strong>IMPORTANT:</strong> This code will expire in 30 minutes. If you didn't request this verification, please ignore this email.
                    </div>
                    
                    <p>After verifying your email, you'll be able to enjoy our full menu of delicious coffee beverages and food items.</p>
                    
                    <p>Thank you for choosing Cuptain's Brew!</p>
                </div>
                <div class='footer'>
                    <p>© " . date('Y') . " Cuptain's Brew. All rights reserved.</p>
                    <p>This is an automated message, please do not reply to this email.</p>
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
                $errors[] = "Verification code has expired.";
                echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Code Expired',
                            text: 'Your verification code has expired. Would you like to request a new one?',
                            showCancelButton: true,
                            confirmButtonText: 'Yes, send new code',
                            cancelButtonText: 'No, cancel',
                            customClass: {
                                confirmButton: 'swal2-confirm',
                                cancelButton: 'swal2-cancel'
                            }
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // Send AJAX request to resend code
                                fetch('/views/auth/resend-code.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/x-www-form-urlencoded',
                                    },
                                    body: 'email=' + encodeURIComponent('$email')
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'Code Sent!',
                                            text: 'A new verification code has been sent to your email.',
                                            customClass: {
                                                confirmButton: 'swal2-confirm'
                                            }
                                        });
                                    } else {
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Error',
                                            text: data.message || 'Failed to send new code. Please try again.',
                                            customClass: {
                                                confirmButton: 'swal2-confirm'
                                            }
                                        });
                                    }
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: 'Failed to send new code. Please try again.',
                                        customClass: {
                                            confirmButton: 'swal2-confirm'
                                        }
                                    });
                                });
                            }
                        });
                    });
                </script>";
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
            filter: brightness(50%) blur(8px);
            transform: scale(1.1); /* Prevent blur edges from showing */
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
            background: rgba(44, 110, 138, 0.9);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            color: var(--white);
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 500;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 10;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .back-button:hover {
            background: rgba(35, 90, 115, 0.95);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .back-button i {
            font-size: 1rem;
        }

        .register-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px) saturate(180%);
            -webkit-backdrop-filter: blur(12px) saturate(180%);
            padding: 2.5rem;
            border-radius: 1rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 420px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            margin: 2rem auto;
        }

        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .register-header h1 {
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

        .register-button {
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
            margin-top: 1rem;
        }

        .register-button:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            color: var(--gray-600);
            font-size: 0.95rem;
        }

        .login-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .login-link a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        /* Verification Form */
        #verification-form {
            display: none;
            animation: fadeIn 0.5s;
        }

        .verification-container {
            text-align: center;
            margin-top: 2rem;
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
            margin-bottom: 0.5rem;
        }

        .verification-inputs {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin: 1.5rem 0;
            flex-wrap: wrap;
        }

        .verification-inputs input {
            width: 45px;
            height: 45px;
            text-align: center;
            font-size: 1.2rem;
            border: 1px solid var(--gray-300);
            border-radius: 0.5rem;
            background: rgba(255, 255, 255, 0.9);
            transition: all 0.3s ease;
            padding: 0;
            -webkit-appearance: none;
            -moz-appearance: textfield;
        }

        .verification-inputs input::-webkit-outer-spin-button,
        .verification-inputs input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .verification-inputs input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(44, 110, 138, 0.1);
            background: var(--white);
            transform: scale(1.05);
        }

        .verification-actions {
            margin-top: 1.5rem;
        }

        #verify-button {
            padding: 0.75rem 2rem;
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
            width: 100%;
            max-width: 250px;
        }

        #verify-button:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .resend-code {
            margin-top: 1.5rem;
            font-size: 0.95rem;
            color: var(--gray-600);
            padding: 1rem;
            background-color: rgba(44, 110, 138, 0.1);
            border-radius: 0.5rem;
            text-align: center;
        }

        .resend-code a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-block;
            margin-left: 0.5rem;
            padding: 0.25rem 0.75rem;
            background-color: rgba(44, 110, 138, 0.2);
            border-radius: 0.25rem;
        }

        .resend-code a:hover {
            color: var(--primary-dark);
            background-color: rgba(44, 110, 138, 0.3);
            transform: translateY(-1px);
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Responsive Design */
        @media (max-width: 640px) {
            .register-card {
            padding: 2rem;
                margin: 1rem;
            }

            .back-button {
                top: 1rem;
                left: 1rem;
                padding: 0.5rem 1rem;
                font-size: 0.875rem;
            }

            .register-header h1 {
                font-size: 1.75rem;
            }

            .form-group label {
                font-size: 0.875rem;
            }

            .form-group input {
                padding: 0.75rem;
                font-size: 0.95rem;
            }

            .verification-inputs {
                gap: 0.35rem;
            }

            .verification-inputs input {
                width: 40px;
                height: 40px;
                font-size: 1rem;
            }
        }

        @media (max-width: 380px) {
            .register-card {
                padding: 1.5rem;
            }

            .verification-inputs input {
                width: 35px;
                height: 35px;
                font-size: 0.9rem;
            }

            .verification-instructions h3 {
                font-size: 1.1rem;
            }

            .verification-instructions p {
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>
    <div class="image-container">
        <img src="/public/images/background/login.jpg" alt="Background" id="getstarted">
        </div>

    <a href="/views/auth/login.php" class="back-button">
        <i class="fas fa-arrow-left"></i>
        Back to Login
    </a>

    <div class="register-card">
        <div class="register-header">
            <h1>REGISTER</h1>
        </div>
        
        <!-- Registration Form -->
        <form id="registration-form" class="edit-form" action="register.php" method="POST">
            <div class="form-group">
            <label for="email">Email Address</label>
                <input id="email" type="email" name="email" placeholder="Enter your email" value="<?php echo htmlspecialchars($email); ?>" required>
            </div>

            <div class="form-group">
            <label for="password">Password</label>
                <div class="password-field">
                    <input id="password" type="password" name="password" placeholder="Enter your password" required>
                    <i class="fas fa-eye password-toggle" onclick="togglePassword('password')"></i>
                </div>
            </div>

            <div class="form-group">
            <label for="password-confirm">Confirm Password</label>
                <div class="password-field">
                    <input id="password-confirm" type="password" name="password_confirmation" placeholder="Confirm your password" required>
                    <i class="fas fa-eye password-toggle" onclick="togglePassword('password-confirm')"></i>
                </div>
            </div>

            <button type="submit" class="register-button">Register</button>
        </form>
        
        <!-- Verification Form -->
        <div id="verification-form">
            <div class="verification-container">
                <div class="verification-instructions">
                    <h3>Verify Your Email</h3>
                    <p>We've sent a 6-digit verification code to <strong><?php echo htmlspecialchars($_SESSION['register_email'] ?? ''); ?></strong></p>
                    <p>Please check your email inbox (including spam/junk folder) for an email with <strong>IMPORTANT</strong> verification code and enter it below.</p>
                </div>
                
                <form action="register.php" method="POST" onsubmit="return combineVerificationCode()">
                    <div class="verification-inputs">
                        <input type="number" min="0" max="9" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
                        <input type="number" min="0" max="9" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
                        <input type="number" min="0" max="9" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
                        <input type="number" min="0" max="9" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
                        <input type="number" min="0" max="9" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
                        <input type="number" min="0" max="9" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
                    </div>
                    
                    <input type="hidden" id="verification_code" name="verification_code">
                    <input type="hidden" name="verify_code" value="1">
                    
                    <div class="verification-actions">
                        <button id="verify-button" type="submit">Verify Account</button>
                    </div>
                </form>
                
                <div class="resend-code">
                    No verification code in your inbox or spam folder? <a href="#" onclick="resendVerificationCode()">Resend Verification Code</a>
                </div>
            </div>
        </div>
        
        <div class="login-link">
            <p>Already have an account? <a href="/views/auth/login.php">Login here</a></p>
        </div>
    </div>

    <script>
        function togglePassword(id) {
            const input = document.getElementById(id);
            const icon = input.nextElementSibling;
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        function setupVerificationInputs() {
            const inputs = document.querySelectorAll('.verification-inputs input');
            
            inputs.forEach((input, index) => {
                // Focus first input on load
                if (index === 0) {
                    input.focus();
                }
                
                // Handle input event
                input.addEventListener('input', function(e) {
                    // Ensure only one digit
                    if (this.value.length > 1) {
                        this.value = this.value.slice(0, 1);
                    }
                    
                    // Move to next input when filled
                    if (this.value.length === 1) {
                        if (index < inputs.length - 1) {
                            inputs[index + 1].focus();
                        } else {
                            this.blur();
                            document.getElementById('verify-button').focus();
                        }
                    }
                });
                
                // Handle paste event for pasting the whole code at once
                input.addEventListener('paste', function(e) {
                    e.preventDefault();
                    const pastedText = (e.clipboardData || window.clipboardData).getData('text');
                    if (/^\d{6}$/.test(pastedText)) {
                        // If we have a 6-digit number, fill all inputs
                        for (let i = 0; i < 6; i++) {
                            inputs[i].value = pastedText.charAt(i);
                        }
                        // Focus the submit button
                        document.getElementById('verify-button').focus();
                    }
                });
                
                // Handle keyboard navigation
                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Backspace' && this.value.length === 0) {
                        if (index > 0) {
                            inputs[index - 1].focus();
                        }
                    } else if (e.key === 'ArrowLeft') {
                        if (index > 0) {
                            inputs[index - 1].focus();
                        }
                    } else if (e.key === 'ArrowRight') {
                        if (index < inputs.length - 1) {
                            inputs[index + 1].focus();
                        }
                    }
                });
                
                // Allow only digits
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
            const email = '<?php echo $_SESSION['register_email'] ?? ''; ?>';
            if (!email) {
            Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No email address found in session. Please try registering again.',
                    customClass: {
                        confirmButton: 'swal2-confirm'
                    }
                });
                return;
            }

            Swal.fire({
                title: 'Resending Code',
                text: 'Please wait...',
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                    
                    fetch('/views/auth/resend-code.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'email=' + encodeURIComponent(email)
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            // Clear existing inputs
                            const inputs = document.querySelectorAll('.verification-inputs input');
                            inputs.forEach(input => input.value = '');
                            inputs[0].focus();
                            
                            Swal.fire({
                                icon: 'success',
                                title: 'Code Sent!',
                                text: 'A new verification code has been sent to your email.',
                                customClass: {
                                    confirmButton: 'swal2-confirm'
                                }
                            });
                        } else {
                            throw new Error(data.message || 'Failed to send verification code');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: error.message || 'Failed to send verification code. Please try again.',
                            customClass: {
                                confirmButton: 'swal2-confirm'
                            }
                        });
                    });
                }
            });
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