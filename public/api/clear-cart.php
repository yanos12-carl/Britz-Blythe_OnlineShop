<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

clear_cart();

$response = [
    'success' => true,
    'message' => 'Cart cleared successfully.',
    'cart_count' => 0
];

echo json_encode($response);