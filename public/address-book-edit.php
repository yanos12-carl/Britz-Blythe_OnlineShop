<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

require_once __DIR__ . '/../includes/header.php';

$user = get_logged_in_user();
$userId = (int)$user['id'];

// Fetch full user data
$db = get_db();
$stmt = $db->prepare('SELECT * FROM users WHERE id = :id');
$stmt->execute([':id' => $userId]);
$fullUser = $stmt->fetch();

$addressId = (int)($_GET['id'] ?? 0);
$address = null;
if ($addressId) {
    $address = get_address_by_id($addressId, $userId);
    if (!$address) {
        header('Location: ' . ($_GET['redirect'] ?? 'address-book.php'));
        exit;
    }
}

$existingAddresses = get_user_addresses($userId);
$isFirstAddress = empty($existingAddresses);

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'delete' && $addressId) {
        if (delete_user_address($addressId, $userId)) {
            $target = $_GET['redirect'] ?? 'address-book.php';
            $_SESSION['flash_message'] = 'Address removed.';
            header('Location: ' . $target);
            exit;
        }
    }

    $data = [
        'label' => trim($_POST['label'] ?? 'Home'),
        'recipient_name' => trim($_POST['recipient_name'] ?? ''),
        'phone_number' => trim($_POST['phone_number'] ?? ''),
        'address' => trim($_POST['address'] ?? ''),
        'city' => trim($_POST['city'] ?? ''),
        'state' => trim($_POST['state'] ?? ''),
        'zip_code' => trim($_POST['zip_code'] ?? ''),
        'is_default' => !empty($_POST['is_default']) ? 1 : 0
    ];

    if (!empty($data['phone_number']) && !is_valid_ph_phone($data['phone_number'])) {
        $message = 'Invalid PH phone format.';
    } elseif (save_user_address($userId, $data, $addressId ?: null)) {
        $_SESSION['flash_message'] = $addressId ? 'Address updated!' : 'Address saved!';
        $target = $_GET['redirect'] ?? 'address-book.php';
        header('Location: ' . $target);
        exit;
    } else {
        $message = 'Save failed. Check fields.';
    }
}
?>

