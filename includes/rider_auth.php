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
    // Check if all required session variables exist
    if (!isset($_SESSION['rider_id']) || !isset($_SESSION['rider_loggedin']) || $_SESSION['rider_loggedin'] !== true) {
        return false;
    }
    
    // Check for session timeout (30 minutes)
    if (isset($_SESSION['rider_last_activity']) && (time() - $_SESSION['rider_last_activity'] > 1800)) {
        // Session expired, logout rider
        logoutRider();
        return false;
    }
    
    // Check for IP change (potential session hijacking)
    if (isset($_SESSION['rider_ip']) && $_SESSION['rider_ip'] !== $_SERVER['REMOTE_ADDR']) {
        // IP changed, logout rider
        logoutRider();
        return false;
    }
    
    // Update last activity time
    $_SESSION['rider_last_activity'] = time();
    
    return true;
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
    // Regenerate session ID to prevent session fixation attacks
    session_regenerate_id(true);
    
    $_SESSION['rider_id'] = $rider_id;
    $_SESSION['rider_name'] = $rider_name;
    $_SESSION['rider_loggedin'] = true;
    $_SESSION['rider_login_time'] = time();
    $_SESSION['rider_last_activity'] = time();
    $_SESSION['rider_ip'] = $_SERVER['REMOTE_ADDR'];
    
    // Log successful login
    logRiderActivity($rider_id, "Successful login");
    
    // Reset failed login attempts
    resetFailedLoginAttempts($_SERVER['REMOTE_ADDR']);
}

/**
 * Logout rider
 * 
 * @return void
 */
function logoutRider() {
    // Log the logout if rider was logged in
    if (isset($_SESSION['rider_id'])) {
        logRiderActivity($_SESSION['rider_id'], "Logout");
    }
    
    // Unset rider session variables
    unset($_SESSION['rider_id']);
    unset($_SESSION['rider_name']);
    unset($_SESSION['rider_loggedin']);
    unset($_SESSION['rider_login_time']);
    unset($_SESSION['rider_last_activity']);
    unset($_SESSION['rider_ip']);
    
    // Regenerate session ID
    session_regenerate_id(true);
    
    // Redirect to login page
    header('Location: /views/riders/login.php');
    exit();
}

/**
 * Authenticate rider with contact number and password
 * 
 * @param string $contact Contact number
 * @param string $password Password
 * @return array|false Rider data if authenticated, false otherwise
 */
function authenticateRider($contact, $password = null) {
    global $conn;
    
    // Check for too many failed login attempts
    if (tooManyFailedAttempts($_SERVER['REMOTE_ADDR'])) {
        logRiderActivity('unknown', "Login blocked due to too many failed attempts from IP: {$_SERVER['REMOTE_ADDR']}");
        return false;
    }
    
    // Sanitize input
    $contact = $conn->real_escape_string(trim($contact));
    
    // Query database
    $query = "SELECT * FROM riders WHERE contact = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $contact);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $rider = $result->fetch_assoc();
        
        // If password is provided and password field exists in database, verify it
        if ($password !== null && isset($rider['password'])) {
            if (!password_verify($password, $rider['password'])) {
                // Password verification failed
                recordFailedLoginAttempt($_SERVER['REMOTE_ADDR'], $contact);
                logRiderActivity($rider['id'], "Failed login attempt - incorrect password");
                return false;
            }
        }
        
        return $rider;
    }
    
    // Record failed attempt if contact number doesn't exist
    recordFailedLoginAttempt($_SERVER['REMOTE_ADDR'], $contact);
    logRiderActivity('unknown', "Failed login attempt with contact: $contact");
    
    return false;
}

/**
 * Record failed login attempt
 * 
 * @param string $ip IP address
 * @param string $contact Contact attempted
 * @return void
 */
function recordFailedLoginAttempt($ip, $contact) {
    global $conn;
    
    // Clean up old attempts (older than 30 minutes)
    $cleanup = "DELETE FROM login_attempts WHERE attempt_time < DATE_SUB(NOW(), INTERVAL 30 MINUTE)";
    $conn->query($cleanup);
    
    // Record this attempt
    $stmt = $conn->prepare("INSERT INTO login_attempts (ip_address, username, attempt_time) VALUES (?, ?, NOW())");
    $stmt->bind_param("ss", $ip, $contact);
    $stmt->execute();
}

/**
 * Check if there are too many failed login attempts
 * 
 * @param string $ip IP address
 * @return bool True if too many attempts, false otherwise
 */
function tooManyFailedAttempts($ip) {
    global $conn;
    
    // Check for 5 or more failed attempts in the last 15 minutes
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM login_attempts WHERE ip_address = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
    $stmt->bind_param("s", $ip);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['count'] >= 5;
}

/**
 * Reset failed login attempts
 * 
 * @param string $ip IP address
 * @return void
 */
function resetFailedLoginAttempts($ip) {
    global $conn;
    
    $stmt = $conn->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
    $stmt->bind_param("s", $ip);
    $stmt->execute();
}

/**
 * Log rider activity
 * 
 * @param int|string $rider_id Rider ID or 'unknown'
 * @param string $activity Activity description
 * @return void
 */
function logRiderActivity($rider_id, $activity) {
    global $conn;
    
    $ip = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
    $stmt = $conn->prepare("INSERT INTO rider_activity_logs (rider_id, activity, ip_address, user_agent, log_time) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssss", $rider_id, $activity, $ip, $user_agent);
    $stmt->execute();
}
?> 