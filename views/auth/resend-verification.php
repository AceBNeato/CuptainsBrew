<?php
session_start();

header('Content-Type: application/json');

// Require Composer's autoloader
require_once('register.php');

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'cafe_db';

// Connect to MySQL server
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$email = $_POST['email'] ?? '';
if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Email is required']);
    exit;
}

// Generate new verification code
$verification_code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

// Update the verification code in database
$sql = "UPDATE users SET verification_code = ?, verification_sent_at = NOW() WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $verification_code, $email);

if ($stmt->execute()) {
    // Include the sendVerificationEmail function
    require_once('register.php');
    
    if (sendVerificationEmail($email, $verification_code)) {
        echo json_encode(['success' => true]);
        error_log("Resent verification email to $email", 3, '../../logs/registration_errors.log');
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send email']);
        error_log("Failed to resend verification email to $email", 3, '../../logs/registration_errors.log');
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update verification code']);
    error_log("Failed to update verification code for $email: " . $conn->error, 3, '../../logs/registration_errors.log');
}

$stmt->close();
$conn->close();
?>