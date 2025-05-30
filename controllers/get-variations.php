<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth_check.php';
// Regular users need to access variations, so we don't require admin here

header('Content-Type: application/json');

try {
    if (!isset($_GET['product_id'])) {
        throw new Exception('Product ID is required');
    }

    $product_id = filter_var($_GET['product_id'], FILTER_VALIDATE_INT);
    if ($product_id === false || $product_id <= 0) {
        throw new Exception('Invalid product ID');
    }

    $stmt = $conn->prepare("SELECT * FROM product_variations WHERE product_id = ?");
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }

    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $variations = [];
    while ($row = $result->fetch_assoc()) {
        $variations[] = [
            'id' => $row['id'],
            'variation_type' => $row['variation_type'],
            'price' => $row['price']
        ];
    }

    echo json_encode(['success' => true, 'variations' => $variations]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} 