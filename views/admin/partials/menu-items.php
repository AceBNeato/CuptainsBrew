<?php
require_once __DIR__ . '../../../../config.php';

$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 1;
$searchTerm = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

// Always output the container for the menu list
echo "<div id='menu-list-container'>";

try {
    // Verify the category exists
    $categoryQuery = $conn->query("SELECT id, name FROM categories WHERE id = $category_id");
    
    if (!$categoryQuery) {
        throw new Exception("Category query failed: " . $conn->error);
    }

    $categoryRow = $categoryQuery->fetch_assoc();

    if (!$categoryRow) {
        echo "<div class='no-items'>Category not found.</div>";
    } else {
        $query = "SELECT * FROM products WHERE category_id = $category_id";
        
        if (!empty($searchTerm)) {
            $query .= " AND item_name LIKE '%$searchTerm%'";
        }

        $products = $conn->query($query);
        
        if (!$products) {
            throw new Exception("Products query failed: " . $conn->error);
        }

        if ($products->num_rows > 0) {
            while ($row = $products->fetch_assoc()) {
                $name = htmlspecialchars($row['item_name'], ENT_QUOTES);
                $desc = htmlspecialchars($row['item_description'], ENT_QUOTES);
                $image = htmlspecialchars($row['item_image'], ENT_QUOTES);
                
                echo "<div class='menu-card' id='menuCard-{$row['id']}'>
                        <img src='/public/{$row['item_image']}' alt='$name' class='menu-image' style='width: 150px; height:auto; margin: 0px 40px 0px 0px;'>
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
                            $category_id
                        )'>+</button>
                      </div>";
            }
        } else {
            $message = empty($searchTerm) ? "in this category." : "matching your search.";
            echo "<div class='no-items'>No items found $message</div>";
        }
    }
} catch (Exception $e) {
    echo "<div class='error-message'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</div>"; // Close menu-list-container
?>