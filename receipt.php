<?php
/** Customer receipt (store slip) for one of their own fully paid orders. ?id=<order> ; &format=pdf downloads it. */
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/receipt.php';
require_login();

$order_id = (int)($_GET['id'] ?? 0);
$r = receipt_data($order_id, (int)$_SESSION['customer_id']);   // only the customer's own, paid orders
if (!$r) { flash('error', 'There is no receipt for that order yet. Receipts are available once an order is fully paid.'); redirect('my_account.php?tab=orders'); }

if (($_GET['format'] ?? '') === 'pdf') receipt_send_pdf($r);

$page_title = 'Receipt ' . $r['number'];
require __DIR__ . '/includes/header.php';
?>
<p><a href="<?= url('order_confirm.php?id=' . $r['order_id']) ?>">&larr; Back to order #<?= $r['order_id'] ?></a></p>
<h1>Receipt</h1>
<?= receipt_page($r, url('receipt.php?id=' . $r['order_id'] . '&format=pdf')) ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
