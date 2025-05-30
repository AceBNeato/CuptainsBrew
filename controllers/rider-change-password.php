<?php
// Include the database configuration
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/rider_auth.php';

// Ensure session is started
if (!isset($_SESSION)) {
    session_start();
}

// Set content type to JSON
header('Content-Type: application/json');

// Function to log errors
function logPasswordError($message) {
    $log_file = __DIR__ . '/../views/riders/error.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] $message\n";
    file_put_contents($log_file, $log_message, FILE_APPEND);
}

// Check if rider is logged in
if (!isRiderLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => 'You must be logged in to change your password.'
    ]);
    exit();
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    logPasswordError("Invalid CSRF token on password change attempt from rider ID: {$_SESSION['rider_id']}");
    echo json_encode([
        'success' => false,
        'message' => 'Invalid security token. Please refresh the page and try again.'
    ]);
    exit();
}

// Get rider ID from session
$rider_id = $_SESSION['rider_id'];

// Validate input
if (!isset($_POST['current_password']) || !isset($_POST['new_password'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields.'
    ]);
    exit();
}

$current_password = $_POST['current_password'];
$new_password = $_POST['new_password'];

// Validate new password
if (strlen($new_password) < 6) {
    echo json_encode([
        'success' => false,
        'message' => 'New password must be at least 6 characters long.'
    ]);
    exit();
}

try {
    // Get rider's current password
    $stmt = $conn->prepare("SELECT password FROM riders WHERE id = ?");
    $stmt->bind_param("i", $rider_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Rider not found");
    }
    
    $rider = $result->fetch_assoc();
    
    // Verify current password
    if ($rider['password'] === null) {
        // First time password change (no password set yet)
        logRiderActivity($rider_id, "First time password setup");
    } else if (!password_verify($current_password, $rider['password'])) {
        logPasswordError("Failed password change attempt - incorrect current password for rider ID: $rider_id");
        echo json_encode([
            'success' => false,
            'message' => 'Current password is incorrect.'
        ]);
        exit();
    }
    
    // Hash new password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    // Update password in database
    $update_stmt = $conn->prepare("UPDATE riders SET password = ? WHERE id = ?");
    $update_stmt->bind_param("si", $hashed_password, $rider_id);
    
    if (!$update_stmt->execute()) {
        throw new Exception("Failed to update password: " . $conn->error);
    }
    
    // Log successful password change
    logRiderActivity($rider_id, "Password changed successfully");
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Password updated successfully.'
    ]);
    
} catch (Exception $e) {
    logPasswordError("Error during password change for rider ID $rider_id: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while changing your password. Please try again later.'
    ]);
}
?> 