<main class="page-shell">
    <div class="container">
        <div class="profile-dashboard-layout">
            <aside class="profile-sidebar">
                <div class="profile-header-card" style="padding: 1.5rem; text-align: center;">
                    <strong style="display: block; font-size: 1.1rem;"><?= $address ? 'Edit Address' : 'New Address' ?></strong>
                    <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0.5rem 0 0;">Managing your delivery book</p>
                </div>
                <nav class="profile-nav">
                    <a href="<?= htmlspecialchars($_GET['redirect'] ?? 'address-book.php') ?>"><span>⬅</span> Return to Previous</a>
                </nav>
            </aside>

            <div class="profile-content">
                <section class="account-card" style="padding: 2.5rem;">
                    <div class="section-heading" style="margin-bottom: 2rem;">
                        <div>
                            <h2 style="margin: 0; font-size: 1.75rem;"><?= $address ? 'Edit Address' : 'Add New Address' ?></h2>
                            <p style="margin: 0.5rem 0 0; color: var(--text-muted); font-size: 0.95rem;">Accurate details ensure fast delivery</p>
                        </div>
                    </div>

                    <?php if ($message): ?>
                        <div class="alert alert-error fade-in-up" style="margin-bottom: 1.5rem; padding: 1rem 1.25rem; border-radius: 12px; background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.2); color: #ef4444; font-size: 0.9rem;"><?= htmlspecialchars($message) ?></div>
                    <?php endif; ?>

                    <form class="profile-form" method="post" style="gap: 1.75rem;">
                        <!-- Contact Info Section -->
                        <div style="background: var(--surface-alt); border: 1px solid var(--border); border-radius: 16px; padding: 1.5rem;">
                            <h4 style="margin: 0 0 1.25rem; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); font-weight: 700;">📇 Contact Information</h4>
                            <div class="form-row" style="margin-bottom: 1.25rem;">
                                <div class="form-group" style="margin: 0;">
                                    <label for="label" style="font-size: 0.85rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.5rem; display: block;">Address Label</label>
                                    <input id="label" name="label" type="text" value="<?= htmlspecialchars($address['label'] ?? 'Home') ?>" required placeholder="e.g. Home, Work" style="margin: 0;">
                                    <div style="display: flex; gap: 0.5rem; margin-top: 0.75rem; flex-wrap: wrap;">
                                        <button type="button" class="preset-label" style="padding: 0.4rem 1rem; border-radius: 99px; border: 1px solid var(--border-strong); background: var(--surface); color: var(--text-muted); font-size: 0.8rem; font-weight: 600; cursor: pointer; transition: all 0.2s;">🏠 Home</button>
                                        <button type="button" class="preset-label" style="padding: 0.4rem 1rem; border-radius: 99px; border: 1px solid var(--border-strong); background: var(--surface); color: var(--text-muted); font-size: 0.8rem; font-weight: 600; cursor: pointer; transition: all 0.2s;">💼 Work</button>
                                        <button type="button" class="preset-label" style="padding: 0.4rem 1rem; border-radius: 99px; border: 1px solid var(--border-strong); background: var(--surface); color: var(--text-muted); font-size: 0.8rem; font-weight: 600; cursor: pointer; transition: all 0.2s;">🏢 Office</button>
                                    </div>
                                </div>
                                <div class="form-group" style="margin: 0;">
                                    <label for="recipient_name" style="font-size: 0.85rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.5rem; display: block;">Recipient Name</label>
                                    <input id="recipient_name" name="recipient_name" type="text" value="<?= htmlspecialchars($address['recipient_name'] ?? $fullUser['name'] ?? '') ?>" required placeholder="Full name" style="margin: 0;">
                                </div>
                            </div>
                            <div class="form-group" style="margin: 0;">
                                <label for="phone_number" style="font-size: 0.85rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.5rem; display: block;">Phone Number</label>
                                <input id="phone_number" name="phone_number" type="tel" value="<?= htmlspecialchars($address['phone_number'] ?? $fullUser['phone_number'] ?? '') ?>" placeholder="09123456789" style="margin: 0; max-width: 320px;">
                            </div>
                        </div>

                        <!-- Address Details Section -->
                        <div style="background: var(--surface-alt); border: 1px solid var(--border); border-radius: 16px; padding: 1.5rem;">
                            <h4 style="margin: 0 0 1.25rem; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); font-weight: 700;">📍 Address Details</h4>
                            <div class="form-group" style="margin: 0 0 1.25rem;">
                                <label for="address" style="font-size: 0.85rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.5rem; display: block;">Street Address</label>
                                <input type="text" id="address" name="address" required placeholder="Start typing to search..." value="<?= htmlspecialchars($address['address'] ?? '') ?>" style="margin: 0;">
                                <p style="margin: 0.5rem 0 0; font-size: 0.8rem; color: var(--text-muted);">💡 Type to search with Google Places autocomplete</p>
                            </div>
                            <div class="form-row triplet" style="gap: 1rem;">
                                <div class="form-group" style="margin: 0;">
                                    <label for="city" style="font-size: 0.85rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.5rem; display: block;">City</label>
                                    <input id="city" name="city" type="text" value="<?= htmlspecialchars($address['city'] ?? '') ?>" required placeholder="City" style="margin: 0;">
                                </div>
                                <div class="form-group" style="margin: 0;">
                                    <label for="state" style="font-size: 0.85rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.5rem; display: block;">Province</label>
                                    <input id="state" name="state" type="text" value="<?= htmlspecialchars($address['state'] ?? '') ?>" required placeholder="Province" style="margin: 0;">
                                </div>
                                <div class="form-group" style="margin: 0;">
                                    <label for="zip_code" style="font-size: 0.85rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.5rem; display: block;">Zip Code</label>
                                    <input id="zip_code" name="zip_code" type="text" value="<?= htmlspecialchars($address['zip_code'] ?? '') ?>" required placeholder="Zip" style="margin: 0;">
                                </div>
                            </div>
                        </div>

                        <!-- Preferences Section -->
                        <div style="display: flex; align-items: center; gap: 0.75rem; padding: 1rem 1.25rem; background: var(--surface); border: 1px solid var(--border); border-radius: 12px;">
                            <input type="checkbox" id="is_default" name="is_default" value="1" <?= ($address['is_default'] ?? $isFirstAddress) ? 'checked' : '' ?> style="width: 20px !important; height: 20px; accent-color: var(--accent); cursor: pointer; margin: 0;">
                            <label for="is_default" style="margin: 0; font-size: 0.9rem; font-weight: 600; cursor: pointer; color: var(--text);">Set as my default shipping address</label>
                        </div>

                        <div class="profile-actions" style="margin-top: 0.5rem; padding-top: 1.5rem; border-top: 1px solid var(--border);">
                            <button type="submit" class="button button-primary" style="padding: 0.9rem 2rem; font-size: 0.9rem;">
                                <?= $address ? '💾 Update Address' : '💾 Save New Address' ?>
                            </button>
                            <a href="<?= htmlspecialchars($_GET['redirect'] ?? 'address-book.php') ?>" class="button button-ghost" style="padding: 0.9rem 2rem; font-size: 0.9rem; text-decoration: none; display: inline-flex; align-items: center;">↩ Cancel</a>
                            <?php if ($addressId): ?>
                                <a href="address-book-edit.php<?= !empty($_GET['redirect']) ? '?redirect=' . htmlspecialchars($_GET['redirect']) : '' ?>" class="button button-secondary" style="padding: 0.9rem 2rem; font-size: 0.9rem; text-decoration: none; display: inline-flex; align-items: center; background: var(--surface); border: 1px solid var(--border-strong); color: var(--text); border-radius: 12px; font-weight: 600;">+ Add New Address</a>
                                <button type="submit" name="action" value="delete" class="button button-secondary danger" onclick="return confirm('Delete this address?')" style="padding: 0.9rem 2rem; font-size: 0.9rem; background: transparent; border: 1px solid #f87171; color: #f87171; border-radius: 12px; font-weight: 600; cursor: pointer; margin-left: auto;">
                                    🗑 Delete
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </div>
</main>

<script src="https://maps.googleapis.com/maps/api/js?key=<?= GOOGLE_MAPS_API_KEY ?>&libraries=places&loading=async" async defer></script>
<script>
function viewOnMaps() {
    const addr = document.getElementById('address').value;
    if (addr) {
        window.open(`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(addr)}`, '_blank');
    }
}

function initAutocomplete() {
    if (typeof google === 'undefined' || !google.maps.places) return;
    const addressInput = document.getElementById('address');
    if (!addressInput) return;

    const autocomplete = new google.maps.places.Autocomplete(addressInput, {
        types: ['address'],
        componentRestrictions: { country: 'ph' },
        fields: ['address_components', 'formatted_address', 'geometry']
    });

    autocomplete.addListener('place_changed', () => {
        const place = autocomplete.getPlace();
        if (!place.geometry) return;
        updateFieldsFromComponents(place.address_components, place.formatted_address);
    });
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.preset-label').forEach(btn => {
        btn.onclick = () => document.getElementById('label').value = btn.textContent;
    });

    const mapsBtn = document.getElementById('view-on-maps');
    if (mapsBtn) mapsBtn.onclick = viewOnMaps;

    initAutocomplete();
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
