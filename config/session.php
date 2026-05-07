<?php

// Start the session as early as possible to avoid header issues.
// (This file must not emit/print anything.)
if (!headers_sent()) {
    // Extra safety: ensure we buffer any unexpected output before redirects.
    if (ob_get_level() === 0) {
        ob_start();
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/functions.php';



if (!function_exists('is_logged_in')) {
    function is_logged_in(): bool
    {
        return !empty($_SESSION['user']);
    }
}

if (!function_exists('require_login')) {
    function require_login(): void
    {
        if (!is_logged_in()) {
            // If output already started, clear any active buffer to avoid "headers already sent".
            if (ob_get_level() > 0) {
                @ob_end_clean();
            }
            header('Location: login.php');
            exit;
        }
    }
}


if (!function_exists('is_admin')) {
    function is_admin(): bool
    {
        if (empty($_SESSION['user'])) return false;
        
        if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin') {
            return true;
        }

        $db = get_db();
        if (!$db) return false;
        $stmt = $db->prepare('SELECT role FROM users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $_SESSION['user']['id']]);
        return $stmt->fetchColumn() === 'admin';
    }
}

if (!function_exists('require_admin')) {
    function require_admin(): void
    {
        if (!is_admin()) {
            if (ob_get_level() > 0) {
                @ob_end_clean();
            }
            header('Location: login.php');
            exit;
        }
    }
}


if (!function_exists('get_logged_in_user')) {
    function get_logged_in_user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }
}
