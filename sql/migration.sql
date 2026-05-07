-- Migration to add role column and admin user

USE britz_blythe;

-- Update all users to 'user' role if missing
UPDATE users SET role = 'user' WHERE role IS NULL OR role = '';
-- Add role column if not exists
ALTER TABLE users ADD COLUMN IF NOT EXISTS role ENUM('user', 'admin') NOT NULL DEFAULT 'user';

-- Insert admin user if not exists
INSERT IGNORE INTO users (name, email, password, role) VALUES
('Admin', 'admin@britzblythe.local', '$2y$10$/TFXVam1xAJN20MwIhTlD.2JlL.HHd501buuxVOKedrzOlEFOG50C', 'admin');

-- Add deleted_at column to products for soft deletes
ALTER TABLE products ADD COLUMN IF NOT EXISTS deleted_at DATETIME DEFAULT NULL;

-- Add profile_image column to users
ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_image VARCHAR(255) DEFAULT NULL;

-- Add shipping details to orders for historical snapshots
ALTER TABLE orders 
ADD COLUMN IF NOT EXISTS recipient_name VARCHAR(255) AFTER user_id,
ADD COLUMN IF NOT EXISTS address TEXT AFTER recipient_name,
ADD COLUMN IF NOT EXISTS city VARCHAR(100) AFTER address,
ADD COLUMN IF NOT EXISTS state VARCHAR(100) AFTER city,
ADD COLUMN IF NOT EXISTS zip_code VARCHAR(20) AFTER state,
ADD COLUMN IF NOT EXISTS phone_number VARCHAR(20) AFTER zip_code;

-- Add billing details to orders
ALTER TABLE orders
ADD COLUMN IF NOT EXISTS billing_recipient_name VARCHAR(255) AFTER phone_number,
ADD COLUMN IF NOT EXISTS billing_address TEXT AFTER billing_recipient_name,
ADD COLUMN IF NOT EXISTS billing_city VARCHAR(100) AFTER billing_address,
ADD COLUMN IF NOT EXISTS billing_state VARCHAR(100) AFTER billing_city,
ADD COLUMN IF NOT EXISTS billing_zip_code VARCHAR(20) AFTER billing_state,
ADD COLUMN IF NOT EXISTS billing_phone_number VARCHAR(20) AFTER billing_zip_code;

-- Add is_default column to users for default address preference
ALTER TABLE users ADD COLUMN IF NOT EXISTS is_default TINYINT(1) NOT NULL DEFAULT 1;

-- Create user_addresses table for multiple saved addresses
CREATE TABLE IF NOT EXISTS user_addresses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    label VARCHAR(50) DEFAULT 'Home', -- e.g., 'Work', 'Home', 'Office'
    recipient_name VARCHAR(255) NOT NULL,
    phone_number VARCHAR(20),
    address TEXT NOT NULL,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL,
    zip_code VARCHAR(20) NOT NULL,
    is_default TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Add shipping_cost to orders for historical record
ALTER TABLE orders ADD COLUMN IF NOT EXISTS shipping_cost DECIMAL(10, 2) DEFAULT 0.00 AFTER phone_number;
-- IMPORTANT: After making changes to this file, ensure you run the migration script.
