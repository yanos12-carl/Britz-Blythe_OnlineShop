<?php
// Avoid any output that could break redirects.
// If headers are already sent, nothing can be redirected safely; avoid further output.
if (headers_sent()) {
    return;
}
$cartCount = get_cart_count();

$userAvatar = null;
if (is_logged_in()) {
    $user = get_logged_in_user();
    $db = get_db();
    if ($db) {
        $stmt = $db->prepare('SELECT profile_image FROM users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $user['id']]);
        $img = $stmt->fetchColumn();
        if ($img) {
            $userAvatar = SITE_URL . '/' . $img;
        }
    }
}
?>
<header class="site-header">

    <div class="site-header-inner">
        <div class="brand-panel">
            <a class="brand-logo" href="<?= SITE_URL ?>/public/index.php"><?= SITE_NAME ?></a>
            <span class="brand-subtitle">Artisan Goods</span>
        </div>

        <nav class="site-nav">
            <a href="<?= SITE_URL ?>/public/index.php">Home</a>
            <a href="<?= SITE_URL ?>/public/products.php">Shop</a>
            <a href="<?= SITE_URL ?>/public/about.php">About</a>
            <a href="<?= SITE_URL ?>/public/reviews.php">Reviews</a>
        </nav>

        <div class="nav-actions">
            <form class="search-form" action="<?= SITE_URL ?>/public/products.php" method="get" role="search">
                <label class="sr-only" for="site-search">Search artisan goods</label>
                <input id="site-search" name="search" type="search" placeholder="Search artisan goods" autocomplete="off">
            </form>

            <button type="button" class="theme-toggle" aria-label="Toggle light and dark theme">
                <span class="sun">☀️</span>
                <span class="moon">🌙</span>
            </button>

            <button type="button" class="nav-icon-btn cart-drawer-btn" title="Cart" aria-label="Open shopping cart">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                </svg>
                <?php if ($cartCount > 0): ?><span class="cart-count"><?= $cartCount ?></span><?php endif; ?>
            </button>

            <?php if (is_logged_in()): ?>
                <div class="profile-dropdown-container">
                    <a class="nav-icon-btn" href="<?= SITE_URL ?>/public/profile.php" title="Profile">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                    </a>
                    <div class="profile-dropdown-menu">
                        <a href="<?= SITE_URL ?>/public/profile.php">Profile</a>
                        <a href="<?= SITE_URL ?>/public/orders.php">My Orders</a>
                        <a href="<?= SITE_URL ?>/public/address-book.php">Address Book</a>
                        <div class="dropdown-divider"></div>
                        <a href="<?= SITE_URL ?>/public/logout.php" class="logout-link">Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <a class="nav-icon-btn" href="<?= SITE_URL ?>/public/login.php" title="Login">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                        <polyline points="10,17 15,12 10,7"/>
                        <line x1="15" y1="12" x2="3" y2="12"/>
                    </svg>
                </a>
            <?php endif; ?>
        </div>
    </div>
</header>

<!-- Off-Canvas Shopping Cart Drawer -->
<div class="cart-drawer-overlay" id="cartDrawerOverlay"></div>
<aside class="cart-drawer" id="cartDrawer">
    <div class="cart-drawer-header">
        <h2>Your Cart</h2>
        <button type="button" class="cart-drawer-close" aria-label="Close cart drawer">×</button>
    </div>
    <div class="cart-drawer-body" id="cartDrawerBody">
        <div style="padding: 2rem; text-align: center; color: var(--text-muted);">
            Loading cart items...
        </div>
    </div>
    <div class="cart-drawer-footer">
        <div class="cart-drawer-subtotal">
            <span class="cart-drawer-subtotal-label">Subtotal:</span>
            <span class="cart-drawer-subtotal-value" id="cartDrawerTotal">$0.00</span>
        </div>
        <div class="cart-drawer-actions">
            <a href="<?= SITE_URL ?>/public/cart.php" class="btn-view-cart">View Full Cart</a>
            <a href="<?= SITE_URL ?>/public/checkout.php" class="btn-checkout">Checkout</a>
        </div>
    </div>
</aside>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const cartDrawerBtn = document.querySelector('.cart-drawer-btn');
    const cartDrawer = document.getElementById('cartDrawer');
    const cartDrawerOverlay = document.getElementById('cartDrawerOverlay');
    const cartDrawerClose = document.querySelector('.cart-drawer-close');
    const cartDrawerBody = document.getElementById('cartDrawerBody');
    const cartDrawerTotal = document.getElementById('cartDrawerTotal');

    // Toggle drawer on cart button click
    if (cartDrawerBtn) {
        cartDrawerBtn.addEventListener('click', function(e) {
            e.preventDefault();
            toggleCartDrawer();
            loadCartItems();
        });
    }

    // Close drawer on overlay click
    cartDrawerOverlay.addEventListener('click', closeCartDrawer);

    // Close drawer on close button click
    cartDrawerClose.addEventListener('click', closeCartDrawer);

    // Close drawer on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && cartDrawer.classList.contains('active')) {
            closeCartDrawer();
        }
    });

    function toggleCartDrawer() {
        const isActive = cartDrawer.classList.contains('active');
        if (isActive) {
            closeCartDrawer();
        } else {
            openCartDrawer();
        }
    }

    function openCartDrawer() {
        cartDrawer.classList.add('active');
        cartDrawerOverlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeCartDrawer() {
        cartDrawer.classList.remove('active');
        cartDrawerOverlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    function loadCartItems() {
        // Fetch cart items from API
        fetch('<?= SITE_URL ?>/public/api/get-cart.php')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.items && data.items.length > 0) {
                    let html = '';
                    data.items.forEach(item => {
                        const imageUrl = item.image || '<?= SITE_URL ?>/public/assets/images/placeholder.png';
                        html += `
                            <div class="cart-drawer-item">
                                <div class="cart-drawer-item-image">
                                    <img src="${imageUrl}" alt="${item.name}" loading="lazy">
                                </div>
                                <div class="cart-drawer-item-content">
                                    <h3 class="cart-drawer-item-name">${item.name}</h3>
                                    <span class="cart-drawer-item-price">$${parseFloat(item.price).toFixed(2)}</span>
                                    <div class="cart-drawer-item-qty">
                                        <button type="button" class="qty-btn qty-decrease" data-cart-key="${item.cart_key}">−</button>
                                        <input type="number" value="${item.quantity}" min="1" readonly>
                                        <button type="button" class="qty-btn qty-increase" data-cart-key="${item.cart_key}">+</button>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    cartDrawerBody.innerHTML = html;
                    cartDrawerTotal.textContent = '$' + parseFloat(data.total).toFixed(2);

                    // Add event listeners for quantity buttons
                    document.querySelectorAll('.qty-increase').forEach(btn => {
                        btn.addEventListener('click', function() {
                            updateQuantity(this.dataset.cartKey, 1);
                        });
                    });
                    document.querySelectorAll('.qty-decrease').forEach(btn => {
                        btn.addEventListener('click', function() {
                            updateQuantity(this.dataset.cartKey, -1);
                        });
                    });
                } else {
                    cartDrawerBody.innerHTML = `
                        <div style="padding: 2rem; text-align: center; color: var(--text-muted);">
                            <p style="font-size: 3rem; margin-bottom: 1rem;">🛒</p>
                            <p>Your cart is empty</p>
                        </div>
                    `;
                    cartDrawerTotal.textContent = '$0.00';
                }
            })
            .catch(error => {
                console.error('Error loading cart:', error);
                cartDrawerBody.innerHTML = `
                    <div style="padding: 2rem; text-align: center; color: var(--text-muted);">
                        <p>Error loading cart</p>
                    </div>
                `;
            });
    }

    function updateQuantity(cartKey, change) {
        const formData = new FormData();
        formData.append('cart_key', cartKey);
        formData.append('change', change);

        fetch('<?= SITE_URL ?>/public/update_cart_quantity.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadCartItems();
                // Update cart badge
                const badge = document.querySelector('.cart-count');
                if (data.cart_count > 0) {
                    if (!badge) {
                        const btn = document.querySelector('.cart-drawer-btn');
                        const newBadge = document.createElement('span');
                        newBadge.className = 'cart-count';
                        newBadge.textContent = data.cart_count;
                        btn.appendChild(newBadge);
                    } else {
                        badge.textContent = data.cart_count;
                    }
                } else if (badge) {
                    badge.remove();
                }
            }
        })
        .catch(error => console.error('Error updating quantity:', error));
    }
});
</script>
