<?php
require_once __DIR__ . '/../../../config.php';

try {
    $database = new Database();
    $conn = $database->getConnection();

    $category = $_GET['category'] ?? 'coffee';
    $searchTerm = $_GET['search'] ?? '';

    $categoryStmt = $conn->prepare("SELECT id FROM categories WHERE name = ?");
    $categoryName = str_replace('_', ' ', $category);
    $categoryStmt->execute([$categoryName]);
    $categoryRow = $categoryStmt->fetch(PDO::FETCH_ASSOC);

    if ($categoryRow) {
        $categoryId = $categoryRow['id'];

        if (!empty($searchTerm)) {
            $productStmt = $conn->prepare("SELECT * FROM products WHERE category_id = ? AND item_name LIKE ?");
            $searchParam = "%$searchTerm%";
            $productStmt->execute([$categoryId, $searchParam]);
        } else {
            $productStmt = $conn->prepare("SELECT * FROM products WHERE category_id = ?");
            $productStmt->execute([$categoryId]);
        }

        if ($productStmt->rowCount() > 0) {
            while ($row = $productStmt->fetch(PDO::FETCH_ASSOC)) {
                echo "<div class='menu-card' id='menuCard-{$row['id']}'>";
                echo "<img src='/public/{$row['item_image']}' alt='{$row['item_name']}' class='menu-image'>";
                echo "<div class='menu-content'>";
                echo "<h2 class='menu-title'>{$row['item_name']}</h2>";
                echo "<p class='menu-price'>₱ {$row['item_price']}</p>";
                echo "<p class='menu-desc'>{$row['item_description']}</p>";
                echo "</div>";
                echo "<button class='menu-manage' onclick='openManageModal(
                    \"" . htmlspecialchars($row['item_name'], ENT_QUOTES) . "\", 
                    {$row['item_price']}, 
                    \"" . htmlspecialchars($row['item_description'], ENT_QUOTES) . "\", 
                    \"" . htmlspecialchars($row['item_image'], ENT_QUOTES) . "\", 
                    {$row['id']},
                    \"" . htmlspecialchars($category, ENT_QUOTES) . "\"
                )'>+</button>";
                echo "</div>";
            }
        } else {
            echo "<div class='no-items'>No items found" .
                (empty($searchTerm) ? " in this category." : " matching your search.") .
                "</div>";
        }
    } else {
        echo "<div class='no-items'>Category not found.</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>Error loading menu items: " . htmlspecialchars($e->getMessage()) . "</div>";
    error_log("Menu items error: " . $e->getMessage());
}
