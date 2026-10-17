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
        $media = product_media($id);
        try {
            $pdo->prepare('DELETE FROM equipment WHERE equipment_id = ?')->execute([$id]);   // gallery rows go too (ON DELETE CASCADE)
            delete_file($img);
            foreach ($media as $m) delete_file($m['file_path']);
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

        // Extra gallery photos and videos (several files per input)
        $newMedia = [];
        foreach (['gallery' => 'image', 'videos' => 'video'] as $field => $type) {
            foreach (uploaded_files($field) as $f) {
                if ($err) break 2;
                [$path, $mErr] = $type === 'image' ? save_image($f, 'uploads/products') : save_video($f);
                if ($mErr) $err = $f['name'] . ': ' . $mErr;
                elseif ($path) $newMedia[] = [$type, $path];
            }
        }

        if ($err) {
            delete_file($img);
            foreach ($newMedia as [, $path]) delete_file($path);
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
            // Remove the gallery items the admin ticked
            $remove = array_map('intval', (array)($_POST['remove_media'] ?? []));
            foreach (product_media($id) as $m) {
                if (in_array((int)$m['media_id'], $remove, true)) {
                    $pdo->prepare('DELETE FROM product_media WHERE media_id = ?')->execute([$m['media_id']]);
                    delete_file($m['file_path']);
                }
            }
            inv_change($id, 'available', (int)$d['stock'] - (int)$old['stock_quantity'], $aid, true);   // keep inventory in sync + stamp the date stock was added
            flash('success', 'Product updated.');
        } else {                                                     // ---- insert
            $pdo->prepare('INSERT INTO equipment (category_id, name, description, price, stock_quantity, image_url, is_featured, status)
                           VALUES (?, ?, ?, ?, 0, ?, ?, ?)')
                ->execute([$d['category_id'], $d['name'], $d['description'] ?: null, $d['price'], $img, $d['featured'], $d['status']]);
            $id = (int)$pdo->lastInsertId();
            inv_change($id, 'available', (int)$d['stock'], $aid, true);
            flash('success', 'Product added.');
        }
        // New gallery items go after the existing ones
        $st = $pdo->prepare('SELECT COALESCE(MAX(sort_order), 0) FROM product_media WHERE equipment_id = ?'); $st->execute([$id]);
        $order = (int)$st->fetchColumn();
        $ins = $pdo->prepare('INSERT INTO product_media (equipment_id, media_type, file_path, sort_order) VALUES (?, ?, ?, ?)');
        foreach ($newMedia as [$type, $path]) $ins->execute([$id, $type, $path, ++$order]);
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
    <div class="form-group"><label for="stock">Stock available</label><input type="number" id="stock" name="stock_quantity" min="0" value="<?= (int)$p['stock_quantity'] ?>" required style="max-width:none">
      <?php if (!empty($p['last_restocked_at'])): ?><p class="help">Date stock added: <?= e(date('M j, Y', strtotime($p['last_restocked_at']))) ?></p><?php endif; ?>
    </div>
    <div class="form-group"><label for="status">Status</label>
      <select id="status" name="status"><?php foreach (['active', 'inactive', 'discontinued'] as $s): ?><option <?= $p['status'] === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?></select>
      <p class="help">Only "active" products show in the shop.</p></div>
  </div>
  <div class="form-group"><label for="desc">Description</label><textarea id="desc" name="description"><?= e($p['description']) ?></textarea></div>
  <div class="form-group"><label for="image">Photo</label>
    <img id="preview" src="<?= e(product_image($p['image_url'])) ?>" alt="" style="max-width:220px;margin-bottom:8px;border-radius:6px">
    <input type="file" id="image" name="image" accept="image/*" data-preview="#preview">
    <p class="help">JPG, PNG, WEBP or GIF, up to <?= MAX_IMAGE_SIZE / 1048576 ?> MB. Leave empty to keep the current photo.
      This is the main photo shown on product cards and first in the gallery.</p></div>
  <?php $media = $p['equipment_id'] ? product_media($p['equipment_id']) : []; ?>
  <?php if ($media): ?>
  <div class="form-group"><label>Gallery (tick to remove)</label>
    <div class="media-admin">
      <?php foreach ($media as $m): ?>
      <label class="media-admin-item">
        <?php if ($m['media_type'] === 'video'): ?>
          <video src="<?= e(url($m['file_path'])) ?>" preload="metadata" muted></video><span class="media-tag">Video</span>
        <?php else: ?>
          <img src="<?= e(url($m['file_path'])) ?>" alt="">
        <?php endif; ?>
        <span class="check"><input type="checkbox" name="remove_media[]" value="<?= (int)$m['media_id'] ?>"> Remove</span>
      </label>
      <?php endforeach; ?>
    </div></div>
  <?php endif; ?>
  <div class="form-row">
    <div class="form-group"><label for="gallery">Add more photos</label>
      <input type="file" id="gallery" name="gallery[]" accept="image/*" multiple>
      <p class="help">Select several at once. Each up to <?= MAX_IMAGE_SIZE / 1048576 ?> MB.</p></div>
    <div class="form-group"><label for="videos">Add videos</label>
      <input type="file" id="videos" name="videos[]" accept="video/mp4" multiple>
      <p class="help">MP4 only, each up to <?= MAX_VIDEO_SIZE / 1048576 ?> MB. All files in one save must fit the server limit (post_max_size: <?= e(ini_get('post_max_size')) ?>).</p></div>
  </div>
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
