<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Invalid request.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $slug = $_POST['slug'] ?? '';

    if ($slug) {
        remove_from_cart($slug);
        $response['success'] = true;
        $response['message'] = 'Item removed from cart.';
        $response['cart_count'] = array_sum($_SESSION['cart'] ?? []);
    } else {
        $response['message'] = 'Product slug missing.';
    }
}

echo json_encode($response);