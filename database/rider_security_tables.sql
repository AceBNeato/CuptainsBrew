-- Create login attempts table
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    username VARCHAR(100) NOT NULL,
    attempt_time DATETIME NOT NULL,
    INDEX (ip_address),
    INDEX (attempt_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create rider activity logs table
CREATE TABLE IF NOT EXISTS rider_activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rider_id VARCHAR(50) NOT NULL, -- Can be rider ID or 'unknown'
    activity VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    log_time DATETIME NOT NULL,
    INDEX (rider_id),
    INDEX (log_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
