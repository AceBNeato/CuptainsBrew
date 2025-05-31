<?php
session_start();
global $conn;
require_once __DIR__ . '/../../config.php';

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
</head>
<body>
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

        


        /* About Us Section */
        .about-us {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            text-align: center;
            padding: 50px 20px;
            background: var(--dark); /* Dark background to match original */
        }

        .container {
            max-width: 1100px;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 40px;
            margin: 0 auto;
            position: relative; /* For back button */
        }

        .back-btn {
            position: absolute;
            display: none;
            top: -2rem;
            left: 1rem;
            font-size: 1.5rem;
            color: var(--accent); /* Changed to accent for visibility on dark background */
            transition: var(--transition);
            align-items: center;
            justify-content: center;
            padding: 0.5rem;
            border-radius: var(--border-radius);
        }

        .back-btn:hover {
            color: var(--white);
            background: var(--primary-dark);
        }

        .about-content {
            flex: 1;
            padding: 20px;
        }

        .about-content h1 {
            font-size: 2.5rem;
            color: aliceblue; /* Match original */
            font-weight: 600;
            margin-bottom: 1rem;
            text-transform: uppercase;
        }

        .about-content h1 span {
            color: var(--accent); /* Match userhome.css */
        }

        .about-content p {
            font-size: 1.125rem;
            color: aliceblue; /* Match original */
            line-height: 1.6;
            margin-top: 15px;
            font-weight: 400;
        }

        .about-content .divider {
            font-size: 1.5rem;
            color: aliceblue; /* Match original */
            margin: 1rem 0;
            font-weight: 400;
        }

        .about-image {
            flex: 1;
            padding: 20px;
        }

        .about-image img {
            width: 100%;
            max-width: 500px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-medium);
            transition: var(--transition);
        }

        .about-image img:hover {
            transform: scale(1.02);
            box-shadow: var(--shadow-dark);
        }

        .about-map {
            flex: 1;
            padding: 20px;
        }

        .about-map iframe {
            width: 100%;
            max-width: 600px;
            height: 450px;
            border: 0;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-medium);
            transition: var(--transition);
        }

        .about-map iframe:hover {
            transform: scale(1.02);
            box-shadow: var(--shadow-dark);
        }

        /* Back to Top Button */
        .back-to-top {
            position: fixed;
            bottom: 90px;
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

        .back-to-top img {
            width: 24px;
            height: 24px;
        }

        /* Footer (from userhome.css) */
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


            .back-btn {
            position: absolute;
            display: flex;
            top: -2rem;
            left: 1rem;
            font-size: 1.5rem;
            color: var(--accent); /* Changed to accent for visibility on dark background */
            transition: var(--transition);
            align-items: center;
            justify-content: center;
            padding: 0.5rem;
            border-radius: var(--border-radius);
        }

        .back-btn:hover {
            color: var(--white);
            background: var(--primary-dark);
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
            

            .about-us {
                padding: 30px 15px;
            }

            .container {
                flex-direction: column;
                gap: 20px;
            }

            .about-content h1 {
                font-size: 2rem;
            }

            .about-content p {
                font-size: 1rem;
            }

            .about-content .divider {
                font-size: 1.2rem;
            }

            .about-image img {
                max-width: 80vw;
            }

            .about-map iframe {
                max-width: 80vw;
                height: 300px;
            }
        }

        @media (max-width: 480px) {
            #logo {
                height: 40px;
            }

            .nav-button {
                font-size: 5vw;
            }

            .about-content h1 {
                font-size: 1.5rem;
            }

            .about-content p {
                font-size: 0.9rem;
            }

            .about-content .divider {
                font-size: 1rem;
            }

            .about-image img {
                max-width: 90vw;
            }

            .about-map iframe {
                max-width: 90vw;
                height: 250px;
            }
        }
    </style>

        <?php require_once __DIR__ . '/partials/header.php'; ?>
    
    <section class="about-us">
        <div class="container">
            <a href="/views/users/user-home.php" class="back-btn" title="Back to Home">
                <span class="back-icon">←</span>
            </a>
            <div class="about-content">
                <h1>About <span>Cuptain's Brew Cafe</span></h1>
                <p>Welcome to Cuptain's Brew Cafe, your cozy retreat for artisanal coffee and handcrafted delights. Nestled in the heart of the city, we are passionate about bringing people together over a cup of perfectly brewed coffee.</p>
                <h1 class="divider">══════════════════════</h1>
                <p>Our journey began with a love for rich flavors and warm conversations. Every cup we serve is made with freshly roasted beans and a touch of love. Whether you're here for a quiet reading session, a casual meet-up, or to savor our signature blends, Cuptain's Brew is your home away from home.</p>
                <p>Join us and experience the magic in every sip.</p>
            </div>
            <div class="about-image">
                <img src="/public/images/background/cbc-opening.jpg" alt="Cuptain's Brew Cafe">
            </div>
            <div class="about-map">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d247.2574719023001!2d125.80107775944143!3d7.452024028726014!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x32f9530005299dc7%3A0x459d7947f646be48!2sCaptain%E2%80%99s%20Brew!5e0!3m2!1sen!2sph!4v1748249923111!5m2!1sen!2sph" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        </div>
    </section>

    <button id="backToTop" class="back-to-top"><img src="/public/images/icons/top-icon.png" alt="Go to Top"></button>

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