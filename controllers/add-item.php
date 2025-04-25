<?php
include '/config.php'; 

$item_name = $_POST['item_name'];
$item_description = $_POST['item_description'];
$item_price = $_POST['item_price'];
$item_category = $_POST['item_category']; 
$item_image = 'images/uploads/' . basename($_FILES["item_image"]["name"]);

$allowed_categories = ['coffee', 'non_coffee', 'frappe', 'milktea', 'soda'];
if (!in_array($item_category, $allowed_categories)) {
    die("Invalid category selected.");
}

$targetDirectory = realpath(__DIR__ . '/../public/images/uploads') . '/';
$targetFile = $targetDirectory . basename($_FILES["item_image"]["name"]);

if (move_uploaded_file($_FILES["item_image"]["tmp_name"], $targetFile)) {
    $table = $conn->real_escape_string($item_category);

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
