<?php

class Item {

    // Database connection
    private static $conn;

    public static function connect() {
        if (!self::$conn) {
            self::$conn = new mysqli("localhost", "root", "", "cafe_db");
            if (self::$conn->connect_error) {
                die("Connection failed: " . self::$conn->connect_error);
            }
        }
        return self::$conn;
    }

    // Insert item into the database
    public static function create($name, $description, $price, $category, $imageName) {
        $conn = self::connect();
        $stmt = $conn->prepare("INSERT INTO items (name, description, price, category, image) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdss", $name, $description, $price, $category, $imageName);

        if ($stmt->execute()) {
            return true;
        } else {
            return false;
        }
        $stmt->close();
    }
}
