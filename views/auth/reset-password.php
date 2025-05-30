<?php
session_start();
require_once __DIR__ . '/../../config.php';
require_once '../../vendor/autoload.php';

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'cafe_db';

$errors = [];
$success = false;
$token = $_GET['token'] ?? '';
$password = '';
$confirm_password = '';

// Connect to MySQL server
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Verify token validity
if (empty($token)) {
    header("Location: login.php");
    exit();
}

$stmt = $conn->prepare("SELECT u.email, u.reset_expires, r.name as role FROM users u JOIN roles r ON u.role_id = r.id WHERE u.reset_token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $errors[] = "Invalid or expired reset link.";
} else {
    $user = $result->fetch_assoc();
    
    // Check if user is an admin
    if ($user['role'] === 'admin') {
        $errors[] = "Password reset is not available for admin accounts. Please contact system support.";
    } 
    // Check if token is expired
    else if (strtotime($user['reset_expires']) < time()) {
        $errors[] = "This reset link has expired. Please request a new one.";
    }
}
$stmt->close();

if ($_SERVER["REQUEST_METHOD"] == "POST" && empty($errors)) {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['password_confirmation'] ?? '';
    
    // Validate password
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters";
    } elseif (!preg_match("/[A-Z]/", $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    } elseif (!preg_match("/[0-9]/", $password)) {
        $errors[] = "Password must contain at least one number";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        
        $updateStmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE reset_token = ?");
        $updateStmt->bind_param("ss", $hashed_password, $token);
        
        if ($updateStmt->execute()) {
            $success = true;
        } else {
            $errors[] = "Failed to update password. Please try again.";
            error_log("Failed to update password for token $token: " . $updateStmt->error);
        }
        $updateStmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cuptain's Brew | Reset Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="icon" href="/public/images/LOGO.png" sizes="any">
    <style>
        :root {
            --primary: #2C6E8A;
            --primary-dark: #235A73;
            --primary-light: #A9D6E5;
            --secondary: #4A3B2B;
            --accent: #ffb74a;
            --white: #fff;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-600: #4b5563;
            --error: #ef4444;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            padding: 1rem;
        }

        .image-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }

        #getstarted {
            width: 100%;
            height: 100%;
            object-fit: cover;
            filter: brightness(50%) blur(8px);
            transform: scale(1.1);
        }

        .reset-password-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px) saturate(180%);
            -webkit-backdrop-filter: blur(12px) saturate(180%);
            padding: 2.5rem;
            border-radius: 1rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 420px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            margin: 2rem auto;
        }

        .reset-password-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .reset-password-header h1 {
            color: var(--primary);
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            letter-spacing: 1px;
        }

        .reset-password-header p {
            color: var(--gray-600);
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            color: var(--gray-600);
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
            font-weight: 500;
        }

        .password-field {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-600);
            cursor: pointer;
            font-size: 1.25rem;
            opacity: 0.7;
            transition: opacity 0.3s ease;
        }

        .password-toggle:hover {
            opacity: 1;
        }

        .form-group input {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 1px solid var(--gray-300);
            border-radius: 0.5rem;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(44, 110, 138, 0.1);
            background: var(--white);
        }

        .submit-button {
            width: 100%;
            padding: 0.875rem;
            background: var(--primary);
            color: var(--white);
            border: none;
            border-radius: 0.5rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .submit-button:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        @media (max-width: 640px) {
            .reset-password-card {
                padding: 2rem;
                margin: 1rem;
            }

            .reset-password-header h1 {
                font-size: 1.75rem;
            }
        }
    </style>
</head>
<body>
    <div class="image-container">
        <img src="/public/images/background/login.jpg" alt="Background" id="getstarted">
    </div>

    <div class="reset-password-card">
        <div class="reset-password-header">
            <h1>Reset Password</h1>
            <p>Please enter your new password below.</p>
        </div>

        <?php if (!$success): ?>
        <form method="POST" id="reset-form">
            <div class="form-group">
                <label for="password">New Password</label>
                <div class="password-field">
                    <input type="password" id="password" name="password" required 
                           placeholder="Enter your new password"
                           value="<?php echo htmlspecialchars($password); ?>">
                    <i class="fas fa-eye password-toggle" onclick="togglePassword('password')"></i>
                </div>
            </div>

            <div class="form-group">
                <label for="password-confirm">Confirm New Password</label>
                <div class="password-field">
                    <input type="password" id="password-confirm" name="password_confirmation" required
                           placeholder="Confirm your new password"
                           value="<?php echo htmlspecialchars($confirm_password); ?>">
                    <i class="fas fa-eye password-toggle" onclick="togglePassword('password-confirm')"></i>
                </div>
            </div>

            <button type="submit" class="submit-button">Reset Password</button>
        </form>
        <?php endif; ?>
    </div>

    <script>
        function togglePassword(id) {
            const input = document.getElementById(id);
            const icon = input.nextElementSibling;
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Form validation
        document.getElementById('reset-form')?.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('password-confirm').value;
            let errors = [];

            if (password.length < 8) {
                errors.push('Password must be at least 8 characters');
            }
            if (!/[A-Z]/.test(password)) {
                errors.push('Password must contain at least one uppercase letter');
            }
            if (!/[0-9]/.test(password)) {
                errors.push('Password must contain at least one number');
            }
            if (password !== confirmPassword) {
                errors.push('Passwords do not match');
            }

            if (errors.length > 0) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    html: errors.join('<br>'),
                    confirmButtonColor: '#2C6E8A'
                });
            }
        });

        <?php if ($success): ?>
        Swal.fire({
            icon: 'success',
            title: 'Password Reset Successful!',
            text: 'Your password has been updated. You can now login with your new password.',
            confirmButtonColor: '#2C6E8A'
        }).then(() => {
            window.location.href = 'login.php';
        });
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            html: '<?php echo implode("<br>", array_map("htmlspecialchars", $errors)); ?>',
            confirmButtonColor: '#2C6E8A'
        }).then(() => {
            <?php if ($errors[0] === "Invalid or expired reset link." || $errors[0] === "This reset link has expired. Please request a new one."): ?>
            window.location.href = 'forgot-password.php';
            <?php endif; ?>
        });
        <?php endif; ?>
    </script>
</body>
</html> 