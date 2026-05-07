<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

$user = get_logged_in_user();

$addressId = (int)($_GET['id'] ?? 0);
$address = null;
if ($addressId) {
    $address = get_address_by_id($addressId, (int)$user['id']);
}

?>
<div style="padding: 0;">
    <div style="margin-bottom: 1.5rem;">
        <h3 style="margin: 0; font-size: 1.4rem; font-weight: 800; background: var(--accent-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;"><?= $address ? 'Edit Address' : 'Add New Address' ?></h3>
        <p style="margin: 0.4rem 0 0; color: var(--text-muted); font-size: 0.9rem;"><?= $address ? 'Update your delivery details below.' : 'Enter your delivery details below.' ?></p>
    </div>

    <form class="profile-form" id="ajax-address-form" style="gap: 1.25rem;">
        <?php if ($addressId): ?>
            <input type="hidden" name="address_id" value="<?= $addressId ?>">
        <?php endif; ?>

        <!-- Contact Info -->
        <div style="background: var(--surface-alt); border: 1px solid var(--border); border-radius: 14px; padding: 1.25rem;">
            <h4 style="margin: 0 0 1rem; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); font-weight: 700;">📇 Contact</h4>
            <div class="form-row" style="margin-bottom: 1rem; gap: 1rem;">
                <div class="form-group" style="margin: 0;">
                    <label for="modal_label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.4rem; display: block;">Label</label>
                    <input id="modal_label" name="label" type="text" value="<?= htmlspecialchars($address['label'] ?? 'Home') ?>" required style="margin: 0; padding: 0.7rem 0.9rem !important;">
                </div>
                <div class="form-group" style="margin: 0;">
                    <label for="modal_recipient" style="font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.4rem; display: block;">Recipient</label>
                    <input id="modal_recipient" name="recipient_name" type="text" value="<?= htmlspecialchars($address['recipient_name'] ?? $user['name']) ?>" required style="margin: 0; padding: 0.7rem 0.9rem !important;">
                </div>
            </div>
            <div class="form-group" style="margin: 0;">
                <label for="modal_phone" style="font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.4rem; display: block;">Phone</label>
                <input id="modal_phone" name="phone_number" type="tel" value="<?= htmlspecialchars($address['phone_number'] ?? '') ?>" placeholder="09XX XXX XXXX" required style="margin: 0; max-width: 260px; padding: 0.7rem 0.9rem !important;">
            </div>
        </div>

        <!-- Address Details -->
        <div style="background: var(--surface-alt); border: 1px solid var(--border); border-radius: 14px; padding: 1.25rem;">
            <h4 style="margin: 0 0 1rem; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); font-weight: 700;">📍 Address</h4>
            <div class="form-group" style="margin: 0 0 1rem;">
                <label for="address" style="font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.4rem; display: block;">Street Address</label>
                <input type="text" id="address" name="address" required placeholder="Type to search..." value="<?= htmlspecialchars($address['address'] ?? '') ?>" style="margin: 0; padding: 0.7rem 0.9rem !important;">
                <p style="margin: 0.4rem 0 0; font-size: 0.75rem; color: var(--text-muted);">💡 Google Places autocomplete enabled</p>
            </div>
            <div class="form-row triplet" style="gap: 0.75rem;">
                <div class="form-group" style="margin: 0;">
                    <label style="font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.4rem; display: block;">City</label>
                    <input id="city" name="city" type="text" value="<?= htmlspecialchars($address['city'] ?? '') ?>" required style="margin: 0; padding: 0.7rem 0.9rem !important;">
                </div>
                <div class="form-group" style="margin: 0;">
                    <label style="font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.4rem; display: block;">State</label>
                    <input id="state" name="state" type="text" value="<?= htmlspecialchars($address['state'] ?? '') ?>" required style="margin: 0; padding: 0.7rem 0.9rem !important;">
                </div>
                <div class="form-group" style="margin: 0;">
                    <label style="font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.4rem; display: block;">Zip</label>
                    <input id="zip_code" name="zip_code" type="text" value="<?= htmlspecialchars($address['zip_code'] ?? '') ?>" required style="margin: 0; padding: 0.7rem 0.9rem !important;">
                </div>
            </div>
        </div>

        <!-- Default Checkbox -->
        <div style="display: flex; align-items: center; gap: 0.6rem; padding: 0.875rem 1rem; background: var(--surface); border: 1px solid var(--border); border-radius: 10px;">
            <input type="checkbox" id="modal_is_default" name="is_default" value="1" <?= (!empty($address['is_default'])) ? 'checked' : '' ?> style="width: 18px !important; height: 18px; accent-color: var(--accent); cursor: pointer; margin: 0;">
            <label for="modal_is_default" style="margin: 0; font-size: 0.85rem; font-weight: 600; cursor: pointer; color: var(--text);">Set as my primary address</label>
        </div>

        <!-- Actions -->
        <div class="profile-actions" style="margin-top: 0.5rem; padding-top: 1.25rem; border-top: 1px solid var(--border); display: flex; flex-wrap: wrap; gap: 0.6rem;">
            <button type="submit" class="button button-primary" style="padding: 0.8rem 1.5rem; font-size: 0.85rem;"><?= $address ? '💾 Update' : '💾 Save' ?></button>
            <?php if ($addressId): ?>
                <button type="button" class="button button-secondary danger" onclick="deleteAddressFromModal(<?= $addressId ?>)" style="padding: 0.8rem 1.5rem; font-size: 0.85rem; background: transparent; border: 1px solid #f87171; color: #f87171; border-radius: 12px; font-weight: 600; cursor: pointer;">🗑 Delete</button>
            <?php endif; ?>
            <button type="button" class="button button-ghost" onclick="this.closest('.modal-overlay').classList.remove('is-active')" style="padding: 0.8rem 1.5rem; font-size: 0.85rem; margin-left: auto;">Cancel</button>
        </div>
    </form>
</div>


