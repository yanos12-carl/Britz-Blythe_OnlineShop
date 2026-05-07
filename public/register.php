<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($name && $email && $password && register_user($name, $email, $password)) {
        login_user($email, $password);
        header('Location: profile.php');
        exit;
    }
    $message = 'Please complete the form with a valid email.';
}

require_once __DIR__ . '/../includes/header.php';
?>
<section class="page-header">
    <div>
        <h1>Create Account</h1>
        <p>Get faster checkout and order tracking.</p>
    </div>
</section>
<form class="auth-form" method="post" action="<?= SITE_URL ?>/register.php">
    <?php if ($message): ?><div class="alert alert-error"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <label for="name">Full Name</label>
    <input id="name" name="name" type="text" required>
    <label for="email">Email</label>
    <input id="email" name="email" type="email" required>
    <label for="password">Password</label>
    <input id="password" name="password" type="password" required>
    <button type="submit" class="button button-primary">Register</button>
    <p>Already have an account? <a href="<?= SITE_URL ?>/login.php">Login</a></p>
</form>
<?php include __DIR__ . '/../includes/footer.php';
