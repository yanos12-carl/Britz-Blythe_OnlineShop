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
    unset($_SESSION['admin_message'], $_SESSION['admin_message_type']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_slug'])) {
    if (!delete_product(sanitize($_POST['delete_slug']))) {
        $message = 'Unable to delete product. It may be linked to existing orders.';
        $messageType = 'error';
    } else {
        $message = 'Product archived successfully.';
        $messageType = 'success';
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

<?php if ($message): ?>
    <div class="admin-alert alert-<?= $messageType ?>" style="margin-bottom: 2rem; animation: slideDown 0.3s ease;">
        <span class="alert-text"><?= htmlspecialchars($message) ?></span>
        <button class="alert-close" onclick="this.parentElement.style.display='none';" style="background: none; border: none; cursor: pointer; font-size: 1.25rem; opacity: 0.6;">&times;</button>
    </div>
<?php endif; ?>

<div class="admin-toolbar">
    <div class="toolbar-left">
        <div class="status-nav">
            <a href="?status=published" class="status-btn <?= $status_filter == 'published' ? 'active' : '' ?>">Published</a>
            <a href="?status=draft" class="status-btn <?= $status_filter == 'draft' ? 'active' : '' ?>">Drafts</a>
            <a href="?status=archived" class="status-btn <?= $status_filter == 'archived' ? 'active' : '' ?>">Archived</a>
        </div>
    </div>
    <a class="button button-primary create-btn" href="add-product.php">
        <span style="font-size: 1.25rem;">➕</span> Create Product
    </a>
</div>

<?php if (empty($products)): ?>
    <div class="empty-state">
        <div class="empty-icon">📦</div>
        <h2>No Products Found</h2>
        <p>You don't have any <?= htmlspecialchars($status_filter) ?> products yet.</p>
        <a href="add-product.php" class="button button-primary" style="margin-top: 1rem;">Create Your First Product</a>
    </div>
<?php else: ?>
    <div class="products-grid">
        <?php foreach ($products as $product): ?>
            <article class="product-card">
                <div class="product-card-image">
                    <img src="<?= resolve_asset_url($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" loading="lazy" 
                         onerror="this.onerror=null;this.src='<?= resolve_asset_url(null) ?>';"
                         <?php if ($product['status'] === 'draft' && (empty($product['image']) || strpos($product['image'], 'placeholder.svg') !== false)): ?>
                             style="filter: grayscale(0.7); opacity: 0.7;"
                         <?php endif; ?>>
                    <span class="product-badge <?= $product['status'] ?>">
                        <?= ucfirst($product['status']) ?>
                    </span>
                </div>
                
                <div class="product-card-body">
                    <h3 class="product-name"><?= htmlspecialchars($product['name']) ?></h3>
                    <p class="product-excerpt"><?= htmlspecialchars(substr($product['excerpt'] ?? $product['description'] ?? '', 0, 100)) ?></p>
                    
                    <div class="product-meta">
                        <div class="product-price"><?= format_price($product['price']) ?></div>
                        <div class="product-stock">
                            <span class="stock-badge <?= (int)($product['stock'] ?? 0) > 0 ? 'in-stock' : 'out-of-stock' ?>">
                                <?= (int)($product['stock'] ?? 0) ?> in stock
                            </span>
                        </div>
                    </div>
                    
                    <div class="product-actions">
                        <a class="btn btn-secondary" href="<?= SITE_URL ?>/public/product.php?slug=<?= urlencode($product['slug']) ?>" target="_blank" title="View">👁️</a>
                        <a class="btn btn-primary" href="add-product.php?slug=<?= urlencode($product['slug']) ?>" title="Edit">✏️</a>
                        <form method="POST" class="btn-form" onsubmit="return confirm('Archive this product?');">
                            <input type="hidden" name="delete_slug" value="<?= htmlspecialchars($product['slug']) ?>">
                            <button type="submit" class="btn btn-danger" title="Archive">🗑️</button>
                        </form>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <?php if ($totalPages > 1): ?>
        <nav class="pagination">
            <?php if ($page > 1): ?>
                <a href="?status=<?= $status_filter ?>&page=1" class="pagination-btn">«</a>
                <a href="?status=<?= $status_filter ?>&page=<?= $page - 1 ?>" class="pagination-btn">‹</a>
            <?php endif; ?>

            <?php 
            $start = max(1, $page - 2);
            $end = min($totalPages, $page + 2);
            
            if ($start > 1): ?>
                <a href="?status=<?= $status_filter ?>&page=1" class="pagination-btn">1</a>
                <?php if ($start > 2): ?>
                    <span class="pagination-ellipsis">…</span>
                <?php endif;
            endif;

            for ($i = $start; $i <= $end; $i++): ?>
                <a href="?status=<?= $status_filter ?>&page=<?= $i ?>" class="pagination-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor;

            if ($end < $totalPages): ?>
                <?php if ($end < $totalPages - 1): ?>
                    <span class="pagination-ellipsis">…</span>
                <?php endif; ?>
                <a href="?status=<?= $status_filter ?>&page=<?= $totalPages ?>" class="pagination-btn"><?= $totalPages ?></a>
            <?php endif; ?>

            <?php if ($page < $totalPages): ?>
                <a href="?status=<?= $status_filter ?>&page=<?= $page + 1 ?>" class="pagination-btn">›</a>
                <a href="?status=<?= $status_filter ?>&page=<?= $totalPages ?>" class="pagination-btn">»</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
<?php endif; ?>

<style>
    .admin-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2.5rem;
        flex-wrap: wrap;
        gap: 1.5rem;
    }

    .toolbar-left {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .status-nav {
        display: flex;
        gap: 0.5rem;
        background: var(--admin-surface);
        padding: 0.4rem;
        border-radius: 12px;
        border: 1px solid var(--admin-border);
    }

    .status-btn {
        padding: 0.6rem 1.2rem;
        font-size: 0.85rem;
        font-weight: 600;
        text-decoration: none;
        color: var(--admin-text);
        background: transparent;
        border-radius: 8px;
        cursor: pointer;
        border: none;
        transition: all 0.25s ease;
        opacity: 0.6;
    }

    .status-btn:hover {
        opacity: 0.85;
    }

    .status-btn.active {
        background: var(--admin-accent);
        color: white;
        opacity: 1;
    }

    .create-btn {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        background: linear-gradient(135deg, var(--admin-accent), #10b981);
        color: white;
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: 12px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        transition: all 0.25s ease;
        box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
    }

    .create-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(99, 102, 241, 0.4);
    }

    .products-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .product-card {
        background: var(--admin-surface);
        border: 1px solid var(--admin-border);
        border-radius: 16px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        transition: all 0.25s ease;
        animation: fadeIn 0.3s ease;
    }

    .product-card:hover {
        border-color: var(--admin-accent);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
        transform: translateY(-4px);
    }

    .product-card-image {
        position: relative;
        height: 180px;
        overflow: hidden;
        background: var(--admin-bg);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .product-card-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }

    .product-card:hover .product-card-image img {
        transform: scale(1.05);
    }

    .product-badge {
        position: absolute;
        top: 0.75rem;
        right: 0.75rem;
        padding: 0.4rem 0.8rem;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .product-badge.published {
        background: rgba(16, 185, 129, 0.2);
        color: #10b981;
    }

    .product-badge.draft {
        background: rgba(99, 102, 241, 0.2);
        color: var(--admin-accent);
    }

    .product-badge.archived {
        background: rgba(239, 68, 68, 0.2);
        color: #ef4444;
    }

    .product-card-body {
        padding: 1rem;
        display: flex;
        flex-direction: column;
        flex: 1;
    }

    .product-name {
        margin: 0 0 0.5rem 0;
        font-size: 1rem;
        font-weight: 700;
        color: var(--admin-text);
        line-height: 1.3;
    }

    .product-excerpt {
        margin: 0 0 1rem 0;
        font-size: 0.85rem;
        color: var(--admin-text);
        opacity: 0.7;
        line-height: 1.4;
        flex: 1;
    }

    .product-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 0;
        border-top: 1px solid var(--admin-border);
        margin-bottom: 1rem;
        font-size: 0.9rem;
    }

    .product-price {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--admin-accent);
    }

    .stock-badge {
        padding: 0.35rem 0.75rem;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .stock-badge.in-stock {
        background: rgba(16, 185, 129, 0.15);
        color: #10b981;
    }

    .stock-badge.out-of-stock {
        background: rgba(239, 68, 68, 0.15);
        color: #ef4444;
    }

    .product-actions {
        display: flex;
        gap: 0.5rem;
    }

    .btn {
        flex: 1;
        padding: 0.6rem;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        transition: all 0.25s ease;
        background: var(--admin-bg);
        color: var(--admin-text);
        border: 1px solid var(--admin-border);
    }

    .btn:hover {
        background: rgba(99, 102, 241, 0.1);
        border-color: var(--admin-accent);
    }

    .btn-primary {
        background: var(--admin-accent);
        color: white;
        border: none;
    }

    .btn-primary:hover {
        background: #4f46e5;
        filter: brightness(1.1);
    }

    .btn-danger {
        background: rgba(239, 68, 68, 0.1);
        color: #ef4444;
        border: 1px solid rgba(239, 68, 68, 0.3);
    }

    .btn-danger:hover {
        background: rgba(239, 68, 68, 0.2);
        border-color: #ef4444;
    }

    .btn-form {
        display: contents;
    }

    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        background: var(--admin-surface);
        border-radius: 16px;
        border: 2px dashed var(--admin-border);
    }

    .empty-icon {
        font-size: 4rem;
        margin-bottom: 1rem;
    }

    .empty-state h2 {
        margin: 0 0 0.5rem 0;
        color: var(--admin-text);
    }

    .empty-state p {
        margin: 0 0 1rem 0;
        color: var(--admin-text);
        opacity: 0.6;
    }

    .pagination {
        display: flex;
        justify-content: center;
        gap: 0.5rem;
        margin-top: 2rem;
        flex-wrap: wrap;
    }

    .pagination-btn {
        padding: 0.5rem 0.75rem;
        border: 1px solid var(--admin-border);
        background: var(--admin-surface);
        color: var(--admin-text);
        border-radius: 8px;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.25s ease;
        font-weight: 500;
    }

    .pagination-btn:hover {
        background: var(--admin-accent);
        color: white;
        border-color: var(--admin-accent);
    }

    .pagination-btn.active {
        background: var(--admin-accent);
        color: white;
        border-color: var(--admin-accent);
    }

    .pagination-ellipsis {
        color: var(--admin-text);
        opacity: 0.5;
        padding: 0.5rem 0.25rem;
    }

    .admin-alert {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem;
        border-radius: 12px;
        border: 1px solid;
        animation: slideDown 0.3s ease;
    }

    .admin-alert.alert-success {
        background: rgba(16, 185, 129, 0.1);
        border-color: rgba(16, 185, 129, 0.3);
        color: #10b981;
    }

    .admin-alert.alert-error {
        background: rgba(239, 68, 68, 0.1);
        border-color: rgba(239, 68, 68, 0.3);
        color: #ef4444;
    }

    .admin-alert.alert-info {
        background: rgba(99, 102, 241, 0.1);
        border-color: rgba(99, 102, 241, 0.3);
        color: var(--admin-accent);
    }

    .alert-text {
        flex: 1;
        font-weight: 500;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @media (max-width: 768px) {
        .admin-toolbar {
            flex-direction: column;
            align-items: stretch;
        }

        .products-grid {
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        }

        .create-btn {
            justify-content: center;
        }
    }
</style>

<?php
$page_content = ob_get_clean();
require 'admin-master.php';
?>
