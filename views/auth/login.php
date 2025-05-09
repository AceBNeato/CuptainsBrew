<?php
ob_start(); // Start output buffering
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
                
                // PHP redirect as fallback
                header("Location: /views/users/User-Home.php");
                
                // Show SweetAlert and attempt JavaScript redirect
                echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            icon: 'success',
                            title: 'Login Successful!',
                            text: 'Welcome back, {$user['username']}!',
                            showConfirmButton: false,
                            timer: 2000,
                            customClass: {
                                confirmButton: 'swal2-confirm'
                            }
                        }).then(() => {
                            try {
                                window.location.href = '/views/users/User-Home.php';
                            } catch (e) {
                                console.error('Redirect failed:', e);
                            }
                        }).catch(e => {
                            console.error('SweetAlert2 error:', e);
                        });
                    });
                </script>";
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
    <title>Captain's Brew | Login</title>
    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Font Awesome CDN for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="icon" href="/public/images/LOGO.png" sizes="any">
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
        .login-container {
            max-width: 450px;
            margin: 3rem auto;
            padding: 2rem;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(74, 59, 43, 0.5);
        }

        .login-container h2 {
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

        .edit-form input:focus {
            outline: none;
            box-shadow: 0 0 0 2px rgba(44, 110, 138, 0.3);
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

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 1rem 0;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .forgot-password {
            color: #2C6E8A;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .forgot-password:hover {
            text-decoration: underline;
        }

        .create-account {
            text-align: center;
            margin-top: 1rem;
        }

        .create-account p {
            font-size: 0.9rem;
            color: #4a3b2b;
        }

        .create-account a {
            color: #2C6E8A;
            text-decoration: none;
        }

        .create-account a:hover {
            text-decoration: underline;
        }

        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #4a3b2b;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .input-wrapper {
            position: relative;
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

        /* Responsive Design */
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

            .login-container {
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

            .form-options {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .footer-container {
                flex-direction: column;
                gap: 2rem;
            }
        }
    </style>
    <script>
        // Toggle Password Visibility
        function togglePassword() {
            const password = document.getElementById('password');
            const toggle = document.getElementById('togglePassword');
            const type = password.type === 'password' ? 'text' : 'password';
            password.type = type;
            toggle.classList.toggle('fa-eye');
            toggle.classList.toggle('fa-eye-slash');
        }

        // Client-side validation with SweetAlert
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('.edit-form');
            form.addEventListener('submit', function(e) {
                const email = document.getElementById('email').value.trim();
                const password = document.getElementById('password').value;

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
            });

            // Display server-side errors with SweetAlert
            <?php if (!empty($errors)): ?>
                Swal.fire({
                    icon: 'error',
                    title: 'Login Failed',
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
    <!-- Header -->
    <header class="header">
        <div class="logo-section">
            <a href="/">
                <img src="/public/images/LOGO.png" alt="Captain's Brew Cafe">
            </a>
        </div>
        <nav class="nav-menu">
            <button class="nav-button" onclick="window.location.href='/views/auth/register.php'">Sign Up</button>
        </nav>
    </header>

    <!-- Main Content -->
    <main class="login-container">
        <h2>LOGIN</h2>

        <form class="edit-form" method="POST" action="login.php">
            <label for="email">Email Address</label>
            <div class="input-wrapper">
                <input type="email" name="email" id="email" placeholder="Enter Email" value="<?php echo htmlspecialchars($email); ?>" required>
            </div>

            <label for="password">Password</label>
            <div class="input-wrapper">
                <input type="password" name="password" id="password" placeholder="Enter Password" required>
                <i class="fas fa-eye password-toggle" id="togglePassword" onclick="togglePassword()"></i>
            </div>

            <div class="form-options">
                <div class="remember-me">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">Remember me</label>
                </div>
                <a href="/views/auth/forgot-password.php" class="forgot-password">Forgot password?</a>
            </div>

            <button type="submit">Login</button>

            <div class="create-account">
                <p>Don't have an account? <a href="/views/auth/register.php">Create one</a></p>
            </div>
        </form>
    </main>

    <!-- Footer -->
    <footer>
        <div class="footer-container">
            <div class="footer-left">
                <div class="footer-links">
                    <ul style="list-style: none; padding: 0;">
                        <li style="margin-bottom: 0.5rem;"><a href="/views/index.php">Home</a></li>
                        <li style="margin-bottom: 0.5rem;"><a href="/views/menu.html">Menu</a></li>
                        <li style="margin-bottom: 0.5rem;"><a href="/views/aboutus.html">About Us</a></li>
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
                    <p><strong>E-mail:</strong> support@captainsbrew.com</p>
                    <p><strong>Website:</strong> www.captainsbrew.com</p>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <p>© Copyright 2025 Captain's Brew Cafe. All Rights Reserved.</p>
        </div>
    </footer>

    <?php
    // Close database connection
    $conn->close();
    ob_end_flush(); // Flush output buffer
    ?>
</body>
</html>