<?php
// Include the database configuration
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth_check.php';
requireAdmin();

// Ensure session is started
if (!isset($_SESSION)) {
    session_start();
}

// Verify admin is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['loggedin'])) {
    header('Location: /views/auth/login.php');
    exit();
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    header('Location: /views/admin/Admin-Riders.php?error=Invalid security token');
    exit();
}

// Get action
$action = isset($_POST['action']) ? $_POST['action'] : '';

// Handle different actions
switch ($action) {
    case 'add':
        addRider();
        break;
    case 'edit':
        editRider();
        break;
    case 'delete':
        deleteRider();
        break;
    default:
        header('Location: /views/admin/Admin-Riders.php?error=Invalid action');
        exit();
}

// Function to add a new rider
function addRider() {
    global $conn;
    
    // Validate input
    $name = isset($_POST['rider_name']) ? trim($_POST['rider_name']) : '';
    $contact = isset($_POST['rider_contact']) ? trim($_POST['rider_contact']) : '';
    $password = isset($_POST['rider_password']) ? trim($_POST['rider_password']) : '';
    
    if (empty($name) || empty($contact) || empty($password)) {
        header('Location: /views/admin/Admin-Riders.php?error=Name, contact, and password are required');
        exit();
    }
    
    // Sanitize input
    $name = $conn->real_escape_string($name);
    $contact = $conn->real_escape_string($contact);
    
    // Check if contact already exists
    $check_query = "SELECT COUNT(*) as count FROM riders WHERE contact = '$contact'";
    $result = $conn->query($check_query);
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        header('Location: /views/admin/Admin-Riders.php?error=A rider with this contact number already exists');
        exit();
    }
    
    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert rider into database
    $query = "INSERT INTO riders (name, contact, password, created_at) VALUES ('$name', '$contact', '$hashed_password', NOW())";
    
    if ($conn->query($query)) {
        header('Location: /views/admin/Admin-Riders.php?success=Rider added successfully');
        exit();
    } else {
        header('Location: /views/admin/Admin-Riders.php?error=Failed to add rider: ' . $conn->error);
        exit();
    }
}

// Function to edit a rider
function editRider() {
    global $conn;
    
    // Validate input
    $rider_id = isset($_POST['rider_id']) ? (int)$_POST['rider_id'] : 0;
    $name = isset($_POST['rider_name']) ? trim($_POST['rider_name']) : '';
    $contact = isset($_POST['rider_contact']) ? trim($_POST['rider_contact']) : '';
    $password = isset($_POST['rider_password']) ? trim($_POST['rider_password']) : '';
    
    if ($rider_id <= 0 || empty($name) || empty($contact)) {
        header('Location: /views/admin/Admin-Riders.php?error=Invalid rider data');
        exit();
    }
    
    // Sanitize input
    $name = $conn->real_escape_string($name);
    $contact = $conn->real_escape_string($contact);
    
    // Check if contact already exists for another rider
    $check_query = "SELECT COUNT(*) as count FROM riders WHERE contact = '$contact' AND id != $rider_id";
    $result = $conn->query($check_query);
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        header('Location: /views/admin/Admin-Riders.php?error=Another rider with this contact number already exists');
        exit();
    }
    
    // Update rider in database
    if (!empty($password)) {
        // Hash the new password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $query = "UPDATE riders SET name = '$name', contact = '$contact', password = '$hashed_password' WHERE id = $rider_id";
    } else {
        // Keep existing password
        $query = "UPDATE riders SET name = '$name', contact = '$contact' WHERE id = $rider_id";
    }
    
    if ($conn->query($query)) {
        header('Location: /views/admin/Admin-Riders.php?success=Rider updated successfully');
        exit();
    } else {
        header('Location: /views/admin/Admin-Riders.php?error=Failed to update rider: ' . $conn->error);
        exit();
    }
}

// Function to delete a rider
function deleteRider() {
    global $conn;
    
    // Validate input
    $rider_id = isset($_POST['rider_id']) ? (int)$_POST['rider_id'] : 0;
    
    if ($rider_id <= 0) {
        header('Location: /views/admin/Admin-Riders.php?error=Invalid rider ID');
        exit();
    }
    
    // Check if rider has active orders
    $check_query = "SELECT COUNT(*) as active_count FROM orders 
                   WHERE rider_id = $rider_id 
                   AND status IN ('Assigned', 'Out for Delivery')";
    $result = $conn->query($check_query);
    $row = $result->fetch_assoc();
    
    if ($row['active_count'] > 0) {
        header('Location: /views/admin/Admin-Riders.php?error=Cannot delete rider with active orders');
        exit();
    }
    
    // Delete rider from database
    $query = "DELETE FROM riders WHERE id = $rider_id";
    
    if ($conn->query($query)) {
        // Update any orders with this rider to have no rider
        $update_orders = "UPDATE orders SET rider_id = NULL WHERE rider_id = $rider_id";
        $conn->query($update_orders);
        
        header('Location: /views/admin/Admin-Riders.php?success=Rider deleted successfully');
        exit();
    } else {
        header('Location: /views/admin/Admin-Riders.php?error=Failed to delete rider: ' . $conn->error);
        exit();
    }
}
?> 