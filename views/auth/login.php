<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Cuptain's Brew | Login</title>
    <link rel="stylesheet" href="/public/css/style.css">
    <link rel="icon" href="/images/LOGO.png" sizes="any">

</head>
<body>
    <header class="header">
    <img src="/images/LOGO.png" id="logo" alt="cuptainsbrewlogo">

    <!-- "nav-menu" is for mobile
    <button id="menu-toggle" class="menu-toggle"> ☰  </button> -->

        
    <div id="hamburger-menu" class="hamburger">&#9776;</div>

    <nav class="button-container" id="nav-menu">

        <button onclick="window.location.href='/views/index.php'" class="nav-button active" >Home</button>
        
        <a href="/views/menu.html" class="nav-button" class="nav-button" > Menu</a>
        <a href="/views/career.html" class="nav-button" class="nav-button" > Career</a>
        <a href="/views/aboutus.html" class="nav-button" class="nav-button" > About Us</a>


        <div class="icon-container">
            <a href="/views/users/cart.html" id="cart-icon" class="nav-icon hidden">
                <img src="/images/cart-icon.png" alt="Cart">
            </a>
            <a href="/views/users/profile.html" id="profile-icon" class="nav-icon hidden">
                <img src="/images/profile-icon.png" alt="Profile" >
            </a>
        </div>

    </nav>
</header>

<div class="login-container">
    <h2>LOGIN</h2>
  
    <form id="user-login-form" >

        <label for="email" id="label">Email Account</label>
        <input type="email" name="email" id="email" placeholder="Enter email" required />

        <label for="password" id="label">Password</label>
        <input type="password" name="password" id="password" placeholder="Enter Password" required />

        <div class="options">
            <div class="options-container">
                <input type="checkbox" id="showPassword" onclick="togglePassword()" />
                <label for="showPassword" id="show-password">Show Password</label>
            </div>
            <a href="#">Forgot Password</a>
        </div>

        
        <div class="submit">
            <button id="submit">LOGIN</button>
        </div>

        <div class="create-account">
            <a href="/views/auth/register.html">Create Account</a>


        <!-- Loading Animation -->
        <div id="loading-overlay" class="loading-overlay">
            <div class="spinner"></div>
        </div>

        </div>
    </form>
</div>
 
<footer>
    <div class="footer-container" style="display: flex;">
       
        <div class="footer-left" >
            <div class="footer-links" style="display: flex; flex-direction: column;">
                <ul>
                    <li><a href="/views/home.html">Home</a></li>
                    <li><a href="/views/aboutus.html">About Us</a></li>
                </ul>
            </div>
            <div class="footer-social">
                <a href="#"><img src="/images/facebook.png" style="width: 2vw;" alt="facebook"><i class="fab fa-facebook"></i> </a>
                <a href="#"><img src="/images/twitter.png" style="width: 2vw;" alt="twitter" alt=""><i class="fab fa-twitter"></i></a>
                <a href="#"><img src="/images/instagram.png" style="width: 2vw;" alt="instagram"><i class="fab fa-instagram"></i></a>
            </div>
            
        </div>
        
        <div class="footer-right" style="display: flex;">
            <div class="footer-contact">
                <h3>CONTACT US</h3>
                <p>123 Coffee Street, City Name</p>
                <p><strong>Phone:</strong> +1 800 555 6789</p>
                <p><strong>E-mail:</strong> support@cuptainsbrew.com</p>
                <p><strong>Website:</strong> www.cuptainsbrew.com</p>
            </div>
                
        </div>


   </div>

   <div class="footer-bottom">
    <p>© Copyright 2025 Cuptain’s Brew Cafe. All Rights Reserved.</p>
</div>


</footer>
<script  src="/public/js/auth.js"></script>
<script  src="/public/js/script.js"></script>


</body>
</html>
