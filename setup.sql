-- Create the admin_users table to store administrator credentials
CREATE TABLE IF NOT EXISTS `admin_users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create the generated_domains table to log all domain suggestions for analytics
CREATE TABLE IF NOT EXISTS `generated_domains` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `keyword` VARCHAR(255) NOT NULL,
    `domain_name` VARCHAR(255) NOT NULL,
    `tld` VARCHAR(50) NOT NULL,
    `category` VARCHAR(255) NULL,
    `reason` TEXT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `user_agent` TEXT NOT NULL,
    `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add indexes for faster queries on commonly filtered columns
CREATE INDEX idx_keyword ON generated_domains(keyword);
CREATE INDEX idx_tld ON generated_domains(tld);
CREATE INDEX idx_timestamp ON generated_domains(timestamp);
