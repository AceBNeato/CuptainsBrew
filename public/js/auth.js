document.addEventListener("keydown", function (event) {
    // Detect "Ctrl + Shift + S"
    if (event.ctrlKey && event.shiftKey && event.key.toLowerCase() === "q") {
        console.log("Admin shortcut detected!");
        
        // Store admin login status
        localStorage.setItem("userRole", "admin");

        // Show confirmation
        alert("Admin logged in via secret shortcut!");

        // Redirect to Admin Dashboard (Change the URL if needed)
        window.location.href = "/views/auth/admin-login.html";
    } 
});

document.addEventListener("keydown", function (event) {
if (event.ctrlKey && event.shiftKey && event.key.toLowerCase() === "s") {
    console.log("User shortcut detected!");
    
    // Store admin login status
    localStorage.setItem("userRole", "user");

    // Show confirmation
    alert("Going back to users");

    // Redirect to Admin Dashboard (Change the URL if needed)
    window.location.href = "/views/auth/login.html";
}
});


document.addEventListener("DOMContentLoaded", function () {
    const adminLoginForm = document.getElementById("admin-login-form");

    if (adminLoginForm) {
        adminLoginForm.addEventListener("submit", function (event) {
            event.preventDefault();

            const email = document.getElementById("email").value;
            const password = document.getElementById("password").value;
            const loadingOverlay = document.getElementById("loading-overlay");

            // Show loading overlay
            loadingOverlay.style.display = "flex";

            // Simulate a delay for demonstration purposes
            setTimeout(() => {
                if (email === "admin@123" && password === "admin123") {
                   
                    sessionStorage.setItem("userRole", "admin");
                    localStorage.setItem("userLoggedIn", "true");

                    
                    window.location.href = "../../../views/admin/admin-menu.html";
                } else {
                   
                        Swal.fire({
                            icon: "error",
                            title: "Oops...",
                            text: "Invalid email or password! Please Try Again!",
                            footer: '<a href="#">Why do I have this issue?</a>'
                        }); 

                    loadingOverlay.style.display = "none"; // Hide loading overlay on failed login
                }
            }, 2000); // Adjust the delay as needed
        });
    }
});






document.addEventListener("DOMContentLoaded", function () {
    const userLoginForm = document.getElementById("user-login-form");

    if (userLoginForm) {
        userLoginForm.addEventListener("submit", function (event) {
            event.preventDefault();

            const email = document.getElementById("email").value;
            const password = document.getElementById("password").value;
            const loadingOverlay = document.getElementById("loading-overlay");

            // Show loading overlay
            loadingOverlay.style.display = "flex";

            // Simulate a delay for demonstration purposes
            setTimeout(() => {
                if (email === "user@123" && password === "user123") {
                    sessionStorage.setItem("userRole", "user");
                    localStorage.setItem("userLoggedIn", "true");
                    window.location.href = "/views/users/user-home.html";
                } else {
                    alert("Invalid login credentials.");
                    loadingOverlay.style.display = "none"; // Hide loading overlay on failed login
                }
            }, 2000); // Adjust the delay as needed
        });
    }
});


window.onload = function() {
    const loadingOverlay = document.getElementById("loading-overlay");
    if (loadingOverlay) {
        loadingOverlay.style.display = "none";
    }
};


document.addEventListener("DOMContentLoaded", function () {
    const getStartedBtn = document.getElementById("view-menu-button");
    const cartIcon = document.getElementById("cart-icon");
    const profileIcon = document.getElementById("profile-icon");

    // Check if the user is logged in
    const userLoggedIn = localStorage.getItem("userLoggedIn");
    const userRole = sessionStorage.getItem("userRole");

    if (userLoggedIn === "true" && userRole === "user") {
        // Change "Get Started" button to "View Our Menu"
        getStartedBtn.innerText = "View Our Menu";
        getStartedBtn.href = "/views/users/user-menu.html";

        // Show Cart and Profile icons
        cartIcon.classList.remove("hidden");
        profileIcon.classList.remove("hidden");
    }
});

document.addEventListener("DOMContentLoaded", function () {
    const getStartedBtn = document.getElementById("view-menu-button");
    const cartIcon = document.getElementById("cart-icon");
    const profileIcon = document.getElementById("profile-icon");

 
    if (cartIcon && profileIcon) {
        
        const userLoggedIn = localStorage.getItem("userLoggedIn");
        const userRole = sessionStorage.getItem("userRole");

        if (userLoggedIn === "true" && userRole === "user") {
            
            cartIcon.classList.remove("hidden");
            profileIcon.classList.remove("hidden");
        }
    }
});















