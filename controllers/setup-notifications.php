<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

$response = [
    'success' => false,
    'messages' => []
];

try {
    // Check if the notifications table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'notifications'");
    
    if ($table_check->num_rows === 0) {
        // Create notifications table if it doesn't exist
        $create_table_sql = "CREATE TABLE notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(255) DEFAULT NULL,
            message TEXT NOT NULL,
            is_read TINYINT(1) DEFAULT 0,
            order_id INT DEFAULT NULL,
            status VARCHAR(50) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (user_id),
            INDEX (order_id)
        )";
        
        if ($conn->query($create_table_sql)) {
            $response['messages'][] = "Notifications table created successfully";
        } else {
            throw new Exception("Failed to create notifications table: " . $conn->error);
        }
    } else {
        $response['messages'][] = "Notifications table already exists";
        
        // Check if title column exists
        $column_check = $conn->query("SHOW COLUMNS FROM notifications LIKE 'title'");
        
        if ($column_check->num_rows === 0) {
            // Add title column if it doesn't exist
            $add_column_sql = "ALTER TABLE notifications ADD COLUMN title VARCHAR(255) DEFAULT NULL AFTER user_id";
            
            if ($conn->query($add_column_sql)) {
                $response['messages'][] = "Title column added to notifications table";
            } else {
                throw new Exception("Failed to add title column: " . $conn->error);
            }
        } else {
            $response['messages'][] = "Title column already exists in notifications table";
        }
    }
    
    // Check if status column exists
    $status_column_check = $conn->query("SHOW COLUMNS FROM notifications LIKE 'status'");
    
    if ($status_column_check->num_rows === 0) {
        // Add status column if it doesn't exist
        $add_status_sql = "ALTER TABLE notifications ADD COLUMN status VARCHAR(50) DEFAULT NULL";
        
        if ($conn->query($add_status_sql)) {
            $response['messages'][] = "Status column added to notifications table";
        } else {
            throw new Exception("Failed to add status column: " . $conn->error);
        }
    } else {
        $response['messages'][] = "Status column already exists in notifications table";
    }
    
    // Make sure orders table has is_viewed column
    $orders_column_check = $conn->query("SHOW COLUMNS FROM orders LIKE 'is_viewed'");
    
    if ($orders_column_check->num_rows === 0) {
        // Add is_viewed column if it doesn't exist
        $add_viewed_sql = "ALTER TABLE orders ADD COLUMN is_viewed TINYINT(1) NOT NULL DEFAULT 0";
        
        if ($conn->query($add_viewed_sql)) {
            $response['messages'][] = "is_viewed column added to orders table";
        } else {
            throw new Exception("Failed to add is_viewed column: " . $conn->error);
        }
    } else {
        $response['messages'][] = "is_viewed column already exists in orders table";
    }
    
    // Show current structure of notifications table
    $columns_query = $conn->query("DESCRIBE notifications");
    $columns = [];
    
    while ($column = $columns_query->fetch_assoc()) {
        $columns[] = $column;
    }
    
    $response['columns'] = $columns;
    $response['success'] = true;
    
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response, JSON_PRETTY_PRINT);
?> 