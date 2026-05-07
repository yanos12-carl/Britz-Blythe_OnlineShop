-- Add 'image' column to the categories table
USE britz_blythe;

ALTER TABLE categories
ADD COLUMN IF NOT EXISTS image VARCHAR(255) DEFAULT NULL AFTER name;

-- Verify the column was added
DESCRIBE categories;