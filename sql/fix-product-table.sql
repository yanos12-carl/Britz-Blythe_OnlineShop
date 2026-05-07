-- Fix for "Unknown column" errors in the products table
USE britz_blythe;

ALTER TABLE products 
ADD COLUMN IF NOT EXISTS excerpt TEXT AFTER image,
ADD COLUMN IF NOT EXISTS deleted_at DATETIME DEFAULT NULL;

DESCRIBE products;