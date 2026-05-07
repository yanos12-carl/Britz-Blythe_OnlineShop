<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = (int)$_POST['delete_id'];
    if (delete_user($delete_id)) {
        $message = 'User deleted successfully.';
    } else {
        $error = 'Unable to delete user. They may have existing orders or you might be trying to delete your own account.';
    }
}

$customers = get_customers();

$current_page = 'customers';
$page_title = 'Customers';
$page_description = 'View and manage registered user accounts.';

ob_start();
?>

<style>
    .customers-container {
        background: var(--admin-surface);
        border: 1px solid var(--admin-border);
        border-radius: 16px;
        overflow: hidden;
    }

    .admin-table {
        width: 100%;
        border-collapse: collapse;
    }

    .admin-table thead {
        background: var(--admin-bg);
    }

    .admin-table th {
        padding: 1rem 0.75rem;
        text-align: left;
        font-weight: 700;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--admin-text);
        opacity: 0.7;
        border-bottom: 1px solid var(--admin-border);
    }

    .admin-table td {
        padding: 1rem 0.75rem;
        border-bottom: 1px solid var(--admin-border);
        color: var(--admin-text);
    }

    .admin-table tbody tr {
        transition: all 0.25s ease;
    }

    .admin-table tbody tr:hover {
        background: rgba(99, 102, 241, 0.05);
    }

    .customer-name {
        font-weight: 700;
        color: var(--admin-text);
    }

    .customer-email {
        color: var(--admin-text);
        opacity: 0.7;
        font-size: 0.9rem;
    }

    .role-badge {
        display: inline-block;
        padding: 0.4rem 0.8rem;
        border-radius: 8px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: capitalize;
    }

    .role-admin {
        background: rgba(99, 102, 241, 0.15);
        color: var(--admin-accent);
    }

    .role-customer {
        background: rgba(16, 185, 129, 0.15);
        color: #10b981;
    }

    .btn-delete {
        padding: 0.4rem 0.8rem;
        background: rgba(239, 68, 68, 0.1);
        color: #ef4444;
        border: 1px solid rgba(239, 68, 68, 0.3);
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        font-size: 0.8rem;
        transition: all 0.25s ease;
    }

    .btn-delete:hover {
        background: rgba(239, 68, 68, 0.2);
        border-color: #ef4444;
    }

    .active-user {
        color: var(--admin-text);
        opacity: 0.6;
        font-size: 0.85rem;
        font-style: italic;
    }

    .alert-error {
        background: rgba(239, 68, 68, 0.15);
        border: 1px solid rgba(239, 68, 68, 0.3);
        color: #ef4444;
        padding: 1rem;
        border-radius: 12px;
        margin-bottom: 2rem;
    }

    .delete-form {
        display: inline;
    }

    @media (max-width: 768px) {
        .admin-table {
            font-size: 0.9rem;
        }

        .admin-table th,
        .admin-table td {
            padding: 0.75rem 0.5rem;
        }
    }
</style>

<?php if ($error): ?>
    <div class="alert-error">
        <strong>⚠️ Error:</strong> <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<?php if ($message): ?>
    <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); color: #10b981; padding: 1rem; border-radius: 12px; margin-bottom: 2rem;">
        <strong>✅ Success:</strong> <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<div class="customers-container">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th style="text-align: right;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($customers as $customer): ?>
                <tr>
                    <td class="customer-name"><?= htmlspecialchars($customer['name']) ?></td>
                    <td class="customer-email"><?= htmlspecialchars($customer['email']) ?></td>
                    <td>
                        <span class="role-badge role-<?= strtolower($customer['role']) ?>">
                            <?= htmlspecialchars($customer['role']) ?>
                        </span>
                    </td>
                    <td style="text-align: right;">
                        <?php if ((int)$customer['id'] !== (int)($_SESSION['user']['id'] ?? 0)): ?>
                            <form method="POST" class="delete-form" onsubmit="return confirm('Delete this user account? This action cannot be undone.');">
                                <input type="hidden" name="delete_id" value="<?= $customer['id'] ?>">
                                <button type="submit" class="btn-delete">🗑️ Delete</button>
                            </form>
                        <?php else: ?>
                            <span class="active-user">👤 You (Active)</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php
$page_content = ob_get_clean();
require 'admin-master.php';