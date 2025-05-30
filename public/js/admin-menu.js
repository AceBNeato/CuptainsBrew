// Constants
const ERROR_REDIRECT_DELAY = 3000;

// DOM Elements
const elements = {
  menuItems: document.querySelectorAll(".menu-item"),
  navButtons: document.querySelectorAll(".nav-button"),
  productsContainer: document.querySelector(".products-container"),
  addItemModal: document.getElementById('addItemModal'),
  editFormContainer: document.getElementById('edit-form-container'),
  editContainer: document.getElementById('edit-container'),
  viewMode: document.getElementById('view-mode'),
  editItemForm: document.getElementById('edit-item-form'),
  addButton: document.getElementById('add-button'),
  noItemSelected: document.getElementById('no-item-selected')
};

// Initialize page
document.addEventListener("DOMContentLoaded", function() {
  setupEventListeners();
  checkForErrors();
  handleUrlParams();
});

// Event Listeners
function setupEventListeners() {
  // Add item form submission
  const addItemForm = document.getElementById('add-item-form');
  if (addItemForm) {
    addItemForm.addEventListener('submit', handleAddItemSubmit);
  }

  // Close modal on outside click
  window.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
      closeAddItemModal();
    }
  });

  // Close modal on escape key
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      closeAddItemModal();
    }
  });

  // Category selection
  document.querySelectorAll('.menu-item').forEach(item => {
    item.addEventListener('click', function() {
      loadCategory(this.getAttribute('data-category-id'));
  });
  });

  // Search functionality
  const searchInput = document.getElementById('search-input');
  if (searchInput) {
    searchInput.addEventListener('keyup', handleSearch);
  }

  // Add edit form submission handler
  const editForm = document.getElementById('edit-item-form');
  if (editForm) {
    editForm.addEventListener('submit', handleEditSubmit);
  }

  // Handle variations checkbox
  document.getElementById('has-variations').addEventListener('change', function(e) {
    const variationPrices = document.getElementById('variation-prices');
    variationPrices.style.display = e.target.checked ? 'block' : 'none';
    
    // Set required attribute based on checkbox state
    const hotPrice = document.getElementById('hot-price');
    const icedPrice = document.getElementById('iced-price');
    hotPrice.required = e.target.checked;
    icedPrice.required = e.target.checked;
  });
}

// Error checking
function checkForErrors() {
  const url = new URL(window.location.href);
  if (url.pathname.includes('null') || url.pathname.includes('undefined')) {
    console.error("Invalid path detected");
    setTimeout(() => {
      window.location.href = '/views/admin/Admin-Menu.php';
    }, ERROR_REDIRECT_DELAY);
  }
}

// URL parameter handling
function handleUrlParams() {
  const urlParams = new URLSearchParams(window.location.search);
  const successMessage = urlParams.get('success');
  const errorMessage = urlParams.get('error');

  if (successMessage) {
    Swal.fire({
      icon: 'success',
      title: 'Success!',
      text: successMessage,
      confirmButtonColor: '#2C6E8A'
    });
  } else if (errorMessage) {
    Swal.fire({
      icon: 'error',
      title: 'Error!',
      text: errorMessage,
      confirmButtonColor: '#2C6E8A'
    });
  }
}

// Modal functions
function openAddItemModal() {
  const modal = document.getElementById('addItemModal');
  if (modal) {
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
  }
}

function closeAddItemModal() {
  const modal = document.getElementById('addItemModal');
  if (modal) {
    modal.classList.remove('active');
    document.body.style.overflow = '';
    
    // Reset form
    const form = document.getElementById('add-item-form');
    if (form) {
      form.reset();
      const preview = document.getElementById('add-image-preview');
      const placeholder = document.getElementById('add-image-placeholder');
      if (preview && placeholder) {
        preview.style.display = 'none';
        preview.src = '';
        placeholder.style.display = 'flex';
}
    }
  }
}

