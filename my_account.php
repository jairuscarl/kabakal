<?php
/** My Account: profile + my orders and their status. */
require_once __DIR__ . '/includes/init.php';
require_login();
$cid = $_SESSION['customer_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['action'] ?? '') === 'profile') {
        $first = trim($_POST['first_name'] ?? ''); $last = trim($_POST['last_name'] ?? '');
        $phone = trim($_POST['phone'] ?? ''); $addr = trim($_POST['address'] ?? '');
        // full_name stays a convenience "first last" copy of first_name + last_name.
        $name = trim($first . ' ' . $last);
        if ($first === '' || strlen($first) > 50) {
            flash('error', 'Please enter your first name (50 characters max).');
        } elseif ($last === '' || strlen($last) > 50) {
            flash('error', 'Please enter your last name (50 characters max).');
        } elseif ($phone !== '' && !preg_match('/^[0-9+\-\s()]{7,20}$/', $phone)) {
            flash('error', 'Phone number looks invalid.');
        } else {
            $pdo->prepare('UPDATE customer SET first_name = ?, last_name = ?, full_name = ?, phone = ?, address = ? WHERE customer_id = ?')
                ->execute([$first, $last, $name, $phone ?: null, $addr ?: null, $cid]);
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

<div class="form-row account-forms">
  <form method="post" class="form-box">
    <h3>Profile</h3>
    <?= csrf_field() ?><input type="hidden" name="action" value="profile">
    <div class="form-group"><label>Email</label><input type="email" value="<?= e($me['email']) ?>" disabled></div>
    <div class="form-row">
      <div class="form-group"><label for="fn">First name</label><input type="text" id="fn" name="first_name" value="<?= e($me['first_name']) ?>" required></div>
      <div class="form-group"><label for="ln">Last name</label><input type="text" id="ln" name="last_name" value="<?= e($me['last_name']) ?>" required></div>
    </div>
    <div class="form-group"><label for="ph">Mobile number</label><input type="tel" id="ph" name="phone" value="<?= e($me['phone']) ?>"></div>
    <div class="form-group"><label for="ad">Delivery address</label><input type="text" id="ad" name="address" value="<?= e($me['address']) ?>"></div>
    <button class="btn" type="submit">Save profile</button>
  </form>
  <form method="post" class="form-box">
    <h3>Change password</h3>
    <?= csrf_field() ?><input type="hidden" name="action" value="password">
    <?= pw_field('op', 'old_password', 'Current password', 'required') ?>
    <?= pw_field('np', 'new_password', 'New password', 'minlength="8" required') ?>
    <?= pw_field('np2', 'new_password2', 'Confirm new password', 'minlength="8" required') ?>
    <button class="btn" type="submit">Change password</button>
  </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
