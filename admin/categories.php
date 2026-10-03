<?php
/** Admin > Categories: list, add, edit, delete (admin role only). */
require_once __DIR__ . '/../includes/init.php';
require_admin(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? ''; $id = (int)($_POST['category_id'] ?? 0);
    $name = trim($_POST['name'] ?? ''); $desc = trim($_POST['description'] ?? '');
    try {
        if ($action === 'save') {
            if ($name === '' || strlen($name) > 100) { flash('error', 'Category name is required (max 100 characters).'); }
            elseif ($id) { $pdo->prepare('UPDATE category SET name = ?, description = ? WHERE category_id = ?')->execute([$name, $desc ?: null, $id]); flash('success', 'Category updated.'); }
            else         { $pdo->prepare('INSERT INTO category (name, description) VALUES (?, ?)')->execute([$name, $desc ?: null]); flash('success', 'Category added.'); }
        } elseif ($action === 'delete') {
            $pdo->prepare('DELETE FROM category WHERE category_id = ?')->execute([$id]);
            flash('success', 'Category deleted.');
        }
    } catch (PDOException $ex) {
        // 23000 = duplicate name, or the category still has products (RESTRICT foreign key)
        flash('error', $action === 'delete' ? 'This category still has products. Move or delete them first.' : 'A category with that name already exists.');
    }
    redirect('admin/categories.php');
}

$edit = null;
if (isset($_GET['edit'])) { $st = $pdo->prepare('SELECT * FROM category WHERE category_id = ?'); $st->execute([(int)$_GET['edit']]); $edit = $st->fetch(); }
$cats = $pdo->query('SELECT c.*, (SELECT COUNT(*) FROM equipment e WHERE e.category_id = c.category_id) AS product_count FROM category c ORDER BY c.name')->fetchAll();

$page_title = 'Categories';
require __DIR__ . '/_header.php';
?>
<h1>Categories</h1>
<form method="post" class="form-box form-wide">
  <h3><?= $edit ? 'Edit category' : 'Add category' ?></h3>
  <?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="category_id" value="<?= (int)($edit['category_id'] ?? 0) ?>">
  <div class="form-row">
    <div class="form-group"><label for="n">Name</label><input type="text" id="n" name="name" value="<?= e($edit['name'] ?? '') ?>" required></div>
    <div class="form-group"><label for="d">Description (optional)</label><input type="text" id="d" name="description" value="<?= e($edit['description'] ?? '') ?>"></div>
  </div>
  <button class="btn" type="submit">Save category</button>
  <?php if ($edit): ?><a class="btn btn-outline" href="<?= url('admin/categories.php') ?>">Cancel</a><?php endif; ?>
</form>
<div class="table-wrap" style="margin-top:24px"><table>
  <tr><th>Name</th><th>Description</th><th>Products</th><th></th></tr>
  <?php foreach ($cats as $c): ?>
  <tr><td><?= e($c['name']) ?></td><td><?= e($c['description']) ?></td><td><?= (int)$c['product_count'] ?></td>
    <td class="actions"><a class="btn btn-sm btn-outline" href="<?= url('admin/categories.php?edit=' . (int)$c['category_id']) ?>">Edit</a>
      <form method="post" class="inline-form" data-confirm="Delete this category?"><?= csrf_field() ?>
        <input type="hidden" name="action" value="delete"><input type="hidden" name="category_id" value="<?= (int)$c['category_id'] ?>">
        <button class="btn btn-sm btn-danger" type="submit">Delete</button></form></td></tr>
  <?php endforeach; ?>
</table></div>
<?php require __DIR__ . '/_footer.php'; ?>
