-- Create user_addresses table (missing from migration)
-- SQL Migration: Create User Addresses Table
-- Optimized for Britz Blythe E-commerce Platform
USE britz_blythe;

-- Drop table if it exists to allow for a clean re-run during development
-- DROP TABLE IF EXISTS user_addresses;

CREATE TABLE IF NOT EXISTS user_addresses (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    label VARCHAR(50) NOT NULL DEFAULT 'Home' COMMENT 'e.g., Home, Work, Office',
    recipient_name VARCHAR(255) NOT NULL COMMENT 'Full name of the receiver',
    phone_number VARCHAR(20) DEFAULT NULL,
    address TEXT NOT NULL COMMENT 'Street address, building, apartment',
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL COMMENT 'Province or State',
    zip_code VARCHAR(20) NOT NULL,
    is_default TINYINT(1) UNSIGNED DEFAULT 0 COMMENT '1 if primary shipping address',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes for performance
    INDEX idx_user_addresses_user_id (user_id),
    INDEX idx_user_addresses_default (user_id, is_default),
    
    -- Constraints for data integrity
    CONSTRAINT fk_user_addresses_user_id FOREIGN KEY (user_id) 
        REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Verify table created
SHOW CREATE TABLE user_addresses;
