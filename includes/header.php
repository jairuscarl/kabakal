<?php
/** Customer-side header + navigation. Set $page_title before including. */
$shop_name = setting('shop_name', 'Kabakal Fitness Equipment');
$cart_total = cart_count();
$current = basename($_SERVER['SCRIPT_NAME']);
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e(($page_title ?? 'Home') . ' | ' . $shop_name) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;700&display=swap" rel="stylesheet">
  <link rel="icon" type="image/png" href="<?= url('assets/images/favicon.png') ?>">
  <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body>
<header class="site-header">
  <div class="container nav">
    <a class="brand brand-logo" href="<?= url('index.php') ?>"><img src="<?= url('assets/images/logo.png') ?>" alt="<?= e($shop_name) ?>" width="720" height="229"></a>
    <button class="nav-toggle" type="button" aria-label="Menu" aria-expanded="false">&#9776;</button>
    <nav class="nav-links" id="nav-links">
      <a href="<?= url('index.php') ?>" class="<?= $current === 'index.php' ? 'active' : '' ?>">Home</a>
      <a href="<?= url('products.php') ?>" class="<?= in_array($current, ['products.php', 'product.php']) ? 'active' : '' ?>">Products</a>
      <?php if (is_logged_in()): ?>
        <a href="<?= url('my_account.php') ?>" class="<?= $current === 'my_account.php' ? 'active' : '' ?>">My Account</a>
        <a href="<?= url('logout.php') ?>" class="btn btn-sm btn-danger-solid">Logout</a>
      <?php else: ?>
        <a href="<?= url('login.php') ?>">Login</a>
        <a href="<?= url('register.php') ?>" class="btn btn-sm">Register</a>
      <?php endif; ?>
    </nav>
    <?php /* The cart sits alone on the right edge of the bar and shows just an icon; the badge is the item count. */ ?>
    <a class="nav-cart<?= $current === 'cart.php' ? ' active' : '' ?>" href="<?= url('cart.php') ?>" title="Cart" aria-label="Cart<?php if ($cart_total): ?> (<?= (int)$cart_total ?>)<?php endif; ?>">
      <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
        <circle cx="8" cy="21" r="1" />
        <circle cx="19" cy="21" r="1" />
        <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12" />
      </svg>
      <?php if ($cart_total): ?><span class="cart-badge"><?= (int)$cart_total ?></span><?php endif; ?>
    </a>
  </div>
</header>
<?php $main_class = $main_class ?? 'container page'; /* the Home page passes '' for a full-bleed hero */ ?>
<main<?= $main_class === '' ? '' : ' class="' . e($main_class) . '"' ?>>
<?php show_flash(); ?>
