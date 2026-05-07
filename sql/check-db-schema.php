<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

try {
    $db = get_db();
    if (!$db) {
        die("❌ Could not connect to the database.");
    }

    $query = $db->query("SHOW COLUMNS FROM users LIKE 'address'");
    $column = $query->fetch();

    if ($column) {
        echo "✅ Verification Successful: The 'address' column exists in the 'users' table.<br>";
        echo "Type: " . $column['Type'] . "<br>";
    } else {
        echo "❌ Verification Failed: The 'address' column was NOT found in the 'users' table.<br>";
        echo "Please run: mysql -u root -p britz_blythe < c:\\xampp\\htdocs\\ecommerce\\fix-db.sql";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}