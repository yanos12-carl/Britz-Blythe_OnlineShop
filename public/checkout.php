<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$user = get_logged_in_user();
$addresses = get_user_addresses((int)$user['id']);

$defaultAddress = null;
foreach ($addresses as $a) if ($a['is_default']) { $defaultAddress = $a; break; }

$useDefault = ($_SERVER['REQUEST_METHOD'] === 'POST') ? isset($_POST['use_default']) : (bool)$defaultAddress;
$selectedAddressId = (int)($_POST['address_id'] ?? 0);
$selectedBillingId = (int)($_POST['billing_id'] ?? 0);
$sameAsShipping = isset($_POST['same_as_shipping']) || $_SERVER['REQUEST_METHOD'] !== 'POST';

if ($useDefault && $defaultAddress) {
    $selectedAddressId = (int)$defaultAddress['id'];
}

if (!$selectedAddressId && $defaultAddress) {
    $selectedAddressId = (int)$defaultAddress['id'];
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $selectedAddr = null;
    foreach($addresses as $a) if((int)$a['id'] === $selectedAddressId) $selectedAddr = $a;

    $billingAddr = null;
    if ($sameAsShipping) {
        $billingAddr = $selectedAddr;
    } else {
        foreach($addresses as $a) if((int)$a['id'] === $selectedBillingId) $billingAddr = $a;
    }

    if ($selectedAddr && $billingAddr) {
        $result = process_checkout((int)$user['id'], $selectedAddr, $billingAddr);
        if ($result['success']) {
            header('Location: order-success.php?order=' . $result['order_id']);
            exit;
        }
        $message = $result['message'];
    } else {
        $message = 'Please select a shipping address.';
    }
}
?>
<section class="page-header">
    <div>
        <h1>Checkout</h1>
        <p>Complete your order with secure payment and delivery details.</p>
    </div>
</section>
<div class="checkout-grid">
    <form id="checkout-main-form" method="post" class="checkout-form">
        <?php if ($message): ?><div class="alert alert-error"><?= htmlspecialchars($message) ?></div><?php endif; ?>
        
        <h3>Shipping Address</h3>
        <div id="shipping-address-container">
            <?php if (empty($addresses)): ?>
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
            <?php endif; ?>
        </div>

        <div style="margin-bottom: 2rem;">
            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-weight: 600;">
                <input type="checkbox" id="same_as_shipping" name="same_as_shipping" value="1" <?= $sameAsShipping ? 'checked' : '' ?> onchange="toggleBilling()">
                My Billing Address is the same as Shipping
            </label>
        </div>

        <div id="billing_section" style="display: <?= $sameAsShipping ? 'none' : 'block' ?>; margin-bottom: 2rem;">
            <h3>Billing Address</h3>
            <div id="billing-address-container" style="display: grid; gap: 1rem;">
                <?php if (!empty($addresses)): ?>
                    <?php foreach ($addresses as $addr): ?>
                        <label class="address-card <?= $selectedBillingId === (int)$addr['id'] ? 'is-default-address' : '' ?>" style="cursor: pointer; display: flex; align-items: flex-start; gap: 1rem;">
                            <input type="radio" name="billing_id" value="<?= $addr['id'] ?>" <?= $selectedBillingId === (int)$addr['id'] ? 'checked' : '' ?> style="width: auto !important; margin-top: 5px;">
                            <div>
                                <strong style="display: block;"><?= htmlspecialchars($addr['label']) ?></strong>
                                <small><?= htmlspecialchars($addr['recipient_name']) ?> — <?= htmlspecialchars($addr['address']) ?></small>
                            </div>
                        </label>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <label for="notes">Order Notes (Optional)</label>
        <div class="textarea-counter-wrapper">
            <textarea id="notes" name="notes" rows="3" maxlength="500"></textarea>
            <div id="notes-counter" class="char-counter">0 / 500</div>
        </div>

        <?php 
            $isAddressSelected = ($useDefault && $defaultAddress) || (!$useDefault && $selectedAddressId > 0);
        ?>
        <button type="submit" name="place_order" id="place-order-btn" 
                class="button button-primary" 
                style="width: 100%; <?= !$isAddressSelected ? 'opacity: 0.5; cursor: not-allowed;' : '' ?>"
                <?= !$isAddressSelected ? 'disabled' : '' ?>>
            Complete Purchase
        </button>
    </form>
    <aside class="checkout-summary">
        <h2>Ready to ship</h2>
        <p>We’ll send a confirmation email once your order is placed.</p>
    </aside>
</div>

<script>
/**
 * Orchestrates the Address Modal within Checkout
 */
async function openAddressModal(id = 0) {
    const modalOverlay = document.querySelector('.modal-overlay');
    const contentArea = document.getElementById('modal-content-area');
    
    if (!modalOverlay || !contentArea) return;

    // Show loading state
    contentArea.innerHTML = '<div class="modal-loading-spinner"><div class="spinner"></div></div>';
    modalOverlay.classList.add('is-active');

    try {
        const response = await fetch(`api/address_modal_content.php${id ? '?id=' + id : ''}`);
        const html = await response.text();
        contentArea.innerHTML = html;

        // Initialize address autocomplete inside the modal
        initModalAutocomplete();

        // Handle the AJAX form submission
        const form = document.getElementById('ajax-address-form');
        if (form) {
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                const formData = new FormData(form);
                
                try {
                    const res = await fetch('api/save_address_ajax.php', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await res.json();

                    if (result.success) {
                        await refreshCheckoutAddresses();
                        modalOverlay.classList.remove('is-active');
                    } else {
                        showToast(result.message || 'Error saving address.', 'error');
                    }
                } catch (err) {
                    console.error('Save failed:', err);
                }
            });
        }
    } catch (err) {
        contentArea.innerHTML = '<p class="alert alert-error">Failed to load form.</p>';
    }
}

