<?php
/** Customer-side header + navigation. Set $page_title before including. */
$shop_name = setting('shop_name', 'Kabakal Fitness Equipment');
$cart_total = cart_count();
$unread = unread_count();
$me_avatar = null;
$site_theme = 'dark';   // guests always get the dark shop; customers pick in My Account > Preferences
if (is_logged_in()) {
    $st = $pdo->prepare('SELECT avatar_url, theme FROM customer WHERE customer_id = ?'); $st->execute([$_SESSION['customer_id']]);
    $me_row = $st->fetch() ?: [];
    $me_avatar = ($me_row['avatar_url'] ?? null) ?: null;
    $site_theme = ($me_row['theme'] ?? '') === 'light' ? 'light' : 'dark';
}
$me_name = is_logged_in() ? (trim((string)($_SESSION['customer_name'] ?? '')) ?: 'Customer') : '';
$current = basename($_SERVER['SCRIPT_NAME']);
/* Line icons in front of each menu link (24x24 outline drawings, coloured by the link text). */
$nav_icon = function ($name) {
    $paths = [
        'home'     => '<path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',
        'products' => '<path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/>',
        'reviews'  => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',
        'login'    => '<path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/>',
        'register' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/>',
    ];
    return '<svg class="nav-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" '
         . 'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths[$name] . '</svg>';
};
?><!DOCTYPE html>
<html lang="en"<?= $site_theme === 'light' ? ' class="theme-light"' : '' ?>>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e(($page_title ?? 'Home') . ' | ' . $shop_name) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;700&display=swap" rel="stylesheet">
  <link rel="icon" type="image/png" href="<?= url('assets/images/favicon.png') ?>">
  <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body<?= !empty($body_class) ? ' class="' . e($body_class) . '"' : '' ?>>
<header class="site-header">
  <div class="nav">
    <a class="brand brand-logo" href="<?= url('index.php') ?>"><img src="<?= url('assets/images/logo.png') ?>" alt="<?= e($shop_name) ?>" width="720" height="229"></a>
    <button class="nav-toggle" type="button" aria-label="Menu" aria-expanded="false">&#9776;</button>
    <nav class="nav-links" id="nav-links">
      <?php /* Desktop: .nav-main sits after the logo and .nav-auth is moved (CSS order) to the far right, just before the cart; on phones both just stack inside the menu. */ ?>
      <span class="nav-main">
        <a href="<?= url('index.php') ?>" class="<?= $current === 'index.php' ? 'active' : '' ?>"><?= $nav_icon('home') ?>Home</a>
        <a href="<?= url('products.php') ?>" class="<?= in_array($current, ['products.php', 'product.php']) ? 'active' : '' ?>"><?= $nav_icon('products') ?>Products</a>
        <a href="<?= url('reviews.php') ?>" class="<?= $current === 'reviews.php' ? 'active' : '' ?>"><?= $nav_icon('reviews') ?>Reviews</a>
      </span>
      <?php if (!is_logged_in()): /* logged in: the profile picture on the right opens My Account (Logout is in there) */ ?>
      <span class="nav-auth">
        <a href="<?= url('login.php') ?>" class="<?= $current === 'login.php' ? 'active' : '' ?>"><?= $nav_icon('login') ?>Login</a>
        <a href="<?= url('register.php') ?>" class="btn btn-sm"><?= $nav_icon('register') ?>Register</a>
      </span>
      <?php endif; ?>
    </nav>
    <?php /* Header search: always lands on the product list; keeps the typed word when already there. */ ?>
    <form class="nav-search" action="<?= url('products.php') ?>" method="get" role="search">
      <label class="sr-only" for="nav-search">Search products</label>
      <input type="search" id="nav-search" name="q" value="<?= $current === 'products.php' ? e(trim($_GET['q'] ?? '')) : '' ?>" placeholder="Search equipment...">
      <button type="submit" aria-label="Search">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
      </button>
    </form>
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
