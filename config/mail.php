<?php
/**
 * Mail Configuration
 * 
 * This file contains the configuration for sending emails through PHPMailer
 */

// SMTP Configuration
$mail_config = [
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_secure' => 'tls',
    'smtp_auth' => true,
    'smtp_username' => 'gdgarcia00410@usep.edu.ph', // REPLACE WITH ACTUAL EMAIL
    'smtp_password' => 'emarwfuaalwbknfu', // REPLACE WITH APP PASSWORD (NOT REGULAR PASSWORD)
    'from_email' => 'gdgarcia00410@usep.edu.ph', // REPLACE WITH ACTUAL EMAIL
    'from_name' => 'Captain\'s Brew Cafe',
    'debug_level' => 0 // Set to 2 for verbose debugging
];

/**
 * IMPORTANT:
 * 
 * 1. For Gmail, you need to:
 *    - Enable 2-Step Verification on your Google account
 *    - Create an App Password (Google Account → Security → App Passwords)
 *    - Use that App Password here, not your regular Gmail password
 * 
 * 2. For other providers, adjust the SMTP settings accordingly
 */
?> 