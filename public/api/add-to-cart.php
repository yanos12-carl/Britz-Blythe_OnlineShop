<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$slug = $data['slug'] ?? $_POST['slug'] ?? '';
$quantity = max(1, (int)($data['quantity'] ?? $_POST['quantity'] ?? 1));
$variationItemId = $data['variation_item_id'] ?? $_POST['variation_item_id'] ?? null;

if ($slug) {
    // If variations provided, find the variation item
    // The product-view.js now sends variation_item_id directly if a variant is selected
    // If variations were sent as an array, you'd need to re-implement the lookup here.
    // For now, assuming variation_item_id is directly passed.
    // if ($variations) {
    //     $product = get_product_by_slug($slug);
    //     if ($product) {
    //         $variationItem = get_variation_item_by_combination($product['id'], $variations);
    //         $variationItemId = $variationItem ? $variationItem['id'] : null;
    //     }
    // }
    
    $result = add_to_cart_with_variation($slug, $quantity, $variationItemId);
    echo json_encode($result);
} else {
    echo json_encode(['success' => false, 'message' => 'Product slug is required']);
}
?>
