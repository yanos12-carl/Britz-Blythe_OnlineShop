<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';
$slug = $_POST['slug'] ?? '';
if ($slug) {
    remove_from_cart($slug);
}
header('Location: /ecommerce/public/cart.php');
exit;
