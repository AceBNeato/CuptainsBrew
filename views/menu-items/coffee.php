<?php
$conn = new mysqli("localhost", "root", "", "cafe_db");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$category = "coffee"; // Adjust based on file
$result = $conn->query("SELECT * FROM coffee");

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<div class='menu-card'>";
        echo "<img src='/public/{$row['item_image']}' alt='{$row['item_name']}' class='menu-image'>";
        echo "<div class='menu-content'>";
        echo "<h2 class='menu-title'>{$row['item_name']}</h2>";
        echo "<p class='menu-price'>₱ {$row['item_price']}</p>";
        echo "<p class='menu-desc'>{$row['item_description']}</p>";
        echo "</div>";
        echo "<button class='menu-manage' onclick='openManageModal(
            \"{$row['item_name']}\", 
            {$row['item_price']}, 
            \"{$row['item_description']}\", 
            \"{$row['item_image']}\", 
            {$row['id']},
            \"$category\"
        )'>+</button>";
        echo "</div>";
    }
} else {
    echo "<div class='no-items'>No items available in this category.</div>";
}

$conn->close();
?>
