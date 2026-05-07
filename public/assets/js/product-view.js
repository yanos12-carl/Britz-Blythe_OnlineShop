document.addEventListener('DOMContentLoaded', function () {
    const mainProductImage = document.getElementById('mainProductImage');
    const productThumbnails = document.getElementById('productThumbnails');
    const productQtyInput = document.getElementById('productQty');
    const qtyMinusBtn = document.getElementById('qtyMinus');
    const qtyPlusBtn = document.getElementById('qtyPlus');
    const stockDisplay = document.getElementById('stockDisplay');
    const priceDisplay = document.getElementById('priceDisplay');
    const addToCartBtn = document.getElementById('addToCartBtn');
    const buyNowBtn = document.getElementById('buyNowBtn');
    const variationManager = document.getElementById('variationManager');
    const infoTabs = document.querySelector('.info-tabs');
    const infoContent = document.querySelector('.info-content');

    let selectedVariations = {};
    let availableVariants = [];
    let currentProductStock = parseInt(productQtyInput?.max || 0);
    let currentProductPrice = parseFloat(priceDisplay?.querySelector('.current-price')?.textContent?.replace(/[^0-9.]/g, '') || 0);
    let currentVariantId = null;

    // --- Image Gallery Logic ---
    if (productThumbnails && mainProductImage) {
        productThumbnails.addEventListener('click', function (event) {
            const thumbItem = event.target.closest('.thumb-item');
            if (thumbItem) {
                // Remove active class from all thumbnails
                productThumbnails.querySelectorAll('.thumb-item').forEach(item => {
                    item.classList.remove('active');
                });
                // Add active class to the clicked thumbnail
                thumbItem.classList.add('active');
                // Change the main image source
                mainProductImage.src = thumbItem.dataset.url;
            }
        });
    }

    // --- Lightbox/Full Screen Logic ---
    if (mainProductImage) {
        mainProductImage.addEventListener('click', openLightbox);
    }

    // --- Image Zoom Logic ---
    if (mainProductImage && mainProductImage.parentElement) {
        const wrapper = mainProductImage.parentElement;

        wrapper.addEventListener('mousemove', function (e) {
            const rect = wrapper.getBoundingClientRect();
            const x = ((e.clientX - rect.left) / rect.width) * 100;
            const y = ((e.clientY - rect.top) / rect.height) * 100;

            mainProductImage.style.transformOrigin = `${x}% ${y}%`;
        });

        wrapper.addEventListener('mouseleave', () => {
            mainProductImage.style.transformOrigin = 'center center';
        });
    }

    // --- Lightbox Functions ---
    let lightboxOverlay, lightboxImage, lightboxClose;

    function createLightbox() {
        lightboxOverlay = document.createElement('div');
        lightboxOverlay.id = 'lightbox-overlay';
        lightboxOverlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease;
        `;

        const lightboxContent = document.createElement('div');
        lightboxContent.style.cssText = `
            position: relative;
            max-width: 90%;
            max-height: 90%;
        `;

        lightboxImage = document.createElement('img');
        lightboxImage.id = 'lightbox-image';
        lightboxImage.style.cssText = `
            max-width: 100%;
            max-height: 100%;
            display: block;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.5);
        `;

        lightboxClose = document.createElement('button');
        lightboxClose.id = 'lightbox-close';
        lightboxClose.innerHTML = '&times;';
        lightboxClose.style.cssText = `
            position: absolute;
            top: -15px;
            right: -15px;
            background: #fff;
            color: #333;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            font-size: 2rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        `;

        lightboxContent.appendChild(lightboxImage);
        lightboxContent.appendChild(lightboxClose);
        lightboxOverlay.appendChild(lightboxContent);
        document.body.appendChild(lightboxOverlay);

        lightboxClose.addEventListener('click', closeLightbox);
        lightboxOverlay.addEventListener('click', (e) => {
            if (e.target === lightboxOverlay) {
                closeLightbox();
            }
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeLightbox();
            }
        });
    }

    function openLightbox() {
        if (!lightboxOverlay) {
            createLightbox();
        }
        lightboxImage.src = mainProductImage.src;
        lightboxOverlay.style.opacity = '1';
        lightboxOverlay.style.visibility = 'visible';
        document.body.style.overflow = 'hidden'; // Prevent scrolling
    }

    function closeLightbox() {
        if (lightboxOverlay) {
            lightboxOverlay.style.opacity = '0';
            lightboxOverlay.style.visibility = 'hidden';
            document.body.style.overflow = ''; // Restore scrolling
        }
    }

    // --- Quantity Stepper Logic ---
    if (qtyMinusBtn && productQtyInput && qtyPlusBtn) {
        qtyMinusBtn.addEventListener('click', function () {
            let qty = parseInt(productQtyInput.value);
            if (qty > parseInt(productQtyInput.min)) {
                productQtyInput.value = qty - 1;
            }
        });

        qtyPlusBtn.addEventListener('click', function () {
            let qty = parseInt(productQtyInput.value);
            if (qty < parseInt(productQtyInput.max)) {
                productQtyInput.value = qty + 1;
            }
        });
    }

    // --- Variation Selection Logic ---
    if (variationManager) {
        availableVariants = JSON.parse(variationManager.dataset.variants || '[]');

        variationManager.addEventListener('click', async function (event) {
            const varBtn = event.target.closest('.var-btn');
            if (!varBtn) return;

            const group = varBtn.closest('.variation-group');
            const groupName = group.dataset.group;
            const optionValue = varBtn.dataset.value;

            // Toggle active class
            group.querySelectorAll('.var-btn').forEach(btn => btn.classList.remove('active'));
            varBtn.classList.add('active');

            // Update selected variations
            selectedVariations[groupName] = optionValue;

            // Check if all variation groups have a selection
            const allGroupsSelected = Array.from(variationManager.querySelectorAll('.variation-group')).every(g =>
                g.querySelector('.var-btn.active')
            );

            if (allGroupsSelected) {
                // Make AJAX call to get variant details
                const productSlug = variationManager.dataset.productSlug; // Get product slug
                const response = await fetch(`/ecommerce/public/api/get_product_variation.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        product_slug: productSlug,
                        variations: selectedVariations
                    })
                });
                const data = await response.json();

                if (data.success && data.variation) {
                    currentVariantId = data.variation.id;
                    currentProductStock = data.variation.stock;
                    currentProductPrice = data.variation.price;

                    // Update UI
                    priceDisplay.querySelector('.current-price').textContent = data.variation.price_formatted;
                    stockDisplay.textContent = `${currentProductStock} pieces available`;
                    productQtyInput.max = currentProductStock;
                    if (parseInt(productQtyInput.value) > currentProductStock) {
                        productQtyInput.value = currentProductStock > 0 ? 1 : 0;
                    }
                    if (data.variation.image) {
                        mainProductImage.src = data.variation.image;
                        // Also update active thumbnail if it matches
                        productThumbnails.querySelectorAll('.thumb-item').forEach(item => {
                            item.classList.remove('active');
                            if (item.dataset.url === data.variation.image) {
                                item.classList.add('active');
                            }
                        });
                    }

                    // Enable/disable buttons
                    if (currentProductStock > 0) {
                        addToCartBtn.disabled = false;
                        buyNowBtn.disabled = false;
                    } else {
                        addToCartBtn.disabled = true;
                        buyNowBtn.disabled = true;
                    }
                } else {
                    // Variation not found or out of stock
                    currentVariantId = null;
                    currentProductStock = 0;
                    stockDisplay.textContent = 'Out of stock';
                    productQtyInput.max = 0;
                    productQtyInput.value = 0;
                    addToCartBtn.disabled = true;
                    buyNowBtn.disabled = true;
                }
            } else {
                // Not all variations selected, disable buttons
                addToCartBtn.disabled = true;
                buyNowBtn.disabled = true;
            }
        });
    }

    // --- Info Tabs Logic (Description / Reviews) ---
    if (infoTabs && infoContent) {
        const tabButtons = infoTabs.querySelectorAll('button');
        const descriptionContent = infoContent.querySelector('.rich-content');
        const reviewsContent = document.createElement('div'); // Placeholder for reviews
        reviewsContent.className = 'reviews-content';
        reviewsContent.style.display = 'none'; // Initially hidden
        reviewsContent.innerHTML = `
            <h3>Customer Reviews</h3>
            <p>Reviews will be loaded here dynamically or from a separate section.</p>
            <!-- You would typically load reviews via AJAX or have them pre-rendered -->
        `;
        infoContent.appendChild(reviewsContent);

        tabButtons.forEach((button, index) => {
            button.addEventListener('click', function () {
                tabButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');

                if (index === 0) { // Description tab
                    descriptionContent.style.display = 'block';
                    reviewsContent.style.display = 'none';
                } else { // Reviews tab
                    descriptionContent.style.display = 'none';
                    reviewsContent.style.display = 'block';
                }
            });
        });
    }

    // --- Add to Cart / Buy Now Logic ---
    if (addToCartBtn) {
        addToCartBtn.addEventListener('click', async function () {
            const slug = this.dataset.slug; // Assuming slug is set on the button
            const quantity = parseInt(productQtyInput.value);

            if (quantity <= 0 || currentProductStock < quantity) {
                showNotification('Please select a valid quantity.', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('slug', slug);
            formData.append('quantity', quantity);
            if (currentVariantId) {
                formData.append('variation_item_id', currentVariantId);
            }

            try {
                const response = await fetch(`/ecommerce/public/add_to_cart.php`, {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.success) {
                    showNotification(data.message, 'success');
                    // Update cart count in navbar (assuming cartCountElement is global or passed)
                    const cartCountElement = document.querySelector('.cart-count');
                    if (cartCountElement) cartCountElement.textContent = data.cart_count;
                    // Optionally refresh mini-cart
                    if (typeof refreshMiniCart === 'function') refreshMiniCart();
                } else {
                    showNotification(data.message, 'error');
                }
            } catch (error) {
                console.error('Error adding to cart:', error);
                showNotification('An error occurred while adding to cart.', 'error');
            }
        });
    }

    if (buyNowBtn) {
        buyNowBtn.addEventListener('click', async function () {
            const slug = this.dataset.slug; // Assuming slug is set on the button
            const quantity = parseInt(productQtyInput.value);

            if (quantity <= 0 || currentProductStock < quantity) {
                showNotification('Please select a valid quantity.', 'error');
                return;
            }

            // Add to cart first, then redirect to checkout
            const formData = new FormData();
            formData.append('slug', slug);
            formData.append('quantity', quantity);
            if (currentVariantId) {
                formData.append('variation_item_id', currentVariantId);
            }

            try {
                const response = await fetch(`/ecommerce/public/add_to_cart.php`, {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.success) {
                    window.location.href = `/ecommerce/public/checkout.php`;
                } else {
                    showNotification(data.message, 'error');
                }
            } catch (error) {
                console.error('Error during Buy Now:', error);
                showNotification('An error occurred during Buy Now.', 'error');
            }
        });
    }

    // Initial state check for buttons
    if (currentProductStock > 0) {
        addToCartBtn.disabled = false;
        buyNowBtn.disabled = false;
    }
});
