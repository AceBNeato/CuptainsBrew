<?php
$db_host = 'localhost';
$db_user = 'root'; 
$db_pass = ''; 
$db_name = 'cafe_db';

// Initialize variables
$username = $email = '';
$errors = [];

// Connect to MySQL server
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Process form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize inputs
    $username = trim($conn->real_escape_string($_POST['username'] ?? ''));
    $email = trim($conn->real_escape_string($_POST['email'] ?? ''));
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['password_confirmation'] ?? '');
    
    // Simple validation
    if (empty($username)) {
        $errors[] = "Username is required";
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
    
    // Check if username or email already exists
    if (empty($errors)) {
        $sql = "SELECT id FROM users WHERE email = ? OR username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $email, $username);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $errors[] = "Username or email already taken";
        }
        $stmt->close();
    }
    
    // If no errors, proceed with registration
    if (empty($errors)) {
        // Hash the password (using bcrypt)
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        
        // Insert user into database
        $sql = "INSERT INTO users (username, password, email) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $username, $hashed_password, $email);
        
        if ($stmt->execute()) {
            // Registration successful - show SweetAlert and redirect
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'success',
                        title: 'Registration Successful!',
                        text: 'You will be redirected to the login page.',
                        showConfirmButton: false,
                        timer: 2000,
                        customClass: {
                            confirmButton: 'swal2-confirm'
                        }
                    }).then(() => {
                        window.location.href = '/views/auth/login.php';
                    });
                });
            </script>";
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
    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: #fff;
            color: #4a3b2b;
        }

        /* Header */
        .header {
            display: flex;
            align-items: center;
            padding: 1rem 2rem;
            background: linear-gradient(135deg, #FFFAEE, #FFDBB5);
            box-shadow: 0 2px 5px rgba(74, 59, 43, 0.3);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .logo-section img {
            width: 200px;
            margin: 0px 100px 0px 100px;
            transition: transform 0.3s;
        }

        .logo-section img:hover {
            transform: scale(1.1);
        }

        .nav-menu {
            display: flex;
            gap: 3rem;
        }

        .nav-button {
            background: none;
            border: none;
            color: #4a3b2b;
            font-size: 1rem;
            padding: 1rem 2rem;
            cursor: pointer;
            border-radius: 10px;
            transition: all 0.3s;
        }

        .nav-button:hover, .nav-button.active {
            background-color: #2C6E8A;
            color: #fff;
        }

        /* Form Container */
        .register-container {
            max-width: 450px;
            margin: 3rem auto;
            padding: 2rem;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(74, 59, 43, 0.5);
        }

        .register-container h2 {
            font-size: 1.5rem;
            color: #2C6E8A;
            margin-bottom: 1rem;
            text-align: center;
        }

        .edit-form {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .edit-form label {
            font-size: 0.9rem;
            color: #4a3b2b;
        }

        .edit-form input[type="text"],
        .edit-form input[type="email"],
        .edit-form input[type="password"] {
            padding: 0.5rem;
            border: none;
            border-radius: 5px;
            background: #A9D6E5;
            color: #4a3b2b;
            font-size: 0.9rem;
            width: 100%;
        }

        .edit-form button {
            padding: 0.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background-color 0.3s;
            background: #2C6E8A;
            color: #fff;
        }

        .edit-form button:hover {
            background: #235A73;
        }

        .options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 1rem 0;
        }

        .options-container {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .options a {
            color: #2C6E8A;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .options a:hover {
            text-decoration: underline;
        }

        .login-link {
            text-align: center;
            margin-top: 1rem;
        }

        .login-link p {
            font-size: 0.9rem;
            color: #4a3b2b;
        }

        .login-link a {
            color: #2C6E8A;
            text-decoration: none;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        /* Footer */
        .footer-container {
            background-color: #2C6E8A;
            color: white;
            padding: 2rem;
            display: flex;
            justify-content: space-between;
        }

        .footer-links a, .footer-contact p {
            color: white;
            text-decoration: none;
        }

        .footer-social img {
            width: 30px;
            margin-right: 10px;
        }

        .footer-bottom {
            background-color: #FFFAEE;
            display: flex;
            flex-direction: column;
            text-align: center;
            padding: 10px;
        }

        /* SweetAlert2 Custom Styling */
        .swal2-confirm {
            background-color: #2C6E8A !important;
            color: #fff !important;
            border-radius: 5px !important;
        }

        .swal2-confirm:hover {
            background-color: #235A73 !important;
        }

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                padding: 2vw;
                text-align: center;
            }

            .logo-section img {
                width: 40vw;
                margin: 0 0 2vw 0;
            }

            .nav-menu {
                flex-direction: column;
                gap: 1vw;
                width: 100%;
            }

            .nav-button {
                padding: 1vw;
                width: 100%;
                font-size: 3vw;
            }

            .register-container {
                width: 95%;
                padding: 2vw;
                margin: 2rem auto;
            }

            .edit-form label {
                font-size: 2.5vw;
            }

            .edit-form input {
                font-size: 2.5vw;
            }

            .edit-form button {
                font-size: 2.5vw;
            }

            .footer-container {
                flex-direction: column;
                gap: 2rem;
            }
        }
    </style>
    <script>
        function togglePassword() {
            const password = document.getElementById('password');
            const confirm = document.getElementById('password-confirm');
            const type = password.type === 'password' ? 'text' : 'password';
            password.type = type;
            confirm.type = type;
        }

        // Client-side validation with SweetAlert
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('.edit-form');
            form.addEventListener('submit', function(e) {
                const username = document.getElementById('username').value.trim();
                const email = document.getElementById('email').value.trim();
                const password = document.getElementById('password').value;
                const confirm = document.getElementById('password-confirm').value;

                if (!username) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Username is required!',
                        customClass: {
                            confirmButton: 'swal2-confirm'
                        }
                    });
                    return;
                }
                if (!email) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Email is required!',
                        customClass: {
                            confirmButton: 'swal2-confirm'
                        }
                    });
                    return;
                }
                if (!password) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Password is required!',
                        customClass: {
                            confirmButton: 'swal2-confirm'
                        }
                    });
                    return;
                }
                if (password.length < 8) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Password must be at least 8 characters!',
                        customClass: {
                            confirmButton: 'swal2-confirm'
                        }
                    });
                    return;
                }
                if (password !== confirm) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Passwords do not match!',
                        customClass: {
                            confirmButton: 'swal2-confirm'
                        }
                    });
                    return;
                }
            });

            // Display server-side errors with SweetAlert
            <?php if (!empty($errors)): ?>
                Swal.fire({
                    icon: 'error',
                    title: 'Registration Failed',
                    html: '<?php echo implode("<br>", array_map("htmlspecialchars", $errors)); ?>',
                    customClass: {
                        confirmButton: 'swal2-confirm'
                    }
                });
            <?php endif; ?>
        });
    </script>
