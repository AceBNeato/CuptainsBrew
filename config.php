<?php



$db_host = 'localhost';
$db_user = 'root';
$db_pass = ''; // Default XAMPP/WAMP password (change if needed)
$db_name = 'cafe_db';

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
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
        ) ENGINE=InnoDB",

        // Users (independent, fixed syntax)
        "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(255) NOT NULL UNIQUE,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            verification_code VARCHAR(6),
            verification_sent_at DATETIME,
            is_verified BOOLEAN DEFAULT FALSE,
            address TEXT,
            contact VARCHAR(20),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            reset_token VARCHAR(64) DEFAULT NULL,
            reset_expires DATETIME DEFAULT NULL
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
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        ) ENGINE=InnoDB;",

        // Riders (independent)
        "CREATE TABLE IF NOT EXISTS riders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            contact VARCHAR(20) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB",

        // Orders (depends on users and riders, fixed foreign key)
        "CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            total_amount DECIMAL(10, 2) NOT NULL,
            status ENUM('Pending', 'Approved', 'Processing', 'Assigned', 'Out for Delivery', 'Delivered', 'Rejected', 'Canceled') DEFAULT 'Pending',
            delivery_address TEXT NOT NULL,
            payment_method ENUM('Card', 'COD', 'Digital Wallet') NOT NULL,
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
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        ) ENGINE=InnoDB",

        // Payments (depends on orders)
        "CREATE TABLE IF NOT EXISTS payments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            amount DECIMAL(10, 2) NOT NULL,
            method ENUM('Card', 'COD', 'Digital Wallet') NOT NULL,
            status ENUM('Pending', 'Completed', 'Failed') DEFAULT 'Pending',
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

    // Insert default categories with explicit IDs to match products
    $conn->query("INSERT INTO categories (id, name) VALUES
        (1, 'Coffee'),
        (2, 'Non-Coffee'),
        (3, 'Frappe'),
        (4, 'Milktea')
        ON DUPLICATE KEY UPDATE name = VALUES(name)");

   
    

    global $conn;
} catch (Exception $e) {
    error_log($e->getMessage(), 3, __DIR__ . '/error.log');
    die("Database error. Check error.log for details.");
}
?>
