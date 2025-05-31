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
    // Check if item_id is provided
    if (!isset($_GET['item_id']) || empty($_GET['item_id'])) {
        throw new Exception('Menu item ID is required.');
    }
    
    $item_id = intval($_GET['item_id']);
    
    // Get reviews for the item
    $query = "
        SELECT r.id, r.rating, r.comment, r.created_at, 
               u.username, u.profile_image
        FROM reviews r
        JOIN users u ON r.user_id = u.id
        WHERE r.item_id = ?
        ORDER BY r.created_at DESC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $reviews = [];
    while ($row = $result->fetch_assoc()) {
        // Format the date
        $date = new DateTime($row['created_at']);
        $formatted_date = $date->format('M d, Y');
        
        // Set default profile image if none exists
        $profile_image = $row['profile_image'] ? $row['profile_image'] : '/public/images/icons/profile-icon.png';
        
        $reviews[] = [
            'id' => $row['id'],
            'username' => $row['username'],
            'profile_image' => $profile_image,
            'rating' => $row['rating'],
            'comment' => $row['comment'],
            'date' => $formatted_date
        ];
    }
    
    $stmt->close();
    
    // Get average rating
    $avg_query = "SELECT AVG(rating) as avg_rating, COUNT(*) as review_count FROM reviews WHERE item_id = ?";
    $avg_stmt = $conn->prepare($avg_query);
    $avg_stmt->bind_param("i", $item_id);
    $avg_stmt->execute();
    $avg_result = $avg_stmt->get_result();
    $avg_row = $avg_result->fetch_assoc();
    
    $avg_rating = $avg_row['avg_rating'] ? round($avg_row['avg_rating'], 1) : 0;
    $review_count = $avg_row['review_count'];
    
    $avg_stmt->close();
    
    // Check if current user has reviewed this item
    $user_review = null;
    if (isset($_SESSION['user_id']) && $_SESSION['loggedin'] === true) {
        $user_query = "SELECT rating, comment FROM reviews WHERE user_id = ? AND item_id = ?";
        $user_stmt = $conn->prepare($user_query);
        $user_stmt->bind_param("ii", $_SESSION['user_id'], $item_id);
        $user_stmt->execute();
        $user_result = $user_stmt->get_result();
        
        if ($user_result->num_rows > 0) {
            $user_review = $user_result->fetch_assoc();
        }
        
        $user_stmt->close();
    }
    
    $response = [
        'success' => true,
        'message' => 'Reviews retrieved successfully.',
        'data' => [
            'reviews' => $reviews,
            'avg_rating' => $avg_rating,
            'review_count' => $review_count,
            'user_review' => $user_review
        ]
    ];
    
} catch (Exception $e) {
    // Log the error
    error_log("Get reviews error: " . $e->getMessage(), 3, __DIR__ . '/error.log');
    
    $response = [
        'success' => false,
        'message' => $e->getMessage(),
        'data' => []
    ];
}

// Return JSON response
echo json_encode($response);
exit; 