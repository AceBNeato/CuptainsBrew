<?php
$conn = new mysqli("localhost", "root", "", "cafe_db");
$result = $conn->query("SELECT * FROM soda");

while ($row = $result->fetch_assoc()) {
    echo "<div class='menu-item'>";
    echo "<img src='/" . $row['item_image'] . "' alt='{$row['item_name']}'>";
    echo "<h3>{$row['item_name']}</h3>";
    echo "<p>{$row['item_description']}</p>";
    echo "<p>₱{$row['item_price']}</p>";
    echo "</div>";
}
?>
