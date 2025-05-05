<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <meta http-equiv="X-UA-Compatible" content="ie=edge" />
  <title>Admin Menu - Cuptain's Brew</title>
  <link rel="icon" href="/images/LOGO.png" sizes="any" />
  <link rel="stylesheet" href="/public/css/admin-menu.css" />
</head>
<body>

  <!-- Header -->
  <header class="header">
    <div class="logo-section">
      <img src="/public/images/LOGO.png" id="logo" alt="cuptainsbrewlogo" />
    </div>

    <nav class="button-container" id="nav-menu">
      <button class="nav-button active" onclick="gotoMenu()">Menu</button>
      <button class="nav-button" onclick="gotoOrders()">Orders</button>
      <button class="nav-button" onclick="gotoReports()">Reports</button>
      <button class="nav-button" onclick="gotoAccounts()">Accounts</button>
      <a id="logout-button" class="nav-button" href="/logout.php">Logout</a>
    </nav>
  </header>

<!-- Category Bar -->
<div class="menu-bar">
    <?php
    $currentCategory = $_GET['category'] ?? 'coffee';
    $categories = [
        'coffee' => 'Coffee',
        'non-coffee' => 'Non-Coffee',
        'frappe' => 'Frappe',
        'milktea' => 'MilkTea',
        'soda' => 'Soda'
    ];
    foreach ($categories as $key => $name): ?>
        <div class="menu-item <?= $currentCategory === $key ? 'active' : '' ?>" 
             onclick="loadCategory('<?= $key ?>')">
            <?= htmlspecialchars($name) ?>
        </div>
    <?php endforeach; ?>

    <div class="search-box">
        <input type="text" class="search-input" placeholder="🔍 Search item" 
               id="search-input" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
               onkeyup="handleSearch(event)"/>
    </div>
</div>



  <!-- Main Content -->
  <main class="main-content">
    <div class="products-container"> 
        <section id="menu-list">
          <?php
          $menuItemsPath = __DIR__ . '/partials/menu-items.php';
          if (file_exists($menuItemsPath)) {
              include $menuItemsPath;
          } else {
              echo '<div class="error">Menu component not available</div>';
              error_log("Missing menu items file at: " . $menuItemsPath);
          }
          ?>
        </section>
  </div>

    <div class="edit-container">
    <div class="edit-section" id="edit-section">
        <p class="edit-placeholder" id="edit-placeholder"></p>

        <div id="edit-form-container" style="display: none;">
           
            <div id="view-mode">
                <img id="view-image" src="" alt="Item Image" style="width: 150px; height: auto;">
                <input type="hidden" name="existing_image" id="edit-existing-image">
                <h3 id="view-name"></h3>
                <p id="view-price"></p>
                <p id="view-description"></p>

                <button onclick="closeModal()">X</button>
                <button onclick="enableEditMode()">Edit</button>
                <button onclick="deleteItem()">Delete</button>
            </div>

            <form class="edit-form" action="/controllers/update-item.php" method="POST" id="edit-item-form" enctype="multipart/form-data">
                <input type="hidden" name="id" id="edit-id">

                <img id="edit-image-preview" src="" alt="Current Image" style="width: 150px; height: auto; margin-bottom: 1vw;"><br>

                <label>Change Image:</label>
                <input type="file" name="item_image" accept="image/*">
                
                <label>Name:</label>
                <input type="text" name="item_name" id="edit-name"><br>

                <label>Price:</label>
                <input type="number" name="item_price" id="edit-price"><br>

                <label>Description:</label>
                <textarea name="item_description" id="edit-description"></textarea>

                <input type="hidden" name="category" id="edit-category" value=""> 
                
                <button type="submit">Update Item</button>
                <button type="button" onclick="cancelEditMode()">Cancel</button>
            </form>

        </div>

        
        <button class="add-button" id="add-button" onclick="openAddItemModal()">Add Item</button>
    </div>
</div>



<!-- Add Item Modal -->
<div id="addItemModal" class="modal">
  <div class="modal-content">
    <span class="close-btn" onclick="closeAddItemModal()">&times;</span>
    <h2>Add New Item</h2>
    <form action="/controllers/add-item.php" method="POST" enctype="multipart/form-data">
      <label for="item-name">Item Name</label>
      <input type="text" id="item-name" name="item_name" required />

      <label for="item-description">Description</label>
      <textarea id="item-description" name="item_description" required></textarea>

      <label for="item-price">Price</label>
      <input type="number" id="item-price" name="item_price" step="0.01" required />

        <label for="item-category">Category</label>
        <select id="item-category" name="item_category" required>
          <option value="coffee">Coffee</option>
          <option value="non_coffee">Non-Coffee</option>
          <option value="frappe">Frappe</option>
          <option value="milktea">MilkTea</option>
          <option value="soda">Soda</option>
        </select>

      <label for="item-image">Image</label>
      <input type="file" id="item-image" name="item_image" accept="image/*" />

      <button class="add-button" type="submit">Add Item</button>
    </form>
    
  </div>
</div>

  

 <script>

    function loadCategory(category) {
    // Fix the URL path - ensure it's correct
    const url = new URL(window.location.href);
    url.pathname = '/views/admin/admin-menu.php'; // Set correct base path
    url.searchParams.set('category', category);
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
  </script>
    

  <script src="/public/js/admin-menu.js"></script>
  <script src="/public/js/auth.js"></script>
</body>
</html>



