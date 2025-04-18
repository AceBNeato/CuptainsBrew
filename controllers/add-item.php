<?php
require_once __DIR__ . '/../config/database.php'; // Make sure $conn is properly connected

// Retrieve form data
$item_name = $_POST['item_name'];
$item_description = $_POST['item_description'];
$item_price = $_POST['item_price'];
$item_category = $_POST['item_category']; // <-- this decides the table
$item_image = 'images/uploads/' . basename($_FILES["item_image"]["name"]);

// Define allowed categories/tables to prevent SQL injection
$allowed_categories = ['coffee', 'non_coffee', 'frappe', 'milktea', 'soda'];

// Check if selected category is allowed
if (!in_array($item_category, $allowed_categories)) {
    die("Invalid category selected.");
}

// Upload image
$targetDirectory = realpath(__DIR__ . '/../public/images/uploads') . '/';
$targetFile = $targetDirectory . basename($_FILES["item_image"]["name"]);

if (move_uploaded_file($_FILES["item_image"]["tmp_name"], $targetFile)) {
    // Use dynamic table name based on selected category
    $table = $conn->real_escape_string($item_category);

    // Insert into the corresponding table
    $stmt = $conn->prepare("INSERT INTO `$table` (item_name, item_description, item_price, item_image) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssds", $item_name, $item_description, $item_price, $item_image);

    if ($stmt->execute()) {
        echo "New item added to the $table menu!";
    } else {
        echo "Database error: " . $stmt->error;
    }
    $stmt->close();
} else {
    echo "Image upload failed.";
}
?>
