<?php
// Debug: Show all received data
echo "<pre>POST data: ";
print_r($_POST);
echo "FILES data: ";
print_r($_FILES);
echo "</pre>";

// 1. Config file inclusion
$configPath = __DIR__ . '/../config.php';
require_once $configPath;

// 2. Verify database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 3. Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Error: Form not submitted");
}

// 4. Validate all required inputs
$required = ['item_name', 'item_price', 'item_category'];
foreach ($required as $field) {
    if (empty($_POST[$field])) {
        die("Error: Missing required field: " . $field);
    }
}

// 5. Sanitize and validate inputs
$item_name = trim($conn->real_escape_string($_POST['item_name']));
$item_description = trim($conn->real_escape_string($_POST['item_description'] ?? ''));
$item_price = filter_var($_POST['item_price'], FILTER_VALIDATE_FLOAT);

// 6. Validate category and get category ID
$allowed_categories = ['coffee' => 1, 'non_coffee' => 2, 'frappe' => 3, 'milktea' => 4, 'soda' => 5];
if (!array_key_exists($_POST['item_category'], $allowed_categories)) {
    die("Error: Invalid category selected");
}
$item_category_id = $allowed_categories[$_POST['item_category']];

if ($item_price === false || $item_price <= 0) {
    die("Error: Invalid price value");
}

// 7. Handle file upload if provided
$imagePath = null;
if (!empty($_FILES['item_image']['name'])) {
    $uploadDir = realpath(__DIR__ . '/../public/assets/uploads') . '/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            die("Error: Failed to create upload directory");
        }
    }

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
        die("Error: File upload failed. Error: " . $_FILES["item_image"]["error"]);
    }
}

// 8. Database operation - CORRECTED VERSION
$conn->begin_transaction();

try {
    $stmt = $conn->prepare("INSERT INTO products 
                          (category_id, item_name, item_price, item_description, item_image) 
                          VALUES (?, ?, ?, ?, ?)");
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("issss", 
        $item_category_id, 
        $item_name,     
        $item_price,       
        $item_description,  
        $imagePath         
    );

    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $newItemId = $conn->insert_id;
    $stmt->close();
    $conn->commit();
    
    // Success response
    header('Location: /views/admin/Admin-Menu.php?success=1&new_id=' . $newItemId);
    exit;

} catch (Exception $e) {
    $conn->rollback();
    
    // Clean up if file was uploaded but DB failed
    if (isset($targetFile) && file_exists($targetFile)) {
        unlink($targetFile);
    }
    
    // Error response
    header('Location: /views/admin/Admin-Menu.php?error=' . urlencode($e->getMessage()));
    exit;
}
?>