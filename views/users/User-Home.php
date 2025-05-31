<?php
session_start();

// Clear any potentially problematic session variables if user is not properly logged in
if (!isset($_SESSION['user_id']) && isset($_SESSION['loggedin'])) {
    unset($_SESSION['loggedin']);
}
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
            min-height: 700px; 
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

        /* Featured Section */
        .featured-section {
            padding: 4rem 2rem;
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('/public/images/background/home1.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            position: relative;
        }

        .featured-container {
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        .featured-title {
            text-align: center;
            margin-bottom: 3rem;
        }

        .featured-title h2 {
            font-size: 2.5rem;
            color: var(--white);
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .featured-title p {
            color: var(--secondary-lighter);
            font-size: 1.1rem;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
        }

        .featured-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }

        .featured-item {
            background: rgba(255, 255, 255, 0.95);
            padding: 2rem;
            border-radius: var(--border-radius);
            text-align: center;
            transition: var(--transition);
            box-shadow: var(--shadow-light);
            backdrop-filter: blur(5px);
        }

        .featured-item:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-medium);
            background: rgba(255, 255, 255, 0.98);
        }

        .featured-icon {
            width: 60px;
            height: 60px;
            margin: 0 auto 1rem;
            background: var(--primary-light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .featured-icon img {
            width: 30px;
            height: 30px;
        }

        .featured-item h3 {
            color: var(--primary);
            font-size: 1.3rem;
            margin-bottom: 0.5rem;
        }

        .featured-item p {
            color: var(--secondary);
            font-size: 0.95rem;
            line-height: 1.6;
        }

        @media (max-width: 768px) {
            .featured-section {
                padding: 3rem 1rem;
            }

            .featured-title h2 {
                font-size: 2rem;
            }

            .featured-grid {
                gap: 1.5rem;
            }
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

        /* Carousel Section */
        .carousel-section {
            padding: 4rem 2rem;
            background-color: var(--secondary-light);
        }

        .carousel-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .carousel-title {
            font-size: 2.5rem;
            color: var(--primary-dark);
            margin-bottom: 1rem;
            position: relative;
            display: inline-block;
        }

        .carousel-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: var(--primary);
            border-radius: 4px;
        }

        .carousel-subtitle {
            color: var(--secondary);
            font-size: 1.1rem;
        }

        .carousel-container {
            width: 100%;
            overflow: hidden;
            position: relative;
            padding: 20px 0;
        }

        .carousel-track {
            display: flex;
            width: max-content;
            animation: scrollCarousel 30s linear infinite;
        }

        .carousel-track:hover {
            animation-play-state: paused;
        }

        .carousel-item {
            width: 300px;
            margin: 0 20px;
            flex-shrink: 0;
        }

        .carousel-item img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-light);
            transition: var(--transition);
        }

        .carousel-item:hover img {
            transform: scale(1.05);
            box-shadow: var(--shadow-medium);
        }

        @keyframes scrollCarousel {
            0% { transform: translateX(0); }
            100% { transform: translateX(calc(-320px * 4)); } /* Width + margin of items * number of unique items */
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
            .image-container{
            min-height: 430px;
            border-bottom: 5px solid #1a1310;
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

        /* Popular Picks Section */
        .popular-picks {
            padding: 4rem 2rem;
            background-color: var(--secondary-light);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .popular-picks-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .popular-picks-title {
            font-size: 2.5rem;
            color: var(--primary-dark);
            margin-bottom: 1rem;
            position: relative;
            display: inline-block;
        }
        
        .popular-picks-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: var(--primary);
            border-radius: 4px;
        }
        
        .popular-picks-subtitle {
            color: var(--secondary);
            font-size: 1.1rem;
        }
        
        .popular-picks-grid {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        /* Menu Card Styles for Popular Picks */
        .menu-card {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-light);
            overflow: hidden;
            transition: var(--transition);
            display: flex;
            flex-direction: row;
            position: relative;
            margin: 0 1.5rem 1.5rem 1.5rem;
            align-items: stretch;
            min-height: 180px;
        }
        
        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-medium);
        }
        
        .menu-card-image {
            flex-shrink: 0;
            width: 180px;
            height: 180px;
            overflow: hidden;
            position: relative;
        }
        
        .menu-card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: var(--transition);
        }
        
        .menu-card:hover .menu-card-image img {
            transform: scale(1.05);
        }
        
        .menu-card-content {
            padding: 1.5rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        
        .menu-card-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 0.5rem;
        }
        
        .menu-card-description {
            font-size: 0.95rem;
            color: var(--secondary);
            opacity: 0.8;
            margin-bottom: 1rem;
            line-height: 1.5;
        }
        
        .menu-card-rating {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0.5rem 0 1rem;
        }
        
        .menu-card-stars {
            color: #ffb74a;
            font-size: 0.9rem;
        }
        
        .menu-card-review-count {
            font-size: 0.8rem;
            color: #666;
        }
        
        .menu-card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
        }
        
        .menu-card-price {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--secondary);
        }
        
        .menu-card-btn {
            background: transparent;
            border: 1px solid var(--primary);
            color: var(--primary);
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .menu-card-btn:hover {
            background: var(--primary-light);
            color: var(--primary-dark);
        }
        
        .hot-label {
            position: absolute;
            top: 10px;
            left: 10px;
            background-color: #f44336;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
            z-index: 1;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        
        .loading-spinner {
            text-align: center;
            padding: 2rem;
            color: var(--primary);
            font-size: 1.2rem;
        }
        
        .loading-spinner i {
            margin-right: 0.5rem;
            font-size: 1.5rem;
        }
        
        .no-items {
            text-align: center;
            padding: 2.5rem;
            color: var(--secondary);
            font-size: 1.2rem;
            font-weight: 500;
            background: var(--white);
            border-radius: var(--border-radius);
        }
        
        .error-message {
            background: #FFEBEE;
            color: #D32F2F;
            padding: 1rem;
            border-radius: var(--border-radius);
            margin: 1.5rem 0;
            text-align: center;
            font-weight: 500;
        }
        
        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .menu-card {
                flex-direction: column;
                margin: 0 0 1.5rem 0;
            }
            
            .menu-card-image {
                width: 100%;
                height: 200px;
            }
            
            .popular-picks {
                padding: 3rem 1rem;
            }
            
            .popular-picks-title {
                font-size: 2rem;
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
            <p>Coffee makes everything smooth sailing ⛵️
                <br>
               Drop anchor, relax, and let your coffee journey begin!</p>
            <button onclick="window.location.href = '/views/users/User-Menu.php'" class="centered-button" id="view-menu-button">View Menu</button>
        </div>
    </div>

    <!-- Featured Section -->
    <section class="featured-section">
        <div class="featured-container">
            <div class="featured-title">
                <h2>What Makes Us Special</h2>
                <p>Experience the Captain's Brew difference</p>
            </div>
            <div class="featured-grid">
                <div class="featured-item">
                    <i class="fas fa-coffee"></i>
                    <h3>Premium Coffee</h3>
                    <p>We source the finest beans from around the world, roasted to perfection for a rich, aromatic experience.</p>
                </div>
                <div class="featured-item">
                    <i class="fas fa-utensils"></i>
                    <h3>Fresh Food</h3>
                    <p>Our menu features locally-sourced ingredients, prepared fresh daily for maximum flavor and quality.</p>
                </div>
                <div class="featured-item">
                    <i class="fas fa-heart"></i>
                    <h3>Cozy Atmosphere</h3>
                    <p>Relax in our warm, inviting space designed for comfort, conversation, and connection.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Popular Picks Section -->
    <div class="popular-picks">
        <div class="container">
            <div class="popular-picks-header">
                <h2 class="popular-picks-title">Popular Picks</h2>
                <p class="popular-picks-subtitle">Our most ordered and highest-rated items</p>
            </div>
            <div class="popular-picks-grid" id="popular-items-container">
                <!-- Popular items will be loaded here via JavaScript -->
                <div class="loading-spinner">
                    <i class="fas fa-spinner fa-spin"></i>
                    <span>Loading popular items...</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Carousel Section -->
    <div class="carousel-section">
        <div class="container">
            <div class="carousel-header">
                <h2 class="carousel-title">Our Coffee Gallery</h2>
                <p class="carousel-subtitle">Take a visual tour of our delightful offerings</p>
            </div>
        </div>
        <div class="carousel-container">
            <div class="carousel-track">
                <!-- First set of images -->
                <div class="carousel-item"><img src="/public/images/carousel-img/img-1.png" alt="Slide 1"></div>
                <div class="carousel-item"><img src="/public/images/carousel-img/img-2.png" alt="Slide 2"></div>
                <div class="carousel-item"><img src="/public/images/carousel-img/img-3.png" alt="Slide 3"></div>
                <div class="carousel-item"><img src="/public/images/carousel-img/img-4.png" alt="Slide 4"></div>
                
                <!-- Duplicate set for seamless loop -->
                <div class="carousel-item"><img src="/public/images/carousel-img/img-1.png" alt="Slide 1"></div>
                <div class="carousel-item"><img src="/public/images/carousel-img/img-2.png" alt="Slide 2"></div>
                <div class="carousel-item"><img src="/public/images/carousel-img/img-3.png" alt="Slide 3"></div>
                <div class="carousel-item"><img src="/public/images/carousel-img/img-4.png" alt="Slide 4"></div>
                
                <!-- Third set for extra smoothness -->
                <div class="carousel-item"><img src="/public/images/carousel-img/img-1.png" alt="Slide 1"></div>
                <div class="carousel-item"><img src="/public/images/carousel-img/img-2.png" alt="Slide 2"></div>
                <div class="carousel-item"><img src="/public/images/carousel-img/img-3.png" alt="Slide 3"></div>
                <div class="carousel-item"><img src="/public/images/carousel-img/img-4.png" alt="Slide 4"></div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
    <?php include '../partials/footer.php'; ?>
    </footer>

    <!-- Back to Top Button -->
    <div class="back-to-top" id="backToTop">↑</div>

    <!-- Review Modal -->
    <div id="review-modal" class="review-modal">
        <div class="review-modal-content">
            <span class="close-review-modal">&times;</span>
            <h3 class="review-modal-title">Write a Review</h3>
            <p class="review-item-name" id="review-item-name"></p>
            
            <form id="review-form">
                <input type="hidden" id="review-item-id" name="item_id">
                <input type="hidden" id="rating" name="rating">
                
                <div class="rating-container">
                    <label class="rating-label">Your Rating:</label>
                    <div class="rating-stars">
                        <i class="fas fa-star rating-star" data-rating="1"></i>
                        <i class="fas fa-star rating-star" data-rating="2"></i>
                        <i class="fas fa-star rating-star" data-rating="3"></i>
                        <i class="fas fa-star rating-star" data-rating="4"></i>
                        <i class="fas fa-star rating-star" data-rating="5"></i>
                    </div>
                </div>
                
                <div class="review-form-group">
                    <label class="review-form-label" for="comment">Your Review (optional):</label>
                    <textarea id="comment" name="comment" class="review-form-textarea" placeholder="Share your experience with this item..."></textarea>
                </div>
                
                <button type="submit" class="review-submit-btn">Submit Review</button>
            </form>
        </div>
    </div>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Reviews CSS -->
    <link rel="stylesheet" href="/public/css/reviews.css">

    <!-- JavaScript -->
    <script>
        // Hamburger menu toggle
        document.getElementById('hamburger-menu').addEventListener('click', function() {
            document.getElementById('nav-menu').classList.toggle('active');
        });

        // Profile dropdown toggle for mobile
        document.querySelector('.profile').addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                this.classList.toggle('active');
            }
        });

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

        // Load popular items
        document.addEventListener('DOMContentLoaded', function() {
            loadPopularItems();
        });

        function loadPopularItems() {
            const container = document.getElementById('popular-items-container');
            
            fetch('/controllers/get-popular-items.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data.length > 0) {
                        container.innerHTML = '';
                        
                        data.data.forEach(item => {
                            // Create stars HTML
                            let starsHtml = '';
                            const rating = parseFloat(item.avg_rating);
                            for (let i = 1; i <= 5; i++) {
                                if (i <= rating) {
                                    starsHtml += '<i class="fas fa-star"></i>';
                                } else if (i - 0.5 <= rating) {
                                    starsHtml += '<i class="fas fa-star-half-alt"></i>';
                                } else {
                                    starsHtml += '<i class="far fa-star"></i>';
                                }
                            }
                            
                            const itemCard = document.createElement('div');
                            itemCard.className = 'menu-card';
                            itemCard.setAttribute('data-item-id', item.id);
                            
                            itemCard.innerHTML = `
                                ${item.is_hot ? '<div class="hot-label">HOT</div>' : ''}
                                <div class="menu-card-image">
                                    <img src="${item.image_path}" alt="${item.name}">
                                </div>
                                <div class="menu-card-content">
                                    <div class="menu-content-top">
                                        <h3 class="menu-card-title">${item.name}</h3>
                                        <div class="menu-card-rating">
                                            <div class="menu-card-stars">${starsHtml}</div>
                                            <div class="menu-card-review-count">(${item.review_count})</div>
                                        </div>
                                        <p class="menu-card-description">${item.description}</p>
                                    </div>
                                    <div class="menu-card-footer">
                                        <span class="menu-card-price">₱${item.price}</span>
                                        <button class="menu-card-btn open-review-modal" data-item-id="${item.id}" data-item-name="${item.name}">
                                            <i class="far fa-star"></i> Review
                                        </button>
                                    </div>
                                </div>
                            `;
                            
                            container.appendChild(itemCard);
                        });
                    } else {
                        container.innerHTML = '<div class="no-items">No popular items found.</div>';
                    }
                })
                .catch(error => {
                    console.error('Error loading popular items:', error);
                    container.innerHTML = '<div class="error-message">Failed to load popular items.</div>';
                });
        }
    </script>

    <script src="/public/js/reviews.js"></script>
    <script src="/public/js/auth.js"></script>
</body>
</html>