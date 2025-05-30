<?php
// Include the database configuration
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth_check.php';
requireAdmin();

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

// Verify admin is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['loggedin'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get rider ID from query string
$rider_id = isset($_GET['rider_id']) ? (int)$_GET['rider_id'] : 0;

if ($rider_id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid rider ID']);
    exit();
}

try {
    // Get rider details
    $rider_query = "SELECT * FROM riders WHERE id = $rider_id";
    $rider_result = $conn->query($rider_query);
    
    if (!$rider_result) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    if ($rider_result->num_rows === 0) {
        throw new Exception("Rider not found");
    }
    
    $rider = $rider_result->fetch_assoc();
    
    // Get rider statistics
    $stats_query = "SELECT 
                    (SELECT COUNT(*) FROM orders WHERE rider_id = $rider_id AND status = 'Assigned') as assigned,
                    (SELECT COUNT(*) FROM orders WHERE rider_id = $rider_id AND status = 'Out for Delivery') as active,
                    (SELECT COUNT(*) FROM orders WHERE rider_id = $rider_id AND status = 'Delivered') as completed";
    $stats_result = $conn->query($stats_query);
    
    if (!$stats_result) {
        throw new Exception("Error fetching statistics: " . $conn->error);
    }
    
    $stats = $stats_result->fetch_assoc();
    
    // Get current assignments
    $assignments_query = "SELECT o.*, u.username as customer_name
                         FROM orders o
                         LEFT JOIN users u ON o.user_id = u.id
                         WHERE o.rider_id = $rider_id
                         AND o.status IN ('Assigned', 'Out for Delivery')
                         ORDER BY o.created_at DESC";
    $assignments_result = $conn->query($assignments_query);
    
    if (!$assignments_result) {
        throw new Exception("Error fetching assignments: " . $conn->error);
    }
    
    $assignments = [];
    if ($assignments_result->num_rows > 0) {
        while ($row = $assignments_result->fetch_assoc()) {
            $assignments[] = $row;
        }
    }
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'rider' => $rider,
        'stats' => $stats,
        'assignments' => $assignments
    ]);
    
} catch (Exception $e) {
    logRiderError($e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 