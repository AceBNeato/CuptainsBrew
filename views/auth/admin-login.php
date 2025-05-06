<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Cuptain's Brew | Login</title>
    <link rel="stylesheet" href="/public/css/admin.css">
    <link rel="icon" href="/public/images/LOGO.png" sizes="any">

</head>
<body>
    <header class="header">
    <img src="/public/images/LOGO.png" id="logo" alt="cuptainsbrewlogo">


   
    </nav>
</header>

<div class="login-container">
    <h2>LOGIN</h2>
  
    <form id="admin-login-form" >

        <label for="email" id="label">Email Account</label>
        <input type="email" name="email" id="email" placeholder="Enter email" required />

        <label for="password" id="label">Password</label>
        <input type="password" name="password" id="password" placeholder="Enter Password" required />

        <div class="options">
            <div class="options-container">
                <input type="checkbox" id="showPassword" onclick="togglePassword()" />
                <label for="showPassword" id="show-password">Show Password</label>
            </div>
        </div>

        
        <div class="submit">
            <button id="submit">LOGIN</button>
        </div>

       
        <!-- Loading Animation -->
        <div id="loading-overlay" class="loading-overlay">
            <div class="spinner"></div>
        </div>
        
    </form>
</div>

 
<script src="/public/js/auth.js"></script>
<script src="/public/js/script.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</body>
</html>
