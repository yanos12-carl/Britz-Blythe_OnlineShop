<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_logged_in()) {
    $user = get_logged_in_user();
    $addressId = (int)($_POST['address_id'] ?? 0);

    if ($addressId > 0 && delete_user_address($addressId, (int)$user['id'])) {
        $_SESSION['flash_message'] = 'Address removed successfully.';
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Unable to delete address.']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unauthorized.']);