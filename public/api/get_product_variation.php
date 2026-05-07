<?php
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$productSlug = $data['product_slug'] ?? null; // Changed to product_slug
$variations = $data['variations'] ?? [];

if (!$productSlug || empty($variations)) {
    echo json_encode(['success' => false, 'message' => 'Invalid request data: product_slug or variations missing.']);
    exit;
}

// Get product ID from slug
$product = get_product_by_slug($productSlug);
if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Product not found.']);
    exit;
}
$productId = (int)$product['id'];

if (!$productId) {
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit;
}

$variationItem = get_variation_item_by_combination($productId, $variations);

if ($variationItem) {
    echo json_encode([
        'success' => true,
        'variation' => [
            'id' => $variationItem['id'],
            'price' => $variationItem['price'],
            'price_formatted' => format_price($variationItem['price']),
            'stock' => $variationItem['stock'],
            'image' => $variationItem['image_path'] ?? null
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Variation combination not found']);
}
?>