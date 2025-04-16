<?php
// Include the database connection
require_once __DIR__ . '/../config/database.php';

// Get the form data
$item_name = $_POST['item_name'];
$item_description = $_POST['item_description'];
$item_price = $_POST['item_price'];

// Handle the image upload
$item_image = 'images/uploads/' . basename($_FILES["item_image"]["name"]);
$targetDirectory = realpath(__DIR__ . '/../public/images/uploads') . '/';
$targetFile = $targetDirectory . basename($_FILES["item_image"]["name"]);

// Validate if the image is uploaded successfully
if (move_uploaded_file($_FILES["item_image"]["tmp_name"], $targetFile)) {
    // Prepare the SQL query to insert the data
    $stmt = $conn->prepare("INSERT INTO coffee (item_name, item_description, item_price, item_image) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $item_name, $item_description, $item_price, $item_image);  // "ssss" indicates string parameters

    // Execute the query and check if it was successful
    if ($stmt->execute()) {
        echo "New item added to coffee menu!";
    } else {
        echo "Error: " . $stmt->error;
    }

    // Close the prepared statement
    $stmt->close();
} else {
    echo "Image upload failed.";
}

// Close the database connection
$conn->close();
?>
