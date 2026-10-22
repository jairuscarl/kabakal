<?php
/** Messages: customer <-> shop owner. List + new message, or one conversation (?id=). */
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/messages.php';
require_login();
$cid = (int)$_SESSION['customer_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    [$body, $err] = message_clean($_POST['body'] ?? '');

    if ($action === 'new') {
        $eid = (int)($_POST['equipment_id'] ?? 0);
        $product = null;
        if ($eid) {
            $st = $pdo->prepare("SELECT equipment_id, name FROM equipment WHERE equipment_id = ? AND status = 'active'");
            $st->execute([$eid]);
            $product = $st->fetch();
            if (!$product && !$err) $err = 'Please choose a product from the list.';
        }
        $subject = trim($_POST['subject'] ?? '') ?: ($product ? 'Question about ' . $product['name'] : 'General question');
        if (!$err && mb_strlen($subject) > 150) $err = 'Subject is too long (max 150 characters).';
        if ($err) { flash('error', $err); redirect('messages.php' . ($eid ? "?product=$eid" : '')); }

        $pdo->prepare('INSERT INTO conversation (customer_id, equipment_id, subject) VALUES (?, ?, ?)')
            ->execute([$cid, $product['equipment_id'] ?? null, $subject]);
        $id = (int)$pdo->lastInsertId();
        message_add($id, 'customer', $body);
        flash('success', 'Message sent! The shop will reply here.');
        redirect("messages.php?id=$id");
    }

    if ($action === 'reply') {
        $id = (int)($_POST['conversation_id'] ?? 0);
        $cv = conversation_get($id);
        if (!$cv || (int)$cv['customer_id'] !== $cid) redirect('messages.php');
        if ($err) flash('error', $err); else message_add($id, 'customer', $body);
        redirect("messages.php?id=$id");
    }
    redirect('messages.php');
}

$page_title = 'Messages';

// ---------- One conversation ----------
if (isset($_GET['id'])) {
    $cv = conversation_get($_GET['id']);
    if (!$cv || (int)$cv['customer_id'] !== $cid) { flash('error', 'That conversation could not be found.'); redirect('messages.php'); }
    $pdo->prepare('UPDATE conversation SET customer_unread = 0 WHERE conversation_id = ?')->execute([$cv['conversation_id']]);
    require __DIR__ . '/includes/header.php';
?>
<p><a href="<?= url('messages.php') ?>">&larr; All messages</a></p>
<div class="chat-head">
  <?php if ($cv['equipment_id']): ?><img class="thumb" src="<?= e(product_image($cv['image_url'])) ?>" alt=""><?php endif; ?>
  <div>
    <h1><?= e($cv['subject']) ?></h1>
    <span class="muted">Topic:
      <?php if ($cv['equipment_id']): ?><a href="<?= url('product.php?id=' . (int)$cv['equipment_id']) ?>"><?= e(conversation_topic($cv)) ?></a>
      <?php else: ?>General<?php endif; ?></span>
  </div>
</div>
<?= message_thread($cv['conversation_id'], 'customer') ?>
<form method="post" class="chat-reply">
  <?= csrf_field() ?><input type="hidden" name="action" value="reply"><input type="hidden" name="conversation_id" value="<?= (int)$cv['conversation_id'] ?>">
  <textarea name="body" maxlength="<?= MESSAGE_MAX ?>" placeholder="Write a reply..." required></textarea>
  <button class="btn" type="submit">Send</button>
</form>
<?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

// ---------- List + new message ----------
$st = $pdo->prepare('SELECT cv.*, e.name AS equipment_name,
                            (SELECT body FROM message m WHERE m.conversation_id = cv.conversation_id ORDER BY message_id DESC LIMIT 1) AS last_body
                     FROM conversation cv LEFT JOIN equipment e ON e.equipment_id = cv.equipment_id
                     WHERE cv.customer_id = ? ORDER BY cv.last_message_at DESC');
$st->execute([$cid]);
$convs = $st->fetchAll();
$products = $pdo->query("SELECT equipment_id, name FROM equipment WHERE status = 'active' ORDER BY name")->fetchAll();
$pick = (int)($_GET['product'] ?? 0);
$unread_updates = unread_notification_count();

require __DIR__ . '/includes/header.php';
?>
<h1>Inbox</h1>
<nav class="tabs">
  <a href="<?= url('notifications.php') ?>">Order updates<?php if ($unread_updates): ?> <span class="cart-badge"><?= $unread_updates ?></span><?php endif; ?></a>
  <a href="<?= url('messages.php') ?>" class="active">Messages</a>
</nav>

<div class="msg-layout">
  <div>
    <h2>Your conversations</h2>
    <?php if (!$convs): ?>
      <p class="empty">No messages yet. Ask us anything using the form.</p>
    <?php else: ?>
      <ul class="inbox-list">
        <?php foreach ($convs as $c): ?>
        <li class="conv-item<?= $c['customer_unread'] ? ' unread' : '' ?>">
          <div class="conv-line">
            <?php if ($c['customer_unread']): ?><span class="inbox-new">New reply</span><?php endif; ?>
            <a class="conv-open" href="<?= url('messages.php?id=' . (int)$c['conversation_id']) ?>"><strong><?= e($c['subject']) ?></strong></a>
            <div class="muted conv-preview"><?= e(conversation_topic($c)) ?> · <?= e(mb_strimwidth((string)$c['last_body'], 0, 90, '…')) ?></div>
          </div>
          <time class="muted"><?= e(date('M j, g:i A', strtotime($c['last_message_at']))) ?></time>
        </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>

  <form method="post" class="form-box msg-new">
    <h2>Message the shop</h2>
    <?= csrf_field() ?><input type="hidden" name="action" value="new">
    <div class="form-group"><label for="topic">About</label>
      <select id="topic" name="equipment_id">
        <option value="0">General question</option>
        <?php foreach ($products as $p): ?>
          <option value="<?= (int)$p['equipment_id'] ?>" <?= $pick === (int)$p['equipment_id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
        <?php endforeach; ?>
      </select></div>
    <div class="form-group"><label for="subject">Subject <span class="muted">(optional)</span></label>
      <input type="text" id="subject" name="subject" maxlength="150" placeholder="e.g. Is this available in black?"></div>
    <div class="form-group"><label for="body">Message</label>
      <textarea id="body" name="body" maxlength="<?= MESSAGE_MAX ?>" required></textarea></div>
    <button class="btn btn-block" type="submit">Send message</button>
  </form>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
