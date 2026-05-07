<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

$items = get_cart_items();
$totals = calculate_cart_totals();
?>
<div class="mini-cart-header">
    <span>Your Cart (<?= array_sum(array_column($items, 'quantity')) ?>)</span>
</div>
<div class="mini-cart-items">
    <?php if (empty($items)): ?>
        <p class="empty-msg">Your cart is empty.</p>
    <?php else: ?>
        <?php foreach ($items as $item): ?>
            <div class="mini-cart-item">
                <img src="<?= resolve_asset_url($item['product']['image']) ?>" alt="<?= htmlspecialchars($item['product']['name']) ?>">
                <div class="item-details">
                    <span class="item-name"><?= htmlspecialchars($item['product']['name']) ?></span>
                    <div class="item-meta">
                        <div class="quantity-toggle">
                            <button class="qty-btn minus" data-product-slug="<?= $item['product']['slug'] ?>">−</button>
                            <span class="qty-val"><?= $item['quantity'] ?></span>
                            <button class="qty-btn plus" data-product-slug="<?= $item['product']['slug'] ?>">+</button>
                        </div>
                        <span class="item-price"><?= format_price($item['product']['price']) ?></span>
                        <button class="remove-item-btn" data-product-slug="<?= $item['product']['slug'] ?>" title="Remove item">×</button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php if (!empty($items)): ?>
    <div class="mini-cart-footer">
        <div class="mini-cart-total">Total: <span><?= format_price($totals['total']) ?></span></div>
        <a href="checkout.php" class="button button-primary">Checkout</a>
    </div>
<?php endif; ?>