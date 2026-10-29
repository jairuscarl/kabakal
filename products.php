<?php
/** Product list with category filter, in-stock filter + search. */
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/product_card.php';
$page_title = 'Products';

$cat_id = (int)($_GET['category'] ?? 0);
$q      = trim($_GET['q'] ?? '');
$avail  = !empty($_GET['available']);

$sql = "SELECT e.*, c.name AS category_name FROM equipment e
        JOIN category c ON c.category_id = e.category_id WHERE e.status = 'active'";
$params = [];
if ($cat_id) { $sql .= ' AND e.category_id = ?'; $params[] = $cat_id; }
if ($avail) { $sql .= ' AND e.stock_quantity > 0'; }
if ($q !== '') { $sql .= ' AND (e.name LIKE ? OR e.description LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; }
$sql .= ' ORDER BY c.name, e.name';
$st = $pdo->prepare($sql); $st->execute($params);
$products = $st->fetchAll();
$categories = $pdo->query('SELECT category_id, name FROM category ORDER BY name')->fetchAll();

// Builds an HTML-escaped products.php link that keeps the current filters, with $over replacing some of them.
$link = function (array $over) use ($cat_id, $q, $avail) {
  $qs = array_filter(array_merge(['category' => $cat_id, 'q' => $q, 'available' => $avail ? 1 : 0], $over));
  return e(url('products.php' . ($qs ? '?' . http_build_query($qs) : '')));
};

require __DIR__ . '/includes/header.php';
?>
<h1>Products</h1>
<form class="search-bar" method="get" role="search">
  <?php if ($cat_id): ?><input type="hidden" name="category" value="<?= $cat_id ?>"><?php endif; ?>
  <?php if ($avail): ?><input type="hidden" name="available" value="1"><?php endif; ?>
  <label class="sr-only" for="product-search">Search equipment</label>
  <input type="search" id="product-search" name="q" value="<?= e($q) ?>" placeholder="Search equipment...">
  <button type="submit" aria-label="Search">
    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
  </button>
</form>
<div class="chips">
  <a class="chip <?= !$cat_id ? 'active' : '' ?>" href="<?= $link(['category' => 0]) ?>">All</a>
  <?php foreach ($categories as $c): ?>
    <a class="chip <?= $cat_id === (int)$c['category_id'] ? 'active' : '' ?>" href="<?= $link(['category' => (int)$c['category_id']]) ?>"><?= e($c['name']) ?></a>
  <?php endforeach; ?>
  <a class="chip chip-toggle <?= $avail ? 'active' : '' ?>" href="<?= $link(['available' => $avail ? 0 : 1]) ?>" aria-pressed="<?= $avail ? 'true' : 'false' ?>"><?= $avail ? '&#10003; ' : '' ?>Available only</a>
</div>
<?php if (!$products): ?>
  <p class="empty">No products found. Try another category or search word<?= $avail ? ', or show out-of-stock items too' : '' ?>.</p>
<?php else: ?>
  <div class="grid"><?php foreach ($products as $p) product_card($p); ?></div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
