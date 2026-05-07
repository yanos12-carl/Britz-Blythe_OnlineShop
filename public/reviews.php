<?php
require_once __DIR__ . '/../includes/header.php';

$message = '';
$messageType = '';

// Handle AJAX review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $rating = $_POST['rating'] ?? 5;
    $comment = $_POST['comment'] ?? '';
    $photo_path = null;

    if (!empty($_FILES['photo']['name'])) {
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $upload_name = 'review_' . time() . '.' . $ext;
        $relative_dir = 'assets/images/reviews/';
        $full_dir = __DIR__ . '/' . $relative_dir;

        if (!is_dir($full_dir)) mkdir($full_dir, 0777, true);

        if (move_uploaded_file($_FILES['photo']['tmp_name'], $full_dir . $upload_name)) {
            $photo_path = generate_webp(resize_image($relative_dir . $upload_name, 600, 600));
        }
    }

    if ($name && $comment && submit_review($name, (int)$rating, $comment, $photo_path)) {
        $message = 'Thank you! Your review has been submitted for approval.';
        $messageType = 'success';
    } else {
        $message = 'Please fill out all fields correctly.';
        $messageType = 'error';
    }

    // If AJAX request, return JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => $messageType === 'success', 'message' => $message]);
        exit;
    }
}

$reviews = get_reviews(true);
$avg_rating = count($reviews) > 0 ? array_sum(array_column($reviews, 'rating')) / count($reviews) : 0;
$total_reviews = count($reviews);

// Rating distribution
$distribution = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
foreach ($reviews as $r) {
    $distribution[(int)$r['rating']]++;
}

// Filter & sort
$filter_rating = $_GET['rating'] ?? '';
$sort_by = $_GET['sort'] ?? 'newest';

if ($filter_rating !== '') {
    $reviews = array_filter($reviews, fn($r) => (int)$r['rating'] === (int)$filter_rating);
}

switch ($sort_by) {
    case 'highest':
        usort($reviews, fn($a, $b) => $b['rating'] <=> $a['rating']);
        break;
    case 'lowest':
        usort($reviews, fn($a, $b) => $a['rating'] <=> $b['rating']);
        break;
    default:
        usort($reviews, fn($a, $b) => strtotime($b['created_at']) <=> strtotime($a['created_at']));
        break;
}

// Pagination
$per_page = 6;
$page = max(1, (int)($_GET['page'] ?? 1));
$total_pages = max(1, ceil(count($reviews) / $per_page));
$page = min($page, $total_pages);
$offset = ($page - 1) * $per_page;
$paged_reviews = array_slice($reviews, $offset, $per_page);
?>

<style>
/* Reviews Page Specific Styles */
.reviews-hero {
    position: relative;
    min-height: 50vh;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 5rem 1.5rem;
    background: radial-gradient(ellipse at 50% 0%, hsl(35 40% 92%) 0%, var(--bg) 60%);
    overflow: hidden;
}

.reviews-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%239C8B7E' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    opacity: 0.6;
    pointer-events: none;
}

.reviews-hero .hero-inner {
    position: relative;
    z-index: 2;
}

.reviews-hero .eyebrow {
    display: inline-block;
    font-size: 0.85rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.15em;
    color: var(--accent);
    background: var(--surface);
    padding: 0.5rem 1.25rem;
    border-radius: 99px;
    border: 1px solid var(--border);
    margin-bottom: 1.5rem;
}

.reviews-hero h1 {
    font-size: clamp(2.5rem, 6vw, 4rem);
    font-weight: 900;
    line-height: 1.05;
    letter-spacing: -0.03em;
    margin: 0 0 1rem;
    color: var(--text);
}

.reviews-hero p {
    font-size: 1.15rem;
    color: var(--text-muted);
    max-width: 500px;
    margin: 0 auto;
    line-height: 1.6;
}

/* Rating summary card */
.rating-summary-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 24px;
    padding: 2.5rem;
    text-align: center;
    margin-bottom: 2rem;
}

.rating-summary-card .big-rating {
    font-size: 4rem;
    font-weight: 900;
    line-height: 1;
    color: var(--text);
    display: block;
    margin-bottom: 0.5rem;
}

.rating-summary-card .stars {
    margin-bottom: 0.5rem;
    font-size: 1.25rem;
    color: #f59e0b;
}

.rating-summary-card .review-count {
    color: var(--text-muted);
    font-size: 0.9rem;
    margin: 0;
}

/* Rating bars */
.rating-bars {
    margin-bottom: 2rem;
}

.rating-bar {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.5rem;
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 8px;
    transition: background 0.2s ease;
    text-decoration: none;
    color: inherit;
}

.rating-bar:hover {
    background: var(--surface-alt);
}

.rating-bar.active-filter {
    background: hsl(35 40% 92%);
}

