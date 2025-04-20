<?php
// Database credentials
$host = 'localhost';  // Database host
$user = 'root';       // Database username
$pass = '';           // Database password
$dbname = 'cafe_db';  // Database name

// Create connection
$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// At the end of database.php
return $conn;

?>
