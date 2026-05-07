USE britz_blythe;

UPDATE products SET image = 'assets/images/products/prod_69e99d32e04597.14262363.jpg' WHERE slug = 'artisan-leather-tote';
UPDATE products SET image = 'assets/images/products/prod_69e99cf4ecfbd9.45554262.jpg' WHERE slug = 'linen-bedroom-set';
UPDATE products SET image = 'assets/images/products/prod_69e99d0a90c052.66711779.jpg' WHERE slug = 'desktop-organizer';
UPDATE products SET image = 'assets/images/products/prod_69e99d872c5987.71962261.jpg' WHERE slug = 'travel-essentials-kit';

SELECT slug, image FROM products;