.rating-bar .star-label {
    font-size: 0.8rem;
    font-weight: 700;
    color: var(--text-muted);
    width: 40px;
    text-align: right;
}

.rating-bar .bar-track {
    flex: 1;
    height: 8px;
    background: var(--surface-alt);
    border-radius: 99px;
    overflow: hidden;
}

.rating-bar .bar-fill {
    height: 100%;
    background: var(--gradient-warm);
    border-radius: 99px;
    transition: width 0.6s ease;
}

.rating-bar .bar-count {
    font-size: 0.8rem;
    color: var(--text-muted);
    width: 30px;
}

/* Review form card */
.review-form-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 24px;
    padding: 2rem;
}

.review-form-card h3 {
    font-size: 1.1rem;
    font-weight: 800;
    margin: 0 0 1.25rem;
    color: var(--text);
}

.review-form-card .form-group {
    margin-bottom: 1rem;
}

.review-form-card label {
    display: block;
    font-size: 0.8rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--text-muted);
    margin-bottom: 0.4rem;
}

.review-form-card input[type="text"],
.review-form-card textarea {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid var(--border);
    border-radius: 12px;
    background: var(--bg);
    color: var(--text);
    font-family: inherit;
    font-size: 0.95rem;
    transition: all 0.2s ease;
}

.review-form-card input:focus,
.review-form-card textarea:focus {
    outline: none;
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.review-form-card input[type="file"] {
    padding: 0.5rem 0;
    font-size: 0.85rem;
}

/* Star rating selector */
.star-rating-input {
    display: flex;
    gap: 0.25rem;
    font-size: 1.75rem;
    cursor: pointer;
    margin-bottom: 0.5rem;
}

.star-rating-input .star {
    color: var(--border);
    transition: all 0.15s ease;
    line-height: 1;
}

.star-rating-input .star.active,
.star-rating-input .star:hover,
.star-rating-input .star.hovered {
    color: #f59e0b;
}

.star-rating-input .star:hover {
    transform: scale(1.15);
}

.review-form-card .btn-submit {
    width: 100%;
    padding: 1rem;
    border: none;
    border-radius: 12px;
    background: var(--gradient-warm);
    color: white;
    font-weight: 700;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.review-form-card .btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(99, 102, 241, 0.3);
}

.review-form-card .btn-submit:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.review-form-card .alert {
    padding: 0.75rem 1rem;
    border-radius: 12px;
    font-size: 0.9rem;
    margin-bottom: 1rem;
    display: none;
}

.review-form-card .alert.show {
    display: block;
}

.review-form-card .alert-success {
    background: hsl(145 60% 95%);
    color: hsl(145 60% 30%);
    border: 1px solid hsl(145 60% 85%);
}

.review-form-card .alert-error {
    background: hsl(0 70% 95%);
    color: hsl(0 60% 40%);
    border: 1px solid hsl(0 60% 85%);
}

/* Photo preview */
.photo-preview {
    width: 100%;
    max-width: 200px;
    border-radius: 12px;
    margin-top: 0.5rem;
    display: none;
    object-fit: cover;
}

.photo-preview.show {
    display: block;
}

/* Reviews toolbar */
.reviews-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.reviews-toolbar .result-count {
    font-size: 0.9rem;
    color: var(--text-muted);
}

.reviews-toolbar .sort-select {
    padding: 0.5rem 1rem;
    border: 1px solid var(--border);
    border-radius: 10px;
    background: var(--surface);
    color: var(--text);
    font-family: inherit;
    font-size: 0.9rem;
    cursor: pointer;
}

/* Reviews grid */
.reviews-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.5rem;
}

.review-card-v2 {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 20px;
    padding: 2rem;
    transition: all 0.3s ease;
}

.review-card-v2:hover {
    box-shadow: var(--shadow-md);
    border-color: var(--accent);
}

