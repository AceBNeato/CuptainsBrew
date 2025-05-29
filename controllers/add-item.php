<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth_check.php';
requireAdmin();

// Set JSON response header
header('Content-Type: application/json');

try {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        throw new Exception('Invalid CSRF token');
    }

    // Validate required fields
    if (!isset($_POST['item_name']) || !isset($_POST['item_price']) || 
        !isset($_POST['item_description']) || !isset($_POST['category_id'])) {
        throw new Exception('Missing required fields');
    }

    // Sanitize and validate input
    $itemName = trim($_POST['item_name']);
    $itemPrice = floatval($_POST['item_price']);
    $itemDescription = trim($_POST['item_description']);
    $categoryId = intval($_POST['category_id']);
    $hasVariations = isset($_POST['has_variations']) ? 1 : 0;

    if (empty($itemName) || empty($itemDescription) || $itemPrice <= 0 || $categoryId <= 0) {
        throw new Exception('Invalid input data');
    }

    // Validate variation prices if variations are enabled
    if ($hasVariations) {
        if (!isset($_POST['hot_price']) || !isset($_POST['iced_price'])) {
            throw new Exception('Variation prices are required when variations are enabled');
        }
        
        $hotPrice = floatval($_POST['hot_price']);
        $icedPrice = floatval($_POST['iced_price']);
        
        if ($hotPrice <= 0 || $icedPrice <= 0) {
            throw new Exception('Variation prices must be greater than zero');
        }
    }

    // Handle image upload
    if (!isset($_FILES['item_image']) || $_FILES['item_image']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Image upload failed');
    }

    $file = $_FILES['item_image'];
    $fileName = $file['name'];
    $fileTmpName = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileError = $file['error'];
    $fileType = $file['type'];

    // Get file extension
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

    if (!in_array($fileExt, $allowedExtensions)) {
        throw new Exception('Invalid file type. Only JPG, JPEG, PNG & GIF files are allowed.');
    }

    if ($fileSize > 5000000) { // 5MB max
        throw new Exception('File is too large. Maximum size is 5MB.');
    }

    // Generate unique filename
    $newFileName = uniqid('item_', true) . '.' . $fileExt;
    $uploadPath = __DIR__ . '/../public/uploads/' . $newFileName;

    // Create uploads directory if it doesn't exist
    if (!file_exists(__DIR__ . '/../public/uploads/')) {
        mkdir(__DIR__ . '/../public/uploads/', 0777, true);
    }

    // Move uploaded file
    if (!move_uploaded_file($fileTmpName, $uploadPath)) {
        throw new Exception('Failed to move uploaded file');
    }

    // Begin transaction
    $conn->begin_transaction();

    try {
        // Insert into products table
        $stmt = $conn->prepare("INSERT INTO products (category_id, item_name, item_description, item_price, item_image, has_variation) VALUES (?, ?, ?, ?, ?, ?)");
        $imageUrl = 'uploads/' . $newFileName;
        
        if (!$stmt->bind_param("issdsi", $categoryId, $itemName, $itemDescription, $itemPrice, $imageUrl, $hasVariations)) {
            throw new Exception('Failed to bind parameters');
        }

        if (!$stmt->execute()) {
            throw new Exception('Failed to add item to database: ' . $stmt->error);
        }

        $productId = $conn->insert_id;
        $stmt->close();

        // Add variations if enabled
        if ($hasVariations) {
            $stmt = $conn->prepare("INSERT INTO product_variations (product_id, variation_type, price) VALUES (?, ?, ?)");
            
            // Insert Hot variation
            $type = 'Hot';
            $stmt->bind_param("isd", $productId, $type, $hotPrice);
            if (!$stmt->execute()) {
                throw new Exception('Failed to add Hot variation: ' . $stmt->error);
            }

            // Insert Iced variation
            $type = 'Iced';
            $stmt->bind_param("isd", $productId, $type, $icedPrice);
            if (!$stmt->execute()) {
                throw new Exception('Failed to add Iced variation: ' . $stmt->error);
            }
            
            $stmt->close();
        }

        $conn->commit();

        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'Item added successfully',
            'item' => [
                'id' => $productId,
                'item_name' => $itemName,
                'item_price' => $itemPrice,
                'item_description' => $itemDescription,
                'item_image' => $imageUrl,
                'category_id' => $categoryId,
                'has_variations' => $hasVariations
            ]
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        // Delete uploaded file if database insert fails
        if (file_exists($uploadPath)) {
            unlink($uploadPath);
        }
        throw $e;
    }

    } catch (Exception $e) {
    // Clean up uploaded file if it exists and there was an error
    if (isset($uploadPath) && file_exists($uploadPath)) {
        unlink($uploadPath);
    }
    
    // Return error response
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();