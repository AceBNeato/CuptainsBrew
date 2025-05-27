
<?php


$config_path = __DIR__ . '..\..\config.php';

if (!file_exists($config_path)) {
    die("Error: config.php not found at $config_path. Please check the file path.");
}

require_once $config_path;


header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $orderId = $data['orderId'] ?? null;
    $action = $data['action'] ?? null;
    $status = $data['status'] ?? null;
    $riderId = $data['riderId'] ?? null;
    $notify = $data['notify'] ?? false;

    if (!$orderId || !$action || !$status) {
        $response['message'] = 'Invalid request parameters';
        echo json_encode($response);
        exit;
    }

    $validStatuses = ['Pending', 'Approved', 'Processing', 'Assigned', 'Out for Delivery', 'Delivered', 'Rejected', 'Completed'];
    if (!in_array($status, $validStatuses)) {
        $response['message'] = 'Invalid status';
        echo json_encode($response);
        exit;
    }

    $conn->begin_transaction();
    try {
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
                        error_log("Notification sent to {$user['email']} for order #$orderId: Out for Delivery", 3, __DIR__ . '/notification.log');
                        $response['message'] .= ' and user notified';
                    } else {
                        $response['message'] .= ' but user notification failed (no email found)';
                    }
                }
            } else {
                $response['message'] = 'Failed to update order status';
                error_log("Update failed: " . $stmt->error, 3, __DIR__ . '/error.log');
            }
            $stmt->close();
        } else {
            $response['message'] = 'Invalid action';
        }

        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        $response['message'] = 'Database error: ' . $e->getMessage();
        error_log("Error: " . $e->getMessage(), 3, __DIR__ . '/error.log');
    }

    $conn->close();
} else {
    $response['message'] = 'Invalid request method';
}

echo json_encode($response);
?>
