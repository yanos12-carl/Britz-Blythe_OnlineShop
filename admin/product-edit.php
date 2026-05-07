<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$slug = $_GET['slug'] ?? '';
$product = $slug ? get_product_by_slug($slug) : null;
$message = '';
$message_type = 'info';
$categories = get_categories();

// Check for session messages
if (isset($_SESSION['admin_message'])) {
    $message = $_SESSION['admin_message'];
    $message_type = $_SESSION['admin_message_type'] ?? 'info';
    unset($_SESSION['admin_message'], $_SESSION['admin_message_type']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'slug' => sanitize($_POST['slug'] ?? ''),
        'name' => sanitize($_POST['name'] ?? ''),
        'price' => (float)($_POST['price'] ?? 0),
        'stock' => (int)($_POST['stock'] ?? 0),
        'category' => sanitize($_POST['category'] ?? ''),
        'image' => sanitize($_POST['image'] ?? ''),
        'excerpt' => sanitize($_POST['excerpt'] ?? ''),
        'description' => $_POST['description'] ?? ''
    ];
    $productId = (int)($product['id'] ?? 0);
    try {
        $success = $productId > 0 ? update_product($productId, $data) : create_product($data);
        if ($success) {
            $_SESSION['admin_message'] = 'Product saved successfully!';
            $_SESSION['admin_message_type'] = 'success';
            header('Location: products.php');
            exit;
        }
        $message = 'Unable to save product.';
        $message_type = 'error';
    } catch (Throwable $e) {
        $message = 'Error: ' . $e->getMessage();
        $message_type = 'error';
    }
}

$current_page = 'products';
$page_title = $product ? 'Edit Product' : 'Add Product';
$page_description = $product ? 'Update product details' : 'Create a new product';

ob_start();
?>

<style>
    .form-container {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 2rem;
    }
    .form-section {
        background: var(--admin-surface);
        border: 1px solid var(--admin-border);
        border-radius: 12px;
        padding: 2rem;
    }
    .form-section-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--admin-text);
        margin: 0 0 1.5rem 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .form-group {
        margin-bottom: 1.5rem;
    }
    .form-label {
        display: block;
        font-weight: 600;
        color: var(--admin-text);
        margin-bottom: 0.5rem;
        font-size: 0.95rem;
    }
    .form-label .required {
        color: #ef4444;
    }
    .form-input, .form-textarea, .form-select {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid var(--admin-border);
        border-radius: 8px;
        background: var(--admin-bg);
        color: var(--admin-text);
        font-family: inherit;
        font-size: 0.95rem;
        transition: all 0.25s ease;
    }
    .form-input:focus, .form-textarea:focus, .form-select:focus {
        outline: none;
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }
    .form-textarea {
        resize: vertical;
        min-height: 100px;
    }
    .sidebar-section {
        background: var(--admin-surface);
        border: 1px solid var(--admin-border);
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }
    .sidebar-section-title {
        font-weight: 700;
        color: var(--admin-text);
        margin: 0 0 1rem 0;
        font-size: 1rem;
    }
    .image-preview {
        width: 100%;
        height: 150px;
        background: var(--admin-bg);
        border: 2px dashed var(--admin-border);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1rem;
        overflow: hidden;
        cursor: pointer;
    }
    .image-preview img {
        max-width: 100%;
        max-height: 100%;
        object-fit: cover;
    }
    .btn-submit {
        width: 100%;
        padding: 0.875rem;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: white;
        border: none;
        border-radius: 8px;
        font-weight: 700;
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.25s ease;
    }
    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(99, 102, 241, 0.3);
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
    @media (max-width: 1024px) {
        .form-container {
            grid-template-columns: 1fr;
        }
    }
</style>

<?php if ($message): ?>
<div class="alert-message alert-<?= $message_type ?>">
    <?= htmlspecialchars($message) ?>
</div>
<?php endif; ?>

<form method="POST" class="form-container">
    <input type="hidden" name="original_slug" value="<?= htmlspecialchars($product['slug'] ?? '') ?>">

    <!-- Main Content -->
    <div>
        <div class="form-section">
            <h2 class="form-section-title">📋 Basic Information</h2>
            <div class="form-group">
                <label class="form-label" for="name">Product Name <span class="required">*</span></label>
                <input class="form-input" id="name" name="name" type="text" value="<?= htmlspecialchars($product['name'] ?? '') ?>" placeholder="Enter product name" required />
            </div>
            <div class="form-group">
                <label class="form-label" for="excerpt">Excerpt</label>
                <textarea class="form-textarea" id="excerpt" name="excerpt"><?= htmlspecialchars($product['excerpt'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label" for="description">Description</label>
                <textarea class="form-textarea" id="description" name="description" style="min-height: 200px;"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div>
        <div class="sidebar-section">
            <h3 class="sidebar-section-title">⚙️ Settings</h3>
            <div class="form-group">
                <label class="form-label" for="category">Category <span class="required">*</span></label>
                <select class="form-select" id="category" name="category" required>
                    <option value="">Select category</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= htmlspecialchars($cat['slug']) ?>" <?= (($product['category'] ?? '') == $cat['slug']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="price">Price <span class="required">*</span></label>
                <input class="form-input" id="price" name="price" type="number" step="0.01" value="<?= $product['price'] ?? '' ?>" placeholder="0.00" required />
            </div>
            <div class="form-group">
                <label class="form-label" for="stock">Stock</label>
                <input class="form-input" id="stock" name="stock" type="number" value="<?= $product['stock'] ?? 0 ?>" placeholder="0" />
            </div>
        </div>
        <div class="sidebar-section">
            <h3 class="sidebar-section-title">🖼️ Image</h3>
            <div class="image-preview" onclick="document.getElementById('image').click()">
                <img id="imagePreview" src="<?= resolve_asset_url($product['image'] ?? 'assets/images/products/placeholder.svg') ?>" alt="Preview" onerror="this.src='<?= resolve_asset_url('assets/images/products/placeholder.svg') ?>'" />
            </div>
            <div class="form-group">
                <input class="form-input" id="image" name="image" type="text" value="<?= htmlspecialchars($product['image'] ?? '') ?>" placeholder="assets/images/products/filename.jpg" />
            </div>
        </div>
        <button type="submit" class="btn-submit"><?= $product ? 'Update Product' : 'Create Product' ?></button>
    </div>
</form>

<?php
$page_content = ob_get_clean();
require 'admin-master.php';
?>

