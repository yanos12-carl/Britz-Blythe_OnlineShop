<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$adminId = get_primary_admin_id();
$activeUserId = (int)($_GET['user_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'], $_POST['target_user'])) {
    send_chat_message($adminId, (int)$_POST['target_user'], $_POST['message']);
    header('Location: chat.php?user_id=' . (int)$_POST['target_user']);
    exit;
}

$chatList = get_admin_chat_list();
$messages = $activeUserId ? get_chat_messages($adminId, $activeUserId) : [];

$db = get_db();
$activeUser = null;
if ($activeUserId) {
    $stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$activeUserId]);
    $activeUser = $stmt->fetch();
}

$current_page = 'chat';
$page_title = 'Live Support';
$page_description = 'Interact with your customers in real-time.';
ob_start();
?>

<div class="admin-card" style="display: grid; grid-template-columns: 320px 1fr; gap: 0; padding: 0; height: calc(100vh - 250px); overflow: hidden;">
    <!-- Chat Sidebar -->
    <aside style="border-right: 1px solid var(--admin-border); overflow-y: auto; background: var(--admin-bg);">
        <div style="padding: 1.5rem; border-bottom: 1px solid var(--admin-border);">
            <h4 style="margin: 0;">Conversations</h4>
        </div>
        <?php if (empty($chatList)): ?>
            <div style="padding: 2rem; text-align: center; color: var(--admin-text); opacity: 0.6;">No chats found.</div>
        <?php else: ?>
            <?php foreach ($chatList as $chat): ?>
                <a href="chat.php?user_id=<?= $chat['id'] ?>" style="display: flex; align-items: center; gap: 1rem; padding: 1rem 1.5rem; text-decoration: none; border-bottom: 1px solid var(--admin-border); transition: background 0.2s; <?= (int)$chat['id'] === $activeUserId ? 'background: rgba(99, 102, 241, 0.1); border-left: 4px solid var(--admin-accent);' : '' ?>">
                    <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--admin-accent); color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; flex-shrink: 0;">
                        <?= strtoupper(substr($chat['name'], 0, 1)) ?>
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.25rem;">
                            <strong style="color: var(--admin-text); font-size: 0.9rem;"><?= htmlspecialchars($chat['name']) ?></strong>
                            <?php if ($chat['unread_count'] > 0): ?>
                                <span style="background: var(--admin-accent); color: white; font-size: 0.65rem; padding: 2px 6px; border-radius: 10px;"><?= $chat['unread_count'] ?></span>
                            <?php endif; ?>
                        </div>
                        <div style="font-size: 0.75rem; color: var(--admin-text); opacity: 0.6; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            <?= date('M d', strtotime($chat['last_message_at'])) ?>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </aside>

    <!-- Chat Window -->
    <section style="display: flex; flex-direction: column; background: var(--admin-surface);">
        <?php if (!$activeUser): ?>
            <div style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; opacity: 0.5;">
                <span style="font-size: 4rem; margin-bottom: 1rem;">💬</span>
                <h3>Select a conversation to start chatting</h3>
            </div>
        <?php else: ?>
            <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--admin-border); display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--admin-accent); color: white; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: bold;">
                        <?= strtoupper(substr($activeUser['name'], 0, 1)) ?>
                    </div>
                    <h4 style="margin: 0;"><?= htmlspecialchars($activeUser['name']) ?></h4>
                </div>
                <div style="font-size: 0.8rem; color: var(--admin-text); opacity: 0.6;"><?= htmlspecialchars($activeUser['email']) ?></div>
            </div>

            <div id="admin-chat-box" style="flex: 1; overflow-y: auto; padding: 2rem; display: flex; flex-direction: column; gap: 1rem;">
                <?php foreach ($messages as $msg): 
                    $isMe = (int)$msg['sender_id'] === (int)$adminId;
                ?>
                    <div style="max-width: 70%; padding: 0.85rem 1.15rem; border-radius: 1rem; font-size: 0.9rem; line-height: 1.5; 
                        <?= $isMe ? 'align-self: flex-end; background: var(--admin-accent); color: white; border-bottom-right-radius: 0.2rem;' : 'align-self: flex-start; background: var(--admin-bg); color: var(--admin-text); border-bottom-left-radius: 0.2rem;' ?>">
                        <?= htmlspecialchars($msg['message']) ?>
                        <div style="font-size: 0.65rem; margin-top: 0.4rem; opacity: 0.7; text-align: <?= $isMe ? 'right' : 'left' ?>;">
                            <?= date('g:i a', strtotime($msg['created_at'])) ?>
                        </div>