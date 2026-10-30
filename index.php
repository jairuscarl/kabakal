<?php
/** Home page: hero (boxingkabakal.jpg backdrop), categories, featured products, promo videos, about. */
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/product_card.php';
$page_title = 'Home';
$main_class = '';   // home page uses a full-bleed hero + its own .container sections (no page padding)
$body_class = 'home';   // one full-strength photo backdrop for the whole page (style.css body.home)

// Visible promo videos (newest first)
$videos = $pdo->query("SELECT * FROM promo_video WHERE is_visible = 1 ORDER BY video_id DESC")->fetchAll();

// Categories that have at least one active product
$cats = $pdo->query("SELECT c.category_id, c.name FROM category c
                     WHERE EXISTS (SELECT 1 FROM equipment e WHERE e.category_id = c.category_id AND e.status = 'active')
                     ORDER BY c.name")->fetchAll();

// Featured products (fall back to newest if none are marked)
$featured = $pdo->query("SELECT e.*, c.name AS category_name FROM equipment e JOIN category c ON c.category_id = e.category_id
                         WHERE e.status = 'active' AND e.is_featured = 1 ORDER BY e.equipment_id DESC LIMIT 8")->fetchAll();
if (!$featured) {
    $featured = $pdo->query("SELECT e.*, c.name AS category_name FROM equipment e JOIN category c ON c.category_id = e.category_id
                             WHERE e.status = 'active' ORDER BY e.equipment_id DESC LIMIT 8")->fetchAll();
}
require __DIR__ . '/includes/header.php';
?>
<section class="hero">
  <?php /* Backdrop is only the boxingkabakal.jpg photo (set in style.css .hero); promo videos stay in their own section below. */ ?>
  <div class="container hero-content">
    <h1>Build your <span>strength</span>.<br>Build your gym.</h1>
    <p>Boxing gear, gym equipment and machines for gym owners, athletes and home workouts.</p>
    <?php /* Call-to-action row: the Shop equipment button with the social icons (Facebook / Instagram /
             Google Maps) right beside it. Each icon prints only when its link is filled in Admin > Settings. */ ?>
    <div class="hero-cta">
      <a class="btn" href="<?= url('products.php') ?>">Shop equipment</a>
      <?php if (setting('shop_facebook') || setting('shop_instagram') || setting('shop_map')): ?>
      <div class="hero-social">
        <?php if (setting('shop_facebook')): ?>
          <a href="<?= e(setting('shop_facebook')) ?>" target="_blank" rel="noopener noreferrer" title="Facebook" aria-label="Facebook">
            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
              <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z" />
            </svg>
          </a>
        <?php endif; ?>
        <?php if (setting('shop_instagram')): ?>
          <a href="<?= e(setting('shop_instagram')) ?>" target="_blank" rel="noopener noreferrer" title="Instagram" aria-label="Instagram">
            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
              <rect x="2" y="2" width="20" height="20" rx="5" ry="5" />
              <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z" />
              <line x1="17.5" y1="6.5" x2="17.51" y2="6.5" />
            </svg>
          </a>
        <?php endif; ?>
        <?php if (setting('shop_map')): ?>
          <a href="<?= e(setting('shop_map')) ?>" target="_blank" rel="noopener noreferrer" title="Find us on Google Maps" aria-label="Find us on Google Maps">
            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
              <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" />
              <circle cx="12" cy="10" r="3" />
            </svg>
          </a>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<div class="container">
  <?php show_flash(); ?>

  <?php if ($cats): ?>
  <section class="section">
    <h2 class="section-title">Shop by category</h2>
    <div class="grid">
      <?php foreach ($cats as $c): ?>
        <a class="cat-tile" href="<?= url('products.php?category=' . (int)$c['category_id']) ?>"><?= e($c['name']) ?></a>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <section class="section">
    <h2 class="section-title">Featured products</h2>
    <?php if (!$featured): ?><p class="muted">No products yet. Please check back soon.</p><?php endif; ?>
    <div class="grid"><?php foreach ($featured as $p) product_card($p); ?></div>
  </section>

  <?php if ($videos): ?>
  <section class="section">
    <h2 class="section-title">Watch Kabakal in action</h2>
    <div class="video-grid">
      <?php foreach ($videos as $v): ?>
        <div class="video-card">
          <?php if ($v['source_type'] === 'facebook'): ?>
            <iframe src="<?= e(facebook_embed_src($v['embed_url'])) ?>" loading="lazy" allowfullscreen
                    allow="autoplay; clipboard-write; encrypted-media; picture-in-picture" title="<?= e($v['title']) ?>"></iframe>
          <?php else: ?>
            <video controls preload="metadata" playsinline poster="<?= e($v['poster'] ? url($v['poster']) : '') ?>">
              <source src="<?= e(url($v['file_path'])) ?>" type="video/mp4">
            </video>
          <?php endif; ?>
          <h3><?= e($v['title']) ?></h3>
          <?php if ($v['source_type'] === 'facebook'): ?>
            <p class="video-fallback muted">Player not loading? <a href="<?= e($v['embed_url']) ?>" target="_blank" rel="noopener">Watch this video on Facebook &rarr;</a></p>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <section class="section">
    <h2 class="section-title">About Kabakal</h2>
    <p style="max-width:70ch"><?= e(setting('shop_about')) ?></p>
    <p class="muted">Visit us: <?= e(setting('shop_address')) ?> &nbsp;|&nbsp; <?= e(setting('shop_phone')) ?></p>
  </section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
