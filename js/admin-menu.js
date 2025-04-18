
// LOAD PAGE

document.addEventListener("DOMContentLoaded", function () {
    const menuItems = document.querySelectorAll(".menu-item");

    menuItems.forEach(item => {
        item.addEventListener("click", function () {
            // Remove "active" class from all items
            menuItems.forEach(el => el.classList.remove("active"));
            
            // Add "active" class to clicked item
            this.classList.add("active");
        });
    });
});


document.addEventListener("DOMContentLoaded", function () {
    const navbutton = document.querySelectorAll(".nav-button");

    navbutton.forEach(item => {
        item.addEventListener("click", function () {
            // Remove "active" class from all items
            navbutton.forEach(el => el.classList.remove("active"));
            
            // Add "active" class to clicked item
            this.classList.add("active");
        });
    });
});


document.addEventListener("DOMContentLoaded", function () {
    const menuItems = document.querySelectorAll(".menu-item");
    const productsContainer = document.querySelector(".products-container");
    
    function loadMenuItem(page) {
        fetch(page)
            .then(response => response.text())
            .then(data => {
                productsContainer.innerHTML = data;
            })
            .catch(error => console.error("Error loading content:", error));
    }

    const defaultMenu = document.querySelector(".menu-item").getAttribute("data-page");
    loadMenuItem(defaultMenu);

    menuItems.forEach(item => {
        item.addEventListener("click", function () {
            const page = this.getAttribute("data-page");
            loadMenuItem(page);
        });
    });

    document.querySelector(".nav-button[href='#menu']").addEventListener("click", function (e) {
        e.preventDefault();
        document.querySelector(".menu-bar").scrollIntoView({ behavior: "smooth" });
    });
});

// END PAGE LOAD




//OPEN MODAL ADD ITEM
function openAddItemModal() {
    document.getElementById('addItemModal').style.display = 'block';
  }

  function closeAddItemModal() {
    document.getElementById('addItemModal').style.display = 'none';
  }

  // Optional: Close modal if clicking outside of it
  window.onclick = function(event) {
    const modal = document.getElementById('addItemModal');
    if (event.target === modal) {
      closeAddItemModal();
    }
  }











//MODAL FOR EDIT ITEM

  function openEditItemModal(id, name, price, description, imageUrl) {
    // Populate the modal with the item data
    document.getElementById('edit-id').value = id;
    document.getElementById('edit-name').value = name;
    document.getElementById('edit-price').value = price;
    document.getElementById('edit-description').value = description;
    document.getElementById('edit-image').src = imageUrl;

    // Show the edit modal
    document.getElementById('edit-form-container').style.display = 'block';
    document.getElementById('edit-placeholder').style.display = 'none';
}

function deleteItem() {
    // Get the item ID from the hidden input field
    const itemId = document.getElementById('edit-id').value;

    if (confirm("Are you sure you want to delete this item?")) {
        // Send a request to the backend to delete the item
        fetch('/controllers/delete-item.php', {
            method: 'POST',
            body: JSON.stringify({ id: itemId }),
            headers: {
                'Content-Type': 'application/json',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Item deleted successfully!');
                window.location.reload();  // Reload the page to reflect changes
            } else {
                alert('Failed to delete the item');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error deleting item');
        });
    }
}


//END OF MODAL EDIT ITEM
