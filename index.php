<?php
/** Home page: hero video, categories, featured products, promo videos, about. */
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/product_card.php';
$page_title = 'Home';
$main_class = '';   // home page uses a full-bleed hero + its own .container sections (no page padding)

// Visible promo videos (newest first)
$videos = $pdo->query("SELECT * FROM promo_video WHERE is_visible = 1 ORDER BY video_id DESC")->fetchAll();
$hero_video = null;                                   // first uploaded MP4 becomes the hero background
foreach ($videos as $v) { if ($v['source_type'] === 'file' && $v['file_path']) { $hero_video = $v; break; } }

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
  <?php if ($hero_video): ?>
    <!-- muted + autoplay + loop; poster and preload="metadata" keep the page light -->
    <video autoplay muted loop playsinline preload="metadata"
           poster="<?= e($hero_video['poster'] ? url($hero_video['poster']) : '') ?>">
      <source src="<?= e(url($hero_video['file_path'])) ?>" type="video/mp4">
    </video>
  <?php endif; ?>
  <div class="container hero-content">
    <h1>Build your <span>strength</span>.<br>Build your gym.</h1>
    <p>Boxing gear, gym equipment and machines for gym owners, athletes and home workouts.</p>
    <a class="btn" href="<?= url('products.php') ?>">Shop equipment</a>
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
