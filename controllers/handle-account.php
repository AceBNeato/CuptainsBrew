<?php
session_start();

$config_path = __DIR__ . '..\..\config.php';

if (!file_exists($config_path)) {
    die("Error: config.php not found at $config_path. Please check the file path.");
}

require_once $config_path;
$errors = [];
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1; // Hardcoded for testing

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $contact = trim($_POST['contact'] ?? '');

    // Server-side validation
    if (strlen($username) < 3 || strlen($username) > 50) {
        $errors[] = ['field' => 'username', 'message' => 'Username must be 3–50 characters.'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = ['field' => 'email', 'message' => 'Invalid email format.'];
    }

    if ($password && strlen($password) < 8) {
        $errors[] = ['field' => 'password', 'message' => 'Password must be at least 8 characters.'];
    }

    if ($contact && !preg_match('/^\+?\d{10,20}$/', $contact)) {
        $errors[] = ['field' => 'contact', 'message' => 'Invalid phone number (10–20 digits, optional +).'];
    }

    // Check for unique username (excluding current user)
    $sql = "SELECT id FROM users WHERE username = ? AND id != ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $errors[] = ['field' => 'general', 'message' => 'Database error: Unable to prepare statement.'];
    } else {
        $stmt->bind_param('si', $username, $user_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = ['field' => 'username', 'message' => 'Username is already taken.'];
        }
        $stmt->close();
    }

    // Check for unique email (excluding current user)
    $sql = "SELECT id FROM users WHERE email = ? AND id != ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $errors[] = ['field' => 'general', 'message' => 'Database error: Unable to prepare statement.'];
    } else {
        $stmt->bind_param('si', $email, $user_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = ['field' => 'email', 'message' => 'Email is already in use.'];
        }
        $stmt->close();
    }

    if (empty($errors)) {
        // Prepare update query
        $updates = [];
        $params = [];
        $types = '';

        // Username
        $updates[] = 'username = ?';
        $params[] = $username;
        $types .= 's';

        // Email
        $updates[] = 'email = ?';
        $params[] = $email;
        $types .= 's';

        // Password (only if provided)
        if ($password) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $updates[] = 'password = ?';
            $params[] = $hashed_password;
            $types .= 's';
        }

        // Contact (allow NULL)
        $updates[] = 'contact = ?';
        $params[] = $contact ?: null;
        $types .= 's';

        // Add user_id for WHERE clause
        $params[] = $user_id;
        $types .= 'i';

        // Fixed SQL query construction
        $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $errors[] = ['field' => 'general', 'message' => 'Database error: Unable to prepare update statement.'];
        } else {
            $stmt->bind_param($types, ...$params);
            if ($stmt->execute()) {
                $_SESSION['account_success'] = 'Your profile has been updated successfully.';
            } else {
                $errors[] = ['field' => 'general', 'message' => 'Failed to update profile: ' . $conn->error];
            }
            $stmt->close();
        }
    }

    if (!empty($errors)) {
        $_SESSION['account_errors'] = $errors;
    }

    $conn->close();
    header('Location: /views/users/User-Account.php');
    exit;
}
?>