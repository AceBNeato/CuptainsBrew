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
                
                // Check if item has variations
                $hasVariations = $row['has_variation'] == 1;
                $variationsHtml = '';
                
                if ($hasVariations) {
                    // Get variations
                    $varQuery = "SELECT * FROM product_variations WHERE product_id = {$row['id']}";
                    $variations = $conn->query($varQuery);
                    
                    if ($variations && $variations->num_rows > 0) {
                        $variationsHtml = '<div class="variation-tags" style="display: flex; gap: 8px; margin-top: 8px;">';
                        
                        while ($var = $variations->fetch_assoc()) {
                            $varType = htmlspecialchars($var['variation_type'], ENT_QUOTES);
                            $varPrice = number_format($var['price'], 2);
                            $bgColor = $varType === 'Hot' ? '#ffcccb' : '#cce5ff';
                            $textColor = $varType === 'Hot' ? '#e74c3c' : '#0056b3';
                            
                            $variationsHtml .= "<span style='background: $bgColor; color: $textColor; padding: 2px 6px; border-radius: 4px; font-size: 0.8rem;'>$varType: ₱$varPrice</span>";
                        }
                        
                        $variationsHtml .= '</div>';
                    }
                }
                
                echo "<div class='menu-card' id='menuCard-{$row['id']}'>
                        <img src='/public/{$row['item_image']}' alt='$name' class='menu-image' style='width: 150px; height: 150px; margin: 0px 40px 0px 0px; object-fit: cover; border-radius: 8px;'>
                        <div class='menu-content'>
                            <h2 class='menu-title'>$name</h2>
                            <p class='menu-price'>₱ {$row['item_price']}</p>
                            <p class='menu-desc'>$desc</p>
                            $variationsHtml
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