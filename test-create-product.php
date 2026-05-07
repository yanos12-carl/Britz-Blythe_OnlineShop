<?php
// CLEAN VERSION: Test create_product() function 
// Run: http://localhost/ecommerce/test-create-product.php  
// Adds ?clean=1 to auto-delete test products after.

require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

function run_test($name, $testFn) {
    echo "<div class='test-result'><strong>$name:</strong> ";
    try {
        $result = $testFn();
        echo $result ? "✅ PASS" : "❌ FAIL";
        echo "</div>";
        return $result;
    } catch (Exception $e) {
        echo "❌ ERROR: " . htmlspecialchars($e->getMessage());
        return false;
    }
}

// Test data
$data = [
    'name' => 'Clean Test Product ' . time(),
    'category' => 'living',
    'price' => 29.99,
    'stock' => 100,
    'excerpt' => 'Clean test excerpt',
    'description' => 'Clean test description',
    'status' => 'draft',
    'slug' => 'clean-test-' . time()
];

// Auto-clean param
$should_cleanup = isset($_GET['clean']) && $_GET['clean'] == 1;

echo "<h1>🧪 Product Creation Tests</h1>";
echo "<p><a href='?clean=1'>Auto-clean test products →</a></p>";

// Test 1: Basic create
run_test("Basic Product Creation", function() use ($data) {
    $result = create_product($data);
    if ($result) {
        $product = get_product_by_slug($data['slug']);
        return $product !== null;
    }
    return false;
});

// Test 2: Validation failure
run_test("Validation Errors (empty name)", function() use ($data) {
    $invalid = $data;
    $invalid['name'] = '';
    $errors = validate_product_data($invalid);
    return !empty($errors['name']); // Should have error
});

// Test 3: SKU generation
run_test("SKU Generation", function() use ($data) {
    $sku = generate_sku($data['name'], 'test');
    return !empty($sku) && strlen($sku) > 5;
});

echo "<hr>";
echo "<p><strong>All tests complete.</strong> Check <a href='admin/products.php'>Admin Products</a></p>";
?>
<style>
.test-result { 
    padding: 1rem; 
    margin: 0.5rem 0; 
    border-left: 4px solid; 
    background: #f8f9fa; 
}
.test-result:matches([strong*="✅"]) { border-color: #10b981; }
.test-result:matches([strong*="❌"]) { border-color: #ef4444; background: #fef2f2; }
</style>

