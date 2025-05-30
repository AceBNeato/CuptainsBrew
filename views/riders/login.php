<?php
// Include the database configuration
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/rider_auth.php';

// Ensure session is started
if (!isset($_SESSION)) {
    session_start();
}

// Redirect to dashboard if already logged in
if (isRiderLoggedIn()) {
    header('Location: /views/riders/Rider-Dashboard.php');
    exit();
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle login form submission
$error = '';
$contact = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Invalid security token. Please try again.';
    } else {
        // Get form data
        $contact = isset($_POST['contact']) ? trim($_POST['contact']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        
        if (empty($contact)) {
            $error = 'Please enter your contact number';
        } elseif (empty($password)) {
            $error = 'Please enter your password';
        } else {
            // Check for too many failed login attempts
            if (tooManyFailedAttempts($_SERVER['REMOTE_ADDR'])) {
                $error = 'Too many failed login attempts. Please try again later.';
            } else {
                // Authenticate rider with password
                $rider = authenticateRider($contact, $password);
                
                if ($rider) {
                    // Update last login time
                    $stmt = $conn->prepare("UPDATE riders SET last_login = NOW() WHERE id = ?");
                    $stmt->bind_param("i", $rider['id']);
                    $stmt->execute();
                    
                    // Login successful
                    loginRider($rider['id'], $rider['name']);
                    header('Location: /views/riders/Rider-Dashboard.php');
                    exit();
                } else {
                    $error = 'Invalid credentials. Please try again.';
                }
            }
        }
    }
}

// Get error message from URL if exists
if (empty($error) && isset($_GET['error'])) {
    $error = $_GET['error'];
}

// Get success message from URL if exists
$success = isset($_GET['success']) ? $_GET['success'] : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title>Rider Login - Captain's Brew Cafe</title>
    <link rel="icon" href="/public/images/LOGO.png" sizes="any" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: #f9fafb;
            color: #4a3b2b;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-image: url('/public/images/background/login.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            padding: 2rem;
            width: 90%;
            max-width: 400px;
            text-align: center;
        }

        .logo {
            width: 120px;
            margin-bottom: 1rem;
        }

        .login-title {
            color: #2C6E8A;
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .login-form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .form-group {
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
            color: #4a3b2b;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #D1D5DB;
            border-radius: 6px;
            font-size: 1rem;
        }

        .form-control:focus {
            outline: none;
            border-color: #2C6E8A;
            box-shadow: 0 0 0 3px rgba(44, 110, 138, 0.1);
        }

        .password-field {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #4a3b2b;
            cursor: pointer;
            opacity: 0.7;
        }

        .password-toggle:hover {
            opacity: 1;
        }

        .login-btn {
            background: #2C6E8A;
            color: #FFFFFF;
            border: none;
            padding: 0.75rem;
            border-radius: 6px;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 0.5rem;
        }

        .login-btn:hover {
            background: #235A73;
        }

        .error-message {
            background: #FEF2F2;
            color: #EF4444;
            padding: 0.75rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            font-size: 0.875rem;
        }

        .success-message {
            background: #DEF7EC;
            color: #057A55;
            padding: 0.75rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            font-size: 0.875rem;
        }

        .back-link {
            display: inline-block;
            margin-top: 1.5rem;
            color: #2C6E8A;
            text-decoration: none;
            font-size: 0.875rem;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #ffffff;
            border-top: 4px solid #2C6E8A;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <img src="/public/images/LOGO.png" alt="Captain's Brew Logo" class="logo">
        <h1 class="login-title">Rider Login</h1>
        
        <?php if (!empty($error)): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="success-message"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <form class="login-form" method="POST" action="" id="login-form">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            
            <div class="form-group">
                <label for="contact">Contact Number</label>
                <input type="text" placeholder="Enter Contact Number" id="contact"  name="contact"  class="form-control" value="<?= htmlspecialchars($contact) ?>" required
                >
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-field">
                    <input  type="password" placeholder="Enter Password"  id="password"  name="password"   class="form-control" required
                    >
                    <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                </div>
            </div>
            
            <button type="submit" class="login-btn">Login</button>
        </form>
        
        <a href="/views/auth/login.php" class="back-link">Back to Main Website</a>
    </div>

    <div class="loading-overlay">
        <div class="spinner"></div>
    </div>

    <script>
        // Toggle password visibility
        const togglePassword = document.getElementById('togglePassword');
        const password = document.getElementById('password');

        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });

        // Show loading overlay on form submission
        document.getElementById('login-form').addEventListener('submit', function() {
            document.querySelector('.loading-overlay').style.display = 'flex';
        });
    </script>
</body>
</html> 