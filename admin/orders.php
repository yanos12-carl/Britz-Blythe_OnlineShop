<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$orders = get_orders();

$current_page = 'orders';
$page_title = 'Orders';
$page_description = 'Review recent orders and shipping status.';

if (ob_get_level() === 0) {
    ob_start();
}
?>


<style>
    .orders-container {
        background: var(--admin-surface);
        border: 1px solid var(--admin-border);
        border-radius: 16px;
        overflow: hidden;
    }

    .admin-table {
        width: 100%;
        border-collapse: collapse;
    }

    .admin-table thead {
        background: var(--admin-bg);
    }

    .admin-table th {
        padding: 1rem 0.75rem;
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
        padding: 1rem 0.75rem;
        border-bottom: 1px solid var(--admin-border);
        color: var(--admin-text);
    }

    .admin-table tbody tr {
        transition: all 0.25s ease;
    }

    .admin-table tbody tr:hover {
        background: rgba(99, 102, 241, 0.05);
    }

    .order-id {
        font-weight: 800;
        color: var(--admin-accent);
        font-size: 1rem;
    }

    .customer-name {
        font-weight: 600;
        color: var(--admin-text);
    }

    .shipping-info {
        font-size: 0.85rem;
        color: var(--admin-text);
        opacity: 0.7;
    }

    .order-total {
        font-weight: 700;
        color: var(--admin-accent);
    }

    .status-pill {
        display: inline-block;
        padding: 0.4rem 0.8rem;
        border-radius: 8px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: capitalize;
        white-space: nowrap;
    }

    .status-pending {
        background: rgba(245, 158, 11, 0.15);
        color: #f59e0b;
    }

    .status-processing {
        background: rgba(99, 102, 241, 0.15);
        color: var(--admin-accent);
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

    .btn-view {
        padding: 0.5rem 1rem;
        background: var(--admin-accent);
        color: white;
        border: none;
        border-radius: 6px;
        text-decoration: none;
        cursor: pointer;
        font-weight: 600;
        font-size: 0.8rem;
        transition: all 0.25s ease;
        display: inline-block;
    }

    .btn-view:hover {
        background: #4f46e5;
        transform: translateY(-1px);
    }

    .empty-state-container {
        background: var(--admin-surface);
        border: 1px solid var(--admin-border);
        border-radius: 16px;
        padding: 4rem 2rem;
        text-align: center;
    }

    .empty-icon {
        font-size: 3rem;
        margin-bottom: 1rem;
    }

    .empty-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--admin-text);
        margin: 0 0 0.5rem 0;
    }

    .empty-text {
        color: var(--admin-text);
        opacity: 0.6;
        margin: 0;
    }

    @media (max-width: 768px) {
        .admin-table {
            font-size: 0.9rem;
        }

        .admin-table th,
        .admin-table td {
            padding: 0.75rem 0.5rem;
        }

        .shipping-info {
            display: none;
        }
    }
</style>

<?php if (empty($orders)): ?>
    <div class="empty-state-container">
        <div class="empty-icon">📭</div>
        <h2 class="empty-title">No Orders Found</h2>
        <p class="empty-text">Once customers place orders, they will appear here.</p>
    </div>
<?php else: ?>
    <div class="orders-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th class="shipping-info">Ship To</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                <tr>
                    <td class="order-id">#<?= $order['id'] ?></td>
                    <td class="customer-name"><?= htmlspecialchars($order['customer_name'] ?? 'Guest') ?></td>
                    <td class="shipping-info">
                        <strong><?= htmlspecialchars($order['recipient_name'] ?? 'N/A') ?></strong>
                        <br>
                        <small><?= htmlspecialchars($order['city'] ?? '') ?></small>
                    </td>
                    <td class="order-total"><?= format_price($order['total']) ?></td>
                    <td><span class="status-pill status-<?= strtolower($order['status']) ?>"><?= $order['status'] ?></span></td>
                    <td style="text-align: right;">
                        <a class="btn-view" href="order-view.php?id=<?= urlencode($order['id']) ?>">View Details</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php
$page_content = ob_get_clean();
require 'admin-master.php';
