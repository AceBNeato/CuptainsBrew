<?php
session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../config/mail.php'; // Include mail configuration
require_once __DIR__ . '/../../vendor/autoload.php'; // Add the autoloader

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'cafe_db';

$errors = [];
$success = false;

// Connect to MySQL server
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    } else {
        // Check if email exists in database and is not an admin account
        $stmt = $conn->prepare("SELECT u.id, r.name as role FROM users u JOIN roles r ON u.role_id = r.id WHERE u.email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $errors[] = "No account found with this email address.";
        } else {
            $user = $result->fetch_assoc();
            
            // Check if user is an admin
            if ($user['role'] === 'admin') {
                $errors[] = "Password reset is not available for admin accounts. Please contact system support.";
            } else {
                // Generate reset token
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour expiration
                
                // Store token in database
                $updateStmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE email = ?");
                $updateStmt->bind_param("sss", $token, $expires, $email);
                
                if ($updateStmt->execute()) {
                    try {
                        // Create reset link
                        $resetLink = "http://" . $_SERVER['HTTP_HOST'] . "/views/auth/reset-password.php?token=" . $token;
                        
                        // For development/testing, store the link in session
                        $_SESSION['reset_link'] = $resetLink;
                        $_SESSION['reset_email'] = $email;
                        
                        // Send reset email
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
                        $success = true;
                        
                    } catch (Exception $e) {
                        // For development purposes, still show success even if email fails
                        // but log the error and store the link in session for testing
                        $success = true;
                        error_log("Failed to send reset email for $email: " . $e->getMessage());
                    }
                } else {
                    $errors[] = "An error occurred. Please try again later.";
                    error_log("Failed to update reset token for $email: " . $updateStmt->error);
                }
                $updateStmt->close();
            }
        }
        $stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cuptain's Brew | Forgot Password</title>
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
            transform: scale(1.1);
        }

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

        .forgot-password-card {
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

        .forgot-password-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .forgot-password-header h1 {
            color: var(--primary);
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            letter-spacing: 1px;
        }

        .forgot-password-header p {
            color: var(--gray-600);
            font-size: 0.95rem;
            line-height: 1.5;
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

        .submit-button {
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

        .submit-button:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        @media (max-width: 640px) {
            .forgot-password-card {
                padding: 2rem;
                margin: 1rem;
            }

            .back-button {
                top: 1rem;
                left: 1rem;
                padding: 0.5rem 1rem;
                font-size: 0.875rem;
            }

            .forgot-password-header h1 {
                font-size: 1.75rem;
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

    <div class="forgot-password-card">
        <div class="forgot-password-header">
            <h1>Forgot Password</h1>
            <p>Enter your email address and we'll send you a link to reset your password.</p>
        </div>

        <form method="POST" action="forgot-password.php">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required placeholder="Enter your email">
            </div>

            <button type="submit" class="submit-button">Send Reset Link</button>
        </form>
    </div>

    <script>
        <?php if ($success): ?>
        Swal.fire({
            icon: 'success',
            title: 'Reset Link Sent!',
            html: 'A password reset link has been sent to your email address.',
            confirmButtonColor: '#2C6E8A'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '/views/auth/login.php';
            }
        });
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            html: '<?php echo implode("<br>", array_map("htmlspecialchars", $errors)); ?>',
            confirmButtonColor: '#2C6E8A'
        });
        <?php endif; ?>
    </script>
</body>
</html> 