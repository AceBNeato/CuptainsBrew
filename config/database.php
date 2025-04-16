<?php
// Database configuration settings
$host = 'localhost';  // Database server
$user = 'root';       // Database username
$pass = '';           // Database password
$dbname = 'cafe_db';  // Database name

// Create a new MySQLi connection
$conn = new mysqli($host, $user, $pass, $dbname);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Optionally, set the character set for the connection (for proper encoding)
$conn->set_charset("utf8");
?>
