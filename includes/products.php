<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
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
        $messageType = 'error';
    } else {
        $_SESSION['admin_message'] = 'Product archived successfully.';
        $_SESSION['admin_message_type'] = 'success';
        header('Location: products.php');
        exit;
    }
}

// Pagination settings
$limit = 8; // Number of products per page
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$totalProducts = get_total_products_count();
$totalPages = ceil($totalProducts / $limit);
$products = get_products('', '', 'name-asc', $limit, $offset);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Products - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/components.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/theme.css">
    <style>
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 3rem;
            padding-bottom: 2rem;
        }
        .pagination .button {
            min-width: 40px;
            padding: 0.5rem;
            display: flex;
            justify-content: center;
        }
    </style>
</head>
<body class="admin-dashboard">
    <div class="admin-container">
        <aside class="admin-sidebar">
            <div class="brand">Britz Blythe <span>Admin</span></div>
            <nav class="admin-nav">
                <a href="index.php">Dashboard</a>
                <a href="products.php" class="active">Products</a>
                <a href="orders.php">Orders</a>
                <a href="customers.php">Customers</a>
                <a href="categories.php">Categories</a>
                <a href="reviews.php">Reviews</a>
                <div class="nav-divider"></div>
                <a href="settings.php">Settings</a>
                <a href="logout.php" class="logout-link">Logout</a>
            </nav>
        </aside>

        <main class="admin-main">
            <header class="admin-header">
                <div>
                    <h1>Products</h1>
                    <p>Manage store products and inventory details.</p>
                </div>
                <div class="user-info">Welcome, <?= htmlspecialchars(get_logged_in_user()['name']) ?></div>
                <a class="button button-primary" href="add-product.php">Create Product</a>
            </header>

            <?php if ($message): ?> 
                <div class="alert <?= $messageType === 'success' ? 'alert-success' : 'alert-info' ?>" style="margin-bottom: 2rem;"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <div class="product-grid">
                <?php foreach ($products as $product): ?>
                    <article class="product-card">
                        <div class="product-card-image" style="height: 180px; margin-bottom: 1rem; border-radius: 12px; overflow: hidden; background: var(--admin-bg); display: flex; align-items: center; justify-content: center;">
                            <img src="<?= resolve_asset_url($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" 
                                 style="width: 100%; height: 100%; object-fit: cover;
                                 <?php if ($product['status'] === 'draft' && (empty($product['image']) || strpos($product['image'], 'placeholder.svg') !== false)): ?>
                                     filter: grayscale(0.7); opacity: 0.7;
                                 <?php endif; ?>"
                                 onerror="this.onerror=null;this.src='<?= resolve_asset_url(null) ?>'">
                        </div>
                        <h3><?= htmlspecialchars($product['name']) ?></h3>
                        <p><?= htmlspecialchars($product['excerpt']) ?></p>
                        <div class="product-footer" style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <div style="font-weight: 700;"><?= format_price($product['price']) ?></div>
                                <div style="font-size: 0.8rem; color: var(--text-muted);"><?= (int)($product['stock'] ?? 0) ?> in stock</div>
                            </div>
                            <div style="display: flex; gap: 0.5rem;">
                                <a class="button button-ghost" href="add-product.php?slug=<?= urlencode($product['slug']) ?>">Edit</a>
                                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this product? This action cannot be undone.');" style="display: inline;">
                                    <input type="hidden" name="delete_slug" value="<?= htmlspecialchars($product['slug']) ?>">
                                    <button type="submit" class="button button-secondary" style="color: #f87171; border-color: rgba(248, 113, 113, 0.2); padding: 0.5rem 1rem;">Delete</button>
                                </form>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <?php if ($totalPages > 1): ?>
                <nav class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>" class="button button-ghost">&larr; Previous</a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?page=<?= $i ?>" class="button <?= $i === $page ? 'button-primary' : 'button-ghost' ?>"><?= $i ?></a>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?>" class="button button-ghost">Next &rarr;</a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
