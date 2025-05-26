<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Cuptain's Brew</title>
    <link rel="stylesheet" href="/css/style.css">

</head>
<body>

    <style>
        .about-us {
            display: flex;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            align-items: center;
            min-height: 100vh;
            text-align: center;
            padding: 50px 20px;
        }

        .container {
            max-width: 1100px;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 40px;
        }

        .about-content {
            flex: 1;
        }

        .about-content h1 {
            font-size: 36px;
            color: aliceblue;
            text-transform: uppercase;
        }

        .about-content h1 span {
            color: #c49a6c;
        }

        .about-content p {
            font-size: 18px;
            color: aliceblue;
            line-height: 1.6;
            margin-top: 15px;
        }

        .about-image {
            flex: 1;
        }

        .about-image img {
            width: 30vw;
            max-width: 500px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
            transition: .3s;
        }
        .about-image img:hover {
            width: 30.5vw;
            box-shadow: 1px 1px 10px #ffffff;
            transition: .3s;
        }
    </style>

<header class="header">
    <img src="/images/LOGO.png" id="logo" alt="cuptainsbrewlogo">
    <nav class="button-container" id="nav-menu">

        <a href="/views/home.html" class="nav-button" class="nav-button" > Home</a>
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

<section class="about-us">
    <div class="container">
        <div class="about-content">
            <h1>About <span>Cuptain's Brew Cafe</span></h1>
            <p>Welcome to Cuptain’s Brew Cafe, your cozy retreat for artisanal coffee and handcrafted delights. Nestled in the heart of the city, we are passionate about bringing people together over a cup of perfectly brewed coffee.</p>
            <h1 style="color: white;">   ══════════════════════ </h1>
            <p>Our journey began with a love for rich flavors and warm conversations. Every cup we serve is made with freshly roasted beans and a touch of love. Whether you’re here for a quiet reading session, a casual meet-up, or to savor our signature blends, Cuptain’s Brew is your home away from home.</p>
            <p>Join us and experience the magic in every sip.</p>
        </div>
        <div class="about-image">
            <img src="/images/cbc-opening.jpg" alt="Cuptain's Brew Cafe">
        </div>
    </div>
</section>







    <button id="back-to-top" ><img src="/images/top-icon.png" alt="Go to Top"></button>

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


    <script src="/public/js/auth.js"></script>
    <script src="/public/js/script.js"></script>
    
    
</body>
</html>
