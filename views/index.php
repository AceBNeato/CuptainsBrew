<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Captain's Brew Cafe</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600&family=Poppins:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        /* Updated Root Variables with User Menu Colors */
        :root {
            --primary: #2C6E8A;
            --primary-dark: #235A73;
            --primary-light: #A9D6E5;
            --secondary: #4a3b2b;
            --secondary-light: #FFFAEE;
            --secondary-lighter: #FFDBB5;
            --accent: #ffb74a;
            --white: #fff;
            --dark: #1a1310;
            --text: #333333;
            --shadow-light: 0 2px 5px rgba(74, 59, 43, 0.2);
            --shadow-medium: 0 4px 8px rgba(44, 110, 138, 0.2);
            --shadow-dark: 0 5px 15px rgba(74, 59, 43, 0.5);
            --border-radius: 10px;
            --transition: all 0.3s ease;
        }
            *{
                margin: 0;
            }
        /* Header - Updated to Match User Menu */
        .header {
            display: flex;
            align-items: center;
            padding: 0.75rem 2rem;
            background: linear-gradient(135deg, var(--secondary-light), var(--secondary-lighter));
            box-shadow: var(--shadow-light);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
        }

        .logo {
            height: 60px;
            margin-right: 3rem;
            transition: var(--transition);
        }

        .logo:hover {
            transform: scale(1.05);
        }

        /* Navigation - Updated to Match User Menu */
        .nav-menu {
            display: flex;
            align-items: center;
            flex: 1;
        }

        .nav-link {
            padding: 0.75rem 1.5rem;
            margin-right: 1rem;
            color: var(--secondary);
            font-weight: 500;
            position: relative;
            transition: var(--transition);
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 2px;
            background: var(--primary);
            transition: var(--transition);
            transform: translateX(-50%);
        }

        .nav-link:hover::after {
            width: 70%;
        }

        .nav-link.active {
            color: var(--primary);
        }

        .nav-link.active::after {
            width: 70%;
        }

        /* Icons and Profile - Updated to Match User Menu */
        .nav-icons {
            margin-left: auto;
            display: flex;
            align-items: center;
        }

        .nav-icon {
            margin-left: 1rem;
            position: relative;
        }

        .nav-icon img {
            width: 24px;
            height: 24px;
            transition: var(--transition);
        }

        .nav-icon:hover img {
            transform: scale(1.1);
        }

        /* Hamburger Menu - Updated */
        .hamburger {
            display: none;
            font-size: 1.5rem;
            cursor: pointer;
            margin-left: auto;
            color: var(--secondary);
        }

        /* Hero Section */
        .hero {
            height: 100vh;
            min-height: 700px;
            background: linear-gradient(rgba(26, 19, 16, 0.7), rgba(26, 19, 16, 0.7)), 
                        url('/public/images/background/getstarted.png') no-repeat center center/cover;
            display: flex;
            align-items: center;
            text-align: center;
            color: var(--light);
            padding-top: 80px;
        }

        .hero-content {
            max-width: 800px;
            margin: 0 auto;
        }

        .hero h1 {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: white;
            font-size: 4.5rem;
            margin-bottom: 20px;
            line-height: 1.2;
            animation: fadeInUp 1s ease;
        }

        .hero p {
            
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: white;
            font-size: 1.2rem;
            margin-bottom: 40px;
            opacity: 0.9;
            animation: fadeInUp 1s ease 0.2s forwards;
            opacity: 0;
        }

        .hero .btn {
            
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: white;
            animation: fadeInUp 1s ease 0.4s forwards;
            opacity: 0;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Menu Preview Section */
        .menu-preview {
            background-color: var(--light);
            position: relative;
        }

        .menu-highlight {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 50px;
        }

        .menu-item {
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: var(--transition);
        }

        .menu-item:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        }

        .menu-item-img {
            height: 250px;
            overflow: hidden;
        }

        .menu-item-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: var(--transition);
        }

        .menu-item:hover .menu-item-img img {
            transform: scale(1.05);
        }

        .menu-item-content {
            padding: 20px;
        }

        .menu-item-content h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: var(--primary);
        }

        .menu-item-content p {
            color: var(--text);
            opacity: 0.8;
            margin-bottom: 15px;
        }

        .menu-item-content .price {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--accent);
        }

        /* Testimonials */
        .testimonials {
            background: linear-gradient(rgba(26, 19, 16, 0.9), rgba(26, 19, 16, 0.9)), 
                        url('/public/images/background/testimonial-bg.jpg') no-repeat center center/cover;
            color: var(--light);
            text-align: center;
        }

        .testimonial-slider {
            max-width: 800px;
            margin: 0 auto;
        }

        .testimonial {
            padding: 0 20px;
        }

        .testimonial-text {
            font-size: 1.2rem;
            font-style: italic;
            margin-bottom: 30px;
            position: relative;
        }

        .testimonial-text::before,
        .testimonial-text::after {
            content: '"';
            font-size: 2rem;
            color: var(--accent);
            opacity: 0.5;
        }

        .testimonial-author {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .author-img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            overflow: hidden;
        }

        .author-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .author-info h4 {
            margin-bottom: 5px;
        }

        .author-info p {
            opacity: 0.7;
            font-size: 0.9rem;
        }

        /* Newsletter */
        .newsletter {
            background-color: var(--primary);
            color: var(--light);
            text-align: center;
            padding: 60px 0;
        }

        .newsletter h2 {
            margin-bottom: 20px;
        }

        .newsletter p {
            max-width: 600px;
            margin: 0 auto 30px;
            opacity: 0.8;
        }

        .newsletter-form {
            display: flex;
            max-width: 500px;
            margin: 0 auto;
        }

        .newsletter-form input {
            flex: 1;
            padding: 15px 20px;
            border: none;
            border-radius: 30px 0 0 30px;
            font-family: inherit;
        }

        .newsletter-form .btn {
            border-radius: 0 30px 30px 0;
            padding: 15px 25px;
        }

        /* Footer */
        .footer {
            background-color: var(--dark);
            color: var(--light);
            padding: 80px 0 30px;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            margin-bottom: 50px;
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
        }

        .social-links {
            display: flex;
            gap: 15px;
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
        }

        /* Back to Top Button */
        .back-to-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            background-color: var(--accent);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            opacity: 0;
            visibility: hidden;
            transition: var(--transition);
            z-index: 999;
            box-shadow: var(--shadow);
        }

        .back-to-top.active {
            opacity: 1;
            visibility: visible;
        }

        .back-to-top:hover {
            background-color: var(--secondary);
            transform: translateY(-3px);
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .hero h1 {
                font-size: 3.5rem;
            }
        }

        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                gap: 20px;
            }

            .nav-menu {
                flex-direction: column;
                gap: 15px;
                display: none;
            }

            .nav-menu.active {
                display: flex;
            }

            .hamburger {
                display: block;
                position: absolute;
                top: 25px;
                right: 20px;
            }

            .hero {
                min-height: 600px;
                padding-top: 120px;
            }

            .hero h1 {
                font-size: 2.5rem;
            }

            .hero p {
                font-size: 1rem;
            }

            .section-title h2 {
                font-size: 2rem;
            }

            .newsletter-form {
                flex-direction: column;
            }

            .newsletter-form input {
                border-radius: 30px;
                margin-bottom: 10px;
            }

            .newsletter-form .btn {
                border-radius: 30px;
                width: 100%;
            }
        }

        @media (max-width: 576px) {
            .hero h1 {
                font-size: 2rem;
            }

            .section-title h2 {
                font-size: 1.8rem;
            }

            .menu-highlight {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container header-container">
            <a href="/">
                <img src="/public/images/LOGO.png" alt="Captain's Brew Cafe" class="logo">
            </a>
            
            <div class="hamburger" id="hamburger">☰</div>
            
            <?php if(isset($_SESSION['user_id'])): ?>
                <div class="nav-icons">
                    <a href="/views/cart.php" class="nav-icon">
                        <img src="/public/images/icons/cart.png" alt="Cart">
                    </a>
                    <a href="/views/profile.php" class="nav-icon">
                        <img src="/public/images/icons/user.png" alt="Profile">
                    </a>
                </div>
            <?php else: ?>
                <div class="nav-menu" id="navMenu">
                    <a href="/views/auth/login.php" class="nav-link">Login</a>
                    <a href="/views/auth/register.php" class="nav-link">Register</a>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container hero-content">
            <h1>Where Every Sip is an Adventure</h1>
            <p>Discover the finest coffee blends at Captain's Brew Cafe. We've got your brew covered. Drop anchor, relax, and let your coffee journey begin!</p>
            <a href="/views/auth/login.php" class="btn btn-primary">Get Started</a>
        </div>
    </section>

    <!-- Menu Preview Section -->
    <section class="menu-preview">
        <div class="container">
            <div class="section-title">
                <h2>Our Signature Brews</h2>
                <p>Explore our handcrafted selection of premium coffees and delicious treats</p>
            </div>
            
            <div class="menu-highlight">
                <div class="menu-item">
                    <div class="menu-item-img">
                        <img src="/public/images/carousel-img/img-1.png" alt="Coffee Blend">
                    </div>
                    <div class="menu-item-content">
                        <h3>Captain's Dark Roast</h3>
                        <p>Our signature dark roast with rich, bold flavors and a smooth finish</p>
                        <div class="price">$4.50</div>
                    </div>
                </div>
                
                <div class="menu-item">
                    <div class="menu-item-img">
                        <img src="/public/images/carousel-img/img-2.png" alt="Coffee Blend">
                    </div>
                    <div class="menu-item-content">
                        <h3>Caramel Wave Latte</h3>
                        <p>Espresso with steamed milk and our homemade caramel sauce</p>
                        <div class="price">$5.25</div>
                    </div>
                </div>
                
                <div class="menu-item">
                    <div class="menu-item-img">
                        <img src="/public/images/carousel-img/img-3.png" alt="Coffee Blend">
                    </div>
                    <div class="menu-item-content">
                        <h3>Sea Salt Mocha</h3>
                        <p>Chocolatey mocha with a hint of sea salt for the perfect balance</p>
                        <div class="price">$5.75</div>
                    </div>
                </div>
            </div>
            
            <div style="text-align: center; margin-top: 50px;">
                <a href="/views/menu.php" class="btn btn-outline" style="color: var(--primary); border-color: var(--primary);">View Full Menu</a>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials">
        <div class="container">
            <div class="section-title">
                <h2>What Our Customers Say</h2>
                <p>Hear from fellow coffee enthusiasts who've experienced Captain's Brew</p>
            </div>
            
            <div class="testimonial-slider">
                <div class="testimonial">
                    <div class="testimonial-text">
                        The best coffee I've had in years! The atmosphere is perfect for both work and relaxation. I come here every morning before work.
                    </div>
                    <div class="testimonial-author">
                        <div class="author-img">
                            <img src="/public/images/testimonials/testimonial-1.jpg" alt="Sarah J.">
                        </div>
                        <div class="author-info">
                            <h4>Sarah J.</h4>
                            <p>Regular Customer</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Newsletter Section -->
    <section class="newsletter">
        <div class="container">
            <h2>Join Our Coffee Club</h2>
            <p>Subscribe to our newsletter and receive exclusive offers, updates on new blends, and special events</p>
            
            <form class="newsletter-form" action="/subscribe.php" method="POST">
                <input type="email" name="email" placeholder="Your email address" required>
                <button type="submit" class="btn btn-primary">Subscribe</button>
            </form>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <h3>Captain's Brew</h3>
                    <p style="opacity: 0.8; margin-bottom: 20px;">Where every sip is an adventure. We've got your brew covered.</p>
                    <div class="social-links">
                        <a href="#"><img src="/public/images/facebook.png" alt="Facebook"></a>
                        <a href="#"><img src="/public/images/instagram.png" alt="Instagram"></a>
                        <a href="#"><img src="/public/images/twitter.png" alt="Twitter"></a>
                    </div>
                </div>
                
                <div class="footer-col">
                    <h3>Quick Links</h3>
                    <ul class="footer-links">
                        <li><a href="/">Home</a></li>
                        <li><a href="/views/menu.php">Menu</a></li>
                        <li><a href="/views/career.php">Careers</a></li>
                        <li><a href="/views/aboutus.php">About Us</a></li>
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
                <p>&copy; <?php echo date('Y'); ?> Captain's Brew Cafe. All Rights Reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Back to Top Button -->
    <div class="back-to-top" id="backToTop">
        ↑
    </div>

    <script>
        // Mobile Navigation Toggle
        const hamburger = document.getElementById('hamburger');
        const navMenu = document.getElementById('navMenu');
        
        hamburger.addEventListener('click', () => {
            navMenu.classList.toggle('active');
        });

        // Header Scroll Effect
        const header = document.querySelector('.header');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 100) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });

        // Back to Top Button
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
</body>
</html>