// Form submission handling
async function handleAddItemSubmit(e) {
  e.preventDefault();
  
  try {
    const formData = new FormData(this);
    
    // Validate form data
    const itemName = formData.get('item_name').trim();
    const itemPrice = formData.get('item_price');
    const itemDescription = formData.get('item_description').trim();
    const itemImage = formData.get('item_image');
    const hasVariations = formData.get('has_variations') === 'on';
    
    if (!itemName || !itemPrice || !itemDescription || !itemImage.size) {
      Swal.fire({
        icon: 'error',
        title: 'Validation Error',
        text: 'Please fill in all required fields and select an image',
        confirmButtonColor: '#2C6E8A'
      });
      return;
    }
    
    // Validate variation prices if variations are enabled
    if (hasVariations) {
      const hotPrice = formData.get('hot_price');
      const icedPrice = formData.get('iced_price');
      
      if (!hotPrice || !icedPrice) {
        Swal.fire({
          icon: 'error',
          title: 'Validation Error',
          text: 'Please provide prices for both Hot and Iced variations',
          confirmButtonColor: '#2C6E8A'
        });
        return;
      }
    }
    
    // Close modal before showing loading state
    closeAddItemModal();
    
    // Show loading state
    Swal.fire({
      title: 'Adding item...',
      text: 'Please wait while we process your request',
      allowOutsideClick: false,
      showConfirmButton: false,
      willOpen: () => {
        Swal.showLoading();
      }
    });
    
    // Submit form
    const response = await fetch('/controllers/add-item.php', {
      method: 'POST',
      body: formData
    });
    
    const result = await response.json();
    
    if (result.success) {
      Swal.fire({
        icon: 'success',
        title: 'Success!',
        text: result.message,
        confirmButtonColor: '#2C6E8A'
      }).then(() => {
        window.location.reload();
      });
    } else {
      throw new Error(result.message || 'Failed to add item');
    }
  } catch (error) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: error.message || 'An error occurred while adding the item',
      confirmButtonColor: '#2C6E8A'
    });
  }
}

// Image preview handling
function previewImage(input) {
  const preview = document.getElementById('imagePreview');
  const placeholder = document.getElementById('imagePlaceholder');
  
  if (input.files && input.files[0] && preview && placeholder) {
    const reader = new FileReader();
    
    reader.onload = function(e) {
      preview.src = e.target.result;
      preview.style.display = 'block';
      placeholder.style.display = 'none';
}

    reader.readAsDataURL(input.files[0]);
  }
}

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
        
        reader.readAsDataURL(input.files[0]);
    }
}

