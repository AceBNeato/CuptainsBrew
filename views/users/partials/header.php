<?php
if (!isset($_SESSION)) {
    session_start();
}

// Clear any potentially problematic session variables if user is not properly logged in
if (!isset($_SESSION['user_id']) && isset($_SESSION['loggedin'])) {
    unset($_SESSION['loggedin']);
}
?>

<header class="header">
    <img src="/public/images/LOGO.png" id="logo" alt="Captain's Brew Logo">
    <div id="hamburger-menu" class="hamburger">☰</div>
    <nav class="button-container" id="nav-menu">
        <div class="nav-links">
            <a href="/views/users/User-Home.php" class="nav-button <?php echo basename($_SERVER['PHP_SELF']) == 'User-Home.php' ? 'active' : ''; ?>">Home</a>
            <a href="/views/users/User-Menu.php" class="nav-button <?php echo basename($_SERVER['PHP_SELF']) == 'User-Menu.php' ? 'active' : ''; ?>">Menu</a>
            <a href="/views/users/User-Career.php" class="nav-button <?php echo basename($_SERVER['PHP_SELF']) == 'User-Career.php' ? 'active' : ''; ?>">Career</a>
            <a href="/views/users/User-Aboutus.php" class="nav-button <?php echo basename($_SERVER['PHP_SELF']) == 'User-Aboutus.php' ? 'active' : ''; ?>">About Us</a>
        </div>
        <div class="icon-profile-container">
            <div class="icon-container">
                <a href="/views/users/cart.php" id="cart-icon" class="nav-icon">
                    <img src="/public/images/icons/cart-icon.png" alt="Cart">
                </a>
            </div>
            <div class="profile">
                <img src="/public/images/icons/profile-icon.png" alt="Profile">
                <span>
                    <?php 
                    echo isset($_SESSION['user_id']) && isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Guest'; 
                    ?>
                </span>
                <div class="dropdown">
                    <?php if (isset($_SESSION['user_id']) && isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
                        <a href="/views/users/User-Account.php">My Account</a>
                        <a href="/views/users/User-Purchase.php">My Purchase</a>
                        <a class="nav-button" onclick="showLogoutOverlay()">Logout</a>
                    <?php else: ?>
                        <a href="/views/auth/login.php">Login</a>
                        <a href="/views/auth/register.php">Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
</header>

<style>
    :root {
        --primary: #2C6E8A;
        --primary-dark: #1B4A5E;
        --primary-light: #B3E0F2;
        --secondary: #4A3B2B;
        --secondary-light: #FFF8E7;
        --secondary-lighter: #FFE8C2;
        --white: #FFFFFF;
        --black: #1A1A1A;
        --accent: #ffb74a;
        --dark: #1a1310;
        --shadow-light: 0 4px 12px rgba(74, 59, 43, 0.15);
        --shadow-medium: 0 6px 16px rgba(44, 110, 138, 0.2);
        --shadow-dark: 0 8px 24px rgba(74, 59, 43, 0.3);
        --border-radius: 12px;
        --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .header {
        display: flex;
        align-items: center;
        padding: 1rem 2rem;
        background: linear-gradient(135deg, var(--secondary-light), var(--secondary-lighter));
        box-shadow: var(--shadow-light);
        position: sticky;
        top: 0;
        z-index: 1000;
    }

    #logo {
        height: 60px;
        margin-right: 2rem;
        transition: var(--transition);
    }

    #logo:hover {
        transform: scale(1.08);
        filter: brightness(1.1);
    }

    .hamburger {
        display: none;
        font-size: 1.75rem;
        cursor: pointer;
        color: var(--secondary);
        transition: var(--transition);
    }

    .hamburger:hover {
        color: var(--primary);
    }

    .button-container {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex: 1;
        gap: 0.5rem;
    }

    .nav-links {
        display: flex;
        gap: 0.5rem;
    }

    .nav-button {
        padding: 0.75rem 1.25rem;
        color: var(--secondary);
        font-weight: 500;
        font-size: 1rem;
        border-radius: 8px;
        transition: var(--transition);
    }

    .nav-button:hover {
        background: var(--primary-light);
        color: var(--primary-dark);
        transform: translateY(-2px);
    }

    .nav-button.active {
        background: var(--primary);
        color: var(--white);
        font-weight: 600;
    }

    .icon-profile-container {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .icon-container {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .nav-icon {
        position: relative;
        transition: var(--transition);
    }

    .nav-icon img {
        width: 28px;
        height: 28px;
        transition: var(--transition);
    }

    .nav-icon:hover img {
        transform: scale(1.15);
        filter: brightness(1.2);
    }

    .profile {
        display: flex;
        align-items: center;
        position: relative;
        cursor: pointer;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        transition: var(--transition);
    }

    .profile:hover {
        background: var(--primary-light);
    }

    .profile img {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        margin-right: 0.75rem;
    }

    .profile span {
        font-size: 0.95rem;
        font-weight: 500;
        color: var(--secondary);
    }

    .profile .dropdown {
        position: absolute;
        top: 100%;
        right: 0;
        background: var(--white);
        min-width: 180px;
        box-shadow: var(--shadow-medium);
        border-radius: var(--border-radius);
        opacity: 0;
        visibility: hidden;
        transform: translateY(10px);
        transition: var(--transition);
        z-index: 100;
        padding: 0.75rem 0;
        margin-top: 0.5rem;
        border: 1px solid var(--primary-light);
    }

    .profile:hover .dropdown,
    .profile:focus-within .dropdown {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .profile .dropdown::before {
        content: '';
        position: absolute;
        top: -8px;
        right: 16px;
        width: 14px;
        height: 14px;
        background: var(--white);
        transform: rotate(45deg);
        border-top: 1px solid var(--primary-light);
        border-left: 1px solid var(--primary-light);
        box-shadow: -2px -2px 4px rgba(0, 0, 0, 0.05);
    }

    .profile .dropdown a,
    .profile .dropdown button {
        display: block;
        padding: 0.75rem 1.25rem;
        color: var(--secondary);
        font-size: 0.95rem;
        transition: var(--transition);
    }

    .profile .dropdown a:hover,
    .profile .dropdown button:hover {
        background: var(--primary-light);
        color: var(--primary-dark);
    }

    .login-button {
        background: var(--primary);
        color: var(--white);
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        font-weight: 600;
        transition: var(--transition);
    }

    .login-button:hover {
        background: var(--primary-dark);
        transform: translateY(-2px);
        box-shadow: var(--shadow-medium);
    }

    @media (max-width: 768px) {
        .header {
            padding: 0.75rem 1rem;
        }

        #logo {
            height: 48px;
            margin-right: 1rem;
        }

        .hamburger {
            display: block;
            margin-left: auto;
        }

        .button-container {
            position: fixed;
            top: 64px;
            left: 0;
            width: 100%;
            flex-direction: column;
            background: var(--white);
            box-shadow: var(--shadow-medium);
            padding: 1rem 0;
            transform: translateY(-100%);
            opacity: 0;
            visibility: hidden;
            transition: var(--transition);
            z-index: 999;
            align-items: flex-start;
        }

        .button-container.active {
            transform: translateY(0);
            opacity: 1;
            visibility: visible;
        }

        .nav-links {
            width: 100%;
            flex-direction: column;
        }

        .nav-button {
            width: 100%;
            padding: 0.75rem 1.5rem;
            margin: 0.25rem 0;
            text-align: left;
        }

        .icon-profile-container {
            width: 100%;
            justify-content: flex-end;
            padding: 0 1.5rem;
            margin: 0.5rem 0;
        }

        .profile {
            width: 100%;
            padding: 0.75rem 1.5rem;
            margin: 0;
            justify-content: space-between;
        }

        .profile .dropdown {
            width: 100%;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
            opacity: 1;
            visibility: visible;
            transform: none;
            box-shadow: none;
            margin: 0;
            padding: 0;
            border: none;
        }

        .profile.active .dropdown {
            max-height: 300px;
        }

        .profile .dropdown::before {
            display: none;
        }

        .profile .dropdown a,
        .profile .dropdown button {
            padding: 0.75rem 2.5rem;
        }
    }

    @media (max-width: 480px) {
        #logo {
            height: 40px;
        }

        .nav-button {
            font-size: 5vw;
        }
    }
</style>

<script>
    // Hamburger menu toggle
    document.getElementById('hamburger-menu').addEventListener('click', function() {
        document.getElementById('nav-menu').classList.toggle('active');
    });

    // Profile dropdown toggle for mobile
    const profile = document.querySelector('.profile');
    if (profile) {
        profile.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                this.classList.toggle('active');
            }
        });
    }
</script>