<?php
require_once __DIR__ . '/../config.php';

// Start the session if not already started
if (!isset($_SESSION)) {
    session_start();
}

// Set the response header to JSON
header('Content-Type: application/json');

// Initialize response array
$response = [
    'success' => false,
    'message' => 'An error occurred.',
    'data' => []
];

try {
    // Get limit parameter, default to 6
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 6;
    
    // Ensure limit is reasonable
    if ($limit < 1 || $limit > 20) {
        $limit = 6;
    }
    
    // Query to get popular items based on order count and ratings
    $query = "
        SELECT 
            mi.id,
            mi.item_name as name,
            mi.item_description as description,
            mi.item_price as price,
            mi.item_image as image_path,
            mi.category_id,
            c.name as category_name,
            COUNT(DISTINCT oi.id) as order_count,
            COALESCE(AVG(r.rating), 0) as avg_rating,
            COUNT(DISTINCT r.id) as review_count
        FROM 
            products mi
        LEFT JOIN 
            order_items oi ON mi.id = oi.product_id
        LEFT JOIN 
            reviews r ON mi.id = r.item_id
        JOIN 
            categories c ON mi.category_id = c.id
        WHERE 
            mi.is_active = 1
        GROUP BY 
            mi.id
        ORDER BY 
            order_count DESC, 
            avg_rating DESC
        LIMIT ?
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $popular_items = [];
    while ($row = $result->fetch_assoc()) {
        // Format the price
        $price = number_format($row['price'], 2);
        
        // Determine if item is "hot" (more than 10 orders)
        $is_hot = $row['order_count'] >= 10;
        
        $popular_items[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'description' => $row['description'],
            'price' => $price,
            'image_path' => $row['image_path'],
            'category_id' => $row['category_id'],
            'category_name' => $row['category_name'],
            'order_count' => $row['order_count'],
            'avg_rating' => round($row['avg_rating'], 1),
            'review_count' => $row['review_count'],
            'is_hot' => $is_hot
        ];
    }
    
    $stmt->close();
    
    $response = [
        'success' => true,
        'message' => 'Popular items retrieved successfully.',
        'data' => $popular_items
    ];
    
} catch (Exception $e) {
    // Log the error
    error_log("Get popular items error: " . $e->getMessage(), 3, __DIR__ . '/error.log');
    
    $response = [
        'success' => false,
        'message' => $e->getMessage(),
        'data' => []
    ];
}

// Return JSON response
echo json_encode($response);
exit; 