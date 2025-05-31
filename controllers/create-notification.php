<?php
require_once __DIR__ . '/../config.php';
session_start();

header('Content-Type: application/json');

// Check if user is logged in with appropriate permissions
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Get POST data
$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$message = isset($_POST['message']) ? trim($_POST['message']) : '';
$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
$status = isset($_POST['status']) ? trim($_POST['status']) : '';

// Alternative: Get JSON data if not POST
if (empty($user_id) && empty($title) && empty($message)) {
    $json_data = json_decode(file_get_contents('php://input'), true);
    if ($json_data) {
        $user_id = isset($json_data['user_id']) ? (int)$json_data['user_id'] : 0;
        $title = isset($json_data['title']) ? trim($json_data['title']) : '';
        $message = isset($json_data['message']) ? trim($json_data['message']) : '';
        $order_id = isset($json_data['order_id']) ? (int)$json_data['order_id'] : 0;
        $status = isset($json_data['status']) ? trim($json_data['status']) : '';
    }
}

// Validate inputs
if ($user_id <= 0 || empty($title) || empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    // Check if the notifications table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'notifications'");
    if ($table_check->num_rows === 0) {
        // Create notifications table if it doesn't exist
        $create_table_sql = "CREATE TABLE notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            is_read TINYINT(1) DEFAULT 0,
            order_id INT DEFAULT NULL,
            status VARCHAR(50) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (user_id),
            INDEX (order_id)
        )";
        
        if (!$conn->query($create_table_sql)) {
            throw new Exception("Failed to create notifications table: " . $conn->error);
        }
    }
    
    // Check if the status column exists
    $status_column_check = $conn->query("SHOW COLUMNS FROM notifications LIKE 'status'");
    $has_status_column = $status_column_check->num_rows > 0;
    
    // Insert notification
    if ($has_status_column && !empty($status)) {
        $sql = "INSERT INTO notifications (user_id, title, message, order_id, status) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('issis', $user_id, $title, $message, $order_id, $status);
    } else {
        $sql = "INSERT INTO notifications (user_id, title, message, order_id) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('issi', $user_id, $title, $message, $order_id);
    }
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to create notification: " . $stmt->error);
    }
    
    $notification_id = $stmt->insert_id;
    $stmt->close();
    
    // For order cancellations, also create notification for admin
    if (stripos($title, 'cancel') !== false && $order_id > 0) {
        // Check if user is not an admin (to avoid duplicate notifications)
        $is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
        
        if (!$is_admin) {
            // Get all admin user IDs
            $admin_query = "SELECT id FROM users WHERE role = 'admin'";
            $admin_result = $conn->query($admin_query);
            
            if ($admin_result && $admin_result->num_rows > 0) {
                while ($admin = $admin_result->fetch_assoc()) {
                    $admin_id = $admin['id'];
                    
                    // Create notification for each admin
                    if ($has_status_column && !empty($status)) {
                        $admin_sql = "INSERT INTO notifications (user_id, title, message, order_id, status) 
                                     VALUES (?, 'Order Cancelled', ?, ?, 'Cancelled')";
                        $admin_stmt = $conn->prepare($admin_sql);
                        $admin_stmt->bind_param('isi', $admin_id, $message, $order_id);
                    } else {
                        $admin_sql = "INSERT INTO notifications (user_id, title, message, order_id) 
                                     VALUES (?, 'Order Cancelled', ?, ?)";
                        $admin_stmt = $conn->prepare($admin_sql);
                        $admin_stmt->bind_param('isi', $admin_id, $message, $order_id);
                    }
                    
                    $admin_stmt->execute();
                    $admin_stmt->close();
                }
            }
        }
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'Notification created successfully',
        'notification_id' => $notification_id
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    error_log("Notification creation error: " . $e->getMessage(), 3, __DIR__ . '/notification.log');
}

$conn->close();
?> 