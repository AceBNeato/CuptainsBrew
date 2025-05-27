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
            z-index: -1;
        }

        .centered-home {
            text-align: center;
            color: var(--white);
            max-width: 800px;
            padding: 2rem;
        }

        .centered-home h1 {
            font-size: 4rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            animation: glow 10s ease-in-out infinite alternate;
        }

        .glow h1 {
            animation: fadeInUp 1s ease;
        }

        .centered-home h2 {
            font-size: 2.5rem;
            font-weight: 400;
            margin-bottom: 1.5rem;
            text-transform: uppercase;
        }

        .centered-home p {
            font-size: 1.2rem;
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
            font-size: 1.1rem;
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

        @media (max-width: 768px) {
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
    <?php require_once __DIR__ . '/partials/header.php'; ?>

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
    <div class="menu-image-container">
        <img src="/public/images/background/menu.jpg" alt="Menu" id="gsmenu">
        <div class="centered-menu">
            <h1>Explore Our Menu</h1>
            <p>Indulge in our curated selection of beverages and treats.</p>
            <button onclick="window.location.href = '/views/users/user-menu.php'" class="centered-button">Discover Now</button>
        </div>
    </div>

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
                    <li><a href="/views/users/user-career.php">Careers</li>
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