-- Drop category image column
USE britz_blythe;
ALTER TABLE categories DROP COLUMN IF EXISTS image;
DESCRIBE categories;
