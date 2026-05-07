<?php
require_once __DIR__ . '/../includes/header.php';
?>

<style>
/* About Page Specific Styles */
.about-hero {
    position: relative;
    min-height: 70vh;
    display: flex;
    align-items: center;
    padding: 6rem 1.5rem;
    background: radial-gradient(ellipse at 30% 20%, hsl(35 40% 92%) 0%, var(--bg) 55%);
    overflow: hidden;
}

.about-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%239C8B7E' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    opacity: 0.6;
    pointer-events: none;
}

.about-hero .hero-inner {
    position: relative;
    z-index: 2;
    max-width: 700px;
}

.about-hero .eyebrow {
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

.about-hero h1 {
    font-size: clamp(2.5rem, 6vw, 4.5rem);
    font-weight: 900;
    line-height: 1.05;
    letter-spacing: -0.03em;
    margin: 0 0 1.5rem;
    color: var(--text);
}

.about-hero p {
    font-size: 1.15rem;
    color: var(--text-muted);
    max-width: 540px;
    line-height: 1.7;
    margin: 0;
}

.about-hero .shape {
    position: absolute;
    border-radius: 50%;
    filter: blur(90px);
    opacity: 0.35;
    pointer-events: none;
    z-index: 1;
}

.about-hero .shape-1 {
    width: 350px;
    height: 350px;
    background: hsl(16 55% 65%);
    top: -5%;
    right: 5%;
}

.about-hero .shape-2 {
    width: 250px;
    height: 250px;
    background: hsl(145 20% 50%);
    bottom: -5%;
    right: 20%;
    opacity: 0.2;
}

/* Section header */
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

/* Value cards */
.values-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 2rem;
    margin-bottom: 5rem;
}

.value-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 24px;
    padding: 2.5rem;
    transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
}

.value-card:hover {
    transform: translateY(-8px);
    box-shadow: var(--shadow-xl);
    border-color: var(--accent);
}

.value-card .num {
    font-size: 2.5rem;
    font-weight: 900;
    color: var(--accent);
    opacity: 0.25;
    line-height: 1;
    margin-bottom: 1rem;
    display: block;
}

.value-card h3 {
    font-size: 1.25rem;
    font-weight: 800;
    margin: 0 0 0.75rem;
    color: var(--text);
}

.value-card p {
    color: var(--text-muted);
    line-height: 1.7;
    margin: 0;
    font-size: 0.95rem;
}

/* Team grid */
.team-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 2rem;
    margin-bottom: 5rem;
}

.team-card-v2 {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 24px;
    padding: 2.5rem 2rem;
    text-align: center;
    transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
}

.team-card-v2:hover {
    transform: translateY(-8px);
    box-shadow: var(--shadow-xl);
    border-color: var(--accent);
}

.team-card-v2 .avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: var(--gradient-warm);
    color: white;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    font-weight: 800;
    margin-bottom: 1.25rem;
    box-shadow: var(--shadow-soft);
}

.team-card-v2 h3 {
    font-size: 1.15rem;
    font-weight: 800;
    margin: 0 0 0.25rem;
    color: var(--text);
}

.team-card-v2 .role {
    font-size: 0.8rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--accent);
    margin-bottom: 1rem;
}

.team-card-v2 p {
    color: var(--text-muted);
    font-size: 0.9rem;
    line-height: 1.6;
    margin: 0 0 1.25rem;
}

.team-card-v2 .socials {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
}

.team-card-v2 .socials a {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: var(--surface-alt);
    border: 1px solid var(--border);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: var(--text);
    text-decoration: none;
    font-size: 0.75rem;
    font-weight: 700;
    transition: all 0.2s ease;
}

.team-card-v2 .socials a:hover {
    background: var(--gradient-warm);
    color: white;
    border-color: transparent;
    transform: translateY(-2px);
}

/* Philosophy banner */
.philosophy-banner {
    position: relative;
    background: var(--text);
    color: var(--bg);
    border-radius: 32px;
    padding: 5rem 2rem;
    text-align: center;
    overflow: hidden;
    margin-bottom: 4rem;
}

