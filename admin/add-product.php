<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$is_edit = isset($_GET['slug']);
$product = null;
$errors = [];

// Handle Cropped Image Data from Cropper.js
$cropped_base64 = $_POST['cropped_image_data'] ?? null;

// Fetch product data if in edit mode
if ($is_edit) {
    $slug = sanitize($_GET['slug']);
    $product = get_product_by_slug($slug, true);

    if (!$product) {
        $_SESSION['admin_message'] = "Product not found.";
        $_SESSION['admin_message_type'] = "error";
        header('Location: products.php');
        exit;
    }
}

$categories = get_categories();

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $new_slug = sanitize($_POST['slug'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);
    $status = sanitize($_POST['status'] ?? 'draft');
    $description = $_POST['description'] ?? '';
$category = sanitize($_POST['category'] ?? 'uncategorized');

    // Basic Validation
    if (empty($name)) $errors[] = "Product name is required.";
    if (empty($new_slug)) $errors[] = "URL slug is required.";

    // Handle Image Upload
    $image_path = $product['image'] ?? 'public/assets/images/products/placeholder.svg';

    if (!empty($cropped_base64)) {
        // Process Cropped Image (Base64)
$uploadDir = dirname(__DIR__) . '/public/assets/images/products/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $data = explode(',', $cropped_base64);
        $decoded_image = base64_decode($data[1]);
        
        $newFileName = 'prod_cropped_' . uniqid() . '.jpg';
        $fullNewPath = $uploadDir . $newFileName;

        if (file_put_contents($fullNewPath, $decoded_image)) {
            $relative_path = 'assets/images/products/' . $newFileName;
            $processed_image_path = process_product_image_suite($relative_path);
            $image_path = $processed_image_path;
        } else {
            $errors[] = "Failed to save cropped image data.";
        }
    } elseif (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = dirname(__DIR__) . '/public/assets/images/products/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        
        $fileExt = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $newFileName = 'prod_' . uniqid() . '.' . $fileExt;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $newFileName)) {
            $image_path = process_product_image_suite('assets/images/products/' . $newFileName);
        }
    }

    if (empty($errors)) {
        $data = [
            'name' => $name,
            'slug' => $new_slug,
            'price' => $price,
            'stock' => $stock,
            'status' => $status,
            'description' => $description,
            'category' => $category,
            'image' => $image_path
        ];

        // Note: You'll need save_product or update_product functions in your functions.php
        $success = $is_edit ? update_product($product['id'], $data) : create_product($data);

        if ($success) {
            $_SESSION['admin_message'] = "Product " . ($is_edit ? "updated" : "created") . " successfully!";
            $_SESSION['admin_message_type'] = "success";
            header('Location: products.php');
            exit;
        } else {
            $errors[] = "Database error: Could not save product.";
        }
    }
}

$current_page = 'products';
$page_title = $is_edit ? 'Edit Product' : 'Create New Product';
$page_description = $is_edit ? "Updating: <strong>" . htmlspecialchars($product['name']) . "</strong>" : "Add a new item to your store collection.";

ob_start();
?>

<!-- Cropper.js Requirements -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>

