<?php
/** TEMPORARY upload test harness. Delete after use. */
require_once __DIR__ . '/includes/init.php';
$target = $_POST['folder'] ?? 'uploads/products';
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['pic'])) {
    [$path, $err] = upload_image('pic', $target);
    $msg = $err === null
        ? 'OK saved as ' . $path . ' exists=' . var_export(is_file(ROOT_PATH . '/' . $path), true)
        : 'FAILED: ' . $err;
}
?><!DOCTYPE html>
<html><body>
<div id="out"><?= e($msg) ?></div>
<form method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <input type="hidden" name="folder" value="<?= e($target) ?>">
  <input type="file" name="pic">
  <button type="submit">send</button>
</form>
</body></html>
