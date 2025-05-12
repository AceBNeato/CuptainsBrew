<?php
session_start();
global $conn;
require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json');

if (isset($_GET['action']) && $_GET['action'] === 'add_to_cart') {
    if (!isset($_SESSION['user_id'])) {
        error_log("User not logged in for product_id: " . ($_POST['product_id'] ?? 'null'));
        echo json_encode(['success' => false, 'error' => 'User not logged in']);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        error_log("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
        echo json_encode(['success' => false, 'error' => 'Invalid request method']);
        exit;
    }

    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

    if ($product_id <= 0 || $quantity <= 0) {
        error_log("Invalid product ID or quantity: product_id=$product_id, quantity=$quantity");
        echo json_encode(['success' => false, 'error' => 'Invalid product ID or quantity']);
        exit;
    }

    $user_id = $_SESSION['user_id'];

    $check_query = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
    if ($check_query === false) {
        error_log("Prepare failed: " . $conn->error);
        echo json_encode(['success' => false, 'error' => 'Database query preparation failed']);
        exit;
    }
    $check_query->bind_param("ii", $user_id, $product_id);
    $check_query->execute();
    $check_result = $check_query->get_result();

    if ($check_result === false) {
        error_log("Query execution failed: " . $conn->error);
        echo json_encode(['success' => false, 'error' => 'Database query execution failed']);
        exit;
    }

    if ($check_result->num_rows > 0) {
        $row = $check_result->fetch_assoc();
        $new_quantity = $row['quantity'] + $quantity;
        $update_query = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
        if ($update_query === false) {
            error_log("Prepare failed: " . $conn->error);
            echo json_encode(['success' => false, 'error' => 'Database query preparation failed']);
            exit;
        }
        $update_query->bind_param("ii", $new_quantity, $row['id']);
        $success = $update_query->execute();
        $update_query->close();
    } else {
        $insert_query = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
        if ($insert_query === false) {
            error_log("Prepare failed: " . $conn->error);
            echo json_encode(['success' => false, 'error' => 'Database query preparation failed']);
            exit;
        }
        $insert_query->bind_param("iii", $user_id, $product_id, $quantity);
        $success = $insert_query->execute();
        $insert_query->close();
    }
    $check_query->close();

    if ($success) {
        error_log("Cart updated successfully: user_id=$user_id, product_id=$product_id, quantity=$quantity");
        echo json_encode(['success' => true]);
    } else {
        error_log("Update/Insert failed: " . $conn->error);
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);
exit;
?>