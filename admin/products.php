<?php
/** Admin > Products: list, add, edit, delete. */
require_once __DIR__ . '/../includes/init.php';
require_admin();
$aid = $_SESSION['admin_id'];

// ---------- Handle form posts ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id     = (int)($_POST['equipment_id'] ?? 0);

    if ($action === 'delete') {
        require_admin(['admin']);                                   // staff cannot delete
        $st = $pdo->prepare('SELECT image_url FROM equipment WHERE equipment_id = ?'); $st->execute([$id]);
        $img = $st->fetchColumn();
        try {
            $pdo->prepare('DELETE FROM equipment WHERE equipment_id = ?')->execute([$id]);
            delete_file($img);
            flash('success', 'Product deleted.');
        } catch (PDOException $ex) {
            // order_item has a RESTRICT foreign key: products that were ordered cannot be deleted
            flash('error', 'This product is part of past orders and cannot be deleted. Set its status to "inactive" instead.');
        }
        redirect('admin/products.php');
    }

    if ($action === 'save') {
        $d = [
            'name'        => trim($_POST['name'] ?? ''),
            'category_id' => (int)($_POST['category_id'] ?? 0),
            'price'       => $_POST['price'] ?? '',
            'stock'       => $_POST['stock_quantity'] ?? '0',
            'description' => trim($_POST['description'] ?? ''),
            'status'      => $_POST['status'] ?? 'active',
            'featured'    => isset($_POST['is_featured']) ? 1 : 0,
        ];
        $err = null;
        $cat = $pdo->prepare('SELECT 1 FROM category WHERE category_id = ?'); $cat->execute([$d['category_id']]);
        if ($d['name'] === '' || strlen($d['name']) > 150)          $err = 'Product name is required (max 150 characters).';
        elseif (!$cat->fetch())                                     $err = 'Please choose a category.';
        elseif (!is_numeric($d['price']) || $d['price'] < 0)        $err = 'Price must be a number, 0 or more.';
        elseif (!ctype_digit((string)$d['stock']))                  $err = 'Stock must be a whole number, 0 or more.';
        elseif (!in_array($d['status'], ['active', 'inactive', 'discontinued'], true)) $err = 'Invalid status.';

        [$img, $imgErr] = upload_image('image', 'uploads/products');
        if (!$err && $imgErr) $err = $imgErr;

        if ($err) {
            delete_file($img);
            flash('error', $err);
            redirect('admin/products.php?action=' . ($id ? "edit&id=$id" : 'add'));
        }

        if ($id) {                                                   // ---- update
            $st = $pdo->prepare('SELECT image_url, stock_quantity FROM equipment WHERE equipment_id = ?'); $st->execute([$id]);
            $old = $st->fetch();
            if (!$old) redirect('admin/products.php');
            $pdo->prepare('UPDATE equipment SET name=?, category_id=?, price=?, description=?, status=?, is_featured=?, image_url=? WHERE equipment_id=?')
                ->execute([$d['name'], $d['category_id'], $d['price'], $d['description'] ?: null, $d['status'], $d['featured'], $img ?: $old['image_url'], $id]);
            if ($img) delete_file($old['image_url']);
            inv_change($id, 'available', (int)$d['stock'] - (int)$old['stock_quantity'], $aid);   // keep inventory in sync
            flash('success', 'Product updated.');
        } else {                                                     // ---- insert
            $pdo->prepare('INSERT INTO equipment (category_id, name, description, price, stock_quantity, image_url, is_featured, status)
                           VALUES (?, ?, ?, ?, 0, ?, ?, ?)')
                ->execute([$d['category_id'], $d['name'], $d['description'] ?: null, $d['price'], $img, $d['featured'], $d['status']]);
            inv_change((int)$pdo->lastInsertId(), 'available', (int)$d['stock'], $aid);
            flash('success', 'Product added.');
        }
        redirect('admin/products.php');
    }
}

$categories = $pdo->query('SELECT category_id, name FROM category ORDER BY name')->fetchAll();
$action = $_GET['action'] ?? '';
$page_title = 'Products';
require __DIR__ . '/_header.php';

// ---------- Add / Edit form ----------
if ($action === 'add' || $action === 'edit'):
    $p = ['equipment_id' => 0, 'name' => '', 'category_id' => 0, 'price' => '', 'stock_quantity' => 0, 'description' => '', 'status' => 'active', 'is_featured' => 0, 'image_url' => null];
    if ($action === 'edit') {
        $st = $pdo->prepare('SELECT * FROM equipment WHERE equipment_id = ?'); $st->execute([(int)($_GET['id'] ?? 0)]);
        $p = $st->fetch() ?: redirect('admin/products.php');
    }