// Category and search functions
function loadCategory(categoryId) {
  if (!categoryId) return;
  
  const url = new URL(window.location.href);
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

// Item management functions
async function openManageModal(name, price, description, image, id, category) {
    // Store the item ID globally for delete operation
    window.currentItemIdToDelete = id;

    // Update view mode
    const viewMode = document.getElementById('view-mode');
    const editFormContainer = document.getElementById('edit-form-container');
    const addButton = document.getElementById('add-button');
    const noItemSelected = document.getElementById('no-item-selected');

    if (!viewMode || !editFormContainer || !addButton || !noItemSelected) {
        console.error('Required elements not found');
        return;
    }

    // Show view mode and hide other elements
    viewMode.style.display = 'block';
    editFormContainer.style.display = 'block';
    addButton.style.display = 'none';
    noItemSelected.style.display = 'none';

    // Update view data
  document.getElementById('view-image').src = '/public/' + image;
  document.getElementById('view-name').textContent = name;
  document.getElementById('view-price').textContent = '₱' + price;
  document.getElementById('view-description').textContent = description;

    // Update form data
  document.getElementById('edit-id').value = id;
  document.getElementById('edit-name').value = name;
  document.getElementById('edit-price').value = price;
  document.getElementById('edit-description').value = description;
  document.getElementById('edit-image-preview').src = '/public/' + image;
    document.getElementById('edit-category').value = category;

    // Update active card
document.querySelectorAll('.menu-card').forEach(card => {
    card.classList.remove('active');
});

    const activeCard = document.getElementById(`menuCard-${id}`);
    if (activeCard) {
        activeCard.classList.add('active');
}

    // Fetch variations data
    try {
        const response = await fetch(`/controllers/get-variations.php?product_id=${id}`);
        const variations = await response.json();
        
        // Update variations UI
        const hasVariations = variations.length > 0;
        document.getElementById('has-variations').checked = hasVariations;
        document.getElementById('variation-prices').style.display = hasVariations ? 'block' : 'none';
        
        // Update view mode with variations
        const viewVariations = document.getElementById('view-variations');
        if (viewVariations) {
            if (hasVariations) {
                const hotVariation = variations.find(v => v.variation_type === 'Hot');
                const icedVariation = variations.find(v => v.variation_type === 'Iced');
                
                if (hotVariation) {
                    document.getElementById('hot-price-display').textContent = parseFloat(hotVariation.price).toFixed(2);
                }
                
                if (icedVariation) {
                    document.getElementById('iced-price-display').textContent = parseFloat(icedVariation.price).toFixed(2);
                }
                
                viewVariations.style.display = 'block';
            } else {
                viewVariations.style.display = 'none';
            }
        }
        
        if (hasVariations) {
            const hotVariation = variations.find(v => v.variation_type === 'Hot');
            const icedVariation = variations.find(v => v.variation_type === 'Iced');
            
            document.getElementById('hot-price').value = hotVariation ? hotVariation.price : '';
            document.getElementById('iced-price').value = icedVariation ? icedVariation.price : '';
        } else {
            document.getElementById('hot-price').value = '';
            document.getElementById('iced-price').value = '';
        }
    } catch (error) {
        console.error('Error fetching variations:', error);
    }
}

function closeModal() {
  if (!elements.editFormContainer || !elements.viewMode || !elements.addButton || !elements.noItemSelected) return;

  // Hide edit form and view mode
  elements.editFormContainer.style.display = 'none';
  elements.viewMode.style.display = 'none';
  
  // Show add button and no item selected message
  elements.addButton.style.display = 'block';
  elements.noItemSelected.style.display = 'block';

  // Reset edit form if it exists
  const editForm = document.getElementById('edit-item-form');
  if (editForm) {
    editForm.reset();
  }

  // Reset image preview
  const preview = document.getElementById('edit-image-preview');
  if (preview) {
    preview.src = '';
  }

  // Remove active state from all menu cards
  document.querySelectorAll('.menu-card').forEach(card => {
      card.classList.remove('active');
  });

  // Clear the stored item ID
  window.currentItemIdToDelete = null;
}

// Edit form submission handling
async function handleEditSubmit(e) {
  e.preventDefault();
  
  try {
    const formData = new FormData(e.target);
    
    // Add variations data
    const hasVariations = formData.get('has_variations') === 'on';
    if (hasVariations) {
      formData.append('variations', JSON.stringify([
        { type: 'Hot', price: formData.get('hot_price') },
        { type: 'Iced', price: formData.get('iced_price') }
      ]));
    }
    
    // Validate form data
    const itemName = formData.get('item_name').trim();
    const itemPrice = formData.get('item_price');
    const itemDescription = formData.get('item_description').trim();
    
    if (!itemName || !itemPrice || !itemDescription) {
      throw new Error('Please fill in all required fields');
    }

    // Show loading state
    Swal.fire({
      title: 'Updating item...',
      text: 'Please wait while we process your request',
      allowOutsideClick: false,
      showConfirmButton: false,
      willOpen: () => {
        Swal.showLoading();
      }
    });

    // Submit form
    const response = await fetch('/controllers/update-item.php', {
      method: 'POST',
      body: formData
    });

    const result = await response.json();

    if (result.success) {
      Swal.fire({
        icon: 'success',
        title: 'Success!',
        text: result.message,
        confirmButtonColor: '#2C6E8A'
      }).then(() => {
        window.location.reload();
      });
    } else {
      throw new Error(result.message || 'Failed to update item');
    }
  } catch (error) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: error.message,
      confirmButtonColor: '#2C6E8A'
    });
  }
}

