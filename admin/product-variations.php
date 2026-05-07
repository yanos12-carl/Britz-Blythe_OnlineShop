<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$productSlug = sanitize($_GET['product'] ?? '');
$product = get_product_by_slug($productSlug);
if (!$product) {
    header('Location: products.php');
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_variation_type'])) {
        // Add variation type
        $typeName = sanitize($_POST['type_name']);
        $result = add_product_variation_type($product['id'], $typeName);
        $message = $result ? 'Variation type added.' : 'Failed to add variation type.';
    } elseif (isset($_POST['add_option'])) {
        // Add option to type
        $typeId = (int)$_POST['type_id'];
        $optionValue = sanitize($_POST['option_value']);
        $result = add_variation_option($typeId, $optionValue);
        $message = $result ? 'Option added.' : 'Failed to add option.';
    } elseif (isset($_POST['save_combo'])) {
        // Save variation combo
        $options = json_decode($_POST['options'], true);
        $price = (float)$_POST['combo_price'];
        $stock = (int)$_POST['combo_stock'];
        $image = sanitize($_POST['combo_image'] ?? '');
        $result = save_variation_combo($product['id'], $options, $price, $stock, $image);
        $message = $result ? 'Variation combo saved.' : 'Failed to save combo.';
    }
}

// Load current data
$variationTypes = get_product_variation_types($product['id']);
$combos = get_product_variation_combos($product['id']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Variations - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body class="admin-dashboard">
    <div class="admin-container">
        <aside class="admin-sidebar">
            <div class="brand">Britz Blythe <span>Admin</span></div>
            <nav class="admin-nav">
                <a href="index.php">Dashboard</a>
                <a href="products.php">Products</a>
<!-- Deprecated - use product-edit.php#step3 instead -->
<meta http-equiv="refresh" content="0; url=product-edit.php?product=<?= urlencode($productSlug) ?>&step=3">
                <div class="nav-divider"></div>
                <a href="logout.php">Logout</a>
            </nav>
        </aside>
        <main class="admin-main">
            <header class="admin-header">
                <div>
                    <h1>Manage Variations - <?= htmlspecialchars($product['name']) ?></h1>
                    <p>V...</p>
                </div>
            </header>

            <?php if ($message): ?>
                <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <div class="admin-card">
                <h3>Add Variation Type (Color, Size, etc.)</h3>
                <form method="post">
                    <input type="text" name="type_name" placeholder="Variation type name (e.g. Color)" required style="width: 200px;">
                    <button type="submit" name="add_variation_type" class="button button-primary">Add Type</button>
                </form>
            </div>

            <div class="admin-card">
                <h3>Current Variation Types</h3>
                <?php foreach ($variationTypes as $type): ?>
                    <div style="border: 1px solid var(--border); padding: 1rem; margin-bottom: 1rem; border-radius: 8px;">
                        <h4><?= htmlspecialchars($type['name']) ?> <small>(ID: <?= $type['id'] ?>)</small></h4>
                        <form method="post" style="display: inline-block;">
                            <input type="hidden" name="type_id" value="<?= $type['id'] ?>">
                            <input type="text" name="option_value" placeholder="Option (e.g. Red)" required style="width: 120px;">
                            <button type="submit" name="add_option" class="button button-small">Add Option</button>
                        </form>
                        <div style="margin-top: 0.5rem;">
                            Options: <?= implode(', ', array_column(get_variation_options($type['id']), 'value')) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="admin-card">
                <h3>Variation Combinations</h3>
                <form method="post">
                    <h4>New Combo</h4>
                    <textarea name="options" placeholder='[{"type_id":1,"option_id":1},{"type_id":2,"option_id":3}]' rows="3" style="width: 100%; margin-bottom: 1rem;"></textarea>
                    <div style="display: flex; gap: 1rem;">
                        <input type="number" name="combo_price" placeholder="Price" step="0.01" required>
                        <input type="number" name="combo_stock" placeholder="Stock" required>
                        <input type="text" name="combo_image" placeholder="Image path (optional)">
                    </div>
                    <button type="submit" name="save_combo" class="button button-primary">Save Combo</button>
                </form>
            </div>

            <h3>Current Combos</h3>
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr>
                        <th>Options</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Image</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($combos as $combo): ?>
                    <tr>
                        <td><?= htmlspecialchars($combo['options']) ?></td>
                        <td><?= format_price($combo['price']) ?></td>
                        <td><?= $combo['stock'] ?></td>
                        <td><?= htmlspecialchars($combo['image_path'] ?? 'None') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </main>
    </div>
</body>
</html>