?>
<div class="page-head"><h1><?= $p['equipment_id'] ? 'Edit product' : 'Add product' ?></h1><a href="<?= url('admin/products.php') ?>">&larr; Back</a></div>
<form method="post" enctype="multipart/form-data" class="form-box form-wide">
  <?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="equipment_id" value="<?= (int)$p['equipment_id'] ?>">
  <div class="form-group"><label for="name">Name</label><input type="text" id="name" name="name" value="<?= e($p['name']) ?>" maxlength="150" required></div>
  <div class="form-row">
    <div class="form-group"><label for="cat">Category</label>
      <select id="cat" name="category_id" required><option value="">Choose...</option>
        <?php foreach ($categories as $c): ?><option value="<?= (int)$c['category_id'] ?>" <?= $c['category_id'] == $p['category_id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
      </select></div>
    <div class="form-group"><label for="price">Price (₱)</label><input type="number" id="price" name="price" step="0.01" min="0" value="<?= e($p['price']) ?>" required style="max-width:none"></div>
    <div class="form-group"><label for="stock">Stock available</label><input type="number" id="stock" name="stock_quantity" min="0" value="<?= (int)$p['stock_quantity'] ?>" required style="max-width:none"></div>
    <div class="form-group"><label for="status">Status</label>
      <select id="status" name="status"><?php foreach (['active', 'inactive', 'discontinued'] as $s): ?><option <?= $p['status'] === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?></select>
      <p class="help">Only "active" products show in the shop.</p></div>
  </div>
  <div class="form-group"><label for="desc">Description</label><textarea id="desc" name="description"><?= e($p['description']) ?></textarea></div>
  <div class="form-group"><label for="image">Photo</label>
    <img id="preview" src="<?= e(product_image($p['image_url'])) ?>" alt="" style="max-width:220px;margin-bottom:8px;border-radius:6px">
    <input type="file" id="image" name="image" accept="image/*" data-preview="#preview">
    <p class="help">JPG, PNG, WEBP or GIF, up to <?= MAX_IMAGE_SIZE / 1048576 ?> MB. Leave empty to keep the current photo.</p></div>
  <div class="form-group"><label class="check"><input type="checkbox" name="is_featured" <?= $p['is_featured'] ? 'checked' : '' ?>> Show as featured on the Home page</label></div>
  <button class="btn" type="submit">Save product</button>
</form>
<?php else:
    // ---------- List ----------
    $q = trim($_GET['q'] ?? ''); $cat = (int)($_GET['category'] ?? 0);
    $sql = 'SELECT e.*, c.name AS category_name FROM equipment e JOIN category c ON c.category_id = e.category_id WHERE 1=1';
    $params = [];
    if ($q !== '') { $sql .= ' AND e.name LIKE ?'; $params[] = "%$q%"; }
    if ($cat)      { $sql .= ' AND e.category_id = ?'; $params[] = $cat; }
    $st = $pdo->prepare($sql . ' ORDER BY c.name, e.name'); $st->execute($params);
    $products = $st->fetchAll();
?>
<div class="page-head"><h1>Products</h1><a class="btn" href="<?= url('admin/products.php?action=add') ?>">+ Add product</a></div>
<form class="filter-bar" method="get">
  <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search name...">
  <select name="category"><option value="0">All categories</option>
    <?php foreach ($categories as $c): ?><option value="<?= (int)$c['category_id'] ?>" <?= $cat == $c['category_id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select>
  <button class="btn btn-sm" type="submit">Filter</button>
</form>
<div class="table-wrap"><table>
  <tr><th>Photo</th><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th><th></th></tr>
  <?php foreach ($products as $p): ?>
  <tr>
    <td><img class="thumb" src="<?= e(product_image($p['image_url'])) ?>" alt=""></td>
    <td><?= e($p['name']) ?><?= $p['is_featured'] ? ' &#9733;' : '' ?></td>
    <td><?= e($p['category_name']) ?></td><td><?= peso($p['price']) ?></td>
    <td><?= (int)$p['stock_quantity'] ?></td><td><?= badge($p['status']) ?></td>
    <td class="actions">
      <a class="btn btn-sm btn-outline" href="<?= url('admin/products.php?action=edit&id=' . (int)$p['equipment_id']) ?>">Edit</a>
      <?php if ($_SESSION['admin_role'] === 'admin'): ?>
      <form method="post" class="inline-form" data-confirm="Delete this product?">
        <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="equipment_id" value="<?= (int)$p['equipment_id'] ?>">
        <button class="btn btn-sm btn-danger" type="submit">Delete</button></form>
      <?php endif; ?>
    </td>
  </tr>
  <?php endforeach; ?>
  <?php if (!$products): ?><tr><td colspan="7" class="empty">No products found.</td></tr><?php endif; ?>
</table></div>
<?php endif; ?>
<?php require __DIR__ . '/_footer.php'; ?>
