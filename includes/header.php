<?php
/** Customer-side header + navigation. Set $page_title before including. */
$shop_name = setting('shop_name', 'Kabakal Fitness Equipment');
$cart_total = cart_count();
$unread = unread_count();
$me_avatar = null;
if (is_logged_in()) {
    $st = $pdo->prepare('SELECT avatar_url FROM customer WHERE customer_id = ?'); $st->execute([$_SESSION['customer_id']]);
    $me_avatar = $st->fetchColumn() ?: null;
}
$me_name = is_logged_in() ? (trim((string)($_SESSION['customer_name'] ?? '')) ?: 'Customer') : '';
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
      <a href="<?= url('reviews.php') ?>" class="<?= $current === 'reviews.php' ? 'active' : '' ?>">Reviews</a>
      <?php if (!is_logged_in()): /* logged in: the profile picture on the right opens My Account (Logout is in there) */ ?>
        <a href="<?= url('login.php') ?>">Login</a>
        <a href="<?= url('register.php') ?>" class="btn btn-sm">Register</a>
      <?php endif; ?>
    </nav>
    <?php /* Top-right of the bar: the cart icon first, then the "Welcome, <name>!" greeting (only after login) hard against the right edge; the badge is the item count. */ ?>
    <div class="nav-right">
    <span class="nav-icons">
    <?php if (is_logged_in()): ?>
    <a class="nav-cart nav-mail<?= in_array($current, ['notifications.php', 'messages.php']) ? ' active' : '' ?>" href="<?= url('notifications.php') ?>" title="Inbox" aria-label="Inbox<?php if ($unread): ?> (<?= $unread ?> new)<?php endif; ?>" data-unread-url="<?= url('notifications.php?unread=1') ?>">
      <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
        <rect x="2" y="4" width="20" height="16" rx="2" />
        <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" />
      </svg>
      <span class="cart-badge"<?= $unread ? '' : ' hidden' ?>><?= $unread ?></span>
    </a>
    <?php endif; ?>
    <a class="nav-cart<?= $current === 'cart.php' ? ' active' : '' ?>" href="<?= url('cart.php') ?>" title="Cart" aria-label="Cart<?php if ($cart_total): ?> (<?= (int)$cart_total ?>)<?php endif; ?>">
      <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
        <circle cx="8" cy="21" r="1" />
        <circle cx="19" cy="21" r="1" />
        <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12" />
      </svg>
      <?php if ($cart_total): ?><span class="cart-badge"><?= (int)$cart_total ?></span><?php endif; ?>
    </a>
    <?php if (is_logged_in()): ?>
    <a class="nav-avatar<?= $current === 'my_account.php' ? ' active' : '' ?>" href="<?= url('my_account.php') ?>" title="My Account" aria-label="My Account">
      <?= avatar_html($me_avatar, $me_name, 34) ?>
    </a>
    <?php endif; ?>
    </span>
      <?php if ($me_name !== ''): /* First name only, capped at 20 chars (name_limit adds "…" when cut); a longer name also gets a smaller-font class so "Welcome, Name!" fits the bar, and the title attribute reveals the uncapped name on hover. */
        $me_raw   = preg_split('/\s+/', $me_name)[0];
        $me_first = name_limit($me_raw);
        $me_len   = function_exists('mb_strlen') ? mb_strlen($me_first, 'UTF-8') : strlen($me_first);
        $me_fs    = $me_len > 16 ? ' nav-welcome-xs' : ($me_len > 12 ? ' nav-welcome-sm' : '');
      ?><span class="nav-welcome<?= $me_fs ?>"<?= $me_first !== $me_raw ? ' title="' . e($me_raw) . '"' : '' ?>>Welcome, <b><?= e($me_first) ?></b>!</span><?php endif; ?>
    </div>
  </div>
</header>
<?php $main_class = $main_class ?? 'container page'; /* the Home page passes '' for a full-bleed hero */ ?>
<main<?= $main_class === '' ? '' : ' class="' . e($main_class) . '"' ?>>
<?php show_flash(); ?>