/**
 * Specifically handles Google Places for the modal fields
 */
function initModalAutocomplete() {
    const addressInput = document.getElementById('address');
    if (!addressInput || typeof google === 'undefined' || !google.maps.places) return;

    const autocomplete = new google.maps.places.Autocomplete(addressInput, {
        types: ['address'],
        fields: ['address_components', 'formatted_address', 'geometry']
    });

    autocomplete.addListener('place_changed', function() {
        const place = autocomplete.getPlace();
        if (!place.geometry) return;
        updateFieldsFromComponents(place.address_components, place.formatted_address);
    });
}

/**
 * Updates the "Complete Purchase" button state based on address selection
 */
function updatePurchaseButtonState() {
    const btn = document.getElementById('place-order-btn');
    if (!btn) return;

    const useDefault = document.getElementById('use_default')?.checked;
    const hasRadioSelection = document.querySelector('input[name="address_id"]:checked') !== null;
    
    // Enabled if using primary address OR a specific address is selected from the list
    const isValid = useDefault || hasRadioSelection;

    if (isValid) {
        btn.disabled = false;
        btn.style.opacity = '1';
        btn.style.cursor = 'pointer';
    } else {
        btn.disabled = true;
        btn.style.opacity = '0.5';
        btn.style.cursor = 'not-allowed';
    }
}

/**
 * Refreshes the address selection sections via AJAX
 */
async function refreshCheckoutAddresses() {
    const shippingContainer = document.getElementById('shipping-address-container');
    const billingContainer = document.getElementById('billing-address-container');
    
    // Capture current UI state to maintain selections where possible
    const selectedId = document.querySelector('input[name="address_id"]:checked')?.value || 0;
    const selectedBillingId = document.querySelector('input[name="billing_id"]:checked')?.value || 0;
    const useDefault = document.getElementById('use_default')?.checked ? 1 : 0;

    try {
        const response = await fetch(`api/get_checkout_address_section.php?selected_id=${selectedId}&billing_id=${selectedBillingId}&use_default=${useDefault}`);
        const data = await response.json();
        
        if (data.success) {
            if (shippingContainer) shippingContainer.innerHTML = data.shipping_html;
            if (billingContainer) billingContainer.innerHTML = data.billing_html;
            updatePurchaseButtonState();
        }
    } catch (err) {
        console.error('Failed to refresh addresses:', err);
    }
}

async function deleteAddressFromModal(id) {
    if (!confirm('Are you sure you want to delete this address?')) return;
    const formData = new FormData();
    formData.append('address_id', id);
    try {
        const res = await fetch('api/delete_address_ajax.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            await refreshCheckoutAddresses();
            updatePurchaseButtonState();
            document.querySelector('.modal-overlay').classList.remove('is-active');
        } else {
            alert(data.message || 'Failed to delete address.');
        }
    } catch (err) { console.error('Delete failed:', err); }
}

function toggleAddressList() {
    const isDefault = document.getElementById('use_default').checked;
    document.getElementById('address_selection_list').style.display = isDefault ? 'none' : 'grid';
    const preview = document.getElementById('default_preview');
    if (preview) preview.style.display = isDefault ? 'block' : 'none';
    updatePurchaseButtonState();
}

function toggleBilling() {
    const isSame = document.getElementById('same_as_shipping').checked;
    document.getElementById('billing_section').style.display = isSame ? 'none' : 'block';
}

/**
 * Prevents double submission and shows loading state
 */
document.getElementById('checkout-main-form')?.addEventListener('submit', function(e) {
    const submitter = e.submitter;
    if (submitter && submitter.name === 'place_order') {
        submitter.classList.add('button-loading');
        
        // Ensure the 'place_order' value is still sent in the POST data
        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'place_order';
        hidden.value = '1';
        this.appendChild(hidden);
        
        submitter.disabled = true;
    }
});

// Listen for address selection changes to update button state
document.addEventListener('change', function(e) {
    if (e.target.name === 'address_id' || e.target.id === 'use_default') {
        updatePurchaseButtonState();
    }
});

// Order Notes Character Counter
const notesField = document.getElementById('notes');
const notesCounter = document.getElementById('notes-counter');
if (notesField && notesCounter) {
    const updateCounter = () => {
        const length = notesField.value.length;
        notesCounter.textContent = `${length} / 500`;
        notesCounter.classList.toggle('limit-reached', length >= 500);
    };
    
    notesField.addEventListener('input', updateCounter);
    updateCounter(); // Initialize on page load
}
</script>

<script src="https://maps.googleapis.com/maps/api/js?key=<?= GOOGLE_MAPS_API_KEY ?>&libraries=places&loading=async" async defer></script>
