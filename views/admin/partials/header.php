<?php
// Ensure session is started (safe to call if already started)
if (!isset($_SESSION)) {
    session_start();
}

// Determine current page for active nav button
$current_page = basename($_SERVER['PHP_SELF']);
?>

<header class="header">
    <div class="logo-section">
        <img src="/public/images/LOGO.png" id="logo" alt="Captain's Brew Cafe Logo" />
    </div>
    <nav class="nav-menu" id="nav-menu">
        <button class="nav-button <?= $current_page === 'Admin-Menu.php' ? 'active' : '' ?>" onclick="gotoMenu()">Menu</button>
        <div class="nav-button-with-notification">
            <button class="nav-button <?= $current_page === 'Admin-Orders.php' ? 'active' : '' ?>" onclick="gotoOrders()">Orders</button>
            <span class="order-notification-badge" id="order-notification-badge"></span>
        </div>
        <button class="nav-button <?= $current_page === 'Admin-Reports.php' ? 'active' : '' ?>" onclick="gotoReports()">Reports</button>
        <button class="nav-button <?= $current_page === 'Admin-Accounts.php' ? 'active' : '' ?>" onclick="gotoAccounts()">Accounts</button>
        <button class="nav-button <?= $current_page === 'Admin-Career.php' ? 'active' : '' ?>" onclick="gotoCareer()">Career</button>
        
        <!-- Notification Bell -->
        <div class="notification-container">
            <div class="notification-bell" id="notification-bell">
                <i class="fas fa-bell"></i>
                <span class="notification-counter" id="notification-counter"></span>
            </div>
            <div class="notification-dropdown" id="notification-dropdown">
                <div class="notification-header">
                    <h3>Notifications</h3>
                </div>
                <ul class="notification-list" id="notification-list">
                    <li class="no-notifications">No notifications</li>
                </ul>
            </div>
        </div>
        
        <button class="nav-button" onclick="showLogoutOverlay()">Logout</button>
    </nav>
</header>

<style>
    /* Header Styles */
    .header {
        display: flex;
        align-items: center;
        padding: 1rem 2rem;
        background: linear-gradient(135deg, #FFFAEE, #FFDBB5);
        box-shadow: 0 2px 5px rgba(74, 59, 43, 0.3);
        position: sticky;
        top: 0;
        z-index: 1000;
    }

    .logo-section img {
        width: 200px;
        margin: 0 100px;
        transition: transform 0.3s;
    }

    .logo-section img:hover {
        transform: scale(1.1);
    }

    .nav-menu {
        display: flex;
        gap: 3rem;
        align-items: center;
    }

    .nav-button {
        background: none;
        border: none;
        color: #4a3b2b;
        font-size: 1rem;
        padding: 1rem 2rem;
        cursor: pointer;
        border-radius: 10px;
        transition: all 0.3s;
    }

    .nav-button:hover, .nav-button.active {
        background-color: #2C6E8A;
        color: #fff;
    }
    
    /* Notification Badge for Orders Button */
    .nav-button-with-notification {
        position: relative;
    }
    
    .order-notification-badge {
        position: absolute;
        top: 0;
        right: 0;
        background-color: #ef4444;
        color: white;
        border-radius: 50%;
        font-size: 0.7rem;
        font-weight: 600;
        min-width: 18px;
        height: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0 4px;
        transform: scale(0);
        transition: transform 0.2s ease;
    }
    
    .order-notification-badge.active {
        transform: scale(1);
    }

    /* Mobile Responsiveness */
    @media (max-width: 768px) {
        .header {
            flex-direction: column;
            padding: 2vw;
            text-align: center;
        }

        .logo-section img {
            width: 40vw;
            margin: 0 0 2vw 0;
        }

        .nav-menu {
            flex-direction: column;
            gap: 1vw;
            width: 100%;
        }

        .nav-button {
            padding: 1vw;
            width: 100%;
            font-size: 3vw;
        }
        
        .notification-container {
            margin: 1vw 0;
        }
    }
</style>

<!-- Link to Font Awesome for bell icon -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<!-- Link to notification CSS -->
<link rel="stylesheet" href="/public/css/notifications.css">

<script>
    function gotoMenu() {
        window.location.href = '/views/admin/Admin-Menu.php';
    }

    function gotoOrders() {
        window.location.href = '/views/admin/Admin-Orders.php';
    }

    function gotoReports() {
        window.location.href = '/views/admin/Admin-Reports.php';
    }

    function gotoAccounts() {
        window.location.href = '/views/admin/Admin-Accounts.php';
    }

    function gotoCareer() {
        window.location.href = '/views/admin/Admin-Career.php';
    }

    function showLogoutOverlay() {
        Swal.fire({
            title: 'Logout Confirmation',
            text: 'Are you sure you want to logout?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#2C6E8A',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, logout'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '/views/auth/logout.php';
            }
        });
    }
    
    // Update the order notification badge with the same count as notifications
    document.addEventListener('DOMContentLoaded', function() {
        // Check if notifications.js is loaded
        if (typeof window.NotificationSystem !== 'undefined') {
            // Override the updateNotificationCounter function to also update the order badge
            const originalUpdateCounter = window.NotificationSystem.updateNotificationCounter;
            
            if (originalUpdateCounter) {
                window.NotificationSystem.updateNotificationCounter = function(unreadCount) {
                    // Call the original function if it exists
                    if (typeof originalUpdateCounter === 'function') {
                        originalUpdateCounter(unreadCount);
                    }
                    
                    // Update the order badge
                    const orderBadge = document.getElementById('order-notification-badge');
                    if (orderBadge && unreadCount > 0) {
                        orderBadge.textContent = unreadCount > 99 ? '99+' : unreadCount;
                        orderBadge.classList.add('active');
                    } else if (orderBadge) {
                        orderBadge.textContent = '';
                        orderBadge.classList.remove('active');
                    }
                };
            }
        }
    });
</script>

<!-- Include the notifications.js script -->
<script src="/public/js/notifications.js"></script>

<!-- Include performance controls at the end of the header -->
<?php include_once __DIR__ . '/../../partials/performance-controls.php'; ?>