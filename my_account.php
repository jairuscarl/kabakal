<?php
/** My Account: profile + my orders and their status. */
require_once __DIR__ . '/includes/init.php';
require_login();
$cid = $_SESSION['customer_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['action'] ?? '') === 'profile') {
        $name = trim($_POST['full_name'] ?? ''); $phone = trim($_POST['phone'] ?? ''); $addr = trim($_POST['address'] ?? '');
        if (strlen($name) < 2 || strlen($name) > 100) {
            flash('error', 'Please enter your full name.');
        } elseif ($phone !== '' && !preg_match('/^[0-9+\-\s()]{7,20}$/', $phone)) {
            flash('error', 'Phone number looks invalid.');
        } else {
            $pdo->prepare('UPDATE customer SET full_name = ?, phone = ?, address = ? WHERE customer_id = ?')
                ->execute([$name, $phone ?: null, $addr ?: null, $cid]);
            $_SESSION['customer_name'] = $name;
            flash('success', 'Profile updated.');
        }
    } elseif (($_POST['action'] ?? '') === 'password') {
        $st = $pdo->prepare('SELECT password FROM customer WHERE customer_id = ?'); $st->execute([$cid]);
        $new = $_POST['new_password'] ?? '';
        if (!password_verify($_POST['old_password'] ?? '', $st->fetchColumn())) flash('error', 'Current password is wrong.');
        elseif (strlen($new) < 8)                     flash('error', 'New password must be at least 8 characters.');
        elseif ($new !== ($_POST['new_password2'] ?? '')) flash('error', 'New passwords do not match.');
        else {
            $pdo->prepare('UPDATE customer SET password = ? WHERE customer_id = ?')->execute([password_hash($new, PASSWORD_DEFAULT), $cid]);
            flash('success', 'Password changed.');
        }
    }
    redirect('my_account.php');
}

$st = $pdo->prepare('SELECT * FROM customer WHERE customer_id = ?'); $st->execute([$cid]);
$me = $st->fetch();
$orders = fetch_orders('AND o.customer_id = ?', [$cid]);

$page_title = 'My Account';
require __DIR__ . '/includes/header.php';
?>
<h1>My Account</h1>
<h2>My orders</h2>
<?php if (!$orders): ?>
  <p class="empty">You have no orders yet. <a href="<?= url('products.php') ?>">Start shopping</a></p>
<?php else: ?>
<div class="table-wrap"><table>
  <tr><th>Order</th><th>Date</th><th>Total</th><th>Balance</th><th>Order status</th><th>Payment</th><th></th></tr>
  <?php foreach ($orders as $o): ?>
    <tr>
      <td>#<?= (int)$o['order_id'] ?></td>
      <td><?= e(date('M j, Y', strtotime($o['order_date']))) ?></td>
      <td><?= peso($o['total_amount']) ?></td>
      <td><?= $o['status'] === 'cancelled' ? '-' : peso($o['balance']) ?></td>
      <td><?= badge($o['status']) ?></td>
      <td><?= badge($o['pay_state']) ?></td>
      <td><a class="btn btn-sm btn-outline" href="<?= url('order_confirm.php?id=' . (int)$o['order_id']) ?>"><?= $o['balance'] > 0 && $o['status'] !== 'cancelled' ? 'Pay / View' : 'View' ?></a></td>
    </tr>
  <?php endforeach; ?>
</table></div>
<?php endif; ?>

<div class="form-row" style="align-items:start">
  <form method="post" class="form-box" style="max-width:none">
    <h3>Profile</h3>
    <?= csrf_field() ?><input type="hidden" name="action" value="profile">
    <div class="form-group"><label>Email</label><input type="email" value="<?= e($me['email']) ?>" disabled></div>
    <div class="form-group"><label for="fn">Full name</label><input type="text" id="fn" name="full_name" value="<?= e($me['full_name']) ?>" required></div>
    <div class="form-group"><label for="ph">Mobile number</label><input type="tel" id="ph" name="phone" value="<?= e($me['phone']) ?>"></div>
    <div class="form-group"><label for="ad">Delivery address</label><input type="text" id="ad" name="address" value="<?= e($me['address']) ?>"></div>
    <button class="btn" type="submit">Save profile</button>
  </form>
  <form method="post" class="form-box" style="max-width:none">
    <h3>Change password</h3>
    <?= csrf_field() ?><input type="hidden" name="action" value="password">
    <div class="form-group"><label for="op">Current password</label><input type="password" id="op" name="old_password" required></div>
    <div class="form-group"><label for="np">New password</label><input type="password" id="np" name="new_password" minlength="8" required></div>
    <div class="form-group"><label for="np2">Confirm new password</label><input type="password" id="np2" name="new_password2" minlength="8" required></div>
    <button class="btn" type="submit">Change password</button>
  </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
