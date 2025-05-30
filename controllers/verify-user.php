<?php
// Prevent any output before headers
error_reporting(E_ALL);
ini_set('display_errors', 0);

session_start();
require_once __DIR__ . '/../config.php';

// Start output buffering
ob_start();

try {
    // Set JSON header
    header('Content-Type: application/json');

    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Get and validate code
    $code = $_POST['code'] ?? '';
    if (empty($code)) {
        throw new Exception('Verification code is required');
    }

    // Get user ID from session
    $user_id = $_SESSION['unverified_user_id'] ?? 0;
    if (!$user_id) {
        throw new Exception('No user to verify');
    }

    // Connect to database
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    // Check verification code
    $stmt = $conn->prepare("SELECT id, username, verification_code, role_id FROM users WHERE id = ? AND verification_code = ?");
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }

    $stmt->bind_param("is", $user_id, $code);
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute query: " . $stmt->error);
    }

    $result = $stmt->get_result();
    if ($result->num_rows !== 1) {
        throw new Exception('Invalid verification code');
    }

    $user = $result->fetch_assoc();

    // Mark user as verified
    $updateStmt = $conn->prepare("UPDATE users SET is_verified = 1, verification_code = NULL, email_verified_at = NOW() WHERE id = ?");
    if (!$updateStmt) {
        throw new Exception("Failed to prepare update statement: " . $conn->error);
    }

    $updateStmt->bind_param("i", $user_id);
    if (!$updateStmt->execute()) {
        throw new Exception("Failed to update user: " . $updateStmt->error);
    }

    // Get role name
    $roleStmt = $conn->prepare("SELECT name FROM roles WHERE id = ?");
    $roleStmt->bind_param("i", $user['role_id']);
    $roleStmt->execute();
    $roleResult = $roleStmt->get_result();
    $role = $roleResult->fetch_assoc()['name'] ?? 'user';

    // Log in the user
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $role;
    $_SESSION['loggedin'] = true;
    
    // Clear unverified user session data
    unset($_SESSION['unverified_user_id']);
    unset($_SESSION['unverified_email']);

    // Return success response with redirect URL
    $redirect = ($role === 'admin') ? '/views/admin/Admin-Menu.php' : '/views/users/User-Home.php';
    
    // Clear any buffered output
    ob_clean();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Your email has been verified successfully',
        'redirect' => $redirect
    ]);

} catch (Exception $e) {
    // Log error
    error_log("Error in verify-user.php: " . $e->getMessage(), 3, __DIR__ . '/error.log');
    
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
    if (isset($roleStmt)) $roleStmt->close();
    if (isset($conn)) $conn->close();
}
?> 