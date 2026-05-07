<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Invalid request.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $slug = $_POST['slug'] ?? '';
    $quantity = (int)($_POST['quantity'] ?? 1);
    $variationItemId = isset($_POST['variation_item_id']) ? (int)$_POST['variation_item_id'] : null;

    if ($slug && $quantity > 0) {
        $cart_result = add_to_cart_with_variation($slug, $quantity, $variationItemId);
        $response['success'] = $cart_result['success'];
        $response['message'] = $cart_result['message'];
        // Optionally, include updated cart count
        $response['cart_count'] = array_sum($_SESSION['cart'] ?? []);
    } else {
        $response['message'] = 'Product slug or quantity missing.';
    }
}

echo json_encode($response);
?>