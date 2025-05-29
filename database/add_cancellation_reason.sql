-- Add cancellation_reason column to orders table if it doesn't exist
ALTER TABLE orders ADD COLUMN IF NOT EXISTS cancellation_reason VARCHAR(255) DEFAULT NULL;

-- Create order_cancellations table if it doesn't exist
CREATE TABLE IF NOT EXISTS order_cancellations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    user_id INT NOT NULL,
    reason VARCHAR(255) NOT NULL,
    is_admin TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
); 