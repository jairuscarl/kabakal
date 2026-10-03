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
$recent = array_slice(fetch_orders(), 0, 8);

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
<div class="page-head"><h2>Recent orders</h2><a class="btn btn-sm btn-outline" href="<?= url('admin/orders.php') ?>">All orders</a></div>
<div class="table-wrap"><table>
  <tr><th>Order</th><th>Customer</th><th>Date</th><th>Total</th><th>Status</th><th>Payment</th><th></th></tr>
  <?php foreach ($recent as $o): ?>
    <tr><td>#<?= (int)$o['order_id'] ?></td><td><?= e($o['customer_name']) ?></td><td><?= e($o['order_date']) ?></td>
        <td><?= peso($o['total_amount']) ?></td><td><?= badge($o['status']) ?></td><td><?= badge($o['pay_state']) ?></td>
        <td><a class="btn btn-sm btn-outline" href="<?= url('admin/order_view.php?id=' . (int)$o['order_id']) ?>">Open</a></td></tr>
  <?php endforeach; ?>
  <?php if (!$recent): ?><tr><td colspan="7" class="empty">No orders yet.</td></tr><?php endif; ?>
</table></div>
<?php require __DIR__ . '/_footer.php'; ?>
