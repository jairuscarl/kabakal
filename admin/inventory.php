<?php
/**
 * Admin > Inventory: see Available / Reserved / Sold / Damaged / Out of Stock per product
 * and adjust stock (restock, mark damaged, return repaired items).
 * Reserved and Sold numbers are updated automatically by orders.
 */
require_once __DIR__ . '/../includes/init.php';
require_admin();
$aid = $_SESSION['admin_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id  = (int)($_POST['equipment_id'] ?? 0);
    $qty = (int)($_POST['qty'] ?? 0);
    $act = $_POST['adjust'] ?? '';

    $st = $pdo->prepare("SELECT e.stock_quantity, COALESCE((SELECT quantity FROM inventory WHERE equipment_id = e.equipment_id AND status = 'damaged'), 0) AS damaged
                         FROM equipment e WHERE e.equipment_id = ?");
    $st->execute([$id]);
    $row = $st->fetch();

    if (!$row || $qty < 1)                                   flash('error', 'Enter a quantity of 1 or more.');
    elseif ($act === 'restock')                              { inv_change($id, 'available', $qty, $aid); flash('success', "Added $qty to available stock."); }
    elseif ($act === 'damaged' && $qty > $row['stock_quantity']) flash('error', 'Not enough available stock to mark as damaged.');
    elseif ($act === 'damaged')                              { inventory_move($id, 'available', 'damaged', $qty, $aid); flash('success', "$qty marked as damaged."); }
    elseif ($act === 'repaired' && $qty > $row['damaged'])   flash('error', 'That is more than the damaged quantity.');
    elseif ($act === 'repaired')                             { inventory_move($id, 'damaged', 'available', $qty, $aid); flash('success', "$qty returned to available stock."); }
    else                                                     flash('error', 'Unknown action.');
    redirect('admin/inventory.php?' . http_build_query(['view' => $_POST['view'] ?? '', 'q' => $_POST['q'] ?? '']));
}

$view = $_GET['view'] ?? ''; $q = trim($_GET['q'] ?? '');
$sql = "SELECT e.equipment_id, e.name, c.name AS category_name, e.stock_quantity AS available,
          COALESCE(SUM(CASE WHEN i.status = 'reserved' THEN i.quantity END), 0) AS reserved,
          COALESCE(SUM(CASE WHEN i.status = 'sold'     THEN i.quantity END), 0) AS sold,
          COALESCE(SUM(CASE WHEN i.status = 'damaged'  THEN i.quantity END), 0) AS damaged
        FROM equipment e JOIN category c ON c.category_id = e.category_id
        LEFT JOIN inventory i ON i.equipment_id = e.equipment_id
        WHERE e.status <> 'discontinued'";
$params = [];
if ($q !== '') { $sql .= ' AND e.name LIKE ?'; $params[] = "%$q%"; }
$sql .= ' GROUP BY e.equipment_id, e.name, c.name, e.stock_quantity';
$having = ['available' => 'available > 0', 'reserved' => 'reserved > 0', 'sold' => 'sold > 0', 'damaged' => 'damaged > 0', 'out_of_stock' => 'available = 0'];
if (isset($having[$view])) $sql .= ' HAVING ' . $having[$view];
$st = $pdo->prepare($sql . ' ORDER BY c.name, e.name'); $st->execute($params);
$rows = $st->fetchAll();

$page_title = 'Inventory';
require __DIR__ . '/_header.php';
?>
<h1>Inventory</h1>
<form class="filter-bar" method="get">
  <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search product...">
  <select name="view">
    <option value="">All statuses</option>
    <?php foreach (['available' => 'Available', 'reserved' => 'Reserved', 'sold' => 'Sold', 'damaged' => 'Damaged', 'out_of_stock' => 'Out of Stock'] as $k => $label): ?>
      <option value="<?= $k ?>" <?= $view === $k ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?>
  </select>
  <button class="btn btn-sm" type="submit">Filter</button>
</form>
<div class="table-wrap"><table>
  <tr><th>Product</th><th>Category</th><th>Available</th><th>Reserved</th><th>Sold</th><th>Damaged</th><th>Status</th><th>Adjust stock</th></tr>
  <?php foreach ($rows as $r): ?>
  <tr>
    <td><?= e($r['name']) ?></td><td><?= e($r['category_name']) ?></td>
    <td><b><?= (int)$r['available'] ?></b></td><td><?= (int)$r['reserved'] ?></td><td><?= (int)$r['sold'] ?></td><td><?= (int)$r['damaged'] ?></td>
    <td><?= $r['available'] > 0 ? badge('available') : badge('out of stock') ?></td>
    <td>
      <form method="post" class="actions">
        <?= csrf_field() ?><input type="hidden" name="equipment_id" value="<?= (int)$r['equipment_id'] ?>">
        <input type="hidden" name="view" value="<?= e($view) ?>"><input type="hidden" name="q" value="<?= e($q) ?>">
        <input type="number" name="qty" min="1" value="1" aria-label="Quantity" style="width:80px">
        <select name="adjust" style="width:auto" aria-label="Action">
          <option value="restock">Restock (+)</option><option value="damaged">Mark damaged</option><option value="repaired">Damaged &rarr; available</option></select>
        <button class="btn btn-sm" type="submit">Apply</button>
      </form>
    </td>
  </tr>
  <?php endforeach; ?>
  <?php if (!$rows): ?><tr><td colspan="8" class="empty">Nothing matches.</td></tr><?php endif; ?>
</table></div>
<p class="help">Reserved = ordered but not yet paid. Sold = paid, shipped or delivered orders. Cancelled orders return their items to Available.</p>
<?php require __DIR__ . '/_footer.php'; ?>
