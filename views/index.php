<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Captain's Brew Cafe - Welcome</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Segoe+UI:wght@400;500&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary: #2C6E8A;
            --primary-dark: #235A73;
            --primary-light: #A9D6E5;
            --secondary: #4A3B2B;
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
            padding: 0.75rem 2rem;
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
            transform: scale(1.05);
        }

        .hamburger {
            display: none;
            font-size: 1.5rem;
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
            font-family: 'Segoe UI', sans-serif;
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
            width: 24px;
            height: 24px;
            transition: var(--transition);
        }

        .nav-icon:hover img {
            transform: scale(1.1);
        }

        .profile {
            display: flex;
            align-items: center;
            position: relative;
            cursor: pointer;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            transition: var(--transition);
        }

        .profile:hover {
            background: var(--primary-light);
        }

        .profile img {
            width: 48px;
            height: auto;
            border-radius: 50%;
            margin-right: 0.75rem;
            border: 2px solid var(--primary-light);
        }

        .profile span {
            font-size: 0.95rem;
            font-weight: 500;
            color: var(--secondary);
            font-family: 'Segoe UI', sans-serif;
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

        .profile .dropdown a {
            display: block;
            padding: 0.75rem 1.25rem;
            color: var(--secondary);
            font-size: 0.95rem;
            transition: var(--transition);
            font-family: 'Segoe UI', sans-serif;
        }

        .profile .dropdown a:hover {
            background: var(--primary-light);
            color: var(--primary-dark);
        }

        /* Hero Section */
        .hero-container {
            position: relative;
            width: 100%;
            height: 100vh;
            min-height: 700px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', sans-serif;
        }

        .hero-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            filter: brightness(40%);
            position: absolute;
            top: 0;
            left: 0;
            z-index: -1;
        }

        .hero-content {
            text-align: center;
            color: var(--white);
            padding: 2rem;
            max-width: 800px;
        }

        .hero-content h1 {
            font-size: 4rem;
            font-weight: 600;
            margin-bottom: 1rem;
            animation: fadeInUp 1s ease;
        }

        .hero-content p {
            font-size: 1.2rem;
            line-height: 1.6;
            margin-bottom: 2rem;
            opacity: 0.9;
            animation: fadeInUp 1s ease 0.2s forwards;
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

        .hero-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            animation: fadeInUp 1s ease 0.4s forwards;
        }

        .hero-button {
            background-color: var(--accent);
            color: var(--white);
            font-size: 1.1rem;
            font-weight: 500;
            padding: 0.75rem 2rem;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: var(--transition);
            font-family: 'Segoe UI', sans-serif;
        }

        .hero-button:hover {
            background-color: #e69626;
            transform: translateY(-5px);
            box-shadow: var(--shadow-medium);
        }

        .hero-button.secondary {
            background-color: transparent;
            border: 2px solid var(--primary-light);
            color: var(--primary-light);
        }

        .hero-button.secondary:hover {
            background-color: var(--primary-light);
            color: var(--primary-dark);
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

        /* Responsive Design */
        @media (max-width: 992px) {
            .hero-content h1 {
                font-size: 3.5rem;
            }
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

            .profile .dropdown a {
                padding: 0.75rem 2.5rem;
            }

            .hero-container {
                height: 80vh;
                min-height: 600px;
            }

            .hero-content h1 {
                font-size: 2.5rem;
            }

            .hero-content p {
                font-size: 1rem;
            }

            .hero-button {
                font-size: 1rem;
                padding: 0.5rem 1.5rem;
            }

            .social-links img {
                width: 5vw;
            }
        }

        @media (max-width: 576px) {
            #logo {
                height: 40px;
            }

            .nav-button {
                font-size: 0.9rem;
            }

            .hero-content h1 {
                font-size: 2rem;
            }

            .hero-content p {
                font-size: 0.9rem;
            }

            .hero-button {
                font-size: 0.9rem;
                padding: 0.5rem 1rem;
            }
        }
    </style>
</head>
<body>

    <!-- Hero Section -->
    <div class="hero-container">
        <img src="/public/images/background/getstarted.png" alt="Welcome to Captain's Brew" class="hero-image">
        <div class="hero-content">
            <h1>Welcome to Captain's Brew Cafe</h1>
            <p>Embark on a coffee adventure with us! Enjoy handcrafted beverages and delightful treats in a cozy atmosphere. Sign in to start your journey or explore our menu as a guest.</p>
            <div class="hero-buttons">
                <?php if (isset($_SESSION['username'])): ?>
                    <button class="hero-button" onclick="window.location.href = '/views/users/user-menu.php'">Explore Menu</button>
                <?php else: ?>
                    <button class="hero-button" onclick="window.location.href = '/views/auth/login.php'">Login</button>
                    <button class="hero-button secondary" onclick="window.location.href = '/views/auth/register.php'">Register</button>
                    <button class="hero-button secondary" onclick="window.location.href = '/views/users/user-home.php'">Continue as Guest</button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <?php include __DIR__ . '/partials/footer.php'; ?>
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
    </script>
</body>
</html>
