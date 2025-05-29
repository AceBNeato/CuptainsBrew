<?php
// Ensure no whitespace or output before headers
$config_path = __DIR__ . '/../config.php';

// Try different paths if the first one doesn't work
if (!file_exists($config_path)) {
    // Try root directory
    $config_path = dirname(__DIR__) . '/config.php';
}

if (!file_exists($config_path)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => "Error: config.php not found. Please check the file path."]);
    exit;
}

require_once $config_path;

// Set content type header
header('Content-Type: application/json');

// Disable error output to prevent it from corrupting JSON
ini_set('display_errors', 0);

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle both form data and JSON request formats
    if (isset($_POST['order_id']) && isset($_POST['status'])) {
        // Form data submission
        $orderId = intval($_POST['order_id']);
        $status = $_POST['status'];
        $riderId = isset($_POST['rider_id']) ? intval($_POST['rider_id']) : null;
        $notify = isset($_POST['notify']) ? (bool)$_POST['notify'] : false;
        $action = 'update';
    } else {
        // JSON data submission
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $response['message'] = 'Invalid JSON input: ' . json_last_error_msg();
            echo json_encode($response);
            exit;
        }
        
        $orderId = $data['orderId'] ?? null;
        $action = $data['action'] ?? null;
        $status = $data['status'] ?? null;
        $riderId = $data['riderId'] ?? null;
        $notify = $data['notify'] ?? false;
    }

    if (!$orderId || !$status) {
        $response['message'] = 'Invalid request parameters';
        echo json_encode($response);
        exit;
    }
    
    // Validate status - include all possible statuses
    $validStatuses = ['Pending', 'Approved', 'Processing', 'Assigned', 'Out for Delivery', 'Delivered', 'Rejected', 'Cancelled', 'Completed'];
    if (!in_array($status, $validStatuses)) {
        $response['message'] = 'Invalid status';
        echo json_encode($response);
        exit;
    }

    try {
        // Check if $conn is available
        if (!isset($conn) || !($conn instanceof mysqli)) {
            // Database connection parameters
            $db_host = 'localhost';
            $db_user = 'root';
            $db_pass = '';
            $db_name = 'cafe_db';
            
            // Create connection
            $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
            
            // Check connection
            if ($conn->connect_error) {
                throw new Exception("Connection failed: " . $conn->connect_error);
            }
        }
        
        $conn->begin_transaction();
        
        // Default action is update if not specified
        $action = $action ?? 'update';
        
        if ($action === 'update') {
            if ($riderId) {
                $sql = "UPDATE orders SET status = ?, rider_id = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('sii', $status, $riderId, $orderId);
            } else {
                $sql = "UPDATE orders SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('si', $status, $orderId);
            }

            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Order status updated successfully';

                // If status is "Delivered", update payment status to "Completed"
                if ($status === 'Delivered') {
                    $sql = "UPDATE payments SET status = 'Completed', updated_at = NOW() WHERE order_id = ?";
                    $paymentStmt = $conn->prepare($sql);
                    $paymentStmt->bind_param('i', $orderId);
                    $paymentStmt->execute();
                    $paymentStmt->close();
                }

                if ($notify) {
                    // Fetch user email for notification
                    $sql_user = "SELECT u.email FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?";
                    $stmt_user = $conn->prepare($sql_user);
                    $stmt_user->bind_param('i', $orderId);
                    $stmt_user->execute();
                    $user_result = $stmt_user->get_result();
                    $user = $user_result->fetch_assoc();
                    $stmt_user->close();

                    if ($user && $user['email']) {
                        // Mock notification (replace with actual email/push notification logic)
                        error_log("Notification sent to {$user['email']} for order #$orderId: Status changed to $status", 3, __DIR__ . '/notification.log');
                        $response['message'] .= ' and user notified';
                    } else {
                        $response['message'] .= ' but user notification failed (no email found)';
                    }
                }
            } else {
                $response['message'] = 'Failed to update order status: ' . $stmt->error;
                error_log("Update failed: " . $stmt->error, 3, __DIR__ . '/error.log');
            }
            $stmt->close();
        } else {
            $response['message'] = 'Invalid action';
        }
        
        $conn->commit();
    } catch (Exception $e) {
        if (isset($conn) && $conn instanceof mysqli) {
            try {
                $conn->rollback();
            } catch (Exception $rollbackError) {
                // Silently ignore rollback errors
            }
        }
        $response['message'] = 'Database error: ' . $e->getMessage();
        error_log("Error: " . $e->getMessage(), 3, __DIR__ . '/error.log');
    }
} else {
    $response['message'] = 'Invalid request method';
}

// Always return a JSON response
echo json_encode($response);
exit;
?>
