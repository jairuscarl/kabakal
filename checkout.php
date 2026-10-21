<?php
/** Checkout: review cart, enter address, place order. Login required. */
require_once __DIR__ . '/includes/init.php';
require_login();

// ?buy_now=1 checks out only the item from the "Buy now" button (kept in the session), not the cart
$buy_now = isset($_GET['buy_now']);
$items = $buy_now ? cart_items($_SESSION['buy_now'] ?? []) : cart_items();
if (!$items) { flash('info', $buy_now ? 'Please choose a product to buy.' : 'Your cart is empty.'); redirect($buy_now ? 'products.php' : 'cart.php'); }
$methods = ['gcash' => 'GCash', 'cod' => 'Cash on Delivery'];
$method = 'gcash';

$st = $pdo->prepare('SELECT * FROM customer WHERE customer_id = ?');
$st->execute([$_SESSION['customer_id']]);
$customer = $st->fetch();

$address = $customer['address'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address = trim($_POST['shipping_address'] ?? '');
    $method  = $_POST['payment_method'] ?? '';
    if (strlen($address) < 10 || strlen($address) > 255) {
        flash('error', 'Please enter a complete delivery address (10 to 255 characters).');
    } elseif (!isset($methods[$method])) {
        flash('error', 'Please choose how you want to pay.');
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

            $pdo->prepare('INSERT INTO orders (customer_id, total_amount, payment_method, status, shipping_address) VALUES (?, ?, ?, ?, ?)')
                ->execute([$customer['customer_id'], $total, $method, 'pending', $address]);
            $order_id = (int)$pdo->lastInsertId();

            $ins = $pdo->prepare('INSERT INTO order_item (order_id, equipment_id, quantity, unit_price) VALUES (?, ?, ?, ?)');
            foreach ($lines as $l) {
                $ins->execute([$order_id, $l['id'], $l['qty'], $l['price']]);
                inventory_move($l['id'], 'available', 'reserved', $l['qty']);   // reduce stock + reserve it
            }
            if ($buy_now) {
                unset($_SESSION['buy_now']);              // the cart itself was not used, so it stays as it is
            } else {
                // Close this cart; a fresh one is created next time
                $pdo->prepare("UPDATE cart SET status = 'checked_out' WHERE cart_id = ?")->execute([cart_id()]);
            }
            $pdo->commit();
            if ($method === 'cod') {
                notify($customer['customer_id'], "Order #$order_id placed (Cash on Delivery). Pay " . peso($total) . ' in cash when it arrives.', $order_id);
                flash('success', "Order #$order_id placed! Please prepare " . peso($total) . ' in cash for the delivery.');
            } else {
                notify($customer['customer_id'], "Order #$order_id placed. Send your GCash payment so we can confirm it.", $order_id);
                flash('success', "Order #$order_id placed! Please send your GCash payment below.");
            }
            redirect("order_confirm.php?id=$order_id");
        } catch (RuntimeException $ex) {
            $pdo->rollBack();
            flash('error', $ex->getMessage());
            redirect($buy_now ? 'products.php' : 'cart.php');
        } catch (Throwable $ex) { $pdo->rollBack(); throw $ex; }
    }
}

$total = 0; foreach ($items as $it) { $total += $it['price'] * $it['quantity']; }
$page_title = 'Checkout';
require __DIR__ . '/includes/header.php';
?>
<h1>Checkout</h1>
<?php if ($buy_now): ?><p class="muted">Buying now: only the item below is ordered. Your cart stays as it is.</p><?php endif; ?>
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
      <fieldset class="pay-methods">
        <legend>Payment method</legend>
        <label class="pay-option">
          <input type="radio" name="payment_method" value="gcash" <?= $method === 'gcash' ? 'checked' : '' ?> required>
          <span><strong>GCash</strong><small>After placing your order you will see the shop's GCash details and upload your receipt.</small></span>
        </label>
        <label class="pay-option">
          <input type="radio" name="payment_method" value="cod" <?= $method === 'cod' ? 'checked' : '' ?>>
          <span><strong>Cash on Delivery</strong><small>Pay in cash to the rider when your order arrives.</small></span>
        </label>
      </fieldset>
      <button class="btn" type="submit">Place order</button>
    </form>
  </div>
  <aside class="summary">
    <h3>Order summary</h3>
    <div class="row total"><span>Total to pay</span><span><?= peso($total) ?></span></div>
  </aside>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
