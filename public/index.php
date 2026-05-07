<?php
require_once __DIR__ . '/../includes/header.php';
$categories = get_categories();
$featured = array_slice(get_products(), 0, 4);
$reviews = array_slice(get_reviews(true), 0, 3);
?>

<style>
/* Home Page Specific Styles */
.home-hero {
    position: relative;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 8rem 1.5rem;
    background: linear-gradient(135deg, var(--bg) 0%, rgba(212, 175, 55, 0.05) 50%, var(--bg) 100%);
    overflow: hidden;
}

.home-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23d4af37' fill-opacity='0.04'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    opacity: 0.8;
    pointer-events: none;
}

.home-hero .hero-inner {
    position: relative;
    z-index: 2;
    max-width: 950px;
}

.home-hero .eyebrow {
    display: inline-block;
    font-size: 0.85rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.15em;
    color: var(--accent);
    background: var(--surface);
    backdrop-filter: var(--blur);
    -webkit-backdrop-filter: var(--blur);
    padding: 0.5rem 1.25rem;
    border-radius: 99px;
    border: 1px solid var(--glass-border);
    margin-bottom: 2rem;
    box-shadow: 0 4px 12px rgba(212, 175, 55, 0.08);
}

.home-hero h1 {
    font-size: clamp(3rem, 8vw, 6rem);
    font-weight: 900;
    line-height: 1;
    letter-spacing: -0.03em;
    margin: 0 0 1.5rem;
    color: var(--text);
    font-family: 'Playfair Display', serif;
}

.home-hero .tagline {
    font-size: 1.35rem;
    font-weight: 600;
    color: var(--accent);
    margin-bottom: 0.5rem;
    letter-spacing: 0.05em;
}

.home-hero p {
    font-size: 1.25rem;
    color: var(--text-muted);
    max-width: 600px;
    margin: 0 auto 2.5rem;
    line-height: 1.6;
}

.home-hero .hero-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

.home-hero .hero-actions a {
    padding: 1rem 2.5rem;
    border-radius: 100px;
    font-weight: 700;
    text-decoration: none;
    transition: all 0.3s ease;
    font-size: 0.95rem;
}

.home-hero .hero-actions .btn-primary {
    background: var(--gradient-warm);
    color: #000;
    box-shadow: 0 8px 20px rgba(212, 175, 55, 0.25);
}

.home-hero .hero-actions .btn-primary:hover {
    transform: translateY(-3px);
    box-shadow: 0 0 28px rgba(212, 175, 55, 0.5);
}

.home-hero .hero-actions .btn-secondary {
    background: var(--glass-bg);
    color: var(--text);
    border: 1px solid var(--glass-border);
    backdrop-filter: var(--blur);
    -webkit-backdrop-filter: var(--blur);
}

.home-hero .hero-actions .btn-secondary:hover {
    background: rgba(212, 175, 55, 0.1);
    border-color: var(--accent);
    transform: translateY(-3px);
}

/* Floating decorative shapes */
.home-hero .shape {
    position: absolute;
    border-radius: 50%;
    filter: blur(80px);
    opacity: 0.25;
    pointer-events: none;
    z-index: 1;
}

.home-hero .shape-1 {
    width: 500px;
    height: 500px;
    background: hsl(25 40% 50%);
    top: -15%;
    right: -10%;
}

.home-hero .shape-2 {
    width: 400px;
    height: 400px;
    background: hsl(40 60% 55%);
    bottom: -15%;
    left: -10%;
    opacity: 0.15;
}

/* Benefits strip */
.benefits-strip {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 2rem;
    padding: 3rem 0;
    border-top: 1px solid var(--glass-border);
    border-bottom: 1px solid var(--glass-border);
    margin-bottom: 4rem;
}

.benefit-block {
    text-align: center;
    padding: 1rem;
}

.benefit-block .icon {
    width: 56px;
    height: 56px;
    border-radius: 16px;
    background: var(--surface);
    border: 1px solid var(--glass-border);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin-bottom: 1rem;
    transition: all 0.3s ease;
}

.benefit-block:hover .icon {
    transform: translateY(-4px);
    box-shadow: var(--shadow-md);
    border-color: var(--accent);
}

.benefit-block h4 {
    margin: 0 0 0.4rem;
    font-size: 1rem;
    font-weight: 700;
    color: var(--text);
}

.benefit-block p {
    margin: 0;
    font-size: 0.85rem;
    color: var(--text-muted);
    line-height: 1.5;
}

