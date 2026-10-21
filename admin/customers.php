<?php
/** Admin > Customers: searchable list. */
require_once __DIR__ . '/../includes/init.php';
require_admin();

require_once __DIR__ . '/../includes/reports.php';   // customers_report_rows(): same list the Excel/PDF/CSV download uses
$q = trim($_GET['q'] ?? '');
$customers = customers_report_rows($q);

$page_title = 'Customers';
require __DIR__ . '/_header.php';
?>
<div class="page-head"><h1>Customers</h1><?= export_buttons('customers', report_filters($_GET)) ?></div>
<form class="filter-bar" method="get"><input type="search" name="q" value="<?= e($q) ?>" placeholder="Name, email or phone"><button class="btn btn-sm" type="submit">Search</button></form>
<div class="table-wrap"><table>
  <tr><th>Name</th><th>Email</th><th>Phone</th><th>Orders</th><th>Joined</th><th></th></tr>
  <?php foreach ($customers as $c): ?>
  <tr><td><?= e($c['full_name']) ?></td><td><?= e($c['email']) ?></td><td><?= e($c['phone']) ?></td><td><?= (int)$c['order_count'] ?></td>
      <td><?= e(date('M j, Y', strtotime($c['created_at']))) ?></td>
      <td><a class="btn btn-sm btn-outline" href="<?= url('admin/customer_view.php?id=' . (int)$c['customer_id']) ?>">Details</a></td></tr>
  <?php endforeach; ?>
  <?php if (!$customers): ?><tr><td colspan="6" class="empty">No customers found.</td></tr><?php endif; ?>
</table></div>
<?php require __DIR__ . '/_footer.php'; ?>
