<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

$items = get_cart_items_with_variations();
$totals = calculate_cart_totals();

$response = [
    'success' => true,
    'items' => [],
    'total' => $totals['total'],
    'subtotal' => $totals['subtotal'],
    'count' => array_sum(array_column($items, 'quantity'))
];

foreach ($items as $item) {
    $response['items'][] = [
        'cart_key' => $item['cart_key'],
        'name' => $item['product']['name'],
        'price' => $item['price'],
        'quantity' => $item['quantity'],
        'image' => resolve_asset_url($item['product']['image']),
        'variation_details' => $item['variation_details']
    ];
}

echo json_encode($response);