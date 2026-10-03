<?php
/** Checkout: review cart, enter address, place order. Login required. */
require_once __DIR__ . '/includes/init.php';
require_login();

$items = cart_items();
if (!$items) { flash('info', 'Your cart is empty.'); redirect('cart.php'); }

$st = $pdo->prepare('SELECT * FROM customer WHERE customer_id = ?');
$st->execute([$_SESSION['customer_id']]);
$customer = $st->fetch();

$address = $customer['address'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address = trim($_POST['shipping_address'] ?? '');
    if (strlen($address) < 10 || strlen($address) > 255) {
        flash('error', 'Please enter a complete delivery address (10 to 255 characters).');
    } else {
        $pdo->beginTransaction();
        try {
            // Lock each product row so two customers cannot buy the last item at the same time
            $lines = []; $total = 0;
            $lock = $pdo->prepare("SELECT equipment_id, name, price, stock_quantity FROM equipment
                                   WHERE equipment_id = ? AND status = 'active' FOR UPDATE");
            foreach ($items as $it) {
                $lock->execute([$it['equipment_id']]);
                $p = $lock->fetch();
                if (!$p || $p['stock_quantity'] < $it['quantity']) {
                    throw new RuntimeException('Not enough stock for "' . $it['name'] . '". Please update your cart.');
                }
                $lines[] = ['id' => $p['equipment_id'], 'price' => $p['price'], 'qty' => $it['quantity']];
                $total  += $p['price'] * $it['quantity'];       // price always comes from the database
            }

            $pdo->prepare('INSERT INTO orders (customer_id, total_amount, status, shipping_address) VALUES (?, ?, ?, ?)')
                ->execute([$customer['customer_id'], $total, 'pending', $address]);
            $order_id = (int)$pdo->lastInsertId();

            $ins = $pdo->prepare('INSERT INTO order_item (order_id, equipment_id, quantity, unit_price) VALUES (?, ?, ?, ?)');
            foreach ($lines as $l) {
                $ins->execute([$order_id, $l['id'], $l['qty'], $l['price']]);
                inventory_move($l['id'], 'available', 'reserved', $l['qty']);   // reduce stock + reserve it
            }
            // Close this cart; a fresh one is created next time
            $pdo->prepare("UPDATE cart SET status = 'checked_out' WHERE cart_id = ?")->execute([cart_id()]);
            $pdo->commit();
            flash('success', "Order #$order_id placed! Please send your GCash payment below.");
            redirect("order_confirm.php?id=$order_id");
        } catch (RuntimeException $ex) {
            $pdo->rollBack();
            flash('error', $ex->getMessage());
            redirect('cart.php');
        } catch (Throwable $ex) { $pdo->rollBack(); throw $ex; }
    }
}

$total = 0; foreach ($items as $it) { $total += $it['price'] * $it['quantity']; }
$page_title = 'Checkout';
require __DIR__ . '/includes/header.php';
?>
<h1>Checkout</h1>
<div class="two-col">
  <div>
    <div class="table-wrap"><table>
      <tr><th>Product</th><th>Qty</th><th>Subtotal</th></tr>
      <?php foreach ($items as $it): ?>
        <tr><td><?= e($it['name']) ?></td><td><?= (int)$it['quantity'] ?></td><td><?= peso($it['price'] * $it['quantity']) ?></td></tr>
      <?php endforeach; ?>
    </table></div>
    <form method="post" class="form-box form-wide">
      <?= csrf_field() ?>
      <div class="form-group"><label for="addr">Delivery address</label>
        <textarea id="addr" name="shipping_address" required><?= e($address) ?></textarea></div>
      <p class="help">Payment is by GCash. After placing your order you will see the shop's GCash details and can upload your receipt.</p>
      <button class="btn" type="submit">Place order</button>
    </form>
  </div>
  <aside class="summary">
    <h3>Order summary</h3>
    <div class="row total"><span>Total to pay</span><span><?= peso($total) ?></span></div>
  </aside>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
