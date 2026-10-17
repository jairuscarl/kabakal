<?php
/** Admin header + menu. Include AFTER require_admin(). Set $page_title first. */
$current = basename($_SERVER['SCRIPT_NAME']);
/* The admin's name only shows in the Logout chip; cap first/last name at 20 chars each —
   the same limit as the customer "Welcome" greeting — so a long name can't stretch the bar. */
$admin_name  = trim((string)($_SESSION['admin_name'] ?? ''));
$admin_short = $admin_name === '' ? 'Admin' : implode(' ', array_filter(array_map('name_limit', preg_split('/\s+/', $admin_name))));
$pending_proofs = (int)$pdo->query("SELECT COUNT(*) FROM payment WHERE payment_status = 'pending'")->fetchColumn();
$unread_msgs = (int)$pdo->query('SELECT COUNT(*) FROM conversation WHERE admin_unread = 1')->fetchColumn();
$menu = [
    'index.php' => 'Dashboard', 'products.php' => 'Products', 'categories.php' => 'Categories',
    'inventory.php' => 'Inventory', 'orders.php' => 'Orders', 'customers.php' => 'Customers',
    'payments.php' => 'Payments', 'reviews.php' => 'Reviews', 'messages.php' => 'Messages', 'videos.php' => 'Promo Videos',
    'account.php' => 'My Password',
];
if ($_SESSION['admin_role'] === 'admin') $menu['settings.php'] = 'Settings';
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e(($page_title ?? 'Admin') . ' | Kabakal Admin') ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;700&display=swap" rel="stylesheet">
  <link rel="icon" type="image/png" href="<?= url('assets/images/favicon.png') ?>">
  <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body>
<header class="admin-bar">
  <div class="container nav">
    <a class="brand brand-logo" href="<?= url('admin/index.php') ?>"><img src="<?= url('assets/images/logo.png') ?>" alt="Kabakal Fitness Equipment" width="720" height="229"><span class="admin-tag">ADMIN</span></a>
    <button class="nav-toggle" type="button" aria-label="Menu" aria-expanded="false">&#9776;</button>
    <nav class="admin-nav">
      <?php foreach ($menu as $file => $label): ?>
        <a href="<?= url('admin/' . $file) ?>" class="<?= $current === $file ? 'active' : '' ?>"><?= e($label) ?>
          <?php if ($file === 'payments.php' && $pending_proofs): ?><span class="cart-badge"><?= $pending_proofs ?></span><?php endif; ?>
          <?php if ($file === 'messages.php' && $unread_msgs): ?><span class="cart-badge"><?= $unread_msgs ?></span><?php endif; ?></a>
      <?php endforeach; ?>
      <a href="<?= url('index.php') ?>" target="_blank">View site</a>
      <a href="<?= url('admin/logout.php') ?>" class="btn btn-sm btn-danger-solid">Logout (<?= e($admin_short) ?>)</a>
    </nav>
  </div>
</header>
<main class="container page">
<?php show_flash(); ?>
