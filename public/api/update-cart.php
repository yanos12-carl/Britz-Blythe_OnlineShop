<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';
$slug = $_POST['slug'] ?? '';
$quantity = max(0, (int)($_POST['quantity'] ?? 0));
if ($slug) {
    update_cart($slug, $quantity);
}
header('Location: /ecommerce/public/cart.php');
exit;
