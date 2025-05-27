<?php
session_start();

$config_path = __DIR__ . '..\..\config.php';

if (!file_exists($config_path)) {
    die("Error: config.php not found at $config_path. Please check the file path.");
}

require_once $config_path;

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);
$order_id = isset($data['orderId']) ? (int)$data['orderId'] : 0;
$reason = isset($data['reason']) ? trim($data['reason']) : '';
$custom_reason = isset($data['customReason']) ? trim($data['customReason']) : '';

if ($order_id <= 0 || empty($reason)) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID or reason']);
    exit;
}

// Verify the order belongs to the user and is in a cancellable state
$stmt = $conn->prepare("SELECT status FROM orders WHERE id = ? AND user_id = ?");
$stmt->bind_param('ii', $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Order not found or not authorized']);
    $stmt->close();
    $conn->close();
    exit;
}

$row = $result->fetch_assoc();
if (!in_array($row['status'], ['Pending', 'Approved'])) {
    echo json_encode(['success' => false, 'message' => 'Order cannot be cancelled']);
    $stmt->close();
    $conn->close();
    exit;
}
$stmt->close();

// Update order status to Rejected
$stmt = $conn->prepare("UPDATE orders SET status = 'Canceled', updated_at = CURRENT_TIMESTAMP WHERE id = ?");
$stmt->bind_param('i', $order_id);
if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Failed to update order status']);
    $stmt->close();
    $conn->close();
    exit;
}
$stmt->close();

// Insert cancellation reason
$stmt = $conn->prepare("INSERT INTO order_cancellations (order_id, user_id, reason, custom_reason) VALUES (?, ?, ?, ?)");
$stmt->bind_param('iiss', $order_id, $user_id, $reason, $custom_reason);
if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Failed to record cancellation reason']);
    $stmt->close();
    $conn->close();
    exit;
}

$stmt->close();
$conn->close();

echo json_encode(['success' => true, 'message' => 'Order cancelled successfully']);
?>