<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$orderId = (int)($_GET['id'] ?? 0);
$order = get_order_by_id($orderId);

if (!$order) {
    die("Order not found.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Shipping Label - Order #<?= $orderId ?></title>
    <style>
        body { font-family: 'Inter', sans-serif; padding: 40px; background: #f4f4f4; color: #000; display: flex; justify-content: center; }
        .label-container {
            background: #fff;
            width: 450px;
            border: 3px solid #000;
            padding: 30px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header { border-bottom: 3px solid #000; padding-bottom: 15px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; }
        .order-id { font-size: 2rem; font-weight: 900; }
        .from-section { font-size: 0.75rem; margin-bottom: 25px; color: #666; text-transform: uppercase; }
        .recipient-label { font-size: 0.8rem; font-weight: 800; text-transform: uppercase; margin-bottom: 5px; color: #444; }
        .recipient-name { font-size: 1.5rem; font-weight: 800; margin-bottom: 10px; }
        .address { font-size: 1.1rem; line-height: 1.4; margin-bottom: 25px; font-weight: 500; }
        .footer { border-top: 2px dashed #000; padding-top: 15px; font-size: 0.85rem; display: flex; justify-content: space-between; font-weight: 700; }
        .tracking-placeholder { background: #000; color: #fff; padding: 10px; text-align: center; margin-bottom: 20px; letter-spacing: 5px; font-weight: bold; }
        
        @media print {
            .no-print { display: none; }
            body { padding: 0; background: none; }
            .label-container { border: 3px solid #000; box-shadow: none; width: 100%; }
        }
    </style>
</head>
<body>
    <div style="display: flex; flex-direction: column; align-items: center;">
        <div class="no-print" style="margin-bottom: 20px;">
            <button onclick="window.print()" style="padding: 10px 25px; cursor: pointer; background: #000; color: #fff; border: none; border-radius: 5px; font-weight: bold;">🖨️ Print / Save as PDF</button>
        </div>

        <div class="label-container">
            <div class="header">
                <div class="order-id">#<?= $orderId ?></div>
                <div style="text-align: right; font-size: 0.8rem; font-weight: bold;"><?= date('F d, Y', strtotime($order['created_at'])) ?></div>
            </div>
            
            <div class="from-section">
                <strong>From:</strong><br>
                <?= SITE_NAME ?> Warehouse<br>
                Logistics Hub Dept.
            </div>

            <div class="tracking-placeholder">|||| ||| ||||| || ||||</div>
            
            <div class="recipient-label">Ship To:</div>
            <div class="recipient-name"><?= htmlspecialchars($order['recipient_name'] ?? 'N/A') ?></div>
            <div class="address">
                <?= nl2br(htmlspecialchars($order['address'] ?? '')) ?><br>
                <?= htmlspecialchars($order['city'] ?? '') ?>, <?= htmlspecialchars($order['state'] ?? '') ?> <?= htmlspecialchars($order['zip_code'] ?? '') ?><br>
                TEL: <?= htmlspecialchars($order['phone_number'] ?? 'n/a') ?>
            </div>

            <div class="footer">
                <span>STANDARD GROUND</span>
                <span>FRAGILE</span>
            </div>
        </div>
    </div>
</body>
</html>