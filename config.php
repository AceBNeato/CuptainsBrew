<?php
$db_host = 'localhost';
$db_user = 'root'; 
$db_pass = ''; 
$db_name = 'cafe_db';

// Connect to MySQL server
$conn = new mysqli($db_host, $db_user, $db_pass);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

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
