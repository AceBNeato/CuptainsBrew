<?php

$db_host = 'localhost';
$db_user = 'root'; 
$db_pass = ''; 
$db_name = 'cafe_db'; 

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Verify database selection
if (!$conn->select_db($db_name)) {
    die("Cannot use database: " . $conn->error);
}

class Database {
    private $host = 'localhost';
    private $db_name = 'cafe_db';
    private $username = 'root';
    private $password = '';
    private $conn;

    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->db_name}", 
                $this->username, 
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            throw new Exception("Database connection failed");
        }

        return $this->conn;
    }
}
?>