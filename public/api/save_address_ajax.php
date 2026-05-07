<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_logged_in()) {
    $user = get_logged_in_user();
    $data = $_POST;
    
    $addressId = !empty($data['address_id']) ? (int)$data['address_id'] : null;

    if (!empty($data['phone_number']) && !is_valid_ph_phone($data['phone_number'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid phone number format.']);
        exit;
    }

    $result = save_user_address((int)$user['id'], $data, $addressId);
    if ($result['success']) {
        $_SESSION['flash_message'] = $addressId ? 'Address updated successfully!' : 'New address added successfully!'; // Still use flash for full page reloads
        echo json_encode(['success' => true, 'message' => $result['message']]);
    } else {
        echo json_encode(['success' => false, 'message' => $result['message']]);
    }
    exit;
}