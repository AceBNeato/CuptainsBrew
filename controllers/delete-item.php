<?php
$conn = require_once __DIR__ . '/../config/database.php';


if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['item_id']) && isset($_POST['item_category'])) {
    $id = intval($_POST['item_id']);
    $item_category = $_POST['item_category'];

    // Whitelist table names
    $allowed_categories = ['coffee', 'non_coffee', 'frappe', 'milktea', 'soda'];
    if (!in_array($item_category, $allowed_categories)) {
        die("Invalid category.");
    }

    $table = $conn->real_escape_string($item_category);
    $sql = "DELETE FROM `$table` WHERE id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: /views/admin/Admin-Menu.php");
        exit();
    } else {
        echo "Error deleting item: " . $conn->error;
    }

    $stmt->close();
    $conn->close();
} else {
    echo "Invalid request.";
}
?>
