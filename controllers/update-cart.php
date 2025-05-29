<?php
session_start();
require_once __DIR__ . '/../config.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($data['cart_id']) || !isset($data['quantity'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request parameters']);
    exit;
}

$cart_id = (int)$data['cart_id'];
$quantity = (int)$data['quantity'];
$user_id = $_SESSION['user_id'];

// Validate quantity
if ($quantity < 1 || $quantity > 10) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Quantity must be between 1 and 10']);
    exit;
}

try {
    // Verify cart item belongs to user
    $stmt = $conn->prepare("SELECT * FROM cart WHERE id = ? AND user_id = ?");
    $stmt->bind_param('ii', $cart_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Cart item not found']);
        exit;
    }
    
    // Update quantity
    $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param('iii', $quantity, $cart_id, $user_id);
    $success = $stmt->execute();
    
    if ($success) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Cart updated successfully']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to update cart: ' . $stmt->error]);
    }
    
    $stmt->close();
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?> 