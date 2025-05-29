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

// Verify user is logged in (either rider or admin)
$is_rider = isRiderLoggedIn();
$is_admin = isset($_SESSION['user_id']) && isset($_SESSION['loggedin']) && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;

if (!$is_rider && !$is_admin) {
    logRiderError("Unauthorized access attempt to get-order-items.php");
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get order ID from query string
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($order_id <= 0) {
    logRiderError("Invalid order ID requested: " . ($order_id ?? 'null'));
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit();
}

try {
    // If rider is logged in, verify order belongs to the rider
    if ($is_rider) {
        $rider_id = getCurrentRiderId();
        $check_query = "SELECT * FROM orders WHERE id = $order_id AND rider_id = $rider_id";
        $check_result = $conn->query($check_query);
        
        if (!$check_result) {
            throw new Exception('Database error: ' . $conn->error);
        }
        
        if ($check_result->num_rows === 0) {
            throw new Exception('Order not found or not assigned to you');
        }
        
        logRiderError("Rider ID: $rider_id accessed items for Order ID: $order_id");
    }
    
    // Get order items
    $items_query = "SELECT oi.*, p.item_name, p.item_image
                   FROM order_items oi
                   LEFT JOIN products p ON oi.product_id = p.id
                   WHERE oi.order_id = $order_id";
    $items_result = $conn->query($items_query);
    
    if (!$items_result) {
        throw new Exception('Failed to fetch order items: ' . $conn->error);
    }
    
    $items = [];
    while ($item = $items_result->fetch_assoc()) {
        // Format image path
        $image_path = !empty($item['item_image']) ? '/public/' . ltrim($item['item_image'], '/') : '';
        
        $items[] = [
            'id' => $item['id'],
            'product_id' => $item['product_id'],
            'item_name' => $item['item_name'],
            'quantity' => $item['quantity'],
            'price' => $item['price'],
            'variation' => $item['variation'],
            'image' => $image_path
        ];
    }
    
    // Return success response
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'items' => $items]);
    
} catch (Exception $e) {
    logRiderError("Error fetching items for Order ID: $order_id - " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 