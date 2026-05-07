<?php
// Check if orders table has required columns for get_user_orders()
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$db = get_db();
if (!$db) {
    die("❌ Database connection failed\n");
}

echo "🔍 Checking orders table columns for profile/orders functionality...\n\n";

$required = [
    'recipient_name', 'address', 'city', 'state', 'zip_code', 'phone_number',
    'billing_recipient_name', 'billing_address', 'billing_city', 'billing_state', 
    'billing_zip_code', 'billing_phone_number'
];
$missing = [];

foreach ($required as $col) {
    $stmt = $db->prepare("SHOW COLUMNS FROM orders LIKE ?");
    $stmt->execute([$col]);
    if (!$stmt->fetch()) {
        $missing[] = $col;
        echo "❌ MISSING: $col\n";
    } else {
        echo "✅ EXISTS: $col\n";
    }
}

if (empty($missing)) {
    echo "\n🎉 All order columns present - profile.php and orders.php should work!\n";
    echo "\n💡 Test: http://localhost/ecommerce/public/profile.php\n";
} else {
    echo "\n⚠️  MISSING: " . implode(', ', $missing) . "\n";
    echo "💡 Run: run-orders-fix.bat\n";
}

echo "\n--- Orders table test query ---\n";
try {
    $test_orders = get_user_orders('test@example.com'); // Won't find user but tests query structure
    echo "✅ get_user_orders() query executes successfully\n";
} catch (Exception $e) {
    echo "❌ get_user_orders() ERROR: " . $e->getMessage() . "\n";
}
?>
