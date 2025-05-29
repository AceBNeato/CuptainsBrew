<?php
// Include the database configuration
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/rider_auth.php';

// Error logging function
function logRiderError($message) {
    $log_file = __DIR__ . '/../views/riders/error.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] $message\n";
    file_put_contents($log_file, $log_message, FILE_APPEND);
}

// Ensure session is started
if (!isset($_SESSION)) {
    session_start();
}

// Get rider ID for logging
$rider_id = isset($_SESSION['rider_id']) ? $_SESSION['rider_id'] : 'unknown';

// Verify CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    logRiderError("Invalid CSRF token on logout attempt from rider ID: $rider_id");
    header('Location: /views/riders/login.php?error=Invalid security token');
    exit();
}

// Log the logout
logRiderError("Rider ID: $rider_id logged out successfully");

// Logout rider
logoutRider();
?> 