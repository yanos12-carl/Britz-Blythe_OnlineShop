<?php
require __DIR__ . '/../config/database.php';
try {
    $db = get_db();
    $db->exec('ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_image VARCHAR(255) DEFAULT NULL AFTER registered_at');
    echo "✅ profile_image column added successfully!\n";
    echo "Test: http://localhost/ecommerce/public/profile.php\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>

