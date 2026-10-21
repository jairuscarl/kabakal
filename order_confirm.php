<?php
/**
 * Order confirmation + GCash payment page.
 * Shows the shop's GCash details, the amount to send, and lets the customer
 * upload the receipt screenshot + reference number. Also used to view an order later.
 */
require_once __DIR__ . '/includes/init.php';
require_login();

$order_id = (int)($_GET['id'] ?? 0);
$rows = fetch_orders('AND o.order_id = ? AND o.customer_id = ?', [$order_id, $_SESSION['customer_id']]);
if (!$rows) { flash('error', 'Order not found.'); redirect('my_account.php?tab=orders'); }
$order = $rows[0];

// Payments already sent for this order
$st = $pdo->prepare('SELECT * FROM payment WHERE order_id = ? ORDER BY payment_id DESC');
$st->execute([$order_id]);
$payments = $st->fetchAll();

// Money that is paid or still being checked cannot be sent again
$pending_amt = 0;
foreach ($payments as $p) { if ($p['payment_status'] === 'pending') $pending_amt += $p['amount']; }
$to_send = max(0, round($order['total_amount'] - $order['paid_amount'] - $pending_amt, 2));
$is_cod  = $order['payment_method'] === 'cod';
$can_pay = $order['status'] !== 'cancelled' && $to_send > 0 && !$is_cod;   // COD is paid in cash to the rider

// ---- Cancel order: only while still pending and no money has been sent or verified ----
$has_payment = false;
foreach ($payments as $p) { if (in_array($p['payment_status'], ['pending', 'paid'], true)) $has_payment = true; }
$can_cancel = $order['status'] === 'pending' && !$has_payment;
$cancel_reasons = ['Ordered by mistake', 'Found a better price', 'Changed my mind', 'Delivery takes too long', 'Other'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel') {
    $reason = $_POST['cancel_reason'] ?? '';
    if (!$can_cancel)                                      flash('error', 'This order can no longer be cancelled. Please contact the shop.');
    elseif (!in_array($reason, $cancel_reasons, true))     flash('error', 'Please choose a reason for cancelling.');
    elseif (set_order_status($order_id, 'cancelled')) {    // also returns the items to available stock
        $pdo->prepare('UPDATE orders SET cancel_reason = ?, cancelled_at = NOW() WHERE order_id = ? AND customer_id = ?')
            ->execute([$reason, $order_id, $_SESSION['customer_id']]);
        flash('success', 'Your order has been cancelled.');
    } else flash('error', 'Could not cancel this order.');
    redirect("order_confirm.php?id=$order_id");
}

// ---- Customer uploads proof of payment ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $can_pay && ($_POST['action'] ?? '') !== 'cancel') {
    $amount = round((float)($_POST['amount'] ?? 0), 2);
    $ref    = trim($_POST['reference_number'] ?? '');
    $error  = null;

    if ($amount <= 0 || $amount > $to_send)        $error = 'Amount must be more than 0 and not more than ' . peso($to_send) . '.';
    elseif (!preg_match('/^[A-Za-z0-9]{6,30}$/', $ref)) $error = 'Enter the GCash reference number (letters and numbers only, no spaces).';
    else {
        [$path, $upErr] = upload_image('proof', 'uploads/proofs');
        if ($upErr)        $error = $upErr;
        elseif (!$path)    $error = 'Please attach a screenshot of your GCash receipt.';
    }

    if ($error) {
        flash('error', $error);
    } else {
        $pdo->prepare("INSERT INTO payment (order_id, payment_method, reference_number, proof_image, amount, payment_status)
                       VALUES (?, 'gcash', ?, ?, ?, 'pending')")->execute([$order_id, $ref, $path, $amount]);
        flash('success', 'Receipt received! The shop will verify your payment soon.');
        redirect("order_confirm.php?id=$order_id");
    }
}

$items = $pdo->prepare('SELECT oi.*, e.name FROM order_item oi JOIN equipment e ON e.equipment_id = oi.equipment_id WHERE oi.order_id = ?');
$items->execute([$order_id]);
$items = $items->fetchAll();

$page_title = "Order #$order_id";
require __DIR__ . '/includes/header.php';
?>
<div class="page-head">
  <h1>Order #<?= $order_id ?></h1>
  <div><?= badge($order['status']) ?> <?= badge($order['pay_state']) ?></div>
</div>

