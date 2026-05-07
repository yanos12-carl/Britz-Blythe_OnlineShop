<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$slug = $_GET['slug'] ?? '';
$categories = get_categories();
$category = null;
foreach ($categories as $cat) {
    if ($cat['slug'] === $slug) {
        $category = $cat;
        break;
    }
}

if (!$category) {
    header('Location: categories.php');
    exit;
}

$message = '';
$message_type = 'info';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $newSlug = $_POST['slug'] ?? '';
    if (update_category($slug, $name, $newSlug)) {
        $_SESSION['admin_message'] = 'Category updated successfully!';
        $_SESSION['admin_message_type'] = 'success';
        header('Location: categories.php');
        exit;
    }
    $message = 'Unable to update category. The slug might already be in use.';
    $message_type = 'error';
}

$current_page = 'categories';
$page_title = 'Edit Category';
$page_description = 'Update category details';

ob_start();
?>

<style>
    .edit-form-container {
        max-width: 500px;
    }

    .form-section {
        background: var(--admin-surface);
        border: 1px solid var(--admin-border);
        border-radius: 12px;
        padding: 2rem;
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-group:last-child {
        margin-bottom: 0;
    }

    .form-label {
        display: block;
        font-weight: 600;
        color: var(--admin-text);
        margin-bottom: 0.5rem;
        font-size: 0.95rem;
    }

    .form-input {
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

    .form-input:focus {
        outline: none;
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }

    .form-input:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .form-hint {
        font-size: 0.85rem;
        color: var(--admin-text);
        opacity: 0.6;
        margin-top: 0.5rem;
    }

    .form-actions {
        display: flex;
        gap: 1rem;
        margin-top: 2rem;
    }

    .btn-submit {
        flex: 1;
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

    .btn-cancel {
        flex: 1;
        padding: 0.875rem;
        background: var(--admin-surface);
        color: var(--admin-text);
        border: 1px solid var(--admin-border);
        border-radius: 8px;
        font-weight: 600;
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.25s ease;
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .btn-cancel:hover {
        background: var(--admin-bg);
        border-color: #6366f1;
    }

    .alert-message {
        padding: 1rem;
        border-radius: 12px;
        margin-bottom: 2rem;
    }

    .alert-error {
        background: rgba(239, 68, 68, 0.15);
        border: 1px solid rgba(239, 68, 68, 0.3);
        color: #ef4444;
    }

    .reserved-category-warning {
        background: rgba(245, 158, 11, 0.15);
        border: 1px solid rgba(245, 158, 11, 0.3);
        color: #f59e0b;
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
        font-size: 0.9rem;
    }
</style>

<?php if ($message): ?>
    <div class="alert-message alert-<?= $message_type ?>">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<div class="edit-form-container">
    <?php if ($category['slug'] === 'uncategorized'): ?>
        <div class="reserved-category-warning">
            🔒 <strong>System Default Category</strong><br>
            This is the default category for uncategorized products. The slug cannot be changed, but you can rename the category display name.
        </div>
    <?php endif; ?>

    <form method="POST" class="form-section">
        <div class="form-group">
            <label class="form-label" for="name">Category Name <span style="color: #ef4444;">*</span></label>
            <input
                class="form-input"
                id="name"
                name="name"
                type="text"
                value="<?= htmlspecialchars($category['name']) ?>"
                placeholder="Enter category name"
                required
            />
        </div>

        <div class="form-group">
            <label class="form-label" for="slug">Slug <span style="color: #ef4444;">*</span></label>
            <input
                class="form-input"
                id="slug"
                name="slug"
                type="text"
                value="<?= htmlspecialchars($category['slug']) ?>"
                placeholder="category-slug"
                <?= $category['slug'] === 'uncategorized' ? 'disabled' : 'required' ?>
            />
            <?php if ($category['slug'] === 'uncategorized'): ?>
                <p class="form-hint">This slug is reserved and cannot be changed.</p>
            <?php else: ?>
                <p class="form-hint">URL-friendly identifier for this category (lowercase, hyphens only).</p>
            <?php endif; ?>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-submit">💾 Save Changes</button>
            <a href="categories.php" class="btn-cancel">❌ Cancel</a>
        </div>
    </form>
</div>

<?php
$page_content = ob_get_clean();
require 'admin-master.php';