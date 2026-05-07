<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Ensure access
require_admin();

// Fetch stats for the content area
$revenue = get_total_revenue();
$total_orders = count(get_orders(1000));
$total_products = get_total_products_count();
$customers = count(get_customers());
$recent_orders = get_orders(5);
$pending_orders = array_filter(get_orders(100), fn($o) => strtolower($o['status']) === 'pending');
$low_stock_products = array_filter(get_products(), fn($p) => ($p['stock'] ?? 0) <= 5);

$current_page = 'dashboard';
$page_title = 'Dashboard';
$page_description = 'A summary of your store’s current performance and alerts.';

ob_start();
?>

<style>
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 1.5rem;
        margin-bottom: 3rem;
    }

    .stat-card {
        background: var(--admin-surface);
        border: 1px solid var(--admin-border);
        border-radius: 16px;
        padding: 1.5rem;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        transition: all 0.25s ease;
    }

    .stat-card:hover {
        border-color: var(--admin-accent);
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
    }

    .stat-label {
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--admin-text);
        opacity: 0.7;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .stat-value {
        font-size: 2rem;
        font-weight: 900;
        background: linear-gradient(135deg, var(--admin-accent), #10b981);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .stat-card small {
        font-size: 0.8rem;
        color: var(--admin-text);
        opacity: 0.6;
    }

    .dashboard-grid {
        display: grid;
        grid-template-columns: 1.5fr 1fr;
        gap: 2rem;
    }

    .dashboard-section {
        background: var(--admin-surface);
        border: 1px solid var(--admin-border);
        border-radius: 16px;
        padding: 1.5rem;
    }

    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid var(--admin-border);
    }

    .section-header h2 {
        margin: 0;
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--admin-text);
    }

    .section-link {
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--admin-accent);
        text-decoration: none;
        transition: all 0.25s ease;
    }

    .section-link:hover {
        opacity: 0.8;
    }

    .admin-table {
        width: 100%;
        border-collapse: collapse;
    }

    .admin-table thead {
        background: var(--admin-bg);
    }

    .admin-table th {
        padding: 0.75rem;
        text-align: left;
        font-weight: 700;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--admin-text);
        opacity: 0.7;
        border-bottom: 1px solid var(--admin-border);
    }

    .admin-table td {
        padding: 0.875rem 0.75rem;
        border-bottom: 1px solid var(--admin-border);
        color: var(--admin-text);
    }

    .admin-table tbody tr:hover {
        background: rgba(99, 102, 241, 0.05);
    }

    .order-id {
        font-weight: 800;
        color: var(--admin-accent);
    }

    .status-pill {
        display: inline-block;
        padding: 0.35rem 0.75rem;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: capitalize;
    }

    .status-pending {
        background: rgba(245, 158, 11, 0.15);
        color: #f59e0b;
    }

    .status-completed {
        background: rgba(16, 185, 129, 0.15);
        color: #10b981;
    }

    .status-shipped {
        background: rgba(99, 102, 241, 0.15);
        color: var(--admin-accent);
    }

    .status-cancelled {
        background: rgba(239, 68, 68, 0.15);
        color: #ef4444;
    }

    .activity-list {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .activity-item {
        padding: 1rem;
        background: var(--admin-bg);
        border: 1px solid var(--admin-border);
        border-radius: 12px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: all 0.25s ease;
    }

    .activity-item:hover {
        border-color: var(--admin-accent);
        background: rgba(99, 102, 241, 0.05);
    }

    .item-name {
        display: block;
        font-weight: 700;
        color: var(--admin-text);
        margin-bottom: 0.25rem;
    }

    .item-meta {
        font-size: 0.8rem;
        color: #ef4444;
        font-weight: 600;
    }

    .btn-edit {
        padding: 0.4rem 0.8rem;
        font-size: 0.75rem;
        background: var(--admin-accent);
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        transition: all 0.25s ease;
    }

    .btn-edit:hover {
        background: #4f46e5;
        transform: translateY(-1px);
    }

    @media (max-width: 768px) {
        .dashboard-grid {
            grid-template-columns: 1fr;
        }

        .stats-grid {
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        }
    }
</style>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
    <!-- Action Alert: Pending Orders -->
    <?php if (!empty($pending_orders)): ?>
    <div class="admin-card" style="border-left: 4px solid #f59e0b; background: rgba(245, 158, 11, 0.05);">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <h3 style="margin: 0; color: #f59e0b;">🚨 Attention Needed</h3>
                <p style="margin: 0.5rem 0; font-size: 0.9rem;">You have <strong><?= count($pending_orders) ?></strong> orders waiting for processing.</p>
            </div>
            <a href="orders.php?status=pending" class="btn-edit" style="background: #f59e0b;">Process Now</a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Action Alert: Low Stock -->
    <?php if (!empty($low_stock_products)): ?>
    <div class="admin-card" style="border-left: 4px solid #ef4444; background: rgba(239, 68, 68, 0.05);">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <h3 style="margin: 0; color: #ef4444;">📉 Inventory Low</h3>
                <p style="margin: 0.5rem 0; font-size: 0.9rem;"><strong><?= count($low_stock_products) ?></strong> products are almost out of stock.</p>
            </div>
            <a href="stock-manage.php" class="btn-edit" style="background: #ef4444;">Restock</a>
        </div>
    </div>
    <?php else: ?>
    <div class="admin-card" style="border-left: 4px solid #10b981; background: rgba(16, 185, 129, 0.05);">
        <h3 style="margin: 0; color: #10b981;">✅ All Clear</h3>
        <p style="margin: 0.5rem 0; font-size: 0.9rem;">Inventory levels and orders are healthy.</p>
    </div>
    <?php endif; ?>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <span class="stat-label">💰 Gross Revenue</span>
        <span class="stat-value"><?= format_price($revenue) ?></span>
        <small>All-time sales</small>
    </div>
    <div class="stat-card">
        <span class="stat-label">📦 Total Orders</span>
        <span class="stat-value"><?= $total_orders ?></span>
        <small>Processed shipments</small>
    </div>
    <div class="stat-card">
        <span class="stat-label">👥 Active Customers</span>
        <span class="stat-value"><?= $customers ?></span>
        <small>Registered users</small>
    </div>
    <div class="stat-card">
        <span class="stat-label">📊 Active Items</span>
        <span class="stat-value"><?= $total_products ?></span>
        <small>Live in shop</small>
    </div>
</div>

<div class="dashboard-grid">
    <section class="dashboard-section">
        <div class="section-header">
            <h2>Recent Orders</h2>
            <a href="orders.php" class="section-link">View All →</a>
        </div>
        <?php if (!empty($recent_orders)): ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Total</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_orders as $order): ?>
                    <tr>
                        <td class="order-id">#<?= $order['id'] ?></td>
                        <td><?= htmlspecialchars($order['customer_name'] ?? 'Guest') ?></td>
                        <td><?= format_price($order['total']) ?></td>
                        <td><span class="status-pill status-<?= strtolower($order['status']) ?>"><?= $order['status'] ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="color: var(--admin-text); opacity: 0.6; margin: 0;">No recent orders</p>
        <?php endif; ?>
    </section>

    <section class="dashboard-section">
        <div class="section-header">
            <h2>Inventory Alerts</h2>
            <a href="stock-manage.php" class="section-link">Manage →</a>
        </div>
        <div class="activity-list">
            <?php if (empty($low_stock_products)): ?>
                <p style="color: var(--admin-text); opacity: 0.6; margin: 0;">✅ All products are well-stocked.</p>
            <?php else: ?>
                <?php foreach ($low_stock_products as $p): ?>
                    <div class="activity-item">
                        <div>
                            <span class="item-name"><?= htmlspecialchars($p['name']) ?></span>
                            <span class="item-meta">⚠️ Only <?= $p['stock'] ?? 0 ?> left</span>
                        </div>
                        <a href="add-product.php?slug=<?= urlencode($p['slug']) ?>" class="btn-edit">Edit</a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php 
$page_content = ob_get_clean();
require 'admin-master.php';