.philosophy-banner .glow {
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

.philosophy-banner .content {
    position: relative;
    z-index: 2;
    max-width: 700px;
    margin: 0 auto;
}

.philosophy-banner .eyebrow {
    color: var(--accent);
    opacity: 0.9;
    margin-bottom: 1rem;
    display: inline-block;
}

.philosophy-banner h2 {
    font-size: clamp(2rem, 5vw, 3rem);
    font-weight: 900;
    margin-bottom: 1rem;
    color: inherit;
}

.philosophy-banner p {
    opacity: 0.75;
    font-size: 1.1rem;
    line-height: 1.8;
    margin: 0 auto 2rem;
    max-width: 560px;
}

.philosophy-banner .btn-light {
    display: inline-block;
    background: var(--bg);
    color: var(--text);
    padding: 1.1rem 3rem;
    border-radius: 100px;
    font-weight: 700;
    text-decoration: none;
    transition: all 0.3s ease;
}

.philosophy-banner .btn-light:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}

@media (max-width: 768px) {
    .about-hero {
        min-height: 50vh;
        padding: 4rem 1rem;
    }
    .values-grid,
    .team-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    .value-card,
    .team-card-v2 {
        padding: 2rem;
    }
}
</style>

<!-- Hero -->
<section class="about-hero">
    <div class="shape shape-1"></div>
    <div class="shape shape-2"></div>
    <div class="container hero-inner">
        <span class="eyebrow fade-in-up">Our Story</span>
        <h1 class="fade-in-up" style="animation-delay: 0.1s;">Crafting Space for Modern Life.</h1>
        <p class="fade-in-up" style="animation-delay: 0.2s;">Britz Blythe began with a simple idea: that the objects we surround ourselves with should be as intentional as the lives we lead. We curate premium essentials that balance form and function.</p>
    </div>
</section>

<div class="container">
    <!-- Core Values -->
    <div class="section-header fade-in-up">
        <h2>Our Core Values</h2>
        <p>The principles that guide every collection we curate.</p>
    </div>
    <div class="values-grid">
        <article class="value-card fade-in-up" style="--order: 0;">
            <span class="num">01</span>
            <h3>Quality</h3>
            <p>We partner with artisans who prioritize longevity over trends, ensuring every piece lasts a lifetime.</p>
        </article>
        <article class="value-card fade-in-up" style="--order: 1;">
            <span class="num">02</span>
            <h3>Sustainability</h3>
            <p>From vegetable-tanned leathers to recycled linens, our materials are chosen with the planet in mind.</p>
        </article>
        <article class="value-card fade-in-up" style="--order: 2;">
            <span class="num">03</span>
            <h3>Craftsmanship</h3>
            <p>Every product tells a story of skill and dedication, bridging the gap between traditional techniques and modern needs.</p>
        </article>
    </div>

    <!-- Team -->
    <div class="section-header fade-in-up">
        <h2>Meet the Team</h2>
        <p>The visionaries behind our curated collections.</p>
    </div>
    <div class="team-grid">
        <article class="team-card-v2 fade-in-up" style="--order: 0;">
            <div class="avatar">BB</div>
            <h3>Britz Blythe</h3>
            <div class="role">Founder & Creative Director</div>
            <p>A designer with a passion for intentional living and artisanal craft.</p>
            <div class="socials">
                <a href="#" title="LinkedIn">Li</a>
                <a href="#" title="Twitter">Tw</a>
                <a href="#" title="Instagram">In</a>
            </div>
        </article>
        <article class="team-card-v2 fade-in-up" style="--order: 1;">
            <div class="avatar">MC</div>
            <h3>Marcus Chen</h3>
            <div class="role">Head of Sustainability</div>
            <p>Ensuring that every partnership meets our rigorous environmental standards.</p>
            <div class="socials">
                <a href="#" title="LinkedIn">Li</a>
                <a href="#" title="Twitter">Tw</a>
                <a href="#" title="Instagram">In</a>
            </div>
        </article>
        <article class="team-card-v2 fade-in-up" style="--order: 2;">
            <div class="avatar">ER</div>
            <h3>Elena Rodriguez</h3>
            <div class="role">Lead Curator</div>
            <p>Finding the hidden gems from artisans across the globe.</p>
            <div class="socials">
                <a href="#" title="LinkedIn">Li</a>
                <a href="#" title="Twitter">Tw</a>
                <a href="#" title="Instagram">In</a>
            </div>
        </article>
    </div>

    <!-- Philosophy -->
    <section class="philosophy-banner fade-in-up">
        <div class="glow"></div>
        <div class="content">
            <span class="eyebrow">The Philosophy</span>
            <h2>Less, but Better.</h2>
            <p>We believe in the power of a curated environment. By choosing quality over quantity, you create a home that breathes and an office that inspires. Our mission is to help you find those few, perfect items that make every day a bit more beautiful.</p>
            <a href="<?= SITE_URL ?>/products.php" class="btn-light">Shop the Collection</a>
        </div>
    </section>
</div>

<?php include __DIR__ . '/../includes/footer.php'; 

