<?php
/** Admin / staff login (uses the `admin` table). */
require_once __DIR__ . '/../includes/init.php';
if (is_admin_logged_in()) redirect('admin/index.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $st = $pdo->prepare('SELECT * FROM admin WHERE username = ?');
    $st->execute([trim($_POST['username'] ?? '')]);
    $a = $st->fetch();
    if ($a && password_verify($_POST['password'] ?? '', $a['password'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id']   = (int)$a['admin_id'];
        $_SESSION['admin_name'] = $a['full_name'];
        $_SESSION['admin_role'] = $a['role'];
        redirect('admin/index.php');
    }
    flash('error', 'Wrong username or password.');
}
?><!DOCTYPE html>
<html lang="en"><head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Login | Kabakal</title>
  <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;700&display=swap" rel="stylesheet">
  <link rel="icon" type="image/png" href="<?= url('assets/images/favicon.png') ?>">
  <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head><body>
<div class="admin-login"><div class="form-box" style="width:100%">
  <div class="login-logo"><img src="<?= url('assets/images/logo.png') ?>" alt="Kabakal Fitness Equipment" width="720" height="229"></div>
  <h1 class="brand" style="text-align:center">STAFF <span>LOGIN</span></h1>
  <?php show_flash(); ?>
  <form method="post">
    <?= csrf_field() ?>
    <div class="form-group"><label for="u">Username</label><input type="text" id="u" name="username" required autofocus></div>
    <?= pw_field('p', 'password', 'Password', 'required') ?>
    <button class="btn btn-block" type="submit">Log in</button>
  </form>
  <p class="muted"><a href="<?= url('index.php') ?>">&larr; Back to shop</a></p>
</div></div>
<script src="<?= url('assets/js/main.js') ?>"></script>
</body></html>
