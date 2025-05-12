<?php
session_start();
global $conn;
require_once __DIR__ . '../../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cart_id = (int)$_POST['cart_id'];
    $user_id = $_SESSION['user_id'];

    $delete_query = $conn->query("DELETE FROM cart WHERE id = $cart_id AND user_id = $user_id");
    if ($delete_query) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
}
?>