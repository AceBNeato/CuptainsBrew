<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set cache control headers to prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Wed, 11 Jan 1984 05:00:00 GMT");

// Only declare functions if they don't already exist
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']) && isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
    }
}

// Skip isAdmin() declaration since it's already in config.php
// Use the existing isAdmin() function from config.php with user_id parameter

if (!function_exists('isUser')) {
    function isUser() {
        return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'user';
    }
}

if (!function_exists('requireLogin')) {
    function requireLogin() {
        if (!isLoggedIn()) {
            header("Location: /views/auth/login.php");
            exit();
        }
    }
}

if (!function_exists('requireAdmin')) {
    function requireAdmin() {
        if (!isLoggedIn()) {
            header("Location: /views/auth/login.php");
            exit();
        }
        
        // Check if user is admin and set the flag
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
            $_SESSION['is_admin'] = true;
        } else if (!isset($_SESSION['is_admin'])) {
            // If not set, check using the isAdmin function
            $_SESSION['is_admin'] = isAdmin($_SESSION['user_id']);
        }
        
        // Redirect if not admin
        if (!$_SESSION['is_admin']) {
            header("Location: /views/auth/access-denied.php");
            exit();
        }
    }
}

if (!function_exists('requireUser')) {
    function requireUser() {
        if (!isUser()) {
            header("Location: /views/auth/access-denied.php");
            exit();
        }
    }
} 