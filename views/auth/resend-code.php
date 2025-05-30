<?php
// Prevent any output before headers
error_reporting(E_ALL);
ini_set('display_errors', 0);

session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../config/mail.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Start output buffering
ob_start();

try {
    // Database configuration
    $db_host = 'localhost';
    $db_user = 'root';
    $db_pass = '';
    $db_name = 'cafe_db';

    // Set JSON header
    header('Content-Type: application/json');

    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Get and validate email
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email address');
    }

    // Connect to database
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    // Check if user exists and is unverified
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND is_verified = 0");
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }

    $stmt->bind_param("s", $email);
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute query: " . $stmt->error);
    }

    $result = $stmt->get_result();
    if ($result->num_rows !== 1) {
        throw new Exception('No unverified account found with this email');
    }

    // Generate new verification code
    $verification_code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

    // Update verification code and sent time
    $updateStmt = $conn->prepare("UPDATE users SET verification_code = ?, verification_sent_at = NOW() WHERE email = ?");
    if (!$updateStmt) {
        throw new Exception("Failed to prepare update statement: " . $conn->error);
    }

    $updateStmt->bind_param("ss", $verification_code, $email);
    if (!$updateStmt->execute()) {
        throw new Exception("Failed to update verification code: " . $updateStmt->error);
    }

    // Send verification email
    function sendVerificationEmail($email, $code) {
        global $mail_config;
        
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->SMTPDebug = $mail_config['debug_level'];
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

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Email Verification Code';
            $mail->Body = "Your verification code is: <b>$code</b><br>Please enter this code to verify your email address.";
            $mail->AltBody = "Your verification code is: $code\nPlease enter this code to verify your email address.";

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("PHPMailer Error for $email: " . $mail->ErrorInfo, 3, __DIR__ . '/../../logs/phpmailer_errors.log');
            return false;
        }
    }

    if (sendVerificationEmail($email, $verification_code)) {
        // Update session
        $_SESSION['register_email'] = $email;

        // Log success
        error_log("Successfully resent verification code to $email", 3, __DIR__ . '/../../logs/verification.log');

        // Clear any buffered output
        ob_clean();

        // Return success response
        echo json_encode(['success' => true, 'message' => 'Verification code sent successfully']);
    } else {
        throw new Exception("Failed to send verification email");
    }

} catch (Exception $e) {
    // Log error
    error_log("Error in resend-code.php: " . $e->getMessage(), 3, __DIR__ . '/../../logs/error.log');
    
    // Clear any buffered output
    ob_clean();
    
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);

} finally {
    // Clean up resources
    if (isset($stmt)) $stmt->close();
    if (isset($updateStmt)) $updateStmt->close();
    if (isset($conn)) $conn->close();
}
?> 