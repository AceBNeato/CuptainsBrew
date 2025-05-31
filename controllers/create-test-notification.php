<?php
require_once __DIR__ . '/../config.php';

// Create a test notification
$user_id = 1; // Assuming user ID 1 exists
$title = 'Test Notification - Out for Delivery';
$message = 'Your order is out for delivery. You have 5 minutes to cancel if needed.';
$order_id = 1; // Assuming order ID 1 exists
$status = 'Out for Delivery';

$query = "INSERT INTO notifications (user_id, title, message, order_id, status, created_at) 
          VALUES (?, ?, ?, ?, ?, NOW())";
$stmt = $conn->prepare($query);
$stmt->bind_param("issis", $user_id, $title, $message, $order_id, $status);

if ($stmt->execute()) {
    echo "Test notification created successfully with ID: " . $conn->insert_id . "\n";
} else {
    echo "Error creating test notification: " . $stmt->error . "\n";
}

// Display recent notifications
$query = "SELECT * FROM notifications ORDER BY created_at DESC LIMIT 5";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    echo "\nRecent notifications:\n";
    echo str_repeat('-', 80) . "\n";
    echo sprintf("%-5s | %-8s | %-30s | %-40s | %-15s\n", "ID", "User ID", "Title", "Message", "Status");
    echo str_repeat('-', 80) . "\n";
    
    while ($row = $result->fetch_assoc()) {
        echo sprintf("%-5s | %-8s | %-30s | %-40s | %-15s\n", 
            $row['id'], 
            $row['user_id'], 
            substr($row['title'] ?? 'No title', 0, 30), 
            substr($row['message'], 0, 40), 
            $row['status'] ?? 'No status'
        );
    }
    echo str_repeat('-', 80) . "\n";
} else {
    echo "No notifications found\n";
}

$conn->close();
?> 