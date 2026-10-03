<?php
/** Admin > Customers: searchable list. */
require_once __DIR__ . '/../includes/init.php';
require_admin();

$q = trim($_GET['q'] ?? '');
$sql = 'SELECT c.*, (SELECT COUNT(*) FROM orders o WHERE o.customer_id = c.customer_id) AS order_count FROM customer c';
$params = [];
if ($q !== '') { $sql .= ' WHERE c.full_name LIKE ? OR c.email LIKE ? OR c.phone LIKE ?'; $params = ["%$q%", "%$q%", "%$q%"]; }
$st = $pdo->prepare($sql . ' ORDER BY c.customer_id DESC'); $st->execute($params);
$customers = $st->fetchAll();

$page_title = 'Customers';
require __DIR__ . '/_header.php';
?>
<h1>Customers</h1>
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
