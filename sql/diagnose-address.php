<?php
// Address Display Diagnostic Tool
echo "<h1>📍 Address Page Diagnostic</h1>";
echo "<pre>";

// 1. Check DB connection
try {
    $pdo = require __DIR__ . '/../config/database.php';
    echo "✅ DB Connection: OK\n";
} catch (Exception $e) {
    die("❌ DB Error: " . $e->getMessage());
}

// 2. Check table
$tables = $pdo->query("SHOW TABLES LIKE 'user_addresses'")->fetch();
if ($tables) {
    echo "✅ user_addresses table: EXISTS\n";
    
    // Count records
    $count = $pdo->query("SELECT COUNT(*) FROM user_addresses")->fetchColumn();
    echo "📊 Records: $count\n";
    
    // List records
    $stmt = $pdo->query("SELECT * FROM user_addresses LIMIT 5");
    echo "📋 Sample data:\n";
    print_r($stmt->fetchAll());
} else {
    echo "❌ user_addresses table: MISSING\n";
    echo "<p><strong>Fix: Run <code>sql/create-user-addresses-table.sql</code> in phpMyAdmin</strong></p>";
}

// 3. Check users
$stmt = $pdo->query("SELECT id, name, email FROM users LIMIT 3");
echo "\n👥 Users:\n";
print_r($stmt->fetchAll());

// 4. Test functions
require __DIR__ . '/../includes/functions.php';
if (function_exists('get_user_addresses')) {
    echo "\n🔧 Functions loaded OK\n";
} else {
    echo "❌ functions.php missing\n";
}

// 5. Add test data button
echo "\n\n<form method='post'>";
echo "<button type='submit' name='add_test'>➕ Add Test Address (User ID=1)</button>";
if ($_POST['add_test']) {
    $pdo->exec("INSERT INTO user_addresses (user_id, label, recipient_name, phone_number, address, city, state, zip_code, is_default) VALUES 
    (1, 'Home', 'Test User', '09123456789', '123 Sample St', 'Quezon City', 'Metro Manila', '1100', 1) 
    ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)");
    echo "<p style='color:green'>✅ Test address added!</p>";
}
echo "</form>";

echo "</pre>";
echo "<p><strong>Next: <a href='../public/address-book.php'>Visit Address Page</a></strong></p>";
?>

