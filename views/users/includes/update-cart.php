<?php
session_start();
require_once '../../../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    error_log("User not logged in", 3, 'error.log');
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$cart_id = isset($_POST['cart_id']) ? (int)$_POST['cart_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

if ($cart_id <= 0 || $quantity < 1 || $quantity > 10) {
    error_log("Invalid input: cart_id=$cart_id, quantity=$quantity", 3, 'error.log');
    echo json_encode(['success' => false, 'error' => 'Invalid cart ID or quantity']);
    exit;
}

try {
    $sql = "SELECT quantity FROM cart WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $cart_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        error_log("Cart item not found for user_id: $user_id, cart_id: $cart_id", 3, 'error.log');
        echo json_encode(['success' => false, 'error' => 'Cart item not found']);
        $stmt->close();
        exit;
    }
    
    $current_quantity = $result->fetch_assoc()['quantity'];
    $stmt->close();
    
    $sql = "UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iii', $quantity, $cart_id, $user_id);
    $success = $stmt->execute();
    if (!$success) {
        error_log("Update failed: " . $stmt->error, 3, 'error.log');
    }
    $stmt->close();
    
    $response = [
        'success' => $success,
        'quantity' => $success ? $quantity : $current_quantity,
        'error' => $success ? '' : 'Failed to update cart'
    ];
    echo json_encode($response);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON encode failed: " . json_last_error_msg(), 3, 'error.log');
        echo json_encode(['success' => false, 'error' => 'Internal server error']);
    }
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage(), 3, 'error.log');
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>