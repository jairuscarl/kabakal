<?php
/** Admin > Customer details + order history. */
require_once __DIR__ . '/../includes/init.php';
require_admin();

$st = $pdo->prepare('SELECT * FROM customer WHERE customer_id = ?'); $st->execute([(int)($_GET['id'] ?? 0)]);
$c = $st->fetch();
if (!$c) { flash('error', 'Customer not found.'); redirect('admin/customers.php'); }

$orders = fetch_orders('AND o.customer_id = ?', [$c['customer_id']]);
$spent = 0; $owed = 0;
foreach ($orders as $o) { if ($o['status'] !== 'cancelled') { $spent += $o['paid_amount']; $owed += $o['balance']; } }

$page_title = $c['full_name'];
require __DIR__ . '/_header.php';
?>
<div class="page-head"><h1><?= e($c['full_name']) ?></h1><a href="<?= url('admin/customers.php') ?>">&larr; All customers</a></div>
<div class="stats">
  <div class="stat"><b><?= count($orders) ?></b><span>Orders</span></div>
  <div class="stat"><b><?= peso($spent) ?></b><span>Total paid</span></div>
  <div class="stat"><b><?= peso($owed) ?></b><span>Balance owed</span></div>
</div>
<div class="summary" style="margin-bottom:24px">
  <div class="row"><span>Email</span><span><?= e($c['email']) ?></span></div>
  <div class="row"><span>Phone</span><span><?= e($c['phone'] ?: '-') ?></span></div>
  <div class="row"><span>Address</span><span><?= e($c['address'] ?: '-') ?></span></div>
  <div class="row"><span>Joined</span><span><?= e(date('M j, Y', strtotime($c['created_at']))) ?></span></div>
</div>
<h2>Order history</h2>
<div class="table-wrap"><table>
  <tr><th>Order</th><th>Date</th><th>Total</th><th>Balance</th><th>Status</th><th>Payment</th><th></th></tr>
  <?php foreach ($orders as $o): ?>
  <tr><td>#<?= (int)$o['order_id'] ?></td><td><?= e($o['order_date']) ?></td><td><?= peso($o['total_amount']) ?></td>
      <td><?= $o['status'] === 'cancelled' ? '-' : peso($o['balance']) ?></td><td><?= badge($o['status']) ?></td><td><?= badge($o['pay_state']) ?></td>
      <td><a class="btn btn-sm btn-outline" href="<?= url('admin/order_view.php?id=' . (int)$o['order_id']) ?>">Open</a></td></tr>
  <?php endforeach; ?>
  <?php if (!$orders): ?><tr><td colspan="7" class="empty">This customer has no orders yet.</td></tr><?php endif; ?>
</table></div>
<?php require __DIR__ . '/_footer.php'; ?>
