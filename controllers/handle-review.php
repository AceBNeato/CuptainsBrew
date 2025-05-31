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
    'message' => 'An error occurred.'
];

try {
    // Check if the request is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method.');
    }
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
        throw new Exception('You must be logged in to submit a review.');
    }
    
    // Validate required fields
    $required_fields = ['item_id', 'rating'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
            throw new Exception("Field '$field' is required.");
        }
    }
    
    // Get data from POST
    $user_id = $_SESSION['user_id'];
    $item_id = intval($_POST['item_id']);
    $rating = intval($_POST['rating']);
    $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
    
    // Validate rating
    if ($rating < 1 || $rating > 5) {
        throw new Exception('Rating must be between 1 and 5.');
    }
    
    // Check if the menu item exists
    $check_item = $conn->prepare("SELECT id FROM products WHERE id = ?");
    $check_item->bind_param("i", $item_id);
    $check_item->execute();
    $result = $check_item->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Invalid menu item.');
    }
    $check_item->close();
    
    // Check if user has already reviewed this item
    $check_review = $conn->prepare("SELECT id FROM reviews WHERE user_id = ? AND item_id = ?");
    $check_review->bind_param("ii", $user_id, $item_id);
    $check_review->execute();
    $result = $check_review->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing review
        $review_id = $result->fetch_assoc()['id'];
        $stmt = $conn->prepare("UPDATE reviews SET rating = ?, comment = ? WHERE id = ?");
        $stmt->bind_param("isi", $rating, $comment, $review_id);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to update review: ' . $stmt->error);
        }
        
        $response = [
            'success' => true,
            'message' => 'Your review has been updated!'
        ];
    } else {
        // Insert new review
        $stmt = $conn->prepare("INSERT INTO reviews (user_id, item_id, rating, comment) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiis", $user_id, $item_id, $rating, $comment);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to submit review: ' . $stmt->error);
        }
        
        $response = [
            'success' => true,
            'message' => 'Your review has been submitted!'
        ];
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    // Log the error
    error_log("Review submission error: " . $e->getMessage(), 3, __DIR__ . '/error.log');
    
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
}

// Return JSON response
echo json_encode($response);
exit; 