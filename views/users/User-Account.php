<?php
session_start();
require_once '../../config.php';

// Replace with actual user ID from session
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1; // Hardcoded for testing

// Fetch user data
$user = null;
$sql = "SELECT id, username, email, address, contact FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
}
$stmt->close();

// Handle form submission errors/success (set by handle-account.php)
$errors = isset($_SESSION['account_errors']) ? $_SESSION['account_errors'] : [];
$success = isset($_SESSION['account_success']) ? $_SESSION['account_success'] : '';
unset($_SESSION['account_errors'], $_SESSION['account_success']);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta http-equiv="X-UA-Compatible" content="ie=edge" />
  <title>My Account - Captain's Brew Cafe</title>
  <link rel="icon" href="/public/images/LOGO.png" sizes="any" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Poppins', sans-serif;
    }

    body {
      background: #f5f6f5;
      color: #4a3b2b;
      min-height: 100vh;
    }

    .header {
      display: flex;
      align-items: center;
      padding: 1rem 2rem;
      background: linear-gradient(135deg, #FFFAEE, #FFDBB5);
      box-shadow: 0 2px 5px rgba(74, 59, 43, 0.2);
      position: sticky;
      top: 0;
      z-index: 1000;
    }

    .back-home-btn {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      background: #2C6E8A;
      color: #fff;
      border: none;
      padding: 0.75rem 1.5rem;
      border-radius: 8px;
      font-size: 1rem;
      cursor: pointer;
      transition: background-color 0.3s, transform 0.2s;
      text-decoration: none;
    }

    .back-home-btn:hover {
      background: #235A73;
      transform: translateY(-2px);
    }

    .back-home-btn svg {
      width: 20px;
      height: 20px;
      fill: #fff;
    }

    .logo-section {
      margin-left: auto;
      margin-right: auto;
    }

    .logo-section img {
      width: 180px;
      transition: transform 0.3s;
    }

    .logo-section img:hover {
      transform: scale(1.05);
    }

    .account-container {
      padding: 2rem;
      max-width: 600px;
      margin: 0 auto;
    }

    .account-title {
      font-size: 2rem;
      color: #2C6E8A;
      margin-bottom: 1.5rem;
      text-align: center;
      font-weight: 600;
    }

    .account-card {
      background: #fff;
      border-radius: 12px;
      padding: 2rem;
      box-shadow: 0 8px 20px rgba(74, 59, 43, 0.1);
    }

    .form-group {
      margin-bottom: 1.5rem;
    }

    .form-group label {
      display: block;
      font-size: 0.95rem;
      color: #2C6E8A;
      font-weight: 500;
      margin-bottom: 0.5rem;
    }

    .form-group input {
      width: 100%;
      padding: 0.8rem;
      border: 1px solid #A9D6E5;
      border-radius: 6px;
      font-size: 0.95rem;
      color: #4a3b2b;
      transition: border-color 0.3s, box-shadow 0.3s;
    }

    .form-group input:focus {
      outline: none;
      border-color: #2C6E8A;
      box-shadow: 0 0 5px rgba(44, 110, 138, 0.3);
    }

    .form-group input.error {
      border-color: #e74c3c;
    }

    .error-message {
      color: #e74c3c;
      font-size: 0.85rem;
      margin-top: 0.3rem;
      display: none;
    }

    .save-btn {
      width: 100%;
      padding: 0.8rem;
      background: #2C6E8A;
      color: #fff;
      border: none;
      border-radius: 6px;
      font-size: 1rem;
      cursor: pointer;
      transition: background-color 0.3s, transform 0.2s;
    }

    .save-btn:hover {
      background: #235A73;
      transform: translateY(-2px);
    }

    @media (max-width: 768px) {
      .header {
        flex-direction: column;
        gap: 1rem;
      }

      .back-home-btn {
        align-self: flex-start;
      }

      .logo-section {
        margin: 0;
      }

      .account-container {
        padding: 1rem;
      }

      .account-card {
        padding: 1.5rem;
      }
    }
  </style>
</head>
<body>
  <header class="header">
    <a href="/views/users/User-Home.php" class="back-home-btn">
      Back to Home
    </a>
    <div class="logo-section">
      <img src="/public/images/LOGO.png" alt="Captain's Brew Cafe Logo" />
    </div>
  </header>

  <div class="account-container">
    <h1 class="account-title">My Account</h1>
    <div class="account-card">
      <?php if (!$user): ?>
        <p class="error-message" style="display: block; text-align: center;">User not found.</p>
      <?php else: ?>
        <form id="account-form" action="/controllers/handle-account.php" method="POST">
          <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" value="<?= htmlspecialchars($user['username']) ?>" required />
            <span class="error-message" id="username-error"></span>
          </div>
          <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required />
            <span class="error-message" id="email-error"></span>
          </div>
          <div class="form-group">
            <label for="password">New Password (leave blank to keep current)</label>
            <input type="password" id="password" name="password" placeholder="Enter new password" />
            <span class="error-message" id="password-error"></span>
          </div>
          <div class="form-group">
            <label for="address">Address</label>
            <input type="text" id="address" name="address" value="<?= htmlspecialchars($user['address'] ?? '') ?>" />
            <span class="error-message" id="address-error"></span>
          </div>
          <div class="form-group">
            <label for="contact">Contact Number</label>
            <input type="text" id="contact" name="contact" value="<?= htmlspecialchars($user['contact'] ?? '') ?>" />
            <span class="error-message" id="contact-error"></span>
          </div>
          <button type="submit" class="save-btn">Save Changes</button>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <script>
    const form = document.getElementById('account-form');
    const errors = <?= json_encode($errors) ?>;
    const success = <?= json_encode($success) ?>;

    // Display server-side errors or success
    if (errors.length > 0) {
      errors.forEach(error => {
        const field = error.field;
        const message = error.message;
        const errorElement = document.getElementById(`${field}-error`);
        const inputElement = document.getElementById(field);
        if (errorElement && inputElement) {
          errorElement.textContent = message;
          errorElement.style.display = 'block';
          inputElement.classList.add('error');
        }
      });
    } else if (success) {
      Swal.fire({
        icon: 'success',
        title: 'Profile Updated',
        text: success,
        timer: 2000,
        showConfirmButton: false
      });
    }

    // Client-side validation
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      let hasErrors = false;

      // Reset error states
      document.querySelectorAll('.error-message').forEach(el => {
        el.style.display = 'none';
        el.textContent = '';
      });
      document.querySelectorAll('input').forEach(el => el.classList.remove('error'));

      // Validate username
      const username = document.getElementById('username').value.trim();
      if (username.length < 3 || username.length > 50) {
        showError('username', 'Username must be 3–50 characters.');
        hasErrors = true;
      }

      // Validate email
      const email = document.getElementById('email').value.trim();
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(email)) {
        showError('email', 'Invalid email format.');
        hasErrors = true;
      }

      // Validate password (if provided)
      const password = document.getElementById('password').value;
      if (password && password.length < 8) {
        showError('password', 'Password must be at least 8 characters.');
        hasErrors = true;
      }

      // Validate address (if provided)
      const address = document.getElementById('address').value.trim();
      if (address && address.length > 255) {
        showError('address', 'Address cannot exceed 255 characters.');
        hasErrors = true;
      }

      // Validate contact (if provided)
      const contact = document.getElementById('contact').value.trim();
      const contactRegex = /^\+?\d{10,20}$/;
      if (contact && !contactRegex.test(contact)) {
        showError('contact', 'Invalid phone number (10–20 digits, optional +).');
        hasErrors = true;
      }

      if (!hasErrors) {
        form.submit();
      }
    });

    function showError(field, message) {
      const errorElement = document.getElementById(`${field}-error`);
      const inputElement = document.getElementById(field);
      errorElement.textContent = message;
      errorElement.style.display = 'block';
      inputElement.classList.add('error');
    }
  </script>
</body>
</html>