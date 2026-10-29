<?php
/** Admin dashboard: totals + monthly sales chart + item performance + recent orders. */
require_once __DIR__ . '/../includes/init.php';
require_admin();

require_once __DIR__ . '/../includes/reports.php';   // dashboard_summary() + item_performance() + monthly_sales(): also used by the Sales Report download

[$stats, $sales_total, $sales_month, $receivables] = dashboard_summary();

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

// ---------- Item performance: units sold per product (cancelled orders excluded), High / Moderate / Low ----------
$perf = item_performance();

// ---------- Sales chart: verified sales per month, last 12 months ----------
$monthly = monthly_sales();
$peak = max(array_column($monthly, 'sales'));
// y-axis top: the peak rounded up to a "nice" number (1, 1.2, 1.5, 2 ... 8 x 10^n), so the gridlines read cleanly
$axis_max = 1000;
if ($peak > 0) {
    $mag = 10 ** floor(log10($peak));
    foreach ([1, 1.2, 1.5, 2, 2.5, 3, 4, 5, 6, 8, 10] as $m) { if ($m * $mag >= $peak) { $axis_max = $m * $mag; break; } }
}
$peak_i = $peak > 0 ? array_search($peak, array_column($monthly, 'sales')) : -1;

$page_title = 'Dashboard';
require __DIR__ . '/_header.php';
?>
<div class="page-head"><h1>Dashboard</h1><?= export_buttons('sales') ?></div>
<div class="stats">
  <?php foreach ($stats as $label => $n): ?><div class="stat"><b><?= $n ?></b><span><?= e($label) ?></span></div><?php endforeach; ?>
  <div class="stat"><b><?= peso($sales_total) ?></b><span>Total sales (verified)</span></div>
  <div class="stat"><b><?= peso($sales_month) ?></b><span>Sales this month</span></div>
  <div class="stat"><b><?= peso($receivables) ?></b><span>Unpaid balances</span></div>
</div>
<div class="page-head"><h2>Sales per month</h2><span class="muted">Verified payments &middot; last 12 months</span></div>
<div class="sales-chart">
  <div class="sc-plot" role="img" aria-label="Bar chart of verified sales per month for the last 12 months. The same numbers are in the table below.">
    <?php foreach ([1, .5, 0] as $f): ?>
      <div class="sc-grid" style="bottom: <?= $f * 100 ?>%"><span><?= $f ? '&#8369;' . number_format($axis_max * $f) : '0' ?></span></div>
    <?php endforeach; ?>
    <div class="sc-bars">
      <?php foreach ($monthly as $i => $m): $label = date('M Y', strtotime($m['ym'] . '-01')); ?>
        <div class="sc-col<?= $i === count($monthly) - 1 ? ' sc-now' : '' ?>" tabindex="0" aria-label="<?= e($label . ': ' . peso($m['sales']) . ', ' . $m['orders'] . ' orders') ?>">
          <div class="sc-bar" style="height: <?= round($m['sales'] / $axis_max * 100, 2) ?>%">
            <?php if ($i === $peak_i): ?><span class="sc-value"><?= peso($m['sales']) ?></span><?php endif; ?>
          </div>
          <div class="sc-tip" aria-hidden="true"><b><?= e($label) ?></b><?= peso($m['sales']) ?><span><?= (int)$m['orders'] ?> order<?= $m['orders'] == 1 ? '' : 's' ?> placed</span></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="sc-months" aria-hidden="true">
    <?php foreach ($monthly as $i => $m): $t = strtotime($m['ym'] . '-01'); ?>
      <span><?= date('M', $t) ?><?php if ($i === 0 || date('n', $t) == 1): ?><small><?= date('Y', $t) ?></small><?php endif; ?></span>
    <?php endforeach; ?>
  </div>
  <details class="sc-table">
    <summary>Show as table</summary>
    <div class="table-wrap"><table>
      <tr><th>Month</th><th>Verified sales</th><th>Orders placed</th></tr>
      <?php foreach (array_reverse($monthly) as $m): ?>
        <tr><td><?= date('F Y', strtotime($m['ym'] . '-01')) ?></td><td><?= peso($m['sales']) ?></td><td><?= (int)$m['orders'] ?></td></tr>
      <?php endforeach; ?>
    </table></div>
  </details>
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
