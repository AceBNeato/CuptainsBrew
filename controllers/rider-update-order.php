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

// Verify rider is logged in
if (!isRiderLoggedIn()) {
    logRiderError("Unauthorized access attempt");
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get rider ID
$rider_id = getCurrentRiderId();

// Get request data
$data = json_decode(file_get_contents('php://input'), true);

// Verify CSRF token
if (!isset($data['csrf_token']) || $data['csrf_token'] !== $_SESSION['csrf_token']) {
    logRiderError("Invalid CSRF token from rider ID: $rider_id");
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid security token']);
    exit();
}

// Validate input data
if (!isset($data['order_id']) || !isset($data['status'])) {
    logRiderError("Missing parameters in request from rider ID: $rider_id");
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

$order_id = (int)$data['order_id'];
$status = $conn->real_escape_string($data['status']);

// Validate status
$allowed_statuses = ['Out for Delivery', 'Delivered'];
if (!in_array($status, $allowed_statuses)) {
    logRiderError("Invalid status: $status requested by rider ID: $rider_id for order ID: $order_id");
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit();
}

try {
    // Verify order belongs to the rider
    $check_query = "SELECT * FROM orders WHERE id = $order_id AND rider_id = $rider_id";
    $check_result = $conn->query($check_query);
    
    if (!$check_result) {
        throw new Exception('Database error: ' . $conn->error);
    }
    
    if ($check_result->num_rows === 0) {
        throw new Exception('Order not found or not assigned to you');
    }
    
    $order = $check_result->fetch_assoc();
    
    // Validate status transition
    if ($status === 'Out for Delivery' && $order['status'] !== 'Assigned') {
        throw new Exception('Order must be in Assigned status to start delivery');
    } else if ($status === 'Delivered' && $order['status'] !== 'Out for Delivery') {
        throw new Exception('Order must be in Out for Delivery status to mark as delivered');
    }
    
    // Update order status
    $update_query = "UPDATE orders SET status = '$status', updated_at = NOW() WHERE id = $order_id";
    $update_result = $conn->query($update_query);
    
    if (!$update_result) {
        throw new Exception('Failed to update order status: ' . $conn->error);
    }
    
    // Create notification for the user when order is out for delivery
    if ($status === 'Out for Delivery') {
        // Get the user ID from the order
        $user_query = "SELECT user_id FROM orders WHERE id = $order_id";
        $user_result = $conn->query($user_query);
        
        if ($user_result && $user_result->num_rows > 0) {
            $user_data = $user_result->fetch_assoc();
            $user_id = $user_data['user_id'];
            
            // Check if notifications table exists
            $notif_check = $conn->query("SHOW TABLES LIKE 'notifications'");
            if ($notif_check->num_rows > 0) {
                // Create notification for user
                $title = 'Order Out for Delivery';
                $message = "Your order #$order_id is now out for delivery. You can cancel this order within 5 minutes if needed.";
                
                // Check the structure of the notifications table
                $columns_check = $conn->query("SHOW COLUMNS FROM notifications");
                $columns = [];
                while ($column = $columns_check->fetch_assoc()) {
                    $columns[] = $column['Field'];
                }
                
                // Insert notification based on available columns
                if (in_array('status', $columns)) {
                    $notif_sql = "INSERT INTO notifications (user_id, title, message, order_id, status) 
                                 VALUES (?, ?, ?, ?, ?)";
                    $notif_stmt = $conn->prepare($notif_sql);
                    $notif_stmt->bind_param('issis', $user_id, $title, $message, $order_id, $status);
                } else {
                    $notif_sql = "INSERT INTO notifications (user_id, title, message, order_id) 
                                 VALUES (?, ?, ?, ?)";
                    $notif_stmt = $conn->prepare($notif_sql);
                    $notif_stmt->bind_param('issi', $user_id, $title, $message, $order_id);
                }
                
                $notif_stmt->execute();
                $notif_stmt->close();
            }
        }
    }
    
    // Check if order_status_logs table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'order_status_logs'");
    
    if ($table_check && $table_check->num_rows > 0) {
        // Log the status change if table exists
    $log_query = "INSERT INTO order_status_logs (order_id, status, updated_by, updated_by_type, created_at)
                 VALUES ($order_id, '$status', $rider_id, 'rider', NOW())";
    $log_result = $conn->query($log_query);
    
    if (!$log_result) {
        logRiderError("Failed to insert into order_status_logs: " . $conn->error);
        }
    } else {
        // Create the table if it doesn't exist
        $create_table_query = "CREATE TABLE IF NOT EXISTS order_status_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            status VARCHAR(50) NOT NULL,
            updated_by INT NOT NULL,
            updated_by_type ENUM('admin', 'rider', 'system') NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
        )";
        
        $create_result = $conn->query($create_table_query);
        
        if ($create_result) {
            // Now try to insert the log
            $log_query = "INSERT INTO order_status_logs (order_id, status, updated_by, updated_by_type, created_at)
                         VALUES ($order_id, '$status', $rider_id, 'rider', NOW())";
            $conn->query($log_query);
        }
    }
    
    logRiderError("Order ID: $order_id status updated to $status by rider ID: $rider_id");
    
    // Return success response
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Order status updated successfully']);
    
} catch (Exception $e) {
    logRiderError("Error updating order ID: $order_id - " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 