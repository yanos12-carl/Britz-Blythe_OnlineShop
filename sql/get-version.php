<?php
require_once __DIR__ . '/../includes/functions.php';

$db = get_db();
if ($db) {
    $version = $db->query('SELECT VERSION()')->fetchColumn();
    echo "Database Version: " . $version . PHP_EOL;
} else {
    echo "Could not connect to database." . PHP_EOL;
}