<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email - Cuptain's Brew</title>
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
            --success: #22c55e;
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

        /* Background Image */
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
            filter: brightness(50%);
        }

        .verify-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 2.5rem;
            border-radius: 1rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 420px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            text-align: center;
        }

        .verify-header {
            margin-bottom: 2rem;
        }

        .verify-header h2 {
            color: var(--primary);
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
            letter-spacing: 1px;
        }

        .verify-header p {
            color: var(--gray-600);
            font-size: 1rem;
            line-height: 1.5;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-group input {
            width: 100%;
            padding: 0.875rem 1rem 0.875rem 2.75rem;
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

        .form-group i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-600);
            font-size: 1.1rem;
        }

        .verify-button {
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
            margin-bottom: 1.5rem;
        }

        .verify-button:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .resend-link {
            color: var(--gray-600);
            font-size: 0.95rem;
        }

        .resend-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .resend-link a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        /* Loading Spinner */
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
            border: 4px solid var(--white);
            border-top: 4px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsive Design */
        @media (max-width: 640px) {
            .verify-card {
                padding: 2rem;
                margin: 1rem;
            }

            .verify-header h2 {
                font-size: 1.75rem;
            }

            .form-group input {
                padding: 0.75rem 1rem 0.75rem 2.5rem;
                font-size: 0.95rem;
            }
        }
    </style>
</head>
<body>
    <!-- Background Image -->
    <div class="image-container">
        <img src="/public/images/background/login.jpg" alt="Verification Background" id="getstarted">
    </div>

    <div class="verify-card">
        <div class="verify-header">
            <h2>Verify Email</h2>
            <p>Enter the 6-digit code sent to your email</p>
        </div>

        <form id="verify-form" method="POST" action="">
            <div class="form-group">
                <i class="fas fa-key"></i>
                <input type="text" name="code" placeholder="Enter 6-digit code" required pattern="\d{6}" 
                       title="Please enter a valid 6-digit code" maxlength="6">
            </div>
            <div class="form-group">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" placeholder="Enter your email" required>
            </div>
            <button type="submit" class="verify-button">Verify Email</button>
            <p class="resend-link">Didn't receive a code? <a href="resend-verification.php">Resend Code</a></p>
        </form>
    </div>

    <div class="loading-overlay">
        <div class="spinner"></div>
    </div>

    <script>
        // Form submission handling
        document.getElementById('verify-form').addEventListener('submit', function(e) {
            const code = document.querySelector('input[name="code"]').value.trim();
            const email = document.querySelector('input[name="email"]').value.trim();

            if (!code || !email) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: 'Please fill in all fields',
                    confirmButtonColor: '#2C6E8A'
                });
                return;
            }

            if (!/^\d{6}$/.test(code)) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Code',
                    text: 'Please enter a valid 6-digit code',
                    confirmButtonColor: '#2C6E8A'
                });
                return;
            }

            // Show loading overlay
            document.querySelector('.loading-overlay').style.display = 'flex';
        });
    </script>

    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        require_once '../../config.php';

        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $code = filter_var($_POST['code'], FILTER_SANITIZE_STRING);

        // Use the prepared statement helper from config.php
        $sql = "SELECT * FROM users WHERE email = ? AND verification_code = ? AND is_verified = 0";
        $stmt = prepareAndExecute($sql, [$email, $code]);

        if ($stmt && $result = $stmt->get_result()) {
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // Check if code is not expired (30 minutes)
                $sent_at = new DateTime($user['verification_sent_at']);
                $now = new DateTime();
                $interval = $now->diff($sent_at);
                $minutes = $interval->days * 24 * 60 + $interval->h * 60 + $interval->i;

                if ($minutes <= 30) {
                    // Mark user as verified
                    $updateSql = "UPDATE users SET is_verified = 1, verification_code = NULL, verification_sent_at = NULL WHERE email = ?";
                    $updateStmt = prepareAndExecute($updateSql, [$email]);

                    if ($updateStmt) {
                        echo "<script>
                            Swal.fire({
                                icon: 'success',
                                title: 'Verification Successful!',
                                text: 'Your email has been verified. You can now log in to your account.',
                                confirmButtonColor: '#2C6E8A',
                                allowOutsideClick: false,
                                allowEscapeKey: false
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.href = 'login.php';
                                }
                            });
                        </script>";
                    }
                } else {
                    echo "<script>
                        Swal.fire({
                            icon: 'error',
                            title: 'Code Expired',
                            text: 'The verification code has expired. Please request a new one.',
                            confirmButtonColor: '#2C6E8A',
                            showCancelButton: true,
                            cancelButtonText: 'Try Again',
                            confirmButtonText: 'Resend Code',
                            allowOutsideClick: false
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = 'resend-verification.php';
                            }
                        });
                    </script>";
                }
            } else {
                echo "<script>
                    Swal.fire({
                        icon: 'error',
                        title: 'Invalid Code',
                        text: 'The verification code or email is incorrect. Please try again.',
                        confirmButtonColor: '#2C6E8A'
                    });
                </script>";
            }
        }
    }
    ?>
</body>
</html>