<div class="admin-card">
    <?php if (!empty($errors)): ?>
        <div class="form-alert alert-error">
            <div class="alert-header">
                <span class="alert-icon">❌</span>
                <span class="alert-title">Validation Errors</span>
            </div>
            <ul class="alert-list">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="product-form">
        <input type="hidden" name="cropped_image_data" id="cropped_image_data">
        <!-- Main Content Area -->
        <div class="form-main">
            <div class="form-section">
                <h2 class="section-title">Basic Information</h2>
                
                <div class="form-group">
                    <label for="name" class="form-label">Product Name *</label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($product['name'] ?? '') ?>" required class="form-input" placeholder="Enter product name">
                </div>

                <div class="form-group">
                    <label for="slug" class="form-label">URL Slug *</label>
                    <input type="text" id="slug" name="slug" value="<?= htmlspecialchars($product['slug'] ?? '') ?>" required class="form-input" placeholder="url-friendly-slug">
                    <small class="form-help">Used in product URLs. Use hyphens, no spaces.</small>
                </div>
            </div>

            <div class="form-section">
                <h2 class="section-title">Description</h2>
                
                <div class="form-group">
                    <label for="description" class="form-label">Product Description</label>
                    <textarea id="description" name="description" rows="10" class="form-textarea" placeholder="Enter detailed product description..."><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
                    <small class="form-help">HTML is not allowed. Links will be converted automatically.</small>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <aside class="form-sidebar">
            <div class="form-section">
                <h2 class="section-title">Publishing</h2>
                
                <div class="form-group">
                    <label for="category" class="form-label">Category *</label>
                    <select id="category" name="category" required class="form-select">
                        <option value="">Select category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat['slug']) ?>" <?= (($product['category'] ?? '') === $cat['slug']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="status" class="form-label">Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="draft" <?= ($product['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
                        <option value="published" <?= ($product['status'] ?? '') === 'published' ? 'selected' : '' ?>>Published</option>
                        <option value="archived" <?= ($product['status'] ?? '') === 'archived' ? 'selected' : '' ?>>Archived</option>
                    </select>
                    <small class="form-help">Only published items appear in your store.</small>
                </div>
            </div>

            <div class="form-section">
                <h2 class="section-title">Pricing & Inventory</h2>
                
                <div class="form-group">
                    <label for="price" class="form-label">Price ($)</label>
                    <input type="number" id="price" name="price" value="<?= $product['price'] ?? '0.00' ?>" step="0.01" min="0" class="form-input" placeholder="0.00">
                </div>

                <div class="form-group">
                    <label for="stock" class="form-label">Stock Quantity</label>
                    <input type="number" id="stock" name="stock" value="<?= $product['stock'] ?? '0' ?>" min="0" class="form-input" placeholder="0">
                </div>
            </div>

            <div class="form-section">
                <h2 class="section-title">Product Image</h2>
                
                <div class="image-preview-container" style="position: relative;">
                    <?php if ($is_edit && !empty($product['image'])): ?>
                        <a href="<?= SITE_URL ?>/public/product.php?slug=<?= urlencode($product['slug']) ?>" target="_blank" title="View Product Page" class="product-view-link">
                    <?php endif; ?>
                    <div class="image-preview">
                        <img id="imagePreview" src="<?= resolve_asset_url($is_edit ? ($product['image'] ?? null) : null) ?>" alt="<?= htmlspecialchars($product['name'] ?? 'Product') ?>">
                        <span class="preview-label"><?= $is_edit ? 'Current Image' : 'Preview' ?></span>
                    </div>
                    <?php if ($is_edit && !empty($product['image'])): ?></a><?php endif; ?>
                    <button type="button" id="removeImageBtn" class="btn-remove-preview" title="Remove selection" style="display: none;">&times;</button>
                </div>
                
                <div class="form-group">
                    <label for="image" class="form-label">Product Image</label>
                    <input type="file" id="image" name="image" class="form-file" accept="image/*">
                    <small class="form-help">JPEG, PNG, GIF. Max 5MB.</small>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-save">
                    <span class="btn-icon"><?= $is_edit ? '💾' : '✨' ?></span>
                    <?= $is_edit ? 'Update Product' : 'Create Product' ?>
                </button>
                <a href="products.php" class="btn-cancel">Cancel</a>
            </div>
        </aside>
    </form>
</div>

<!-- Cropper Modal -->
<div id="cropperModal" class="cropper-modal">
    <div class="cropper-modal-content">
        <div class="cropper-modal-header">
            <h3>Crop Product Photo</h3>
            <button type="button" class="close-modal" id="cancelCrop">&times;</button>
        </div>
        <div class="cropper-body">
            <img id="cropperImage" src="" style="max-width: 100%; display: block;">
        </div>
        <div class="cropper-modal-footer">
            <small>Tip: All product photos look best when cropped to a square.</small>
            <button type="button" class="btn-save" id="saveCrop" style="width: auto; padding: 0.5rem 2rem;">Apply Crop</button>
        </div>
    </div>
