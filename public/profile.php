<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

require_once __DIR__ . '/../includes/header.php';

if (is_admin()) {
    header('Location: ../admin/index.php');
    exit;
}
$user = get_logged_in_user();
$message = '';
$securityMessage = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle Profile Update
    if (isset($_POST['update_profile'])) {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone_number'] ?? '');

    $success = update_user_details($user['id'], $name, $email, $phone);
    if ($success) {
        $_SESSION['user']['name'] = $name;
        $_SESSION['user']['email'] = $email;
        $user = get_logged_in_user();
        $message = 'Profile and primary address updated successfully.';
        $messageType = 'success';
    } else {
        $message = 'Unable to update profile. Please ensure details are valid.';
        $messageType = 'error';
    }
    } 
    
    // Handle Password Update
    if (isset($_POST['update_password'])) {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $result = update_user_password($user['id'], $current, $new);
        $securityMessage = $result['message'];
        $messageType = $result['success'] ? 'success' : 'error';
    }

    // Handle Avatar Upload
    if (isset($_POST['avatar_data']) && !empty($_POST['avatar_data'])) {
        $data = $_POST['avatar_data'];
        if (preg_match('/^data:image\/(\w+);base64,/', $data, $type)) {
            $data = substr($data, strpos($data, ',') + 1);
            $data = base64_decode($data);
            
            $uploadDir = 'assets/images/profiles/';
            if (!is_dir(__DIR__ . '/' . $uploadDir)) mkdir(__DIR__ . '/' . $uploadDir, 0755, true);
            
            $filename = 'user_' . $user['id'] . '_' . time() . '.png';
            $fullPath = __DIR__ . '/' . $uploadDir . $filename;
            
            if (file_put_contents($fullPath, $data)) {
                $optimizedPath = generate_webp(resize_image($uploadDir . $filename, 300, 300));
                update_user_image($user['id'], $optimizedPath);
                $message = 'Profile picture updated.';
            }
        }
    }
}

// Fetch full user data for "Member Since" date
$db = get_db();
$stmt = $db->prepare('SELECT registered_at AS created_at, profile_image FROM users WHERE id = :id');
$stmt->execute([':id' => $user['id']]);
$fullUser = $stmt->fetch();
$userAvatar = $fullUser['profile_image'] ?: ASSET_PATH . '/images/products/placeholder.svg';
$orders = get_user_orders($user['email']);
$addresses = get_user_addresses($user['id']);
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">

