<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if (login_user($email, $password)) {
        $db = get_db();
        $stmt = $db->prepare('SELECT role FROM users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $_SESSION['user']['id']]);
        $role = $stmt->fetchColumn();
        if ($role === 'admin') {
            header('Location: ../admin/index.php');
        } else {
            header('Location: profile.php');
        }
        exit;
    }
    $message = 'Invalid email or password.';
}

$pageTitle = 'Login';
require_once __DIR__ . '/../includes/header.php';
?>
<style>
    body {
        min-height: 100vh;
        margin: 0;
        background: var(--bg);
        background-image: radial-gradient(circle at top left, rgba(249, 115, 22, 0.12), transparent 24%),
                          radial-gradient(circle at bottom right, rgba(148, 163, 184, 0.12), transparent 22%);
    }

    .login-shell {
        width: 100%;
        max-width: 1180px;
        margin: 0 auto;
        display: grid;
        grid-template-columns: minmax(320px, 1.1fr) minmax(320px, 0.9fr);
        gap: 28px;
        padding: 24px 16px 48px;
    }

    .login-panel,
    .hero-panel {
        border-radius: 32px;
        position: relative;
        overflow: hidden;
        box-shadow: 0 40px 90px rgba(15, 23, 42, 0.32);
    }

    .hero-panel {
        background: linear-gradient(135deg, var(--surface-alt), var(--surface));
        padding: 48px 46px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .hero-panel::before,
    .hero-panel::after {
        content: '';
        position: absolute;
        border-radius: 50%;
        filter: blur(40px);
        opacity: 0.72;
    }

    .hero-panel::before {
        width: 280px;
        height: 280px;
        top: -80px;
        right: -100px;
        background: rgba(249, 115, 22, 0.22);
    }

    .hero-panel::after {
        width: 260px;
        height: 260px;
        bottom: -90px;
        left: -80px;
        background: rgba(96, 165, 250, 0.14);
    }

    .hero-label {
        color: var(--text);
        font-size: 0.85rem;
        letter-spacing: 0.18em;
        text-transform: uppercase;
        margin-bottom: 1rem;
        display: inline-block;
    }

    .hero-heading {
        font-family: 'Playfair Display', serif;
        font-size: clamp(2.6rem, 4vw, 4.5rem);
        color: var(--text);
        line-height: 0.95;
        margin: 0 0 1rem;
    }

    .hero-copy {
        color: var(--text-muted);
        max-width: 520px;
        font-size: 1rem;
        line-height: 1.85;
        margin: 0 0 1.8rem;
    }

    .hero-note {
        display: inline-flex;
        align-items: center;
        gap: 0.75rem;
        color: var(--text-muted);
        font-size: 0.95rem;
        margin-top: auto;
    }

    .hero-note svg {
        width: 1.1rem;
        height: 1.1rem;
        color: var(--accent);
    }

    .login-panel {
        background: var(--surface);
        border: 1px solid var(--border);
        padding: 42px 40px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        min-height: 620px;
    }

    .login-panel h2 {
        margin: 0;
        font-size: 2rem;
        color: var(--text);
    }

    .login-panel p {
        margin: 0.9rem 0 0 0;
        color: var(--text-muted);
        font-size: 0.97rem;
        line-height: 1.8;
    }

    .form-block {
        margin-top: 2.25rem;
    }

    .alert.alert-error {
        background: rgba(248, 113, 113, 0.12);
        border: 1px solid rgba(248, 113, 113, 0.24);
        color: #fecaca;
        padding: 1rem 1.1rem;
        border-radius: 18px;
        margin-bottom: 1.75rem;
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-label {
        display: block;
        margin-bottom: 0.55rem;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        color: var(--text-muted);
        font-weight: 600;
    }

    .input-wrapper {
        position: relative;
    }

    .form-input {
        width: 100%;
        border-radius: 16px;
        border: 1px solid var(--border);
        background: var(--surface-alt);
        color: var(--text);
        padding: 1.1rem 1rem 1.1rem 3.4rem;
        font-size: 1rem;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .form-input::placeholder {
        color: rgba(100, 116, 139, 0.8);
    }

    .form-input:focus {
        outline: none;
        border-color: var(--accent);
        box-shadow: 0 0 0 5px rgba(249, 115, 22, 0.12);
        background: rgba(255, 255, 255, 0.06);
    }

    .input-icon {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-muted);
        display: flex;
        align-items: center;
    }

    .password-toggle {
        position: absolute;
        right: 1rem;
        top: 50%;
        transform: translateY(-50%);
        border: none;
        background: transparent;
        color: var(--text-muted);
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.2rem;
    }

    .password-toggle:hover {
        color: var(--accent);
    }

    .form-actions {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-top: 0.3rem;
    }

    .form-actions label {
        display: inline-flex;
        align-items: center;
        gap: 0.65rem;
        color: var(--text-muted);
        font-size: 0.95rem;
        cursor: pointer;
    }

    .form-actions input[type="checkbox"] {
        width: 1rem;
        height: 1rem;
        accent-color: var(--accent);
        border-radius: 4px;
        background: var(--surface);
        border: 1px solid var(--border);
    }

    .form-actions a {
        color: var(--text);
        text-decoration: none;
        font-weight: 600;
        font-size: 0.95rem;
    }

    .button-primary {
        width: 100%;
        border-radius: 18px;
        border: none;
        background: linear-gradient(135deg, var(--accent), #fb923c);
        color: #fff;
        padding: 1.12rem 1rem;
        font-size: 1rem;
        font-weight: 700;
        cursor: pointer;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        margin-top: 1.7rem;
    }

    .button-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 18px 38px rgba(249, 115, 22, 0.28);
    }

    .login-help {
        margin-top: auto;
        text-align: center;
        color: var(--text-muted);
        font-size: 0.95rem;
        line-height: 1.7;
    }

    .login-help a {
        color: var(--accent);
        text-decoration: none;
        font-weight: 700;
    }

    @media (max-width: 980px) {
        .login-shell {
            grid-template-columns: 1fr;
        }

        .hero-panel,
        .login-panel {
            min-height: auto;
            padding: 38px 28px;
        }
    }

    @media (max-width: 560px) {
        .login-shell {
            padding: 20px 12px 32px;
            gap: 18px;
        }

        .hero-heading {
            font-size: clamp(2.2rem, 8vw, 3.2rem);
        }

        .form-actions {
            flex-direction: column;
            align-items: flex-start;
        }
    }
</style>

<section class="login-shell">
    <section class="hero-panel">
        <div class="hero-label">Welcome Back</div>
        <h1 class="hero-heading">Sign in to explore your orders and profile.</h1>
        <p class="hero-copy">Access your account to track purchases, save favorites, and stay connected with handcrafted artisan goods from Britz & Blythe.</p>
        <div class="hero-note">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8v4l3 3"/><circle cx="12" cy="12" r="10"/></svg>
            Secure, elegant, and designed for modern shopping.
        </div>
    </section>

    <section class="login-panel">
        <div>
            <h2>Login to your account</h2>
            <p>Enter your credentials to continue.</p>
        </div>

        <div class="form-block">
            <form class="auth-form" method="post" action="<?= SITE_URL ?>/public/login.php">
                <?php if ($message): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="email" class="form-label">Email</label>
                    <div class="input-wrapper">
                        <span class="input-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16v16H4z"/><path d="M22 6l-10 7L2 6"/></svg>
                        </span>
                        <input id="email" name="email" type="email" class="form-input" placeholder="you@example.com" required autocomplete="email">
                    </div>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-wrapper">
                        <span class="input-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                        </span>
                        <input id="password" name="password" type="password" class="form-input" placeholder="••••••••" required autocomplete="current-password">
                        <button type="button" id="togglePassword" class="password-toggle" aria-label="Toggle password visibility">
                            <svg id="eyeIcon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg id="eyeOffIcon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none;"><path d="M17.94 17.94A10.94 10.94 0 0112 19c-7 0-11-7-11-7a20.07 20.07 0 015.2-5.94"/><path d="M1 1l22 22"/><path d="M9.53 9.53a3 3 0 104.24 4.24"/><path d="M12 5a7 7 0 017 7c0 1.38-.41 2.66-1.12 3.74"/></svg>
                        </button>
                    </div>
                </div>

                <div class="form-actions">
                    <label>
                        <input type="checkbox" name="remember" value="1">
                        Remember me
                    </label>
                    <a href="#">Forgot password?</a>
                </div>

                <button type="submit" class="button button-primary">Login</button>
            </form>
        </div>

        <div class="login-help">
            New customer? <a href="<?= SITE_URL ?>/register.php">Create an account</a>
        </div>
    </section>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const passwordInput = document.getElementById('password');
        const toggleBtn = document.getElementById('togglePassword');
        const eyeIcon = document.getElementById('eyeIcon');
        const eyeOffIcon = document.getElementById('eyeOffIcon');

        if (toggleBtn && passwordInput) {
            toggleBtn.addEventListener('click', function() {
                const isPassword = passwordInput.type === 'password';
                passwordInput.type = isPassword ? 'text' : 'password';
                eyeIcon.style.display = isPassword ? 'none' : 'block';
                eyeOffIcon.style.display = isPassword ? 'block' : 'none';
            });
        }
    });
</script>
<?php include __DIR__ . '/../includes/footer.php';