</div>

<style>
    .image-preview-container {
        position: relative;
        margin-bottom: 1rem;
    }

    .btn-remove-preview {
        position: absolute;
        top: 10px;
        right: 10px;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #ef4444;
        color: white;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        line-height: 1;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        z-index: 10;
        transition: all 0.2s ease;
    }

    .btn-remove-preview:hover {
        background: #dc2626;
        transform: scale(1.15);
    }

    /* Cropper Modal Styles */
    .cropper-modal {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 10000;
        background: rgba(0,0,0,0.85);
        backdrop-filter: blur(4px);
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    .cropper-modal.active { display: flex; }
    .cropper-modal-content {
        background: var(--admin-surface);
        border-radius: 20px;
        width: 100%;
        max-width: 600px;
        padding: 24px;
    }
    .cropper-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    .cropper-body {
        max-height: 450px;
        background: #111;
        border-radius: 12px;
        overflow: hidden;
    }
    .cropper-modal-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 24px;
    }
    .close-modal {
        background: none;
        border: none;
        color: var(--admin-text);
        font-size: 2rem;
        cursor: pointer;
    }

    .product-form {
        display: grid;
        grid-template-columns: 1fr 400px;
        gap: 2rem;
    }

    .form-main {
        display: flex;
        flex-direction: column;
        gap: 2rem;
    }

    .form-sidebar {
        display: flex;
        flex-direction: column;
        gap: 2rem;
    }

    .form-section {
        background: var(--admin-bg);
        padding: 1.5rem;
        border-radius: 12px;
        border: 1px solid var(--admin-border);
    }

    .section-title {
        margin: 0 0 1rem 0;
        font-size: 1rem;
        font-weight: 700;
        color: var(--admin-text);
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-group:last-child {
        margin-bottom: 0;
    }

    .form-label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: var(--admin-text);
        font-size: 0.95rem;
    }

    .form-input,
    .form-select,
    .form-textarea {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid var(--admin-border);
        border-radius: 8px;
        background: var(--admin-surface);
        color: var(--admin-text);
        font-family: inherit;
        font-size: 0.95rem;
        transition: all 0.25s ease;
    }

    .form-input:focus,
    .form-select:focus,
    .form-textarea:focus {
        outline: none;
        border-color: var(--admin-accent);
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }

    .form-textarea {
        resize: vertical;
        min-height: 250px;
    }

    .form-file {
        display: block;
        width: 100%;
        padding: 1rem;
        border: 2px dashed var(--admin-border);
        border-radius: 8px;
        background: transparent;
        cursor: pointer;
        transition: all 0.25s ease;
    }

    .form-file:hover {
        border-color: var(--admin-accent);
        background: rgba(99, 102, 241, 0.05);
    }

    .form-help {
        display: block;
        margin-top: 0.4rem;
        font-size: 0.85rem;
        color: var(--admin-text);
        opacity: 0.6;
    }

    .form-alert {
        padding: 1rem;
        border-radius: 12px;
        margin-bottom: 1.5rem;
        border: 1px solid;
    }

    .form-alert.alert-error {
        background: rgba(239, 68, 68, 0.1);
        border-color: rgba(239, 68, 68, 0.3);
        color: #ef4444;
    }

    .alert-header {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 0.75rem;
        font-weight: 600;
    }

    .alert-icon {
        font-size: 1.25rem;
    }

    .alert-list {
        margin: 0;
        padding-left: 1.5rem;
    }

    .alert-list li {
        margin-bottom: 0.25rem;
    }

    .image-preview {
        position: relative;
        border-radius: 8px;
        overflow: hidden;
        margin-bottom: 1rem;
        border: 1px solid var(--admin-border);
    }

    .product-view-link {
        text-decoration: none;
        display: block;
    }

    .product-view-link .image-preview {
        transition: transform 0.25s ease, border-color 0.25s ease, box-shadow 0.25s ease;
    }

    .product-view-link:hover .image-preview {
        transform: translateY(-2px);
        border-color: var(--admin-accent);
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.2);
    }

    .image-preview img {
        width: 100%;
        height: 200px;
        object-fit: cover;
    }

    .preview-label {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: rgba(0, 0, 0, 0.6);
        color: white;
        padding: 0.5rem;
        font-size: 0.85rem;
        font-weight: 600;
    }

    .form-actions {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        margin-top: auto;
        padding-top: 1rem;
        border-top: 1px solid var(--admin-border);
    }

    .btn-save {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        width: 100%;
        padding: 0.875rem;
        background: linear-gradient(135deg, var(--admin-accent), #4f46e5);
        color: white;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.25s ease;
        font-size: 0.95rem;
    }

    .btn-save:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
    }

    .btn-cancel {
        display: block;
        text-align: center;
        padding: 0.875rem;
        background: var(--admin-surface);
        color: var(--admin-text);
        border: 1px solid var(--admin-border);
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.25s ease;
    }

    .btn-cancel:hover {
        background: rgba(99, 102, 241, 0.05);
        border-color: var(--admin-accent);
    }

    .btn-icon {
        font-size: 1.1rem;
    }

    @media (max-width: 768px) {
        .product-form {
            grid-template-columns: 1fr;
        }

        .form-textarea {
            min-height: 150px;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('image');
    const previewImg = document.getElementById('imagePreview');
    const modal = document.getElementById('cropperModal');
    const cropperImg = document.getElementById('cropperImage');
    const saveBtn = document.getElementById('saveCrop');
    const cancelBtn = document.getElementById('cancelCrop');
    const hiddenInput = document.getElementById('cropped_image_data');
    const removeBtn = document.getElementById('removeImageBtn');
    const originalImage = previewImg ? previewImg.src : '';
    
    let cropper;

    function toggleRemoveBtn() {
        if (hiddenInput.value || (fileInput.files && fileInput.files.length > 0)) {
            removeBtn.style.display = 'flex';
        } else {
            removeBtn.style.display = 'none';
        }
    }

    removeBtn.addEventListener('click', function() {
        fileInput.value = '';
        hiddenInput.value = '';
        previewImg.src = originalImage;
        toggleRemoveBtn();
    });

    fileInput.addEventListener('change', function(e) {
        const files = e.target.files;
        if (files && files.length > 0) {
            const reader = new FileReader();
            reader.onload = function(event) {
                cropperImg.src = event.target.result;
                modal.classList.add('active');
                
                if (cropper) cropper.destroy();
                
                cropper = new Cropper(cropperImg, {
                    aspectRatio: 1, // Forces square ratio
                    viewMode: 1,
                    dragMode: 'move',
                    autoCropArea: 0.8,
                    restore: false,
                    guides: true,
                    highlight: false,
                    cropBoxMovable: true,
                    cropBoxResizable: true,
                });
            };
            reader.readAsDataURL(files[0]);
        }
    });

    saveBtn.addEventListener('click', function() {
        if (!cropper) return;

        // High quality output
        const canvas = cropper.getCroppedCanvas({
            width: 1000,
            height: 1000
        });

        const base64Data = canvas.toDataURL('image/jpeg', 0.9);
        
        previewImg.src = base64Data;
        hiddenInput.value = base64Data;
        modal.classList.remove('active');
        toggleRemoveBtn();
    });

    cancelBtn.addEventListener('click', () => {
        modal.classList.remove('active');
        fileInput.value = ''; // Reset file input
        toggleRemoveBtn();
    });
});
</script>
<?php
$page_content = ob_get_clean();
require 'admin-master.php';
?>