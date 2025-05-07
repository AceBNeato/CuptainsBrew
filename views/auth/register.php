<?php
$db_host = 'localhost';
$db_user = 'root'; 
$db_pass = ''; 
$db_name = 'cafe_db';

// Initialize variables
$name = $email = '';
$errors = [];

// Connect to MySQL server
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// Process form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize inputs
    $name = trim($conn->real_escape_string($_POST['name'] ?? ''));
    $email = trim($conn->real_escape_string($_POST['email'] ?? ''));
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['password_confirmation'] ?? '');
    
    // Simple validation
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    // Check if email already exists
    if (empty($errors)) {
        $sql = "SELECT id FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $errors[] = "Email is already taken";
        }
        $stmt->close();
    }
    
    // If no errors, proceed with registration
    if (empty($errors)) {
        // Hash the password (using bcrypt)
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        
        // Insert user into database
        $sql = "INSERT INTO users (name, email, password) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $name, $email, $hashed_password);
        
        if ($stmt->execute()) {
            // Registration successful - redirect to login
            header("Location: /views/login.html");
            exit();
        } else {
            $errors[] = "Something went wrong. Please try again later.";
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
    <title>Cuptain's Brew | Register</title>
    <link rel="stylesheet" href="/public/css/style.css">
    <script>
        function togglePassword() {
            const password = document.getElementById('password');
            const confirm = document.getElementById('password-confirm');
            const type = password.type === 'password' ? 'text' : 'password';
            password.type = type;
            confirm.type = type;
        }
    </script>
</head>
<body>
    <header class="header">
        <img src="/public/images/LOGO.png" id="logo" alt="cuptainsbrewlogo">
        <nav class="button-container" id="nav-menu">
            <a href="/views/home.html" class="nav-button">Home</a>
            <a href="/views/menu.html" class="nav-button">Menu</a>
            <a href="/views/career.html" class="nav-button">Career</a>
            <a href="/views/aboutus.html" class="nav-button">About Us</a>
        </nav>
    </header>

    <div class="register-container">
        <h2>REGISTER</h2>
        
        <?php if (!empty($errors)): ?>
            <div class="error-messages">
                <?php foreach ($errors as $error): ?>
                    <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <form class="register-form" action="register.php" method="POST">
            <label for="name" id="label">Name</label>
            <input id="name" type="text" name="name" placeholder="Enter Name" value="<?php echo htmlspecialchars($name); ?>" required>

            <label for="email" id="label">Email Address</label>
            <input id="email" type="email" name="email" placeholder="Enter Email" value="<?php echo htmlspecialchars($email); ?>" required>

            <label for="password" id="label">Password</label>
            <input id="password" type="password" name="password" placeholder="Enter Password" required>

            <label for="password-confirm" id="label">Confirm Password</label>
            <input id="password-confirm" type="password" name="password_confirmation" placeholder="Confirm Password" required>
            
            <div class="options">
                <div class="options-container">
                    <input type="checkbox" id="showPassword" onclick="togglePassword()" />
                    <label for="showPassword" id="show-password">Show Password</label>
                </div>
                <a href="#">Forgot Password</a>
            </div>

            <div class="register-container-button">
                <button type="submit">Register</button>
            </div>
        </form>
        
        <div class="login-link">
            <p>Already have an account? <a href="/views/login.html">Login here</a></p>
        </div>
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
</body>
</html>
<?php
// Close database connection
$conn->close();
?>