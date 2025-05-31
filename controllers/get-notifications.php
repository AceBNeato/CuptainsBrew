<?php
require_once __DIR__ . '/../config.php';
session_start();

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Performance optimization: Add cache control headers
header('Cache-Control: private, max-age=30'); // Allow browser to cache for 30 seconds

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'] ?? 'customer'; // Default to customer if not set

$response = [
    'success' => false,
    'notifications' => [],
    'unread_count' => 0
];

// Performance optimization: Use session-based cache for frequent requests
$cache_key = "notifications_count_{$user_id}_{$user_type}";
$details_requested = isset($_GET['details']) && $_GET['details'] == 1;

// Only use cache for count requests (not detail requests)
if (!$details_requested && isset($_SESSION[$cache_key]) && $_SESSION[$cache_key]['expires'] > time()) {
    echo json_encode($_SESSION[$cache_key]['data']);
    exit;
}

// For debugging
$debug = isset($_GET['debug']) && $_GET['debug'] == 1;
$debug_info = [];

try {
    if ($user_type === 'admin') {
        // Get only unread count first (more efficient)
        $count_query = "SELECT COUNT(*) as count FROM orders WHERE is_viewed = 0";
        $count_result = $conn->query($count_query);
        $count_row = $count_result->fetch_assoc();
        $response['unread_count'] = (int)$count_row['count'];
        
        // Only get notification details if dropdown is open (passed as parameter)
        if ($details_requested) {
            // Get new orders for admin - limited to 5 most recent
            // Performance optimization: Use more selective indexable fields in WHERE clause
            $query = "SELECT o.id, o.created_at, o.status, u.username, o.is_viewed 
                     FROM orders o 
                     JOIN users u ON o.user_id = u.id 
                     WHERE o.is_viewed = 0 OR o.status = 'pending'
                     ORDER BY o.created_at DESC
                     LIMIT 5";
            
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $response['notifications'][] = [
                    'id' => $row['id'],
                    'title' => 'New Order #' . $row['id'],
                    'message' => 'New order from ' . $row['username'],
                    'created_at' => $row['created_at'],
                    'is_read' => $row['is_viewed'] == 1,
                    'status' => $row['status'],
                    'order_id' => $row['id']
                ];
            }
        }
    } else {
        // Get only unread count first (more efficient)
        $count_query = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
        $count_stmt = $conn->prepare($count_query);
        $count_stmt->bind_param("i", $user_id);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $count_row = $count_result->fetch_assoc();
        $response['unread_count'] = (int)$count_row['count'];
        
        if ($debug) {
            $debug_info['unread_count'] = $response['unread_count'];
        }
        
        // Only get notification details if dropdown is open (passed as parameter)
        if ($details_requested) {
            // Check if notifications table exists
            $table_check = $conn->query("SHOW TABLES LIKE 'notifications'");
            if ($table_check->num_rows > 0) {
                // Notifications table exists, use it
                $query = "SELECT n.id, n.title, n.message, n.created_at, n.is_read, n.order_id, o.status
                         FROM notifications n
                         LEFT JOIN orders o ON n.order_id = o.id
                         WHERE n.user_id = ?
                         ORDER BY n.created_at DESC
                         LIMIT 10";
                
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($debug) {
                    $debug_info['query'] = $query;
                    $debug_info['user_id'] = $user_id;
                    $debug_info['result_count'] = $result->num_rows;
                }
                
                while ($row = $result->fetch_assoc()) {
                    // Normalize status to lowercase for consistent comparison
                    $status = strtolower($row['status'] ?? '');
                    
                    // Check if this is an out for delivery notification
                    $isOutForDelivery = false;
                    if ($status === 'out for delivery' || $status === 'out_for_delivery') {
                        $isOutForDelivery = true;
                    } else if ($row['message'] && (
                        stripos($row['message'], 'out for delivery') !== false ||
                        stripos($row['message'], 'out_for_delivery') !== false
                    )) {
                        $isOutForDelivery = true;
                    }
                    
                    if ($debug) {
                        $debug_info['notifications'][] = [
                            'id' => $row['id'],
                            'title' => $row['title'],
                            'message' => $row['message'],
                            'status' => $status,
                            'is_out_for_delivery' => $isOutForDelivery
                        ];
                    }
                    
                    $response['notifications'][] = [
                        'id' => $row['id'],
                        'title' => $row['title'] ?? 'Order #' . $row['order_id'],
                        'message' => $row['message'],
                        'created_at' => $row['created_at'],
                        'is_read' => $row['is_read'] == 1,
                        'status' => $status,
                        'order_id' => $row['order_id'],
                        'is_out_for_delivery' => $isOutForDelivery
                    ];
                }
            } else {
                // Fallback to orders table if notifications table doesn't exist
                $query = "SELECT o.id, o.status, o.updated_at
                         FROM orders o
                         WHERE o.user_id = ?
                         ORDER BY o.updated_at DESC
                         LIMIT 5";
                
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($debug) {
                    $debug_info['fallback_query'] = $query;
                    $debug_info['fallback_result_count'] = $result->num_rows;
                }
                
                while ($row = $result->fetch_assoc()) {
                    $status = strtolower($row['status'] ?? '');
                    $message = getDefaultStatusMessage($status);
                    
                    // Check if this is an out for delivery notification
                    $isOutForDelivery = ($status === 'out for delivery' || $status === 'out_for_delivery');
                    
                    $response['notifications'][] = [
                        'id' => $row['id'],
                        'title' => 'Order #' . $row['id'],
                        'message' => $message,
                        'created_at' => $row['updated_at'] ?? $row['created_at'] ?? date('Y-m-d H:i:s'),
                        'is_read' => false,
                        'status' => $status,
                        'order_id' => $row['id'],
                        'is_out_for_delivery' => $isOutForDelivery
                    ];
                }
            }
        }
    }
    
    $response['success'] = true;
    
    // Add debug info if requested
    if ($debug) {
        $response['debug'] = $debug_info;
    }
    
    // Store in session cache for 30 seconds (only for count requests)
    if (!$details_requested) {
        $_SESSION[$cache_key] = [
            'data' => $response,
            'expires' => time() + 30
        ];
    }
} catch (Exception $e) {
    $response['error'] = 'Error fetching notifications: ' . $e->getMessage();
    if ($debug) {
        $response['error_details'] = $e->getTraceAsString();
    }
}

echo json_encode($response);
exit;

// Helper function to convert timestamp to "time ago" format
// Performance optimization: Cache time calculations for common timestamps
function getTimeAgo($timestamp) {
    static $cache = [];
    
    // Return from cache if available
    if (isset($cache[$timestamp])) {
        return $cache[$timestamp];
    }
    
    $time = strtotime($timestamp);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        $result = "Just now";
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        $result = $mins . "m ago";
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        $result = $hours . "h ago";
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        $result = $days . "d ago";
    } else {
        $result = date("M j", $time);
    }
    
    // Store in cache
    $cache[$timestamp] = $result;
    
    return $result;
}

// Helper function to get default message based on order status
function getDefaultStatusMessage($status) {
    switch (strtolower($status)) {
        case 'pending':
            return 'Your order has been received and is pending confirmation.';
        case 'processing':
            return 'Your order is being prepared.';
        case 'out for delivery':
        case 'out_for_delivery':
            return 'Your order is out for delivery. You have 5 minutes to cancel if needed.';
        case 'delivered':
            return 'Your order has been delivered.';
        case 'cancelled':
            return 'Your order has been cancelled.';
        default:
            return 'Your order status has been updated to: ' . ucfirst($status);
    }
}
?> 