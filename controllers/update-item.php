<?php
include __DIR__ . '/../config.php';

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Error: Form not submitted");
}

if (empty($_POST['id']) || empty($_POST['item_name']) || empty($_POST['item_price'])) {
    die("Error: Missing required fields");
}

$id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
if ($id === false || $id <= 0) {
    die("Error: Invalid item ID");
}

$item_name = trim($conn->real_escape_string($_POST['item_name']));
$item_description = trim($conn->real_escape_string($_POST['item_description'] ?? ''));
$item_price = filter_var($_POST['item_price'], FILTER_VALIDATE_FLOAT);

if ($item_price === false || $item_price <= 0) {
    die("Error: Invalid price value");
}

// 6. Handle file upload if provided
$imagePath = $_POST['existing_image'] ?? null;

if (!empty($_FILES['item_image']['name'])) {
    $uploadDir = realpath(__DIR__ . '/../public/assets/uploads') . '/';
    
    // Generate unique filename
    $fileExt = pathinfo($_FILES['item_image']['name'], PATHINFO_EXTENSION);
    $imageName = uniqid() . '.' . strtolower($fileExt);
    $targetFile = $uploadDir . $imageName;
    $imagePath = 'assets/uploads/' . $imageName;

    // Validate image file
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $fileType = mime_content_type($_FILES["item_image"]["tmp_name"]);

    if (!in_array($fileType, $allowedTypes)) {
        die("Error: Only JPG, PNG, and GIF images are allowed");
    }

    if ($_FILES['item_image']['size'] > 2000000) {
        die("Error: Image size must be less than 2MB");
    }

    if (!move_uploaded_file($_FILES["item_image"]["tmp_name"], $targetFile)) {
        die("Error: File upload failed");
    }

    // Delete old image if it exists and is different from new one
    if (!empty($_POST['existing_image']) && $_POST['existing_image'] !== $imagePath) {
        $oldImagePath = realpath(__DIR__ . '/../public/' . $_POST['existing_image']);
        if ($oldImagePath && file_exists($oldImagePath)) {
            unlink($oldImagePath);
        }
    }
}

// 7. Update database
$stmt = $conn->prepare("UPDATE products 
                      SET item_name = ?, 
                          item_price = ?, 
                          item_description = ?, 
                          item_image = ?
                      WHERE id = ?");

if (!$stmt) {
    die("Error preparing statement: " . $conn->error);
}

$stmt->bind_param("sdssi", 
    $item_name,
    $item_price,
    $item_description,
    $imagePath,
    $id
);

if ($stmt->execute()) {
    header('Location: /views/admin/Admin-Menu.php?success=1&updated_id=' . $id);
    exit;
} else {
    // Clean up if file was uploaded but DB failed
    if (isset($targetFile) && file_exists($targetFile)) {
        unlink($targetFile);
    }
    die("Error updating item: " . $stmt->error);
}

$stmt->close();
$conn->close();
?>