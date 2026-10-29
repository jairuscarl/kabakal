<?php
/** Shopping cart: add, change quantity, remove. Guests can use it; checkout needs login. */
require_once __DIR__ . '/includes/init.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $eid    = (int)($_POST['equipment_id'] ?? 0);

    if ($action === 'add' || $action === 'update') {
        $st = $pdo->prepare("SELECT stock_quantity FROM equipment WHERE equipment_id = ? AND status = 'active'");
        $st->execute([$eid]);
        $stock = $st->fetchColumn();
        $qty = max(1, (int)($_POST['quantity'] ?? 1));
        if ($stock === false || $stock < 1) {
            flash('error', 'Sorry, that item is not available.');
        } else {
            if ($action === 'add') { $qty += (cart_map()[$eid] ?? 0); }   // add to what is already in the cart
            if ($qty > $stock) { $qty = (int)$stock; flash('info', "Only $stock in stock, so the quantity was adjusted."); }
            cart_set($eid, $qty);
            if ($action === 'add') flash('success', 'Added to your cart.');
        }
    } elseif ($action === 'buy_now') {
        // "Buy now": check out this one item straight away; the cart is left as it is
        $st = $pdo->prepare("SELECT stock_quantity FROM equipment WHERE equipment_id = ? AND status = 'active'");
        $st->execute([$eid]);
        $stock = (int)$st->fetchColumn();
        if ($stock < 1) { flash('error', 'Sorry, that item is not available.'); redirect('product.php?id=' . $eid); }
        $qty = min(max(1, (int)($_POST['quantity'] ?? 1)), $stock);
        $_SESSION['buy_now'] = [$eid => $qty];
        redirect('checkout.php?buy_now=1');
    } elseif ($action === 'remove') {
        cart_set($eid, 0);
        flash('info', 'Item removed from your cart.');
    }
    redirect('cart.php');
}

$items = cart_items();
$total = 0;
foreach ($items as $it) { $total += $it['price'] * $it['quantity']; }
$page_title = 'My Cart';
require __DIR__ . '/includes/header.php';
?>
<h1>My Cart</h1>
<?php if (!$items): ?>
  <p class="empty">Your cart is empty. <a href="<?= url('products.php') ?>">Browse products</a></p>
<?php else: ?>
<div class="two-col">
  <div class="table-wrap">
    <table>
      <tr><th>Product</th><th>Price</th><th>Quantity</th><th>Subtotal</th><th></th></tr>
      <?php foreach ($items as $it): ?>
      <tr>
        <td><div class="cart-product">
            <a href="<?= url('product.php?id=' . (int)$it['equipment_id']) ?>"><img class="cart-thumb" src="<?= e(product_image($it['image_url'])) ?>" alt="" width="56" height="56" loading="lazy"></a>
            <div><a href="<?= url('product.php?id=' . (int)$it['equipment_id']) ?>"><?= e($it['name']) ?></a>
            <?php if ($it['stock_quantity'] < $it['quantity']): ?><br><span class="badge badge-out-of-stock">Only <?= (int)$it['stock_quantity'] ?> left</span><?php endif; ?></div>
        </div></td>
        <td><?= peso($it['price']) ?></td>
        <td>
          <form method="post" class="inline-form">
            <?= csrf_field() ?><input type="hidden" name="action" value="update">
            <input type="hidden" name="equipment_id" value="<?= (int)$it['equipment_id'] ?>">
            <input type="number" name="quantity" value="<?= (int)$it['quantity'] ?>" min="1" max="<?= (int)$it['stock_quantity'] ?>">
            <button class="btn btn-sm btn-outline" type="submit">Update</button>
          </form>
        </td>
        <td><?= peso($it['price'] * $it['quantity']) ?></td>
        <td>
          <form method="post" class="inline-form">
            <?= csrf_field() ?><input type="hidden" name="action" value="remove">
            <input type="hidden" name="equipment_id" value="<?= (int)$it['equipment_id'] ?>">
            <button class="btn btn-sm btn-danger" type="submit">Remove</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>
  <aside class="summary">
    <h3>Order summary</h3>
    <div class="row total"><span>Total</span><span><?= peso($total) ?></span></div>
    <?php if (is_logged_in()): ?>
      <a class="btn btn-block" href="<?= url('checkout.php') ?>">Proceed to checkout</a>
    <?php else: ?>
      <p class="muted">Log in or register to check out. Your cart will be kept.</p>
      <a class="btn btn-block" href="<?= url('login.php?next=checkout.php') ?>">Log in to check out</a>
    <?php endif; ?>
  </aside>
</div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
