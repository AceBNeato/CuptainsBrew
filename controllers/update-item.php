<?php
include '../config/database.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $name = $_POST['item_name'];
    $price = $_POST['item_price'];
    $description = $_POST['item_description'];
    $category = $_POST['category'];

    $allowed_categories = [
        'coffee' => 'coffee',
        'frappe' => 'frappe',
        'milktea' => 'milktea',
        'non-coffee' => 'non_coffee',
        'soda' => 'soda'
    ];

    if (!array_key_exists($category, $allowed_categories)) {
        die("Invalid category.");
    }

    $table = $allowed_categories[$category];

    $imageToSave = $_POST['existing_image'] ?? '';

    if (!empty($_FILES['item_image']['name'])) {
        $imageName = time() . '_' . basename($_FILES['item_image']['name']);
        $uploadDir = __DIR__ . '/../public/images/uploads/';
        $target = $uploadDir . $imageName;

        if (move_uploaded_file($_FILES['item_image']['tmp_name'], $target)) {
            $imageToSave = "images/uploads/" . $imageName;
            $oldImageRelative = $_POST['existing_image'] ?? '';
            $oldImageFullPath = __DIR__ . '/../public/' . $oldImageRelative;

            if ($oldImageRelative && file_exists($oldImageFullPath) && is_file($oldImageFullPath)) {
                unlink($oldImageFullPath); 
            }
        }
    }


    $sql = "UPDATE `$table` SET item_name=?, item_price=?, item_description=?, item_image=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sdssi", $name, $price, $description, $imageToSave, $id);

    if ($stmt->execute()) {
        header("Location: /views/admin/Admin-Menu.php?success=1");
        exit();
    } else {
        echo "Error updating item: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>
