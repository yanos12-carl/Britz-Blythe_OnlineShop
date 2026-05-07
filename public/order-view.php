<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

require_once __DIR__ . '/../includes/header.php';

$orderId = (int)($_GET['id'] ?? 0);
$order = get_order_by_id($orderId);
$user = get_logged_in_user();

if (!$order || (int)$order['user_id'] !== (int)$user['id']) {
    header('Location: profile.php#orders');
    exit;
}

$items = get_order_items($orderId);
$statusSteps = ['Placed', 'Processing', 'Shipped', 'Delivered'];
$stage = get_order_status_stage($order['status']);

// Construct full address for geocoding
$fullAddress = implode(', ', array_filter([
    $order['address'],
    $order['city'],
    $order['state'],
    $order['zip_code']
]));
?>

<main class="page-shell">
    <div class="container">
        <div style="margin-bottom: 2rem;">
            <a href="profile.php#orders" class="text-button" style="text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem;">
                <span>←</span> Back to Order History
            </a>
        </div>

        <div class="account-card" style="margin-bottom: 2rem;">
            <div class="order-header" style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 1px solid var(--border); padding-bottom: 1.5rem; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <h2 style="margin: 0; font-size: 1.75rem;">Order #<?= $orderId ?></h2>
                    <p style="color: var(--text-muted); margin: 0.5rem 0 0;">Ordered on <?= date('F j, Y \a\t g:i a', strtotime($order['created_at'])) ?></p>
                </div>
                <div style="text-align: right;">
                    <div class="status-pill status-<?= strtolower($order['status']) ?>" style="font-size: 0.9rem; padding: 0.5rem 1.25rem;">
                        <?= htmlspecialchars(ucfirst($order['status'])) ?>
                    </div>
                </div>
            </div>

            <div class="order-tracker" style="margin-bottom: 3rem;">
                <?php foreach ($statusSteps as $index => $step): ?>
                    <div class="order-step<?= $index <= $stage ? ' active' : '' ?>">
                        <?= htmlspecialchars($step) ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 3rem;">
                <div class="info-block">
                    <h4 style="margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; font-size: 1.1rem;">
                        <span>📍</span> Delivery Address
                    </h4>
                    <p style="line-height: 1.7; color: var(--text-muted); background: var(--surface-alt); padding: 1.5rem; border-radius: 1rem; margin-bottom: 1.5rem;">
                        <strong style="color: var(--text);"><?= htmlspecialchars($order['recipient_name']) ?></strong><br>
                        <?= htmlspecialchars($order['address']) ?><br>
                        <?= htmlspecialchars($order['city']) ?>, <?= htmlspecialchars($order['state']) ?> <?= htmlspecialchars($order['zip_code']) ?><br>
                        <span style="display: block; margin-top: 0.5rem; font-size: 0.9rem;">📞 <?= htmlspecialchars($order['phone_number']) ?></span>
                    </p>
                    
                    <div id="order-map" style="height: 250px; width: 100%; border-radius: 1.25rem; border: 1px solid var(--border); background: var(--surface-alt);"></div>
                </div>

                <div class="info-block">
                    <h4 style="margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; font-size: 1.1rem;">
                        <span>💳</span> Billing Details
                    </h4>
                    <p style="line-height: 1.7; color: var(--text-muted); background: var(--surface-alt); padding: 1.5rem; border-radius: 1rem;">
                        <strong style="color: var(--text);"><?= htmlspecialchars($order['billing_recipient_name'] ?: $order['recipient_name']) ?></strong><br>
                        <?= htmlspecialchars($order['billing_address'] ?: $order['address']) ?><br>
                        <?= htmlspecialchars($order['billing_city'] ?: $order['city']) ?>, <?= htmlspecialchars($order['billing_state'] ?: $order['state']) ?> <?= htmlspecialchars($order['billing_zip_code'] ?: $order['zip_code']) ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="account-card">
            <h3 style="margin-bottom: 1.5rem; font-size: 1.25rem;">Order Summary</h3>
            <div style="overflow-x: auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th style="background: none;">Product</th>
                            <th style="text-align: center; background: none;">Price</th>
                            <th style="text-align: center; background: none;">Quantity</th>
                            <th style="text-align: right; background: none;">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 1.25rem; padding: 0.5rem 0;">
                                        <img src="<?= resolve_asset_url($item['image']) ?>" alt="" style="width: 60px; height: 60px; border-radius: 12px; object-fit: cover; border: 1px solid var(--border);">
                                        <a href="product.php?slug=<?= $item['slug'] ?>" style="color: var(--text); font-weight: 700; text-decoration: none;"><?= htmlspecialchars($item['name']) ?></a>
                                    </div>
                                </td>
                                <td style="text-align: center;"><?= format_price($item['price']) ?></td>
                                <td style="text-align: center;"><?= $item['quantity'] ?></td>
                                <td style="text-align: right; font-weight: 800;"><?= format_price($item['price'] * $item['quantity']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" style="text-align: right; border: none; padding-top: 2rem;"><strong>Total Paid:</strong></td>
                            <td style="text-align: right; border: none; padding-top: 2rem;"><span class="order-total" style="font-size: 1.75rem; color: var(--accent);"><?= format_price((float)$order['total']) ?></span></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</main>

<script src="https://maps.googleapis.com/maps/api/js?key=<?= GOOGLE_MAPS_API_KEY ?>&libraries=marker&loading=async" async defer></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const mapDiv = document.getElementById('order-map');
    const address = "<?= addslashes($fullAddress) ?>";

    function initOrderMap() {
        const geocoder = new google.maps.Geocoder();
        geocoder.geocode({ address: address }, (results, status) => {
            if (status === "OK") {
                const map = new google.maps.Map(mapDiv, {
                    zoom: 14,
                    center: results[0].geometry.location,
                    mapId: "ORDER_VIEW_MAP",
                    disableDefaultUI: true,
                    zoomControl: true
                });

                new google.maps.marker.AdvancedMarkerElement({
                    map: map,
                    position: results[0].geometry.location,
                    title: "Delivery Location"
                });
            } else {
                mapDiv.style.display = 'none';
            }
        });
    }

    const checkGoogle = setInterval(() => {
        if (typeof google !== 'undefined' && google.maps && google.maps.Geocoder) {
            clearInterval(checkGoogle);
            initOrderMap();
        }
    }, 100);
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>