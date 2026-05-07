<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions.php';

$category = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'newest';

$products = get_products($category, $search, $sort);
?>

<section class="shop-hero-v2">
    <div class="container">
        <span class="shop-eyebrow fade-in-up">The Artisan Series</span>
        <h1 class="shop-title fade-in-up" style="animation-delay: 0.1s;">Handcrafted Ceramics</h1>
        <p class="shop-subtitle fade-in-up" style="animation-delay: 0.2s;">
            Explore our latest collection of organic mugs, bowls, and vases. Each piece is sculpted by hand and fired with dramatic tonal glazes for a timeless editorial finish.
        </p>
        
        <div class="shop-search-bar fade-in-up" style="animation-delay: 0.3s;">
            <form action="products.php" method="get" class="search-field">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
                </svg>
                <input type="text" name="search" placeholder="Search our artisan goods..." value="<?= htmlspecialchars($search) ?>" autocomplete="off">
                <?php if ($category): ?><input type="hidden" name="category" value="<?= htmlspecialchars($category) ?>"><?php endif; ?>
                <?php if ($sort !== 'newest'): ?><input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>"><?php endif; ?>
            </form>
        </div>
    </div>
</section>

<div class="container">
    <nav class="shop-categories fade-in-up" style="animation-delay: 0.4s;">
        <div class="category-scroll">
            <a href="products.php<?= $search ? '?search='.urlencode($search) : '' ?>" class="category-pill <?= $category === '' ? 'active' : '' ?>">
                All Items <span class="cat-count"><?= get_total_products_count('', $search) ?></span>
            </a>
            <?php foreach (get_categories() as $cat): ?>
                <a href="products.php?category=<?= urlencode($cat['slug']) ?><?= $search ? '&search='.urlencode($search) : '' ?>" 
                   class="category-pill <?= $category === $cat['slug'] ? 'active' : '' ?>">
                    <?= htmlspecialchars($cat['name']) ?> 
                    <span class="cat-count"><?= (int)$cat['product_count'] ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </nav>

    <div class="shop-toolbar-v2 fade-in-up" style="animation-delay: 0.5s;">
        <p class="results-text">
            Showing <strong><?= count($products) ?></strong> <?= count($products) === 1 ? 'product' : 'products' ?>
            <?php if ($search): ?> for "<?= htmlspecialchars($search) ?>"<?php endif; ?>
        </p>
        
        <div class="sort-wrapper">
            <label for="sort-select">Sort by:</label>
            <form method="get" id="sort-form">
                <?php if ($category): ?><input type="hidden" name="category" value="<?= htmlspecialchars($category) ?>"><?php endif; ?>
                <?php if ($search): ?><input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>"><?php endif; ?>
                <select name="sort" id="sort-select" onchange="this.form.submit()">
                    <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest Arrival</option>
                    <option value="popular" <?= $sort === 'popular' ? 'selected' : '' ?>>Best Selling</option>
                    <option value="price-asc" <?= $sort === 'price-asc' ? 'selected' : '' ?>>Price: Low to High</option>
                    <option value="price-desc" <?= $sort === 'price-desc' ? 'selected' : '' ?>>Price: High to Low</option>
                </select>
            </form>
        </div>
    </div>

    <main class="product-grid-v2">
        <?php if (empty($products)): ?>
            <div class="shop-empty fade-in-up">
                <div style="font-size: 3rem; margin-bottom: 1rem;">🔍</div>
                <h3>No products found</h3>
                <p>We couldn't find anything matching your current filters.</p>
                <a href="products.php" class="button button-primary" style="margin-top: 1.5rem;">Clear All Filters</a>
            </div>
        <?php else: ?>
            <?php foreach ($products as $i => $product): ?>
                <?php 
                    $isOutOfStock = ($product['stock'] ?? 0) <= 0;
                    $isLowStock = !$isOutOfStock && ($product['stock'] ?? 0) <= 5;
                ?>
                <article class="product-card-v2 fade-in-up" style="--order: <?= $i % 8 ?>;">
                    <a href="product.php?slug=<?= $product['slug'] ?>" class="card-image-wrap">
                        <img src="<?= resolve_asset_url($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" loading="lazy" class="lazy-image">
                        
                        <?php if ($isOutOfStock): ?>
                            <span class="card-badge soldout">Sold Out</span>
                        <?php elseif ($isLowStock): ?>
                            <span class="card-badge lowstock">Low Stock</span>
                        <?php endif; ?>

                        <button type="button" class="quick-add-btn add-to-cart-btn" data-slug="<?= $product['slug'] ?>" title="Quick Add to Cart">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14m-7-7v14"/></svg>
                        </button>
                    </a>
                    
                    <div class="card-body">
                        <h3 class="card-name">
                            <a href="product.php?slug=<?= $product['slug'] ?>"><?= htmlspecialchars($product['name']) ?></a>
                        </h3>
                        <p class="card-desc"><?= htmlspecialchars($product['excerpt']) ?></p>
                        
                        <div class="card-footer">
                            <span class="card-price"><?= format_price($product['price']) ?></span>
                            <div class="card-rating">
                                <?= render_stars((int)$product['average_rating']) ?>
                                <span class="sold-count"><?= (int)$product['sold_count'] ?> sold</span>
                            </div>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>