<?php
$db_host = 'localhost';
$db_user = 'root'; 
$db_pass = ''; 
$db_name = 'cafe_db';


// Enable error reporting for debugging (disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Establish database connection
try {
    // Create connection without selecting database
    $conn = new mysqli($host, $username, $password);

// Create database if not exists
if (!$conn->query("CREATE DATABASE IF NOT EXISTS $db_name")) {
    die("Error creating database: " . $conn->error);
}

// Select database
if (!$conn->select_db($db_name)) {
    die("Cannot use database: " . $conn->error);
}

// Create categories table (simplified without slug)
$sql = "CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE
)";
if (!$conn->query($sql)) {
    die("Error creating categories table: " . $conn->error);
}

// Insert basic categories
$sql = "INSERT IGNORE INTO categories (name) VALUES 
    ('Coffee'),
    ('Non-Coffee'),
    ('Frappe'),
    ('MilkTea'),
    ('Soda')";
if (!$conn->query($sql)) {
    die("Error inserting categories: " . $conn->error);
}

// Create products table
$sql = "CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    item_price DECIMAL(10,2) NOT NULL,
    item_description TEXT NOT NULL,
    item_image VARCHAR(255),
    FOREIGN KEY (category_id) REFERENCES categories(id)
)";
if (!$conn->query($sql)) {
    die("Error creating products table: " . $conn->error);
}

    // Define table creation queries
    $queries = [
        // Categories table
        "CREATE TABLE IF NOT EXISTS categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL UNIQUE
        )",
        
        // Products table
        "CREATE TABLE IF NOT EXISTS products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            category_id INT NOT NULL,
            item_name VARCHAR(255) NOT NULL,
            item_description TEXT NOT NULL,
            item_price DECIMAL(10, 2) NOT NULL,
            item_image VARCHAR(255) NOT NULL,
            stock INT NOT NULL DEFAULT 0,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
        )",
        
        // Users table
        "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(255) NOT NULL UNIQUE,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            address TEXT,
            contact VARCHAR(20),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        // Orders table
        "CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            total_amount DECIMAL(10, 2) NOT NULL,
            status ENUM('Pending', 'Approved', 'Processing', 'Assigned', 'Out for Delivery', 'Delivered', 'Rejected', 'Completed') DEFAULT 'Pending',
            delivery_address TEXT NOT NULL,
            payment_method ENUM('Card', 'COD', 'Digital Wallet') NOT NULL,
            rider_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (rider_id) REFERENCES riders(id) ON DELETE SET NULL
        )",
        
        // Order_items table
        "CREATE TABLE IF NOT EXISTS order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT NOT NULL,
            price DECIMAL(10, 2) NOT NULL,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        )",
        
        // Riders table
        "CREATE TABLE IF NOT EXISTS riders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            contact VARCHAR(20) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        // Payments table
        "CREATE TABLE IF NOT EXISTS payments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            amount DECIMAL(10, 2) NOT NULL,
            method ENUM('Card', 'COD', 'Digital Wallet') NOT NULL,
            status ENUM('Pending', 'Completed', 'Failed') DEFAULT 'Pending',
            transaction_id VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
        )",
        
        // Reviews table
        "CREATE TABLE IF NOT EXISTS reviews (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            user_id INT,
            rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
            comment TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        )"
    ];

    // Execute table creation queries
    foreach ($queries as $query) {
        if (!$conn->query($query)) {
            throw new Exception("Failed to create table: " . $conn->error);
        }
    }




    
} catch (Exception $e) {
    // Log error to file and display user-friendly message
    error_log($e->getMessage(), 3, __DIR__ . '/error.log');
    die("<div class='error-message'>Unable to connect to the database or set up schema. Please try again later.</div>");
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Make $conn available globally
global $conn;
?>