<div class="two-col">
  <div>
    <div class="table-wrap"><table>
      <tr><th>Product</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr>
      <?php foreach ($items as $it): ?>
        <tr><td><?= e($it['name']) ?></td><td><?= (int)$it['quantity'] ?></td><td><?= peso($it['unit_price']) ?></td><td><?= peso($it['subtotal']) ?></td></tr>
      <?php endforeach; ?>
      <tr><td colspan="3" class="right"><b>Total</b></td><td><b><?= peso($order['total_amount']) ?></b></td></tr>
    </table></div>
    <p class="muted">Delivery address: <?= e($order['shipping_address']) ?><br>
      Payment method: <?= $is_cod ? 'Cash on Delivery' : 'GCash' ?></p>

    <h2>Payments</h2>
    <?php if (!$payments): ?><p class="muted">No payment sent yet.</p><?php else: ?>
    <div class="table-wrap"><table>
      <tr><th>Date</th><th>Amount</th><th>Reference</th><th>Receipt</th><th>Status</th></tr>
      <?php foreach ($payments as $p): ?>
        <tr>
          <td><?= e(date('M j, Y', strtotime($p['created_at']))) ?></td>
          <td><?= peso($p['amount']) ?></td>
          <td><?= e($p['reference_number'] ?: '-') ?></td>
          <td><?php if ($p['proof_image']): ?><a href="<?= e(url($p['proof_image'])) ?>" target="_blank"><img class="proof-thumb" src="<?= e(url($p['proof_image'])) ?>" alt="Receipt"></a><?php else: ?>-<?php endif; ?></td>
          <td><?= badge($p['payment_status'] === 'paid' ? 'verified' : ($p['payment_status'] === 'failed' ? 'rejected' : $p['payment_status'])) ?>
              <?php if ($p['remarks']): ?><br><span class="help"><?= e($p['remarks']) ?></span><?php endif; ?></td>
        </tr>
      <?php endforeach; ?>
    </table></div>
    <?php endif; ?>
  </div>

  <aside>
    <?php if ($can_pay): ?>
      <div class="gcash-box">
        <h3>Pay with GCash</h3>
        <p>Send exactly</p>
        <div class="amount"><?= peso($to_send) ?></div>
        <p><b><?= e(setting('gcash_name')) ?></b><br><?= e(setting('gcash_number')) ?></p>
        <?php if (setting('gcash_qr')): ?><img src="<?= e(url(setting('gcash_qr'))) ?>" alt="GCash QR code"><?php endif; ?>
        <p class="help">Then upload your receipt screenshot and reference number below.</p>
      </div>
      <form method="post" enctype="multipart/form-data" class="summary" style="margin-top:16px">
        <?= csrf_field() ?>
        <div class="form-group"><label for="amount">Amount you sent (₱)</label>
          <input type="number" id="amount" name="amount" step="0.01" min="1" max="<?= e($to_send) ?>" value="<?= e($to_send) ?>" required style="max-width:none"></div>
        <div class="form-group"><label for="ref">GCash reference number</label>
          <input type="text" id="ref" name="reference_number" maxlength="30" required></div>
        <div class="form-group"><label for="proof">Receipt screenshot</label>
          <input type="file" id="proof" name="proof" accept="image/*" required>
          <p class="help">JPG, PNG or WEBP, up to <?= MAX_IMAGE_SIZE / 1048576 ?> MB.</p></div>
        <button class="btn btn-block" type="submit">Submit payment proof</button>
      </form>
    <?php elseif ($order['status'] === 'cancelled'): ?>
      <div class="summary"><h3>Order cancelled</h3><p class="muted">This order was cancelled<?= $order['cancelled_at'] ? ' on ' . e(date('M j, Y', strtotime($order['cancelled_at']))) : '' ?>.<?php if ($order['cancel_reason']): ?><br>Reason: <?= e($order['cancel_reason']) ?><?php endif; ?></p></div>
    <?php elseif ($order['pay_state'] === 'Paid'): ?>
      <div class="summary"><h3>Fully paid</h3><p class="muted">Thank you! Your payment is verified.</p></div>
    <?php elseif ($is_cod): ?>
      <div class="gcash-box">
        <h3>Cash on Delivery</h3>
        <p>Please prepare</p>
        <div class="amount"><?= peso($order['balance']) ?></div>
        <p class="help">Pay the rider in cash when your order arrives.</p>
      </div>
    <?php else: ?>
      <div class="summary"><h3>Waiting for verification</h3><p class="muted">Your receipt is being checked by the shop.</p></div>
    <?php endif; ?>

    <?php if ($can_cancel): ?>
      <form method="post" class="summary" style="margin-top:16px" onsubmit="return confirm('Cancel this order? This cannot be undone.');">
        <h3>Cancel order</h3>
        <?= csrf_field() ?><input type="hidden" name="action" value="cancel">
        <div class="form-group"><label for="cr">Reason</label>
          <select id="cr" name="cancel_reason" required>
            <option value="">Choose a reason</option>
            <?php foreach ($cancel_reasons as $r): ?><option value="<?= e($r) ?>"><?= e($r) ?></option><?php endforeach; ?>
          </select></div>
        <button class="btn btn-danger btn-block" type="submit">Cancel this order</button>
        <p class="help">You can cancel only before sending a payment.</p>
      </form>
    <?php endif; ?>
  </aside>
</div>
<p><a href="<?= url('my_account.php?tab=orders') ?>">&larr; Back to my orders</a></p>
<?php require __DIR__ . '/includes/footer.php'; ?>
