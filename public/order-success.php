<?php
require_once __DIR__ . '/../includes/header.php';

$orderId = (int)($_GET['order'] ?? 0);
$order = get_order_by_id($orderId);
$items = $order ? get_order_items($orderId) : [];

if (!$order) {
    header('Location: index.php');
    exit;
}

// Logic to suggest other products from the same categories
$suggestions = [];
if (!empty($items)) {
    $purchasedSlugs = array_column($items, 'slug');
    $uniqueCategories = [];
    
    foreach ($items as $item) {
        $prodDetails = get_product_by_slug($item['slug']);
        if ($prodDetails && !in_array($prodDetails['category'], $uniqueCategories)) {
            $uniqueCategories[] = $prodDetails['category'];
        }
    }

    foreach ($uniqueCategories as $cat) {
        $related = get_products($cat, '', 'newest', 4);
        foreach ($related as $rp) {
            if (!in_array($rp['slug'], $purchasedSlugs)) {
                $suggestions[$rp['slug']] = $rp;
            }
        }
    }
    $suggestions = array_slice($suggestions, 0, 3);
}
?>
<section class="page-header">
    <div>
        <h1>Thank You</h1>
        <p>Your order #<?= htmlspecialchars($orderId) ?> is confirmed.</p>
    </div>
</section>

<div class="container" style="max-width: 800px; margin-bottom: 4rem;">
    <div class="order-success account-card">
        <div style="text-align: center; margin-bottom: 2rem;">
            <div style="font-size: 3rem; margin-bottom: 1rem;">🎉</div>
            <p>We’re preparing your items now. A confirmation email will arrive shortly with shipping details.</p>
        </div>

        <div class="summary-divider"></div>

        <h3>Order Summary</h3>
        <div style="margin-bottom: 2rem;">
            <?php foreach ($items as $item): ?>
                <div style="display: flex; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid var(--border);">
                    <div>
                        <strong><?= htmlspecialchars($item['name']) ?></strong>
                        <div style="font-size: 0.85rem; color: var(--text-muted);">Qty: <?= (int)$item['quantity'] ?></div>
                    </div>
                    <span><?= format_price((float)$item['price'] * $item['quantity']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="summary-row total" style="margin-bottom: 2rem;">
            <span>Total Amount Paid</span>
            <strong><?= format_price((float)$order['total']) ?></strong>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 2rem; padding: 1.5rem; background: var(--surface-alt); border-radius: 16px;">
            <div>
                <strong style="display: block; font-size: 0.8rem; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.5rem;">Shipping To</strong>
                <p style="margin: 0; font-size: 0.9rem; line-height: 1.5;">
                    <?= htmlspecialchars($order['recipient_name']) ?><br>
                    <?= htmlspecialchars($order['address']) ?>, <?= htmlspecialchars($order['city']) ?><br>
                    <?= htmlspecialchars($order['state']) ?> <?= htmlspecialchars($order['zip_code']) ?>
                </p>
            </div>
            <div style="text-align: right;">
                <strong style="display: block; font-size: 0.8rem; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.5rem;">Order Status</strong>
                <span class="status-pill status-<?= strtolower($order['status']) ?>"><?= htmlspecialchars($order['status']) ?></span>
            </div>
        </div>

        <?php if (!empty($suggestions)): ?>
            <div class="summary-divider"></div>
            <div style="margin-top: 1rem;">
                <h3 style="margin-bottom: 1.5rem;">You Might Also Like</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1.5rem;">
                    <?php foreach ($suggestions as $sug): ?>
                        <div class="product-card" style="padding: 1rem; height: 100%; transition: transform 0.2s; cursor: pointer;" onclick="openProductModal('<?= htmlspecialchars($sug['slug']) ?>')">
                            <img src="<?= SITE_URL . '/' . $sug['image'] ?>" 
                                 alt="<?= htmlspecialchars($sug['name']) ?>" 
                                 style="width: 100%; aspect-ratio: 1; object-fit: cover; border-radius: 8px; margin-bottom: 0.75rem;">
                            <div style="font-size: 0.9rem; font-weight: 600; margin-bottom: 0.25rem;"><?= htmlspecialchars($sug['name']) ?></div>
                            <div style="font-size: 0.85rem; color: var(--accent);"><?= format_price($sug['price']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <style>
                .product-card:hover {
                    transform: translateY(-4px);
                    border-color: var(--accent);
                }
            </style>
        <?php endif; ?>

        <div style="text-align: center; margin-top: 3rem; display: flex; justify-content: center; gap: 1rem;">
            <a class="button button-primary" href="<?= SITE_URL ?>/products.php">Continue Shopping</a>
            <a class="button button-ghost" href="<?= SITE_URL ?>/orders.php">My Order History</a>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
