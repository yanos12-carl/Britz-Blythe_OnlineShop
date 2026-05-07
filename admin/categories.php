<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$message = '';
$message_type = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_slug'])) {
        $delete_slug = sanitize($_POST['delete_slug']);
        
        if ($delete_slug === 'uncategorized') {
            $message = 'The "uncategorized" category is reserved by the system and cannot be deleted.';
            $message_type = 'error';
        } elseif (delete_category($delete_slug)) {
            $message = 'Category deleted successfully.';
            $message_type = 'success';
        } else {
            $message = 'Unable to delete category. It may have associated products.';
            $message_type = 'error';
        }
    } else {
        $name = sanitize($_POST['name'] ?? '');
        $slug = sanitize($_POST['slug'] ?? '');

        if ($name && create_category($slug ?: $name, $name)) {
            $message = 'Category created successfully!';
            $message_type = 'success';
        } else {
            $message = 'Unable to add category. The slug might already be in use.';
            $message_type = 'error';
        }
    }
}

$categories = get_categories();

// Calculate the maximum product count to scale relative progress bars
$max_product_count = !empty($categories) ? (int)max(array_column($categories, 'product_count')) : 0;

$current_page = 'categories';
$page_title = 'Categories';
$page_description = 'Manage product categories and organize your shop.';
ob_start();
?>