.review-card-v2 .review-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.review-card-v2 .reviewer {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.review-card-v2 .avatar {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: var(--gradient-warm);
    color: white;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    font-weight: 800;
    flex-shrink: 0;
}

.review-card-v2 .reviewer-info h4 {
    margin: 0;
    font-size: 1rem;
    font-weight: 700;
    color: var(--text);
}

.review-card-v2 .reviewer-info .date {
    font-size: 0.8rem;
    color: var(--text-muted);
    margin: 0;
}

.review-card-v2 .reviewer-info .stars {
    color: #f59e0b;
    font-size: 0.9rem;
}

.review-card-v2 .review-image {
    width: 100%;
    max-width: 300px;
    border-radius: 12px;
    margin-bottom: 1rem;
    object-fit: cover;
}

.review-card-v2 .review-text {
    color: var(--text-muted);
    line-height: 1.7;
    margin: 0;
    font-style: italic;
}

/* Empty state */
.empty-state-v2 {
    text-align: center;
    padding: 4rem 2rem;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 20px;
}

.empty-state-v2 .icon {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.empty-state-v2 p {
    color: var(--text-muted);
    margin: 0;
}

/* Pagination */
.pagination-v2 {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    margin-top: 2rem;
}

.pagination-v2 a,
.pagination-v2 span {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 10px;
    font-size: 0.9rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s ease;
}

.pagination-v2 a {
    background: var(--surface);
    border: 1px solid var(--border);
    color: var(--text);
}

.pagination-v2 a:hover {
    background: var(--surface-alt);
    border-color: var(--accent);
}

.pagination-v2 a.active {
    background: var(--gradient-warm);
    color: white;
    border-color: transparent;
}

/* Layout */
.reviews-layout {
    display: grid;
    grid-template-columns: 320px 1fr;
    gap: 2.5rem;
    padding: 3rem 0;
}

@media (max-width: 900px) {
    .reviews-layout {
        grid-template-columns: 1fr;
        gap: 2rem;
    }
    .reviews-hero {
        min-height: 40vh;
        padding: 3rem 1rem;
    }
}
</style>

<!-- Hero -->
<section class="reviews-hero">
    <div class="hero-inner">
        <span class="eyebrow fade-in-up">Customer Voice</span>
        <h1 class="fade-in-up" style="animation-delay: 0.1s;">Reviews & Experiences</h1>
        <p class="fade-in-up" style="animation-delay: 0.2s;">Transparency is at the heart of Britz Blythe. Read what our community has to say.</p>
    </div>
</section>

<div class="container">
    <div class="reviews-layout">
        <!-- Sidebar -->
        <aside class="fade-in-up">
            <!-- Rating Summary -->
            <div class="rating-summary-card">
                <span class="big-rating"><?= number_format($avg_rating, 1) ?></span>
                <div class="stars"><?= render_stars(round($avg_rating)) ?></div>
                <p class="review-count"><?= $total_reviews ?> Verified Reviews</p>
            </div>

            <!-- Rating Distribution -->
            <div class="rating-bars">
                <?php for ($s = 5; $s >= 1; $s--):
                    $count = $distribution[$s] ?? 0;
                    $pct = $total_reviews > 0 ? ($count / $total_reviews) * 100 : 0;
                    $isActive = $filter_rating === (string)$s;
                    $url = 'reviews.php?' . http_build_query(array_filter(['rating' => $isActive ? null : $s, 'sort' => $sort_by, 'page' => null]));
                ?>
                <a href="<?= $url ?>" class="rating-bar <?= $isActive ? 'active-filter' : '' ?>">
                    <span class="star-label"><?= $s ?> ★</span>
                    <div class="bar-track">
                        <div class="bar-fill" style="width: <?= $pct ?>%"></div>
                    </div>
                    <span class="bar-count"><?= $count ?></span>
                </a>
                <?php endfor; ?>
                <?php if ($filter_rating !== ''): ?>
                    <a href="reviews.php?sort=<?= $sort_by ?>" style="display: block; text-align: center; font-size: 0.85rem; color: var(--accent); margin-top: 0.5rem; text-decoration: none;">Clear filter</a>
                <?php endif; ?>
            </div>

            <!-- Review Form -->
            <div class="review-form-card">
                <h3>Share Your Thoughts</h3>
                <div class="alert alert-<?= $messageType ?> <?= $message ? 'show' : '' ?>" id="reviewAlert"><?= htmlspecialchars($message) ?></div>
                <form action="reviews.php" method="POST" enctype="multipart/form-data" id="reviewForm">
                    <div class="form-group">
                        <label for="rev-name">Your Name</label>
                        <input type="text" id="rev-name" name="name" required placeholder="e.g. Alex Doe">
                    </div>

                    <div class="form-group">
                        <label>Your Rating</label>
                        <div class="star-rating-input" id="starRating">
                            <span class="star" data-value="1">★</span>
                            <span class="star" data-value="2">★</span>
                            <span class="star" data-value="3">★</span>
                            <span class="star" data-value="4">★</span>
                            <span class="star" data-value="5">★</span>
                        </div>
                        <input type="hidden" name="rating" id="ratingInput" value="5">
                    </div>

                    <div class="form-group">
                        <label for="rev-comment">Review</label>
                        <textarea id="rev-comment" name="comment" rows="4" required placeholder="What did you think about our products?"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="rev-photo">Add a Photo (Optional)</label>
                        <input type="file" id="rev-photo" name="photo" accept="image/*">
                        <img id="photoPreview" class="photo-preview" alt="Preview">
                    </div>

                    <button type="submit" class="btn-submit" id="submitBtn">Submit Review</button>
                </form>
            </div>
        </aside>

        <!-- Reviews List -->
        <div>
            <div class="reviews-toolbar fade-in-up">
                <span class="result-count"><?= count($reviews) ?> review<?= count($reviews) !== 1 ? 's' : '' ?></span>
                <form method="GET" action="reviews.php">
                    <?php if ($filter_rating !== ''): ?>
                        <input type="hidden" name="rating" value="<?= htmlspecialchars($filter_rating) ?>">
                    <?php endif; ?>
                    <select name="sort" class="sort-select" onchange="this.form.submit()">
                        <option value="newest" <?= $sort_by === 'newest' ? 'selected' : '' ?>>Newest First</option>
                        <option value="highest" <?= $sort_by === 'highest' ? 'selected' : '' ?>>Highest Rated</option>
                        <option value="lowest" <?= $sort_by === 'lowest' ? 'selected' : '' ?>>Lowest Rated</option>
                    </select>
                </form>
            </div>

            <div class="reviews-grid">
                <?php if (empty($paged_reviews)): ?>
                    <div class="empty-state-v2 fade-in-up">
                        <div class="icon">✍️</div>
                        <p><?= $filter_rating !== '' ? 'No reviews with ' . $filter_rating . ' stars yet.' : 'Be the first to leave a review!' ?></p>
                    </div>
                <?php else: ?>
                    <?php foreach ($paged_reviews as $i => $review): ?>
                        <article class="review-card-v2 fade-in-up" style="--order: <?= $i % 5 ?>">
                            <div class="review-header">
                                <div class="reviewer">
                                    <div class="avatar"><?= strtoupper(substr($review['user_name'], 0, 2)) ?></div>
                                    <div class="reviewer-info">
                                        <h4><?= htmlspecialchars($review['user_name']) ?></h4>
                                        <div class="stars"><?= render_stars($review['rating']) ?></div>
                                        <p class="date"><?= date('M d, Y', strtotime($review['created_at'])) ?></p>
                                    </div>
                                </div>
                            </div>
                            <?php if (!empty($review['image'])): ?>
                                <img src="<?= SITE_URL . '/' . $review['image'] ?>" alt="Customer review photo" class="review-image">
                            <?php endif; ?>
                            <p class="review-text">"<?= nl2br(htmlspecialchars($review['comment'])) ?>"</p>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav class="pagination-v2">
                    <?php for ($i = 1; $i <= $total_pages; $i++):
                        $isActive = $i === $page;
                        $query = array_filter(['rating' => $filter_rating ?: null, 'sort' => $sort_by, 'page' => $isActive ? null : $i]);
                        $url = 'reviews.php' . (!empty($query) ? '?' . http_build_query($query) : '');
                    ?>
                        <?php if ($isActive): ?>
                            <span class="active"><?= $i ?></span>
                        <?php else: ?>
                            <a href="<?= $url ?>"><?= $i ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
(function() {
    // Star rating interaction
    const starContainer = document.getElementById('starRating');
    const ratingInput = document.getElementById('ratingInput');
    const stars = starContainer.querySelectorAll('.star');
    let currentRating = 5;

    function updateStars(value, hover = false) {
        stars.forEach(star => {
            const starVal = parseInt(star.dataset.value);
            star.classList.toggle('active', starVal <= value && !hover);
            star.classList.toggle('hovered', starVal <= value && hover);
        });
    }

    stars.forEach(star => {
        star.addEventListener('mouseenter', () => updateStars(parseInt(star.dataset.value), true));
        star.addEventListener('mouseleave', () => updateStars(currentRating, false));
        star.addEventListener('click', () => {
            currentRating = parseInt(star.dataset.value);
            ratingInput.value = currentRating;
            updateStars(currentRating, false);
        });
    });

    // Photo preview
    const photoInput = document.getElementById('rev-photo');
    const photoPreview = document.getElementById('photoPreview');
    photoInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = e => {
                photoPreview.src = e.target.result;
                photoPreview.classList.add('show');
            };
            reader.readAsDataURL(this.files[0]);
        }
    });

    // AJAX form submission
    const form = document.getElementById('reviewForm');
    const alert = document.getElementById('reviewAlert');
    const submitBtn = document.getElementById('submitBtn');

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        submitBtn.disabled = true;
        submitBtn.textContent = 'Submitting...';

        const formData = new FormData(form);

        try {
            const response = await fetch('reviews.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await response.json();

            alert.textContent = data.message;
            alert.className = 'alert alert-' + (data.success ? 'success' : 'error') + ' show';

            if (data.success) {
                form.reset();
                currentRating = 5;
                ratingInput.value = 5;
                updateStars(5, false);
                photoPreview.classList.remove('show');
                photoPreview.src = '';
            }
        } catch (err) {
            alert.textContent = 'Something went wrong. Please try again.';
            alert.className = 'alert alert-error show';
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Submit Review';
        }
    });
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; 

