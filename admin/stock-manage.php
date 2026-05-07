<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'], $_POST['stock'])) {
    if (update_product_stock((int)$_POST['product_id'], (int)$_POST['stock'])) {
        $message = 'Stock level updated successfully.';
    } else {
        $message = 'Failed to update stock. Please try again.';
    }
}

$products = get_products();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quick Stock Update - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/components.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/theme.css">
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
                    <h1>Manage Stock</h1>
                    <p>Quickly update inventory levels for all active products.</p>
                </div>
                <div class="user-info">Welcome, <?= htmlspecialchars(get_logged_in_user()['name']) ?></div>
            </header>

            <?php if ($message): ?>
                <div class="alert alert-success" style="margin-bottom: 2rem;"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <div class="admin-card">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th style="width: 80px;">Photo</th>
                            <th>Product Name</th>
                            <th>Current Stock</th>
                            <th style="text-align: right;">Quick Update</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $p): ?>
            <?php if (!isset($p['id'])) continue; // Skip if product ID is not set ?>

                        <tr>
                            <td>
                                <img src="<?= resolve_asset_url($p['image']) ?>" alt="" style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px; border: 1px solid var(--admin-border);">
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($p['name']) ?></strong><br>
                                <small class="text-muted"><?= htmlspecialchars($p['category']) ?></small>
                            </td>
                            <td>
                    <input type="checkbox" class="product-checkbox" value="<?= $p['id'] ?>" style="margin-right: 0.5rem;">
                </td>
                <td>
                                <span class="status-pill <?= ($p['stock'] ?? 0) <= 5 ? 'status-pending' : 'status-shipped' ?>">
                                    <?= (int)($p['stock'] ?? 0) ?> in stock
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <form method="POST" style="display: flex; gap: 0.5rem; justify-content: flex-end; align-items: center;">
                                    <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                    <input type="number" name="stock" value="<?= (int)($p['stock'] ?? 0) ?>" min="0" style="width: 80px !important; padding: 0.4rem !important; margin: 0 !important;">
                                    <button type="submit" class="button button-primary" style="padding: 0.4rem 1rem; font-size: 0.75rem;">Update</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>