-- Add New Product Feature - Database Migration
-- Run: mysql -u root -p britz_blythe < sql/add-new-product-fields.sql

USE britz_blythe;

-- Add new fields to products table (safe ALTER with IF NOT EXISTS checks)
ALTER TABLE products 
ADD COLUMN IF NOT EXISTS sku VARCHAR(100) UNIQUE AFTER stock,
ADD COLUMN IF NOT EXISTS weight DECIMAL(8,2) DEFAULT 0.00 AFTER sku,
ADD COLUMN IF NOT EXISTS dimensions VARCHAR(100) DEFAULT NULL AFTER weight,
ADD COLUMN IF NOT EXISTS status ENUM('draft', 'published') DEFAULT 'draft' AFTER dimensions,
ADD COLUMN IF NOT EXISTS video_url VARCHAR(500) DEFAULT NULL AFTER status;

-- Ensure images and variations JSON columns exist (for backward compat)
ALTER TABLE products 
ADD COLUMN IF NOT EXISTS images JSON DEFAULT NULL AFTER video_url,
ADD COLUMN IF NOT EXISTS variations JSON DEFAULT NULL AFTER images;

-- Add indexes for performance
ALTER TABLE products ADD INDEX idx_status (status);
ALTER TABLE products ADD INDEX idx_sku (sku);

-- Verify changes
SELECT 
    'Migration Complete!' as status,
    COUNT(*) as product_count,
    SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published_count
FROM products;

SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'britz_blythe' 
AND TABLE_NAME = 'products' 
AND COLUMN_NAME IN ('sku', 'weight', 'dimensions', 'status', 'video_url', 'images', 'variations')
ORDER BY ORDINAL_POSITION;
