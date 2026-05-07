<?php
require_once __DIR__ . '/../includes/header.php';
?>
<section class="page-header">
    <div>
        <h1>Page Not Found</h1>
        <p>We couldn’t find the page you were looking for.</p>
    </div>
</section>
<div class="empty-state">
    <h2>404</h2>
    <p>Try returning to the homepage or browsing our products.</p>
    <a class="button button-primary" href="<?= SITE_URL ?>/index.php">Home</a>
</div>
<?php include __DIR__ . '/../includes/footer.php';
