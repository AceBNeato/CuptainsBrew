<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email - Cuptain's Brew</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../../css/styles.css"> <!-- Adjust path to your CSS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="container">
        <h2>Verify Your Email</h2>
        <p>Enter the 6-digit code sent to your email.</p>
        <form id="verify-form" method="POST" action="">
            <div class="input-group">
                <i class="fas fa-key"></i>
                <input type="text" name="code" placeholder="Verification Code" required pattern="\d{6}" title="Enter a 6-digit code">
            </div>
            <div class="input-group">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" placeholder="Email" required>
            </div>
            <button type="submit" class="btn">Verify</button>
        </form>
        <p>Didn't receive a code? <a href="resend_verification.php">Resend Code</a></p>
    </div>

    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        require_once '../../config/database.php'; // Adjust path to your DB config

        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $code = filter_input(INPUT_POST, 'code', FILTER_SANITIZE_STRING);

        // Check if email and code match an unverified user
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND verification_code = ? AND is_verified = 0");
        $stmt->execute([$email, $code]);
        $user = $stmt->fetch();

        if ($user) {
            // Check if code is not expired (e.g., within 30 minutes)
            $sent_at = new DateTime($user['verification_sent_at']);
            $now = new DateTime();
            $interval = $now->diff($sent_at);
            $minutes = $interval->days * 24 * 60 + $interval->h * 60 + $interval->i;

            if ($minutes <= 30) {
                // Mark user as verified
                $stmt = $pdo->prepare("UPDATE users SET is_verified = 1, verification_code = NULL, verification_sent_at = NULL WHERE email = ?");
                $stmt->execute([$email]);

                echo "<script>
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'Your email has been verified! You can now log in.',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        window.location.href = 'login.php';
                    });
                </script>";
            } else {
                echo "<script>
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Verification code has expired. Please request a new one.',
                        confirmButtonText: 'OK'
                    });
                </script>";
            }
        } else {
            echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Invalid code or email. Please try again.',
                    confirmButtonText: 'OK'
                });
            </script>";
        }
    }
    ?>
</body>
</html>