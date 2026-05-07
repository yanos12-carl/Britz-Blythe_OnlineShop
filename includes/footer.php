    </main>
    <footer class="site-footer">
        <div class="footer-content">
            <div class="footer-brand">
                <a class="brand-footer" href="<?= SITE_URL ?>/index.php"><?= SITE_NAME ?></a>
                <p>Britz &amp; Blythe designs thoughtful handmade pieces for modern living — warm, editorial, heirloom-ready.</p>
            </div>
            <div class="footer-links">
                <h3>Shop</h3>
                <a href="<?= SITE_URL ?>/public/products.php">All Products</a>
                <a href="<?= SITE_URL ?>/public/reviews.php">Reviews</a>
                <a href="<?= SITE_URL ?>/public/about.php">Our Story</a>
            </div>
            <div class="footer-links">
                <h3>Company</h3>
                <a href="<?= SITE_URL ?>/public/about.php">About</a>
                <a href="<?= SITE_URL ?>/public/contact.php">Contact</a>
                <a href="<?= SITE_URL ?>/public/account.php">My Account</a>
            </div>
            <div class="footer-links">
                <h3>Legal</h3>
                <a href="<?= SITE_URL ?>/public/terms.php">Terms</a>
                <a href="<?= SITE_URL ?>/public/privacy.php">Privacy</a>
                <a href="<?= SITE_URL ?>/public/shipping-label.php">Shipping</a>
            </div>
        </div>
        <div class="footer-bottom">
            <span>&copy; <?= date('Y') ?> <?= htmlspecialchars(SITE_NAME) ?>. Crafted for modern homes and thoughtful gifting.</span>
            <span>Follow us on social for new arrivals and styling notes.</span>
        </div>
    </footer>

    <!-- Back to Top Button -->
    <button id="backToTop" class="back-to-top" aria-label="Back to top">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="m18 15-6-6-6 6"/>
        </svg>
    </button>

    <!-- Product Quick View Modal -->
    <div id="product-modal-overlay" class="product-modal-overlay" onclick="if(event.target===this)closeProductModal()">
        <div class="product-modal" id="product-modal">
            <div id="product-modal-content">
                <div class="modal-loading-spinner"><div class="spinner"></div></div>
            </div>
        </div>
    </div>

    <script src="<?= SITE_URL ?>/assets/js/utils.js"></script>
    <script src="<?= SITE_URL ?>/assets/js/cart.js"></script>
    <script src="<?= SITE_URL ?>/assets/js/product-view.js"></script>
    <script src="<?= SITE_URL ?>/assets/js/bundle.min.js"></script>

    <script>
        (function() {
            const html = document.documentElement;
            const toggle = document.querySelector('.theme-toggle');
            const stored = localStorage.getItem('britz-theme');
            const current = stored || 'light';
            html.dataset.theme = current;

            function updateToggle() {
                if (!toggle) return;
                const isDark = html.dataset.theme === 'dark';
                toggle.classList.toggle('dark', isDark);
            }

            function setTheme(value) {
                html.dataset.theme = value;
                localStorage.setItem('britz-theme', value);
                updateToggle();
            }

            if (toggle) {
                toggle.addEventListener('click', function () {
                    setTheme(html.dataset.theme === 'dark' ? 'light' : 'dark');
                });
            }
            updateToggle();
        })();

        // Announcement bar rotation
        (function() {
            const items = document.querySelectorAll('.announcement-items span');
            if (items.length === 0) return;
            let current = 0;
            items.forEach((item, i) => item.style.display = i === 0 ? 'block' : 'none');
            setInterval(() => {
                items[current].style.display = 'none';
                current = (current + 1) % items.length;
                items[current].style.display = 'block';
            }, 4000);
        })();
    </script>

</body>
</html>


