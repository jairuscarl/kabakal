<?php
/** Admin > change my own password. */
require_once __DIR__ . '/../includes/init.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $st = $pdo->prepare('SELECT password FROM admin WHERE admin_id = ?'); $st->execute([$_SESSION['admin_id']]);
    $new = $_POST['new_password'] ?? '';
    if (!password_verify($_POST['old_password'] ?? '', $st->fetchColumn())) flash('error', 'Current password is wrong.');
    elseif (strlen($new) < 8)                        flash('error', 'New password must be at least 8 characters.');
    elseif ($new !== ($_POST['new_password2'] ?? '')) flash('error', 'New passwords do not match.');
    else {
        $pdo->prepare('UPDATE admin SET password = ? WHERE admin_id = ?')->execute([password_hash($new, PASSWORD_DEFAULT), $_SESSION['admin_id']]);
        flash('success', 'Password changed.');
    }
    redirect('admin/account.php');
}
$page_title = 'My Password';
require __DIR__ . '/_header.php';
?>
<h1>Change password</h1>
<form method="post" class="form-box form-wide">
  <?= csrf_field() ?>
  <div class="form-group"><label for="o">Current password</label><input type="password" id="o" name="old_password" required></div>
  <div class="form-group"><label for="n">New password (8+ characters)</label><input type="password" id="n" name="new_password" minlength="8" required></div>
  <div class="form-group"><label for="n2">Confirm new password</label><input type="password" id="n2" name="new_password2" minlength="8" required></div>
  <button class="btn" type="submit">Change password</button>
</form>
<?php require __DIR__ . '/_footer.php'; ?>