</head>
<body>
    <header class="header">
        <div class="logo-section">
            <img src="/public/images/LOGO.png" alt="Cuptain's Brew Logo">
        </div>
        <nav class="nav-menu">
            <button class="nav-button" onclick="window.location.href='/views/home.html'">Home</button>
            <button class="nav-button" onclick="window.location.href='/views/menu.html'">Menu</button>
            <button class="nav-button" onclick="window.location.href='/views/career.html'">Career</button>
            <button class="nav-button" onclick="window.location.href='/views/aboutus.html'">About Us</button>
        </nav>
    </header>

    <div class="register-container">
        <h2>REGISTER</h2>
        
        <form class="edit-form" action="register.php" method="POST">
            <label for="username">Username</label>
            <input id="username" type="text" name="username" placeholder="Enter Username" value="<?php echo htmlspecialchars($username); ?>" required>

            <label for="email">Email Address</label>
            <input id="email" type="email" name="email" placeholder="Enter Email" value="<?php echo htmlspecialchars($email); ?>" required>

            <label for="password">Password</label>
            <input id="password" type="password" name="password" placeholder="Enter Password" required>

            <label for="password-confirm">Confirm Password</label>
            <input id="password-confirm" type="password" name="password_confirmation" placeholder="Confirm Password" required>
            
            <div class="options">
                <div class="options-container">
                    <input type="checkbox" id="showPassword" onclick="togglePassword()">
                    <label for="showPassword">Show Password</label>
                </div>
                <a href="#">Forgot Password</a>
            </div>

            <button type="submit">Register</button>
        </form>
        
        <div class="login-link">
            <p>Already have an account? <a href="/views/auth/login.php">Login here</a></p>
        </div>
    </div>
 
    <footer>
        <div class="footer-container">
            <div class="footer-left">
                <div class="footer-links">
                    <ul class="space-y-2">
                        <li><a href="/views/home.html">Home</a></li>
                        <li><a href="/views/aboutus.html">About Us</a></li>
                    </ul>
                </div>
                <div class="footer-social mt-4">
                    <a href="#"><img src="/public/images/facebook.png" alt="facebook"></a>
                    <a href="#"><img src="/public/images/twitter.png" alt="twitter"></a>
                    <a href="#"><img src="/public/images/instagram.png" alt="instagram"></a>
                </div>
            </div>
            
            <div class="footer-right">
                <div class="footer-contact">
                    <h3 class="text-lg font-bold">CONTACT US</h3>
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

    <?php
    // Close database connection
    $conn->close();
    ?>
</body>
</html>