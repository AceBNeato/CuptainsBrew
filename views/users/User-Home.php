<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Captain's Brew Cafe</title>
    <style>
        /* Global Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #1a1310;
            color: #FFFAEE;
        }

        /* Header */
        .header {
            position: sticky;
            top: 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 20px;
            background: linear-gradient(135deg, #FFFAEE, #FFDBB5);
            box-shadow: 0 1px 10px #D7BFA5;
            z-index: 1000;
        }

        #logo {
            width: 15vw;
            transition: transform 0.3s;
        }

        #logo:hover {
            transform: scale(1.1);
        }

        /* Navigation */
        .button-container {
            display: flex;
            gap: 2vw;
            align-items: center;
        }

        .nav-button {
            padding: 10px 20px;
            text-decoration: none;
            color: #333;
            font-size: 18px;
            border-radius: 5px;
            transition: background-color 0.3s, color 0.3s;
        }

        .nav-button:hover {
            background-color: #D7BFA5;
            color: #4a3b2b;
        }

        .icon-container {
            display: flex;
            gap: 10px;
        }

        .nav-icon img {
            width: 2vw;
            transition: transform 0.3s;
        }

        .nav-icon img:hover {
            transform: scale(1.1);
        }

        .hidden {
            display: none;
        }

        /* Hamburger Menu */
        #hamburger-menu {
            display: none;
            font-size: 30px;
            cursor: pointer;
            color: #333;
        }

        /* Hero Section */
        .image-container {
            position: relative;
            width: 100%;
        }

        #getstarted {
            width: 100%;
            filter: brightness(40%);
            box-shadow: 1px 1px 10px #4a3b2b;
        }

        .centered-home {
            position: absolute;
            top: 35%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            color: #FFFAEE;
        }

        .centered-home h1 {
            font-size: 5vw;
            font-weight: 400;
            animation: glow 10s ease-in-out infinite alternate;
        }

        .centered-home p {
            font-size: 1vw;
            font-style: italic;
            padding: 0.5vw 0 2vw;
        }

        @keyframes glow {
            from {
                text-shadow: 0 0 10px #fff, 0 0 1px #fff, 0 0 10px #C0834C, 0 0 20px;
            }
            to {
                text-shadow: 0 0 20px #fff, 0 0 10px #FFFAEE, 0 0 30px #FFFAEE, 0 0 30px;
            }
        }

        .centered-button {
            font-family: 'Trebuchet MS', 'Lucida Sans Unicode', 'Lucida Grande', 'Lucida Sans', Arial, sans-serif;
            background-color: #ffb74a;
            color: #ffffff;
            font-size: 2vw;
            border: none;
            border-radius: 1vw;
            padding: 1vw 2vw;
            cursor: pointer;
            transition: background-color 0.2s, color 0.2s, box-shadow 0.2s;
        }

        .centered-button:hover {
            background-color: #ffffff;
            color: #ffb74a;
            box-shadow: 1px 1px 10px #ffffff;
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
            color: #FFFAEE;
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
            background-color: antiquewhite;
            width: 100%;
            overflow: hidden;
            position: relative;
            padding: 20px 0;
        }

        .carousel-divider {
            color: white;
            font-size: 1.5rem;
            text-align: center;
            margin: 10px 0;
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
            border-radius: 10px;
            box-shadow: 1px 1px 10px #4a3b2b;
            transition: transform 0.3s;
        }

        .carousel-item:hover img {
            transform: scale(1.02);
            box-shadow: 1px 1px 10px #f3f3f3;
        }

        @keyframes scrollCarousel {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }

        /* Back to Top Button */
        #back-to-top {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 50px;
            height: 50px;
            background-color: #4a3b2b;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            display: none;
            justify-content: center;
            align-items: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
            transition: opacity 0.3s, transform 0.3s;
        }

        #back-to-top img {
            width: 30px;
            height: 30px;
        }

        #back-to-top:hover {
            background-color: #D7BFA5;
            transform: scale(1.1);
        }

        .show {
            display: flex !important;
            opacity: 1;
        }

        /* Footer */
        footer {
            background-color: #1a1310;
            color: #fff;
            padding: 40px 0;
            font-family: Arial, sans-serif;
        }

        .footer-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-around;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .footer-left {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .footer-links ul {
            list-style: none;
            padding: 0;
            display: flex;
            gap: 15px;
        }

        .footer-links ul li a {
            color: #fff;
            text-decoration: none;
            font-size: 16px;
            transition: color 0.3s;
        }

        .footer-links ul li a:hover {
            color: #c49a6c;
        }

        .footer-social {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }

        .footer-social a img {
            width: 2vw;
            transition: transform 0.3s;
        }

        .footer-social a img:hover {
            transform: scale(1.1);
        }

        .footer-right {
            text-align: center;
        }

        .footer-contact h3 {
            font-size: 18px;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .footer-contact p {
            font-size: 14px;
            margin: 5px 0;
        }

        .footer-bottom {
            margin-top: 20px;
            font-size: 14px;
            text-align: center;
            opacity: 0.7;
        }

        /* Responsive Design */
        @media screen and (max-width: 768px) {
            .header {
                flex-direction: column;
                padding: 15px;
            }

            #logo {
                width: 40vw;
            }

            .button-container {
                display: none;
                flex-direction: column;
                gap: 2vw;
                width: 100%;
                position: absolute;
                top: 100%;
                left: 0;
                background-color: #FFFAEE;
                box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
                padding: 10px 0;
            }

            .button-container.active {
                display: flex;
            }

            #hamburger-menu {
                display: block;
                position: absolute;
                top: 15px;
                left: 15px;
            }

            .nav-button {
                padding: 15px;
                text-align: center;
                font-size: 4vw;
            }

            .nav-icon img {
                width: 5vw;
            }

            .centered-home {
                top: 50%;
                font-size: 5vw;
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

            .footer-social a img {
                width: 5vw;
            }
        }

        @media screen and (max-width: 480px) {
            #logo {
                width: 50vw;
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
    <header class="header">
        <div id="hamburger-menu" class="hamburger">☰</div>
        <img src="/public/images/LOGO.png" id="logo" alt="Captain's Brew Cafe Logo">
        <nav class="button-container" id="nav-menu">
            <a href="/views/home.html" class="nav-button">Home</a>
            <a href="/views/users/User-Menu.php" class="nav-button">Menu</a>
            <a href="/views/users/User-Menu.php" class="nav-button">Career</a>
            <a href="/views/users/User-Menu.php" class="nav-button">About Us</a>
            <div class="icon-container">
                <a href="/views/users/cart.php" id="cart-icon" class="nav-icon hidden">
                    <img src="/images/cart-icon.png" alt="Cart">
                </a>
                <a href="/views/users/profile.php" id="profile-icon" class="nav-icon hidden">
                    <img src="/images/profile-icon.png" alt="Profile">
                </a>
            </div>
        </nav>
    </header>

    <div class="image-container">
        <img src="/public/images/background/getstarted.png" alt="Get Started" id="getstarted">
        <div class="centered-home">
            <h1 class="glow">Captain's Brew Cafe</h1>
            <p>Where every sip is an adventure. We've got your brew covered.<br>
               Drop anchor, relax, and let your coffee journey begin!</p>
            <button onclick="window.location.href = '/views/users/User-Menu.php'" class="centered-button" id="view-menu-button">View Menu</button>
        </div>
    </div>


    <div class="carousel-container">
        <div class="carousel-track">
            <div class="carousel-item"><img src="/public/images/carousel-img/img-1.png" alt="Slide 1"></div>
            <div class="carousel-item"><img src="/public/images/carousel-img/img-2.png" alt="Slide 2"></div>
            <div class="carousel-item"><img src="/public/images/carousel-img/img-3.png" alt="Slide 3"></div>
            <div class="carousel-item"><img src="/public/images/carousel-img/img-4.png" alt="Slide 4"></div>
        </div>
        </div>

    <button id="back-to-top"><img src="/images/top-icon.png" alt="Go to Top"></button>

    <footer>
        <div class="footer-container">
            <div class="footer-left">
                <div class="footer-links">
                    <ul>
                        <li><a href="/views/home.html">Home</a></li>
                        <li><a href="/views/aboutus.html">About Us</a></li>
                    </ul>
                </div>
                <div class="footer-social">
                    <a href="#"><img src="/images/facebook.png" alt="Facebook"></a>
                    <a href="#"><img src="/images/twitter.png" alt="Twitter"></a>
                    <a href="#"><img src="/images/instagram.png" alt="Instagram"></a>
                </div>
            </div>
            <div class="footer-right">
                <div class="footer-contact">
                    <h3>CONTACT US</h3>
                    <p>123 Coffee Street, City Name</p>
                    <p><strong>Phone:</strong> +1 800 555 6789</p>
                    <p><strong>E-mail:</strong> support@captainsbrew.com</p>
                    <p><strong>Website:</strong> www.captainsbrew.com</p>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>© Copyright 2025 Captain's Brew Cafe. All Rights Reserved.</p>
        </div>
    </footer>

    <script src="/public/js/auth.js"></script>
    <script src="/public/js/script.js"></script>
    <script>
        // Hamburger menu toggle
        document.getElementById('hamburger-menu').addEventListener('click', function() {
            document.getElementById('nav-menu').classList.toggle('active');
        });

        // Back to top button visibility
        window.addEventListener('scroll', function() {
            const backToTop = document.getElementById('back-to-top');
            if (window.scrollY > 300) {
                backToTop.classList.add('show');
            } else {
                backToTop.classList.remove('show');
            }
        });

        // Smooth scroll to top
        document.getElementById('back-to-top').addEventListener('click', function() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    </script>
</body>
</html>