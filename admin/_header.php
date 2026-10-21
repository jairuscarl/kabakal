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

/* Line icons for the menu (24x24 outline drawings, coloured by the link text). */
$nav_icons = [
    'index.php'      => '<rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>',
    'products.php'   => '<path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/>',
    'categories.php' => '<path d="M12.59 2.59A2 2 0 0 0 11.17 2H4a2 2 0 0 0-2 2v7.17a2 2 0 0 0 .59 1.42l8.7 8.7a2.43 2.43 0 0 0 3.42 0l6.58-6.58a2.43 2.43 0 0 0 0-3.42z"/><circle cx="7.5" cy="7.5" r="1.5"/>',
    'inventory.php'  => '<path d="M22 8.35V20a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V8.35A2 2 0 0 1 3.26 6.5l8-3.2a2 2 0 0 1 1.48 0l8 3.2A2 2 0 0 1 22 8.35Z"/><path d="M6 18h12"/><path d="M6 14h12"/><rect x="6" y="10" width="12" height="12"/>',
    'orders.php'     => '<rect x="8" y="2" width="8" height="4" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M12 11h4"/><path d="M12 16h4"/><path d="M8 11h.01"/><path d="M8 16h.01"/>',
    'customers.php'  => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
    'payments.php'   => '<rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/><line x1="6" y1="15" x2="10" y2="15"/>',
    'reviews.php'    => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',
    'messages.php'   => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
    'videos.php'     => '<path d="m22 8-6 4 6 4V8Z"/><rect x="2" y="6" width="14" height="12" rx="2"/>',
    'account.php'    => '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
    'settings.php'   => '<path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/>',
    'view_site'      => '<path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>',
    'logout'         => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>',
];
$nav_icon = fn($key) => '<svg class="nav-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" '
    . 'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . ($nav_icons[$key] ?? '') . '</svg>';
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e(($page_title ?? 'Admin') . ' | Kabakal Admin') ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;700&display=swap" rel="stylesheet">
  <link rel="icon" type="image/png" href="<?= url('assets/images/favicon.png') ?>">
  <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
  <link rel="stylesheet" href="<?= url('assets/css/admin.css') ?>">
  <?= admin_theme_script() ?>
</head>
<body class="admin-theme admin-layout">
<header class="admin-bar">
  <div class="container nav">
    <a class="brand brand-logo" href="<?= url('admin/index.php') ?>"><img src="<?= url('assets/images/logo.png') ?>" alt="Kabakal Fitness Equipment" width="720" height="229"><span class="admin-tag">ADMIN</span></a>
    <?= theme_toggle_button() ?>
    <button class="nav-toggle" type="button" aria-label="Menu" aria-expanded="false">&#9776;</button>
    <nav class="admin-nav">
      <?php foreach ($menu as $file => $label): ?>
        <a href="<?= url('admin/' . $file) ?>" class="<?= $current === $file ? 'active' : '' ?>"<?= $current === $file ? ' aria-current="page"' : '' ?>><?= $nav_icon($file) ?><span class="nav-label"><?= e($label) ?></span>
          <?php if ($file === 'payments.php' && $pending_proofs): ?><span class="cart-badge"><?= $pending_proofs ?></span><?php endif; ?>
          <?php if ($file === 'messages.php' && $unread_msgs): ?><span class="cart-badge"><?= $unread_msgs ?></span><?php endif; ?></a>
      <?php endforeach; ?>
      <a href="<?= url('index.php') ?>" target="_blank" class="admin-nav-bottom"><?= $nav_icon('view_site') ?><span class="nav-label">View site</span></a>
      <a href="<?= url('admin/logout.php') ?>" class="btn btn-sm btn-danger-solid"><?= $nav_icon('logout') ?><span class="nav-label">Logout (<?= e($admin_short) ?>)</span></a>
    </nav>
  </div>
</header>
<main class="container page">
<?php show_flash(); ?>
