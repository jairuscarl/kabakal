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
      <a href="<?= url('cart.php') ?>" class="<?= $current === 'cart.php' ? 'active' : '' ?>">Cart<?php if ($cart_total): ?> <span class="cart-badge"><?= $cart_total ?></span><?php endif; ?></a>
      <?php if (is_logged_in()): ?>
        <a href="<?= url('my_account.php') ?>" class="<?= $current === 'my_account.php' ? 'active' : '' ?>">My Account</a>
        <a href="<?= url('logout.php') ?>">Logout</a>
      <?php else: ?>
        <a href="<?= url('login.php') ?>">Login</a>
        <a href="<?= url('register.php') ?>" class="btn btn-sm">Register</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<?php $main_class = $main_class ?? 'container page'; /* the Home page passes '' for a full-bleed hero */ ?>
<main<?= $main_class === '' ? '' : ' class="' . e($main_class) . '"' ?>>
<?php show_flash(); ?>
