-- Add is_viewed column to orders table if it doesn't exist
ALTER TABLE `orders` ADD COLUMN IF NOT EXISTS `is_viewed` TINYINT(1) NOT NULL DEFAULT 0;

-- Add updated_at column to orders table if it doesn't exist
ALTER TABLE `orders` ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP;

-- Create notifications table if it doesn't exist
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) DEFAULT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    order_id INT DEFAULT NULL,
    status VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (user_id),
    INDEX (order_id)
);

-- Add title column if it doesn't exist
ALTER TABLE notifications 
ADD COLUMN IF NOT EXISTS title VARCHAR(255) DEFAULT NULL AFTER user_id; 