<?php
session_start();

$config_path = __DIR__ . '/../config.php';

if (!file_exists($config_path)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => "Error: config.php not found. Please check the file path."]);
    exit;
}

require_once $config_path;

header('Content-Type: application/json');
ini_set('display_errors', 0);

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$user_id = $_SESSION['user_id'];
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// Handle both POST and JSON input
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST)) {
    $order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
    $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
    $custom_reason = isset($_POST['custom_reason']) ? trim($_POST['custom_reason']) : '';
} else {
    $data = json_decode(file_get_contents('php://input'), true);
    $order_id = isset($data['order_id']) ? (int)$data['order_id'] : 0;
    $reason = isset($data['reason']) ? trim($data['reason']) : '';
    $custom_reason = isset($data['custom_reason']) ? trim($data['custom_reason']) : '';
}

// Use custom reason if provided, otherwise use the selected reason
if (!empty($custom_reason)) {
    $final_reason = $custom_reason;
} else {
    $final_reason = $reason;
}

if ($order_id <= 0 || empty($final_reason)) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID or reason']);
    exit;
}

try {
    $conn->begin_transaction();
    
    // Check if order exists and get its status and updated_at timestamp
    $check_sql = "SELECT id, user_id, status, updated_at FROM orders WHERE id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param('i', $order_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Order not found');
    }
    
    $order = $result->fetch_assoc();
    $check_stmt->close();
    
    // If not admin, verify the order belongs to the user
    if (!$is_admin && $order['user_id'] != $user_id) {
        throw new Exception('You are not authorized to cancel this order');
    }
    
    // Check if order is in a cancellable state
    $allowed_statuses = ['Pending'];
    $can_cancel = true;
    $cancel_message = '';
    
    // Check if order is "Out for Delivery" and if it's within the 5-minute window
    if ($order['status'] === 'Out for Delivery') {
        // For now, always allow cancellation for Out for Delivery orders
        // The UI already restricts this to a 5-minute window
        $allowed_statuses[] = 'Out for Delivery';
    }
    
    if (!in_array($order['status'], $allowed_statuses)) {
        throw new Exception('Order cannot be cancelled at its current status');
    }
    
    if (!$can_cancel) {
        throw new Exception($cancel_message);
    }
    
    // Set new status based on who is cancelling
    $new_status = $is_admin ? 'Rejected' : 'Cancelled';
    
    // Update order status
    $update_sql = "UPDATE orders SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param('si', $new_status, $order_id);
    
    if (!$update_stmt->execute()) {
        throw new Exception('Failed to update order status');
    }
    $update_stmt->close();
    
    // Check if order_cancellations table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'order_cancellations'");
    if ($table_check->num_rows > 0) {
        // Check if is_admin column exists
        $column_check = $conn->query("SHOW COLUMNS FROM order_cancellations LIKE 'is_admin'");
        $has_is_admin = $column_check->num_rows > 0;
        
        if ($has_is_admin) {
            // Insert cancellation reason with is_admin
            $reason_sql = "INSERT INTO order_cancellations (order_id, user_id, reason, is_admin) VALUES (?, ?, ?, ?)";
            $reason_stmt = $conn->prepare($reason_sql);
            $reason_stmt->bind_param('iisi', $order_id, $user_id, $final_reason, $is_admin);
        } else {
            // Insert cancellation reason without is_admin
            $reason_sql = "INSERT INTO order_cancellations (order_id, user_id, reason) VALUES (?, ?, ?)";
            $reason_stmt = $conn->prepare($reason_sql);
            $reason_stmt->bind_param('iis', $order_id, $user_id, $final_reason);
        }
        
        if (!$reason_stmt->execute()) {
            throw new Exception('Failed to record cancellation reason');
        }
        $reason_stmt->close();
    } else {
        // If table doesn't exist, add reason to orders table
        $alt_reason_sql = "UPDATE orders SET cancellation_reason = ? WHERE id = ?";
        $alt_reason_stmt = $conn->prepare($alt_reason_sql);
        $alt_reason_stmt->bind_param('si', $final_reason, $order_id);
        
        if (!$alt_reason_stmt->execute()) {
            throw new Exception('Failed to record cancellation reason');
        }
        $alt_reason_stmt->close();
    }
    
    // Create notification for rider if order was out for delivery
    if ($order['status'] === 'Out for Delivery') {
        // Get rider ID
        $rider_query = "SELECT rider_id FROM orders WHERE id = ?";
        $rider_stmt = $conn->prepare($rider_query);
        $rider_stmt->bind_param('i', $order_id);
        $rider_stmt->execute();
        $rider_result = $rider_stmt->get_result();
        
        if ($rider_result->num_rows > 0) {
            $rider_data = $rider_result->fetch_assoc();
            $rider_id = $rider_data['rider_id'];
            
            // Check if notifications table exists
            $notif_check = $conn->query("SHOW TABLES LIKE 'notifications'");
            if ($notif_check->num_rows > 0 && $rider_id > 0) {
                // First check the structure of the notifications table
                $columns_check = $conn->query("SHOW COLUMNS FROM notifications");
                $columns = [];
                while ($column = $columns_check->fetch_assoc()) {
                    $columns[] = $column['Field'];
                }
                
                // Create notification for rider based on available columns
                if (in_array('message', $columns) && in_array('user_id', $columns)) {
                    $notification_text = 'Order #' . $order_id . ' has been cancelled by the customer';
                    
                    if (in_array('title', $columns) && in_array('order_id', $columns)) {
                        // If table has title and order_id columns
                        $notif_sql = "INSERT INTO notifications (user_id, title, message, order_id) 
                                    VALUES (?, 'Order Cancelled', ?, ?)";
                        $notif_stmt = $conn->prepare($notif_sql);
                        $notif_stmt->bind_param('isi', $rider_id, $notification_text, $order_id);
                    } else if (in_array('type', $columns)) {
                        // If table has type column but no title column
                        $notif_sql = "INSERT INTO notifications (user_id, type, message) 
                                    VALUES (?, 'order_cancelled', ?)";
                        $notif_stmt = $conn->prepare($notif_sql);
                        $notif_stmt->bind_param('is', $rider_id, $notification_text);
                    } else {
                        // Basic notification with just user_id and message
                        $notif_sql = "INSERT INTO notifications (user_id, message) 
                                    VALUES (?, ?)";
                        $notif_stmt = $conn->prepare($notif_sql);
                        $notif_stmt->bind_param('is', $rider_id, $notification_text);
                    }
                    
                    $notif_stmt->execute();
                    $notif_stmt->close();
                }
            }
        }
        $rider_stmt->close();
    }
    
    $conn->commit();
    echo json_encode(['success' => true, 'message' => "Order has been " . strtolower($new_status) . " successfully"]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    error_log("Order cancellation error: " . $e->getMessage(), 3, __DIR__ . '/error.log');
}

$conn->close();
?>