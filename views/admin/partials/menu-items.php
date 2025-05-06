<?php
global $conn;
require_once __DIR__ . '../../../../config.php';

$category = $_GET['category'] ?? 'coffee';
$searchTerm = $_GET['search'] ?? '';

$categoryName = str_replace('_', ' ', $category);
$categoryQuery = $conn->query("SELECT id FROM categories WHERE name = '$categoryName'");
$categoryRow = $categoryQuery->fetch_assoc();

if (!$categoryRow) {
    echo "<div class='no-items'>Category not found.</div>";
    exit;
}

$categoryId = $categoryRow['id'];

$query = "SELECT * FROM products WHERE category_id = $categoryId";
if (!empty($searchTerm)) {
    $searchTerm = $conn->real_escape_string($searchTerm);
    $query .= " AND item_name LIKE '%$searchTerm%'";
}

$products = $conn->query($query);

if ($products->num_rows > 0) {
    while ($row = $products->fetch_assoc()) {
        $name = htmlspecialchars($row['item_name'], ENT_QUOTES);
        $desc = htmlspecialchars($row['item_description'], ENT_QUOTES);
        $image = htmlspecialchars($row['item_image'], ENT_QUOTES);
        
        echo "<div class='menu-card' id='menuCard-{$row['id']}'>
                <img src='/public/{$row['item_image']}' alt='$name' class='menu-image' style='width: 150px; height:auto;'>
                <div class='menu-content'>
                    <h2 class='menu-title'>$name</h2>
                    <p class='menu-price'>₱ {$row['item_price']}</p>
                    <p class='menu-desc'>$desc</p>
                </div>
                <button class='menu-manage' onclick='openManageModal(
                    \"$name\", 
                    {$row['item_price']}, 
                    \"$desc\", 
                    \"$image\", 
                    {$row['id']},
                    \"$category\"
                )'>+</button>
              </div>";
    }
} else {
    $message = empty($searchTerm) ? "in this category." : "matching your search.";
    echo "<div class='no-items'>No items found $message</div>";
}