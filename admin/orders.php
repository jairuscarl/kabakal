<?php
/** Admin > Orders: list with filters (status, payment: unpaid / partial / pending / paid). */
require_once __DIR__ . '/../includes/init.php';
require_admin();

$status = $_GET['status'] ?? ''; $pay = $_GET['pay'] ?? ''; $q = trim($_GET['q'] ?? '');
$where = ''; $params = [];
if (in_array($status, ['pending', 'paid', 'shipped', 'delivered', 'cancelled'], true)) { $where .= ' AND o.status = ?'; $params[] = $status; }
if ($q !== '') { $where .= ' AND (c.full_name LIKE ? OR o.order_id = ?)'; $params[] = "%$q%"; $params[] = (int)$q; }
$orders = fetch_orders($where, $params);

// Payment filter is done in PHP because pay_state is calculated
$pay_map = ['unpaid' => 'Unpaid', 'partial' => 'Partially Paid', 'pending' => 'Pending Verification', 'paid' => 'Paid'];
if (isset($pay_map[$pay])) {
    $orders = array_filter($orders, fn($o) => $o['pay_state'] === $pay_map[$pay] && $o['status'] !== 'cancelled');
}

$page_title = 'Orders';
require __DIR__ . '/_header.php';
?>
<h1>Orders</h1>
<form class="filter-bar" method="get">
  <input type="search" name="q" value="<?= e($q) ?>" placeholder="Customer name or order #">
  <select name="status"><option value="">All order statuses</option>
    <?php foreach (['pending', 'paid', 'shipped', 'delivered', 'cancelled'] as $s): ?><option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option><?php endforeach; ?></select>
  <select name="pay"><option value="">All payment states</option>
    <?php foreach ($pay_map as $k => $label): ?><option value="<?= $k ?>" <?= $pay === $k ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select>
  <button class="btn btn-sm" type="submit">Filter</button>
</form>
<div class="table-wrap"><table>
  <tr><th>Order</th><th>Customer</th><th>Date</th><th>Total</th><th>Paid</th><th>Balance</th><th>Status</th><th>Payment</th><th></th></tr>
  <?php foreach ($orders as $o): ?>
  <tr>
    <td>#<?= (int)$o['order_id'] ?></td><td><?= e($o['customer_name']) ?></td><td><?= e($o['order_date']) ?></td>
    <td><?= peso($o['total_amount']) ?></td><td><?= peso($o['paid_amount']) ?></td>
    <td><?= $o['status'] === 'cancelled' ? '-' : peso($o['balance']) ?></td>
    <td><?= badge($o['status']) ?></td><td><?= badge($o['pay_state']) ?></td>
    <td class="actions">
      <a class="btn btn-sm btn-outline" href="<?= url('admin/order_view.php?id=' . (int)$o['order_id']) ?>">Details</a>
      <form method="post" class="inline-form" data-confirm="Delete this order and its items?">
        <?= csrf_field() ?><input type="hidden" name="action" value="delete_order">
        <input type="hidden" name="order_id" value="<?= (int)$o['order_id'] ?>">
        <button class="btn btn-sm btn-danger" type="submit">Delete</button>
      </form>
    </td>
  </tr>
  <?php endforeach; ?>
  <?php if (!$orders): ?><tr><td colspan="9" class="empty">No orders found.</td></tr><?php endif; ?>
</table></div>
<?php require __DIR__ . '/_footer.php'; ?>
