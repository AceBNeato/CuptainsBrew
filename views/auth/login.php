<?php
session_start();

$db_host = 'localhost';
$db_user = 'root'; 
$db_pass = ''; 
$db_name = 'cafe_db';

// Initialize variables
$email = '';
$errors = [];

// Connect to MySQL server
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Process form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize inputs
    $email = trim($conn->real_escape_string($_POST['email'] ?? ''));
    $password = trim($_POST['password'] ?? '');
    
    // Simple validation
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    }
    
    // If no errors, proceed with login
    if (empty($errors)) {
        // Prepare SQL to get user data
        $sql = "SELECT id, username, password FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Password is correct, start session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['loggedin'] = true;
                
                // Redirect to home page
                header("Location: /views/users/User_Home.php");
                exit();
            } else {
                $errors[] = "Incorrect password";
            }
        } else {
            $errors[] = "No account found with that email";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Cuptain's Brew | Login</title>
    <link rel="stylesheet" href="/public/css/style.css">
    <link rel="icon" href="/images/LOGO.png" sizes="any">
    <script>
        function togglePassword() {
            const password = document.getElementById('password');
            const type = password.type === 'password' ? 'text' : 'password';
            password.type = type;
        }
    </script>
</head>
<body>
    <header class="header">
        <img src="/images/LOGO.png" id="logo" alt="cuptainsbrewlogo">
        <div id="hamburger-menu" class="hamburger">&#9776;</div>
        <nav class="button-container" id="nav-menu">
            <button onclick="window.location.href='/views/index.php'" class="nav-button active">Home</button>
            <a href="/views/menu.html" class="nav-button">Menu</a>
            <a href="/views/career.html" class="nav-button">Career</a>
            <a href="/views/aboutus.html" class="nav-button">About Us</a>

            <div class="icon-container">
                <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']): ?>
                    <a href="/views/users/cart.html" id="cart-icon" class="nav-icon">
                        <img src="/images/cart-icon.png" alt="Cart">
                    </a>
                    <a href="/views/users/profile.html" id="profile-icon" class="nav-icon">
                        <img src="/images/profile-icon.png" alt="Profile">
                    </a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <div class="login-container">
        <h2>LOGIN</h2>
        
        <?php if (!empty($errors)): ?>
            <div class="error-messages">
                <?php foreach ($errors as $error): ?>
                    <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <form id="user-login-form" method="POST" action="login.php">
            <label for="email" id="label">Email</label>
            <input type="email" name="email" id="email" placeholder="Enter email" value="<?php echo htmlspecialchars($email); ?>" required>

            <label for="password" id="label">Password</label>
            <input type="password" name="password" id="password" placeholder="Enter Password" required>

            <div class="options">
                <div class="options-container">
                    <input type="checkbox" id="showPassword" onclick="togglePassword()" />
                    <label for="showPassword" id="show-password">Show Password</label>
                </div>
                <a href="#">Forgot Password</a>
            </div>
            
            <div class="submit">
                <button type="submit" id="submit">LOGIN</button>
            </div>

            <div class="create-account">
                <a href="/views/auth/register.php">Create Account</a>
            </div>
        </form>
    </div>
 
    <footer>
        <div class="footer-container" style="display: flex;">
            <div class="footer-left">
                <div class="footer-links" style="display: flex; flex-direction: column;">
                    <ul>
                        <li><a href="/views/home.html">Home</a></li>
                        <li><a href="/views/aboutus.html">About Us</a></li>
                    </ul>
                </div>
                <div class="footer-social">
                    <a href="#"><img src="/images/facebook.png" style="width: 2vw;" alt="facebook"></a>
                    <a href="#"><img src="/images/twitter.png" style="width: 2vw;" alt="twitter"></a>
                    <a href="#"><img src="/images/instagram.png" style="width: 2vw;" alt="instagram"></a>
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
            <p>© Copyright 2025 Cuptain's Brew Cafe. All Rights Reserved.</p>
        </div>
    </footer>

    <script src="/public/js/script.js"></script>
</body>
</html>
<?php
// Close database connection
$conn->close();
?>