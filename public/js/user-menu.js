// LOAD PAGE

document.addEventListener("DOMContentLoaded", function () {
    const menuItems = document.querySelectorAll(".menu-item");

    menuItems.forEach(item => {
        item.addEventListener("click", function () {
            
            menuItems.forEach(el => el.classList.remove("active"));
            
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





















//===========ADD ITEM EVENT============
document.addEventListener("DOMContentLoaded", function () {
    document.body.addEventListener("click", function (event) {
        const editorContainer = document.querySelector(".item-editor");

        // Handle "Add Item" button click
        if (event.target.classList.contains("add-item")) {
            if (!editorContainer) return;

            editorContainer.innerHTML = `
                <h2>Add New Item</h2>
                <img src="placeholder.jpg" class="editor-img" alt="Click to add image">
                <input type="file" id="image-upload" accept="image/*" style="display: none;">
                <input type="text" id="item-name" placeholder="Item Name">
                <input type="text" id="item-price" placeholder="Price">
                <textarea id="item-description" placeholder="Description"></textarea>
                <button id="save-item">Save Item</button>
            `;
        }

        // Handle Image Click (Open File Picker)
        if (event.target.classList.contains("editor-img")) {
            document.getElementById("image-upload").click();
        }

        // Handle Image Selection
        if (event.target.id === "image-upload") {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    document.querySelector(".editor-img").src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        }

        // Handle "Save Item" button click
        if (event.target.id === "save-item") {
            const name = document.querySelector("#item-name").value.trim();
            const price = document.querySelector("#item-price").value.trim();
            const description = document.querySelector("#item-description").value.trim();
            const imgSrc = document.querySelector(".editor-img").src;

            if (!name || !price) {
                alert("Name and Price are required!");
                return;
            }

            // Create new item (menu-card)
            const productsContainer = document.querySelector(".products-container"); // Now saving here
            const newItem = document.createElement("div");
            newItem.classList.add("menu-card");
            newItem.innerHTML = `
                <img src="${imgSrc}" alt="${name}">
                <h2>${name}</h2>
                <p class="price">${price}</p>
                <p class="description">${description}</p>
                <button class="add-button">Edit</button>
               
            `;

            // Append to products container
            productsContainer.appendChild(newItem);

            // Clear editor
            editorContainer.innerHTML = `<h1 id="item-to-edit">Choose Item To Edit</h1><button class="add-item"><h1>Add Item</h1></button>`;

            console.log("New Item Added:", name);
        }
    });
});



document.addEventListener("DOMContentLoaded", function () {
    const editorOverlay = document.createElement("div");
    editorOverlay.classList.add("editor-overlay");
    editorOverlay.style.display = "none"; // Initially hidden
    document.body.appendChild(editorOverlay);

    document.body.addEventListener("click", function (event) {
        // Selecting an item first
        if (event.target.classList.contains("add-button")) {
            const menuCard = event.target.closest(".menu-card");
            if (!menuCard) return;

            // Remove selection from other cards
            document.querySelectorAll(".menu-card").forEach(card => card.classList.remove("selected"));

            // Highlight selected card
            menuCard.classList.add("selected");

            // Extract values
            const imgSrc = menuCard.querySelector("img")?.src || "";
            const title = menuCard.querySelector("h2")?.innerText || "No Title";
            const price = menuCard.querySelector(".price")?.innerText || "No Price";
            const description = menuCard.querySelector(".description")?.innerText || "No Description";

            // Update .item-editor with details
            const editorContainer = document.querySelector(".item-editor");
            if (!editorContainer) return;

            editorContainer.innerHTML = `
                <div class="editor-card">
                    <img src="${imgSrc}" class="editor-img" alt="${title}">
                    <h2>${title}</h2>
                    <p class="price">${price}</p>
                    <p class="description">${description}</p>
                    <button class="update-item">Edit</button>
                    <button class="delete-item">Delete</button> <!-- Delete Button -->
                </div>
            `;

            console.log("Item Loaded in Editor:", title);
        }

        // Open overlay when clicking "Edit"
        if (event.target.classList.contains("update-item")) {
            const editorCard = event.target.closest(".editor-card");
            if (!editorCard) return;

            // Extract values from editor card
            const imgSrc = editorCard.querySelector("img")?.src || "";
            const title = editorCard.querySelector("h2")?.innerText || "";
            const price = editorCard.querySelector(".price")?.innerText || "";
            const description = editorCard.querySelector(".description")?.innerText || "";

            // Show overlay with item details
            editorOverlay.innerHTML = `
                <div class="overlay-content">
                    <h2>Edit Item</h2>
                    <img src="${imgSrc}" class="editor-img" alt="${title}">
                    <input type="file" id="image-upload" accept="image/*" style="display: none;">
                    <input type="text" id="edit-name" value="${title}">
                    <input type="text" id="edit-price" value="${price}">
                    <textarea id="edit-description">${description}</textarea>
                    <button id="save-changes">Save Changes</button>
                    <button id="close-editor">Cancel</button>
                </div>
            `;
            editorOverlay.style.display = "flex";

            console.log("Editing Item:", title);
        }

        // Image Upload
        if (event.target.classList.contains("editor-img")) {
            document.getElementById("image-upload").click();
        }

        document.body.addEventListener("change", function (event) {
            if (event.target.id === "image-upload") {
                const file = event.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        document.querySelector(".editor-img").src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                }
            }
        });

        // Save Changes
        if (event.target.id === "save-changes") {
            const editorCard = document.querySelector(".editor-card");
            if (!editorCard) return;

            const updatedName = document.querySelector("#edit-name").value.trim();
            const updatedPrice = document.querySelector("#edit-price").value.trim();
            const updatedDescription = document.querySelector("#edit-description").value.trim();
            const updatedImgSrc = document.querySelector(".editor-img").src;

            if (!updatedName || !updatedPrice) {
                alert("Name and Price are required!");
                return;
            }

            // Update editor-card
            editorCard.querySelector("h2").innerText = updatedName;
            editorCard.querySelector(".price").innerText = updatedPrice;
            editorCard.querySelector(".description").innerText = updatedDescription;
            editorCard.querySelector("img").src = updatedImgSrc;

            // Also update the selected menu-card
            const selectedCard = document.querySelector(".menu-card.selected");
            if (selectedCard) {
                selectedCard.querySelector("h2").innerText = updatedName;
                selectedCard.querySelector(".price").innerText = updatedPrice;
                selectedCard.querySelector(".description").innerText = updatedDescription;
                selectedCard.querySelector("img").src = updatedImgSrc;
            }

            editorOverlay.style.display = "none";

            console.log("Item Updated:", updatedName);
        }

        // Cancel Editing
        if (event.target.id === "close-editor") {
            editorOverlay.style.display = "none";
        }

        // Delete Item Functionality
        if (event.target.classList.contains("delete-item")) {
            const selectedCard = document.querySelector(".menu-card.selected");
            if (!selectedCard) return;

            // Confirm deletion
            if (confirm("Are you sure you want to delete this item?")) {
                selectedCard.remove();
                document.querySelector(".item-editor").innerHTML = ""; 
                console.log("Item Deleted");
            }


        }

    });
});

// Product Modal Functionality
let currentProduct = null;

async function showProductModal(product) {
    currentProduct = product;
    
    // Fetch variations if available
    try {
        const response = await fetch(`/controllers/get-variations.php?product_id=${product.id}`);
        const data = await response.json();
        
        const variationSelector = document.getElementById('variation-selector');
        const modalProductPrice = document.getElementById('modalProductPrice');
        
        if (data.success && data.variations.length > 0) {
            // Show variation selector
            variationSelector.style.display = 'block';
            
            // Update variation prices
            const hotVariation = data.variations.find(v => v.variation_type === 'Hot');
            const icedVariation = data.variations.find(v => v.variation_type === 'Iced');
            
            document.getElementById('hot-variation-price').textContent = hotVariation ? hotVariation.price : product.price;
            document.getElementById('iced-variation-price').textContent = icedVariation ? icedVariation.price : product.price;
            
            // Update price based on selected variation
            const updatePrice = () => {
                const selectedVariation = document.querySelector('input[name="variation"]:checked').value;
                const variation = data.variations.find(v => v.variation_type === selectedVariation);
                modalProductPrice.textContent = '₱' + (variation ? variation.price : product.price);
            };
            
            // Add change event listeners
            document.querySelectorAll('input[name="variation"]').forEach(radio => {
                radio.addEventListener('change', updatePrice);
            });
            
            // Set initial price
            updatePrice();
        } else {
            // Hide variation selector and show base price
            variationSelector.style.display = 'none';
            modalProductPrice.textContent = '₱' + product.price;
        }
    } catch (error) {
        console.error('Error fetching variations:', error);
        // Show base price if variations fetch fails
        document.getElementById('variation-selector').style.display = 'none';
        document.getElementById('modalProductPrice').textContent = '₱' + product.price;
    }
    
    // Set other modal content
    document.getElementById('modalProductImage').src = '/public/' + product.image;
    document.getElementById('modalProductName').textContent = product.name;
    document.getElementById('modalProductDesc').textContent = product.desc || 'No description available';
    document.getElementById('productQuantity').value = 1;
    
    // Show modal
    document.getElementById('productModal').classList.add('active');
}

function closeProductModal() {
    document.getElementById('productModal').classList.remove('active');
    currentProduct = null;
}

// Cart Functionality
function addToCart(productId, name, price, image, quantity = 1) {
    const variation = document.querySelector('input[name="variation"]:checked')?.value;
    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('quantity', quantity);
    if (variation) {
        formData.append('variation', variation);
    }
    
    fetch('/views/users/user-menu.php?action=add_to_cart', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showCartNotification(`${name} (${variation || 'Regular'}) added to cart (${quantity}x)`);
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: `${name} (${variation || 'Regular'}) added to cart (${quantity}x)`,
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
                        window.location.href = '/views/auth/login.php';
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
    })
    .catch(error => {
        console.error('Error:', error);
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
