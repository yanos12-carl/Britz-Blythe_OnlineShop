<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();
$orderId = (int)($_GET['id'] ?? 0);
$order = get_order_by_id($orderId);
if (!$order) {
    header('Location: orders.php');
    exit;
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $newStatus = sanitize($_POST['status'] ?? '');
    if (update_order_status($orderId, $newStatus)) {
        $order['status'] = $newStatus;
        $message = 'Order status updated successfully.';
    } else {
        $message = 'Failed to update order status.';
    }
}

// Format the full address for display and maps search
$fullAddress = trim(($order['address'] ?? '') . ' ' . ($order['city'] ?? '') . ' ' . ($order['state'] ?? '') . ' ' . ($order['zip_code'] ?? ''));
$displayAddress = $order['address'] ? ($order['address'] . "\n" . $order['city'] . ", " . $order['state'] . " " . $order['zip_code']) : "No address provided.";
$mapUrl = "https://www.google.com/maps/search/?api=1&query=" . urlencode($fullAddress);

// Format the billing address for display
$displayBillingAddress = $order['billing_address'] 
    ? ($order['billing_address'] . "\n" . $order['billing_city'] . ", " . $order['billing_state'] . " " . $order['billing_zip_code']) 
    : "No billing address provided.";

$items = get_order_items($orderId);
?>
$current_page = 'orders';
$page_title = "Order #$orderId";
$page_description = "Order details and shipping information.";

ob_start();
?>

<?php if ($message): ?>
    <div class="alert alert-success" style="margin-bottom: 2rem;"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<div class="admin-card">
    <div class="card-header">
        <h2>Order Summary</h2>
        <div style="display: flex; gap: 1rem; align-items: center;">
            <span class="status-pill status-<?= strtolower(htmlspecialchars($order['status'])) ?>"><?= htmlspecialchars($order['status']) ?></span>
            <form method="POST" style="display: flex; gap: 0.5rem; align-items: center;">
                <select name="status" style="padding: 0.4rem 0.8rem !important; width: auto !important; font-size: 0.85rem;">
                    <?php $statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'completed']; ?>
                    <?php foreach ($statuses as $s): ?>
                        <option value="<?= $s ?>" <?= strtolower($order['status']) === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" name="update_status" class="button button-primary" style="padding: 0.4rem 1rem; font-size: 0.75rem;">Update</button>
            </form>
        </div>
    </div>

    <div class="order-details-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
        <div class="customer-info">
            <p><strong>Customer Details</strong></p>
            <p style="margin: 0.5rem 0;">Name: <?= htmlspecialchars($order['customer_name'] ?? 'Guest') ?></p>
            <p style="margin: 0.5rem 0;">Email: <?= htmlspecialchars($order['customer_email'] ?? 'n/a') ?></p>
            <p style="margin: 0.5rem 0;">Phone: <?= htmlspecialchars($order['customer_phone'] ?? 'n/a') ?></p>
            <p style="margin: 1.5rem 0 0.5rem;"><strong>Order Metadata</strong></p>
            <p style="margin: 0.5rem 0;">Date: <?= htmlspecialchars($order['created_at']) ?></p>
            <p style="margin: 0.5rem 0;">Total Amount: <?= format_price((float)$order['total']) ?></p>
        </div>
        
        <div class="shipping-info">
            <p><strong>Shipping Address</strong></p>
            <div class="address-card" style="display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <p style="margin: 0 0 0.5rem; font-weight: 600;"><?= htmlspecialchars($order['recipient_name'] ?? 'N/A') ?></p>
                    <pre class="address-content" style="margin-bottom: 0.5rem;"><?= htmlspecialchars($displayAddress) ?></pre>
                    <p style="margin: 0; font-size: 0.85rem; color: var(--text-muted);">Phone: <?= htmlspecialchars($order['phone_number'] ?? 'N/A') ?></p>
                </div>
                <?php if ($order['address']): ?>
                    <div style="margin-top: 1rem; border-top: 1px solid var(--border); padding-top: 1rem;">
                        <a href="<?= $mapUrl ?>" target="_blank" class="button button-ghost" style="width: 100%; text-align: center; font-size: 0.8rem;">📍 View Exact Location on Maps</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="billing-info">
            <p><strong>Billing Address</strong></p>
            <div class="address-card">
                <p style="margin: 0 0 0.5rem; font-weight: 600;"><?= htmlspecialchars($order['billing_recipient_name'] ?? 'N/A') ?></p>
                <pre class="address-content" style="margin-bottom: 0.5rem;"><?= htmlspecialchars($displayBillingAddress) ?></pre>
                <p style="margin: 0; font-size: 0.85rem; color: var(--text-muted);">Phone: <?= htmlspecialchars($order['billing_phone_number'] ?? 'N/A') ?></p>
            </div>
        </div>
    </div>

    <h3>Items</h3>
    <?php if (empty($items)): ?>
        <p>No items found for this order.</p>
    <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 60px;">Image</th>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Price</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td>
                        <img src="<?= resolve_asset_url($item['image']) ?>" alt="" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px; border: 1px solid var(--border);">
                    </td>
                    <td><?= htmlspecialchars($item['name'] ?? 'Product') ?></td>
                    <td><?= htmlspecialchars($item['quantity']) ?></td>
                    <td><?= format_price((float)$item['price']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
<?php
$page_content = ob_get_clean();
require 'admin-master.php';
?>
