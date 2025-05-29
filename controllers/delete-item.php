<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth_check.php';
requireAdmin();

// Set JSON response header
header('Content-Type: application/json');

try {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        throw new Exception('Invalid security token. Please refresh the page and try again.');
    }

    // Validate item ID
    if (!isset($_POST['item_id']) || !is_numeric($_POST['item_id'])) {
        throw new Exception('Invalid item ID provided.');
    }

    $itemId = intval($_POST['item_id']);
    if ($itemId <= 0) {
        throw new Exception('Invalid item ID value.');
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Get item details first
        $stmt = $conn->prepare("SELECT item_name, item_image FROM products WHERE id = ? LIMIT 1");
        if (!$stmt) {
            throw new Exception('Database error: ' . $conn->error);
        }

        $stmt->bind_param("i", $itemId);
        if (!$stmt->execute()) {
            throw new Exception('Failed to fetch item details: ' . $stmt->error);
        }

        $result = $stmt->get_result();
        $item = $result->fetch_assoc();
        $stmt->close();

        if (!$item) {
            throw new Exception('Item not found or already deleted.');
        }

        // Store item details for later use
        $itemName = $item['item_name'];
        $imagePath = $item['item_image'];

        // Delete from database
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        if (!$stmt) {
            throw new Exception('Database error: ' . $conn->error);
        }

        $stmt->bind_param("i", $itemId);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to delete item from database: ' . $stmt->error);
        }

        if ($stmt->affected_rows === 0) {
            throw new Exception('Item could not be deleted or was already removed.');
        }

        $stmt->close();

        // Delete image file if it exists
        if (!empty($imagePath)) {
            $fullImagePath = __DIR__ . '/../public/' . $imagePath;
            if (file_exists($fullImagePath)) {
                if (!unlink($fullImagePath)) {
                    // Log error but don't throw exception
                    error_log("Warning: Could not delete image file: $fullImagePath");
                }
            }
        }

        // Commit transaction
        $conn->commit();

        // Return success response
        echo json_encode([
            'success' => true,
            'message' => "\"$itemName\" has been deleted successfully.",
            'item_id' => $itemId
        ]);

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    // Return error response
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// Close database connection
$conn->close();
?>
