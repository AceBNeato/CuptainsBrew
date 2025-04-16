<?php
$conn = new mysqli("localhost", "root", "", "cafe_db");
$result = $conn->query("SELECT * FROM coffee");

while ($row = $result->fetch_assoc()) {
    echo "<div class='menu-item'>";
    echo "<img src='/" . $row['image'] . "' alt='{$row['name']}'>";
    echo "<h3>{$row['name']}</h3>";
    echo "<p>{$row['description']}</p>";
    echo "<p>₱{$row['price']}</p>";
    echo "</div>";
}
?>
