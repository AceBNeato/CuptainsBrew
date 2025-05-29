<?php
// Start session if not already started
if (!isset($_SESSION)) {
    session_start();
}

/**
 * Check if rider is logged in
 * 
 * @return bool True if rider is logged in, false otherwise
 */
function isRiderLoggedIn() {
    return isset($_SESSION['rider_id']) && isset($_SESSION['rider_loggedin']) && $_SESSION['rider_loggedin'] === true;
}

/**
 * Require rider to be logged in
 * Redirects to login page if not logged in
 * 
 * @return void
 */
function requireRider() {
    if (!isRiderLoggedIn()) {
        header('Location: /views/riders/login.php');
        exit();
    }
}

/**
 * Get current rider ID
 * 
 * @return int|null Rider ID if logged in, null otherwise
 */
function getCurrentRiderId() {
    return isRiderLoggedIn() ? $_SESSION['rider_id'] : null;
}

/**
 * Login a rider
 * 
 * @param int $rider_id Rider ID
 * @param string $rider_name Rider name
 * @return void
 */
function loginRider($rider_id, $rider_name) {
    $_SESSION['rider_id'] = $rider_id;
    $_SESSION['rider_name'] = $rider_name;
    $_SESSION['rider_loggedin'] = true;
    $_SESSION['rider_login_time'] = time();
}

/**
 * Logout rider
 * 
 * @return void
 */
function logoutRider() {
    // Unset rider session variables
    unset($_SESSION['rider_id']);
    unset($_SESSION['rider_name']);
    unset($_SESSION['rider_loggedin']);
    unset($_SESSION['rider_login_time']);
    
    // Redirect to login page
    header('Location: /views/riders/login.php');
    exit();
}

/**
 * Authenticate rider with contact number
 * 
 * @param string $contact Contact number
 * @return array|false Rider data if authenticated, false otherwise
 */
function authenticateRider($contact) {
    global $conn;
    
    // Sanitize input
    $contact = $conn->real_escape_string(trim($contact));
    
    // Query database
    $query = "SELECT * FROM riders WHERE contact = '$contact'";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return false;
}
?> 