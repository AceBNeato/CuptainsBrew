<?php

$config_path = __DIR__ . '..\..\config.php';

if (!file_exists($config_path)) {
    die("Error: config.php not found at $config_path. Please check the file path.");
}

require_once $config_path;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Sanitize inputs
        $item_name = $conn->real_escape_string($_POST['item_name']);
        $item_price = (float)$_POST['item_price'];
        $item_description = $conn->real_escape_string($_POST['item_description']);
        $category_id = (int)$_POST['category_id'];

        // Handle file upload
        $item_image = '';
        if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../../public/assets/uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $file_name = uniqid() . '_' . basename($_FILES['item_image']['name']);
            $file_path = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES['item_image']['tmp_name'], $file_path)) {
                $item_image = 'images/' . $file_name;
            } else {
                throw new Exception("Failed to upload image.");
            }
        } else {
            throw new Exception("Image upload failed or no image provided.");
        }

        // Insert into the database
        $query = "INSERT INTO products (category_id, item_name, item_description, item_price, item_image) 
                  VALUES ($category_id, '$item_name', '$item_description', $item_price, '$item_image')";
        
        if (!$conn->query($query)) {
            throw new Exception("Failed to add item: " . $conn->error);
        }

        // Redirect back to admin-menu.php with success message
        header("Location: /views/admin/admin-menu.php?tab=" . ($_POST['category_id'] <= 2 ? 'drinks' : 'foods') . "&category_id=$category_id&success=Item added successfully");
        exit();

    } catch (Exception $e) {
        // Redirect with error message
        header("Location: /views/admin/admin-menu.php?tab=" . ($_POST['category_id'] <= 2 ? 'drinks' : 'foods') . "&category_id=$category_id&error=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    // If not a POST request, redirect back
    header("Location: /views/admin/admin-menu.php");
    exit();
}
?>