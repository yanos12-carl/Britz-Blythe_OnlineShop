<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$message = '';
$message_type = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_id'])) {
        if (delete_review((int)$_POST['delete_id'])) {
            $message = 'Review deleted successfully.';
            $message_type = 'success';
        } else {
            $message = 'Failed to delete review.';
            $message_type = 'error';
        }
    } elseif (isset($_POST['approve_id'])) {
        if (approve_review((int)$_POST['approve_id'])) {
            $message = 'Review approved successfully.';
            $message_type = 'success';
        } else {
            $message = 'Failed to approve review.';
            $message_type = 'error';
        }
    }
}

$reviews = get_reviews(false);
$pending_count = count(array_filter($reviews, fn($r) => !($r['is_approved'] ?? 0)));
$avg_rating = count($reviews) > 0 ? array_sum(array_column($reviews, 'rating')) / count($reviews) : 0;

$current_page = 'reviews';
$page_title = 'Customer Reviews';
$page_description = 'Manage and moderate customer feedback across your store.';

ob_start();
?>

<style>
    /* Stats Bar */
    .reviews-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2.5rem;
    }

    /* Toolbar & Tabs */
    .reviews-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        gap: 1.5rem;
        flex-wrap: wrap;
    }

    .review-tabs {
        display: flex;
        background: var(--admin-surface);
        padding: 0.4rem;
        border-radius: 12px;
        border: 1px solid var(--admin-border);
    }

    .tab-btn {
        padding: 0.6rem 1.2rem;
        font-size: 0.85rem;
        font-weight: 600;
        border-radius: 8px;
        cursor: pointer;
        border: none;
        background: transparent;
        color: var(--admin-text);
        opacity: 0.6;
        transition: all 0.2s;
    }

    .tab-btn.active {
        background: var(--admin-bg);
        color: var(--admin-accent);
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        opacity: 1;
    }

    /* Review Cards Grid */
    .reviews-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.25rem;
    }

    .review-card {
        background: var(--admin-surface);
        border: 1px solid var(--admin-border);
        border-radius: 20px;
        padding: 1.5rem;
        display: grid;
        grid-template-columns: auto 1fr auto;
        gap: 1.5rem;
        align-items: start;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
    }

    .review-card:hover {
        border-color: var(--admin-accent);
        transform: translateY(-2px);
        box-shadow: 0 10px 20px -5px rgba(0, 0, 0, 0.05);
    }

    .view-profile-link {
        display: inline-flex;
        align-items: center;
        color: var(--admin-accent);
        opacity: 0.5;
        transition: all 0.2s ease;
        text-decoration: none;
    }

    .view-profile-link:hover {
        opacity: 1;
        transform: scale(1.1);
    }

    .reviewer-avatar {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: var(--admin-accent);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 1rem;
        flex-shrink: 0;
    }

    .review-content-body h3 {
        margin: 0 0 0.25rem 0;
        font-size: 1.05rem;
        color: var(--admin-text);
    }

    .review-meta-info {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 0.75rem;
        font-size: 0.85rem;
        color: var(--admin-text);
        opacity: 0.6;
    }

    .review-text-message {
        line-height: 1.6;
        color: var(--admin-text);
        font-style: italic;
        opacity: 0.9;
    }

    /* Action Buttons (Circular Ghost Style) */
    .review-action-btns {
        display: flex;
        gap: 0.5rem;
    }

    .btn-circle {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        border: 1.5px solid var(--admin-border);
        background: var(--admin-bg);
        color: var(--admin-text);
        transition: all 0.3s ease;
        position: relative;
    }

    .btn-approve {
        color: #10b981;
        background: rgba(16, 185, 129, 0.08);
        border-color: rgba(16, 185, 129, 0.2);
    }
    .btn-approve:hover {
        background: #10b981;
        color: #10b981;
        border-color: #10b981;
        transform: scale(1.1);
    }

    .btn-delete {
        color: #ef4444;
        background: rgba(239, 68, 68, 0.08);
        border-color: rgba(239, 68, 68, 0.2);
    }
    .btn-delete:hover {
        background: #ef4444;
        color: white;
        border-color: #ef4444;
        transform: scale(1.1) rotate(90deg);
    }

    /* Status Badge */
    .badge-status {
        padding: 0.25rem 0.6rem;
        border-radius: 6px;
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
    }
    .badge-pending { background: rgba(245, 158, 11, 0.15); color: #f59e0b; }
    .badge-approved { background: rgba(16, 185, 129, 0.15); color: #10b981; }

    /* Tooltip */
    [data-tooltip]::after {
        content: attr(data-tooltip);
        position: absolute;
        bottom: 125%;
        left: 50%;
        transform: translateX(-50%) translateY(5px);
        background: #1e293b;
        color: white;
        padding: 5px 10px;
        border-radius: 6px;
        font-size: 0.7rem;
        white-space: nowrap;
        opacity: 0;
        visibility: hidden;
        transition: all 0.2s ease;
        z-index: 100;
    }
    [data-tooltip]:hover::after {
        opacity: 1;
        visibility: visible;
        transform: translateX(-50%) translateY(0);
    }

</style>

<div class="reviews-stats">
    <div class="stat-card">
        <span class="stat-label">Total Reviews</span>
        <span class="stat-value"><?= count($reviews) ?></span>
    </div>
    <div class="stat-card" style="border-left: 4px solid #f59e0b;">
        <span class="stat-label">Awaiting Approval</span>
        <span class="stat-value" style="color: #f59e0b; -webkit-text-fill-color: #f59e0b;"><?= $pending_count ?></span>
    </div>
    <div class="stat-card">
        <span class="stat-label">Avg. Satisfaction</span>
        <span class="stat-value"><?= number_format($avg_rating, 1) ?> ★</span>
    </div>
</div>

<div class="reviews-toolbar">
    <div class="review-tabs">
        <button class="tab-btn active" data-filter="all">All</button>
        <button class="tab-btn" data-filter="pending">Pending (<?= $pending_count ?>)</button>
        <button class="tab-btn" data-filter="approved">Approved</button>
    </div>
    <div style="position: relative; width: 300px;">
        <input type="text" id="reviewSearch" class="form-input" placeholder="Search reviewers or comments..." style="padding-left: 2.5rem;">
        <svg style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); width: 16px; opacity: 0.5;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
    </div>
</div>

<div class="reviews-grid" id="reviewsGrid">
    <?php if (empty($reviews)): ?>
        <div class="admin-card" style="text-align: center; padding: 4rem; opacity: 0.6;">
            <div style="font-size: 3rem; margin-bottom: 1rem;">💬</div>
            <p>No customer reviews have been submitted yet.</p>
        </div>
    <?php else: ?>
        <?php foreach ($reviews as $review): 
            $is_approved = (bool)($review['is_approved'] ?? 0);
        ?>
            <article class="review-card" data-status="<?= $is_approved ? 'approved' : 'pending' ?>">
                <div class="reviewer-avatar">
                    <?= strtoupper(substr($review['user_name'], 0, 1)) ?>
                </div>
                
                <div class="review-content-body">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <h3 style="display: flex; align-items: center; gap: 0.5rem;">
                            <?= htmlspecialchars($review['user_name']) ?>
                            <?php if (!empty($review['user_id'])): ?>
                                <a href="customers.php?search=<?= urlencode($review['user_name']) ?>" class="view-profile-link" data-tooltip="View User Profile">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 14px; height: 14px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                </a>
                            <?php endif; ?>
                        </h3>
                        <span class="badge-status <?= $is_approved ? 'badge-approved' : 'badge-pending' ?>">
                            <?= $is_approved ? 'Approved' : 'Pending' ?>
                        </span>
                    </div>

                    <?php if (!empty($review['product_name'])): ?>
                        <div style="margin-bottom: 0.5rem; font-size: 0.85rem; display: flex; align-items: center; gap: 0.4rem;">
                            <span style="color: var(--admin-text); opacity: 0.6;">Reviewed Product:</span>
                            <a href="<?= SITE_URL ?>/public/product.php?slug=<?= urlencode($review['product_slug']) ?>" target="_blank" style="color: var(--admin-accent); font-weight: 600; text-decoration: none; border-bottom: 1px dashed var(--admin-accent);">
                                <?= htmlspecialchars($review['product_name']) ?>
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <div class="review-meta-info">
                        <div class="stars"><?= render_stars($review['rating']) ?></div>
                        <span>•</span>
                        <span>📅 <?= date('M d, Y', strtotime($review['created_at'])) ?></span>
                    </div>

                    <p class="review-text-message">"<?= nl2br(htmlspecialchars($review['comment'])) ?>"</p>
                </div>

                <div class="review-action-btns">
                    <?php if (!$is_approved): ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="approve_id" value="<?= $review['id'] ?>">
                            <button type="submit" class="btn-circle btn-approve" data-tooltip="Approve Review">
                                <svg style="width: 20px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </button>
                        </form>
                    <?php endif; ?>

                    <form method="POST" onsubmit="return confirm('Permanently delete this review?');" style="display:inline;">
                        <input type="hidden" name="delete_id" value="<?= $review['id'] ?>">
                        <button type="submit" class="btn-circle btn-delete" data-tooltip="Delete Review">
                            <svg style="width: 18px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('reviewSearch');
    const tabs = document.querySelectorAll('.tab-btn');
    const cards = document.querySelectorAll('.review-card');

    function filterReviews() {
        const searchTerm = searchInput.value.toLowerCase();
        const activeTab = document.querySelector('.tab-btn.active').dataset.filter;

        cards.forEach(card => {
            const text = card.textContent.toLowerCase();
            const status = card.dataset.status;
            
            const matchesSearch = text.includes(searchTerm);
            const matchesTab = (activeTab === 'all' || activeTab === status);

            if (matchesSearch && matchesTab) {
                card.style.display = 'grid';
            } else {
                card.style.display = 'none';
            }
        });
    }

    searchInput.addEventListener('input', filterReviews);

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            filterReviews();
        });
    });
});
</script>

<?php
$page_content = ob_get_clean();
require 'admin-master.php';