<main class="page-shell">
    <div class="container">
        <div class="profile-dashboard-layout">
            <!-- Side Navigation -->
            <aside class="profile-sidebar">
                <div class="profile-header-card">
                    <div class="profile-avatar-wrapper">
                        <img id="current-avatar" src="<?= SITE_URL . '/' . htmlspecialchars($userAvatar) ?>" alt="Avatar">
                        <label for="avatar-input" class="avatar-edit-btn">✎</label>
                        <input type="file" id="avatar-input" accept="image/*" style="display: none;">
                    </div>
                    <div class="profile-user-info">
                        <h2><?= htmlspecialchars($user['name']) ?></h2>
                        <span class="member-since">Member since <?= date('Y', strtotime($fullUser['created_at'])) ?></span>
                    </div>
                </div>

                <nav class="profile-nav">
                    <a href="#personal" class="active"><span>👤</span> Personal Details</a>
                    <a href="#security"><span>🔒</span> Security & Privacy</a>
                    <a href="#addresses"><span>📖</span> Address Book</a>
                    <a href="#orders"><span>📦</span> Order History</a>
                    <div class="nav-divider"></div>
                    <div class="profile-theme-toggle">
                        <span>🌗</span>
                        <span>Dark Mode</span>
                        <label class="toggle-switch">
                            <input type="checkbox" id="profile-theme-switch">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <div class="nav-divider"></div>
                    <a href="<?= SITE_URL ?>/logout.php" class="logout-item"><span>🚪</span> Sign Out</a>
                </nav>
            </aside>

            <!-- Main Content Area -->
            <div class="profile-content">
                <?php if ($message): ?>
                    <div class="alert <?= $messageType === 'success' ? 'alert-success' : 'alert-error' ?> fade-in-up"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>

                <!-- Personal Details Section -->
                <section id="personal" class="account-card">
                    <div class="section-title">
                        <h3>Personal Details</h3>
                        <p>Manage your identity and shipping preferences.</p>
                    </div>
                    
                    <form class="profile-form" method="post" action="<?= SITE_URL ?>/profile.php">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="name">Full Name</label>
                                <input id="name" name="name" type="text" value="<?= htmlspecialchars($user['name']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input id="email" name="email" type="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                            </div>
                        </div>

                        <div class="profile-actions">
                            <button type="submit" name="update_profile" class="button button-primary">Save Profile</button>
                        </div>
                    </form>
                </section>

                <!-- Security Section -->
                <section id="security" class="account-card" style="margin-top: 2rem;">
                    <div class="section-title">
                        <h3>Account Security</h3>
                        <p>Keep your account safe by updating your password regularly.</p>
                    </div>
                    <?php if ($securityMessage): ?>
                        <div class="alert <?= $messageType === 'success' ? 'alert-success' : 'alert-error' ?>"><?= htmlspecialchars($securityMessage) ?></div>
                    <?php endif; ?>
                    <form class="profile-form" method="post" action="<?= SITE_URL ?>/profile.php">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="current_password">Current Password</label>
                                <input id="current_password" name="current_password" type="password" required>
                            </div>
                            <div class="form-group">
                                <label for="new_password">New Password</label>
                                <input id="new_password" name="new_password" type="password" required>
                            </div>
                        </div>
                        <button type="submit" name="update_password" class="button button-secondary">Update Password</button>
                    </form>
                </section>

                <!-- Address Book Section -->
                <section id="addresses" class="account-card" style="margin-top: 2rem;">
                    <div class="section-title">
                        <div style="display: flex; justify-content: space-between; align-items: center; width: 100%; flex-wrap: wrap; gap: 1rem;">
                            <div>
                                <h3>Address Book</h3>
                                <p>Manage your saved delivery locations for faster checkout.</p>
                            </div>
                            <button type="button" onclick="openAddressModal()" class="button button-primary" style="font-size: 0.8rem; padding: 0.5rem 1rem;">+ Add New Address</button>
                        </div>
                    </div>
                    
                    <?php if (empty($addresses)): ?>
                        <div class="empty-state" style="padding: 2rem 0;">
                            <div class="empty-icon">📍</div>
                            <p>You haven't saved any addresses yet.</p>
                        </div>
                    <?php else: ?>
                        <div style="display: grid; gap: 1rem; margin-top: 1.5rem;">
                            <?php foreach ($addresses as $addr): ?>
                                <div class="address-card <?= !empty($addr['is_default']) ? 'is-default-address' : '' ?>" style="padding: 1.25rem; border-radius: 12px; border: 1px solid var(--border); background: var(--surface-alt);">
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                        <div style="flex: 1;">
                                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                                                <strong style="font-size: 1rem;"><?= htmlspecialchars($addr['label']) ?></strong>
                                                <?php if (!empty($addr['is_default'])): ?>
                                                    <span class="status-pill" style="font-size: 0.65rem; background: var(--accent); color: #fff; padding: 2px 8px;">PRIMARY</span>
                                                <?php endif; ?>
                                            </div>
                                            <p style="margin: 0; font-size: 0.9rem; color: var(--text-muted); line-height: 1.5;">
                                                <strong><?= htmlspecialchars($addr['recipient_name']) ?></strong><br>
                                                <?= htmlspecialchars($addr['address']) ?>, <?= htmlspecialchars($addr['city']) ?>, <?= htmlspecialchars($addr['state']) ?> <?= htmlspecialchars($addr['zip_code']) ?>
                                            </p>
                                        </div>
                                        <div style="display: flex; flex-direction: column; gap: 0.5rem; align-items: flex-end;">
                                            <button type="button" class="text-button" style="font-size: 0.8rem;" onclick="openAddressModal(<?= (int)$addr['id'] ?>)">Edit</button>
                                            <?php if (empty($addr['is_default'])): ?>
                                                <button type="button" class="text-button" style="font-size: 0.8rem;" onclick="setPrimaryAddress(<?= (int)$addr['id'] ?>)">Set Default</button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>

                <!-- Order History Section -->
                <section id="orders" class="account-card" style="margin-top: 2rem;">
                    <div class="section-title">
                        <h3>Order History</h3>
                        <p>Track your recent purchases and their current status.</p>
                    </div>
                    <?php if (empty($orders)): ?>
                        <div class="empty-state">
                            <div class="empty-icon">🛍️</div>
                            <p>You haven't placed any orders yet.</p>
                            <a class="button button-ghost" href="<?= SITE_URL ?>/products.php">Start Shopping</a>
                        </div>
                    <?php else: ?>
                        <div class="order-list" style="display: grid; gap: 1.5rem;">
                            <?php 
                            $statusSteps = ['Placed', 'Processing', 'Shipped', 'Delivered'];
                            foreach ($orders as $order): 
                                $stage = get_order_status_stage($order['status']);
                            ?>
                                <div class="order-history-card" style="background: var(--surface); border: 1px solid var(--border); border-radius: 1.5rem; padding: 1.5rem; box-shadow: var(--shadow-sm);">
                                    <div class="order-item-row" style="padding: 0; border: none; margin-bottom: 1.25rem;">
                                    <div class="order-main">
                                        <span class="order-id">#<?= htmlspecialchars($order['id']) ?></span>
                                        <span class="order-date"><?= date('M d, Y', strtotime($order['created_at'])) ?></span>
                                        <a href="<?= SITE_URL ?>/order-view.php?id=<?= $order['id'] ?>" class="text-button" style="font-size: 0.8rem; margin-left: 0.5rem;">View Details</a>
                                    </div>
                                    <div class="order-pricing">
                                        <span class="order-total"><?= format_price((float)$order['total']) ?></span>
                                        <span class="status-pill status-<?= strtolower($order['status']) ?>"><?= htmlspecialchars($order['status']) ?></span>
                                        <?php if (strtolower($order['status']) === 'delivered' || strtolower($order['status']) === 'completed'): ?>
                                            <button type="button" class="button button-secondary buy-again-btn" onclick="buyAgain(<?= (int)$order['id'] ?>, event)" style="margin-left: 1rem;">Buy it Again</button>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                    <div class="order-tracker">
                                        <?php foreach ($statusSteps as $index => $step): ?>
                                            <div class="order-step<?= $index <= $stage ? ' active' : '' ?>">
                                                <?= htmlspecialchars($step) ?>
                                            </div>
                                        <?php endforeach; ?>
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

