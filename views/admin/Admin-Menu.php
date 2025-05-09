<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <meta http-equiv="X-UA-Compatible" content="ie=edge" />
  <title>Admin Dashboard - Captain's Brew Cafe</title>
  <link rel="icon" href="/images/logo.png" sizes="any" />
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Poppins', sans-serif;
      font-family: 'Poppins', sans-serif;
    }

    body {
      background: #fff;
      color: #4a3b2b;
    }

    /* Header */
    .header {
      display: flex;
      align-items: center;
      padding: 1rem 2rem;
      background: linear-gradient(135deg, #FFFAEE, #FFDBB5);
      box-shadow: 0 2px 5px rgba(74, 59, 43, 0.3);
      position: sticky;
      top: 0;
      z-index: 1000;
    }

    .logo-section img {
      width: 200px;
      margin: 0px 100px 0px 100px;
      transition: transform 0.3s;
    }

    .logo-section img:hover {
      transform: scale(1.1);
    }

    .nav-menu {
      display: flex;
      gap: 3rem;
    }

    .nav-button {
      background: none;
      border: none;
      color: #4a3b2b;
      font-size: 1rem;
      padding: 1rem 2rem;
      cursor: pointer;
      border-radius: 10px;
      transition: all 0.3s;
    }

    .nav-button:hover, .nav-button.active {
      background-color: #2C6E8A;
      color: #fff;
    }

    /* Menu Bar */
    .menu-bar {
      display: flex;
      align-items: center;
      background: #FFFAEE;
      box-shadow: 0 2px 5px rgba(74, 59, 43, 0.2);
      position: sticky;
      top: 110px;
      z-index: 999;
      border-bottom-left-radius: 10px;
      border-bottom-right-radius: 10px;
    }

    .menu-item {
      padding: 1rem 2rem;
      color: #4a3b2b;
      cursor: pointer;
      transition: all 0.3s ease;
      font-size: 0.95rem;
      font-weight: 500;
      margin: 0 0.5rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .menu-item:hover, .menu-item.active {
      background-color: #2C6E8A;
      color: #fff;
      border-color: #2C6E8A;
      box-shadow: 0 4px 8px rgba(44, 110, 138, 0.2);
    }

    .search-box {
      display: flex;
      align-items: center;
      background:#ffe9d2;
      border-radius: 20px;
      padding: 0.3rem 1rem;
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
      background: #fff;
      border-radius: 10px;
      padding: 2rem;
      position: sticky;
      top: 195px;
      height: fit-content;
      box-shadow: 0 5px 15px rgba(74, 59, 43, 0.5);
    }

    .edit-section {
      display: flex;
      flex-direction: column;
      gap: 1rem;
    }

    #view-mode {
      display: flex;
      flex-direction: column;
      gap: 1rem;
    }

    #view-image {
      width: 100%;
      height: 150px;
      object-fit: cover;
      border-radius: 10px;
    }

    #view-name {
      font-size: 1.5rem;
      color: #2C6E8A;
    }

    #view-price {
      font-size: 1.2rem;
      color: #4a3b2b;
    }

    #view-description {
      font-size: 0.9rem;
      color: #4a3b2b;
    }

    #view-mode button {
      background: #2C6E8A;
      color: #fff;
      border: none;
      padding: 0.5rem 1rem;
      border-radius: 5px;
      cursor: pointer;
      transition: background-color 0.3s;
    }

    #view-mode button:hover {
      background:rgb(2, 31, 45);
    }

    #view-mode button:first-of-type {
      position: absolute;
      top: 10px;
      right: 10px;
      border-radius: 50%;
      width: 30px;
      height: 30px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .add-button {
      background: #2C6E8A;
      color: #fff;
      border: none;
      padding: 0.75rem;
      border-radius: 5px;
      font-size: 1rem;
      cursor: pointer;
      transition: background-color 0.3s;
    }

    .add-button:hover {
      background: #235A73;
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
      overflow-y: auto;
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
      width: 150px;
      height: auto;
      border-radius: 10px;
      margin: 0 auto 1rem;
      display: block;
    }

    /* Modal */
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background: rgba(44, 110, 138, 0.7);
    }

    .modal-content {
      background: white;
      margin: 5% auto;
      padding: 2rem;
      border-radius: 10px;
      width: 450px;
      box-shadow: 0 5px 15px rgba(74, 59, 43, 0.5);
      position: relative;
      color: #4a3b2b;
    }

    .close-btn {
      position: absolute;
      top: 10px;
      right: 20px;
      color: #2C6E8A;
      font-size: 1.5rem;
      cursor: pointer;
      transition: color 0.3s;
    }

    .close-btn:hover {
      color:rgb(1, 24, 35);
      
    }

    .modal-content h2 {
      font-size: 1.5rem;
      color: #2C6E8A;
      margin-bottom: 1rem;
    }

    .modal-content label {
      display: block;
      font-size: 0.9rem;
      margin-top: 0.5rem;
      color: #4a3b2b;
    }

    .modal-content input,
    .modal-content textarea,
    .modal-content select {
      width: 100%;
      padding: 0.5rem;
      margin-top: 0.3rem;
      border-color: #A9D6E5;
      border-radius: 5px;
      background: white;
      color: #4a3b2b;
      font-size: 0.9rem;
    }

    .modal-content textarea {
      min-height: 100px;
      resize: vertical;
    }


    .footer-bottom {
    background-color: #FFFAEE;
    display: flex;
    flex-direction: column;
    text-align: center;
    padding: 10px;
}





