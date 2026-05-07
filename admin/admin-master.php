<?php
/**
 * Master Admin Layout with Dark Mode Toggle
 * Include this in all admin pages for consistency
 * Usage: Define $page_title, $page_description, $page_content, then include this file.
 */

// Security check
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

// Theme logic
$theme = $_SESSION['admin_theme'] ?? $_COOKIE['admin-theme'] ?? 'light';
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= $theme ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Dashboard' ?> — <?= SITE_NAME ?> Admin</title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/components.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/theme.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/admin-light.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/admin-dark.css">
    <style>
        :root {
            --sidebar-width: 280px;
            --admin-bg: hsl(35 30% 97%);
            --admin-surface: hsl(35 25% 95%);
            --admin-text: hsl(25 20% 15%);
            --admin-accent: hsl(16 55% 42%);
            --admin-border: hsl(35 20% 85%);
        }
        
        [data-theme="dark"] {
            --admin-bg: #0f0f23;
            --admin-surface: #1e1e2e;
            --admin-text: #e2e8f0;
            --admin-accent: #6366f1;
            --admin-border: rgba(148, 163, 184, 0.2);
        }

        * {
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        .admin-sidebar {
            width: var(--sidebar-width);
            background: var(--admin-surface);
            border-right: 1px solid var(--admin-border);
            padding: 2rem 1.5rem;
            height: 100vh;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            overflow-y: auto;
        }

        .brand {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--admin-accent);
            margin-bottom: 2rem;
            text-decoration: none;
        }

        .brand span {
            opacity: 0.7;
            font-weight: 500;
        }

        .admin-nav {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .admin-nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0.875rem 1.25rem;
            border-radius: 12px;
            color: var(--admin-text) !important;
            font-size: 0.95rem;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.25s ease;
            opacity: 0.85;
        }

        .admin-nav a svg {
            width: 20px;
            height: 20px;
            stroke-width: 2;
            transition: all 0.25s ease;
        }

        .admin-nav a:hover svg,
        .admin-nav a.active svg {
            stroke: var(--admin-accent);
            transform: scale(1.1);
        }

        .admin-nav a:hover {
            background: rgba(99, 102, 241, 0.1) !important;
            color: var(--admin-accent);
            opacity: 1;
            transform: translateX(4px);
        }

        .admin-nav a.active {
            background: rgba(99, 102, 241, 0.15) !important;
            color: var(--admin-accent);
            opacity: 1;
        }

        .breadcrumb {
            display: flex;
            gap: 8px;
            font-size: 0.85rem;
            color: var(--admin-text);
            opacity: 0.6;
            margin-bottom: 0.5rem;
            list-style: none;
            padding: 0;
        }

        .nav-divider {
            height: 1px;
            background: var(--admin-border);
            margin: 1rem 0;
        }

        .logout-link {
            color: #ef4444 !important;
            opacity: 0.7;
        }

        .logout-link:hover {
            background: rgba(239, 68, 68, 0.1) !important;
            opacity: 1;
        }

        .admin-main {
            margin-left: var(--sidebar-width);
            padding: 3rem;
            min-height: 100vh;
            background: var(--admin-bg);
        }

        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 3rem;
            border-bottom: 1px solid var(--admin-border);
            padding-bottom: 1.5rem;
        }

        .header-left h1 {
            font-size: 2rem;
            margin: 0 0 0.5rem 0;
            color: var(--admin-text);
        }

        .header-left p {
            margin: 0;
            color: var(--admin-text);
            opacity: 0.75;
            font-size: 0.95rem;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .theme-toggle-wrapper {
            display: flex;
            align-items: center;
        }

        .theme-toggle-btn {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            border: 1px solid var(--admin-border);
            background: var(--admin-surface);
            color: var(--admin-text);
            cursor: pointer;
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.25s ease;
        }

        .theme-toggle-btn:hover {
            background: rgba(99, 102, 241, 0.1);
            border-color: var(--admin-accent);
        }

        .user-info {
            background: var(--admin-surface);
            border: 1px solid var(--admin-border);
            padding: 0.75rem 1.25rem;
            border-radius: 12px;
            color: var(--admin-text);
            font-weight: 500;
            font-size: 0.9rem;
        }

        .admin-card {
            background: var(--admin-surface);
            border-radius: 16px;
            padding: 2rem;
            border: 1px solid var(--admin-border);
            box-shadow: 0 2px 12px -2px rgba(0, 0, 0, 0.06);
        }

        .admin-content {
            animation: fadeIn 0.25s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 768px) {
            .admin-sidebar {
                width: 100%;
                height: auto;
                position: relative;
                border-right: none;
                border-bottom: 1px solid var(--admin-border);
                padding: 1.5rem;
            }

            .admin-main {
                margin-left: 0;
                padding: 1.5rem;
            }

            .admin-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
        }
    </style>
    
    <script>
        (function() {
            const saved = localStorage.getItem('admin-theme');
            const isDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.dataset.theme = saved || (isDark ? 'dark' : 'light');
        })();
    </script>
</head>
<body class="admin-dashboard">
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="brand">Britz Blythe <span>Admin</span></div>
            <nav class="admin-nav">
                <a href="index.php" class="<?= $current_page === 'dashboard' ? 'active' : '' ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    Dashboard
                </a>
                <a href="products.php" class="<?= $current_page === 'products' ? 'active' : '' ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    Products
                </a>
                <a href="orders.php" class="<?= $current_page === 'orders' ? 'active' : '' ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    Orders
                </a>
                <a href="customers.php" class="<?= $current_page === 'customers' ? 'active' : '' ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    Customers
                </a>
                <a href="categories.php" class="<?= $current_page === 'categories' ? 'active' : '' ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    Categories
                </a>
                <a href="reviews.php" class="<?= $current_page === 'reviews' ? 'active' : '' ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.382-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                    Reviews
                </a>
                <div class="nav-divider"></div>
                <a href="settings.php" class="<?= $current_page === 'settings' ? 'active' : '' ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Settings
                </a>
                <a href="logout.php" class="logout-link">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    Logout
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="admin-main">
            <!-- Header -->
            <header class="admin-header">
                <div class="header-left">
                    <ul class="breadcrumb">
                        <li>Admin</li>
                        <li>/</li>
                        <li style="color: var(--admin-accent); font-weight: 700;"><?= $page_title ?? 'Dashboard' ?></li>
                    </ul>
                    <h1><?= $page_title ?? 'Dashboard' ?></h1>
                    <p><?= $page_description ?? '' ?></p>
                </div>
                <div class="header-right">
                    <div class="theme-toggle-wrapper">
                        <button class="theme-toggle-btn" id="admin-theme-toggle" aria-label="Toggle dark mode">
                            <span class="theme-icon sun-icon">☀️</span>
                            <span class="theme-icon moon-icon" style="display: none;">🌙</span>
                        </button>
                    </div>
                    <div class="user-info">
                        Welcome, <?= htmlspecialchars(get_logged_in_user()['name']) ?>
                    </div>
                </div>
            </header>

            <!-- Flash Messages -->
            <?php if (isset($_SESSION['admin_message'])): ?>
                <div class="alert alert-<?= $_SESSION['admin_message_type'] ?? 'info' ?>">
                    <?= htmlspecialchars($_SESSION['admin_message']) ?>
                </div>
                <?php 
                unset($_SESSION['admin_message'], $_SESSION['admin_message_type']);
                ?>
            <?php endif; ?>

            <!-- Page Content Placeholder -->
            <div class="admin-content">
                <?= $page_content ?? '' ?>
            </div>
        </main>
    </div>

    <!-- Dark Mode Toggle -->
    <script>
        (function() {
            const html = document.documentElement;
            const toggle = document.getElementById('admin-theme-toggle');
            const sunIcon = document.querySelector('.sun-icon');
            const moonIcon = document.querySelector('.moon-icon');
            
            // Load saved theme or use system preference
            const saved = localStorage.getItem('admin-theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const theme = saved || (prefersDark ? 'dark' : 'light');
            
            setTheme(theme);
            
            function setTheme(t) {
                html.dataset.theme = t;
                localStorage.setItem('admin-theme', t);
                
                if (sunIcon && moonIcon) {
                    if (t === 'dark') {
                        sunIcon.style.display = 'none';
                        moonIcon.style.display = 'inline';
                    } else {
                        sunIcon.style.display = 'inline';
                        moonIcon.style.display = 'none';
                    }
                }
            }
            
            if (toggle) {
                toggle.addEventListener('click', function() {
                    const current = html.dataset.theme || 'light';
                    const newTheme = current === 'dark' ? 'light' : 'dark';
                    setTheme(newTheme);
                });
            }
        })();
    </script>
</body>
</html>
