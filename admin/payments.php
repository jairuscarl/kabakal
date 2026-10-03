<?php
/** Admin > Payments: verify / reject GCash proofs and see outstanding balances. */
require_once __DIR__ . '/../includes/init.php';
require_admin();
$aid = $_SESSION['admin_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pid = (int)($_POST['payment_id'] ?? 0);
    $st = $pdo->prepare("SELECT order_id FROM payment WHERE payment_id = ? AND payment_status = 'pending'");
    $st->execute([$pid]);
    $order_id = $st->fetchColumn();

    if (!$order_id) {
        flash('error', 'That payment was already handled.');
    } elseif (($_POST['action'] ?? '') === 'verify') {
        $pdo->prepare("UPDATE payment SET payment_status = 'paid', payment_date = CURDATE(), verified_by = ? WHERE payment_id = ?")->execute([$aid, $pid]);
        refresh_order_after_payment($order_id, $aid);            // marks the order Paid when fully paid
        flash('success', 'Payment verified.');
    } elseif (($_POST['action'] ?? '') === 'reject') {
        $reason = trim($_POST['remarks'] ?? '') ?: 'Payment proof was rejected. Please send a clear receipt again.';
        $pdo->prepare("UPDATE payment SET payment_status = 'failed', verified_by = ?, remarks = ? WHERE payment_id = ?")->execute([$aid, substr($reason, 0, 255), $pid]);
        flash('success', 'Payment rejected. The customer can upload a new proof.');
    }
    // go back to where the admin came from (only allow our own two pages)
    $back = $_POST['back'] ?? '';
    redirect('admin/' . (preg_match('/^(payments\.php|order_view\.php\?id=\d+)$/', $back) ? $back : 'payments.php'));
}

$pending = $pdo->query("SELECT p.*, c.full_name FROM payment p JOIN orders o ON o.order_id = p.order_id JOIN customer c ON c.customer_id = o.customer_id
                        WHERE p.payment_status = 'pending' ORDER BY p.payment_id")->fetchAll();

$bal_filter = $_GET['pay'] ?? '';
$owing = array_filter(fetch_orders("AND o.status <> 'cancelled'"), fn($o) => $o['balance'] > 0
        && ($bal_filter === '' || ($bal_filter === 'unpaid' && $o['pay_state'] === 'Unpaid') || ($bal_filter === 'partial' && $o['pay_state'] === 'Partially Paid')));
$owing_total = array_sum(array_column($owing, 'balance'));

$page_title = 'Payments';
require __DIR__ . '/_header.php';
?>
<h1>Payments</h1>
<h2>Waiting for verification (<?= count($pending) ?>)</h2>
<div class="table-wrap"><table>
  <tr><th>Order</th><th>Customer</th><th>Amount</th><th>GCash ref.</th><th>Receipt</th><th>Sent</th><th>Action</th></tr>
  <?php foreach ($pending as $p): ?>
  <tr>
    <td><a href="<?= url('admin/order_view.php?id=' . (int)$p['order_id']) ?>">#<?= (int)$p['order_id'] ?></a></td>
    <td><?= e($p['full_name']) ?></td><td><b><?= peso($p['amount']) ?></b></td><td><?= e($p['reference_number']) ?></td>
    <td><a href="<?= e(url($p['proof_image'])) ?>" target="_blank"><img class="proof-thumb" src="<?= e(url($p['proof_image'])) ?>" alt="Receipt"></a></td>
    <td><?= e(date('M j, g:i A', strtotime($p['created_at']))) ?></td>
    <td>
      <form method="post" class="actions">
        <?= csrf_field() ?><input type="hidden" name="payment_id" value="<?= (int)$p['payment_id'] ?>">
        <input type="text" name="remarks" placeholder="Reject reason (optional)" style="width:190px">
        <button class="btn btn-sm" name="action" value="verify" type="submit">Verify</button>
        <button class="btn btn-sm btn-danger" name="action" value="reject" type="submit">Reject</button>
      </form>
    </td>
  </tr>
  <?php endforeach; ?>
  <?php if (!$pending): ?><tr><td colspan="7" class="empty">No payments waiting. All caught up!</td></tr><?php endif; ?>
</table></div>

<div class="page-head"><h2>Orders with a balance (<?= peso($owing_total) ?>)</h2>
  <form method="get" class="filter-bar" style="margin:0"><select name="pay" onchange="this.form.submit()">
    <option value="">Unpaid + partial</option><option value="unpaid" <?= $bal_filter === 'unpaid' ? 'selected' : '' ?>>Unpaid only</option><option value="partial" <?= $bal_filter === 'partial' ? 'selected' : '' ?>>Partially paid only</option></select></form></div>
<div class="table-wrap"><table>
  <tr><th>Order</th><th>Customer</th><th>Total</th><th>Paid</th><th>Balance</th><th>Payment</th><th></th></tr>
  <?php foreach ($owing as $o): ?>
  <tr><td>#<?= (int)$o['order_id'] ?></td><td><?= e($o['customer_name']) ?></td><td><?= peso($o['total_amount']) ?></td><td><?= peso($o['paid_amount']) ?></td>
      <td><b><?= peso($o['balance']) ?></b></td><td><?= badge($o['pay_state']) ?></td>
      <td><a class="btn btn-sm btn-outline" href="<?= url('admin/order_view.php?id=' . (int)$o['order_id']) ?>">Open</a></td></tr>
  <?php endforeach; ?>
  <?php if (!$owing): ?><tr><td colspan="7" class="empty">No outstanding balances.</td></tr><?php endif; ?>
</table></div>
<?php require __DIR__ . '/_footer.php'; ?>