<form id="avatar-form" method="post" action="<?= SITE_URL ?>/profile.php">
    <input type="hidden" name="avatar_data" id="avatar_data">
</form>

<!-- Cropping Modal -->
<div id="cropper-modal" class="modal-overlay">
    <div class="quickview-modal" style="max-width: 500px;">
        <h3>Crop Profile Picture</h3>
        <div style="max-height: 400px; margin: 1.5rem 0;">
            <img id="cropper-image" style="max-width: 100%;">
        </div>
        <div style="display: flex; gap: 1rem;">
            <button type="button" class="button button-primary" id="save-crop">Apply Crop</button>
            <button type="button" class="button button-secondary" onclick="document.getElementById('cropper-modal').classList.remove('is-active')">Cancel</button>
        </div>
    </div>
</div>

<!-- Address Management Modal -->
<div id="address-modal" class="modal-overlay">
    <div class="quickview-modal" style="max-width: 600px;">
        <div id="modal-content-area"></div>
    </div>
</div>

<script src="https://maps.googleapis.com/maps/api/js?key=<?= GOOGLE_MAPS_API_KEY ?>&libraries=places,marker,geometry&loading=async" async defer></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
function showToast(message, type = 'success') {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container';
        document.body.appendChild(container);
    }
    const toast = document.createElement('div');
    toast.className = `toast ${type}`; // Add type for styling (success/error)
    toast.innerHTML = `<span>${message}</span><div class="toast-progress"></div>`; // Add progress bar
    container.appendChild(toast);
    
    const progressBar = toast.querySelector('.toast-progress');
    // Set animation duration dynamically based on toast display time
    const displayTime = 4000; // 4 seconds, matches setTimeout
    progressBar.style.animationDuration = `${displayTime / 1000}s`;

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(20px)';
        setTimeout(() => toast.remove(), 300);
    }, displayTime);
}

async function setPrimaryAddress(addressId) {
    const formData = new FormData();
    formData.append('address_id', addressId);
    try {
        const response = await fetch('api/set_default_address.php', { method: 'POST', body: formData });
        const data = await response.json();
        if (data.success) {
            location.reload(); // Refresh to update the UI badges
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
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.classList.add('button-loading');
            
            const res = await fetch('api/save_address_ajax.php', { method: 'POST', body: new FormData(form) });
            const data = await res.json();
            if (data.success) location.reload();
            else showToast(data.message, 'error');
            submitBtn.classList.remove('button-loading');
        });
    } catch (err) {
        showToast('Failed to load the address form.', 'error');
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
            showToast(data.message || 'Failed to delete address.', 'error');
        }
    } catch (err) { console.error('Delete failed:', err); }
}

