<?php
/**
 * Product Data API for Quick View
 * Returns JSON structure expected by assets/js/cart.js
 */

try {
    // Move requires inside try-catch to catch potential missing file errors
    if (!file_exists(__DIR__ . '/../../config/config.php') || !file_exists(__DIR__ . '/../../includes/functions.php')) {
        header('HTTP/1.1 500 Internal Server Error');
        die(json_encode(['success' => false, 'message' => 'System files missing.']));
    }

    require_once __DIR__ . '/../../config/config.php';
    require_once __DIR__ . '/../../includes/functions.php';

    header('Content-Type: application/json');

    $slug = $_GET['slug'] ?? '';

    if (empty($slug)) {
        throw new Exception('Product identifier is missing.');
    }

    $product = get_product_by_slug($slug);

    if (!$product) {
        throw new Exception('Product not found.');
    }

    $finalImage = resolve_asset_url($product['image']);

    echo json_encode([
        'success' => true,
        'product' => [
            'slug'            => $product['slug'],
            'name'            => $product['name'],
            'category'        => ucfirst($product['category']),
            'formatted_price' => format_price((float)$product['price']),
            'excerpt'         => $product['excerpt'],
            'image'           => $finalImage,
            'stock'           => (int)$product['stock']
        ]
    ]);

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}