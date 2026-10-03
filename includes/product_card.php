<?php
/** Prints one product card. $p needs: equipment_id, name, price, image_url, stock_quantity, category_name */
function product_card($p) { ?>
  <article class="card">
    <a href="<?= url('product.php?id=' . (int)$p['equipment_id']) ?>">
      <img src="<?= e(product_image($p['image_url'])) ?>" alt="<?= e($p['name']) ?>" loading="lazy">
    </a>
    <div class="card-body">
      <span class="card-cat"><?= e($p['category_name']) ?></span>
      <h3><?= e($p['name']) ?></h3>
      <span class="price"><?= peso($p['price']) ?></span>
      <?= $p['stock_quantity'] > 0 ? badge('available') : badge('out of stock') ?>
      <a class="btn btn-sm" href="<?= url('product.php?id=' . (int)$p['equipment_id']) ?>">View details</a>
    </div>
  </article>
<?php }
