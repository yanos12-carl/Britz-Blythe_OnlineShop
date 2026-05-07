<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php'; // Composer Autoloader (optional)
}
require_once __DIR__ . '/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php $pageTitle = isset($pageTitle) ? $pageTitle . ' - ' . SITE_NAME : SITE_NAME; ?>
    <title><?= htmlspecialchars($pageTitle) ?></title>

    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/theme.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/components.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/responsive.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;700&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">

    <script src="<?= SITE_URL ?>/assets/js/maps-config.js"></script>
</head>
<body data-theme="light">
    <?php include __DIR__ . '/navbar.php'; ?>

    <!-- Off-Canvas Shopping Cart Drawer -->
    <div class="cart-drawer-overlay" id="cartDrawerOverlay"></div>
    <aside class="cart-drawer" id="cartDrawer">
        <div class="cart-drawer-header">
            <h2>Your Cart</h2>
            <button type="button" class="cart-drawer-close" aria-label="Close cart drawer">×</button>
        </div>
        <div class="cart-drawer-body" id="cartDrawerBody">
            <div style="padding: 2rem; text-align: center; color: var(--text-muted);">Loading cart items...</div>
        </div>
        <div class="cart-drawer-footer">
            <div class="cart-drawer-subtotal">
                <span class="cart-drawer-subtotal-label">Subtotal:</span>
                <span class="cart-drawer-subtotal-value" id="cartDrawerTotal">$0.00</span>
            </div>
            <div class="cart-drawer-actions">
                <a href="<?= SITE_URL ?>/public/cart.php" class="btn-view-cart">View Full Cart</a>
                <a href="<?= SITE_URL ?>/public/checkout.php" class="btn-checkout">Checkout</a>
            </div>
        </div>
    </aside>

    <main class="page-shell">

