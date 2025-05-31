<?php
require_once __DIR__ . '/../config.php';
session_start();

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'] ?? 'customer'; // Default to customer if not set

// Get notification ID from request
$notification_id = isset($_POST['notification_id']) ? (int)$_POST['notification_id'] : null;
$mark_all = isset($_POST['mark_all']) ? (bool)$_POST['mark_all'] : false;

$response = [
    'success' => false
];

try {
    if ($mark_all) {
        // Mark all notifications as read based on user type
        if ($user_type === 'admin') {
            // For admin, mark all orders as viewed
            $query = "UPDATE orders SET is_viewed = 1 WHERE is_viewed = 0";
            $stmt = $conn->prepare($query);
            $stmt->execute();
        } else {
            // For customers, mark all their notifications as read
            $query = "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
        }
        
        $response['success'] = true;
        $response['message'] = 'All notifications marked as read';
    } elseif ($notification_id) {
        // Mark a specific notification as read
        if ($user_type === 'admin') {
            // For admin, mark the specific order as viewed
            $query = "UPDATE orders SET is_viewed = 1 WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $notification_id);
            $stmt->execute();
        } else {
            // For customers, mark the specific notification as read
            $query = "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $notification_id, $user_id);
            $stmt->execute();
        }
        
        $response['success'] = true;
        $response['message'] = 'Notification marked as read';
    } else {
        $response['error'] = 'Invalid request parameters';
    }
} catch (Exception $e) {
    $response['error'] = 'Error marking notification as read: ' . $e->getMessage();
}

echo json_encode($response);
exit;
?> 