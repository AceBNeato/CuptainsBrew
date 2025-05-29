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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Invalid security token. Please try again.';
    } else {
        // Get contact from form
        $contact = isset($_POST['contact']) ? trim($_POST['contact']) : '';
        
        if (empty($contact)) {
            $error = 'Please enter your contact number';
        } else {
            // Authenticate rider
            $rider = authenticateRider($contact);
            
            if ($rider) {
                // Login successful
                loginRider($rider['id'], $rider['name']);
                header('Location: /views/riders/Rider-Dashboard.php');
                exit();
            } else {
                $error = 'Invalid contact number. Please try again.';
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
    <link rel="icon" href="/public/images/logo.png" sizes="any" />
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
        }

        .login-container {
            background: #FFFFFF;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(74, 59, 43, 0.1);
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
    </style>
</head>
<body>
    <div class="login-container">
        <img src="/public/images/logo.png" alt="Captain's Brew Logo" class="logo">
        <h1 class="login-title">Rider Login</h1>
        
        <?php if (!empty($error)): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="success-message"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <form class="login-form" method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            
            <div class="form-group">
                <label for="contact">Contact Number</label>
                <input type="text" id="contact" name="contact" class="form-control" required>
            </div>
            
            <button type="submit" class="login-btn">Login</button>
        </form>
        
        <a href="/views/auth/login.php" class="back-link">Back to Main Website</a>
    </div>
</body>
</html> 