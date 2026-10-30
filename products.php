<?php
/** Product list with category filter, in-stock filter + search (the search box is in the header). */
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
  <div class="grid" data-reveal><?php foreach ($products as $p) product_card($p); ?></div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