<style>
    .category-management-layout {
        display: grid;
        grid-template-columns: 350px 1fr;
        gap: 2rem;
        align-items: start;
    }

    .form-group { margin-bottom: 1.25rem; }

    .form-label {
        display: block;
        font-weight: 600;
        color: var(--admin-text);
        margin-bottom: 0.5rem;
        font-size: 0.9rem;
        opacity: 0.9;
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
    }

    .form-input:focus {
        outline: none;
        border-color: var(--admin-accent);
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }

    .btn-submit {
        width: 100%;
        padding: 0.75rem 1.5rem;
        background: linear-gradient(135deg, var(--admin-accent), #4f46e5);
        color: white;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.25s ease;
    }

    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
    }

    .categories-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        gap: 1.25rem;
    }

    .category-card {
        background: var(--admin-surface);
        border: 1px solid var(--admin-border);
        border-radius: 16px;
        padding: 1rem;
        transition: all 0.25s ease;
        position: relative;
        display: flex;
        flex-direction: column;
    }

    .category-card:hover {
        border-color: var(--admin-accent);
        transform: translateY(-3px);
        box-shadow: 0 10px 20px -5px rgba(0, 0, 0, 0.1);
    }

    .category-icon-wrapper {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        background: var(--admin-bg);
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid var(--admin-border);
        flex-shrink: 0;
        font-size: 1.25rem;
    }

    .category-card-content {
        flex: 1;
        min-width: 0;
    }

    .category-stats {
        display: inline-flex;
        align-items: center;
        padding: 0.25rem 0.6rem;
        background: rgba(99, 102, 241, 0.1);
        color: var(--admin-accent);
        border-radius: 6px;
        font-size: 0.7rem;
        font-weight: 700;
    }

    .category-progress-track {
        width: 100%;
        height: 4px;
        background: var(--admin-border);
        border-radius: 10px;
        margin-top: 0.5rem;
        overflow: hidden;
    }

    .category-progress-bar {
        height: 100%;
        background: linear-gradient(90deg, var(--admin-accent), #8b5cf6);
        border-radius: 10px;
        transition: width 1s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .category-name {
        font-size: 1rem;
        font-weight: 700;
        color: var(--admin-text);
        margin-bottom: 0.25rem;
    }

    .category-slug {
        font-size: 0.75rem;
        color: var(--admin-text);
        opacity: 0.5;
        margin-bottom: 0.75rem;
        word-break: break-all;
    }

    .category-actions {
        position: absolute;
        top: 0.5rem;
        right: 0.5rem;
        display: flex;
        gap: 0.25rem;
        opacity: 1 !important;
        z-index: 10;
        transition: opacity 0.2s ease;
    }

    .btn-delete-category {
        width: 28px;
        height: 28px;
        position: relative;
        background: #ef4444;
        color: white;
        border: 1px solid #dc2626;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 0.8rem;
        transition: all 0.2s ease;
    }
    .btn-delete-category:hover { background: #dc2626; border-color: #b91c1c; }

    .btn-edit-category {
        width: 28px;
        height: 28px;
        position: relative;
        background: var(--admin-surface);
        border: 1px solid var(--admin-border);
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 0.8rem;
        color: var(--admin-text);
        transition: all 0.2s;
    }
    .btn-edit-category:hover { border-color: var(--admin-accent); color: var(--admin-accent); }

    .delete-form {
        display: inline;
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

    /* Custom Confirmation Modal Styles */
    .custom-modal {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 10000;
        background: rgba(0,0,0,0.5);
        backdrop-filter: blur(6px);
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    .custom-modal.active { display: flex; }
    .custom-modal .admin-card {
        width: 100%;
        max-width: 450px;
        padding: 2rem;
        animation: modalScale 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
    @keyframes modalScale {
        from { opacity: 0; transform: scale(0.9); }
        to { opacity: 1; transform: scale(1); }
    }
    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
    }
    .modal-header h3 { margin: 0; font-size: 1.25rem; color: var(--admin-text); }
    .modal-body { margin-bottom: 2rem; color: var(--admin-text); opacity: 0.8; line-height: 1.5; }
    .modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: 1rem;
    }
    .btn-confirm-delete {
        padding: 0.75rem 1.5rem;
        background: #ef4444;
        color: white;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .btn-confirm-delete:hover {
        background: #dc2626;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
    }

</style>

<?php if ($message): ?>
    <div class="alert-message alert-<?= $message_type ?>">
        <?= ($message_type === 'success' ? '✅' : '⚠️') ?> <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<div class="category-management-layout">
    <aside class="admin-card">
        <h2 style="margin: 0 0 1.5rem 0; font-size: 1.1rem;">Add New Category</h2>
        <form method="POST" id="categoryForm">

            <div class="form-group">
                <label class="form-label">Category Name *</label>
                <input type="text" name="name" id="catName" class="form-input" placeholder="e.g., Living Room" required>
            </div>

            <div class="form-group">
                <label class="form-label">URL Slug (Optional)</label>
                <input type="text" name="slug" id="catSlug" class="form-input" placeholder="e.g., living-room">
            </div>

            <button type="submit" class="btn-submit">✨ Save Category</button>
        </form>
    </aside>

    <section>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
            <h2 style="color: var(--admin-text); margin: 0; font-size: 1.1rem;">Existing Collections (<?= count($categories) ?>)</h2>
            <div style="position: relative; width: 200px;">
                <input type="text" id="categorySearch" class="form-input" placeholder="Search..." style="padding: 0.5rem 0.75rem; font-size: 0.85rem;">
            </div>
        </div>
        
        <?php if (empty($categories)): ?>
            <div class="admin-card" style="text-align: center; padding: 3rem; opacity: 0.6;">
                <div style="font-size: 2rem;">📂</div>
                <p>No categories found.</p>
            </div>
        <?php else: ?>
            <div class="categories-grid">
                <?php foreach ($categories as $cat): ?>
                    <?php 
                        $current_cat_count = (int)($cat['product_count'] ?? 0);
                        $relative_width = $max_product_count > 0 ? ($current_cat_count / $max_product_count) * 100 : 0;
                    ?>
                    <article class="category-card">
                        <div class="category-icon-wrapper">
                            📂
                        </div>
                        
                        <div class="category-card-content">
                            <h3 class="category-name"><?= htmlspecialchars($cat['name']) ?></h3>
                            <div class="category-slug">/<?= htmlspecialchars($cat['slug']) ?></div>
                            <div class="category-stats">📦 <?= $current_cat_count ?> Products</div>
                            <div class="category-progress-track" title="<?= $current_cat_count ?> items in this category">
                                <div class="category-progress-bar" style="width: <?= $relative_width ?>%;"></div>
                            </div>
                        </div>

                        <div class="category-actions">
                            <?php if ($cat['slug'] !== 'uncategorized'): ?>
                                <form method="POST" class="delete-form" data-name="<?= htmlspecialchars($cat['name']) ?>">
                                    <input type="hidden" name="delete_slug" value="<?= htmlspecialchars($cat['slug']) ?>">
                                    <button type="button" class="btn-delete-category js-delete-trigger" data-tooltip="Delete Category">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" style="width: 18px; height: 18px;"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </form>
                                <a href="category-edit.php?slug=<?= urlencode($cat['slug']) ?>" class="btn-edit-category" style="text-decoration: none;" data-tooltip="Edit Category">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14.25v4.5a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18V7.5a2.25 2.25 0 012.25-2.25H9.75"/></svg>
                                </a>
                            <?php else: ?>
                                <span title="System Protected" style="font-size: 0.8rem;">🔒</span>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="custom-modal">
    <div class="admin-card">
        <div class="modal-header">
            <h3>Delete Category?</h3>
            <button type="button" class="close-modal" onclick="closeDeleteModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to delete <strong id="deleteCategoryName"></strong>?</p>
            <p style="font-size: 0.85rem; opacity: 0.7; margin-top: 0.5rem;">Associated products will be moved to the <strong>Uncategorized</strong> collection.</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="form-input" style="width: auto; background: transparent !important; border: 1px solid var(--admin-border); cursor: pointer;" onclick="closeDeleteModal()">Cancel</button>
            <button type="button" id="confirmDeleteBtn" class="btn-confirm-delete">Delete Permanently</button>
        </div>
    </div>
</div>

<?php
/**
 * Inline Scripts for Category Functionality
 */
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Slug Auto-generation
    const nameInput = document.getElementById('catName');
    const slugInput = document.getElementById('catSlug');
    if (nameInput && slugInput) {
        nameInput.addEventListener('input', function() {
            slugInput.value = this.value.toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
        });
    }

    // 2. Real-time Search Filter
    const searchInput = document.getElementById('categorySearch');
    searchInput.addEventListener('input', function() {
        const term = this.value.toLowerCase();
        document.querySelectorAll('.category-card').forEach(card => {
            const name = card.querySelector('.category-name').textContent.toLowerCase();
            card.style.display = name.includes(term) ? 'flex' : 'none';
        });
    });

    // 3. Custom Delete Modal Logic
    const deleteModal = document.getElementById('deleteModal');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const deleteCategoryNameLabel = document.getElementById('deleteCategoryName');
    let formToSubmit = null;

    document.querySelectorAll('.js-delete-trigger').forEach(btn => {
        btn.addEventListener('click', function() {
            formToSubmit = this.closest('.delete-form');
            deleteCategoryNameLabel.textContent = formToSubmit.dataset.name;
            deleteModal.classList.add('active');
        });
    });

    window.closeDeleteModal = function() {
        deleteModal.classList.remove('active');
        formToSubmit = null;
    };

    confirmDeleteBtn.addEventListener('click', function() {
        if (formToSubmit) formToSubmit.submit();
    });

    // Close modal on click outside
    deleteModal.addEventListener('click', (e) => {
        if (e.target === deleteModal) closeDeleteModal();
    });
});
</script>

<?php
$page_content = ob_get_clean();
require 'admin-master.php';
