<?php
/**
 * CLEAN VERSION: GD Extension Diagnostic Tool
 * Run: http://localhost/ecommerce/sql/check-gd.php
 */
echo "<h1>🖼️ GD Extension Status</h1>";

if (extension_loaded('gd')) {
    echo "<div style='border: 3px solid #10b981; padding: 2rem; border-radius: 12px; background: #f0fdf4;'>";
    echo "<p style='font-size: 1.5rem; color: #059669; font-weight: bold;'>✅ GD Extension ENABLED</p>";
    
    $info = gd_info();
    echo "<table style='border-collapse: collapse; margin-top: 1rem;'>";
    echo "<tr><th style='border: 1px solid #d1d5db; padding: 0.75rem; background: #f3f4f6;'>Feature</th><th style='border: 1px solid #d1d5db; padding: 0.75rem; background: #f3f4f6;'>Status</th></tr>";
    
    $features = [
        'FreeType Support' => $info['FreeType Support'] ?? false,
        'JPEG Support' => $info['JPEG Support'] ?? false,
        'PNG Support' => $info['PNG Support'] ?? false,
        'WebP Support' => $info['WebP Support'] ?? false,
        'GD Version' => $info['GD Version'] ?? 'Unknown'
    ];
    
    foreach ($features as $name => $status) {
        $icon = $status ? '✅' : '❌';
        $color = $status ? '#059669' : '#dc2626';
        echo "<tr><td style='border: 1px solid #d1d5db; padding: 0.75rem;'>$name</td>";
        echo "<td style='border: 1px solid #d1d5db; padding: 0.75rem; color: $color;'>$icon</td></tr>";
    }
    
    echo "</table>";
    echo "<p style='margin-top: 1rem; font-size: 0.9rem; color: #6b7280;'>Ready for image processing (resize, WebP conversion).</p>";
    echo "</div>";
    
} else {
    echo "<div style='border: 3px solid #ef4444; padding: 2rem; border-radius: 12px; background: #fef2f2;'>";
    echo "<p style='font-size: 1.5rem; color: #dc2626; font-weight: bold;'>❌ GD Extension DISABLED</p>";
    echo "<ol style='color: #991b1b; margin-top: 1rem;'>";
    echo "<li>Open XAMPP Control Panel → Apache → Config → PHP (php.ini)</li>";
    echo "<li>Find <code>;extension=gd</code> → Remove semicolon</li>";
    echo "<li>Save + Restart Apache</li>";
    echo "<li>Re-run this checker</li>";
    echo "</ol>";
    echo "</div>";
}
?>

