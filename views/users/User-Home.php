<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Captain's Brew Cafe</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Segoe+UI:wght@400;500&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
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

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        body {
            background: var(--white);
            color: var(--secondary);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        /* Header */
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
            border: 2px solid var(--primary-light);
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

        /* Hero Section */
        .image-container {
            position: relative;
            width: 100%;
            min-height: 650px; 
            display: flex;
            align-items: center;
            justify-content: center;
        }

        #getstarted {
            width: 100%;
            object-fit: cover; 
            position: absolute;
            top: 0;
            left: 0;
            filter: brightness(50%);
            box-shadow: 1px 1px 10px var(--secondary);
            z-index: -1; /* Place image behind the content */
        }

        .centered-home {
            text-align: center;
            color: var(--white);
            max-width: 800px; /* Limit width for readability */
            padding: 2rem; /* Add padding for spacing */
        }

        .centered-home h1 {
            font-size: 4rem; /* Adjusted to match screenshot proportions */
            font-weight: 600;
            margin-bottom: 0.5rem;
            animation: glow 10s ease-in-out infinite alternate;
        }

        .glow h1{
            animation: fadeInUp 1s ease;
        }

        .centered-home h2 {
            font-size: 2.5rem; /* Adjusted for "Cafe" in the screenshot */
            font-weight: 400;
            margin-bottom: 1.5rem;
            text-transform: uppercase;
        }

        .centered-home p {
            font-size: 1.2rem; /* Adjusted to match subtitle size */
            font-weight: 300;
            line-height: 1.6;
            margin-bottom: 2rem;
            opacity: 0.9;
        }

        @keyframes glow {
            from {
                text-shadow: 0 0 10px var(--white), 0 0 1px var(--white), 0 0 10px var(--primary-light), 0 0 20px;
            }
            to {
                text-shadow: 0 0 20px var(--white), 0 0 10px var(--secondary-light), 0 0 30px var(--secondary-light), 0 0 30px;
            }
        }

        .centered-button {
            font-family: 'Poppins', sans-serif;
            background-color: var(--primary);
            color: var(--white);
            font-size: 1.1rem; /* Adjusted to match button size in screenshot */
            font-weight: 500;
            border: none;
            border-radius: var(--border-radius);
            padding: 0.75rem 2rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .centered-button:hover {
            background-color: var(--primary-dark);
            color: var(--white);
            box-shadow: var(--shadow-medium);
            transform: translateY(-2px);
        }
        /* Menu Section */
        .menu-image-container {
            position: relative;
            margin-top: -10vw;
        }

        #gsmenu {
            width: 100%;
            filter: brightness(40%);
        }

        .centered-menu {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            color: var(--white);
        }

        .centered-menu h1 {
            font-size: 3vw;
            font-weight: 400;
        }

        .centered-menu p {
            font-size: 1vw;
            font-style: italic;
            padding: 0.5vw 0;
        }

        /* Carousel */
        .carousel-container {
            background-color: var(--secondary-light);
            width: 100%;
            overflow: hidden;
            position: relative;
            padding: 20px 0;
        }

        .carousel-track {
            display: flex;
            width: max-content;
            animation: scrollCarousel 20s linear infinite;
        }

        .carousel-item {
            width: 30vw;
            margin: 0 30px;
            flex-shrink: 0;
        }

        .carousel-item img {
            width: 100%;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-light);
            transition: var(--transition);
        }

        .carousel-item:hover img {
            transform: scale(1.02);
            box-shadow: var(--shadow-medium);
        }

        @keyframes scrollCarousel {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }

        /* Back to Top Button */
        .back-to-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            background-color: var(--accent);
            color: var(--white);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            opacity: 0;
            visibility: hidden;
            transition: var(--transition);
            z-index: 999;
            box-shadow: var(--shadow-medium);
        }

        .back-to-top.active {
            opacity: 1;
            visibility: visible;
        }

        .back-to-top:hover {
            background-color: var(--secondary);
            transform: translateY(-3px);
        }

        /* Footer */
        .footer {
            background-color: var(--dark);
            color: var(--white);
            padding: 5vw 0;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            font-family: 'Segoe UI', sans-serif;
        }

        .footer-col h3 {
            font-size: 1.3rem;
            margin-bottom: 20px;
            position: relative;
            padding-bottom: 10px;
        }

        .footer-col h3::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 2px;
            background-color: var(--accent);
        }

        .footer-links li {
            margin-bottom: 10px;
            list-style: none;
        }

        .footer-links a {
            color: var(--white);
            text-decoration: none;
            opacity: 0.8;
            transition: var(--transition);
        }

        .footer-links a:hover {
            opacity: 1;
            color: var(--accent);
            padding-left: 5px;
        }

        .contact-info {
            margin-bottom: 20px;
        }

        .contact-info p {
            display: flex;
            align-items: flex-start;
            margin-bottom: 10px;
            opacity: 0.8;
        }

        .contact-info i {
            margin-right: 10px;
            color: var(--accent);
            font-style: normal;
        }

        .social-links {
            display: flex;
            gap: 15px;
        }

        .social-links img {
            width: 20px;
        }

        .social-links a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transition: var(--transition);
        }

        .social-links a:hover {
            background-color: var(--accent);
            transform: translateY(-3px);
        }

        .footer-bottom {
            text-align: center;
            padding-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            opacity: 0.7;
            font-size: 0.9rem;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Responsive Design */
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
                position: static;
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

            .centered-home {
                top: 50%;
            }

            .centered-home h1 {
                font-size: 5vw;
            }

            .centered-home p {
                font-size: 2vw;
                padding: 2vw;
            }

            .centered-button {
                font-size: 4vw;
                padding: 3vw;
                border-radius: 3vw;
            }

            .centered-menu h1 {
                font-size: 5vw;
            }

            .centered-menu p {
                font-size: 2vw;
            }

            .carousel-item {
                width: 60vw;
                margin: 0 15px;
            }

            .social-links img {
                width: 5vw;
            }
        }

        @media (max-width: 480px) {
            #logo {
                height: 40px;
            }

            .nav-button {
                font-size: 5vw;
            }

            .centered-home {
                top: 40%;
            }

            .centered-home h1 {
                font-size: 6vw;
            }

            .centered-home p {
                font-size: 3vw;
            }

            .centered-button {
                font-size: 5vw;
                padding: 3vw;
            }

            .carousel-item {
                width: 80vw;
                margin: 0 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Header Section -->
    <header class="header">
    <img src="/public/images/LOGO.png" id="logo" alt="Captain's Brew Logo">
    <div id="hamburger-menu" class="hamburger">☰</div>
    <nav class="button-container" id="nav-menu">
        <div class="nav-links">
            <a href="/views/users/User-Home.php" class="nav-button active">Home</a>
            <a href="/views/users/User-Menu.php" class="nav-button">Menu</a>
            <a href="/views/users/User-Career.php" class="nav-button">Career</a>
            <a href="/views/users/User-Aboutus.php" class="nav-button">About Us</a>
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
                    echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Guest'; 
                    ?>
                </span>
                <div class="dropdown">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="/views/users/account.php">My Account</a>
                        <a href="/views/users/purchases.php">My Purchase</a>
                        <a class="nav-button" onclick="showLogoutOverlay()">Logout</a>
                    <?php else: ?>
                        <a class="nav-button" onclick="window.location.href='/views/auth/login.php'">Login</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
</header>

    <!-- Hero Section -->
    <div class="image-container">
        <img src="/public/images/background/home.jpg" alt="Get Started" id="getstarted">
        <div class="centered-home">
            <h1 class="glow">Captain's Brew Cafe</h1>
            <p>Where every sip is an adventure. We've got your brew covered.<br>
               Drop anchor, relax, and let your coffee journey begin!</p>
            <button onclick="window.location.href = '/views/users/user-menu.php'" class="centered-button" id="view-menu-button">View Menu</button>
        </div>
    </div>

    <!-- Menu Section -->

    <!-- Carousel -->
    <div class="carousel-container">
        <div class="carousel-track">
            <div class="carousel-item"><img src="/public/images/carousel-img/img-1.png" alt="Slide 1"></div>
            <div class="carousel-item"><img src="/public/images/carousel-img/img-2.png" alt="Slide 2"></div>
            <div class="carousel-item"><img src="/public/images/carousel-img/img-3.png" alt="Slide 3"></div>
            <div class="carousel-item"><img src="/public/images/carousel-img/img-4.png" alt="Slide 4"></div>
            <!-- Duplicate items for seamless scrolling -->
            <div class="carousel-item"><img src="/public/images/carousel-img/img-1.png" alt="Slide 1"></div>
            <div class="carousel-item"><img src="/public/images/carousel-img/img-2.png" alt="Slide 2"></div>
            <div class="carousel-item"><img src="/public/images/carousel-img/img-3.png" alt="Slide 3"></div>
            <div class="carousel-item"><img src="/public/images/carousel-img/img-4.png" alt="Slide 4"></div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-grid">
            <div class="footer-col">
                <h3>Captain's Brew</h3>
                <p style="opacity: 0.8; margin-bottom: 20px;">Where every sip is an adventure. We've got your brew covered.</p>
                <div class="social-links">
                    <a href="#"><img src="/public/images/icons/facebook.png" alt="Facebook"></a>
                    <a href="#"><img src="/public/images/icons/instagram.png" alt="Instagram"></a>
                    <a href="#"><img src="/public/images/icons/twitter.png" alt="Twitter"></a>
                </div>
            </div>
            <div class="footer-col">
                <h3>Quick Links</h3>
                <ul class="footer-links">
                    <li><a href="/index.php">Home</a></li>
                    <li><a href="/views/users/user-menu.php">Menu</a></li>
                    <li><a href="/views/users/user-career.php">Careers</a></li>
                    <li><a href="/views/users/user-aboutus.php">About Us</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h3>Contact Us</h3>
                <div class="contact-info">
                    <p><i>📍</i> 123 Coffee Street, City Name</p>
                    <p><i>📞</i> +1 800 555 6789</p>
                    <p><i>✉️</i> support@captainsbrew.com</p>
                </div>
            </div>
            <div class="footer-col">
                <h3>Opening Hours</h3>
                <div class="contact-info">
                    <p><i>⏰</i> Monday - Friday: 7am - 8pm</p>
                    <p><i>⏰</i> Saturday: 8am - 9pm</p>
                    <p><i>⏰</i> Sunday: 8am - 6pm</p>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>© <?php echo date('Y'); ?> Captain's Brew Cafe. All Rights Reserved.</p>
        </div>
    </footer>

    <!-- Back to Top Button -->
    <div class="back-to-top" id="backToTop">↑</div>

    <!-- JavaScript -->
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

        // Back to top button
        const backToTop = document.getElementById('backToTop');
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
                behavior: 'smooth'
            });
        });
    </script>
    <script src="/public/js/auth.js"></script>
</body>
</html>