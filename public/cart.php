<?php
require_once __DIR__ . '/../includes/header.php';
$items = get_cart_items_with_variations();
$totals = calculate_cart_totals();
?>
<section class="page-header">
    <div>
        <h1>Your Cart</h1>
        <p>Review items before checkout.</p>
    </div>
</section>
<?php if (empty($items)): ?>
    <div class="empty-state">
        <h2>Your cart is empty</h2>
        <p>Browse our products and add something special.</p>
        <a class="button button-primary" href="<?= SITE_URL ?>/products.php">Shop Now</a>
    </div>
<?php else: ?>
    <div class="cart-layout">
        <div class="cart-items">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding: 0 0.5rem;">
                <span style="font-weight: 600; color: var(--text-muted);"><?= count($items) ?> unique items in your bag</span>
                <button type="button" class="text-button" style="color: #ef4444; font-size: 0.85rem;" onclick="handleClearCart()">🗑 Clear Bag</button>
            </div>
            <?php foreach ($items as $item): ?>
                <article class="cart-item-card" data-key="<?= htmlspecialchars($item['cart_key']) ?>">
                    <div class="cart-item-image">
                        <img src="<?= resolve_asset_url($item['product']['image']) ?>" alt="<?= htmlspecialchars($item['product']['name']) ?>">
                    </div>
                    <div class="cart-item-content">
                        <div class="cart-item-header">
                            <h3><?= htmlspecialchars($item['product']['name']) ?><?= $item['variation_details'] ? ' <small>(' . htmlspecialchars($item['variation_details']) . ')</small>' : '' ?></h3>
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <span class="cart-item-price"><?= format_price($item['product']['price']) ?></span>
                                <form method="post" action="<?= SITE_URL ?>/public/api/update-cart.php" style="display: inline-flex;" onsubmit="return confirm('Are you sure you want to remove this item from your cart?');">
                                    <input type="hidden" name="slug" value="<?= htmlspecialchars($item['product']['slug']) ?>">
                                    <input type="hidden" name="quantity" value="0">
                                    <button type="submit" class="remove-item-btn" title="Remove item" aria-label="Remove <?= htmlspecialchars($item['product']['name']) ?> from cart">&times;</button>
                                </form>
                            </div>
                        </div>
                        <p class="cart-item-category"><?= htmlspecialchars($item['product']['category']) ?></p>
                        
                        <div class="cart-item-actions">
                            <div class="quantity-control">
                                <input type="hidden" name="cart_key" value="<?= htmlspecialchars($item['cart_key']) ?>">
                                <div class="stepper <?= (int)$item['quantity'] === 0 ? 'is-zero' : '' ?>">
                                    <button type="button" class="step-btn" onclick="handleCartUpdate('<?= $item['cart_key'] ?>', -1, this)">–</button>
                                    <label for="qty-<?= $item['cart_key'] ?>" class="sr-only">Quantity</label>
                                    <input id="qty-<?= $item['cart_key'] ?>" type="number" name="quantity" value="<?= $item['quantity'] ?>" min="0" readonly>
                                    <button type="button" class="step-btn" onclick="handleCartUpdate('<?= $item['cart_key'] ?>', 1, this)">+</button>
                                </div>
                            </div>
                            
                            <div class="cart-item-subtotal">
                                <span>Subtotal:</span>
                                <strong class="item-subtotal-display"><?= format_price($item['subtotal']) ?></strong>
                            </div>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <aside class="cart-summary">
            <div class="summary-card">
                <h2>Order Summary</h2>
                <div class="summary-row"><span>Subtotal</span><strong class="summary-subtotal"><?= format_price($totals['subtotal']) ?></strong></div>
                <div class="summary-row"><span>Estimated Tax</span><strong class="summary-tax"><?= format_price($totals['tax']) ?></strong></div>
                <div class="summary-row"><span>Shipping</span><strong>Free</strong></div>
                <div class="summary-divider"></div>
                <div class="summary-row total"><span>Total</span><strong class="summary-total"><?= format_price($totals['total']) ?></strong></div>
                
                <?php if (is_logged_in()): ?>
                    <a class="button button-primary checkout-btn" href="<?= SITE_URL ?>/public/checkout.php">Proceed to Checkout</a>
                <?php else: ?>
                    <a class="button button-primary checkout-btn" href="<?= SITE_URL ?>/public/login.php?redirect=checkout.php">Login to Checkout</a>
                <?php endif; ?>
                <p class="secure-checkout">🔒 Secure Checkout Guaranteed</p>
            </div>
        </aside>
    </div>
<?php endif; ?>

<script>
async function handleCartUpdate(cartKey, change, btn) {
    const stepper = btn.closest('.stepper');
    const input = stepper.querySelector('input');
    const card = btn.closest('.cart-item-card');
    
    stepper.classList.add('pulse-active');
    
    const formData = new FormData();
    formData.append('cart_key', cartKey);
    formData.append('change', change);

    try {
        const res = await fetch('<?= SITE_URL ?>/public/update_cart_quantity.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if (data.success) {
            if (data.message === 'Item removed.') {
                card.style.opacity = '0';
                card.style.transform = 'translateX(20px)';
                setTimeout(() => {
                    card.remove();
                    if (document.querySelectorAll('.cart-item-card').length === 0) location.reload();
                }, 300);
            } else {
                input.value = parseInt(input.value) + change;
                card.querySelector('.item-subtotal-display').textContent = data.item_subtotal;
            }

            // Update Summary
            document.querySelector('.summary-subtotal').textContent = data.totals.subtotal;
            document.querySelector('.summary-tax').textContent = data.totals.tax;
            document.querySelector('.summary-total').textContent = data.totals.total;
            
            // Update Navbar Badge
            const badge = document.querySelector('.cart-count');
            if (badge) badge.textContent = data.cart_count;

        } else {
            showNotification(data.message, 'error');
        }
    } catch (err) {
        console.error(err);
    } finally {
        setTimeout(() => stepper.classList.remove('pulse-active'), 300);
    }
}

async function handleClearCart() {
    if (!confirm('Are you sure you want to remove all items from your bag? This cannot be undone.')) return;

    try {
        const res = await fetch('<?= SITE_URL ?>/public/api/clear-cart.php', {
            method: 'POST'
        });
        const data = await res.json();

        if (data.success) {
            // Visual feedback before reload
            document.querySelector('.cart-layout').style.opacity = '0.5';
            document.querySelector('.cart-layout').style.pointerEvents = 'none';
            location.reload();
        } else {
            showNotification(data.message || 'Failed to clear cart.', 'error');
        }
    } catch (err) {
        console.error('Clear cart error:', err);
    }
}
</script>
<?php include __DIR__ . '/../includes/footer.php';
