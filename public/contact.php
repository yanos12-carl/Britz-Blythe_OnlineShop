<?php
require_once __DIR__ . '/../includes/header.php';
?>
<section class="page-header">
    <div>
        <h1>Contact</h1>
        <p>Reach out with questions about orders, products, or collaboration.</p>
    </div>
</section>
<section class="content-block">
    <h2>Customer support</h2>
    <p>Email us at <a href="mailto:hello@britzblythe.local">hello@britzblythe.local</a> or use the form below.</p>
    <form class="contact-form">
        <label for="name">Name</label>
        <input id="name" type="text" placeholder="Your name">
        <label for="email">Email</label>
        <input id="email" type="email" placeholder="you@example.com">
        <label for="message">Message</label>
        <textarea id="message" rows="5" placeholder="How can we help?"></textarea>
        <button type="submit" class="button button-primary">Send Message</button>
    </form>
</section>
<?php include __DIR__ . '/../includes/footer.php';
