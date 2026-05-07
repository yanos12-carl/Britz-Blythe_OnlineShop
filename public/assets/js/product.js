document.addEventListener('DOMContentLoaded', function () {
    const quantityInput = document.querySelector('#quantity');
    if (quantityInput) {
        const min = parseInt(quantityInput.min, 10) || 1;
        quantityInput.addEventListener('change', function () {
            if (quantityInput.value < min) {
                quantityInput.value = min;
            }
        });
    }
});

/**
 * Opens the Product Quick View Modal
 * @param {string} slug - Product slug
 */
async function openProductModal(slug) {
    const overlay = document.getElementById('product-modal-overlay');
    const content = document.getElementById('product-modal-content');
    if (!overlay || !content) return;

    const basePath = window.location.pathname.replace(/\/[^\/]*$/, '');

    content.innerHTML = '<div class="modal-loading-spinner"><div class="spinner"></div></div>';
    overlay.classList.add('is-active');
    document.body.style.overflow = 'hidden';

    try {
        const response = await fetch(`${basePath}/api/product_modal_content.php?slug=${encodeURIComponent(slug)}`);
        const html = await response.text();
        content.innerHTML = html;
    } catch (err) {
        content.innerHTML = '<p class="alert alert-error" style="padding:2rem;">Failed to load product.</p>';
    }
}

/**
 * Closes the Product Quick View Modal
 */
function closeProductModal() {
    const overlay = document.getElementById('product-modal-overlay');
    if (overlay) {
        overlay.classList.remove('is-active');
        document.body.style.overflow = '';
    }
}

/**
 * Handles Add to Cart from the dedicated product page (AJAX)
 * @param {HTMLFormElement} form
 */
async function handleProductPageAddToCart(form) {
    await handleAddToCartCommon(form, false);
}

/**
 * Handles Add to Cart from within the modal (AJAX)
 * @param {HTMLFormElement} form
 */
async function handleModalAddToCart(form) {
    await handleAddToCartCommon(form, true);
}

/**
 * Shared Logic for AJAX Add to Cart
 * @param {HTMLFormElement} form 
 * @param {boolean} shouldCloseModal 
 */
async function handleAddToCartCommon(form, shouldCloseModal) {
    const formData = new FormData(form);
    const basePath = window.location.pathname.replace(/\/[^\/]*$/, '');
    const url = basePath + '/add_to_cart.php';

    // Simple visual feedback on button
    const btn = form.querySelector('button[type="submit"]');
    const originalText = btn ? btn.textContent : '';
    if (btn) {
        btn.disabled = true;
        btn.textContent = 'Adding...';
    }

    try {
        const res = await fetch(url, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const text = await res.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('Server did not return JSON:', text);
            showNotification('Server error. Check console.', 'error');
            return;
        }

        if (data.success) {
            showNotification(data.message || 'Added to cart!', 'success');
            if (shouldCloseModal) closeProductModal();

            // Update cart count badge
            const cartCount = document.querySelector('.cart-count');
            if (cartCount && data.cart_count !== undefined) {
                cartCount.textContent = data.cart_count;
            }

            // Refresh mini-cart dropdown
            const miniCart = document.querySelector('.mini-cart-dropdown');
            if (miniCart) {
                fetch(`${basePath}/get_mini_cart.php`).then(r => r.text()).then(html => {
                    miniCart.innerHTML = html;
                    miniCart.classList.add('is-active');
                    setTimeout(() => miniCart.classList.remove('is-active'), 3000);
                });
            }
        } else {
            showNotification(data.message || 'Failed to add.', 'error');
        }
    } catch (err) {
        console.error('Add to cart error:', err);
        showNotification('Network error. Check console.', 'error');
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.textContent = originalText;
        }
    }
}

// Close modal on Escape key
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeProductModal();
});
