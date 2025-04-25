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
      <a href="/views/admin/admin-menu.php" class="nav-button active">Menu</a>
      <a href="/views/admin/admin-orders.php" class="nav-button">Orders</a>
      <a href="/views/admin/admin-reports.php" class="nav-button">Reports</a>
      <a href="/views/admin/admin-accounts.php" class="nav-button">Accounts</a>
      <a id="logout-button" class="nav-button" href="/logout.php">Logout</a>
    </nav>

    
  </header>

  <!-- Category Bar -->
  <div class="menu-bar">
    <div class="menu-item active" data-page="/views/menu-items/coffee.php">Coffee</div>
    <div class="menu-item" data-page="/views/menu-items/non-coffee.php">Non-Coffee</div>
    <div class="menu-item" data-page="/views/menu-items/frappe.php">Frappe</div>
    <div class="menu-item" data-page="/views/menu-items/milktea.php">MilkTea</div>
    <div class="menu-item" data-page="/views/menu-items/soda.php">Soda</div>
    

    <div class="search-box">
      <input type="text" class="search-input" placeholder="🔍 Search item" />
    </div>
  </div>

  <!-- Main Content -->
  <main class="main-content">
    <div class="products-container"> 
      <section id="menu-list">
      <?php
      ?>
    </section></div>

    <div class="edit-container">
    <!-- Edit Section Modal -->
    <div class="edit-section" id="edit-section">
        <p class="edit-placeholder" id="edit-placeholder"></p>

        <!--hidden by default-->
        <div id="edit-form-container" style="display: none;">
            <!-- View-Only Mode -->
            <div id="view-mode">
                <img id="view-image" src="" alt="Item Image" style="width: 150px; height: auto;">
                <h3 id="view-name"></h3>
                <p id="view-price"></p>
                <p id="view-description"></p>
                <input type="hidden" name="existing_image" id="edit-existing-image">

                <button onclick="closeModal()">X</button>
                <button onclick="enableEditMode()">Edit</button>
                <button onclick="deleteItem()">Delete</button>
            </div>

            <!-- Editable Form -->
            <form action="/controllers/update-item.php" method="POST" id="edit-item-form" enctype="multipart/form-data" style="display: none;">
                <input type="hidden" name="id" id="edit-id">

                <label>Name:</label>
                <input type="text" name="item_name" id="edit-name"><br>

                <label>Price:</label>
                <input type="number" name="item_price" id="edit-price"><br>

                <label>Description:</label>
                <textarea name="item_description" id="edit-description"></textarea><br>

                <label>Current Image:</label><br>
                <img id="edit-image-preview" src="" alt="Current Image" style="width: 150px; height: auto; margin-bottom: 1vw;"><br>

                <label>Change Image:</label>
                <input type="file" name="item_image" accept="image/*"><br><br>
                
                <input type="hidden" name="category" id="edit-category" value=""> <!-- Will be set dynamically -->
 
                <button type="submit">Update Item</button>
                <button type="button" onclick="cancelEditMode()">Cancel</button>
            </form>

        </div>
        <!-- Button to open the modal for adding an item -->
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
      <input type="file" id="item-image" name="item_image" accept="image/*" required />

      <button class="add-button" type="submit">Add Item</button>
    </form>
    
  </div>
</div>

  

    

  <script src="/public/js/admin-menu.js"></script>
  <script src="/public/js/auth.js"></script>
</body>
</html>