/* Category cards v2 */
.category-card-v2 {
    background: var(--surface);
    border: 1px solid var(--glass-border);
    border-radius: 20px;
    padding: 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    text-decoration: none;
    transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
}

.category-card-v2:hover {
    transform: translateY(-6px);
    box-shadow: var(--shadow-xl);
    border-color: var(--accent);
}

.category-card-v2 h3 {
    margin: 0;
    font-size: 1.35rem;
    color: var(--text);
    font-weight: 800;
}

.category-card-v2 .meta {
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: var(--text-muted);
    margin-bottom: 0.5rem;
}

.category-card-v2 .arrow {
    font-size: 1.5rem;
    opacity: 0.4;
    transition: all 0.3s ease;
}

.category-card-v2:hover .arrow {
    opacity: 1;
    transform: translateX(4px);
    color: var(--accent);
}

/* Section header v2 */
.section-header {
    text-align: center;
    margin-bottom: 3rem;
}

.section-header h2 {
    font-size: clamp(2rem, 4vw, 2.75rem);
    font-weight: 900;
    margin: 0 0 0.5rem;
    letter-spacing: -0.02em;
}

.section-header p {
    color: var(--text-muted);
    font-size: 1.1rem;
    margin: 0;
}

/* CTA Banner v2 */
.cta-banner {
    position: relative;
    background: var(--text);
    color: var(--bg);
    border-radius: 32px;
    padding: 5rem 2rem;
    text-align: center;
    overflow: hidden;
}

.cta-banner .cta-content {
    position: relative;
    z-index: 2;
}

.cta-banner h2 {
    font-size: clamp(2rem, 5vw, 3.5rem);
    margin-bottom: 1rem;
    font-weight: 900;
    color: inherit;
}

.cta-banner p {
    opacity: 0.7;
    max-width: 600px;
    margin: 0 auto 2rem;
    font-size: 1.1rem;
}

.cta-banner .btn-light {
    display: inline-block;
    background: var(--bg);
    color: var(--text);
    padding: 1.1rem 3rem;
    border-radius: 100px;
    font-weight: 700;
    text-decoration: none;
    transition: all 0.3s ease;
}

.cta-banner .btn-light:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}

.cta-banner .glow {
    position: absolute;
    top: 0;
    right: 0;
    width: 400px;
    height: 400px;
    background: var(--accent);
    filter: blur(150px);
    opacity: 0.15;
    pointer-events: none;
}

@media (max-width: 768px) {
    .home-hero {
        min-height: 70vh;
        padding: 4rem 1rem;
    }
    .home-hero h1 {
        font-size: clamp(2.5rem, 10vw, 4rem);
    }
    .benefits-strip {
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }
    .category-card-v2 {
        padding: 1.5rem;
    }
}
</style>

<!-- Hero -->
<section class="home-hero">
    <div class="shape shape-1"></div>
    <div class="shape shape-2"></div>
    <div class="hero-inner">
        <p class="eyebrow fade-in-up">Handcrafted Excellence</p>
        <p class="tagline fade-in-up" style="animation-delay: 0.05s;">Britz Blythe</p>
        <h1 class="fade-in-up" style="animation-delay: 0.1s;">Artisan Ceramics Redefined</h1>
        <p class="fade-in-up" style="animation-delay: 0.2s;">Discover our collection of premium handcrafted ceramic products, each piece carefully designed with traditional techniques and modern aesthetics. From elegant mugs to sculptural vases, experience the warmth of artisan craftsmanship.</p>
        <div class="hero-actions fade-in-up" style="animation-delay: 0.3s;">
            <a href="<?= SITE_URL ?>/public/products.php" class="btn-primary">View Collection</a>
            <a href="<?= SITE_URL ?>/public/about.php" class="btn-secondary">Our Story</a>
        </div>
    </div>
</section>

