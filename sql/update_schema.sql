
-- Add comment to explain the changes
-- This update adds location tracking for delivery with OpenStreetMap integration
-- The delivery fee starts at 30.00 PHP and increases by 10.00 PHP for every 10km distance

-- Add cancellation_reason column to orders table
ALTER TABLE orders ADD COLUMN cancellation_reason VARCHAR(255) DEFAULT NULL;

-- Create order_cancellations table
CREATE TABLE IF NOT EXISTS order_cancellations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    reason VARCHAR(255) NOT NULL,
    is_admin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- Create order_status_logs table
CREATE TABLE IF NOT EXISTS order_status_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    status VARCHAR(50) NOT NULL,
    updated_by INT NOT NULL,
    updated_by_type ENUM('admin', 'rider', 'system') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
); 