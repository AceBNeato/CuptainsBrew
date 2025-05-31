<?php
// Include the database configuration
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
requireAdmin();

// Ensure session is started
if (!isset($_SESSION)) {
    session_start();
}

// Verify admin is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['loggedin'])) {
    header('Location: /views/auth/login.php');
    exit();
}

// Fetch categories from the database
$categories_query = "SELECT id, name FROM categories";
$categories_result = $conn->query($categories_query);
$categories = [];
while ($row = $categories_result->fetch_assoc()) {
    $categories[$row['id']] = $row['name'];
}

// Define drinks categories (based on config)
$drinks = [
    1 => 'Coffee',
    2 => 'Non-Coffee',
    3 => 'Frappe',
    4 => 'Milktea'
];

// Set default category
$defaultCategoryId = 1; // Default to 'Coffee'
$currentCategoryId = $_GET['category_id'] ?? $defaultCategoryId;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title>Admin Dashboard - Captain's Brew Cafe</title>
    <link rel="icon" href="/public/images/LOGO.png" sizes="any" />
    <!-- SweetAlert2 CSS CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- SweetAlert2 JS CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: #fff;
            color: #4a3b2b;
        }

        /* Menu Bar */
        .menu-bar {
            display: flex;
            align-items: center;
            background: #FFFAEE;
            box-shadow: 0 2px 5px rgba(74, 59, 43, 0.2);
            position: sticky;
            top: 0;
            z-index: 999;
            border-bottom-left-radius: 10px;
            border-bottom-right-radius: 10px;
            padding: 0 1rem;
        }

        .menu-tabs {
            display: flex;
            gap: 1rem;
        }

        .tab {
            padding: 1rem 2rem;
            color: #4a3b2b;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            text-transform: uppercase;
            transition: all 0.3s ease;
        }

        .tab:hover, .tab.active {
            background-color: #2C6E8A;
            color: #fff;
            border-radius: 10px 10px 0 0;
        }

        .category-section {
            display: none;
            flex-wrap: wrap;
            gap: 0.5rem;
            padding: 1rem;
        }

        .category-section.active {
            display: flex;
        }

        .menu-item {
            padding: 0.5rem 1rem;
            color: #4a3b2b;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
            font-weight: 500;
            text-transform: uppercase;
            border-radius: 5px;
        }

        .menu-item:hover, .menu-item.active {
            background-color: #2C6E8A;
            color: #fff;
            box-shadow: 0 4px 8px rgba(44, 110, 138, 0.2);
        }

        .search-box {
            display: flex;
            align-items: center;
            background: #ffe9d2;
            border-radius: 20px;
            padding: 0.3rem 1rem;
            margin-left: auto;
        }

        .search-input {
            background: none;
            border: none;
            color: #4a3b2b;
            font-size: 0.9rem;
            outline: none;
            width: 200px;
        }

        .search-input::placeholder {
            color: #4a3b2b;
            opacity: 0.7;
        }

        /* Main Content */
        .main-content {
            display: flex;
            gap: 2rem;
            padding: 2rem;
            min-height: calc(100vh - 140px);
        }

        .products-container {
            flex: 2;
        }

        .menu-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #fff;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 5px rgba(74, 59, 43, 0.2);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(44, 110, 138, 0.3);
        }

        .menu-content {
            flex: 1;
        }

        .menu-title {
            font-size: 1.2rem;
            color: #2C6E8A;
        }

        .menu-price {
            font-size: 1rem;
            color: #4a3b2b;
        }

        .menu-desc {
            font-size: 0.9rem;
            color: #4a3b2b;
        }

        .menu-manage {
            background-color: #2C6E8A;
            color: #fff;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .menu-manage:hover {
            background-color: #235A73;
        }

        /* Edit Container */
        .edit-container {
            flex: 1;
            background: #FFFFFF;
            border-radius: 12px;
            position: sticky;
            top: 110px;
            height: fit-content;
            box-shadow: 0 5px 15px rgba(74, 59, 43, 0.1);
            overflow: hidden;
        }

        .edit-section {
            display: flex;
            flex-direction: column;
        }

        #view-mode {
            display: none;
            flex-direction: column;
            background: #FFFFFF;
            border-radius: 12px;
            overflow: hidden;
        }

        #view-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-bottom: 2px solid #A9D6E5;
        }

        .view-content {
            padding: 1.5rem;
        }

        #view-name {
            font-size: 1.5rem;
            color: #2C6E8A;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        #view-price {
            font-size: 1.25rem;
            color: #4a3b2b;
            margin-bottom: 1rem;
            font-weight: 500;
        }

        #view-variations {
            display: none;
            margin: 10px 0;
        }

        #view-variations h4 {
            color: #2C6E8A;
            margin-bottom: 5px;
        }

        .variation-badges {
            display: flex;
            gap: 10px;
        }

        .variation-badge {
            background: #ffcccb;
            color: #e74c3c;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.9rem;
        }

        #view-description {
            font-size: 0.875rem;
            color: #666;
            line-height: 1.5;
            margin-bottom: 1.5rem;
        }

        .view-buttons {
            display: flex;
            gap: 0.75rem;
            padding: 0 1.5rem 1.5rem;
        }

        .view-btn {
            flex: 1;
            padding: 0.75rem;
            border: none;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .view-btn-edit {
            background: #2C6E8A;
            color: #FFFFFF;
        }

        .view-btn-edit:hover {
            background: #235A73;
        }

        .view-btn-delete {
            background: #EF4444;
            color: #FFFFFF;
        }

        .view-btn-delete:hover {
            background: #DC2626;
        }

        .add-button {
            background: #2C6E8A;
            color: #FFFFFF;
            border: none;
            padding: 0.875rem;
            margin: 1.5rem;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .add-button:hover {
            background: #235A73;
            transform: translateY(-2px);
        }

        .add-button i {
            font-size: 1.25rem;
        }

        #no-item-selected {
            padding: 3rem 1.5rem;
            text-align: center;
            color: #666;
        }

        #no-item-selected p {
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }

        /* Overlay and Form */
        #overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(44, 110, 138, 0.7);
            z-index: 1000;
        }

        .form-container {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #fff;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(74, 59, 43, 0.5);
            z-index: 1001;
            width: 90%;
            max-width: 450px;
            max-height: 80vh;
        }

        .edit-form {
            display: flex;
            flex-direction: column;
            gap: .5rem;
        }

        .edit-form label {
            font-size: 0.9rem;
            color: #4a3b2b;
        }

        .edit-form input[type="text"],
        .edit-form input[type="number"],
        .edit-form textarea,
        .edit-form input[type="file"] {
            padding: 0.5rem;
            border: none;
            border-radius: 5px;
            background: #A9D6E5;
            color: #4a3b2b;
            font-size: 0.9rem;
            width: 100%;
        }

        .edit-form textarea {
            min-height: 100px;
            resize: vertical;
        }

        .edit-form button {
            padding: 0.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background-color 0.3s;
        }

        .edit-form button[type="submit"] {
            background: #2C6E8A;
            color: #fff;
        }

        .edit-form button[type="submit"]:hover {
            background: #235A73;
        }

        .edit-form button[type="button"] {
            background: #4a3b2b;
            color: #fff;
        }

        .edit-form button[type="button"]:hover {
            background: #3a2b1b;
        }

        #edit-image-preview {
            height: auto;
            border-radius: 10px;
            margin: 0 auto 1rem;
            display: block;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            align-items: center;
            justify-content: center;
            overflow-y: auto;
            padding: 20px;
        }

        .modal.active {
            display: flex;
        }

        /* Add Item Modal Specific */
        #addItemModal {
            z-index: 9998;
        }

        #addItemModal .modal-content {
            background: #FFFFFF;
            border: 2px solid #A9D6E5;
        }

        #addItemModal .modal-form {
            gap: 1rem;
        }

        /* Edit Item Modal Specific */
        #editItemModal {
            z-index: 9999;
        }

        #editItemModal .modal-content {
            background: #FFFFFF;
            border: 2px solid #2C6E8A;
        }

        #editItemModal .modal-form {
            gap: 0.75rem;
        }

        .modal-content {
            background: #FFFFFF;
            padding: 1.5rem;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            position: relative;
            margin: auto;
            max-height: calc(100vh - 40px);
            overflow-y: auto;
        }

        .modal-content h2 {
            color: #2C6E8A;
            font-size: 1.25rem;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #A9D6E5;
        }

        .modal-form {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .form-group {
            margin-bottom: 0.75rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.25rem;
            color: #4a3b2b;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.625rem;
            border: 1px solid #A9D6E5;
            border-radius: 6px;
            font-size: 0.875rem;
            background: #FFFFFF;
            color: #4a3b2b;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            border-color: #2C6E8A;
            outline: none;
            box-shadow: 0 0 0 2px rgba(44, 110, 138, 0.1);
        }

        .form-group textarea {
            min-height: 80px;
            max-height: 120px;
            resize: vertical;
        }

        .image-preview-container {
            width: 100%;
            height: 120px;
            border-radius: 6px;
            border: 2px dashed #A9D6E5;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #FFFFFF;
            margin-bottom: 0.75rem;
            position: relative;
        }

        .image-preview {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .image-preview-placeholder {
            text-align: center;
            color: #2C6E8A;
            font-size: 0.875rem;
            padding: 1rem;
        }

        .image-preview-placeholder i {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: #A9D6E5;
            display: block;
        }

        .file-input-label {
            display: inline-block;
            padding: 0.625rem 1rem;
            background: #A9D6E5;
            color: #2C6E8A;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.875rem;
            text-align: center;
            width: 100%;
            transition: all 0.3s ease;
            margin-bottom: 0.75rem;
        }

        .file-input-label:hover {
            background: #2C6E8A;
            color: #FFFFFF;
        }

        .file-input {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            border: 0;
        }

        .modal-buttons {
            display: flex;
            gap: 0.75rem;
            margin-top: 1.5rem;
        }

        .modal-btn {
            flex: 1;
            padding: 0.625rem;
            border: none;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .modal-btn-primary {
            background: #2C6E8A;
            color: #FFFFFF;
        }

        .modal-btn-primary:hover {
            background: #235A73;
        }

        .modal-btn-secondary {
            background: #A9D6E5;
            color: #2C6E8A;
        }

        .modal-btn-secondary:hover {
            background: #8CC5D8;
        }

        .modal-btn-danger {
            background: #EF4444;
            color: #FFFFFF;
        }

        .modal-btn-danger:hover {
            background: #DC2626;
        }

        .close-btn {
            position: absolute;
            top: 0.75rem;
            right: 0.75rem;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #A9D6E5;
            color: #2C6E8A;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            cursor: pointer;
            border: none;
            transition: all 0.3s ease;
            padding: 0;
            line-height: 1;
        }

        .close-btn:hover {
            background: #2C6E8A;
            color: #FFFFFF;
            transform: rotate(90deg);
        }

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .menu-bar {
                flex-direction: column;
                align-items: stretch;
                padding: 1vw;
            }

            .menu-tabs {
                justify-content: center;
            }

            .tab {
                font-size: 2.5vw;
                padding: 1vw 2vw;
            }

            .category-section {
                justify-content: center;
            }

            .menu-item {
                padding: 0.5vw 1vw;
                font-size: 2vw;
            }

            .search-box {
                margin: 1vw 0;
                width: 100%;
                justify-content: center;
            }

            .search-input {
                width: 100%;
                font-size: 2.5vw;
            }

            .main-content {
                flex-direction: column;
                padding: 2vw;
            }

            .edit-container {
                position: static;
                margin-top: 2vw;
            }

            .form-container {
                width: 95%;
                padding: 2vw;
            }

            .modal-content {
                margin: 5% auto;
                padding: 1rem;
                width: 95%;
            }

            .modal-content h2 {
                font-size: 1.3rem;
            }

            .form-group label {
                font-size: 0.85rem;
            }

            .form-group input,
            .form-group textarea,
            .form-group select,
            .modal-btn {
                font-size: 0.85rem;
            }

            .image-preview-container {
                height: 120px;
            }
        }

        .variation-group {
            margin-bottom: 0.75rem;
            padding: 0.5rem;
            border-radius: 6px;
            background: #f8f9fa;
        }

        .variation-price {
            margin-top: 0.5rem;
            padding: 0.5rem;
            border-radius: 6px;
            background: #f0f8ff;
        }

        .variations-container {
            border: 1px solid #A9D6E5;
            border-radius: 6px;
            padding: 0.75rem;
            background: #f8f9fa;
        }

        #variation-prices, #add-variation-prices {
            margin-top: 0.75rem;
            padding-top: 0.75rem;
            border-top: 1px dashed #A9D6E5;
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/partials/header.php'; ?>

    <div class="menu-bar">
        <div class="menu-tabs">
            <div class="tab active">Drinks</div>
        </div>

        <div id="drinks-categories" class="category-section active">
            <?php foreach ($drinks as $id => $name): ?>
                <div class="menu-item <?= $currentCategoryId == $id ? 'active' : '' ?>" 
                     onclick="loadCategory(<?= $id ?>)">
                    <?= htmlspecialchars($name) ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="search-box">
            <input type="text" class="search-input" placeholder="🔍 Search item" 
                   id="search-input" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                   onkeyup="handleSearch(event)"/>
        </div>
    </div>

    <main class="main-content">
        <div class="products-container"> 
            <section id="menu-list">
                <?php
                $_GET['category_id'] = $currentCategoryId;
                include 'partials/menu-items.php';
                ?>
            </section>
        </div>

        <div class="edit-container">
            <div class="edit-section" id="edit-section">
                <h1></h1>
                <button class="add-button" id="add-button" onclick="openAddItemModal()">Add Item</button>
                
                <div id="no-item-selected" style="margin: 50px; text-align: center;">
                    <p>Select an item to edit or add a new one</p>
                </div>
                
                <div id="edit-form-container" style="display: none;">
                    <div id="view-mode">
                        <button onclick="closeModal()" class="close-btn">×</button>
                        <img id="view-image" src="" alt="Item Image">
                        <div class="view-content">
                        <h3 id="view-name"></h3>
                        <p id="view-price"></p>
                        <div id="view-variations" style="display: none; margin: 10px 0;">
                            <h4 style="color: #2C6E8A; margin-bottom: 5px;">Variations:</h4>
                            <div class="variation-badges" style="display: flex; gap: 10px;">
                                <span id="hot-badge" class="variation-badge" style="background: #ffcccb; color: #e74c3c; padding: 5px 10px; border-radius: 5px; font-size: 0.9rem;">
                                    Hot: ₱<span id="hot-price-display"></span>
                                </span>
                                <span id="iced-badge" class="variation-badge" style="background: #cce5ff; color: #0056b3; padding: 5px 10px; border-radius: 5px; font-size: 0.9rem;">
                                    Iced: ₱<span id="iced-price-display"></span>
                                </span>
                            </div>
                        </div>
                        <p id="view-description"></p>
                        </div>
                        <div class="view-buttons">
                            <button onclick="enableEditMode()" class="view-btn view-btn-edit">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button onclick="deleteItem()" class="view-btn view-btn-delete">
                                <i class="fas fa-trash-alt"></i> Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <div id="overlay"></div>

    <div id="editItemModal" class="modal">
        <div class="modal-content">
            <button class="close-btn" onclick="cancelEditMode()">×</button>
            <h2>Edit Item</h2>
            <form class="modal-form" id="edit-item-form" action="/controllers/update-item.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="id" id="edit-id">
                
                <div class="image-preview-container">
                    <img id="edit-image-preview" class="image-preview" src="" alt="Current Image">
                </div>

                <div class="file-input-container">
                    <label class="file-input-label" for="edit-item-image">
                        <i class="fas fa-upload"></i> Change Image
                    </label>
                    <input type="file" id="edit-item-image" name="item_image" class="file-input" accept="image/*" onchange="previewEditImage(this)">
                </div>

                <div class="form-group">
                    <label for="edit-name">Item Name</label>
                    <input type="text" name="item_name" id="edit-name" required>
                </div>

                <div class="form-group">
                    <label for="edit-price">Base Price (₱)</label>
                    <input type="number" name="item_price" id="edit-price" step="0.01" min="0" required>
                </div>

                <div class="form-group">
                   
                    <div class="variations-container">
                        <div class="variation-group" style="display: flex; align-items: center;">
                            
                        <label for="has-variations">Enable Hot/Iced variations</label>
                        <input type="checkbox"  id="has-variations" name="has_variations">
                        
                        </div>
                        <div id="variation-prices" style="display: none;">
                            <div class="variation-price">
                                <label for="hot-price">Hot Price (₱)</label>
                                <input type="number" name="hot_price" id="hot-price" step="0.01" min="0">
                            </div>
                            <div class="variation-price">
                                <label for="iced-price">Iced Price (₱)</label>
                                <input type="number" name="iced_price" id="iced-price" step="0.01" min="0">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="edit-description">Description</label>
                    <textarea name="item_description" id="edit-description" required></textarea>
                </div>

            <input type="hidden" name="category_id" id="edit-category" value="">

                <div class="modal-buttons">
                    <button type="button" class="modal-btn modal-btn-secondary" onclick="cancelEditMode()">Cancel</button>
                    <button type="submit" class="modal-btn modal-btn-primary">Update Item</button>
                    
                </div>
        </form>
        </div>
    </div>

    <div id="addItemModal" class="modal">
        <div class="modal-content">
            <button class="close-btn" onclick="closeAddItemModal()">×</button>
            <h2>Add New Item</h2>
            <form id="add-item-form" class="modal-form" method="POST" action="/controllers/add-item.php" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="image-preview-container">
                    <img id="add-image-preview" class="image-preview" src="" style="display: none;">
                    <div id="add-image-placeholder" class="image-preview-placeholder">
                        <i class="fas fa-image"></i>
                        <p>Click below to choose an image</p>
                    </div>
                </div>

                <div class="file-input-container">
                    <label class="file-input-label" for="add-item-image">
                        <i class="fas fa-upload"></i> Choose Image
                    </label>
                    <input type="file" id="add-item-image" name="item_image" class="file-input" accept="image/*" required onchange="previewAddImage(this)">
                </div>

                <div class="form-group">
                    <label for="add-name">Item Name</label>
                    <input type="text" id="add-name" name="item_name" required placeholder="Enter item name">
                </div>

                <div class="form-group">
                    <label for="add-price">Price (₱)</label>
                    <input type="number" id="add-price" name="item_price" step="0.01" min="0" required placeholder="0.00">
                </div>
                
                <div class="form-group">
                    <div class="variations-container">
                        <div class="variation-group" style="display: flex; align-items: center;">
                            <label for="add-has-variations">Enable Hot/Iced variations</label>
                            <input type="checkbox" id="add-has-variations" name="has_variations">
                        </div>
                        <div id="add-variation-prices" style="display: none;">
                            <div class="variation-price">
                                <label for="add-hot-price">Hot Price (₱)</label>
                                <input type="number" name="hot_price" id="add-hot-price" step="0.01" min="">
                            </div>
                            <div class="variation-price">
                                <label for="add-iced-price">Iced Price (₱)</label>
                                <input type="number" name="iced_price" id="add-iced-price" step="0.01" min="">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="add-description">Description</label>
                    <textarea id="add-description" name="item_description" required placeholder="Enter item description"></textarea>
                </div>

                <div class="form-group">
                    <label for="add-category">Category</label>
                    <select id="add-category" name="category_id" required>
                    <?php foreach ($drinks as $id => $name): ?>
                        <option value="<?= $id ?>"><?= htmlspecialchars($name) ?></option>
                    <?php endforeach; ?>
                </select>
                </div>

                <div class="modal-buttons">
                    <button type="button" class="modal-btn modal-btn-secondary" onclick="closeAddItemModal()">Cancel</button>
                    <button type="submit" class="modal-btn modal-btn-primary">Add Item</button>
                </div>
            </form>
        </div>
    </div>

    <footer>
        <?php include __DIR__ . '/partials/footer.php'; ?>
    </footer>

    <script>
        function loadCategory(categoryId) {
            const url = new URL(window.location.href);
            url.pathname = '/views/admin/Admin-Menu.php';
            url.searchParams.set('category_id', categoryId);
            window.location.href = url.toString();
        }

        function handleSearch(event) {
            if (event.key === 'Enter') {
                const searchTerm = event.target.value.trim();
                const url = new URL(window.location.href);
                if (searchTerm) {
                    url.searchParams.set('search', searchTerm);
                } else {
                    url.searchParams.delete('search');
                }
                window.location.href = url.toString();
            }
        }

        // SweetAlert2 for success/error messages
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const successMessage = urlParams.get('success');
            const errorMessage = urlParams.get('error');

            if (successMessage) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: successMessage,
                    confirmButtonColor: '#2C6E8A',
                    confirmButtonText: 'OK'
                });
            } else if (errorMessage) {
                Swal.fire({
                    icon: 'error', 
                    title: 'Error!',
                    text: errorMessage,
                    confirmButtonColor: '#2C6E8A',
                    confirmButtonText: 'OK'
                });
            }
        });

        // Image preview for new items
        function previewAddImage(input) {
            const preview = document.getElementById('add-image-preview');
            const placeholder = document.getElementById('add-image-placeholder');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                    placeholder.style.display = 'none';
                }
                
                preview.onerror = function() {
                    this.src = '/public/images/placeholder.jpg'; // Default placeholder
                    console.warn('Failed to load image preview, using placeholder');
                };

                reader.readAsDataURL(input.files[0]);
            }
        }

        // Image preview for edit form
        function previewEditImage(input) {
            const preview = document.getElementById('edit-image-preview');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                }
                
                preview.onerror = function() {
                    this.src = '/public/images/placeholder.jpg'; // Default placeholder
                    console.warn('Failed to load image preview, using placeholder');
                };
                
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Delete item confirmation
        function deleteItem() {
            Swal.fire({
                title: 'Are you sure?',
                text: "This action cannot be undone!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#4A3B2B',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    const itemId = document.getElementById('edit-id').value;
                    window.location.href = `/controllers/delete-item.php?id=${itemId}`;
                }
            });
        }

        // Handle variations checkbox for add item form
        document.addEventListener('DOMContentLoaded', function() {
            const addHasVariations = document.getElementById('add-has-variations');
            if (addHasVariations) {
                addHasVariations.addEventListener('change', function(e) {
                    const variationPrices = document.getElementById('add-variation-prices');
                    variationPrices.style.display = e.target.checked ? 'block' : 'none';
                    
                    // Set required attribute based on checkbox state
                    const hotPrice = document.getElementById('add-hot-price');
                    const icedPrice = document.getElementById('add-iced-price');
                    hotPrice.required = e.target.checked;
                    icedPrice.required = e.target.checked;
                });
            }
        });
    </script>

    <script src="/public/js/admin-menu.js"></script>
    <script src="/public/js/auth.js"></script>
</body>
</html>