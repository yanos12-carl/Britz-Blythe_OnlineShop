<?php

if (!function_exists('register_user')) {
    function register_user(string $name, string $email, string $password): bool
{
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || trim($name) === '' || strlen($password) < 6) {
        return false;
    }

    $db = get_db();
    if (!$db) {
        return false;
    }

    $stmt = $db->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    if ($stmt->fetch()) {
        return false;
    }

    $stmt = $db->prepare(
        'INSERT INTO users (name, email, password) VALUES (:name, :email, :password)'
    );

    return $stmt->execute([
        ':name' => sanitize($name),
        ':email' => $email,
        ':password' => password_hash($password, PASSWORD_DEFAULT),
    ]);
    }
}

if (!function_exists('login_user')) {
function login_user(string $email, string $password): bool
{
    $db = get_db();
    if (!$db) {
        return false;
    }

$stmt = $db->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($password, $user['password'])) {
        return false;
    }

    $_SESSION['user'] = [
        'id' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role'] ?? 'user',
    ];
    return true;
}
}

if (!function_exists('logout_user')) {
    function logout_user(): void
    {
        unset($_SESSION['user']);
    }
}

if (!function_exists('is_logged_in')) {
    function is_logged_in(): bool
    {
        return isset($_SESSION['user']['id']);
    }
}

if (!function_exists('require_login')) {
    function require_login(): void
    {
        if (!is_logged_in()) {
            header('Location: ' . SITE_URL . '/public/login.php');
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

if (!function_exists('is_admin')) {
    function is_admin(): bool
    {
        if (!is_logged_in()) {
            return false;
        }
        
        $user = get_logged_in_user();
        if (isset($user['role']) && $user['role'] === 'admin') {
            return true;
        }

        $db = get_db();
        if (!$db) return false;
    
        $stmt = $db->prepare('SELECT role FROM users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $user['id']]);
        $role = $stmt->fetchColumn();
        
        return $role === 'admin';
    }
}
