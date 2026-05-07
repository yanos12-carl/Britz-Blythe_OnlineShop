<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (login_user($email, $password)) {
        if (is_admin()) {
            header('Location: index.php');
            exit;
        } else {
            logout_user();
            $message = 'Access denied. You do not have administrator privileges.';
        }
    } else {
        $message = 'Invalid email or password.';
    }
}

// If already logged in as admin, skip login page
if (is_admin()) { header('Location: index.php'); exit; }
$theme = $_SESSION['admin_theme'] ?? $_COOKIE['admin-theme'] ?? 'dark';
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= $theme ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/components.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/theme.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;1,400&display=swap" rel="stylesheet">
    <script>
        (function() {
            const saved = localStorage.getItem('admin-theme');
            const isDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.dataset.theme = saved || (isDark ? 'dark' : 'light');
        })();
    </script>
    <style>
        :root {
            --bg: #fdfcfb;
            --panel-hero: linear-gradient(135deg, #fdfcfb 0%, #f3f4f6 100%);
            --panel-login: #ffffff;
            --panel-border: rgba(0, 0, 0, 0.06);
            --text: #1a1a1a;
            --text-heading: #111827;
            --muted: #6b7280;
            --accent: #c2410c;
            --accent-gradient: linear-gradient(135deg, #f97316, #fb923c);
            --accent-soft: rgba(194, 65, 12, 0.1);
            --input-bg: #f9fafb;
            --input-border: #e5e7eb;
            --shadow: 0 20px 48px -10px rgba(0, 0, 0, 0.08);
            --glow-1: rgba(249, 115, 22, 0.1);
            --glow-2: rgba(99, 102, 241, 0.05);
        }

        [data-theme="dark"] {
            color-scheme: dark;
            --bg: #090b10;
            --panel-hero: linear-gradient(135deg, #0f172a 0%, #111827 45%, #181f2d 100%);
            --panel-login: rgba(6, 11, 20, 0.95);
            --panel-border: rgba(255, 255, 255, 0.08);
            --text: #e2e8f0;
            --text-heading: #ffffff;
            --muted: #94a3b8;
            --accent: #6366f1;
            --accent-gradient: linear-gradient(135deg, #6366f1, #8b5cf6);
            --accent-soft: rgba(99, 102, 241, 0.14);
            --input-bg: rgba(15, 23, 42, 0.8);
            --input-border: rgba(148, 163, 184, 0.16);
            --shadow: 0 40px 120px rgba(15, 23, 42, 0.33);
            --glow-1: rgba(99, 102, 241, 0.15);
            --glow-2: rgba(147, 197, 253, 0.08);
        }

        * {
            box-sizing: border-box;
            transition: background 0.3s ease, border-color 0.3s ease, color 0.3s ease, box-shadow 0.3s ease;
        }

        html, body {
            margin: 0;
            min-height: 100%;
            font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--text);
            background: radial-gradient(circle at top left, var(--glow-1), transparent 25%),
                        radial-gradient(circle at bottom right, var(--glow-2), transparent 20%),
                        linear-gradient(180deg, var(--bg) 0%, var(--bg) 100%);
        }

        body {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .login-shell {
            width: 100%;
            max-width: 1200px;
            display: grid;
            grid-template-columns: minmax(320px, 1.1fr) minmax(320px, 0.9fr);
            gap: 32px;
            align-items: stretch;
            min-height: calc(100vh - 48px);
        }

        .panel {
            border-radius: 36px;
            overflow: hidden;
            position: relative;
            box-shadow: var(--shadow);
        }

        .hero-panel {
            background: var(--panel-hero);
            padding: 56px 48px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .hero-panel::before,
        .hero-panel::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            filter: blur(36px);
            opacity: 0.7;
        }

        .hero-panel::before {
            width: 320px;
            height: 320px;
            top: -80px;
            right: -90px;
            background: var(--glow-1);
        }

        .hero-panel::after {
            width: 260px;
            height: 260px;
            bottom: -100px;
            left: -80px;
            background: var(--glow-2);
        }

        .brand-heading {
            font-family: 'Playfair Display', serif;
            font-size: clamp(2.4rem, 3vw, 3.4rem);
            letter-spacing: -0.04em;
            margin: 0 0 1rem 0;
            color: var(--text-heading);
        }

        .brand-heading span {
            color: var(--accent);
            font-style: italic;
            font-weight: 400;
        }

        .hero-title {
            font-size: clamp(3rem, 5vw, 5rem);
            line-height: 0.95;
            letter-spacing: -0.06em;
            margin: 0 0 1.25rem 0;
            max-width: 12ch;
            color: var(--text-heading);
        }

        .hero-copy {
            max-width: 520px;
            color: var(--muted);
            line-height: 1.9;
            font-size: 1rem;
            margin-bottom: 1.8rem;
        }

        .hero-note {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            color: #94a3b8;
            font-size: 0.95rem;
            margin-top: auto;
        }

        .hero-note svg {
            width: 1.1rem;
            height: 1.1rem;
            color: var(--accent);
            flex-shrink: 0;
        }

        .login-panel {
            background: var(--panel-login);
            border: 1px solid var(--panel-border);
            padding: 44px 42px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-height: 620px;
        }

        .login-panel h2 {
            margin: 0;
            font-size: 2rem;
            line-height: 1.05;
            color: var(--text-heading);
        }

        .login-panel p {
            margin: 0.9rem 0 0 0;
            color: #94a3b8;
            max-width: 28rem;
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
            font-size: 0.98rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.55rem;
            font-size: 0.84rem;
            color: #cbd5e1;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.12em;
        }

        .input-wrapper {
            position: relative;
        }

        .form-input {
            width: 100%;
            border-radius: 18px;
            border: 1px solid var(--input-border);
            background: var(--input-bg);
            color: var(--text);
            font-size: 1rem;
            padding: 1.15rem 1rem 1.15rem 3.4rem;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .form-input::placeholder {
            color: #64748b;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 5px var(--accent-soft);
            background: var(--panel-login);
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            display: flex;
            align-items: center;
        }

        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            padding: 0.25rem;
            background: transparent;
            color: #94a3b8;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
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
            color: #cbd5e1;
            font-size: 0.95rem;
            cursor: pointer;
        }

        .form-actions input[type="checkbox"] {
            width: 1rem;
            height: 1rem;
            accent-color: var(--accent);
            border-radius: 4px;
            background: #111827;
            border: 1px solid rgba(148, 163, 184, 0.24);
        }

        .form-actions a {
            color: #f8fafc;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .btn-login {
            width: 100%;
            padding: 1.15rem 1rem;
            border-radius: 18px;
            border: none;
            background: var(--accent-gradient);
            color: #fff;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            margin-top: 1.75rem;
        }

        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 20px 40px var(--accent-soft);
        }

        .login-help {
            margin-top: auto;
            text-align: center;
            color: #94a3b8;
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
                min-height: auto;
            }

            .hero-panel,
            .login-panel {
                min-height: auto;
                padding: 42px 32px;
            }

            .login-panel {
                min-height: 600px;
            }
        }

        @media (max-width: 560px) {
            .hero-panel {
                padding: 32px 24px;
            }

            .login-panel {
                padding: 32px 24px;
            }

            .form-actions {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="login-shell">
        <section class="panel hero-panel">
            <a href="<?= SITE_URL ?>" class="brand-heading">Britz <span>Blythe</span></a>
            <h1 class="hero-title">Welcome Back</h1>
            <p class="hero-copy">Sign in to access your account, view orders, and manage handcrafted artisan goods with a polished admin experience.</p>
            <div class="hero-note">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8v4l3 3"/><circle cx="12" cy="12" r="10"/></svg>
                Keep your admin access secure and ready for every customer moment.
            </div>
        </section>

        <section class="panel login-panel">
            <div>
                <h2>Login to your account</h2>
                <p>Enter your credentials to continue.</p>
            </div>

            <div class="form-block">
                <form method="post" action="login.php">
                    <?php if ($message): ?>
                        <div class="alert alert-error">
                            <strong>⚠️ Error:</strong> <?= htmlspecialchars($message) ?>
                        </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="email" class="form-label">Email</label>
                        <div class="input-wrapper">
                            <span class="input-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16v16H4z"/><path d="M22 6l-10 7L2 6"/></svg>
                            </span>
                            <input id="email" name="email" type="email" class="form-input" placeholder="admin@britzblythe.local" required autocomplete="email">
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

                    <button class="btn-login" type="submit">Login</button>
                </form>
            </div>

            <div class="login-help">
                New customer? <a href="<?= SITE_URL ?>/public/register.php">Create an account</a>
            </div>
        </section>
    </div>

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
</body>
</html>
