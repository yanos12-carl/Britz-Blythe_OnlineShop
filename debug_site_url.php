<?php
require_once __DIR__ . '/config/config.php';

echo "<h1>Configuration Debugger</h1>";
echo "<p><strong>Defined SITE_URL:</strong> <code style='background:#eee;padding:2px 5px;'>" . (defined('SITE_URL') ? SITE_URL : "NOT DEFINED") . "</code></p>";

// Calculate what the URL should likely be based on the current server environment
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$domainName = $_SERVER['HTTP_HOST'];
$scriptPath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$detectedUrl = rtrim($protocol . $domainName . $scriptPath, '/');

echo "<p><strong>Detected Environment URL:</strong> <code style='background:#eee;padding:2px 5px;'>" . $detectedUrl . "</code></p>";

if (defined('SITE_URL') && SITE_URL === $detectedUrl) {
    echo "<p style='color:green;'>✅ SITE_URL matches the detected environment.</p>";
} else {
    echo "<p style='color:red;'>❌ SITE_URL mismatch! Check your config/config.php file.</p>";
    echo "<p>Try setting it to: <code>define('SITE_URL', '$detectedUrl');</code></p>";
}

echo "<h3>Asset Test</h3>";
$testAssetPath = "public/assets/images/products/placeholder.svg";
$resolvedUrl = rtrim(SITE_URL, '/') . '/' . $testAssetPath;
echo "<p>Test Image URL: <code style='background:#eee;padding:2px 5px;'>" . $resolvedUrl . "</code></p>";

// Attempt to derive the expected filesystem path from the resolved URL and SITE_URL
$expectedFilesystemPath = str_replace(rtrim(SITE_URL, '/'), rtrim(dirname(__DIR__), '\\/'), $resolvedUrl);
$expectedFilesystemPath = str_replace('/', DIRECTORY_SEPARATOR, $expectedFilesystemPath);
echo "<p>Expected Filesystem Path: <code style='background:#eee;padding:2px 5px;'>" . $expectedFilesystemPath . "</code></p>";