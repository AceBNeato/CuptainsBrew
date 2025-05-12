<?php
session_start();
global $conn;
require_once __DIR__ . '/../../config.php';

// Handle add_to_cart action directly
if (isset($_GET['action']) && $_GET['action'] === 'add_to_cart') {
    header('Content-Type: application/json');

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'User not logged in']);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'error' => 'Invalid request method']);
        exit;
    }

    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

    if ($product_id <= 0 || $quantity <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid product ID or quantity']);
        exit;
    }

    $user_id = $_SESSION['user_id'];

    $check_query = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
    if ($check_query === false) {
        echo json_encode(['success' => false, 'error' => 'Database query preparation failed']);
        exit;
    }
    $check_query->bind_param("ii", $user_id, $product_id);
    $check_query->execute();
    $check_result = $check_query->get_result();

    if ($check_result === false) {
        echo json_encode(['success' => false, 'error' => 'Database query execution failed']);
        exit;
    }

    if ($check_result->num_rows > 0) {
        $row = $check_result->fetch_assoc();
        $new_quantity = $row['quantity'] + $quantity;
        $update_query = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
        if ($update_query === false) {
            echo json_encode(['success' => false, 'error' => 'Database query preparation failed']);
            exit;
        }
        $update_query->bind_param("ii", $new_quantity, $row['id']);
        $success = $update_query->execute();
        $update_query->close();
    } else {
        $insert_query = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
        if ($insert_query === false) {
            echo json_encode(['success' => false, 'error' => 'Database query preparation failed']);
            exit;
        }
        $insert_query->bind_param("iii", $user_id, $product_id, $quantity);
        $success = $insert_query->execute();
        $insert_query->close();
    }
    $check_query->close();

    if ($success) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Captain's Brew Cafe</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary: #2C6E8A;
            --primary-dark: #235A73;
            --primary-light: #A9D6E5;
            --secondary: #4a3b2b;
            --secondary-light: #FFFAEE;
            --secondary-lighter: #FFDBB5;
            --white: #fff;
            --shadow-light: 0 2px 5px rgba(74, 59, 43, 0.2);
            --shadow-medium: 0 4px 8px rgba(44, 110, 138, 0.2);
            --shadow-dark: 0 5px 15px rgba(74, 59, 43, 0.5);
            --border-radius: 10px;
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: var(--white);
            color: var(--secondary);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        /* Header */
        .header {
            display: flex;
            align-items: center;
            padding: 0.75rem 2rem;
            background: linear-gradient(135deg, var(--secondary-light), var(--secondary-lighter));
            box-shadow: var(--shadow-light);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        #logo {
            height: 60px;
            margin-right: 3rem;
            transition: var(--transition);
        }

        #logo:hover {
            transform: scale(1.05);
        }

        .hamburger {
            display: none;
            font-size: 1.5rem;
            cursor: pointer;
            margin-left: auto;
            color: var(--secondary);
        }

        .button-container {
            display: flex;
            align-items: center;
            flex: 1;
        }

        .nav-button {
            padding: 0.75rem 1.5rem;
            margin-right: 1rem;
            color: var(--secondary);
            font-weight: 500;
            position: relative;
            transition: var(--transition);
        }

        .nav-button::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 2px;
            background: var(--primary);
            transition: var(--transition);
            transform: translateX(-50%);
        }

        .nav-button:hover::after {
            width: 70%;
        }

        /* Menu Dropdown */
        .menu-dropdown {
            position: relative;
        }

        .menu-dropdown .nav-button {
            display: flex;
            align-items: center;
        }

        .menu-dropdown .nav-button::after {
            display: none;
        }

        .menu-dropdown .nav-button::before {
            content: '▼';
            font-size: 0.6rem;
            margin-left: 0.5rem;
            transition: var(--transition);
        }

        .menu-dropdown:hover .nav-button::before,
        .menu-dropdown:focus-within .nav-button::before {
            transform: rotate(180deg);
        }

        .menu-dropdown:hover .dropdown-content,
        .menu-dropdown:focus-within .dropdown-content {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown-content {
            position: absolute;
            top: 100%;
            left: 0;
            background: var(--white);
            min-width: 180px;
            box-shadow: var(--shadow-medium);
            border-radius: var(--border-radius);
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: var(--transition);
            z-index: 100;
            padding: 0.5rem 0;
            margin-top: 0.5rem;
            border: 1px solid var(--primary-light);
        }

        .dropdown-content::before {
            content: '';
            position: absolute;
            top: -6px;
            left: 20px;
            width: 12px;
            height: 12px;
            background: var(--white);
            transform: rotate(45deg);
            box-shadow: -2px -2px 5px rgba(0,0,0,0.05);
            border-top: 1px solid var(--primary-light);
            border-left: 1px solid var(--primary-light);
        }

        .menu-item {
            display: block;
            padding: 0.75rem 1.5rem;
            color: var(--secondary);
            font-size: 0.95rem;
            transition: var(--transition);
        }

        .menu-item:hover {
            background-color: var(--secondary-light);
            color: var(--primary);
        }

        /* Menu Bar */
        .menu-bar {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 1rem 2rem;
            margin: 1rem auto;
            max-width: 1000px;
            background: var(--secondary-light);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-light);
        }

        .search-box {
            width: 100%;
            display: flex;
            align-items: center;
        }

        .search-input {
            padding: 0.75rem 1.5rem;
            border: 2px solid var(--primary-light);
            border-radius: var(--border-radius);
            outline: none;
            font-size: 1rem;
            color: var(--secondary);
            transition: var(--transition);
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
        }

        .search-input:focus {
            border-color: var(--primary);
            box-shadow: var(--shadow-medium);
        }

        /* Icons and Profile */
        .icon-container {
            margin-left: auto;
            display: flex;
            align-items: center;
        }

        .nav-icon {
            margin-left: 1rem;
            position: relative;
        }

        .nav-icon img {
            width: 24px;
            height: 24px;
            transition: var(--transition);
        }

        .nav-icon:hover img {
            transform: scale(1.1);
        }

        .profile {
            display: flex;
            align-items: center;
            margin-left: 1.5rem;
            position: relative;
            cursor: pointer;
        }

        .profile img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            margin-right: 0.5rem;
        }

        .profile span {
            font-size: 0.9rem;
            font-weight: 500;
        }

        .profile .dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background: var(--white);
            min-width: 160px;
            box-shadow: var(--shadow-medium);
            border-radius: var(--border-radius);
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: var(--transition);
            z-index: 100;
            padding: 0.5rem 0;
            margin-top: 0.75rem;
            border: 1px solid var(--primary-light);
        }

        .profile:hover .dropdown,
        .profile:focus-within .dropdown {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .profile .dropdown::before {
            content: '';
            position: absolute;
            top: -6px;
            right: 20px;
            width: 12px;
            height: 12px;
            background: var(--white);
            transform: rotate(45deg);
            box-shadow: -2px -2px 5px rgba(0,0,0,0.05);
            border-top: 1px solid var(--primary-light);
            border-left: 1px solid var(--primary-light);
        }

        .profile .dropdown a {
            display: block;
            padding: 0.6rem 1rem;
            color: var(--secondary);
            font-size: 0.9rem;
            transition: var(--transition);
        }

        .profile .dropdown a:hover {
            background-color: var(--secondary-light);
            color: var(--primary);
        }

        /* Menu Cards */
        .menu-card {
            display: flex;
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-light);
            padding: 1rem;
            margin-bottom: 1.5rem;
            transition: var(--transition);
            align-items: center;
        }

        .menu-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-medium);
        }

        .menu-image {
            border-radius: var(--border-radius);
            object-fit: cover;
        }

        .menu-content {
            flex: 1;
            padding: 0 1rem;
        }

        .menu-title {
            font-size: 1.3rem;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .menu-price {
            font-size: 1.1rem;
            color: var(--secondary);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .menu-desc {
            font-size: 0.9rem;
            color: var(--secondary);
        }

        .menu-manage {
            background: var(--primary);
            color: var(--white);
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .menu-manage:hover {
            background: var(--primary-dark);
            transform: scale(1.1);
        }

        .no-items {
            text-align: center;
            padding: 2rem;
            color: var(--secondary);
            font-size: 1.1rem;
        }

        .error-message {
            background: #ffe0e0;
            color: #d32f2f;
            padding: 1rem;
            border-radius: var(--border-radius);
            margin: 1rem 0;
            text-align: center;
        }

        /* Category Title */
        .category-title {
            text-align: center;
            font-size: 2rem;
            color: var(--primary);
            margin-top: 2rem;
            margin-bottom: 1rem;
            position: relative;
            text-transform: capitalize;
        }

        .category-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: var(--primary-light);
            border-radius: 3px;
        }

        /* Modal Styles */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 2000;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s, visibility 0.3s;
        }

        .modal.active {
            opacity: 1;
            visibility: visible;
        }

        .modal-content {
            background: var(--white);
            border-radius: var(--border-radius);
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            padding: 2rem;
            position: relative;
            box-shadow: var(--shadow-dark);
        }

        .close-modal {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--secondary);
            transition: var(--transition);
        }

        .close-modal:hover {
            color: var(--primary);
        }

        .modal-body {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .modal-image {
            width: 100%;
            max-height: 300px;
            object-fit: cover;
            border-radius: var(--border-radius);
        }

        .modal-details {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .modal-details h2 {
            color: var(--primary);
            font-size: 1.8rem;
            margin: 0;
        }

        .modal-details .price {
            color: var(--secondary);
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
        }

        .modal-details .description {
            color: var(--secondary);
            line-height: 1.6;
        }

        .quantity-control {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 1rem 0;
        }

        .quantity-btn {
            background: var(--primary-light);
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            font-size: 1.2rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
        }

        .quantity-btn:hover {
            background: var(--primary);
            color: white;
        }

        #productQuantity {
            width: 60px;
            text-align: center;
            padding: 0.5rem;
            border: 2px solid var(--primary-light);
            border-radius: var(--border-radius);
            font-size: 1rem;
        }

        .add-to-cart-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 1rem;
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            margin-top: 1rem;
        }

        .add-to-cart-btn:hover {
            background: var(--primary-dark);
        }

        .cart-notification {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--primary);
            color: white;
            padding: 1rem 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-dark);
            opacity: 0;
            transition: opacity 0.3s;
            z-index: 3000;
        }

        .cart-notification.show {
            opacity: 1;
        }

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .header {
                padding: 0.75rem 1rem;
            }
            
            #logo {
                height: 40px;
                margin-right: 1rem;
            }
            
            .hamburger {
                display: block;
            }
            
            .button-container {
                position: fixed;
                top: 70px;
                left: 0;
                width: 100%;
                flex-direction: column;
                background: var(--white);
                box-shadow: var(--shadow-medium);
                padding: 1rem 0;
                transform: translateY(-100%);
                opacity: 0;
                visibility: hidden;
                transition: var(--transition);
                z-index: 999;
                align-items: flex-start;
            }
            
            .button-container.active {
                transform: translateY(0);
                opacity: 1;
                visibility: visible;
            }
            
            .nav-button {
                width: 100%;
                padding: 0.75rem 1.5rem;
                margin: 0;
            }
            
            .nav-button::after {
                display: none;
            }
            
            .menu-dropdown {
                width: 100%;
            }
            
            .dropdown-content {
                position: static;
                opacity: 1;
                visibility: visible;
                transform: none;
                box-shadow: none;
                margin: 0;
                padding: 0;
                width: 100%;
                max-height: 0;
                overflow: hidden;
                transition: max-height 0.3s ease;
                border: none;
            }
            
            .menu-dropdown.active .dropdown-content {
                max-height: 300px;
            }
            
            .dropdown-content::before {
                display: none;
            }
            
            .menu-item {
                padding: 0.6rem 2.5rem;
            }
            
            .icon-container {
                width: 100%;
                justify-content: flex-end;
                padding: 0 1.5rem;
                margin: 0.5rem 0;
            }
            
            .profile {
                width: 100%;
                padding: 0.75rem 1.5rem;
                margin: 0;
                justify-content: space-between;
            }
            
            .profile .dropdown {
                position: static;
                opacity: 1;
                visibility: visible;
                transform: none;
                box-shadow: none;
                margin: 0;
                padding: 0;
                width: 100%;
                max-height: 0;
                overflow: hidden;
                transition: max-height 0.3s ease;
                border: none;
            }
            
            .profile.active .dropdown {
                max-height: 300px;
            }
            
            .profile .dropdown::before {
                display: none;
            }
            
            .profile .dropdown a {
                padding: 0.6rem 2.5rem;
            }
            
            .menu-bar {
                padding: 1rem;
                margin: 1rem;
            }
            
            .search-input {
                padding: 0.75rem 1rem;
            }
            
            .products-container {
                padding: 1rem;
                grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
                gap: 1rem;
            }
            
            .menu-card {
                flex-direction: column;
                text-align: center;
            }
            
            .menu-image {
                width: 100%;
                height: auto;
                margin: 0 0 1rem 0;
            }
            
            .menu-content {
                padding: 0;
                margin-bottom: 1rem;
            }
            
            .menu-manage {
                align-self: center;
            }

            .modal-body {
                flex-direction: column;
            }
            
            .modal-image {
                width: 100%;
                max-height: none;
            }
            
            .modal-details {
                width: 100%;
                padding-left: 0;
            }
        }

        @media (min-width: 768px) {
            .modal-body {
                flex-direction: row;
            }
            
            .modal-image {
                width: 50%;
                max-height: none;
            }
            
            .modal-details {
                width: 50%;
                padding-left: 1.5rem;
            }
        }

        #menu-list-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 0 1rem;
        }
    </style>