/* ========================== Logout Function ========================== */
function showLogoutOverlay() {
   
    const overlay = document.createElement("div");
    overlay.id = "logout-overlay";
    overlay.style.position = "fixed";
    overlay.style.top = "0";
    overlay.style.left = "0";
    overlay.style.width = "100%";
    overlay.style.height = "100%";
    overlay.style.backgroundColor = "rgba(0, 0, 0, 0.5)";
    overlay.style.display = "flex";
    overlay.style.fontFamily = "Segoe UI";
    overlay.style.justifyContent = "center";
    overlay.style.alignItems = "center";
    overlay.style.zIndex = "9999";

  
    const modal = document.createElement("div");
    modal.style.backgroundColor = "#fff";
    modal.style.padding = "20px";
    modal.style.borderRadius = "8px";
    modal.style.textAlign = "center";
    modal.style.boxShadow = "0px 4px 6px rgba(0,0,0,0.1)";
    modal.style.minWidth = "300px";


    const message = document.createElement("p");
    message.innerText = "Thanks for stopping by. See you soon!";
    message.style.fontSize = "16px";
    message.style.marginBottom = "20px";

   
    const buttonsContainer = document.createElement("div");
    buttonsContainer.style.display = "flex";
    buttonsContainer.style.justifyContent = "space-between";

   
    const cancelButton = document.createElement("button");
    cancelButton.innerText = "Cancel";
    cancelButton.style.padding = "10px 20px";
    cancelButton.style.border = "none";
    cancelButton.style.backgroundColor = "#6C4E31";
    cancelButton.style.color = "white";
    cancelButton.style.borderRadius = "5px";
    cancelButton.style.cursor = "pointer";
    cancelButton.onclick = function () {
        document.body.removeChild(overlay);
    };

    const logoutButton = document.createElement("button");
    logoutButton.innerText = "Logout";
    logoutButton.style.padding = "10px 20px";
    logoutButton.style.border = "none";
    logoutButton.style.backgroundColor = "#6C4E31";
    logoutButton.style.color = "white";
    logoutButton.style.borderRadius = "5px";
    logoutButton.style.cursor = "pointer";
    logoutButton.onclick = logout;

    buttonsContainer.appendChild(cancelButton);
    buttonsContainer.appendChild(logoutButton);
    modal.appendChild(message);
    modal.appendChild(buttonsContainer);
    overlay.appendChild(modal);
    document.body.appendChild(overlay);
}

function logout() {
    const userRole = sessionStorage.getItem("userRole");

    sessionStorage.removeItem("userRole");
    localStorage.removeItem("userLoggedIn");

    if (userRole === "admin") {
        window.location.href = "/views/home.html"; 
    } else {
        window.location.href = "/views/home.html"; 
    }
}

// Attach logout function to logout button
document.addEventListener("DOMContentLoaded", function () {
    const logoutButton = document.getElementById("logout-button");
    if (logoutButton) {
        logoutButton.addEventListener("click", showLogoutOverlay);
    }
});












//ADMIN_ACCOUNTS

// auth.js (Handles Authentication & User Storage)
// Simulating user storage in localStorage (Replace with API if needed)
function getUsers() {
    let users = JSON.parse(localStorage.getItem("users")) || [];
    
    // If no users exist, create a default admin account
    if (users.length === 0) {
        let defaultAdmin = {
            firstName: "user",
            lastName: "123",
            email: "user@123",
            number: "1234567890",
            password: "user123",
            role: "user"
        };
        users.push(defaultAdmin);
        localStorage.setItem("users", JSON.stringify(users));
    }
    
    return users;
}

function addUser(firstName, lastName, email, number, username, password, role = "user") {
    let users = getUsers();
    let existingUser = users.find(user => user.username === username || user.email === email);
    if (existingUser) {
        alert("User already exists!");
        return false;
    }
    users.push({ firstName, lastName, email, number, username, password, role });
    localStorage.setItem("users", JSON.stringify(users));
    return true;
}

function getAdminAccounts() {
    return getUsers().filter(user => user.role === "admin");
}

function authenticateUser(username, password) {
    let users = getUsers();
    let user = users.find(u => u.username === username && u.password === password);
    return user ? user.role : null;
}

// Function to display all users in admin panel
function displayUsers() {
    let users = getUsers();
    let userTable = document.getElementById("user-table");
    userTable.innerHTML = "";

    if (users.length === 0) {
        userTable.innerHTML = "<tr><td colspan='6'>No users found</td></tr>";
        return;
    }

    users.forEach(user => {
        let row = document.createElement("tr");
        row.innerHTML = `
            <td>${user.firstName}</td>
            <td>${user.lastName}</td>
            <td>${user.email}</td>
            <td>${user.number}</td>
            <td>${user.role}</td>
            <td><button onclick="removeUser('${user.username}')">Remove</button></td>
        `;
        userTable.appendChild(row);
    });
}

function removeUser(username) {
    let users = getUsers().filter(user => user.username !== username);
    localStorage.setItem("users", JSON.stringify(users));
    displayUsers();
}

// Ensure the default admin is created on page load
document.addEventListener("DOMContentLoaded", function() {
    displayUsers();
});









// CAREERS

document.getElementById('jobForm').addEventListener('submit', function(event) {
    event.preventDefault();
    alert('Form submitted successfully!');
});