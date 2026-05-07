<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Invalid request.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cart_key = $_POST['cart_key'] ?? $_POST['slug'] ?? $_POST['product_id'] ?? '';
    $change = (int)($_POST['change'] ?? $_POST['quantity_change'] ?? 0);

    if ($cart_key && $change !== 0) {
        $result = update_cart_item_quantity($cart_key, $change);
        $response = array_merge($response, $result);
        
        // Calculate new totals for the UI
        $response['cart_count'] = array_sum($_SESSION['cart'] ?? []);
        $totals = calculate_cart_totals();
        $response['totals'] = [
            'subtotal' => format_price($totals['subtotal']),
            'tax' => format_price($totals['tax']),
            'total' => format_price($totals['total'])
        ];

        $item = array_values(array_filter(get_cart_items_with_variations(), fn($i) => $i['cart_key'] === $cart_key))[0] ?? null;
        $response['item_subtotal'] = $item ? format_price($item['subtotal']) : '$0.00';
    } else {
        $response['message'] = 'Missing parameters.';
    }
}

echo json_encode($response);
?>