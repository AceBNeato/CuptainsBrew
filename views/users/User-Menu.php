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
            --primary-dark: #1B4A5E;
            --primary-light: #B3E0F2;
            --secondary: #4A3B2B;
            --secondary-light: #FFF8E7;
            --secondary-lighter: #FFE8C2;
            --white: #FFFFFF;
            --black: #1A1A1A;
            --shadow-light: 0 4px 12px rgba(74, 59, 43, 0.15);
            --shadow-medium: 0 6px 16px rgba(44, 110, 138, 0.2);
            --shadow-dark: 0 8px 24px rgba(74, 59, 43, 0.3);
            --border-radius: 12px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
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
            padding: 1rem 2rem;
            background: linear-gradient(135deg, var(--secondary-light), var(--secondary-lighter));
            box-shadow: var(--shadow-light);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        #logo {
            height: 60px;
            margin-right: 2rem;
            transition: var(--transition);
        }

        #logo:hover {
            transform: scale(1.08);
            filter: brightness(1.1);
        }

        .hamburger {
            display: none;
            font-size: 1.75rem;
            cursor: pointer;
            color: var(--secondary);
            transition: var(--transition);
        }

        .hamburger:hover {
            color: var(--primary);
        }

        .button-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex: 1;
            gap: 0.5rem;
        }

        .nav-links {
            display: flex;
            gap: 0.5rem;
        }

        .nav-button {
            padding: 0.75rem 1.25rem;
            color: var(--secondary);
            font-weight: 500;
            font-size: 1rem;
            border-radius: 8px;
            transition: var(--transition);
            justify-self: center;
        }

        .nav-button:hover {
            background: var(--primary-light);
            color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .nav-button.active {
            background: var(--primary);
            color: var(--white);
            font-weight: 600;
        }

        /* Icons and Profile */
        .icon-profile-container {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .icon-container {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .nav-icon {
            position: relative;
            transition: var(--transition);
        }

        .nav-icon img {
            width: 28px;
            height: 28px;
            transition: var(--transition);
        }

        .nav-icon:hover img {
            transform: scale(1.15);
            filter: brightness(1.2);
        }

        .profile {
            display: flex;
            align-items: center;
            position: relative;
            cursor: pointer;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: var(--transition);
        }

        .profile:hover {
            background: var(--primary-light);
        }

        .profile img {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            margin-right: 0.75rem;
            border: 2px solid var(--primary-light);
        }

        .profile span {
            font-size: 0.95rem;
            font-weight: 500;
            color: var(--secondary);
        }

        .profile .dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background: var(--white);
            min-width: 180px;
            box-shadow: var(--shadow-medium);
            border-radius: var(--border-radius);
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: var(--transition);
            z-index: 100;
            padding: 0.75rem 0;
            margin-top: 0.5rem;
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
            top: -8px;
            right: 16px;
            width: 14px;
            height: 14px;
            background: var(--white);
            transform: rotate(45deg);
            border-top: 1px solid var(--primary-light);
            border-left: 1px solid var(--primary-light);
            box-shadow: -2px -2px 4px rgba(0, 0, 0, 0.05);
        }

        .profile .dropdown a {
            display: block;
            padding: 0.75rem 1.25rem;
            color: var(--secondary);
            font-size: 0.95rem;
            transition: var(--transition);
        }

        .profile .dropdown a:hover {
            background: var(--primary-light);
            color: var(--primary-dark);
        }

        /* Menu Bar */
        .menu-bar {
            display: flex;
            justify-content: center;
            padding: 1rem;
            margin: 0 0 2rem 0;
            width: 100%;
            background: var(--white);
            border-radius: var(--border-radius);
        }

        .search-box {
            width: 100%;
            max-width: 600px;
            position: relative;
        }

        .search-input {
            padding: 0.75rem 1.5rem;
            border: 2px solid var(--primary-light);
            border-radius: var(--border-radius);
            outline: none;
            font-size: 1rem;
            color: var(--secondary);
            width: 100%;
            transition: var(--transition);
            background: var(--white);
        }

        .search-input:focus {
            border-color: var(--primary);
        }

        .search-input::placeholder {
            color: var(--secondary);
            opacity: 0.7;
        }

        /* Left Navigation */
        .left-nav {
            width: 260px;
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-light);
            padding: 1.5rem;
            position: sticky;
            top: 90px;
            z-index: 100;
            height: calc(100vh - 100px);
            overflow-y: auto;
            border: 1px solid rgba(44, 110, 138, 0.1);
        }

        .left-nav-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid var(--primary-light);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .left-nav-list {
            list-style: none;
        }

        .left-nav-item {
            margin-bottom: 0.5rem;
        }

        .left-nav-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            color: var(--secondary);
            font-size: 1rem;
            font-weight: 500;
            border-radius: 8px;
            transition: var(--transition);
            position: relative;
        }

        .left-nav-link:hover {
            background: var(--primary-light);
            color: var(--primary-dark);
            transform: translateX(4px);
        }

        .left-nav-link.active {
            background: var(--primary);
            color: var(--white);
            font-weight: 600;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }

        .left-nav-link.active::before {
            content: '';
            position: absolute;
            left: -1rem;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 60%;
            background: var(--primary-dark);
            border-radius: 0 4px 4px 0;
        }

        /* Main Content Layout */
        .main-content {
            display: flex;
            min-height: calc(100vh - 200px);
        }

        #menu-list-container {
            flex: 1;
            padding: 0;
        }

        /* Category Title */
        .category-title {
            text-align: left;
            font-size: 2.5rem;
            color: var(--primary-dark);
            margin: 0 0 1.5rem 0;
            font-weight: 700;
            text-transform: capitalize;
            position: relative;
        }

        .category-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 100px;
            height: 4px;
            background: var(--primary-light);
            border-radius: 4px;
        }

        /* Menu Cards */
        .menu-card {
            display: grid;
            margin: 2vw;
            grid-template-columns: 150px 1fr auto;
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-light);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: var(--transition);
            align-items: center;
            gap: 1.5rem;
        }

        .menu-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-medium);
        }

        .menu-image {
            width: 150px;
            height: 150px;
            border-radius: var(--border-radius);
            object-fit: cover;
        }

        .menu-content {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .menu-title {
            font-size: 1.5rem;
            color: var(--primary-dark);
            font-weight: 600;
            margin: 0;
        }

        .menu-price {
            font-size: 1.25rem;
            color: var(--secondary);
            font-weight: 600;
        }

        .menu-desc {
            font-size: 0.95rem;
            color: var(--secondary);
            line-height: 1.5;
            opacity: 0.9;
        }

        .menu-manage {
            background: var(--primary);
            color: var(--white);
            border: none;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .menu-manage:hover {
            background: var(--primary-dark);
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(44, 110, 138, 0.3);
        }

        .no-items {
            text-align: center;
            padding: 2.5rem;
            color: var(--secondary);
            font-size: 1.2rem;
            font-weight: 500;
            background: var(--white);
        }

        .error-message {
            background: #FFEBEE;
            color: #D32F2F;
            padding: 1rem;
            border-radius: var(--border-radius);
            margin: 1.5rem 0;
            text-align: center;
            font-weight: 500;
        }

        /* Modal Styles */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.75);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 2000;
            opacity: 0;
            visibility: hidden;
            transition: var(--transition);
        }

        .modal.active {
            opacity: 1;
            visibility: visible;
        }

        .modal-content {
            background: var(--white);
            border-radius: var(--border-radius);
            width: 50%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            box-shadow: var(--shadow-dark);
        }

        .close-modal {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 1.75rem;
            cursor: pointer;
            color: var(--secondary);
            transition: var(--transition);
        }

        .close-modal:hover {
            color: var(--primary-dark);
            transform: rotate(90deg);
        }

        .modal-body {
            flex-direction: row;
            gap: 2rem;
        }

        .modal-image {
            width: 100%;
            max-height: 200px;
            object-fit: cover;
            border-radius: var(--border-radius);
        }

        .modal-details {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            padding: 1vw;
        }

        .modal-details h2 {
            color: var(--primary-dark);
            font-size: 2rem;
            font-weight: 600;
            margin: 0;
        }

        .modal-details .price {
            color: var(--secondary);
            font-size: 1.75rem;
            font-weight: 600;
        }

        .modal-details .description {
            color: var(--secondary);
            font-size: 1rem;
            line-height: 1.6;
            opacity: 0.9;
        }

        .quantity-control {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin: 1rem 0;
        }

        .quantity-btn {
            background: var(--primary-light);
            border: none;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            font-size: 1.4rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
        }

        .quantity-btn:hover {
            background: var(--primary);
            color: var(--white);
            box-shadow: 0 4px 12px rgba(44, 110, 138, 0.3);
        }

        #productQuantity {
            width: 80px;
            text-align: center;
            padding: 0.5rem;
            border: 2px solid var(--primary-light);
            border-radius: var(--border-radius);
            font-size: 1.1rem;
            background: var(--white);
        }

        .add-to-cart-btn {
            background: var(--primary);
            color: var(--white);
            border: none;
            padding: 1rem 2rem;
            border-radius: var(--border-radius);
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }

        .add-to-cart-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(44, 110, 138, 0.3);
        }

        .cart-notification {
            position: fixed;
            bottom: 24px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--primary);
            color: var(--white);
            padding: 1rem 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-dark);
            opacity: 0;
            transition: var(--transition);
            z-index: 3000;
            font-size: 1rem;
            font-weight: 500;
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
                height: 48px;
                margin-right: 1rem;
            }

            .hamburger {
                display: block;
                margin-left: auto;
            }

            .add-to-cart-btn {
            background: var(--primary);
            color: var(--white);
            border: none;
            padding: 1rem 2rem;
            border-radius: var(--border-radius);
            font-size: 2.5vw;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
        }

            .button-container {
                position: fixed;
                top: 64px;
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

            .nav-links {
                width: 100%;
                flex-direction: column;
            }

            .nav-button {
                width: 100%;
                padding: 0.75rem 1.5rem;
                margin: 0.25rem 0;
                text-align: left;
            }

            .icon-profile-container {
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
                width: 100%;
                max-height: 0;
                overflow: hidden;
                transition: max-height 0.3s ease;
                opacity: 1;
                visibility: visible;
                transform: none;
                box-shadow: none;
                margin: 0;
                padding: 0;
                border: none;
            }

            .profile.active .dropdown {
                max-height: 300px;
            }

            .profile .dropdown::before {
                display: none;
            }

            .profile .dropdown a {
                padding: 0.75rem 2.5rem;
            }

            .main-content {
                flex-direction: column;
                padding: 1rem;
            }

            .left-nav {
                width: 100%;
                position: static;
                height: auto;
                padding: 1rem;
                margin-bottom: 1rem;
                border-radius: var(--border-radius);
                box-shadow: var(--shadow-light);
            }

            .left-nav-title {
                font-size: 1.2rem;
                margin-bottom: 1rem;
            }

            .left-nav-list {
                display: flex;
                overflow-x: auto;
                gap: 0.75rem;
                padding-bottom: 0.75rem;
                scrollbar-width: thin;
                scrollbar-color: var(--primary-light) var(--white);
            }

            .left-nav-list::-webkit-scrollbar {
                height: 8px;
            }

            .left-nav-list::-webkit-scrollbar-track {
                background: var(--white);
            }

            .left-nav-list::-webkit-scrollbar-thumb {
                background: var(--primary-light);
                border-radius: 4px;
            }

            .left-nav-item {
                flex: 0 0 auto;
                margin-bottom: 0;
            }

            .left-nav-link {
                padding: 0.5rem 1rem;
                font-size: 0.95rem;
                white-space: nowrap;
                border-radius: 20px;
            }

            .left-nav-link:hover {
                transform: none;
            }

            .left-nav-link.active {
                background: var(--primary);
                color: var(--white);
            }

            .left-nav-link.active::before {
                display: none;
            }

            .menu-bar {
                margin: 0 0 1rem 0;
                padding: 0.75rem;
            }

            .category-title {
                font-size: 2rem;
                margin: 0 0 1rem 0;
                text-align: center;
            }

            .category-title::after {
                left: 50%;
                transform: translateX(-50%);
            }

            .menu-card {
                display: flex;
                flex-direction: column;
                align-items: center;
                text-align: center;
                padding: 1rem;
            }

            .menu-image {
                width: 100%;
                height: auto;
                max-height: 200px;
                margin-top: -1rem;
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

            .modal-content{
                width: 300px;
            }

            .modal-image {
                max-height: 150px;
            }

            .modal-details {
                padding-left: 0;
                padding: 2vw;
            }


            .quantity-btn {
            background: var(--primary-light);
            border: none;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            font-size: 1.4rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
        }

        .quantity-btn:hover {
            background: var(--primary);
            color: var(--white);
            box-shadow: 0 4px 12px rgba(44, 110, 138, 0.3);
        }
        }

        @media (min-width: 768px) {
            .modal-body {
                flex-direction: row;
            }

            .modal-image {
            }

            .modal-details {
                width: 100%;
                padding-left: 1.5rem;
                padding: 1vw;
            }
        }
    </style>
</head>
<body>
    <!-- Header Section -->
<header class="header">
    <img src="/public/images/LOGO.png" id="logo" alt="Captain's Brew Logo">
    <div id="hamburger-menu" class="hamburger">☰</div>
    <nav class="button-container" id="nav-menu">
        <div class="nav-links">
            <a href="/views/users/User-Home.php" class="nav-button">Home</a>
            <a href="/views/users/User-Menu.php" class="nav-button active">Menu</a>
            <a href="/views/users/User-Career.php" class="nav-button">Career</a>
            <a href="/views/users/User-Aboutus.php" class="nav-button">About Us</a>
        </div>
        <div class="icon-profile-container">
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
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="/views/users/account.php">My Account</a>
                        <a href="/views/users/purchases.php">My Purchase</a>
                        <a onclick="showLogoutOverlay()">Logout</a>
                    <?php else: ?>
                        <a onclick="window.location.href='/views/auth/login.php'">Login</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
</header>

    <!-- Main Content -->
    <div class="main-content">
        <?php include __DIR__ . '/partials/left-nav.php'; ?>

        <div id="menu-list-container">
            <!-- Menu Bar with Search -->
            <div class="menu-bar">
                 <!-- Category Title -->

           
                <div class="search-box">
                    <input type="text" class="search-input" placeholder="🔍 Search for drinks..." 
                           id="search-input" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                           onkeyup="handleSearch(event)"/>
                </div>
            </div>

           

            <!-- Menu Items -->
            <?php
$category = $_GET['category'] ?? 'coffee';
$searchTerm = $_GET['search'] ?? '';

try {
    $categoryName = str_replace(' ', ' ', $category);
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
                $isLoggedIn = isset($_SESSION['user_id']);
                $buttonAttributes = $isLoggedIn ? '' : 'disabled style="opacity: 0.5; cursor: not-allowed;" title="Please log in to add to cart"';

                echo "<div class='menu-card' id='menuCard-{$row['id']}'>
                        <img src='/public/{$image}' alt='$name' class='menu-image'>
                        <div class='menu-content'>
                            <h2 class='menu-title'>$name</h2>
                            <p class='menu-price'>₱ {$row['item_price']}</p>
                            <p class='menu-desc'>$desc</p>
                        </div>
                        <button class='menu-manage' $buttonAttributes>+</button>
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
                        <button id='addToCartModal' class='add-to-cart-btn'>Add to Cart</button>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        // Hamburger menu toggle
        document.getElementById('hamburger-menu').addEventListener('click', function() {
            document.getElementById('nav-menu').classList.toggle('active');
        });

        // Profile dropdown toggle for mobile
        const profile = document.querySelector('.profile');
        if (profile) {
            profile.addEventListener('click', function(e) {
                if (window.innerWidth <= 768) {
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
                if (data.error === 'User not logged in') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Login Required',
                        text: 'Please log in to add items to your cart.',
                        showConfirmButton: true,
                        confirmButtonText: 'Go to Login',
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = '/views/users/login.php';
                        }
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to add to cart: ' + data.error,
                    });
                }
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

<script src="/public/js/auth.js"></script>
</body>
</html>