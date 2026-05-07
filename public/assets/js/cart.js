async function handleCartUpdate(slug, change, button) {
    // Resolve path for AJAX
    const basePath = window.location.pathname.replace(/\/[^\/]*$/, '');
    const url = basePath + '/update_cart_quantity.php';

    const stepper = button.parentElement;
    const input = stepper.querySelector('input');
    const card = button.closest('.cart-item-card');

    // 1. Play Pulse Animation
    stepper.classList.remove('pulse-active');
    void stepper.offsetWidth; // Trigger reflow
    stepper.classList.add('pulse-active');

    // 2. AJAX Request
    const formData = new FormData();
    formData.append('slug', slug);
    formData.append('change', change);

    try {
        const response = await fetch(url, {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (data.success) {
            // If quantity becomes 0, remove the item card
            const newQty = parseInt(input.value) + change;
            if (newQty <= 0) {
                card.style.opacity = '0';
                card.style.transform = 'translateX(-20px)';
                setTimeout(() => {
                    card.remove();
                    if (document.querySelectorAll('.cart-item-card').length === 0) location.reload();
                }, 300);
            } else {
                input.value = newQty;
                stepper.classList.toggle('is-zero', newQty === 0);
                card.querySelector('.item-subtotal-display').textContent = data.item_subtotal;
            }

            // Update Totals
            document.querySelector('.summary-subtotal').textContent = data.totals.subtotal;
            document.querySelector('.summary-tax').textContent = data.totals.tax;
            document.querySelector('.summary-total').textContent = data.totals.total;
            const countBadge = document.querySelector('.cart-count');
            if (countBadge) countBadge.textContent = data.cart_count;
        }
    } catch (err) {
        console.error('Cart update failed:', err);
    }
}

document.addEventListener('DOMContentLoaded', function () {
    // Get base path for AJAX calls
    const basePath = window.location.pathname.replace(/\/[^\/]*$/, '');

    // Mobile Menu Toggle Logic
    const body = document.body;
    const mobileToggle = document.createElement('button');
    mobileToggle.className = 'mobile-menu-toggle';
    mobileToggle.innerHTML = '<span></span><span></span><span></span>';

    // Modal Setup
    const modalOverlay = document.createElement('div');
    modalOverlay.className = 'modal-overlay';
    modalOverlay.innerHTML = `
        <div class="quickview-modal">
            <button class="modal-close">&times;</button>
            <div id="modal-content-area"></div>
        </div>
    `;
    document.body.appendChild(modalOverlay);

    modalOverlay.querySelector('.modal-close').addEventListener('click', () => {
        modalOverlay.classList.remove('is-active');
    });

    // Close modal on Escape or clicking outside
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') modalOverlay.classList.remove('is-active');
    });
    modalOverlay.addEventListener('click', (e) => {
        if (e.target === modalOverlay) modalOverlay.classList.remove('is-active');
    });

    // Back to Top Button Creation
    const backToTop = document.createElement('button');
    backToTop.className = 'back-to-top';
    backToTop.innerHTML = '↑';
    backToTop.title = 'Back to top';
    document.body.appendChild(backToTop);

    backToTop.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    const header = document.querySelector('.site-header');
    if (header) {
        header.prepend(mobileToggle);

        // Handle navbar scroll effect
        window.addEventListener('scroll', () => {
            header.classList.toggle('scrolled', window.scrollY > 50);
            backToTop.classList.toggle('show', window.scrollY > 300);
        });
    }

    mobileToggle.addEventListener('click', () => {
        body.classList.toggle('menu-open');
    });

    async function openQuickView(slug) {
        const contentArea = document.getElementById('modal-content-area');
        // Display spinner while loading
        contentArea.innerHTML = `
            <div class="modal-loading-spinner">
                <div class="spinner"></div>
            </div>`;
        modalOverlay.classList.add('is-active');

        try {
            const response = await fetch(`${basePath}/api/get_product.php?slug=${slug}`);
            const data = await response.json();

            if (data.success) {
                const p = data.product;
                contentArea.innerHTML = `
                    <div class="modal-body">
                        <img src="${p.image}" alt="${p.name}" class="lazy-image" style="width: 100%; border-radius: 12px;" onerror="this.classList.add('loaded');">
                        <div>
                            <span class="eyebrow" style="font-size: 0.7rem;">${p.category}</span>
                            <h2 style="margin: 0.5rem 0;">${p.name}</h2>
                            <p class="price" style="font-size: 1.25rem; font-weight: 700; color: var(--accent);">${p.formatted_price}</p>
                            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.5rem;">${p.excerpt}</p>
                            
                            <div class="quantity-control" style="margin-bottom: 1.5rem;">
                                <div class="stepper">
                                    <button type="button" class="step-btn" onclick="const i = this.nextElementSibling; if(i.value > 1) i.stepDown();">–</button>
                                    <input type="number" id="modal-qty" value="1" min="1" max="${p.stock}" readonly>
                                    <button type="button" class="step-btn" onclick="const i = this.previousElementSibling; if(i.value < ${p.stock}) i.stepUp();">+</button>
                                </div>
                                <small style="color: var(--text-muted);">${p.stock} available</small>
                            </div>

                            <button class="button button-primary" id="confirm-add-btn" data-slug="${p.slug}" style="width: 100%;">Confirm Add to Cart</button>
                        </div>
                    </div>
                `;

                document.getElementById('confirm-add-btn').addEventListener('click', function () {
                    const qty = document.getElementById('modal-qty').value;
                    performAddToCart(this.dataset.slug, qty);
                    modalOverlay.classList.remove('is-active');
                });
                initializeLazyImages();
            }
        } catch (error) {
            contentArea.innerHTML = '<p>Error loading product details.</p>';
            console.error('Error loading product details:', error);
        }
    }

    async function performAddToCart(productSlug, quantity) {
        const formData = new FormData();
        formData.append('slug', productSlug);
        formData.append('quantity', quantity);

        try {
            const response = await fetch(`${basePath}/add_to_cart.php`, {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            showNotification(data.message, data.success ? 'success' : 'error');
            if (data.success && cartCountElement) {
                cartCountElement.textContent = data.cart_count;
                refreshMiniCart();

                // Show mini-cart briefly
                miniCartDropdown?.classList.add('is-active');
                setTimeout(() => miniCartDropdown?.classList.remove('is-active'), 3000);
            }
        } catch (error) {
            console.error('Error adding to cart:', error);
        }
    }

    const addToCartButtons = document.querySelectorAll('.add-to-cart-btn, .quick-add-btn');
    const cartCountElement = document.querySelector('.cart-count');
    const messageContainer = document.createElement('div');
    messageContainer.id = 'cart-message-container';
    messageContainer.style.position = 'fixed';
    messageContainer.style.top = '20px';
    messageContainer.style.right = '20px';
    messageContainer.style.zIndex = '1000';
    document.body.appendChild(messageContainer);

    const miniCartDropdown = document.querySelector('.mini-cart-dropdown');

    async function refreshMiniCart() {
        if (!miniCartDropdown) return;
        const res = await fetch(`${basePath}/get_mini_cart.php`);
        miniCartDropdown.innerHTML = await res.text();
    }

    addToCartButtons.forEach(button => {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            openQuickView(this.dataset.slug);
        });
    });

    // Open Quick View when clicking product name or image
    document.addEventListener('click', (e) => {
        const card = e.target.closest('.product-card');
        if (card && (e.target.tagName === 'IMG' || e.target.tagName === 'H3' || e.target.closest('h3'))) {
            const btn = card.querySelector('.add-to-cart-btn, .quick-add-btn');
            const slug = btn?.dataset.productSlug || btn?.dataset.slug;
            if (slug) {
                e.preventDefault();
                openQuickView(slug);
            }
        }
    });

    // Handle Item Removal via Delegation
    miniCartDropdown?.addEventListener('click', async function (e) {
        // Handle Quantity Toggle
        if (e.target.classList.contains('qty-btn')) {
            const slug = e.target.dataset.productSlug;
            const change = e.target.classList.contains('plus') ? 1 : -1;

            const formData = new FormData();
            formData.append('slug', slug);
            formData.append('change', change);

            try {
                const response = await fetch(`${basePath}/update_cart_quantity.php`, {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.success) {
                    if (cartCountElement) cartCountElement.textContent = data.cart_count;
                    refreshMiniCart();
                } else {
                    showNotification(data.message, 'error');
                }
            } catch (error) {
                console.error('Error updating quantity:', error);
            }
            return;
        }

        if (e.target.classList.contains('remove-item-btn')) {
            if (!confirm('Are you sure you want to remove this item from your cart?')) return;

            const slug = e.target.dataset.productSlug;
            const formData = new FormData();
            formData.append('slug', slug);

            try {
                const response = await fetch(`${basePath}/remove_from_cart.php`, {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.success) {
                    showNotification(data.message, 'success');
                    if (cartCountElement) cartCountElement.textContent = data.cart_count;
                    refreshMiniCart();
                } else {
                    showNotification(data.message, 'error');
                }
            } catch (error) {
                console.error('Error removing item:', error);
            }
        }
    });

    // Lazy Image Reveal Logic
    function initializeLazyImages() {
        const lazyImages = document.querySelectorAll('.lazy-image:not(.loaded)');
        lazyImages.forEach(img => {
            // If the image is already complete (from cache) or lacks height (broken), show it
            if (img.complete && img.naturalHeight !== 0) {
                img.classList.add('loaded');
            } else {
                img.addEventListener('load', () => {
                    img.classList.add('loaded');
                }, { once: true });
                img.addEventListener('error', () => {
                    img.classList.add('loaded'); // Reveal even if broken to show placeholder/alt
                }, { once: true });
            }
        });
    }

    // Initial load
    initializeLazyImages();
    refreshMiniCart();
});
