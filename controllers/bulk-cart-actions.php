<?php
session_start();
require_once __DIR__ . '/../config.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($data['action'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Action not specified']);
    exit;
}

$action = $data['action'];

try {
    switch ($action) {
        case 'remove_selected':
            if (!isset($data['cart_ids']) || !is_array($data['cart_ids']) || empty($data['cart_ids'])) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'No items selected']);
                exit;
            }
            
            $cart_ids = array_map('intval', $data['cart_ids']);
            $ids_string = implode(',', $cart_ids);
            
            // Delete selected items from cart
            $stmt = $conn->prepare("DELETE FROM cart WHERE id IN ($ids_string) AND user_id = ?");
            $stmt->bind_param('i', $user_id);
            $success = $stmt->execute();
            
            if ($success) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true, 
                    'message' => 'Selected items removed successfully',
                    'count' => $stmt->affected_rows
                ]);
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Failed to remove items: ' . $stmt->error]);
            }
            
            $stmt->close();
            break;
            
        case 'checkout_selected':
            if (!isset($data['cart_ids']) || !is_array($data['cart_ids']) || empty($data['cart_ids'])) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'No items selected']);
                exit;
            }
            
            $cart_ids = array_map('intval', $data['cart_ids']);
            
            // Store selected cart IDs in session for checkout
            $_SESSION['selected_cart_items'] = $cart_ids;
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true, 
                'message' => 'Items selected for checkout',
                'redirect' => '/views/users/checkout.php'
            ]);
            break;
            
        default:
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            exit;
    }
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?> 