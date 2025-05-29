<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth_check.php';
requireAdmin();

// Set JSON response header
header('Content-Type: application/json');

try {
    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        throw new Exception('Invalid CSRF token');
    }

    // Validate required fields
if (empty($_POST['id']) || empty($_POST['item_name']) || empty($_POST['item_price'])) {
        throw new Exception('Missing required fields');
}

    // Validate and sanitize input
$id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
if ($id === false || $id <= 0) {
        throw new Exception('Invalid item ID');
}

$item_name = trim($conn->real_escape_string($_POST['item_name']));
    if (strlen($item_name) < 2) {
        throw new Exception('Item name must be at least 2 characters long');
    }

$item_description = trim($conn->real_escape_string($_POST['item_description'] ?? ''));
$item_price = filter_var($_POST['item_price'], FILTER_VALIDATE_FLOAT);

if ($item_price === false || $item_price <= 0) {
        throw new Exception('Invalid price value');
    }

    // Get existing item data
    $stmt = $conn->prepare("SELECT item_image FROM products WHERE id = ?");
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }

    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $existing_item = $result->fetch_assoc();
    $stmt->close();

    if (!$existing_item) {
        throw new Exception('Item not found');
    }

    // Handle file upload if provided
    $imagePath = $existing_item['item_image'];

if (!empty($_FILES['item_image']['name'])) {
    $uploadDir = realpath(__DIR__ . '/../public/assets/uploads') . '/';
        
        // Validate upload directory
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
    
    // Generate unique filename
        $fileExt = strtolower(pathinfo($_FILES['item_image']['name'], PATHINFO_EXTENSION));
        $imageName = uniqid('item_') . '.' . $fileExt;
    $targetFile = $uploadDir . $imageName;
        $newImagePath = 'assets/uploads/' . $imageName;

    // Validate image file
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $fileType = mime_content_type($_FILES["item_image"]["tmp_name"]);

    if (!in_array($fileType, $allowedTypes)) {
            throw new Exception('Only JPG, PNG, and GIF images are allowed');
    }

    if ($_FILES['item_image']['size'] > 2000000) {
            throw new Exception('Image size must be less than 2MB');
    }

    if (!move_uploaded_file($_FILES["item_image"]["tmp_name"], $targetFile)) {
            throw new Exception('Failed to upload image');
        }

        // Delete old image if it exists and is different
        if (!empty($existing_item['item_image'])) {
            $oldImagePath = realpath(__DIR__ . '/../public/' . $existing_item['item_image']);
        if ($oldImagePath && file_exists($oldImagePath)) {
            unlink($oldImagePath);
        }
    }

        $imagePath = $newImagePath;
    }

    // Update database
    $conn->begin_transaction();

    try {
        // Update main product
$stmt = $conn->prepare("UPDATE products 
                      SET item_name = ?, 
                          item_price = ?, 
                          item_description = ?, 
                                  item_image = ?,
                                  has_variation = ?
                      WHERE id = ?");

        $hasVariations = isset($_POST['has_variations']) && $_POST['has_variations'] === 'on';
        $stmt->bind_param("sdssii", 
    $item_name,
    $item_price,
    $item_description,
    $imagePath,
            $hasVariations,
    $id
);

        if (!$stmt->execute()) {
            throw new Exception('Failed to update product: ' . $stmt->error);
        }

        // Handle variations
        if ($hasVariations) {
            // Delete existing variations
            $stmt = $conn->prepare("DELETE FROM product_variations WHERE product_id = ?");
            $stmt->bind_param("i", $id);
            if (!$stmt->execute()) {
                throw new Exception('Failed to delete existing variations: ' . $stmt->error);
            }

            // Add new variations
            $hotPrice = filter_var($_POST['hot_price'], FILTER_VALIDATE_FLOAT);
            $icedPrice = filter_var($_POST['iced_price'], FILTER_VALIDATE_FLOAT);

            if ($hotPrice === false || $icedPrice === false) {
                throw new Exception('Invalid variation prices');
            }

            $stmt = $conn->prepare("INSERT INTO product_variations (product_id, variation_type, price) VALUES (?, ?, ?)");
            
            // Insert Hot variation
            $type = 'Hot';
            $stmt->bind_param("isd", $id, $type, $hotPrice);
            if (!$stmt->execute()) {
                throw new Exception('Failed to add Hot variation: ' . $stmt->error);
            }

            // Insert Iced variation
            $type = 'Iced';
            $stmt->bind_param("isd", $id, $type, $icedPrice);
            if (!$stmt->execute()) {
                throw new Exception('Failed to add Iced variation: ' . $stmt->error);
            }
} else {
            // Remove all variations if variations are disabled
            $stmt = $conn->prepare("DELETE FROM product_variations WHERE product_id = ?");
            $stmt->bind_param("i", $id);
            if (!$stmt->execute()) {
                throw new Exception('Failed to delete variations: ' . $stmt->error);
            }
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Item updated successfully']);

    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    // Clean up uploaded file if it exists and there was an error
    if (isset($targetFile) && file_exists($targetFile)) {
        unlink($targetFile);
    }

    // Return error response
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>