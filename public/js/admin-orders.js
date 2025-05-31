//UPDATE ORDER ACCEPT:DECLINE
document.addEventListener("DOMContentLoaded", function () {
    document.body.addEventListener("click", function (event) {
        if (event.target.classList.contains("accept-btn") || event.target.classList.contains("decline-btn")) {
            let row = event.target.closest("tr"); // Get the order row
            let orderId = row.getAttribute("id"); // Get Order ID
            let isAccepted = event.target.classList.contains("accept-btn"); // Check if Accept button was clicked

            updateOrder(orderId, isAccepted);
        }
    });
});

function updateOrder(orderId, isAccepted) {
    let row = document.getElementById(orderId);
    if (!row) {
        console.error("Row not found:", orderId);
        return;
    }
    
    let orderName = row.querySelector(".item-container").innerText.trim(); // Get item name
    let orderImg = row.querySelector(".item-img").src; // Get item image
    let orderDate = row.querySelector(".order-date").innerText;
    let orderTime = row.querySelector(".order-time").innerText;
    let buttons = row.querySelectorAll("button");

    let status = isAccepted ? "processing" : "cancelled"; // Accept → processing, Decline → cancelled
    let statusMessage = isAccepted ? 
        "Your order has been accepted and is being prepared." : 
        "Your order has been declined. Please contact support for assistance.";

    row.classList.add(isAccepted ? "accepted" : "declined");

    // Disable buttons after action
    buttons.forEach(button => {
        button.disabled = true;
        button.style.opacity = "0.5"; // Visually indicate it's disabled
    });

    // Send status update to server and create notification
    const formData = new FormData();
    formData.append('order_id', orderId);
    formData.append('status', status);
    formData.append('message', statusMessage);
    
    fetch('/controllers/create-notification.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Order status updated and notification created');
            
            // If notifications system is available, refresh notifications
            if (window.NotificationSystem && typeof window.NotificationSystem.checkForNotifications === 'function') {
                window.NotificationSystem.checkForNotifications();
            }
            
            // Show success message
            Swal.fire({
                title: 'Success!',
                text: `Order has been ${isAccepted ? 'accepted' : 'declined'}.`,
                icon: 'success',
                confirmButtonColor: '#2C6E8A'
            });
        } else {
            console.error('Error updating order status:', data.error);
            
            // Show error message
            Swal.fire({
                title: 'Error!',
                text: 'Failed to update order status. Please try again.',
                icon: 'error',
                confirmButtonColor: '#2C6E8A'
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        
        // Show error message
        Swal.fire({
            title: 'Error!',
            text: 'An unexpected error occurred. Please try again.',
            icon: 'error',
            confirmButtonColor: '#2C6E8A'
        });
    });

    // Get stored orders from localStorage
    let orders = JSON.parse(localStorage.getItem("orders")) || [];
    let existingOrderIndex = orders.findIndex(order => order.id === orderId);

    if (existingOrderIndex !== -1) {
        orders[existingOrderIndex].status = status;
    } else {
        orders.push({
            id: orderId,
            name: orderName,
            img: orderImg,
            date: orderDate,
            time: orderTime,
            status: status
        });
    }

    localStorage.setItem("orders", JSON.stringify(orders));

    // Optional: Update reports dynamically
    if (typeof updateReportsTable === "function") {
        updateReportsTable();
    }
}






//REPORTS JAVASCRIPT


document.addEventListener("DOMContentLoaded", function () {
    const menubar = document.querySelectorAll(".menu-bar");

    menubar.forEach(item => {
        item.addEventListener("click", function () {
            // Remove "active" class from all items
            menubar.forEach(el => el.classList.remove("active"));
            
            // Add "active" class to clicked item
            this.classList.add("active");
        });
    });
});











document.addEventListener("DOMContentLoaded", function () {
    loadOrders("all"); // Load all orders initially
});

function loadOrders(filter) {
    let orders = JSON.parse(localStorage.getItem("orders")) || [];
    let reportsTable = document.getElementById("reports-table");

    reportsTable.innerHTML = ""; // Clear table before inserting new rows

    if (orders.length === 0) {
        reportsTable.innerHTML = "<tr><td colspan='5'>No orders available</td></tr>";
        return;
    }

    orders.forEach(order => {
        if (filter === "all" || order.status.toLowerCase() === filter.toLowerCase()) {
            let row = document.createElement("tr");
            row.innerHTML = `
                <td>${order.id}</td>
                <td class="item-container"><img src="${order.img}" class="item-img" width="40"> ${order.name}</td>
                <td>${order.date}</td>
                <td>${order.time}</td>
                <td class="order-status ${order.status.toLowerCase()}">${order.status}</td>
            `;
            reportsTable.appendChild(row);
        }
    });
}


// Function to filter reports based on status
function showOrders(status) {
    loadOrders(status);
}

function addNewOrder(orderId, orderName, orderImg, orderDate, orderTime) {
    let orders = JSON.parse(localStorage.getItem("orders")) || [];

    // Add new order with "Pending" status
    orders.push({
        id: orderId,
        name: orderName,
        img: orderImg,
        date: orderDate,
        time: orderTime,
        status: "Pending"
    });

    localStorage.setItem("orders", JSON.stringify(orders));
}




//ORDERS=======

function openModal(title, desc, imgSrc) {
    console.log("Opening modal with image:", imgSrc); // Debugging step

    document.getElementById("modal-title").innerText = title;
    document.getElementById("modal-desc").innerText = desc;

    // Get the image element
    let imgElement = document.getElementById("modal-img");

    // Update the image source
    imgElement.src = imgSrc;

    // Handle image loading error
    imgElement.onerror = function () {
        console.error("Image failed to load:", imgSrc);
        imgElement.src = "/images/default-placeholder.png"; // Fallback image
    };

    // Display the modal
    document.getElementById("orderModal").style.display = "flex";
}

function closeModal() {
    document.getElementById("orderModal").style.display = "none";
}




// USER PURCHASE REVIEW

document.addEventListener("DOMContentLoaded", function () {
    const overlay = document.getElementById("orderOverlay");
    const openBtn = document.getElementById("openModal");
    const closeBtn = document.querySelector(".close-btn");

    openBtn.addEventListener("click", () => {
        overlay.classList.add("show");
    });

    closeBtn.addEventListener("click", () => {
        overlay.classList.remove("show");
    });

    overlay.addEventListener("click", (e) => {
        if (e.target === overlay) {
            overlay.classList.remove("show");
        }
    });
});
