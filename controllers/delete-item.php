<?php
$conn = new mysqli("localhost", "root", "", "cafe_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$data = json_decode(file_get_contents('php://input'), true);  // Get the POST data

$id = $data['id'];  // Extract the item ID

// Prepare SQL to delete the item
$sql = "DELETE FROM coffee WHERE id = ?";  // Replace 'coffee' with your dynamic table logic if needed

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "error" => $conn->error]);
}

$stmt->close();
$conn->close();
?>
