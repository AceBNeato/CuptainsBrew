<?php

$db_host = 'localhost';
$db_user = 'root';
$db_pass = ''; // Default XAMPP/WAMP password (change if needed)
$db_name = 'cafe_db';

// Define cafe location constants
define('CAFE_LOCATION_LAT', 7.4478); // Tagum City coordinates
define('CAFE_LOCATION_LON', 125.8078); // Tagum City coordinates

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    // Connect to MySQL (without selecting DB first)
    $conn = new mysqli($db_host, $db_user, $db_pass);
    if ($conn->connect_error) {
        die("MySQL connection failed: " . $conn->connect_error);
    }

    // Create database if not exists
    if (!$conn->query("CREATE DATABASE IF NOT EXISTS $db_name")) {
        die("Error creating database: " . $conn->error);
    }

    // Select the database
    if (!$conn->select_db($db_name)) {
        die("Cannot use database: " . $conn->error);
    }

    // Define tables in CORRECT ORDER (parents first, then children)
    $queries = [
        // Categories (independent)
        "CREATE TABLE IF NOT EXISTS categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL UNIQUE
        ) ENGINE=InnoDB",

        // Products (depends on categories)
        "CREATE TABLE IF NOT EXISTS products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            category_id INT NOT NULL,
            item_name VARCHAR(255) NOT NULL,
            item_description TEXT NOT NULL,
            item_price DECIMAL(10, 2) NOT NULL,
            item_image VARCHAR(255) NOT NULL,
            has_variation BOOLEAN DEFAULT FALSE,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
        ) ENGINE=InnoDB",



        "CREATE TABLE IF NOT EXISTS product_variations (
            id INT PRIMARY KEY AUTO_INCREMENT,
            product_id INT NOT NULL,
            variation_type ENUM('Hot', 'Iced') NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        ) ENGINE=InnoDB",


        // Roles table (for user role management)
        "CREATE TABLE IF NOT EXISTS roles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL UNIQUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB",

        // Users (independent, with role reference)
        "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(255) NOT NULL UNIQUE,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role_id INT NOT NULL DEFAULT 2, -- Default to regular user role
            verification_code VARCHAR(6),
            verification_sent_at DATETIME,
            is_verified BOOLEAN DEFAULT FALSE,
            address TEXT,
            contact VARCHAR(20),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            reset_token VARCHAR(255) NULL,
            reset_expires DATETIME NULL,
            FOREIGN KEY (role_id) REFERENCES roles(id)
        ) ENGINE=InnoDB",

        // Remember Tokens (depends on users)
        "CREATE TABLE IF NOT EXISTS remember_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            token VARCHAR(128) NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX (token)
        ) ENGINE=InnoDB",

        // Cart (depends on users and products)
        "CREATE TABLE IF NOT EXISTS cart (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            variation VARCHAR(50) NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        ) ENGINE=InnoDB",

        // Riders (independent)
        "CREATE TABLE IF NOT EXISTS riders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            contact VARCHAR(20) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB",

        // Orders (depends on users and riders)
        "CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            total_amount DECIMAL(10, 2) NOT NULL,
            status VARCHAR(50) DEFAULT 'Pending',
            delivery_address TEXT NOT NULL,
            payment_method VARCHAR(50) NOT NULL,
            delivery_fee DECIMAL(10, 2) NOT NULL DEFAULT 30.00,
            lat VARCHAR(20) NULL,
            lon VARCHAR(20) NULL,
            rider_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (rider_id) REFERENCES riders(id) ON DELETE SET NULL
        ) ENGINE=InnoDB",

         // Order Cancellations (depends on orders and users)
        "CREATE TABLE IF NOT EXISTS order_cancellations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            user_id INT,
            reason VARCHAR(255) NOT NULL,
            custom_reason TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB",

        // Order_items (depends on orders and products)
        "CREATE TABLE IF NOT EXISTS order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT NOT NULL,
            price DECIMAL(10, 2) NOT NULL,
            variation VARCHAR(50) NULL,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        ) ENGINE=InnoDB",

        // Payments (depends on orders)
        "CREATE TABLE IF NOT EXISTS payments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            amount DECIMAL(10, 2) NOT NULL,
            method VARCHAR(50) NOT NULL,
            status VARCHAR(20) DEFAULT 'Pending',
            transaction_id VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
        ) ENGINE=InnoDB",

        // Reviews (depends on orders and users)
        "CREATE TABLE IF NOT EXISTS reviews (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            user_id INT,
            rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
            comment TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB"
    ];

    // Execute queries
    foreach ($queries as $query) {
        if (!$conn->query($query)) {
            throw new Exception("Table creation failed: " . $conn->error);
        }
    }

    // Insert default roles if they don't exist
    $conn->query("INSERT IGNORE INTO roles (id, name) VALUES 
        (1, 'admin'),
        (2, 'user')");

    // Insert default admin account if it doesn't exist
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $conn->query("INSERT IGNORE INTO users (username, email, password, role_id, is_verified) 
                 VALUES ('admin', 'admin@usep.edu.ph', '$admin_password', 1, 1)");

    // Insert default categories
    $conn->query("INSERT IGNORE INTO categories (id, name) VALUES
        (1, 'Coffee'),
        (2, 'Non-Coffee'),
        (3, 'Frappe'),
        (4, 'Milktea')");

    global $conn;
} catch (Exception $e) {
    error_log($e->getMessage(), 3, __DIR__ . '/error.log');
    die("Database error. Check error.log for details.");
}

// CSRF Protection Functions
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        error_log("CSRF token validation failed", 3, __DIR__ . '/error.log');
        header('Location: /views/auth/access-denied.php');
        exit();
    }
    return true;
}

// SQL Injection Prevention - Prepared Statement Helper
function prepareAndExecute($sql, $params = [], $types = '') {
    global $conn;
    
    try {
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        if (!empty($params)) {
            if (empty($types)) {
                $types = str_repeat('s', count($params)); // Default all to string
            }
            $stmt->bind_param($types, ...$params);
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        return $stmt;
    } catch (Exception $e) {
        error_log($e->getMessage(), 3, __DIR__ . '/error.log');
        return false;
    }
}

// Role checking helper function
function isAdmin($userId) {
    global $conn;
    $stmt = prepareAndExecute(
        "SELECT role_id FROM users WHERE id = ?", 
        [$userId], 
        'i'
    );
    if ($stmt && $result = $stmt->get_result()) {
        $user = $result->fetch_assoc();
        return $user && $user['role_id'] === 1;
    }
    return false;
}
?>
