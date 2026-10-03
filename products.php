<?php
/** Product list with category filter + search. */
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/product_card.php';
$page_title = 'Products';

$cat_id = (int)($_GET['category'] ?? 0);
$q      = trim($_GET['q'] ?? '');

$sql = "SELECT e.*, c.name AS category_name FROM equipment e
        JOIN category c ON c.category_id = e.category_id WHERE e.status = 'active'";
$params = [];
if ($cat_id) { $sql .= ' AND e.category_id = ?'; $params[] = $cat_id; }
if ($q !== '') { $sql .= ' AND (e.name LIKE ? OR e.description LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; }
$sql .= ' ORDER BY c.name, e.name';
$st = $pdo->prepare($sql); $st->execute($params);
$products = $st->fetchAll();
$categories = $pdo->query('SELECT category_id, name FROM category ORDER BY name')->fetchAll();

require __DIR__ . '/includes/header.php';
?>
<h1>Products</h1>
<form class="filter-bar" method="get">
  <?php if ($cat_id): ?><input type="hidden" name="category" value="<?= $cat_id ?>"><?php endif; ?>
  <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search equipment...">
  <button class="btn btn-sm" type="submit">Search</button>
</form>
<div class="chips">
  <a class="chip <?= !$cat_id ? 'active' : '' ?>" href="<?= url('products.php') ?>">All</a>
  <?php foreach ($categories as $c): ?>
    <a class="chip <?= $cat_id === (int)$c['category_id'] ? 'active' : '' ?>" href="<?= url('products.php?category=' . (int)$c['category_id']) ?>"><?= e($c['name']) ?></a>
  <?php endforeach; ?>
</div>
<?php if (!$products): ?>
  <p class="empty">No products found. Try another category or search word.</p>
<?php else: ?>
  <div class="grid"><?php foreach ($products as $p) product_card($p); ?></div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
