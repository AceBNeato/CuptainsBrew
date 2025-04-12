document.addEventListener("DOMContentLoaded", function () {
    const hamburgerMenu = document.getElementById("hamburger-menu");
    const navMenu = document.getElementById("nav-menu");

    hamburgerMenu.addEventListener("click", function () {
        navMenu.classList.toggle("active");
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

document.addEventListener("DOMContentLoaded", function () {
    document.body.addEventListener("click", function (event) {
        if (event.target.classList.contains("add-button")) {
            const menuCard = event.target.closest(".menu-card");
            if (!menuCard) return;

            // Remove highlight from any previously selected menu-card
            document.querySelectorAll(".menu-card").forEach(card => {
                card.classList.remove("selected");
            });

            // Add highlight to the clicked menu-card
            menuCard.classList.add("selected");

            const imgSrc = menuCard.querySelector("img")?.src || "";
            const title = menuCard.querySelector("h2")?.innerText || "No Title";
            const price = menuCard.querySelector(".price")?.innerText || "No Price";
            const description = menuCard.querySelector(".description")?.innerText || "No Description";

            const editorContainer = document.querySelector(".item-editor");
            if (!editorContainer) return;

            editorContainer.innerHTML = `
                <img src="${imgSrc}" class="editor-img" alt="${title}">
                <h2>${title}</h2>
                <p class="price">${price}</p>
                <p class="description">${description}</p>
                <button class="remove-item">REMOVE</button>
            `;

            console.log("Item Updated:", title);
        }
    });

    // Remove button event
    document.body.addEventListener("click", function (event) {
        if (event.target.classList.contains("remove-item")) {
            document.querySelector(".item-editor").innerHTML = `<h3>Choose Item To Edit</h3> <p>Add Item</p>`;

            // Remove selection highlight when item is removed
            document.querySelectorAll(".menu-card").forEach(card => {
                card.classList.remove("selected");
            });

            console.log("Editor Cleared");
        }
    });
});



// CAROUSEL SCRIPT


document.addEventListener("DOMContentLoaded", function () {
    const track = document.querySelector(".carousel-track");
    const items = document.querySelectorAll(".carousel-item");
    
    // Duplicate slides for infinite scrolling effect
    items.forEach(item => {
        let clone = item.cloneNode(true);
        track.appendChild(clone);
    });
});




document.addEventListener("DOMContentLoaded", function () {
    const backToTop = document.getElementById("back-to-top");

    if (!backToTop) {
        console.error("Back to Top button not found!");
        return;
    }

    // Show the button when scrolling past 300px
    window.addEventListener("scroll", function () {
        if (window.scrollY > 300) {
            backToTop.classList.add("show");
        } else {
            backToTop.classList.remove("show");
        }
    });

    // Scroll smoothly back to top when clicked
    backToTop.addEventListener("click", function () {
        window.scrollTo({ top: 0, behavior: "smooth" });
    });
});



//SHOW PASSWORD//
document.getElementById("showPassword").addEventListener("change", function() {
    let passwordInput = document.getElementById("password");
    passwordInput.type = this.checked ? "text" : "password";
});




