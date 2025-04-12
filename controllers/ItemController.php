<?php
require_once __DIR__ . '/../models/Item.php';

class ItemController {

    // Show the add-item form
    public function create() {
        require_once __DIR__ . '/../views/admin/add-item.php';
    }

    // Handle form submission and insert item into the database
    public function store() {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $name = $_POST["item_name"];
            $description = $_POST["item_description"];
            $price = $_POST["item_price"];
            $category = $_POST["item_category"];

            // Handle the image upload
            $targetDir = "../uploads/";
            $imageName = basename($_FILES["item_image"]["name"]);
            $targetFile = $targetDir . $imageName;
            $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

            // Only allow image files
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array($imageFileType, $allowedTypes)) {
                die("Only JPG, PNG, and GIF files are allowed.");
            }

            if (move_uploaded_file($_FILES["item_image"]["tmp_name"], $targetFile)) {
                // Insert into database using the model
                if (Item::create($name, $description, $price, $category, $imageName)) {
                    echo "<script>
                        alert('Item added successfully!');
                        window.location.href = '/'; // Redirect to homepage or items page
                    </script>";
                } else {
                    echo "Error: Failed to insert item into database.";
                }
            } else {
                echo "Failed to upload image.";
            }
        }
    }
}