// Enable edit mode
function enableEditMode() {
  const modal = document.getElementById('editItemModal');
  if (modal) {
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
  }
}

// Cancel edit mode
function cancelEditMode() {
  const modal = document.getElementById('editItemModal');
  if (modal) {
    modal.classList.remove('active');
    document.body.style.overflow = '';
    
    // Reset form
    const form = document.getElementById('edit-item-form');
    if (form) {
      form.reset();
    }
    
    // Reset image preview
    const preview = document.getElementById('edit-image-preview');
    if (preview) {
      preview.src = '';
    }
  }
}

// Delete item with confirmation
async function deleteItem() {
    // Double check if item ID exists
    if (!window.currentItemIdToDelete) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No item selected for deletion',
            confirmButtonColor: '#2C6E8A'
        });
        return;
    }

    try {
        // Get item details for confirmation
        const itemName = document.getElementById('view-name').textContent;
        if (!itemName) {
            throw new Error('Item details not found');
        }
        
        // Show confirmation dialog
        const result = await Swal.fire({
            title: 'Delete Confirmation',
            html: `Are you sure you want to delete <strong>${itemName}</strong>?<br><br>This action cannot be undone!`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#EF4444',
            cancelButtonColor: '#A9D6E5',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel',
            focusCancel: true
        });

        if (result.isConfirmed) {
            // Show loading state
            Swal.fire({
                title: 'Deleting item...',
                html: `<div class="delete-progress">
                        <p>Removing ${itemName}...</p>
                        <p>Please wait while we process your request</p>
                      </div>`,
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });

            // Get CSRF token
            const csrfToken = document.querySelector('input[name="csrf_token"]').value;
            if (!csrfToken) {
                throw new Error('Security token not found');
            }

            // Prepare form data
            const formData = new FormData();
            formData.append('item_id', window.currentItemIdToDelete);
            formData.append('csrf_token', csrfToken);

            // Send delete request
            const response = await fetch('/controllers/delete-item.php', {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (data.success) {
                // Show success message
                await Swal.fire({
                    icon: 'success',
                    title: 'Deleted Successfully!',
                    html: `<strong>${itemName}</strong> has been deleted.`,
                    confirmButtonColor: '#2C6E8A'
                });
                
                // Remove the deleted item's card with animation
                const deletedCard = document.getElementById(`menuCard-${window.currentItemIdToDelete}`);
                if (deletedCard) {
                    deletedCard.style.transition = 'all 0.3s ease';
                    deletedCard.style.opacity = '0';
                    deletedCard.style.transform = 'scale(0.8)';
                    
                    // Remove element after animation
                    setTimeout(() => {
                        deletedCard.remove();
                        
                        // Check if no items left
                        const remainingCards = document.querySelectorAll('.menu-card');
                        if (remainingCards.length === 0) {
                            // Show empty state message
                            const container = document.getElementById('menu-list-container');
                            if (container) {
                                container.innerHTML = '<div class="no-items">No items in this category.</div>';
                            }
                        }
                    }, 300);
                }
                
                // Close the modal and reset state
                closeModal();
            } else {
                throw new Error(data.message || 'Failed to delete item');
            }
        }
    } catch (error) {
        console.error('Delete error:', error);
        
        Swal.fire({
            icon: 'error',
            title: 'Delete Failed',
            text: error.message || 'An error occurred while deleting the item',
            confirmButtonColor: '#2C6E8A',
            showConfirmButton: true
        });
  }
}

//////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////







document.addEventListener("DOMContentLoaded", initializePage);

/////////////////////////////////////////////////////////////////////////
////////////////////////////SWEET ALERTS////////////////////////////////////////////
