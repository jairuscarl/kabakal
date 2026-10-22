<?php
/** Inbox (mail icon in the navbar): progress of the customer's orders + update messages. */
require_once __DIR__ . '/includes/init.php';

// Small JSON answer for the navbar badge, polled by assets/js/main.js
if (isset($_GET['unread'])) {
    header('Content-Type: application/json');
    header('Cache-Control: no-store');
    echo json_encode(['unread' => unread_count()]);
    exit;
}

require_login();
$cid = (int)$_SESSION['customer_id'];

$st = $pdo->prepare('SELECT * FROM notification WHERE customer_id = ? ORDER BY notification_id DESC LIMIT 50');
$st->execute([$cid]);
$messages = $st->fetchAll();
// Opening the inbox marks everything as read (the list above still shows which ones were new)
$pdo->prepare('UPDATE notification SET is_read = 1 WHERE customer_id = ? AND is_read = 0')->execute([$cid]);

// Orders still on their way: pending, paid or shipped
$orders = fetch_orders("AND o.customer_id = ? AND o.status IN ('pending', 'paid', 'shipped')", [$cid]);
$names = [];
if ($orders) {
    $ids = array_column($orders, 'order_id');
    $st = $pdo->prepare('SELECT oi.order_id, e.name, oi.quantity FROM order_item oi JOIN equipment e ON e.equipment_id = oi.equipment_id
                         WHERE oi.order_id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')');
    $st->execute($ids);
    foreach ($st->fetchAll() as $it) $names[$it['order_id']][] = $it['name'] . ($it['quantity'] > 1 ? ' ×' . (int)$it['quantity'] : '');
}
$steps = ['pending' => 'Order placed', 'paid' => 'Paid', 'shipped' => 'Shipped', 'delivered' => 'Delivered'];

$unread_msgs = unread_message_count();
$page_title = 'Inbox';
require __DIR__ . '/includes/header.php';
?>
<h1>Inbox</h1>
<nav class="tabs">
  <a href="<?= url('notifications.php') ?>" class="active">Order updates</a>
  <a href="<?= url('messages.php') ?>">Messages<?php if ($unread_msgs): ?> <span class="cart-badge"><?= $unread_msgs ?></span><?php endif; ?></a>
</nav>

<h2>Order progress</h2>
<?php if (!$orders): ?>
  <p class="empty">No orders in progress. <a href="<?= url('products.php') ?>">Browse products</a></p>
<?php else: ?>
  <div class="track-list">
    <?php foreach ($orders as $o):
        $at = array_search($o['status'], array_keys($steps), true); ?>
    <div class="track-card conv-item">
      <div class="track-head">
        <a class="conv-open" href="<?= url('order_confirm.php?id=' . (int)$o['order_id']) ?>"><strong>Order #<?= (int)$o['order_id'] ?></strong></a>
        <span class="muted"><?= e(date('M j, Y', strtotime($o['order_date']))) ?> · <?= peso($o['total_amount']) ?></span>
      </div>
      <p class="muted track-items"><?= e(implode(', ', $names[$o['order_id']] ?? [])) ?></p>
      <ol class="track-steps">
        <?php $n = 0; foreach ($steps as $label): ?>
          <li class="<?= $n < $at ? 'done' : ($n === $at ? 'current' : '') ?>"><span class="track-dot"></span><?= e($label) ?></li>
        <?php $n++; endforeach; ?>
      </ol>
      <?php if ($o['status'] === 'pending'): ?>
        <p class="help"><?= $o['pay_state'] === 'Pending Verification' ? 'We are checking your payment.' : 'Waiting for your payment.' ?>
          <a class="conv-inner" href="<?= url('order_confirm.php?id=' . (int)$o['order_id']) ?>">View order</a></p>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<h2>Updates</h2>
<?php if (!$messages): ?>
  <p class="empty">No messages yet. We will let you know here when your orders are updated.</p>
<?php else: ?>
  <ul class="inbox-list">
    <?php foreach ($messages as $m): ?>
    <li class="<?= $m['order_id'] ? 'conv-item' : '' ?><?= $m['is_read'] ? '' : ' unread' ?>">
      <div>
        <?php if (!$m['is_read']): ?><span class="inbox-new">New</span><?php endif; ?>
        <?php if ($m['order_id']): ?>
          <a class="conv-open" href="<?= url('order_confirm.php?id=' . (int)$m['order_id']) ?>"><?= e($m['message']) ?></a>
        <?php else: ?>
          <?= e($m['message']) ?>
        <?php endif; ?>
      </div>
      <time class="muted"><?= e(date('M j, Y g:i A', strtotime($m['created_at']))) ?></time>
    </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
