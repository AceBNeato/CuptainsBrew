<?php
require_once '../includes/db.php'; // connect to `cafe_db`

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $price = $_POST['price'] ?? '';
    $category = $_POST['category'] ?? '';
    $imagePath = ''; // You can implement image saving here

    // Save uploaded image
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $uploadDir = '../uploads/';
        $filename = basename($_FILES['image']['name']);
        $targetPath = $uploadDir . $filename;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
            $imagePath = '/uploads/' . $filename;
        }
    }

    $stmt = $pdo->prepare("INSERT INTO products(name, description, price, category, image_path) VALUES (?, ?, ?, ?, ?)");
    $success = $stmt->execute([$name, $description, $price, $category, $imagePath]);

    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Item added.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'DB insert failed.']);
    }
}
?>
