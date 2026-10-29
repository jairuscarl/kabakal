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

// Orders still on their way: pending, paid, shipped or in transit
$orders = fetch_orders("AND o.customer_id = ? AND o.status IN ('pending', 'paid', 'shipped', 'in_transit')", [$cid]);
$names = [];
if ($orders) {
    $ids = array_column($orders, 'order_id');
    $st = $pdo->prepare('SELECT oi.order_id, e.name, oi.quantity FROM order_item oi JOIN equipment e ON e.equipment_id = oi.equipment_id
                         WHERE oi.order_id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')');
    $st->execute($ids);
    foreach ($st->fetchAll() as $it) $names[$it['order_id']][] = $it['name'] . ($it['quantity'] > 1 ? ' ×' . (int)$it['quantity'] : '');
}
$steps = ['pending' => 'Order placed', 'paid' => 'Paid', 'shipped' => 'Shipped', 'in_transit' => 'In transit', 'delivered' => 'Delivered'];
// Line icon drawn above each step's dot (24x24 outline, coloured like the step text)
$step_icons = [
    'pending'    => '<path d="M9 2h6a1 1 0 0 1 1 1v2H8V3a1 1 0 0 1 1-1z"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M9 12h6M9 16h4"/>',
    'paid'       => '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20M6 15h4"/>',
    'shipped'    => '<path d="m21 8-9-5-9 5v8l9 5 9-5z"/><path d="m3 8 9 5 9-5M12 13v8"/>',
    'in_transit' => '<path d="M1 4h13v12H1zM14 8h4l3 3v5h-7"/><circle cx="5.5" cy="18.5" r="2"/><circle cx="17.5" cy="18.5" r="2"/>',
    'delivered'  => '<path d="m3 10 9-7 9 7v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="m9 15 2 2 4-4"/>',
];

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
        $o_steps = $o['payment_method'] === 'cod' ? array_diff_key($steps, ['paid' => 1]) : $steps;   // COD is paid on delivery: no Paid step
        $at = array_search($o['status'], array_keys($o_steps), true); ?>
    <div class="track-card conv-item">
      <div class="track-head">
        <a class="conv-open" href="<?= url('order_confirm.php?id=' . (int)$o['order_id']) ?>"><strong>Order #<?= (int)$o['order_id'] ?></strong></a>
        <span class="muted"><?= e(date('M j, Y', strtotime($o['order_date']))) ?> · <?= peso($o['total_amount']) ?></span>
      </div>
      <p class="muted track-items"><?= e(implode(', ', $names[$o['order_id']] ?? [])) ?></p>
      <ol class="track-steps">
        <?php $n = 0; foreach ($o_steps as $key => $label): ?>
          <li class="<?= $n < $at ? 'done' : ($n === $at ? 'current' : '') ?>">
            <svg class="track-icon" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><?= $step_icons[$key] ?></svg>
            <span class="track-dot"></span><?= e($label) ?></li>
        <?php $n++; endforeach; ?>
      </ol>
      <?php if ($o['status'] === 'pending'): ?>
        <p class="help"><?= $o['payment_method'] === 'cod' ? 'We are preparing your order. Pay in cash when it is delivered.'
                           : ($o['pay_state'] === 'Pending Verification' ? 'We are checking your payment.' : 'Waiting for your payment.') ?>
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
