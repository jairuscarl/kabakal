<?php
/** My Account: side menu (Profile / Orders / Security / Logout), one section shown at a time (?tab=). */
require_once __DIR__ . '/includes/init.php';
require_login();
$cid = $_SESSION['customer_id'];
$tabs = ['profile' => 'Profile', 'orders' => 'My orders', 'security' => 'Security'];
$tab = isset($tabs[$_GET['tab'] ?? '']) ? $_GET['tab'] : 'profile';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['action'] ?? '') === 'profile') {
        $tab = 'profile';
        $first = trim($_POST['first_name'] ?? ''); $last = trim($_POST['last_name'] ?? '');
        $phone = trim($_POST['phone'] ?? ''); $addr = trim($_POST['address'] ?? '');
        // full_name stays a convenience "first last" copy of first_name + last_name.
        $name = trim($first . ' ' . $last);
        [$avatar, $err] = upload_image('avatar', 'uploads/avatars');
        if (!$err && ($first === '' || strlen($first) > 50))  $err = 'Please enter your first name (50 characters max).';
        elseif (!$err && ($last === '' || strlen($last) > 50)) $err = 'Please enter your last name (50 characters max).';
        elseif (!$err && $phone !== '' && !preg_match('/^[0-9+\-\s()]{7,20}$/', $phone)) $err = 'Phone number looks invalid.';
        if ($err) {
            delete_file($avatar);
            flash('error', $err);
        } else {
            $st = $pdo->prepare('SELECT avatar_url FROM customer WHERE customer_id = ?'); $st->execute([$cid]);
            $old = $st->fetchColumn() ?: null;
            $new = $avatar ?: (isset($_POST['remove_avatar']) ? null : $old);
            $pdo->prepare('UPDATE customer SET first_name = ?, last_name = ?, full_name = ?, phone = ?, address = ?, avatar_url = ? WHERE customer_id = ?')
                ->execute([$first, $last, $name, $phone ?: null, $addr ?: null, $new, $cid]);
            if ($old && $old !== $new) delete_file($old);
            $_SESSION['customer_name'] = $name;
            flash('success', 'Profile updated.');
        }
    } elseif (($_POST['action'] ?? '') === 'password') {
        $tab = 'security';
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
    redirect("my_account.php?tab=$tab");
}

$st = $pdo->prepare('SELECT * FROM customer WHERE customer_id = ?'); $st->execute([$cid]);
$me = $st->fetch();
$orders = $tab === 'orders' ? fetch_orders('AND o.customer_id = ?', [$cid]) : [];
$inbox_unread = unread_count();

$page_title = 'My Account';
require __DIR__ . '/includes/header.php';
?>
<h1>My Account</h1>
<div class="account">
  <aside class="account-side">
    <div class="account-me">
      <?= avatar_html($me['avatar_url'], $me['first_name'], 80) ?>
      <div><strong><?= e($me['full_name']) ?></strong><div class="muted"><?= e($me['email']) ?></div></div>
    </div>
    <nav class="account-menu">
      <?php foreach ($tabs as $key => $label): ?>
        <a href="<?= url("my_account.php?tab=$key") ?>" class="<?= $tab === $key ? 'active' : '' ?>"><?= e($label) ?></a>
      <?php endforeach; ?>
      <a href="<?= url('notifications.php') ?>">Inbox<?php if ($inbox_unread): ?><span class="cart-badge"><?= $inbox_unread ?></span><?php endif; ?></a>
    </nav>
    <a class="btn btn-sm btn-danger-solid btn-block" href="<?= url('logout.php') ?>">Logout</a>
  </aside>

  <section class="account-main">
  <?php if ($tab === 'profile'): ?>
    <form method="post" enctype="multipart/form-data" class="form-box">
      <h2>Profile</h2>
      <?= csrf_field() ?><input type="hidden" name="action" value="profile">
      <div class="avatar-edit">
        <?= $me['avatar_url'] ? '<img id="avatar-preview" class="avatar" src="' . e(url($me['avatar_url'])) . '" alt="Your profile picture">'
                              : avatar_html(null, $me['first_name'], 96) . '<img id="avatar-preview" class="avatar" alt="" hidden>' ?>
        <div class="form-group" style="margin:0;flex:1;min-width:200px">
          <label for="avatar">Profile picture</label>
          <input type="file" id="avatar" name="avatar" accept="image/*" data-preview="#avatar-preview">
          <p class="help">JPG, PNG, WEBP or GIF, up to <?= MAX_IMAGE_SIZE / 1048576 ?> MB. Shown in the top bar.</p>
          <?php if ($me['avatar_url']): ?><label class="check"><input type="checkbox" name="remove_avatar"> Remove my picture</label><?php endif; ?>
        </div>
      </div>
      <div class="form-group"><label>Email</label><input type="email" value="<?= e($me['email']) ?>" disabled></div>
      <div class="form-row">
        <div class="form-group"><label for="fn">First name</label><input type="text" id="fn" name="first_name" value="<?= e($me['first_name']) ?>" required></div>
        <div class="form-group"><label for="ln">Last name</label><input type="text" id="ln" name="last_name" value="<?= e($me['last_name']) ?>" required></div>
      </div>
      <div class="form-group"><label for="ph">Mobile number</label><input type="tel" id="ph" name="phone" value="<?= e($me['phone']) ?>"></div>
      <div class="form-group"><label for="ad">Delivery address</label><input type="text" id="ad" name="address" value="<?= e($me['address']) ?>"></div>
      <button class="btn" type="submit">Save profile</button>
    </form>

  <?php elseif ($tab === 'orders'): ?>
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

  <?php else: ?>
    <form method="post" class="form-box">
      <h2>Security</h2>
      <h3>Change password</h3>
      <?= csrf_field() ?><input type="hidden" name="action" value="password">
      <?= pw_field('op', 'old_password', 'Current password', 'required') ?>
      <?= pw_field('np', 'new_password', 'New password', 'minlength="8" required') ?>
      <?= pw_field('np2', 'new_password2', 'Confirm new password', 'minlength="8" required') ?>
      <button class="btn" type="submit">Change password</button>
    </form>
  <?php endif; ?>
  </section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
