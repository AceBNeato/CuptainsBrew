<?php
session_start();
require_once '../../../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = isset($_POST['action']) && $_POST['action'] === 'clear' ? 'clear' : 'remove';
$cart_id = isset($_POST['cart_id']) ? (int)$_POST['cart_id'] : 0;

try {
    if ($action === 'clear') {
        $sql = "DELETE FROM cart WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $user_id);
        $success = $stmt->execute();
        if (!$success) {
            error_log("Failed to clear cart for user_id: $user_id: " . $stmt->error, 3, 'error.log');
        }
        $stmt->close();
        
        echo json_encode([
            'success' => $success,
            'error' => $success ? '' : 'Failed to clear cart'
        ]);
    } else {
        if ($cart_id <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid cart ID']);
            exit;
        }
        
        $sql = "DELETE FROM cart WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $cart_id, $user_id);
        $success = $stmt->execute();
        if (!$success) {
            error_log("Failed to remove cart item for cart_id: $cart_id, user_id: $user_id: " . $stmt->error, 3, 'error.log');
        }
        $stmt->close();
        
        echo json_encode([
            'success' => $success,
            'error' => $success ? '' : 'Failed to remove item'
        ]);
    }
} catch (Exception $e) {
    error_log("Database error in remove-cart.php: " . $e->getMessage(), 3, 'error.log');
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>