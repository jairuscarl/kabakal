<?php
/** Admin dashboard: totals + sales summary + recent orders. */
require_once __DIR__ . '/../includes/init.php';
require_admin();

$count = fn($sql) => (int)$pdo->query($sql)->fetchColumn();
$stats = [
    'Products'             => $count("SELECT COUNT(*) FROM equipment"),
    'Orders'               => $count("SELECT COUNT(*) FROM orders"),
    'Customers'            => $count("SELECT COUNT(*) FROM customer"),
    'Pending verification' => $count("SELECT COUNT(*) FROM payment WHERE payment_status = 'pending'"),
    'Out of stock items'   => $count("SELECT COUNT(*) FROM equipment WHERE stock_quantity = 0 AND status = 'active'"),
];
$sales_total = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM payment WHERE payment_status = 'paid'")->fetchColumn();
$sales_month = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM payment WHERE payment_status = 'paid'
                                   AND YEAR(payment_date) = YEAR(CURDATE()) AND MONTH(payment_date) = MONTH(CURDATE())")->fetchColumn();
$receivables = 0;                                   // money still owed by customers (not cancelled orders)
foreach (fetch_orders("AND o.status <> 'cancelled'") as $o) { $receivables += $o['balance']; }

// ---------- Delete an order (full admins only) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['action'] ?? '') === 'delete_order') {
        require_admin(['admin']);
        $id = (int)($_POST['order_id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare('DELETE FROM orders WHERE order_id = ?')->execute([$id]);
            $pdo->prepare('DELETE FROM order_item WHERE order_id = ?')->execute([$id]);
            flash('success', 'Order deleted.');
        }
        redirect('admin/index.php');
    }
}
$recent = array_slice(fetch_orders(), 0, 8);

// ---------- Item performance: units sold per product (cancelled orders excluded) ----------
$perf_rows = $pdo->query("SELECT e.equipment_id, e.name,
                                 COALESCE(SUM(CASE WHEN o.status <> 'cancelled' THEN oi.quantity END), 0) AS units_sold,
                                 COALESCE(SUM(CASE WHEN o.status <> 'cancelled' THEN oi.subtotal END), 0) AS revenue
                          FROM equipment e
                          LEFT JOIN order_item oi ON oi.equipment_id = e.equipment_id
                          LEFT JOIN orders o      ON o.order_id = oi.order_id
                          WHERE e.status <> 'discontinued'
                          GROUP BY e.equipment_id, e.name
                          ORDER BY units_sold DESC, revenue DESC, e.name")->fetchAll();

// Products that actually sold something are split into thirds (best first):
// top third = High Performing, middle third = Moderate Performing, rest = Low Performing.
// Products with no sales at all always count as Low Performing.
$sellers = $no_sales = [];
foreach ($perf_rows as $row) { if ($row['units_sold'] > 0) $sellers[] = $row; else $no_sales[] = $row; }
$hi_end  = (int)ceil(count($sellers) / 3);
$mid_end = (int)ceil(count($sellers) * 2 / 3);
$perf = ['high' => [], 'moderate' => [], 'low' => []];
foreach ($sellers as $i => $row) {
    if ($i < $hi_end)      $perf['high'][]     = $row;
    elseif ($i < $mid_end) $perf['moderate'][] = $row;
    else                   $perf['low'][]      = $row;
}
$perf['low'] = array_merge($perf['low'], $no_sales);

$page_title = 'Dashboard';
require __DIR__ . '/_header.php';
?>
<h1>Dashboard</h1>
<div class="stats">
  <?php foreach ($stats as $label => $n): ?><div class="stat"><b><?= $n ?></b><span><?= e($label) ?></span></div><?php endforeach; ?>
  <div class="stat"><b><?= peso($sales_total) ?></b><span>Total sales (verified)</span></div>
  <div class="stat"><b><?= peso($sales_month) ?></b><span>Sales this month</span></div>
  <div class="stat"><b><?= peso($receivables) ?></b><span>Unpaid balances</span></div>
</div>
<div class="page-head"><h2>Item performance</h2><span class="muted">Based on units sold &middot; cancelled orders not counted</span></div>
<div class="perf-grid">
  <?php foreach ([['high', 'High Performing Items'], ['moderate', 'Moderate Performing Items'], ['low', 'Low Performing Items']] as [$key, $label]): ?>
    <div class="perf-col perf-<?= $key ?>">
      <h3><?= e($label) ?><span class="perf-count"><?= count($perf[$key]) ?></span></h3>
      <?php if (!$perf[$key]): ?>
        <p class="muted">Nothing in this group yet.</p>
      <?php else: ?>
        <ul class="perf-list">
          <?php foreach ($perf[$key] as $r): ?>
            <li><span class="perf-name"><?= e($r['name']) ?></span>
                <span class="perf-meta"><?= (int)$r['units_sold'] ?> sold &middot; <?= peso($r['revenue']) ?></span></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>
<div class="page-head"><h2>Recent orders</h2><a class="btn btn-sm btn-outline" href="<?= url('admin/orders.php') ?>">All orders</a></div>
<div class="table-wrap"><table>
  <tr><th>Order</th><th>Customer</th><th>Date</th><th>Total</th><th>Status</th><th>Payment</th><th></th></tr>
  <?php foreach ($recent as $o): ?>
    <tr>
      <td>#<?= (int)$o['order_id'] ?></td><td><?= e($o['customer_name']) ?></td><td><?= e($o['order_date']) ?></td>
      <td><?= peso($o['total_amount']) ?></td><td><?= badge($o['status']) ?></td><td><?= badge($o['pay_state']) ?></td>
      <td class="actions">
        <a class="btn btn-sm btn-outline" href="<?= url('admin/order_view.php?id=' . (int)$o['order_id']) ?>">Open</a>
        <form method="post" class="inline-form" data-confirm="Delete this order and its items?">
          <?= csrf_field() ?><input type="hidden" name="action" value="delete_order">
          <input type="hidden" name="order_id" value="<?= (int)$o['order_id'] ?>">
          <button class="btn btn-sm btn-danger" type="submit">Delete</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$recent): ?><tr><td colspan="7" class="empty">No orders yet.</td></tr><?php endif; ?>
</table></div>
<?php require __DIR__ . '/_footer.php'; ?>
