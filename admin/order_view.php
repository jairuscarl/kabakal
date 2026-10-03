<?php
/** Admin > Order details: items, customer, payments, update status, record a payment. */
require_once __DIR__ . '/../includes/init.php';
require_admin();
$aid = $_SESSION['admin_id'];
$order_id = (int)($_GET['id'] ?? $_POST['order_id'] ?? 0);

$rows = fetch_orders('AND o.order_id = ?', [$order_id]);
if (!$rows) { flash('error', 'Order not found.'); redirect('admin/orders.php'); }
$order = $rows[0];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'status') {
        $new = $_POST['status'] ?? '';
        if (!in_array($new, ['pending', 'paid', 'shipped', 'delivered', 'cancelled'], true)) flash('error', 'Invalid status.');
        elseif (set_order_status($order_id, $new, $aid)) flash('success', 'Order status updated.');
        else flash('error', 'A cancelled order cannot be reopened.');

    } elseif ($action === 'record_payment') {
        // Admin records a payment received in person or confirmed outside the site (counts as verified)
        $pending_amt = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM payment WHERE order_id = $order_id AND payment_status = 'pending'")->fetchColumn();
        $room   = round($order['total_amount'] - $order['paid_amount'] - $pending_amt, 2);
        $amount = round((float)($_POST['amount'] ?? 0), 2);
        $method = in_array($_POST['payment_method'] ?? '', ['cash', 'gcash', 'bank_transfer'], true) ? $_POST['payment_method'] : 'cash';
        $ref    = trim($_POST['reference_number'] ?? '');
        if ($order['status'] === 'cancelled')  flash('error', 'Cannot record a payment on a cancelled order.');
        elseif ($amount <= 0 || $amount > $room) flash('error', 'Amount must be more than 0 and not more than ' . peso($room) . '.');
        else {
            $pdo->prepare("INSERT INTO payment (order_id, verified_by, payment_method, reference_number, amount, payment_status, payment_date)
                           VALUES (?, ?, ?, ?, ?, 'paid', CURDATE())")->execute([$order_id, $aid, $method, $ref ?: null, $amount]);
            refresh_order_after_payment($order_id, $aid);
            flash('success', 'Payment recorded.');
        }
    }
    redirect('admin/order_view.php?id=' . $order_id);
}

$st = $pdo->prepare('SELECT * FROM customer WHERE customer_id = ?'); $st->execute([$order['customer_id']]);
$cust = $st->fetch();
$st = $pdo->prepare('SELECT oi.*, e.name FROM order_item oi JOIN equipment e ON e.equipment_id = oi.equipment_id WHERE oi.order_id = ?');
$st->execute([$order_id]); $items = $st->fetchAll();
$st = $pdo->prepare('SELECT p.*, a.full_name AS admin_name FROM payment p LEFT JOIN admin a ON a.admin_id = p.verified_by WHERE p.order_id = ? ORDER BY p.payment_id DESC');
$st->execute([$order_id]); $payments = $st->fetchAll();

