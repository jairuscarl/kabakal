<?php
/** Product details page. */
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/reviews.php';

$st = $pdo->prepare("SELECT e.*, c.name AS category_name FROM equipment e
                     JOIN category c ON c.category_id = e.category_id
                     WHERE e.equipment_id = ? AND e.status = 'active'");
$st->execute([(int)($_GET['id'] ?? 0)]);
$p = $st->fetch();
if (!$p) { flash('error', 'That product could not be found.'); redirect('products.php'); }

// Reviews: handle POST (post a review / like) first, then load this product's reviews.
review_handle_post('product.php?id=' . (int)$p['equipment_id'], (int)$p['equipment_id']);
$reviews = review_fetch((int)$p['equipment_id']);
$replies = review_replies($reviews);
[$total, $avg] = review_stats($reviews);

// Gallery: main photo first, then the extra photos / videos added in Admin > Products
$slides = array_merge([['media_type' => 'image', 'src' => product_image($p['image_url'])]],
    array_map(fn($m) => ['media_type' => $m['media_type'], 'src' => url($m['file_path'])], product_media($p['equipment_id'])));

$page_title = $p['name'];
require __DIR__ . '/includes/header.php';
?>
<p><a href="<?= url('products.php') ?>">&larr; Back to products</a></p>
<div class="detail">
  <div class="gallery" data-gallery>
    <div class="gallery-stage">
      <div class="gallery-track">
        <?php foreach ($slides as $i => $s): ?>
        <div class="gallery-slide" aria-hidden="<?= $i ? 'true' : 'false' ?>">
          <?php if ($s['media_type'] === 'video'): ?>
            <video src="<?= e($s['src']) ?>" controls playsinline preload="metadata"></video>
          <?php else: ?>
            <img src="<?= e($s['src']) ?>" alt="<?= e($p['name']) ?> — photo <?= $i + 1 ?>">
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
      <?php if (count($slides) > 1): ?>
        <button type="button" class="gallery-nav gallery-prev" aria-label="Previous">&#10094;</button>
        <button type="button" class="gallery-nav gallery-next" aria-label="Next">&#10095;</button>
        <span class="gallery-count">1 / <?= count($slides) ?></span>
      <?php endif; ?>
    </div>
    <?php if (count($slides) > 1): ?>
    <div class="gallery-thumbs">
      <?php foreach ($slides as $i => $s): ?>
      <button type="button" class="gallery-thumb<?= $i ? '' : ' active' ?>" aria-label="Show item <?= $i + 1 ?>">
        <?php if ($s['media_type'] === 'video'): ?>
          <video src="<?= e($s['src']) ?>#t=0.5" preload="metadata" muted></video><span class="play-icon">&#9654;</span>
        <?php else: ?>
          <img src="<?= e($s['src']) ?>" alt="">
        <?php endif; ?>
      </button>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
  <div>
    <span class="card-cat"><?= e($p['category_name']) ?></span>
    <h1><?= e($p['name']) ?></h1>
    <div class="price"><?= peso($p['price']) ?></div>
    <p><?= $p['stock_quantity'] > 0
        ? badge('available') . ' <span class="muted">' . (int)$p['stock_quantity'] . ' in stock</span>'
        : badge('out of stock') ?></p>
    <p><?= nl2br(e($p['description'] ?: 'Contact the shop for more details about this item.')) ?></p>

    <?php if ($p['stock_quantity'] > 0): ?>
      <?= buy_buttons($p, true) ?>
    <?php else: ?>
      <p class="muted">This item is currently out of stock.</p>
    <?php endif; ?>
    <?php $ask = 'messages.php?product=' . (int)$p['equipment_id']; ?>
    <p><a class="btn btn-outline btn-sm" href="<?= url(is_logged_in() ? $ask : 'login.php?next=' . urlencode($ask)) ?>">&#9993; Ask the shop about this product</a></p>
  </div>
</div>
<h2>Customer reviews</h2>
<div class="review-summary">
  <?php if ($reviews): ?>
    <span class="review-avg-num"><?= number_format($avg, 1) ?></span>
    <?= stars_display((int)round($avg)) ?>
    <span class="muted">from <?= (int)$total ?> review<?= $total === 1 ? '' : 's' ?></span>
  <?php else: ?>
    <span class="muted">No reviews yet — be the first to review this product.</span>
  <?php endif; ?>
</div>

<?php if (is_logged_in()): ?>
  <h3>Write a review</h3>
  <?= review_form('product.php?id=' . (int)$p['equipment_id'], (int)$p['equipment_id']) ?>
<?php else: ?>
  <div class="form-box review-form">
    <h3>Write a review</h3>
    <p class="muted">Log in to rate this product 1 to 5 stars, write a comment and add a photo of your gear.</p>
    <a class="btn" href="<?= url('login.php?next=' . urlencode('product.php?id=' . (int)$p['equipment_id'])) ?>">Log in to review</a>
  </div>
<?php endif; ?>

<?php if (!$reviews): ?>
  <p class="empty">No reviews yet. Be the first to tell us how this equipment is holding up!</p>
<?php else: ?>
  <div class="review-list">
    <?php foreach ($reviews as $r): ?>
      <?= review_card($r, $replies, false) ?>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
