<?php
/**
 * includes/messages.php
 * Message box between customers and the shop owner (customer: messages.php, admin: admin/messages.php).
 * A conversation is either "General" (equipment_id NULL) or about one product.
 */

const MESSAGE_MAX = 2000;

/** Check a message text. Returns [clean_text, error]. */
function message_clean($body) {
    $body = trim((string)$body);
    if ($body === '') return [null, 'Please write a message.'];
    if (mb_strlen($body) > MESSAGE_MAX) return [null, 'Message is too long (max ' . MESSAGE_MAX . ' characters).'];
    return [$body, null];
}

/** Add a message and flag the conversation as unread for the other side. */
function message_add($conversation_id, $sender, $body, $admin_id = null) {
    global $pdo;
    $pdo->prepare('INSERT INTO message (conversation_id, sender, admin_id, body) VALUES (?, ?, ?, ?)')
        ->execute([$conversation_id, $sender, $admin_id, $body]);
    $flag = $sender === 'admin' ? 'customer_unread = 1, admin_unread = 0' : 'admin_unread = 1, customer_unread = 0';
    $pdo->prepare("UPDATE conversation SET $flag, status = 'open', last_message_at = NOW() WHERE conversation_id = ?")
        ->execute([$conversation_id]);
}

/** One conversation with its customer and product name. */
function conversation_get($id) {
    global $pdo;
    $st = $pdo->prepare('SELECT cv.*, c.full_name, c.email, c.avatar_url, e.name AS equipment_name, e.image_url
                         FROM conversation cv JOIN customer c ON c.customer_id = cv.customer_id
                         LEFT JOIN equipment e ON e.equipment_id = cv.equipment_id
                         WHERE cv.conversation_id = ?');
    $st->execute([(int)$id]);
    return $st->fetch();
}

/** Chat bubbles. $me is 'customer' or 'admin': your own messages sit on the right. */
function message_thread($conversation_id, $me) {
    global $pdo;
    $st = $pdo->prepare('SELECT * FROM message WHERE conversation_id = ? ORDER BY message_id');
    $st->execute([(int)$conversation_id]);
    $html = '<div class="chat">';
    foreach ($st->fetchAll() as $m) {
        $who = $m['sender'] === 'admin' ? 'Shop' : ($me === 'customer' ? 'You' : 'Customer');
        $html .= '<div class="chat-msg ' . ($m['sender'] === $me ? 'mine' : 'theirs') . '">'
               . '<div class="chat-bubble">' . nl2br(e($m['body'])) . '</div>'
               . '<div class="chat-meta">' . e($who) . ' · ' . e(date('M j, Y g:i A', strtotime($m['created_at']))) . '</div></div>';
    }
    return $html . '</div>';
}

/** Topic label: the product name, or "General". */
function conversation_topic($cv) {
    return $cv['equipment_id'] ? ($cv['equipment_name'] ?? 'Product') : 'General';
}