$page_title = "Order #$order_id";
require __DIR__ . '/_header.php';
?>
<div class="page-head"><h1>Order #<?= $order_id ?></h1><a href="<?= url('admin/orders.php') ?>">&larr; All orders</a></div>
<div class="two-col">
  <div>
    <div class="table-wrap"><table>
      <tr><th>Product</th><th>Qty</th><th>Unit price</th><th>Subtotal</th></tr>
      <?php foreach ($items as $it): ?><tr><td><?= e($it['name']) ?></td><td><?= (int)$it['quantity'] ?></td><td><?= peso($it['unit_price']) ?></td><td><?= peso($it['subtotal']) ?></td></tr><?php endforeach; ?>
      <tr><td colspan="3" class="right"><b>Total</b></td><td><b><?= peso($order['total_amount']) ?></b></td></tr>
    </table></div>

    <h2>Payments</h2>
    <div class="table-wrap"><table>
      <tr><th>Date</th><th>Method</th><th>Amount</th><th>Reference</th><th>Receipt</th><th>Status</th><th></th></tr>
      <?php foreach ($payments as $p): ?>
      <tr>
        <td><?= e(date('M j, Y', strtotime($p['created_at']))) ?></td><td><?= e($p['payment_method']) ?></td><td><?= peso($p['amount']) ?></td>
        <td><?= e($p['reference_number'] ?: '-') ?></td>
        <td><?php if ($p['proof_image']): ?><a href="<?= e(url($p['proof_image'])) ?>" target="_blank"><img class="proof-thumb" src="<?= e(url($p['proof_image'])) ?>" alt="Receipt"></a><?php else: ?>-<?php endif; ?></td>
        <td><?= badge($p['payment_status'] === 'paid' ? 'verified' : ($p['payment_status'] === 'failed' ? 'rejected' : $p['payment_status'])) ?><?= $p['remarks'] ? '<br><span class="help">' . e($p['remarks']) . '</span>' : '' ?></td>
        <td><?php if ($p['payment_status'] === 'pending'): ?>
          <form method="post" action="<?= url('admin/payments.php') ?>" class="actions">
            <?= csrf_field() ?><input type="hidden" name="payment_id" value="<?= (int)$p['payment_id'] ?>"><input type="hidden" name="back" value="order_view.php?id=<?= $order_id ?>">
            <button class="btn btn-sm" name="action" value="verify" type="submit">Verify</button>
            <button class="btn btn-sm btn-danger" name="action" value="reject" type="submit">Reject</button>
          </form><?php endif; ?></td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$payments): ?><tr><td colspan="7" class="empty">No payments yet.</td></tr><?php endif; ?>
    </table></div>
  </div>

  <aside>
    <div class="summary">
      <h3>Summary</h3>
      <div class="row"><span>Order status</span><span><?= badge($order['status']) ?></span></div>
      <div class="row"><span>Payment</span><span><?= badge($order['pay_state']) ?></span></div>
      <div class="row"><span>Total</span><span><?= peso($order['total_amount']) ?></span></div>
      <div class="row"><span>Verified payments</span><span><?= peso($order['paid_amount']) ?></span></div>
      <div class="row total"><span>Balance</span><span><?= $order['status'] === 'cancelled' ? '-' : peso($order['balance']) ?></span></div>
    </div>
    <div class="summary" style="margin-top:16px">
      <h3>Customer</h3>
      <p><a href="<?= url('admin/customer_view.php?id=' . (int)$cust['customer_id']) ?>"><?= e($cust['full_name']) ?></a><br>
         <?= e($cust['email']) ?><br><?= e($cust['phone']) ?></p>
      <p class="muted">Ship to: <?= e($order['shipping_address']) ?></p>
      <?php if ($order['shipping_date']): ?><p class="muted">Shipped: <?= e($order['shipping_date']) ?></p><?php endif; ?>
    </div>
    <form method="post" class="summary" style="margin-top:16px">
      <h3>Update status</h3>
      <?= csrf_field() ?><input type="hidden" name="action" value="status"><input type="hidden" name="order_id" value="<?= $order_id ?>">
      <div class="form-group"><select name="status" <?= $order['status'] === 'cancelled' ? 'disabled' : '' ?>>
        <?php foreach (['pending', 'paid', 'shipped', 'delivered', 'cancelled'] as $s): ?><option value="<?= $s ?>" <?= $order['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option><?php endforeach; ?></select></div>
      <button class="btn btn-block" type="submit" <?= $order['status'] === 'cancelled' ? 'disabled' : '' ?>>Save status</button>
      <p class="help">Cancelling returns the items to available stock.</p>
    </form>
    <?php if ($order['status'] !== 'cancelled' && $order['balance'] > 0): ?>
    <form method="post" class="summary" style="margin-top:16px">
      <h3>Record a payment</h3>
      <?= csrf_field() ?><input type="hidden" name="action" value="record_payment"><input type="hidden" name="order_id" value="<?= $order_id ?>">
      <div class="form-group"><label for="am">Amount (₱)</label><input type="number" id="am" name="amount" step="0.01" min="1" max="<?= e($order['balance']) ?>" required style="max-width:none"></div>
      <div class="form-group"><label for="pm">Method</label><select id="pm" name="payment_method"><option value="cash">Cash</option><option value="gcash">GCash</option><option value="bank_transfer">Bank transfer</option></select></div>
      <div class="form-group"><label for="rf">Reference (optional)</label><input type="text" id="rf" name="reference_number" maxlength="50"></div>
      <button class="btn btn-block" type="submit">Save payment</button>
      <p class="help">Use this for partial or in-store payments. It counts as verified right away.</p>
    </form>
    <?php endif; ?>
  </aside>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
