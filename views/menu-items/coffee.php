<?php
$conn = new mysqli("localhost", "root", "", "cafe_db");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$result = $conn->query("SELECT * FROM coffee");

while ($row = $result->fetch_assoc()) {
    echo "<div class='menu-card'>";
    echo "<img src='/{$row['item_image']}' alt='{$row['item_name']}' class='menu-image'>";
    echo "<div class='menu-content'>";
    echo "<h2 class='menu-title'>{$row['item_name']}</h2>";
    echo "<p class='menu-price'>₱ {$row['item_price']}</p>";
    echo "<p class='menu-desc'>{$row['item_description']}</p>";
    echo "</div>";
    echo "<button class='menu-add' onclick='editItem(
        \"{$row['item_name']}\", 
        {$row['item_price']}, 
        \"{$row['item_description']}\", 
        \"{$row['item_image']}\", 
        {$row['id']}
    )'>+</button>";
    echo "</div>";
}

$conn->close();
?>
