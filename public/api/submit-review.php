<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';
header('Content-Type: application/json');
$name = sanitize($_POST['name'] ?? '');
$message = sanitize($_POST['message'] ?? '');
if ($name && $message) {
    echo json_encode(['success' => true, 'message' => 'Thank you for your review.']);
    exit;
}
echo json_encode(['success' => false, 'message' => 'Please provide both name and review.']);
