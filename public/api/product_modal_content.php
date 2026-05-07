<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';

$slug = $_GET['slug'] ?? '';
$product = get_product_by_slug($slug);

if (!$product) {
    echo '<p class="alert alert-error">Product not found.</p>';
    exit;
}

$isOutOfStock = ($product['stock'] ?? 0) <= 0;
?>
<div class="product-modal-inner">
    <button type="button" class="modal-close" onclick="closeProductModal()" aria-label="Close">&times;</button>
    <div class="product-modal-body">
        <div class="product-modal-image">
            <img src="<?= resolve_asset_url($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
        </div>
        <div class="product-modal-details">
            <span class="eyebrow"><?= htmlspecialchars($product['category']) ?></span>
            <h2><?= htmlspecialchars($product['name']) ?></h2>
            <p class="product-price" style="font-size: 1.5rem; margin: 0.5rem 0;"><?= format_price($product['price']) ?></p>
            <p class="product-stock" style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 1rem;">
                Availability: <strong><?= (int)($product['stock'] ?? 0) ?></strong> in stock
            </p>
            <p style="line-height: 1.6; color: var(--text-muted); margin-bottom: 1.5rem;">
                <?= nl2br(htmlspecialchars($product['description'])) ?>
            </p>
            <?php if (!$isOutOfStock): ?>
                <form method="post" action="./add_to_cart.php" class="product-modal-form" onsubmit="event.preventDefault(); handleModalAddToCart(this); return false;">
                    <input type="hidden" name="slug" value="<?= htmlspecialchars($product['slug']) ?>">
                    <div class="qty-stepper" style="display: flex; align-items: center; gap: 0; margin-bottom: 1.25rem;">
                        <button type="button" class="qty-btn" onclick="var q=document.getElementById('modal-qty');if(q.value>1)q.value--;">−</button>
                        <input id="modal-qty" name="quantity" type="number" value="1" min="1" max="<?= (int)($product['stock'] ?? 99) ?>" readonly>
                        <button type="button" class="qty-btn" onclick="var q=document.getElementById('modal-qty');var m=parseInt(q.max)||99;if(parseInt(q.value)<m)q.value++;">+</button>
                    </div>
                    <button type="submit" class="button button-primary" style="width: 100%;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.5rem;"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                        Add to Cart
                    </button>
                </form>
            <?php else: ?>
                <span class="status-pill" style="display: inline-block; margin-top: 0.5rem;">Sold Out</span>
            <?php endif; ?>
        </div>
    </div>
</div>
