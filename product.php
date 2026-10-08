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

$page_title = $p['name'];
require __DIR__ . '/includes/header.php';
?>
<p><a href="<?= url('products.php') ?>">&larr; Back to products</a></p>
<div class="detail">
  <img src="<?= e(product_image($p['image_url'])) ?>" alt="<?= e($p['name']) ?>">
  <div>
    <span class="card-cat"><?= e($p['category_name']) ?></span>
    <h1><?= e($p['name']) ?></h1>
    <div class="price"><?= peso($p['price']) ?></div>
    <p><?= $p['stock_quantity'] > 0
        ? badge('available') . ' <span class="muted">' . (int)$p['stock_quantity'] . ' in stock</span>'
        : badge('out of stock') ?></p>
    <p><?= nl2br(e($p['description'] ?: 'Contact the shop for more details about this item.')) ?></p>

    <?php if ($p['stock_quantity'] > 0): ?>
      <form method="post" action="<?= url('cart.php') ?>" class="buy-row">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="equipment_id" value="<?= (int)$p['equipment_id'] ?>">
        <div><label for="qty">Quantity</label>
          <input type="number" id="qty" name="quantity" value="1" min="1" max="<?= (int)$p['stock_quantity'] ?>"></div>
        <button class="btn" type="submit">Add to cart</button>
      </form>
    <?php else: ?>
      <p class="muted">This item is currently out of stock.</p>
    <?php endif; ?>
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
