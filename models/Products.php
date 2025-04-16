<?php
class Products {
    private $conn;

    public function __construct() {
        $this->conn = new mysqli("localhost", "root", "", "cafe_db");
        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
    }

    public function addProduct($name, $description, $price, $category, $image) {
        // Sanitize the table name (allow only known category tables)
        $allowedTables = ['coffee', 'non_coffee', 'frappe', 'milktea', 'soda'];

        if (!in_array($category, $allowedTables)) {
            die("Invalid category.");
        }

        // Build query dynamically
        $query = "INSERT INTO `$category` (name, description, price, image) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ssds", $name, $description, $price, $image);
        $stmt->execute();
        $stmt->close();
    }
}
?>
