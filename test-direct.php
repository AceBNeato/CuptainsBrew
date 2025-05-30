<?php
// Test direct inclusion of config.php

// Try different paths
$paths = [
    __DIR__ . '/config.php',
    __DIR__ . '/../config.php',
    dirname(__DIR__) . '/config.php',
    realpath(__DIR__ . '/config.php'),
    realpath(__DIR__ . '/../config.php')
];

echo "Current directory: " . __DIR__ . "\n";
echo "Testing paths for config.php:\n";

foreach ($paths as $path) {
    echo "Checking: $path ... ";
    if (file_exists($path)) {
        echo "EXISTS!\n";
        
        // Try to include it
        try {
            require_once $path;
            echo "Successfully included config.php from: $path\n";
            
            // Check if $conn is available
            if (isset($conn) && $conn instanceof mysqli) {
                echo "Database connection is available.\n";
            } else {
                echo "Database connection not available.\n";
            }
            
            break;
        } catch (Exception $e) {
            echo "Error including file: " . $e->getMessage() . "\n";
        }
    } else {
        echo "not found\n";
    }
} 