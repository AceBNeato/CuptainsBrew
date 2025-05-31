/**
 * Main script file for Captain's Brew Cafe
 * Contains global utility functions and performance optimizations
 */

// Performance optimization functions
const PerformanceOptimizer = {
    // Check if performance mode is enabled
    isPerformanceModeEnabled: function() {
        return localStorage.getItem('performance_mode') === 'true';
    },
    
    // Check if animations are disabled
    areAnimationsDisabled: function() {
        return localStorage.getItem('disable_animations') === 'true';
    },
    
    // Check if image quality is reduced
    isImageQualityReduced: function() {
        return localStorage.getItem('reduce_image_quality') === 'true';
    },
    
    // Apply all performance settings
    applyAllSettings: function() {
        // Apply animation settings
        if (this.areAnimationsDisabled()) {
            document.body.classList.add('disable-animations');
        } else {
            document.body.classList.remove('disable-animations');
        }
        
        // Apply image quality settings
        if (this.isImageQualityReduced()) {
            document.body.classList.add('reduce-image-quality');
            this.optimizeImages();
        } else {
            document.body.classList.remove('reduce-image-quality');
        }
        
        // Apply notification settings is handled by notifications.js
    },
    
    // Optimize images based on settings
    optimizeImages: function() {
        if (!this.isImageQualityReduced()) return;
        
        // Find all images not marked as critical
        const images = document.querySelectorAll('img:not(.critical-image)');
        
        // Add loading="lazy" attribute to all images
        images.forEach(img => {
            if (!img.hasAttribute('loading')) {
                img.setAttribute('loading', 'lazy');
            }
            
            // For non-critical images, use lower resolution if available
            if (img.hasAttribute('data-low-res')) {
                const lowResSrc = img.getAttribute('data-low-res');
                img.setAttribute('src', lowResSrc);
            }
        });
    },
    
    // Lazy load scripts
    lazyLoadScripts: function() {
        const scripts = document.querySelectorAll('script[data-lazy]');
        
        scripts.forEach(script => {
            const src = script.getAttribute('data-src');
            if (src) {
                setTimeout(() => {
                    script.setAttribute('src', src);
                    script.removeAttribute('data-lazy');
                    script.removeAttribute('data-src');
                }, 1000); // 1 second delay
            }
        });
    }
};

// Document ready function
document.addEventListener('DOMContentLoaded', function() {
    // Apply performance settings
    PerformanceOptimizer.applyAllSettings();
    
    // Lazy load non-critical scripts
    PerformanceOptimizer.lazyLoadScripts();
    
    // Add event listeners for dynamic content loading
    document.addEventListener('contentLoaded', function() {
        PerformanceOptimizer.optimizeImages();
    });
    
    // Initialize existing functionality
    initializeExistingFunctionality();
});

// Function to initialize existing functionality
function initializeExistingFunctionality() {
    // Back to top button
    const backToTop = document.getElementById('backToTop');
    if (backToTop) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 300) {
                backToTop.classList.add('active');
            } else {
                backToTop.classList.remove('active');
            }
        });

        backToTop.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: PerformanceOptimizer.areAnimationsDisabled() ? 'auto' : 'smooth'
            });
        });
    }
    
    // Mobile menu toggle
    const hamburgerMenu = document.getElementById('hamburger-menu');
    const navMenu = document.getElementById('nav-menu');
    
    if (hamburgerMenu && navMenu) {
        hamburgerMenu.addEventListener('click', function() {
            navMenu.classList.toggle('active');
        });
    }
    
    // Profile dropdown toggle for mobile
    const profileElement = document.querySelector('.profile');
    if (profileElement) {
        profileElement.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                this.classList.toggle('active');
            }
        });
    }
}

// Export the PerformanceOptimizer for use in other scripts
window.PerformanceOptimizer = PerformanceOptimizer;

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




