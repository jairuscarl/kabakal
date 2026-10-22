<?php
/** Admin > receipt (store slip) of any fully paid order. ?id=<order> ; &format=pdf downloads it. */
require_once __DIR__ . '/../includes/init.php';
require_admin();
require_once __DIR__ . '/../includes/receipt.php';

$order_id = (int)($_GET['id'] ?? 0);
$r = receipt_data($order_id);
if (!$r) { flash('error', 'This order has no receipt yet. Receipts are available once an order is fully paid.'); redirect('admin/order_view.php?id=' . $order_id); }

if (($_GET['format'] ?? '') === 'pdf') receipt_send_pdf($r);

$page_title = 'Receipt ' . $r['number'];
require __DIR__ . '/_header.php';
?>
<div class="page-head"><h1>Receipt <?= e($r['number']) ?></h1><a href="<?= url('admin/order_view.php?id=' . $r['order_id']) ?>">&larr; Order #<?= $r['order_id'] ?></a></div>
<?= receipt_page($r, url('admin/receipt.php?id=' . $r['order_id'] . '&format=pdf')) ?>
<?php require __DIR__ . '/_footer.php'; ?>
