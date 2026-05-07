<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';
header('Content-Type: application/json');
$term = sanitize($_GET['q'] ?? '');
$results = [];
if ($term !== '') {
    $results = array_values(search_products($term));
}
echo json_encode(['query' => $term, 'results' => $results]);
