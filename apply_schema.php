<?php
// Database configuration
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'cafe_db';

// Connect to MySQL server
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Connected successfully to the database.\n";

// Add cancellation_reason column to orders table if it doesn't exist
$sql1 = "ALTER TABLE orders ADD COLUMN IF NOT EXISTS cancellation_reason VARCHAR(255) DEFAULT NULL";
if ($conn->query($sql1)) {
    echo "Added cancellation_reason column to orders table (if it didn't exist).\n";
} else {
    echo "Error adding cancellation_reason column: " . $conn->error . "\n";
}

// Check if order_cancellations table exists
$tableCheck = $conn->query("SHOW TABLES LIKE 'order_cancellations'");
if ($tableCheck->num_rows == 0) {
    // Create order_cancellations table
    $sql2 = "CREATE TABLE order_cancellations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        user_id INT NOT NULL,
        reason VARCHAR(255) NOT NULL,
        is_admin TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    if ($conn->query($sql2)) {
        echo "Created order_cancellations table.\n";
    } else {
        echo "Error creating order_cancellations table: " . $conn->error . "\n";
    }
} else {
    // Check if is_admin column exists
    $columnCheck = $conn->query("SHOW COLUMNS FROM order_cancellations LIKE 'is_admin'");
    if ($columnCheck->num_rows == 0) {
        // Add is_admin column
        $sql3 = "ALTER TABLE order_cancellations ADD COLUMN is_admin TINYINT(1) DEFAULT 0";
        if ($conn->query($sql3)) {
            echo "Added is_admin column to order_cancellations table.\n";
        } else {
            echo "Error adding is_admin column: " . $conn->error . "\n";
        }
    } else {
        echo "is_admin column already exists in order_cancellations table.\n";
    }
}

$conn->close();
echo "Done!\n";
?> 