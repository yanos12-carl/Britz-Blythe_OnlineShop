-- Fix for profile update SQL error: Add missing address columns to users table
USE britz_blythe;

-- Fix users table for Profile Updates
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS address TEXT,
ADD COLUMN IF NOT EXISTS phone_number VARCHAR(20),
ADD COLUMN IF NOT EXISTS city VARCHAR(100),
ADD COLUMN IF NOT EXISTS state VARCHAR(100),
ADD COLUMN IF NOT EXISTS zip_code VARCHAR(20);

-- Fix products table for Add Product/Archive functionality
ALTER TABLE products 
ADD COLUMN IF NOT EXISTS excerpt TEXT AFTER image,
ADD COLUMN IF NOT EXISTS deleted_at DATETIME DEFAULT NULL;

-- Final verification
DESCRIBE users;
DESCRIBE products;
