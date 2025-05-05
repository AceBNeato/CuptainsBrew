<?php
class Product {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function addItem($data, $file) {
        // Validate category
        $allowed_categories = ['coffee', 'non_coffee', 'frappe', 'milktea', 'soda'];
        if (!in_array($data['item_category'], $allowed_categories)) {
            throw new Exception("Invalid category selected.");
        }

        // Handle file upload
        $targetDirectory = realpath(__DIR__ . '/../public/images/uploads') . '/';
        $targetFile = $targetDirectory . basename($file["item_image"]["name"]);
        $item_image = 'images/uploads/' . basename($file["item_image"]["name"]);

        if (!move_uploaded_file($file["item_image"]["tmp_name"], $targetFile)) {
            throw new Exception("Image upload failed.");
        }

        // Insert into database
        $table = $this->conn->real_escape_string($data['item_category']);
        $stmt = $this->conn->prepare("INSERT INTO `$table` (item_name, item_description, item_price, item_image) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssds", $data['item_name'], $data['item_description'], $data['item_price'], $item_image);

        if (!$stmt->execute()) {
            throw new Exception("Database error: " . $stmt->error);
        }

        return $table; // Return the table name for success message
    }
}
?>