</head>
<body>
    <!-- Header Section -->
    <header class="header">
        <img src="/public/images/LOGO.png" id="logo" alt="Captain's Brew Logo">

        <!-- Navigation Menu -->
        <div id="hamburger-menu" class="hamburger">☰</div>

        <nav class="button-container" id="nav-menu">
            <a href="/views/users/user-home.php" class="nav-button">Home</a>
            
            <div class="menu-dropdown">
                <a href="/views/users/user-menu.php" class="nav-button">Menu</a>
                <div class="dropdown-content">
                    <?php
                    $categoryQuery = $conn->query("SELECT DISTINCT name FROM categories ORDER BY id");
                    if ($categoryQuery && $categoryQuery->num_rows > 0) {
                        while ($catRow = $categoryQuery->fetch_assoc()) {
                            $catName = htmlspecialchars($catRow['name'], ENT_QUOTES);
                            $catSlug = strtolower(str_replace(' ', '-', $catName));
                            echo "<a href='/views/users/user-menu.php?category=" . urlencode($catSlug) . "' class='menu-item'>$catName</a>";
                        }
                    }
                    ?>
                </div>
            </div>
            
            <a href="/views/users/user-career.php" class="nav-button">Career</a>
            <a href="/views/users/user-aboutus.php" class="nav-button">About Us</a>

            <div class="icon-container">
                <a href="/views/users/cart.php" id="cart-icon" class="nav-icon">
                    <img src="/public/images/icons/cart-icon.png" alt="Cart">
                </a>
            </div>

            <div class="profile">
                <img src="/public/images/icons/profile-icon.png" alt="Profile">
                <span>
                    <?php 
                    echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Guest'; 
                    ?>
                </span>
                <div class="dropdown">
                    <a href="/views/users/account.php">My Account</a>
                    <a href="/views/users/purchases.php">My Purchase</a>
                    <a href="/logout.php" id="logout-button">Logout</a>
                </div>
            </div>
        </nav>
    </header>

    <!-- Menu Bar with Search -->
    <div class="menu-bar">
        <div class="search-box">
            <input type="text" class="search-input" placeholder="🔍 Search for drinks..." 
                   id="search-input" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                   onkeyup="handleSearch(event)"/>
        </div>
    </div>

    <!-- Category Title -->
    <h1 class="category-title">
        <?php
        $currentCategory = $_GET['category'] ?? 'coffee';
        $displayCategory = str_replace('-', ' ', $currentCategory);
        echo ucwords(htmlspecialchars($displayCategory));
        ?>
    </h1>

    <!-- Menu List Container -->
    <div id="menu-list-container">
        <?php
        $category = $_GET['category'] ?? 'coffee';
        $searchTerm = $_GET['search'] ?? '';
        
        try {
            $categoryName = str_replace('-', ' ', $category);
            $categoryQuery = $conn->query("SELECT id FROM categories WHERE name = '" . $conn->real_escape_string($categoryName) . "'");
            
            if (!$categoryQuery) {
                throw new Exception("Category query failed: " . $conn->error);
            }

            $categoryRow = $categoryQuery->fetch_assoc();

            if (!$categoryRow) {
                echo "<div class='no-items'>Category not found.</div>";
            } else {
                $categoryId = $categoryRow['id'];
                $query = "SELECT * FROM products WHERE category_id = $categoryId";
                
                if (!empty($searchTerm)) {
                    $searchTerm = $conn->real_escape_string($searchTerm);
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
                                <img src='/public/{$image}' alt='$name' class='menu-image' style='width: 150px; height:auto; margin: 0px 40px 0px 0px;'>
                                <div class='menu-content'>
                                    <h2 class='menu-title'>$name</h2>
                                    <p class='menu-price'>₱ {$row['item_price']}</p>
                                    <p class='menu-desc'>$desc</p>
                                </div>
                                <button class='menu-manage'>+</button>
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
        ?>
    </div>

    <!-- Product View Modal -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <span class="close-modal">×</span>
            <div class="modal-body">
                <img id="modalProductImage" src="" alt="Product Image" class='modal-image'>
                <div class='modal-details'>
                    <h2 id='modalProductName'></h2>
                    <p id='modalProductPrice' class='price'></p>
                    <p id='modalProductDesc' class='description'></p>
                    
                    <div class='quantity-control'>
                        <button class='quantity-btn minus'>-</button>
                        <input type='number' id='productQuantity' value='1' min='1' max='10'>
                        <button class='quantity-btn plus'>+</button>
                    </div>
                    
                    <button id='addToCartModal' class='add-to-cart-btn'>Add to Cart</button>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        // Mobile Menu Toggle
        document.getElementById('hamburger-menu').addEventListener('click', function() {
            document.getElementById('nav-menu').classList.toggle('active');
        });

        // Mobile Dropdown Handling
        if (window.innerWidth <= 768) {
            const menuDropdown = document.querySelector('.menu-dropdown');
            menuDropdown.addEventListener('click', function(e) {
                if (e.target.classList.contains('nav-button')) {
                    e.preventDefault();
                    this.classList.toggle('active');
                }
            });

            const profile = document.querySelector('.profile');
            profile.addEventListener('click', function(e) {
                if (!e.target.closest('.dropdown')) {
                    e.preventDefault();
                    this.classList.toggle('active');
                }
            });
        }

        // Search Functionality
        function handleSearch(event) {
            if (event.key === 'Enter') {
                let category = '<?= htmlspecialchars($currentCategory) ?>';
                let searchQuery = document.getElementById('search-input').value;
                let url = '/views/users/user-menu.php?category=' + encodeURIComponent(category);
                
                if (searchQuery) {
                    url += '&search=' + encodeURIComponent(searchQuery);
                }
                
                window.location.href = url;
            }
        }

        // Product Modal Functionality
        let currentProduct = null;

        function showProductModal(product) {
            currentProduct = product;
            
            // Set modal content
            document.getElementById('modalProductImage').src = '/public/' + product.image;
            document.getElementById('modalProductName').textContent = product.name;
            document.getElementById('modalProductPrice').textContent = '₱' + product.price;
            document.getElementById('modalProductDesc').textContent = product.desc || 'No description available';
            document.getElementById('productQuantity').value = 1;
            
            // Show modal
            document.getElementById('productModal').classList.add('active');
        }

        function closeProductModal() {
            document.getElementById('productModal').classList.remove('active');
            currentProduct = null;
        }

        function setupModalListeners() {
            // Close modal when clicking X
            document.querySelector('.close-modal').addEventListener('click', closeProductModal);
            
            // Close modal when clicking outside content
            document.getElementById('productModal').addEventListener('click', function(e) {
                if (e.target === this) {
                    closeProductModal();
                }
            });
            
            // Quantity controls
            document.querySelector('.quantity-btn.minus').addEventListener('click', function() {
                const quantityInput = document.getElementById('productQuantity');
                if (quantityInput.value > 1) {
                    quantityInput.value--;
                }
            });
            
            document.querySelector('.quantity-btn.plus').addEventListener('click', function() {
                const quantityInput = document.getElementById('productQuantity');
                if (quantityInput.value < 10) {
                    quantityInput.value++;
                }
            });
            
            // Add to cart from modal
            document.getElementById('addToCartModal').addEventListener('click', function() {
                const quantity = parseInt(document.getElementById('productQuantity').value);
                addToCart(
                    currentProduct.id, 
                    currentProduct.name, 
                    currentProduct.price, 
                    currentProduct.image,
                    quantity
                );
                closeProductModal();
            });
        }

        // Cart Functionality
        function addToCart(productId, name, price, image, quantity = 1) {
            fetch('/views/users/user-menu.php?action=add_to_cart', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `product_id=${productId}&quantity=${quantity}`
            })
            .then(response => {
                console.log('Response Status:', response.status);
                console.log('Response Headers:', response.headers.get('content-type'));
                return response.text();
            })
            .then(text => {
                console.log('Raw Response:', text);
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        showCartNotification(`${name} added to cart (${quantity}x)`);
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: `${name} added to cart (${quantity}x)`,
                            timer: 1500,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to add to cart: ' + data.error,
                        });
                    }
                } catch (e) {
                    console.error('JSON Parse Error:', e);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Invalid response from server: ' + e.message,
                    });
                }
            })
            .catch(error => {
                console.error('Fetch Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred: ' + error.message,
                });
            });
        }

        // Cart Notification
        function showCartNotification(message) {
            const notification = document.createElement('div');
            notification.className = 'cart-notification';
            notification.textContent = message;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.classList.add('show');
            }, 10);
            
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 3000);
        }

        // Initialize when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            setupModalListeners();
            
            // Add click event to all + buttons
            document.querySelectorAll('.menu-manage').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const card = this.closest('.menu-card');
                    const productId = card.id.replace('menuCard-', '');
                    const productName = card.querySelector('.menu-title').textContent;
                    const productPrice = parseFloat(card.querySelector('.menu-price').textContent.replace('₱ ', ''));
                    const productImage = card.querySelector('.menu-image').src.split('/public/')[1];
                    const productDesc = card.querySelector('.menu-desc').textContent;
                    
                    showProductModal({
                        id: productId,
                        name: productName,
                        price: productPrice,
                        image: productImage,
                        desc: productDesc
                    });
                });
            });
        });
    </script>
</body>
</html>