async function buyAgain(orderId, event) {
    if (!confirm('Are you sure you want to add all items from this order to your cart?')) {
        return;
    }

    const buyAgainBtn = event.currentTarget; // Get the clicked button
    const originalText = buyAgainBtn.textContent;
    buyAgainBtn.textContent = 'Adding...';
    buyAgainBtn.disabled = true;
    buyAgainBtn.classList.add('button-loading');

    try {
        // 1. Fetch order items
        const response = await fetch(`api/get_order_items_ajax.php?order_id=${orderId}`);
        const data = await response.json();

        if (!data.success) {
            showToast(data.message || 'Failed to retrieve order items.', 'error');
            return;
        }

        if (data.items.length === 0) {
            showToast('No items found in this order to re-purchase.', 'error');
            return;
        }

        let allAdded = true;
        let messages = [];

        // 2. Add each item to cart
        for (const item of data.items) {
            const formData = new FormData();
            formData.append('slug', item.slug);
            formData.append('quantity', item.quantity);

            const addResponse = await fetch('api/add-to-cart.php', {
                method: 'POST',
                body: formData
            });
            const addData = await addResponse.json();

            if (!addData.success) {
                allAdded = false;
                messages.push(`Failed to add "${item.name}": ${addData.message}`);
            }
        }

        // 3. Provide feedback and update UI
        if (allAdded) {
            showToast('All items from order #' + orderId + ' added to cart!');
        } else {
            showToast('Some items could not be added. Check your cart.', 'error');
        }

        // Update cart count in navbar
        const cartCountElement = document.querySelector('.cart-count');
        if (cartCountElement) {
            const cartResponse = await fetch('api/get-cart.php'); // Assuming this returns cart data
            const cartData = await cartResponse.json();
            cartCountElement.textContent = cartData.items.reduce((sum, item) => sum + item.quantity, 0);
        }

    } catch (error) {
        console.error('Error during buy again:', error);
        alert('An unexpected error occurred while processing your request.');
    } finally {
        buyAgainBtn.textContent = originalText;
        buyAgainBtn.disabled = false;
        buyAgainBtn.classList.remove('button-loading');
    }
}

async function initModalAutocomplete() {
    const addressInput = document.getElementById('address');
    const mapDiv = document.getElementById('address-map');
    if (!addressInput || !mapDiv) return;

    const coords = { lat: mapDiv.dataset.lat, lng: mapDiv.dataset.lng };
    await initBritzBlytheMap(mapDiv, coords, addressInput);
}
</script>
<script>
let autocomplete;

document.addEventListener('DOMContentLoaded', function() {
    const avatarInput = document.getElementById('avatar-input');
    const cropperModal = document.getElementById('cropper-modal');
    const cropperImage = document.getElementById('cropper-image');
    let cropper;

    avatarInput.addEventListener('change', function(e) {
        const files = e.target.files;
        if (files && files.length > 0) {
            const reader = new FileReader();
            reader.onload = function(event) {
                cropperImage.src = event.target.result;
                cropperModal.classList.add('is-active');
                if (cropper) cropper.destroy();
                cropper = new Cropper(cropperImage, {
                    aspectRatio: 1,
                    viewMode: 1,
                    guides: false,
                });
            };
            reader.readAsDataURL(files[0]);
        }
    });

    document.getElementById('save-crop').addEventListener('click', function() {
        const canvas = cropper.getCroppedCanvas({
            width: 300,
            height: 300
        });
        
        document.getElementById('avatar_data').value = canvas.toDataURL('image/png');
        document.getElementById('avatar-form').submit();
    });

    // Tab switching logic for profile sections
    const navLinks = document.querySelectorAll('.profile-nav a[href^="#"]');
    const sections = document.querySelectorAll('.profile-content > section.account-card');

    function switchTab(targetId) {
        sections.forEach(section => {
            section.classList.toggle('is-active', section.id === targetId);
        });

        navLinks.forEach(link => {
            link.classList.toggle('active', link.getAttribute('href') === '#' + targetId);
        });
    }

    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href').substring(1);
            switchTab(targetId);
            history.replaceState(null, null, '#' + targetId);
        });
    });

    // Initialize view based on URL hash or default to 'personal'
    const initialTab = window.location.hash.substring(1) || 'personal';
    switchTab(initialTab);

    // Profile Theme Toggle
    const themeSwitch = document.getElementById('profile-theme-switch');
    if (themeSwitch) {
        const html = document.documentElement;
        const saved = localStorage.getItem('britz-theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        const currentTheme = html.dataset.theme || saved || (prefersDark ? 'dark' : 'light');

        // Set initial toggle state
        themeSwitch.checked = currentTheme === 'dark';

        themeSwitch.addEventListener('change', function() {
            const next = this.checked ? 'dark' : 'light';
            html.dataset.theme = next;
            localStorage.setItem('britz-theme', next);

            // Sync with navbar toggle if it exists
            const navToggle = document.querySelector('[data-theme-toggle]');
            if (navToggle) {
                navToggle.innerHTML = next === 'dark' ? '☀️' : '🌙';
            }
        });
    }
});
</script>
<?php include __DIR__ . '/../includes/footer.php';
