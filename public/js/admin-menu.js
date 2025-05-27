// Constants
const ERROR_REDIRECT_DELAY = 3000;

// DOM Elements
const elements = {
  menuItems: document.querySelectorAll(".menu-item"),
  navButtons: document.querySelectorAll(".nav-button"),
  productsContainer: document.querySelector(".products-container"),
  addItemModal: document.getElementById('addItemModal'),
  editFormContainer: document.getElementById('edit-form-container'),
  viewMode: document.getElementById('view-mode'),
  editItemForm: document.getElementById('edit-item-form'),
  addButton: document.getElementById('add-button')
};

function initializePage() {
  setupEventListeners();
  checkForErrors();
  loadDefaultMenuItem();
}

function setupEventListeners() {
 
  elements.menuItems.forEach(item => {
    item.addEventListener("click", handleMenuItemClick);
  });


  elements.navButtons.forEach(button => {
    button.addEventListener("click", handleNavButtonClick);
  });


  window.addEventListener("click", handleOutsideClick);


  const menuNavButton = document.querySelector(".nav-button[href='#menu']");
  if (menuNavButton) {
    menuNavButton.addEventListener("click", handleMenuScroll);
  }
}


function checkForErrors() {
  const url = new URL(window.location.href);
  if (url.pathname.includes('null') || url.pathname.includes('videsr') || url.pathname.includes('adria')) {
    console.error("Invalid path detected, redirecting...");
    setTimeout(() => {
      window.location.href = '/admin-menu.php';
    }, ERROR_REDIRECT_DELAY);
  }
}


function loadDefaultMenuItem() {
  const defaultMenu = document.querySelector(".menu-item.active")?.getAttribute("data-page");
  if (defaultMenu) {
    loadMenuItem(defaultMenu);
  }
}






async function loadMenuItem(page) {
  try {
    const response = await fetch(page);
    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
    const data = await response.text();
    elements.productsContainer.innerHTML = data;
  } catch (error) {
    console.error("Error loading content:", error);
    elements.productsContainer.innerHTML = `
      <div class="error">
        <p>Failed to load menu items</p>
        <button onclick="window.location.reload()">Retry</button>
      </div>
    `;
  }
}

//////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////
// Event handlers
function handleMenuItemClick() {
  elements.menuItems.forEach(el => el.classList.remove("active"));
  this.classList.add("active");
  const page = this.getAttribute("data-page");
  if (page) loadMenuItem(page);
}

function handleNavButtonClick() {
  elements.navButtons.forEach(el => el.classList.remove("active"));
  this.classList.add("active");
}

function handleMenuScroll(e) {
  e.preventDefault();
  document.querySelector(".menu-bar").scrollIntoView({ behavior: "smooth" });
}

function handleOutsideClick(event) {
  if (event.target === elements.addItemModal) {
    closeAddItemModal();
  }
}






//////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////


function enableEditMode() {
  document.getElementById('overlay').style.display = 'block';
  document.querySelector('.form-container').style.display = 'block';
}

function cancelEditMode() {
  document.getElementById('overlay').style.display = 'none';
  document.querySelector('.form-container').style.display = 'none';
}

// Other existing functions...
//////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////
// Modal functions
function openAddItemModal() {
  document.getElementById('addItemModal').style.display = 'block';
  document.getElementById('overlay').style.display = 'block';
}

function closeAddItemModal() {
  document.getElementById('addItemModal').style.display = 'none';
  document.getElementById('overlay').style.display = 'none';
}



//////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////
function openManageModal(name, price, description, image, id, category) {
  // Set view mode
  elements.viewMode.style.display = 'block';
  elements.editFormContainer.style.display = 'block';
  elements.addButton.style.display = 'none';

  document.getElementById('no-item-selected').style.display = 'none';
  // Populate view data
  document.getElementById('view-image').src = '/public/' + image;
  document.getElementById('view-name').textContent = name;
  document.getElementById('view-price').textContent = '₱' + price;
  document.getElementById('view-description').textContent = description;

  // Set form data
  document.getElementById('edit-id').value = id;
  document.getElementById('edit-name').value = name;
  document.getElementById('edit-price').value = price;
  document.getElementById('edit-description').value = description;
  document.getElementById('edit-image-preview').src = '/public/' + image;
  document.querySelector('#edit-item-form input[name="category"]').value = category;
  document.getElementById('edit-existing-image').value = image;

  // Store current ID
  window.currentItemIdToDelete = id;

document.querySelectorAll('.menu-card').forEach(card => {
    card.classList.remove('active');
});

const card = document.getElementById(`menuCard-${id}`);
if (card) {
    card.classList.add('active');
}

}
//////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////


function closeModal() {
  elements.editFormContainer.style.display = 'none';
  elements.viewMode.style.display = 'none';
  elements.editItemForm.style.display = 'none';
  elements.addButton.style.display = 'block';
  document.getElementById('edit-item-form').reset();
  document.getElementById('edit-image-preview').src = '';

  
  const activeCards = document.querySelectorAll('.menu-card.active');
  activeCards.forEach(card => {
      card.classList.remove('active');
  });
}



//////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////


function deleteItem() {
  if (confirm("Are you sure you want to delete this item?")) {
    const form = document.createElement("form");
    form.method = "POST";
    form.action = "/controllers/delete-item.php";

    const input = document.createElement("input");
    input.type = "hidden";
    input.name = "item_id";
    input.value = window.currentItemIdToDelete;

    form.appendChild(input);
    document.body.appendChild(form);
    form.submit();
  }
}

//////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////







document.addEventListener("DOMContentLoaded", initializePage);

/////////////////////////////////////////////////////////////////////////
////////////////////////////SWEET ALERTS////////////////////////////////////////////
