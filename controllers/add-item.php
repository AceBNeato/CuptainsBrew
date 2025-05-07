<?php
session_start();

// Debug output (remove in production)
echo "<pre>POST data: ";
print_r($_POST);
echo "FILES data: ";
print_r($_FILES);
echo "</pre>";

$configPath = __DIR__ . '/../config.php';
require_once $configPath;

if ($conn->connect_error) {
    $_SESSION['swal'] = [
        'title' => 'Database Error',
        'text' => "Connection failed: " . $conn->connect_error,
        'icon' => 'error'
    ];
    header('Location: /views/admin/Admin-Menu.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['swal'] = [
        'title' => 'Invalid Request',
        'text' => 'Form not submitted properly',
        'icon' => 'error'
    ];
    header('Location: /views/admin/Admin-Menu.php');
    exit;
}

// Validate required fields
$required = ['item_name', 'item_price', 'item_category'];
foreach ($required as $field) {
    if (empty($_POST[$field])) {
        $_SESSION['swal'] = [
            'title' => 'Missing Information',
            'text' => "Please fill in all required fields. Missing: " . $field,
            'icon' => 'error'
        ];
        header('Location: /views/admin/Admin-Menu.php');
        exit;
    }
}

// Process and validate inputs
$item_name = trim($conn->real_escape_string($_POST['item_name']));
$item_description = trim($conn->real_escape_string($_POST['item_description'] ?? ''));
$item_price = filter_var($_POST['item_price'], FILTER_VALIDATE_FLOAT);

$allowed_categories = ['coffee' => 1, 'non_coffee' => 2, 'frappe' => 3, 'milktea' => 4, 'soda' => 5];
if (!array_key_exists($_POST['item_category'], $allowed_categories)) {
    $_SESSION['swal'] = [
        'title' => 'Invalid Category',
        'text' => 'Please select a valid category',
        'icon' => 'error'
    ];
    header('Location: /views/admin/Admin-Menu.php');
    exit;
}
$item_category_id = $allowed_categories[$_POST['item_category']];

if ($item_price === false || $item_price <= 0) {
    $_SESSION['swal'] = [
        'title' => 'Invalid Price',
        'text' => 'Please enter a valid price greater than 0',
        'icon' => 'error'
    ];
    header('Location: /views/admin/Admin-Menu.php');
    exit;
}

// Image upload handling
$imagePath = null;
if (empty($_FILES['item_image']['name'])) {
    $_SESSION['swal'] = [
        'title' => 'Image Required',
        'text' => 'Please select an image to upload',
        'icon' => 'warning'
    ];
    header('Location: /views/admin/Admin-Menu.php');
    exit;
}

// Proceed with image upload
$uploadDir = realpath(__DIR__ . '/../public/assets/uploads') . '/';
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        $_SESSION['swal'] = [
            'title' => 'Upload Error',
            'text' => 'Failed to create upload directory',
            'icon' => 'error'
        ];
        header('Location: /views/admin/Admin-Menu.php');
        exit;
    }
}

$fileExt = pathinfo($_FILES['item_image']['name'], PATHINFO_EXTENSION);
$imageName = uniqid() . '.' . strtolower($fileExt);
$targetFile = $uploadDir . $imageName;
$imagePath = 'assets/uploads/' . $imageName;

$allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
$fileType = mime_content_type($_FILES["item_image"]["tmp_name"]);

if (!in_array($fileType, $allowedTypes)) {
    $_SESSION['swal'] = [
        'title' => 'Invalid Image',
        'text' => 'Only JPG, PNG, and GIF images are allowed',
        'icon' => 'error'
    ];
    header('Location: /views/admin/Admin-Menu.php');
    exit;
}

if ($_FILES['item_image']['size'] > 2000000) {
    $_SESSION['swal'] = [
        'title' => 'File Too Large',
        'text' => 'Image size must be less than 2MB',
        'icon' => 'error'
    ];
    header('Location: /views/admin/Admin-Menu.php');
    exit;
}

if (!move_uploaded_file($_FILES["item_image"]["tmp_name"], $targetFile)) {
    $_SESSION['swal'] = [
        'title' => 'Upload Failed',
        'text' => 'File upload failed. Error: ' . $_FILES["item_image"]["error"],
        'icon' => 'error'
    ];
    header('Location: /views/admin/Admin-Menu.php');
    exit;
}

// Database transaction
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
    
    $_SESSION['swal'] = [
        'title' => 'Success!',
        'text' => 'Item added successfully',
        'icon' => 'success',
        'new_id' => $newItemId
    ];
    header('Location: /views/admin/Admin-Menu.php');
    exit;

} catch (Exception $e) {
    $conn->rollback();
    
    // Clean up uploaded file if database operation failed
    if (isset($targetFile) && file_exists($targetFile)) {
        unlink($targetFile);
    }
    
    $_SESSION['swal'] = [
        'title' => 'Database Error',
        'text' => $e->getMessage(),
        'icon' => 'error'
    ];
    header('Location: /views/admin/Admin-Menu.php');
    exit;
}
?>