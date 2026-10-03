<?php
/** Product details page. */
require_once __DIR__ . '/includes/init.php';

$st = $pdo->prepare("SELECT e.*, c.name AS category_name FROM equipment e
                     JOIN category c ON c.category_id = e.category_id
                     WHERE e.equipment_id = ? AND e.status = 'active'");
$st->execute([(int)($_GET['id'] ?? 0)]);
$p = $st->fetch();
if (!$p) { flash('error', 'That product could not be found.'); redirect('products.php'); }

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
<?php require __DIR__ . '/includes/footer.php'; ?>
