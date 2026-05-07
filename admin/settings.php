<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$current_page = 'settings';
$page_title = 'Settings';
$page_description = 'Site configuration and store information.';

ob_start();
?>

<style>
    .settings-grid {
        display: grid;
        gap: 2rem;
    }

    .settings-card {
        background: var(--admin-surface);
        border: 1px solid var(--admin-border);
        border-radius: 16px;
        padding: 2rem;
    }

    .settings-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid var(--admin-border);
    }

    .settings-header h2 {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--admin-text);
    }

    .settings-section {
        margin-bottom: 1.5rem;
    }

    .settings-section:last-child {
        margin-bottom: 0;
    }

    .setting-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 0;
        border-bottom: 1px solid var(--admin-border);
    }

    .setting-item:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }

    .setting-label {
        flex: 1;
    }

    .setting-name {
        font-weight: 700;
        color: var(--admin-text);
        margin: 0 0 0.25rem 0;
    }

    .setting-description {
        font-size: 0.85rem;
        color: var(--admin-text);
        opacity: 0.6;
        margin: 0;
    }

    .setting-value {
        padding: 0.5rem 1rem;
        background: var(--admin-bg);
        border: 1px solid var(--admin-border);
        border-radius: 8px;
        color: var(--admin-text);
        font-weight: 600;
        word-break: break-all;
        max-width: 400px;
    }

    .info-icon {
        font-size: 1rem;
        margin-right: 0.5rem;
    }

    @media (max-width: 768px) {
        .setting-item {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
        }

        .setting-value {
            width: 100%;
            max-width: 100%;
        }
    }
</style>

<div class="settings-grid">
    <!-- General Settings -->
    <div class="settings-card">
        <div class="settings-header">
            <h2>⚙️ General Settings</h2>
        </div>
        <div class="settings-section">
            <div class="setting-item">
                <div class="setting-label">
                    <p class="setting-name">Store Name</p>
                    <p class="setting-description">Your shop's display name</p>
                </div>
                <div class="setting-value"><?= htmlspecialchars(SITE_NAME) ?></div>
            </div>
            <div class="setting-item">
                <div class="setting-label">
                    <p class="setting-name">Store URL</p>
                    <p class="setting-description">Primary domain</p>
                </div>
                <div class="setting-value"><?= htmlspecialchars(SITE_URL) ?></div>
            </div>
            <div class="setting-item">
                <div class="setting-label">
                    <p class="setting-name">Currency</p>
                    <p class="setting-description">Default transaction currency</p>
                </div>
                <div class="setting-value"><?= htmlspecialchars(CURRENCY) ?></div>
            </div>
        </div>
    </div>

    <!-- Appearance Settings -->
    <div class="settings-card">
        <div class="settings-header">
            <h2>🎨 Appearance</h2>
        </div>
        <div class="settings-section">
            <div class="setting-item">
                <div class="setting-label">
                    <p class="setting-name">Theme Mode</p>
                    <p class="setting-description">Use the toggle in the admin header to switch between light and dark themes</p>
                </div>
                <div style="padding: 0.5rem 1rem; background: rgba(99, 102, 241, 0.1); border-radius: 8px; color: var(--admin-accent); font-weight: 600; font-size: 0.9rem;">
                    ☀️ Light / 🌙 Dark
                </div>
            </div>
        </div>
    </div>

    <!-- Store Information -->
    <div class="settings-card">
        <div class="settings-header">
            <h2>📊 Store Information</h2>
        </div>
        <div class="settings-section">
            <div class="setting-item">
                <div class="setting-label">
                    <p class="setting-name">Admin Access</p>
                    <p class="setting-description">You are logged in as an administrator</p>
                </div>
                <div style="padding: 0.5rem 1rem; background: rgba(16, 185, 129, 0.1); border-radius: 8px; color: #10b981; font-weight: 600; font-size: 0.9rem;">
                    ✓ Verified
                </div>
            </div>
            <div class="setting-item">
                <div class="setting-label">
                    <p class="setting-name">Logged In User</p>
                    <p class="setting-description">Your account</p>
                </div>
                <div class="setting-value"><?= htmlspecialchars(get_logged_in_user()['name']) ?></div>
            </div>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="settings-card" style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.05), rgba(16, 185, 129, 0.05)); border-color: var(--admin-border);">
        <div class="settings-header">
            <h2>🔗 Quick Actions</h2>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem;">
            <a href="index.php" style="display: block; padding: 1rem; background: var(--admin-surface); border: 1px solid var(--admin-border); border-radius: 8px; text-align: center; text-decoration: none; color: var(--admin-accent); font-weight: 600; transition: all 0.25s ease;">
                📊 Dashboard
            </a>
            <a href="products.php" style="display: block; padding: 1rem; background: var(--admin-surface); border: 1px solid var(--admin-border); border-radius: 8px; text-align: center; text-decoration: none; color: var(--admin-accent); font-weight: 600; transition: all 0.25s ease;">
                📦 Products
            </a>
            <a href="orders.php" style="display: block; padding: 1rem; background: var(--admin-surface); border: 1px solid var(--admin-border); border-radius: 8px; text-align: center; text-decoration: none; color: var(--admin-accent); font-weight: 600; transition: all 0.25s ease;">
                🛒 Orders
            </a>
            <a href="customers.php" style="display: block; padding: 1rem; background: var(--admin-surface); border: 1px solid var(--admin-border); border-radius: 8px; text-align: center; text-decoration: none; color: var(--admin-accent); font-weight: 600; transition: all 0.25s ease;">
                👥 Customers
            </a>
            <a href="categories.php" style="display: block; padding: 1rem; background: var(--admin-surface); border: 1px solid var(--admin-border); border-radius: 8px; text-align: center; text-decoration: none; color: var(--admin-accent); font-weight: 600; transition: all 0.25s ease;">
                📂 Categories
            </a>
            <a href="reviews.php" style="display: block; padding: 1rem; background: var(--admin-surface); border: 1px solid var(--admin-border); border-radius: 8px; text-align: center; text-decoration: none; color: var(--admin-accent); font-weight: 600; transition: all 0.25s ease;">
                ⭐ Reviews
            </a>
        </div>
    </div>
</div>

<?php
$page_content = ob_get_clean();
require 'admin-master.php';
