<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user = get_logged_in_user();
$userId = (int)$user['id'];
$addresses = get_user_addresses($userId);

// Get current state from request to maintain selection UI
$selectedAddressId = (int)($_GET['selected_id'] ?? 0);
$selectedBillingId = (int)($_GET['billing_id'] ?? 0);
$useDefault = (bool)($_GET['use_default'] ?? 0);

$defaultAddress = null;
foreach ($addresses as $a) {
    if ($a['is_default']) {
        $defaultAddress = $a;
        break;
    }
}

// If useDefault is true, enforce the selected ID to be the primary one
if ($useDefault && $defaultAddress) {
    $selectedAddressId = (int)$defaultAddress['id'];
}

// Buffer Shipping HTML
ob_start();
if (empty($addresses)): ?>
    <div class="empty-state" style="padding: 2rem; border: 1px dashed var(--border);">
        <p>You have no saved addresses.</p>
        <button type="button" onclick="openAddressModal()" class="button button-primary">Add New Address</button>
    </div>
<?php else: ?>
    <?php if ($defaultAddress): ?>
        <div style="margin-bottom: 1.5rem; padding: 1.25rem; background: var(--surface-alt); border-radius: 12px; border: 1px solid var(--border);">
            <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer; font-weight: 700;">
                <input type="checkbox" id="use_default" name="use_default" value="1" <?= $useDefault ? 'checked' : '' ?> onchange="toggleAddressList()">
                Use my primary address (<?= htmlspecialchars($defaultAddress['label']) ?>)
            </label>
            <div id="default_preview" style="display: <?= $useDefault ? 'block' : 'none' ?>; margin-top: 0.75rem; font-size: 0.9rem; color: var(--text-muted); padding-left: 2rem;">
                <strong style="color: var(--text);"><?= htmlspecialchars($defaultAddress['recipient_name']) ?></strong><br>
                <?= htmlspecialchars($defaultAddress['address']) ?>, <?= htmlspecialchars($defaultAddress['city']) ?>, <?= htmlspecialchars($defaultAddress['state']) ?> <?= htmlspecialchars($defaultAddress['zip_code']) ?>
            </div>
        </div>
    <?php endif; ?>

    <div id="address_selection_list" style="display: <?= $useDefault ? 'none' : 'grid' ?>; gap: 1rem; margin-bottom: 2rem;">
        <?php foreach ($addresses as $addr): ?>
            <label class="address-card <?= $selectedAddressId === (int)$addr['id'] ? 'is-default-address' : '' ?>" style="cursor: pointer; display: flex; align-items: flex-start; gap: 1rem; position: relative;">
                <input type="radio" name="address_id" value="<?= $addr['id'] ?>" <?= $selectedAddressId === (int)$addr['id'] ? 'checked' : '' ?> onchange="this.form.submit()" style="width: auto !important; margin-top: 6px;">
                <div style="flex: 1;">
                    <strong style="display: block;"><?= htmlspecialchars($addr['label']) ?></strong>
                    <small><?= htmlspecialchars($addr['recipient_name']) ?> — <?= htmlspecialchars($addr['address']) ?></small>
                </div>
                <button type="button" onclick="event.stopPropagation(); openAddressModal(<?= (int)$addr['id'] ?>)" class="text-button" style="font-size: 0.75rem; margin-top: 4px;">Edit</button>
            </label>
        <?php endforeach; ?>
        <button type="button" onclick="openAddressModal()" class="text-button" style="font-size: 0.85rem; margin-top: 0.5rem; display: inline-block; text-align: left;">+ Add another address</button>
    </div>
<?php endif;
$shippingHtml = ob_get_clean();

// Buffer Billing HTML
ob_start();
if (!empty($addresses)): ?>
    <?php foreach ($addresses as $addr): ?>
        <label class="address-card <?= $selectedBillingId === (int)$addr['id'] ? 'is-default-address' : '' ?>" style="cursor: pointer; display: flex; align-items: flex-start; gap: 1rem;">
            <input type="radio" name="billing_id" value="<?= $addr['id'] ?>" <?= $selectedBillingId === (int)$addr['id'] ? 'checked' : '' ?> style="width: auto !important; margin-top: 5px;">
            <div>
                <strong style="display: block;"><?= htmlspecialchars($addr['label']) ?></strong>
                <small><?= htmlspecialchars($addr['recipient_name']) ?> — <?= htmlspecialchars($addr['address']) ?></small>
            </div>
        </label>
    <?php endforeach; ?>
<?php endif;
$billingHtml = ob_get_clean();

echo json_encode([
    'success' => true,
    'shipping_html' => $shippingHtml,
    'billing_html' => $billingHtml
]);