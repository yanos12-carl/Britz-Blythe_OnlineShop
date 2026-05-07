<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$message = '';
$message_type = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_slug'])) {
    if (restore_product(sanitize($_POST['restore_slug']))) {
        $message = 'Product restored successfully!';
        $message_type = 'success';
    } else {
        $message = 'Unable to restore product.';
        $message_type = 'error';
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['permanent_delete_slug'])) {
    if (permanently_delete_product(sanitize($_POST['permanent_delete_slug']))) {
        $message = 'Product permanently deleted.';
        $message_type = 'success';
    } else {
        $message = 'Unable to delete product record. It may be linked to existing order history.';
        $message_type = 'error';
    }
}

$products = get_archived_products();

$current_page = 'archived';
$page_title = 'Archived Products';
$page_description = 'View and restore products that were previously removed from your store.';

ob_start();
?>

<style>
    .archived-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
        gap: 1.5rem;
    }

    .archived-card {
        background: var(--admin-surface);
        border: 1px solid var(--admin-border);
        border-radius: 12px;
        overflow: hidden;
        opacity: 0.8;
        transition: all 0.25s ease;
    }

    .archived-card:hover {
        opacity: 1;
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
    }

    .archived-image {
        height: 150px;
        background: var(--admin-bg);
        overflow: hidden;
        border-bottom: 1px solid var(--admin-border);
        opacity: 1;
    }

    .archived-content {
        padding: 1rem;
    }

    .archived-name {
        font-weight: 700;
        color: var(--admin-text);
        margin: 0 0 0.5rem 0;
        font-size: 1rem;
    }

    .archived-date {
        font-size: 0.85rem;
        color: var(--admin-text);
        opacity: 0.6;
        margin: 0 0 1rem 0;
    }

    .archived-actions {
        display: flex;
        gap: 0.5rem;
    }

    .btn-restore {
        flex: 1;
        padding: 0.6rem;
        background: rgba(16, 185, 129, 0.15);
        color: #10b981;
        border: 1px solid rgba(16, 185, 129, 0.3);
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        font-size: 0.8rem;
        transition: all 0.25s ease;
    }

    .btn-restore:hover {
        background: rgba(16, 185, 129, 0.25);
        border-color: #10b981;
    }

    .btn-permanent-delete {
        flex: 1;
        padding: 0.6rem;
        background: rgba(239, 68, 68, 0.15);
        color: #ef4444;
        border: 1px solid rgba(239, 68, 68, 0.3);
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        font-size: 0.8rem;
        transition: all 0.25s ease;
    }

    .btn-permanent-delete:hover {
        background: rgba(239, 68, 68, 0.25);
        border-color: #ef4444;
    }

    .empty-archived {
        text-align: center;
        padding: 4rem 2rem;
        background: var(--admin-surface);
        border: 1px solid var(--admin-border);
        border-radius: 12px;
    }

    .alert-message {
        padding: 1rem;
        border-radius: 12px;
        margin-bottom: 2rem;
    }

    .alert-success {
        background: rgba(16, 185, 129, 0.15);
        border: 1px solid rgba(16, 185, 129, 0.3);
        color: #10b981;
    }

    .alert-error {
        background: rgba(239, 68, 68, 0.15);
        border: 1px solid rgba(239, 68, 68, 0.3);
        color: #ef4444;
    }

    @media (max-width: 768px) {
        .archived-grid {
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        }
    }
</style>

<?php if ($message): ?>
    <div class="alert-message alert-<?= $message_type ?>">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<?php if (empty($products)): ?>
    <div class="empty-archived">
        <div style="font-size: 2.5rem; margin-bottom: 1rem;">📭</div>
        <h2 style="color: var(--admin-text); margin: 0 0 0.5rem 0;">No Archived Products</h2>
        <p style="color: var(--admin-text); opacity: 0.6; margin: 0;">Products you archive will appear here.</p>
    </div>
<?php else: ?>
    <div class="archived-grid">
        <?php foreach ($products as $product): ?>
            <div class="archived-card">
                <div class="archived-image">
                    <img src="<?= resolve_asset_url($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" style="width: 100%; height: 100%; object-fit: cover; filter: grayscale(0.5);">
                </div>
                <div class="archived-content">
                    <h3 class="archived-name"><?= htmlspecialchars($product['name']) ?></h3>
                    <p class="archived-date">📅 Archived <?= date('M d, Y', strtotime($product['deleted_at'])) ?></p>
                    <div class="archived-actions">
                        <form method="POST" style="flex: 1;">
                            <input type="hidden" name="restore_slug" value="<?= htmlspecialchars($product['slug']) ?>">
                            <button type="submit" class="btn-restore">↩️ Restore</button>
                        </form>
                        <form method="POST" style="flex: 1;" onsubmit="return confirm('⚠️ Permanently delete this product? This cannot be undone and will delete all product history.');">
                            <input type="hidden" name="permanent_delete_slug" value="<?= htmlspecialchars($product['slug']) ?>">
                            <button type="submit" class="btn-permanent-delete">🗑️ Delete</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php
$page_content = ob_get_clean();
require 'admin-master.php';
