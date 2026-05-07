<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$message = '';
$messageType = 'info'; // Default message type

// Check for session messages (e.g., from product-edit.php)
if (isset($_SESSION['admin_message'])) {
    $message = $_SESSION['admin_message'];
    $messageType = $_SESSION['admin_message_type'] ?? 'info';
    unset($_SESSION['admin_message'], $_SESSION['admin_message_type']); // Clear the session message
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_slug'])) {
    if (!delete_product(sanitize($_POST['delete_slug']))) {
        $message = 'Unable to delete product. It may be linked to existing orders.';
    }
}

// Pagination Setup
$limit = 8;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$status_filter = $_GET['status'] ?? 'published';
$totalProducts = get_total_products_count('', '', $status_filter);
$totalPages = ceil($totalProducts / $limit);
$products = get_products('', '', 'newest', $limit, $offset, $status_filter);

$current_page = 'products';
$page_title = 'Collection Manager';
$page_description = "Managing <strong>$totalProducts</strong> items in your artisan collection.";

ob_start();
?>
<div class="admin-toolbar" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem; flex-wrap: wrap; gap: 1.5rem;">
    <div class="status-nav" style="display: flex; gap: 0.5rem; background: var(--surface-alt); padding: 0.4rem; border-radius: 14px;">
        <a href="?status=published" class="button <?= $status_filter == 'published' ? 'button-primary' : 'button-ghost' ?>" style="padding: 0.6rem 1.2rem; font-size: 0.85rem;">Published</a>
        <a href="?status=draft" class="button <?= $status_filter == 'draft' ? 'button-primary' : 'button-ghost' ?>" style="padding: 0.6rem 1.2rem; font-size: 0.85rem;">Drafts</a>
        <a href="?status=archived" class="button <?= $status_filter == 'archived' ? 'button-primary' : 'button-ghost' ?>" style="padding: 0.6rem 1.2rem; font-size: 0.85rem;">Archived</a>
    </div>
    <a class="button button-primary" href="add-product.php">➕ Create Product</a>
</div>

<div class="product-grid-v2">
    <?php foreach ($products as $product): ?>
        <article class="product-card-v2" style="background: var(--surface);">
            <div class="card-image-wrap" style="height: 180px;">
                <img src="<?= resolve_asset_url($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="lazy-image"
                     onerror="this.onerror=null;this.src='<?= resolve_asset_url(null) ?>';"
                     <?php if ($product['status'] === 'draft' && (empty($product['image']) || strpos($product['image'], 'placeholder.svg') !== false)): ?>
                         style="filter: grayscale(0.7); opacity: 0.7;"
                     <?php endif; ?>>
                <span class="card-badge <?= $product['status'] === 'published' ? 'lowstock' : 'soldout' ?>" style="background: <?= $product['status'] === 'published' ? 'var(--accent)' : 'var(--text-muted)' ?>;">
                    <?= ucfirst($product['status']) ?>
                </span>
            </div>
            <div class="card-body">
                <h3 class="card-name"><?= htmlspecialchars($product['name']) ?></h3>
                <p class="card-desc" style="margin-bottom: 0.75rem;"><?= htmlspecialchars($product['excerpt']) ?></p>
                
                <div class="card-footer" style="padding-top: 0.75rem; border-top: 1px solid var(--border);">
                    <span class="card-price" style="font-size: 1.1rem;"><?= format_price($product['price']) ?></span>
                    <span class="sold-count"><?= (int)($product['stock'] ?? 0) ?> in stock</span>
                </div>
                
                <div style="display: flex; gap: 0.5rem; margin-top: 1.25rem;">
                    <a class="button button-ghost" style="flex: 1; padding: 0.6rem; font-size: 0.8rem;" href="<?= SITE_URL ?>/product.php?slug=<?= urlencode($product['slug']) ?>" target="_blank">View</a>
                    <a class="button button-ghost" style="flex: 1; padding: 0.6rem; font-size: 0.8rem;" href="add-product.php?slug=<?= urlencode($product['slug']) ?>">Edit</a>
                    <form method="POST" onsubmit="return confirm('Archive this product?');" style="display: inline; flex: 1;">
                        <input type="hidden" name="delete_slug" value="<?= htmlspecialchars($product['slug']) ?>">
                        <button type="submit" class="button button-secondary" style="width: 100%; color: #f87171; border-color: rgba(248, 113, 113, 0.2); padding: 0.6rem; font-size: 0.8rem;">Archive</button>
                    </form>
                </div>
            </div>
        </article>
    <?php endforeach; ?>
</div>

<?php if ($totalPages > 1): ?>
    <nav class="pagination-v2">
        <?php if ($page > 1): ?>
            <a href="?status=<?= $status_filter ?>&page=<?= $page - 1 ?>">&larr;</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?status=<?= $status_filter ?>&page=<?= $i ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <a href="?status=<?= $status_filter ?>&page=<?= $page + 1 ?>">&rarr;</a>
        <?php endif; ?>
    </nav>
<?php endif; ?>
<?php
$page_content = ob_get_clean();
require 'admin-master.php';
?>
