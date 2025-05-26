document.addEventListener("keydown", function (event) {
    // Detect "Ctrl + Shift + S"
    if (event.ctrlKey && event.shiftKey && event.key.toLowerCase() === "q") {
        console.log("Admin shortcut detected!");
        
        localStorage.setItem("userRole", "admin");

        alert("Admin logged in via secret shortcut!");

        window.location.href = "/views/auth/admin-login.php";
    } 
});

document.addEventListener("keydown", function (event) {
if (event.ctrlKey && event.shiftKey && event.key.toLowerCase() === "s") {
    console.log("User shortcut detected!");
    
    localStorage.setItem("userRole", "user");

    alert("Going back to users");

    window.location.href = "/views/auth/login.php";
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

            loadingOverlay.style.display = "flex";

            setTimeout(() => {
                if (email === "admin@usep.edu.ph" && password === "admin123") {
                   
                    sessionStorage.setItem("userRole", "admin");
                    localStorage.setItem("userLoggedIn", "true");

                    
                    window.location.href = "/views/admin/Admin-Menu.php";
                } else {
                   
                        Swal.fire({
                            icon: "error",
                            title: "Oops...",
                            text: "Invalid email or password! Please Try Again!",
                        }); 

                    loadingOverlay.style.display = "none"; // Hide loading overlay on failed login
                }
            }, 1000); // Adjust the delay as needed
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
    Swal.fire({
        title: 'Are you sure?',
        text: 'Do you want to log out?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#2C6E8A',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, log out',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '/views/auth/logout.php';
        }
    });
}

function logout() {
    const userRole = sessionStorage.getItem("userRole");

    sessionStorage.removeItem("userRole");
    localStorage.removeItem("userLoggedIn");

    if (userRole === "admin") {
        window.location.href = "/views/index.php"; 
    } else {
        window.location.href = "/views/index.php"; 
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