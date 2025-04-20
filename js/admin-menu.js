
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











// MODAL FOR EDIT ITEM
function openManageModal(name, price, description, image, id, category) {
    // Show container
    document.getElementById('edit-form-container').style.display = 'block';

    // Set View Mode
    document.getElementById('view-mode').style.display = 'block';
    document.getElementById('edit-item-form').style.display = 'none';

    // Populate View Mode Info
    document.getElementById('view-image').src = '/public/' + image;
    document.getElementById('view-name').textContent = name;
    document.getElementById('view-price').textContent = '₱' + price;
    document.getElementById('view-description').textContent = description;

    // Pre-fill Edit Form
    document.getElementById('edit-id').value = id;
    document.getElementById('edit-name').value = name;
    document.getElementById('edit-price').value = price;
    document.getElementById('edit-description').value = description;
    document.getElementById('edit-image-preview').src = '/public/' + image;
    document.querySelector('#edit-item-form input[name="category"]').value = category;
    document.getElementById('edit-existing-image').value = image;


    // Store current ID for deletion
    window.currentItemIdToDelete = id;

    // Hide the Add Item button during view/edit
    document.getElementById('add-button').style.display = 'none';
}

function enableEditMode() {
    document.getElementById('view-mode').style.display = 'none';
    document.getElementById('edit-item-form').style.display = 'block';
}

function cancelEditMode() {
    document.getElementById('edit-item-form').style.display = 'none';
    document.getElementById('view-mode').style.display = 'block';
}

function closeManageModal() {
    // Hide the whole edit section (if you want to close it completely)
    document.getElementById('edit-form-container').style.display = 'none';

    // Reset view/edit states
    document.getElementById('view-mode').style.display = 'none';
    document.getElementById('edit-item-form').style.display = 'none';

    // Show the Add Item button again
    document.getElementById('add-button').style.display = 'inline-block';
}


function closeModal() {
    // Hide the modal container
    document.getElementById('edit-form-container').style.display = 'none';

    // Optionally reset any content or state that needs to be reverted
    document.getElementById('view-mode').style.display = 'none';
    document.getElementById('edit-item-form').style.display = 'none';

    // If needed, show the "Add Item" button again
    document.getElementById('add-button').style.display = 'block';

    // You can also clear the form or reset values
    document.getElementById('edit-item-form').reset();

    // Optionally, clear the image preview or other data
    document.getElementById('edit-image-preview').src = '';
}


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




//END OF MODAL EDIT ITEM
