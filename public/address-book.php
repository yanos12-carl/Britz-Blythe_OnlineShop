<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

require_once __DIR__ . '/../includes/header.php';

$user = get_logged_in_user();
$userId = (int)$user['id'];
$addresses = get_user_addresses($userId);

$flash = $_SESSION['flash_message'] ?? '';
unset($_SESSION['flash_message']);
?>

<main class="page-shell">
    <div class="container">
        <div class="profile-dashboard-layout">
            <aside class="profile-sidebar">
                <nav class="profile-nav">
                    <a href="<?= SITE_URL ?>/profile.php"><span>👤</span> Profile</a>
                    <a href="<?= SITE_URL ?>/orders.php"><span>📦</span> Orders</a>
                    <a href="<?= SITE_URL ?>/address-book.php" class="active"><span>📖</span> Address Book</a>
                    <div class="nav-divider"></div>
                    <a href="<?= SITE_URL ?>/logout.php" class="logout-item"><span>🚪</span> Sign Out</a>
                </nav>
            </aside>

            <div class="profile-content">
                <section class="account-card">
                    <div class="section-heading" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                        <div>
                            <h2>Address Book</h2>
                            <p>Manage your delivery addresses for faster checkout.</p>
                        </div>
                        <button type="button" onclick="openAddressModal()" class="button button-primary">+ Add New Address</button>
                    </div>

                    <?php if ($flash): ?>
                        <div class="alert alert-success fade-in-up"><?= htmlspecialchars($flash) ?></div>
                    <?php endif; ?>

                    <?php if (empty($addresses)): ?>
                        <div class="empty-state" style="padding: 3rem 1rem;">
                            <div class="empty-icon">📍</div>
                            <p>You haven't saved any addresses yet.</p>
                            <button type="button" onclick="openAddressModal()" class="button button-primary">Add Your First Address</button>
                        </div>
                    <?php else: ?>
                        <div style="display: grid; gap: 1rem;">
                            <?php foreach ($addresses as $addr): ?>
                                <div class="address-card <?= !empty($addr['is_default']) ? 'is-default-address' : '' ?>" style="padding: 1.5rem; border-radius: 12px; border: 1px solid var(--border); background: var(--surface); position: relative;">
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem;">
                                        <div style="flex: 1;">
                                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                                <strong style="font-size: 1.1rem;"><?= htmlspecialchars($addr['label']) ?></strong>
                                                <?php if (!empty($addr['is_default'])): ?>
                                                    <span class="status-pill" style="font-size: 0.7rem; background: var(--accent); color: #fff;">PRIMARY</span>
                                                <?php endif; ?>
                                            </div>
                                            <p style="margin: 0; color: var(--text); line-height: 1.5;">
                                                <strong><?= htmlspecialchars($addr['recipient_name']) ?></strong><br>
                                                <?= htmlspecialchars($addr['phone_number']) ?><br>
                                                <?= htmlspecialchars($addr['address']) ?>, <?= htmlspecialchars($addr['city']) ?>, <?= htmlspecialchars($addr['state']) ?> <?= htmlspecialchars($addr['zip_code']) ?>
                                            </p>
                                        </div>
                                        <div style="display: flex; flex-direction: column; gap: 0.5rem; align-items: flex-end;">
                                            <button type="button" class="text-button" style="font-size: 0.85rem;" onclick="openAddressModal(<?= (int)$addr['id'] ?>)">Edit</button>
                                            <?php if (empty($addr['is_default'])): ?>
                                                <button type="button" class="text-button" style="font-size: 0.85rem; color: var(--accent);" onclick="setPrimaryAddress(<?= (int)$addr['id'] ?>)">Set as Primary</button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </div>
    </div>
</main>

<!-- Address Management Modal -->
<div id="address-modal" class="modal-overlay">
    <div class="quickview-modal" style="max-width: 600px;">
        <div id="modal-content-area"></div>
    </div>
</div>

<script src="https://maps.googleapis.com/maps/api/js?key=<?= GOOGLE_MAPS_API_KEY ?>&libraries=places&loading=async" async defer></script>
<script>
async function setPrimaryAddress(addressId) {
    const formData = new FormData();
    formData.append('address_id', addressId);
    try {
        const response = await fetch('api/set_default_address.php', { method: 'POST', body: formData });
        const data = await response.json();
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Failed to update primary address.');
        }
    } catch (err) { console.error(err); }
}
async function openAddressModal(id = 0) {
    const modal = document.getElementById('address-modal');
    const content = document.getElementById('modal-content-area');
    modal.classList.add('is-active');
    content.innerHTML = '<div class="modal-loading-spinner"><div class="spinner"></div></div>';

    try {
        const response = await fetch(`api/address_modal_content.php${id ? '?id=' + id : ''}`);
        content.innerHTML = await response.text();
        
        initModalAutocomplete();

        const form = document.getElementById('ajax-address-form');
        form.onsubmit = async (e) => {
            e.preventDefault();
            const res = await fetch('api/save_address_ajax.php', { method: 'POST', body: new FormData(form) });
            const data = await res.json();
            if (data.success) location.reload();
            else showToast(data.message, 'error');
        };
    } catch (err) {
        content.innerHTML = '<p class="alert alert-error">Failed to load form.</p>';
    }
}

async function deleteAddressFromModal(id) {
    if (!confirm('Are you sure you want to delete this address? This action cannot be undone.')) return;
    const formData = new FormData();
    formData.append('address_id', id);
    try {
        const res = await fetch('api/delete_address_ajax.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Failed to delete address.');
        }
    } catch (err) { console.error('Delete failed:', err); }
}

function initModalAutocomplete() {
    const addressInput = document.getElementById('address');
    if (!addressInput || typeof google === 'undefined' || !google.maps.places) return;

    const autocomplete = new google.maps.places.Autocomplete(addressInput, { types: ['address'] });

    autocomplete.addListener('place_changed', function() {
        const place = autocomplete.getPlace();
        if (!place.geometry) return;
        updateFieldsFromComponents(place.address_components, place.formatted_address);
    });
}
</script>

<?php include __DIR__ . '/../includes/footer.php';
