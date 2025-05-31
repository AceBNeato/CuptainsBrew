<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

$response = [
    'success' => true,
    'tables' => [],
    'columns' => [],
    'errors' => []
];

try {
    // Check if notifications table exists
    $tables_query = "SHOW TABLES LIKE 'notifications'";
    $tables_result = $conn->query($tables_query);
    $notifications_table_exists = ($tables_result->num_rows > 0);
    $response['tables']['notifications'] = $notifications_table_exists;
    
    if (!$notifications_table_exists) {
        $response['errors'][] = "The 'notifications' table does not exist.";
    }
    
    // Check orders table structure
    $orders_columns_query = "SHOW COLUMNS FROM orders";
    $orders_columns_result = $conn->query($orders_columns_query);
    
    if ($orders_columns_result) {
        $orders_columns = [];
        while ($column = $orders_columns_result->fetch_assoc()) {
            $orders_columns[] = $column['Field'];
        }
        
        $response['columns']['orders'] = $orders_columns;
        
        // Check for required columns
        $required_columns = ['is_viewed', 'updated_at'];
        foreach ($required_columns as $column) {
            if (!in_array($column, $orders_columns)) {
                $response['errors'][] = "The 'orders' table is missing the '$column' column.";
            }
        }
    } else {
        $response['errors'][] = "Could not query the 'orders' table structure.";
    }
    
    // Check if users have user_type set in session
    $response['session'] = [
        'user_id' => isset($_SESSION['user_id']),
        'user_type' => isset($_SESSION['user_type']),
        'role' => isset($_SESSION['role']),
        'user_type_value' => $_SESSION['user_type'] ?? 'not set',
        'role_value' => $_SESSION['role'] ?? 'not set'
    ];
    
    // Check roles table
    $roles_query = "SHOW TABLES LIKE 'roles'";
    $roles_result = $conn->query($roles_query);
    $roles_table_exists = ($roles_result->num_rows > 0);
    $response['tables']['roles'] = $roles_table_exists;
    
    if ($roles_table_exists) {
        $roles_query = "SELECT id, name FROM roles";
        $roles_result = $conn->query($roles_query);
        $roles = [];
        
        while ($role = $roles_result->fetch_assoc()) {
            $roles[] = $role;
        }
        
        $response['roles'] = $roles;
    }
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['errors'][] = "Error: " . $e->getMessage();
}

echo json_encode($response, JSON_PRETTY_PRINT);
?> 