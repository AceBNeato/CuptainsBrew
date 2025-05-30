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
        <button class="nav-button <?= $current_page === 'Admin-Orders.php' ? 'active' : '' ?>" onclick="gotoOrders()">Orders</button>
        <button class="nav-button <?= $current_page === 'Admin-Reports.php' ? 'active' : '' ?>" onclick="gotoReports()">Reports</button>
        <button class="nav-button <?= $current_page === 'Admin-Accounts.php' ? 'active' : '' ?>" onclick="gotoAccounts()">Accounts</button>
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
    }
</style>

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
</script>