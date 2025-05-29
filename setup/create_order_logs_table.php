<?php
// Include the database configuration
require_once __DIR__ . '/../config.php';

// Create order_status_logs table if not exists
$create_table_query = "CREATE TABLE IF NOT EXISTS order_status_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    status VARCHAR(50) NOT NULL,
    updated_by INT NOT NULL,
    updated_by_type ENUM('admin', 'rider') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
)";

if ($conn->query($create_table_query)) {
    echo "order_status_logs table created or already exists.\n";
} else {
    echo "Error creating order_status_logs table: " . $conn->error . "\n";
}

// Close connection
$conn->close();
?> 