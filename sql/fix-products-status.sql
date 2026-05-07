-- Fix products table for status filtering in admin/products.php
ALTER TABLE products ADD COLUMN IF NOT EXISTS status VARCHAR(20) DEFAULT 'published';

-- Set existing products to published
UPDATE products SET status = 'published' WHERE status IS NULL;

-- Verify
SELECT COUNT(*) as published_products FROM products WHERE status = 'published';
SELECT * FROM products LIMIT 5;