/* Mobile Responsiveness */
@media (max-width: 768px) {
  .header {
    flex-direction: column;
    padding: 2vw;
    text-align: center;
  }

  .logo-section img {
    width: 40vw;
    margin: 0 0 2vw 0;
  }

  .nav-menu {
    flex-direction: column;
    gap: 1vw;
    width: 100%;
  }

  .nav-button {
    padding: 1vw;
    width: 100%;
    font-size: 3vw;
  }

  .menu-bar {
    flex-wrap: wrap;
    justify-content: center;
    padding: 1vw;
    top: 25vw;
  }

  .menu-item {
    padding: 1vw;
    font-size: 2.5vw;
    margin: 0.5vw;
  }

  .search-box {
    margin-top: 1vw;
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
    width: 90%;
    margin: 15% auto;
    padding: 3vw;
  }
  
  /* Adjust font sizes for mobile */
  .menu-title {
    font-size: 3.5vw;
  }
  
  .menu-price {
    font-size: 3vw;
  }
  
  .menu-desc {
    font-size: 2.5vw;
  }
  
  #view-name {
    font-size: 4vw;
  }
  
  #view-price {
    font-size: 3.5vw;
  }
  
  #view-description {
    font-size: 3vw;
  }
  
  .add-button {
    font-size: 3vw;
  }
}
  </style>
</head>
<body>
  <header class="header">
    <div class="logo-section">
      <img src="/public/images/logo.png" id="logo" alt="Captain's Brew Cafe Logo" />
    </div>
    <nav class="nav-menu" id="nav-menu">
      <button class="nav-button active" onclick="gotoMenu()">Menu</button>
      <button class="nav-button" onclick="gotoOrders()">Orders</button>
      <button class="nav-button" onclick="gotoReports()">Reports</button>
      <button class="nav-button" onclick="gotoAccounts()">Accounts</button>
      <button class="nav-button" onclick="showLogoutOverlay()">Logout</button>
    </nav>
  </header>

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
    <img src="public/images/icons/coffee.png" alt="">
  </div>

<main class="main-content">
  <div class="products-container"> 
    <section id="menu-list">
      <?php include 'partials/menu-items.php'; ?>
    </section>
  </div>

  <div class="edit-container">
    <div class="edit-section" id="edit-section">
      <h1></h1>
      <button class="add-button" id="add-button" onclick="openAddItemModal()">Add Item</button>
      
      <div id="no-item-selected" style="margin-top: 20px; text-align: center;">
        <p>Select an item to edit or add a new one</p>
      </div>
      
      <!-- This will be shown when an item is selected -->
      <div id="edit-form-container" style="display: none;">
        <div id="view-mode">
          <button onclick="closeModal()" class="close-btn">X</button>
        
          <img id="view-image" src="" alt="Item Image">
          <input type="hidden" name="existing_image" id="edit-existing-image">
          <h3 id="view-name"></h3>
          <p id="view-price"></p>
          <p id="view-description"></p>
          <button onclick="enableEditMode()">Edit</button>
          <button onclick="deleteItem()">Delete</button>
        </div>
      </div>
    </div>
  </div>
</main>

  <div id="overlay"></div>

  <div class="form-container">
    <form class="edit-form" action="/controllers/update-item.php" method="POST" id="edit-item-form" enctype="multipart/form-data">
      <input type="hidden" name="id" id="edit-id">
      <img id="edit-image-preview" src="" alt="Current Image">
      <label>Change Image:</label>
      <input type="file" name="item_image" accept="image/*">
      <label>Name:</label>
      <input type="text" name="item_name" id="edit-name">
      <label>Price:</label>
      <input type="number" name="item_price" id="edit-price">
      <label>Description:</label>
      <textarea name="item_description" id="edit-description"></textarea>
      <input type="hidden" name="category" id="edit-category" value="">
      <button type="submit">Update Item</button>
      <button type="button" onclick="cancelEditMode()">Cancel</button>
    </form>
  </div>

  <div id="addItemModal" class="modal">
    <div class="modal-content">
      <span class="close-btn" onclick="closeAddItemModal()">×</span>
      <h2>Add New Item</h2>
      <form action="/controllers/add-item.php" method="POST" enctype="multipart/form-data">
        <label for="item-name">Item Name</label>
        <input type="text" id="item-name" name="item_name" required />

        <label for="item-price">Price</label>
        <input type="number" id="item-price" name="item_price" step="0.01" required />
        
        <label for="item-description">Description</label>
        <textarea id="item-description" name="item_description" required></textarea>
        
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

  <footer>
    <?php include __DIR__ . '/partials/footer.php'; ?>
  </footer>

  <script>
    function loadCategory(category) {
      const url = new URL(window.location.href);
      url.pathname = '/views/admin/admin-menu.php';
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