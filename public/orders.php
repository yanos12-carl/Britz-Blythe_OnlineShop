<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

require_once __DIR__ . '/../includes/header.php';

if (is_admin()) {
    header('Location: ../admin/orders.php');
    exit;
}
$user = get_logged_in_user();

// Fetch sidebar data for the dashboard layout
$db = get_db();
$stmt = $db->prepare('SELECT registered_at AS created_at, profile_image FROM users WHERE id = :id');
$stmt->execute([':id' => $user['id']]);
$fullUser = $stmt->fetch();
$userAvatar = $fullUser['profile_image'] ?: ASSET_PATH . '/images/products/placeholder.svg';

$query = sanitize($_GET['q'] ?? '');
$orders = get_user_orders($user['email']);
$seedKey = 'demo_orders_' . md5($user['email']);
if (empty($orders) && empty($_SESSION[$seedKey])) {
    $_SESSION[$seedKey] = [
        ['id' => 10021, 'status' => 'Placed', 'total' => 74.00, 'created_at' => '2026-04-11 14:32:00'],
        ['id' => 10034, 'status' => 'Processing', 'total' => 54.00, 'created_at' => '2026-04-08 09:18:00'],
        ['id' => 10048, 'status' => 'Shipped', 'total' => 199.00, 'created_at' => '2026-04-05 11:50:00'],
        ['id' => 10056, 'status' => 'Delivered', 'total' => 129.00, 'created_at' => '2026-03-30 16:15:00'],
    ];
}
if (empty($orders)) {
    $orders = $_SESSION[$seedKey] ?? [];
}
if ($query !== '') {
    $orders = array_filter($orders, function ($order) use ($query) {
        return stripos((string)$order['id'], $query) !== false
            || stripos($order['status'], $query) !== false
            || stripos((string)$order['total'], $query) !== false;
    });
}
$statusSteps = ['Placed', 'Processing', 'Shipped', 'Delivered'];
?>

<main class="page-shell">
    <div class="container">
        <div class="profile-dashboard-layout">
            <aside class="profile-sidebar">
                <div class="profile-header-card">
                    <div class="profile-avatar-wrapper">
                        <img src="<?= SITE_URL . '/' . htmlspecialchars($userAvatar) ?>" alt="Avatar">
                    </div>
                    <div class="profile-user-info">
                        <h2><?= htmlspecialchars($user['name']) ?></h2>
                        <span class="member-since">View your order history</span>
                    </div>
                </div>
                <nav class="profile-nav">
                    <a href="<?= SITE_URL ?>/profile.php"><span>👤</span> Profile Details</a>
                    <a href="<?= SITE_URL ?>/address-book.php"><span>📖</span> Address Book</a>
                    <a href="<?= SITE_URL ?>/orders.php" class="active"><span>📦</span> My Orders</a>
                    <div class="nav-divider"></div>
                    <a href="<?= SITE_URL ?>/logout.php" class="logout-link"><span>🚪</span> Sign Out</a>
                </nav>
            </aside>

            <div class="profile-content">
                <div class="section-title">
                    <h3>Your Orders</h3>
                    <p>Track your recent purchases and manage delivery details.</p>
                </div>

                <form class="order-search" method="get" action="<?= SITE_URL ?>/orders.php" style="margin-bottom: 2rem;">
                    <input type="search" name="q" placeholder="Search orders..." value="<?= htmlspecialchars($query) ?>">
                    <button type="submit" class="button button-primary">Search</button>
                </form>

                <?php if (empty($orders)): ?>
                    <div class="empty-state">
                        <p>No orders found.</p>
                        <a class="button button-ghost" href="<?= SITE_URL ?>/products.php">Shop now</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                        <?php $stage = get_order_status_stage($order['status']); ?>
                        <article class="order-card account-card" style="margin-bottom: 2rem;">
                            <div class="order-header" style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem;">
                                <div>
                                    <h4 style="margin: 0;">Order #<?= htmlspecialchars($order['id']) ?></h4>
                                    <span class="order-meta"><?= date('M j, Y', strtotime($order['created_at'])) ?> · <?= format_price((float)$order['total']) ?></span>
                                </div>
                                <a href="<?= SITE_URL ?>/order-view.php?id=<?= $order['id'] ?>" class="button button-ghost" style="padding: 0.5rem 1rem; font-size: 0.8rem;">View Order</a>
                                <span class="status-pill status-<?= strtolower($order['status'] ?? 'placed') ?>"><?= htmlspecialchars($order['status'] ?? 'Placed') ?></span>
                            </div>

                            <div style="padding: 1.25rem; background: var(--surface-alt); border-radius: 12px; margin-bottom: 1.5rem;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <strong style="font-size: 0.85rem; text-transform: uppercase; color: var(--text-muted); display: block; margin-bottom: 0.5rem;">Shipping Destination</strong>
                                        <p style="margin: 0; font-size: 0.95rem;">
                                            <?= htmlspecialchars($order['recipient_name'] ?? 'N/A') ?><br>
                                            <?= htmlspecialchars($order['address'] ?? '') ?>, <?= htmlspecialchars($order['city'] ?? '') ?>
                                        </p>
                                    </div>
                                    <?php if (in_array(strtolower($order['status'] ?? 'placed'), ['placed', 'pending', 'processing'])): ?>
                                        <a href="order-edit-address.php?id=<?= $order['id'] ?>" class="text-button" style="font-size: 0.8rem;">Edit Address</a>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="order-tracker">
                                <?php foreach ($statusSteps as $index => $step): ?>
                                    <div class="order-step<?= $index <= $stage ? ' active' : '' ?>">
                                        <?= htmlspecialchars($step) ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>
<?php include __DIR__ . '/../includes/footer.php';
