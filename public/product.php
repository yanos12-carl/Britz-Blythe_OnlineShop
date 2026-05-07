<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/products-api.php';

$slug = $_GET['slug'] ?? '';
$product = get_product_by_slug($slug);

if (!$product) {
    header('Location: products.php');
    exit;
}

// Get full product data including variants and media
$productData = get_product_complete((int)$product['id']);

if (!$productData) {
    // Fallback to basic data if complete fetch failed (e.g. DB is down or using default products)
    $productData = $product;
    $productData['media'] = $productData['media'] ?? [];
    $productData['variants'] = $productData['variants'] ?? [];
    $productData['discount_price'] = $productData['discount_price'] ?? null;
    $productData['sold_count'] = $productData['sold_count'] ?? 0;
}

$ratings = get_product_rating_summary((int)$product['id']);
$attrGroups = get_product_attribute_groups($productData['variants']);
$recommendations = get_recommended_products((int)$product['id'], 6);
?>

<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/product-view.css">

<div class="container product-view-page">

    <nav class="product-breadcrumb fade-in-up" style="animation-delay: 0s;">
        <a href="products.php" class="back-link">← Back to Collection</a>
    </nav>

    <div class="product-main-layout">
        <!-- Gallery Section -->
        <div class="product-gallery fade-in-up" style="animation-delay: 0.1s;">
            <div class="main-image-wrapper">
                <img id="mainProductImage" class="lazy-image" src="<?= resolve_asset_url($productData['image']) ?>" alt="<?= htmlspecialchars($productData['name']) ?>">
            </div>
            <div class="thumbnail-grid" id="productThumbnails">
                <div class="thumb-item active" data-url="<?= resolve_asset_url($productData['image']) ?>">
                    <img class="lazy-image" src="<?= resolve_asset_url($productData['image']) ?>">
                </div>
                <?php foreach ($productData['media'] as $media): ?>
                    <div class="thumb-item" data-url="<?= resolve_asset_url($media['media_url']) ?>">
                        <img class="lazy-image" src="<?= resolve_asset_url($media['media_url']) ?>">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Details Section -->
        <div class="product-info-panel fade-in-up" style="animation-delay: 0.2s;">
            <span class="shop-eyebrow"><?= htmlspecialchars(ucfirst($productData['category'])) ?></span>
            <h1 class="product-title"><?= htmlspecialchars($productData['name']) ?></h1>
            
            <div class="product-stats">
                <div class="rating-box">
                    <span class="rating-value"><?= number_format($ratings['average'], 1) ?></span> <!-- Display actual average -->
                    <?= render_stars((int)$ratings['average']) ?>
                </div>
                <span class="stat-divider">|</span>
                <span class="review-count"><?= $ratings['total'] ?> Reviews</span>
                <span class="stat-divider">|</span>
                <span class="sold-count"><?= $productData['sold_count'] ?> Sold</span>
            </div>

            <div class="price-section-v2" id="priceDisplay">
                <div class="price-wrap">
                    <span class="current-price"><?= format_price($productData['price']) ?></span>
                </div>
                <?php if (isset($productData['discount_price']) && $productData['discount_price'] !== null && $productData['discount_price'] > 0): ?>
                    <div class="discount-wrap" style="display: flex; align-items: center; gap: 1rem; margin-top: 0.5rem;">
                        <span class="original-price"><?= format_price($productData['price']) ?></span>
                        <span class="discount-badge">Save <?= round((($productData['price'] - $productData['discount_price']) / $productData['price']) * 100) ?>%</span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Variations Selection -->
            <div class="variations-selector" id="variationManager" data-product-slug="<?= htmlspecialchars($product['slug']) ?>" data-variants='<?= htmlspecialchars(json_encode($productData['variants'])) ?>'>
                <?php foreach ($attrGroups as $groupName => $options): ?>
                    <div class="variation-group" data-group="<?= htmlspecialchars($groupName) ?>">
                        <label><?= htmlspecialchars($groupName) ?></label>
                        <div class="variation-options">
                            <?php foreach ($options as $option): ?>
                                <button type="button" class="var-btn" data-value="<?= htmlspecialchars($option) ?>">
                                    <?= htmlspecialchars($option) ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="purchase-controls">
                <div class="quantity-selector" style="margin-bottom: 2rem;">
                    <label class="section-label">Quantity</label>
                    <div class="qty-stepper">
                        <button type="button" class="qty-btn" id="qtyMinus">−</button>
                        <input type="number" id="productQty" value="1" min="1" max="<?= $productData['stock'] ?>" readonly>
                        <button type="button" class="qty-btn" id="qtyPlus">+</button>
                    </div>
                    <span class="stock-info" id="stockDisplay" style="margin-top: 0.75rem; display: block; font-size: 0.9rem; color: var(--text-muted);"><?= $productData['stock'] ?> pieces available</span>
                </div>

                <div class="action-buttons" style="display: flex; gap: 1rem; margin-top: 2rem;">
                    <button type="button" id="addToCartBtn" class="button button-ghost" style="flex: 1; padding: 1.2rem;" disabled>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.5rem;"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                        Add to Cart
                    </button>
                    <button type="button" id="buyNowBtn" class="button button-primary" style="flex: 1; padding: 1.2rem;" disabled>
                        Buy Now
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Description & Reviews Section (Tabs) -->
    <div class="product-extra-info fade-in-up" style="animation-delay: 0.3s; margin-top: 4rem;">
        <div class="info-tabs">
            <button class="active">Product Description</button>
            <button>Reviews (<?= $ratings['total'] ?>)</button>
        </div>
        <div class="info-content">
            <div class="rich-content">
                <?= nl2br(htmlspecialchars($productData['description'])) ?>
            </div>
        </div>
    </div>

    <!-- Recommendations Section -->
    <section class="recommendations fade-in-up" style="animation-delay: 0.4s;">
        <div class="section-header" style="text-align: left; margin-bottom: 2rem;">
            <span class="shop-eyebrow">Discovery</span>
            <h2 class="shop-title" style="font-size: 2rem;">You May Also Like</h2>
        </div>
        
        <div class="product-grid-v2">
            <?php foreach ($recommendations as $rec): ?>
                <article class="product-card-v2">
                    <a href="product.php?slug=<?= $rec['slug'] ?>" class="card-image-wrap">
                        <img src="<?= resolve_asset_url($rec['image']) ?>" alt="<?= htmlspecialchars($rec['name']) ?>" loading="lazy" class="lazy-image">
                    </a>
                    <div class="card-body">
                        <h3 class="card-name">
                            <a href="product.php?slug=<?= $rec['slug'] ?>"><?= htmlspecialchars($rec['name']) ?></a>
                        </h3>
                        <div class="card-footer">
                            <span class="card-price"><?= format_price($rec['price']) ?></span>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
