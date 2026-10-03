<?php
/** Admin > Settings: shop info and GCash details (name, number, QR code). */
require_once __DIR__ . '/../includes/init.php';
require_admin(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = ['shop_name' => 100, 'shop_phone' => 30, 'shop_mobile' => 30, 'shop_email' => 150, 'shop_facebook' => 255, 'shop_address' => 255, 'shop_about' => 1000, 'gcash_name' => 100, 'gcash_number' => 30];
    $up = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
    foreach ($fields as $key => $max) { $up->execute([$key, substr(trim($_POST[$key] ?? ''), 0, $max)]); }

    [$qr, $err] = upload_image('gcash_qr', 'uploads/settings');
    if ($err) { flash('error', $err); }
    else {
        if ($qr) { delete_file(setting('gcash_qr')); $up->execute(['gcash_qr', $qr]); }
        flash('success', 'Settings saved.');
    }
    redirect('admin/settings.php');
}

$page_title = 'Settings';
require __DIR__ . '/_header.php';
$S = settings();
?>
<h1>Settings</h1>
<form method="post" enctype="multipart/form-data" class="form-box form-wide">
  <?= csrf_field() ?>
  <h3>GCash payment details</h3>
  <div class="form-row">
    <div class="form-group"><label for="gn">GCash account name</label><input type="text" id="gn" name="gcash_name" value="<?= e($S['gcash_name'] ?? '') ?>" required></div>
    <div class="form-group"><label for="gm">GCash number</label><input type="text" id="gm" name="gcash_number" value="<?= e($S['gcash_number'] ?? '') ?>" required></div>
  </div>
  <div class="form-group"><label for="qr">GCash QR code image</label>
    <?php if (!empty($S['gcash_qr'])): ?><img id="qrp" src="<?= e(url($S['gcash_qr'])) ?>" alt="QR" style="max-width:180px;margin-bottom:8px;background:#fff;border-radius:6px">
    <?php else: ?><img id="qrp" src="" alt="QR" hidden style="max-width:180px;margin-bottom:8px"><?php endif; ?>
    <input type="file" id="qr" name="gcash_qr" accept="image/*" data-preview="#qrp"></div>
  <h3>Shop information</h3>
  <div class="form-group"><label for="sn">Shop name</label><input type="text" id="sn" name="shop_name" value="<?= e($S['shop_name'] ?? '') ?>" required></div>
  <div class="form-row">
    <div class="form-group"><label for="sp">Phone (landline)</label><input type="text" id="sp" name="shop_phone" value="<?= e($S['shop_phone'] ?? '') ?>"></div>
    <div class="form-group"><label for="sm">Mobile</label><input type="text" id="sm" name="shop_mobile" value="<?= e($S['shop_mobile'] ?? '') ?>"></div>
  </div>
  <div class="form-row">
    <div class="form-group"><label for="se">Email</label><input type="text" id="se" name="shop_email" value="<?= e($S['shop_email'] ?? '') ?>"></div>
    <div class="form-group"><label for="sf">Facebook page link</label><input type="url" id="sf" name="shop_facebook" value="<?= e($S['shop_facebook'] ?? '') ?>" placeholder="https://www.facebook.com/..."></div>
  </div>
  <div class="form-group"><label for="sa">Address</label><input type="text" id="sa" name="shop_address" value="<?= e($S['shop_address'] ?? '') ?>"></div>
  <div class="form-group"><label for="ab">About the shop (Home page)</label><textarea id="ab" name="shop_about"><?= e($S['shop_about'] ?? '') ?></textarea></div>
  <button class="btn" type="submit">Save settings</button>
</form>
<?php require __DIR__ . '/_footer.php'; ?>