<div class="container">
    <!-- Benefits Strip -->
    <div class="benefits-strip fade-in-up">
        <div class="benefit-block">
            <div class="icon">🏺</div>
            <h4>Handcrafted</h4>
            <p>Each piece lovingly made by artisan hands.</p>
        </div>
        <div class="benefit-block">
            <div class="icon">🌍</div>
            <h4>Worldwide Shipping</h4>
            <p>Carefully packaged and delivered globally.</p>
        </div>
        <div class="benefit-block">
            <div class="icon">✨</div>
            <h4>Premium Quality</h4>
            <p>Finest materials and expert craftsmanship.</p>
        </div>
        <div class="benefit-block">
            <div class="icon">💚</div>
            <h4>Sustainable</h4>
            <p>Eco-conscious production methods.</p>
        </div>
    </div>

    <!-- Categories -->
    <div class="section-header fade-in-up">
        <h2>Shop by Category</h2>
        <p>Browse our curated collections</p>
    </div>
    <div class="category-grid" style="margin-bottom: 5rem;">
        <?php $cat_index = 0; foreach (array_slice($categories, 0, 4) as $category): ?>
            <a href="<?= SITE_URL ?>/products.php?category=<?= urlencode($category['slug']) ?>" 
               class="category-card-v2 fade-in-up" 
               style="--order: <?= $cat_index++ ?>">
                <div>
                    <div class="meta">Collection &bull; <?= (int)($category['product_count'] ?? 0) ?> Items</div>
                    <h3><?= htmlspecialchars($category['name']) ?></h3>
                </div>
                <span class="arrow">&rarr;</span>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Featured Products -->
    <div class="section-header fade-in-up">
        <h2>Featured</h2>
        <p>Handpicked favorites this season</p>
    </div>
    <div class="product-grid" style="margin-bottom: 5rem;">
        <?php $prod_index = 0; foreach ($featured as $product): ?>
            <?php
            $stock_class = '';
            if (($product['stock'] ?? 0) <= 0) {
                $stock_class = 'out-of-stock';
            } elseif (($product['stock'] ?? 0) <= 5) {
                $stock_class = 'low-stock';
            }
            ?>
            <article class="product-card fade-in-up <?= $stock_class ?>" style="--order: <?= $prod_index++ ?>; cursor: pointer;" onclick="openProductModal('<?= htmlspecialchars($product['slug']) ?>')">
                <a href="javascript:void(0)" onclick="event.stopPropagation(); openProductModal('<?= htmlspecialchars($product['slug']) ?>');">
                    <img src="<?= resolve_asset_url($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" loading="lazy" class="lazy-image">
                </a>
                <div class="product-copy">
                    <p class="eyebrow">Featured Item</p>
                    <h3><a href="javascript:void(0)" onclick="event.stopPropagation(); openProductModal('<?= htmlspecialchars($product['slug']) ?>');" style="text-decoration: none; color: inherit;"><?= htmlspecialchars($product['name']) ?></a></h3>
                    <p><?= htmlspecialchars($product['excerpt']) ?></p>
                    <div class="product-actions">
                        <span class="product-price"><?= format_price($product['price']) ?></span>
                        <?php if (($product['stock'] ?? 0) > 0): ?>
                            <button class="button-primary add-to-cart-btn" data-product-slug="<?= $product['slug'] ?>" aria-label="Add to Cart">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                            </button>
                        <?php else: ?>
                            <span class="status-pill">Sold Out</span>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <!-- Reviews -->
    <?php if (!empty($reviews)): ?>
    <div class="section-header fade-in-up">
        <h2>Customer Voice</h2>
        <p>What our community is saying</p>
    </div>
    <div class="product-grid" style="margin-bottom: 5rem;">
        <?php foreach ($reviews as $i => $review): ?>
            <article class="review-card fade-in-up" style="--order: <?= $i ?>; padding: 2rem;">
                <div style="margin-bottom: 1rem;"><?= render_stars($review['rating']) ?></div>
                <p style="font-style: italic; color: var(--text-muted); margin-bottom: 1.5rem; line-height: 1.7;">"<?= htmlspecialchars($review['comment']) ?>"</p>
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--accent-gradient); display: flex; align-items: center; justify-content: center; color: white; font-weight: 800; font-size: 0.8rem;">
                        <?= strtoupper(substr($review['user_name'], 0, 2)) ?>
                    </div>
                    <strong style="font-size: 0.9rem;"><?= htmlspecialchars($review['user_name']) ?></strong>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- CTA Banner -->
    <section class="cta-banner fade-in-up" style="margin-bottom: 4rem;">
        <div class="glow"></div>
        <div class="cta-content">
            <p class="eyebrow" style="color: var(--accent); opacity: 0.9; margin-bottom: 1rem;">Exclusive Offer</p>
            <h2>Free shipping on orders over <?= CURRENCY ?>100</h2>
            <p>Join thousands of others upgrading their daily rituals with our premium essentials.</p>
            <a href="<?= SITE_URL ?>/products.php" class="btn-light">Start Browsing</a>
        </div>
    </section>
</div>

<?php include __DIR__ . '/../includes/footer.php';




