-- Migration: Add comprehensive product management features
-- Includes: Variants, Media, Status, SKU, Weight, Dimensions

-- Alter products table to add new fields
ALTER TABLE products 
ADD COLUMN status ENUM('draft', 'published') DEFAULT 'draft' AFTER deleted_at,
ADD COLUMN sku VARCHAR(100) UNIQUE AFTER status,
ADD COLUMN weight DECIMAL(8,3) DEFAULT NULL COMMENT 'Weight in kg' AFTER sku,
ADD COLUMN length DECIMAL(8,2) DEFAULT NULL COMMENT 'Length in cm' AFTER weight,
ADD COLUMN width DECIMAL(8,2) DEFAULT NULL COMMENT 'Width in cm' AFTER length,
ADD COLUMN height DECIMAL(8,2) DEFAULT NULL COMMENT 'Height in cm' AFTER width,
ADD COLUMN video_url VARCHAR(500) DEFAULT NULL AFTER height,
ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP AFTER video_url,
ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

-- Create product_variants table for product options (Size, Color, etc.)
CREATE TABLE IF NOT EXISTS product_variants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    sku VARCHAR(100) UNIQUE,
    variant_name VARCHAR(255) NOT NULL COMMENT 'e.g., "Red-M", "Blue-L"',
    price DECIMAL(9,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Create variant_attributes table for variant option values
CREATE TABLE IF NOT EXISTS variant_attributes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    variant_id INT NOT NULL,
    attribute_name VARCHAR(50) NOT NULL COMMENT 'e.g., "Color", "Size"',
    attribute_value VARCHAR(100) NOT NULL COMMENT 'e.g., "Red", "M"',
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE CASCADE,
    UNIQUE KEY unique_variant_attribute (variant_id, attribute_name)
);

-- Create product_media table for multiple images and videos
CREATE TABLE IF NOT EXISTS product_media (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    media_type ENUM('image', 'video') DEFAULT 'image',
    media_url VARCHAR(500) NOT NULL,
    thumbnail_url VARCHAR(500) DEFAULT NULL,
    sort_order INT DEFAULT 0,
    alt_text VARCHAR(255) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product_sort (product_id, sort_order)
);

-- Create shipping_options table for shipping settings
CREATE TABLE IF NOT EXISTS shipping_options (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    shipping_type ENUM('standard', 'express', 'overnight', 'free') DEFAULT 'standard',
    is_enabled TINYINT(1) DEFAULT 1,
    estimated_days INT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_product_shipping (product_id, shipping_type)
);

-- Create product_drafts table to save auto-save functionality
CREATE TABLE IF NOT EXISTS product_drafts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    draft_data LONGTEXT NOT NULL COMMENT 'JSON data of product draft',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
-- Add a unique constraint to ensure only one draft per user
ALTER TABLE product_drafts ADD UNIQUE KEY unique_user_draft (user_id);
-- Create indexes for better performance
CREATE INDEX idx_products_status ON products(status);
CREATE INDEX idx_products_sku ON products(sku);
CREATE INDEX idx_products_created_at ON products(created_at);
CREATE INDEX idx_product_variants_product ON product_variants(product_id);
CREATE INDEX idx_product_media_product ON product_media(product_id);
CREATE INDEX idx_shipping_options_product ON shipping_options(product_id);
CREATE INDEX idx_product_drafts_user ON product_drafts(user_id);

-- Add sold_count column if it doesn't exist
ALTER TABLE products ADD COLUMN sold_count INT DEFAULT 0 AFTER video_url;
