<?php
/** Admin > Messages: read and answer customer messages (general or about a product). */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/messages.php';
require_admin();
$aid = $_SESSION['admin_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['conversation_id'] ?? 0);
    $cv = conversation_get($id);
    if (!$cv) { flash('error', 'That conversation no longer exists.'); redirect('admin/messages.php'); }
    $action = $_POST['action'] ?? '';

    if ($action === 'reply') {
        [$body, $err] = message_clean($_POST['body'] ?? '');
        if ($err) flash('error', $err);
        else { message_add($id, 'admin', $body, $aid); flash('success', 'Reply sent.'); }
    } elseif ($action === 'toggle') {
        $pdo->prepare("UPDATE conversation SET status = IF(status = 'open', 'closed', 'open') WHERE conversation_id = ?")->execute([$id]);
        flash('success', $cv['status'] === 'open' ? 'Conversation closed.' : 'Conversation reopened.');
    } elseif ($action === 'delete') {
        require_admin(['admin']);                                   // staff cannot delete
        $pdo->prepare('DELETE FROM conversation WHERE conversation_id = ?')->execute([$id]);   // messages go too (CASCADE)
        flash('success', 'Conversation deleted.');
        redirect('admin/messages.php');
    }
    // the list's ⋮ menu sends back_to=<filter>: return to the list instead of opening the chat
    $back = $_POST['back_to'] ?? '';
    if (in_array($back, ['open', 'closed', 'unread', 'all'], true)) redirect("admin/messages.php?show=$back");
    redirect("admin/messages.php?id=$id");
}

$page_title = 'Messages';

// ---------- One conversation ----------
if (isset($_GET['id'])) {
    $cv = conversation_get($_GET['id']);
    if (!$cv) redirect('admin/messages.php');
    $pdo->prepare('UPDATE conversation SET admin_unread = 0 WHERE conversation_id = ?')->execute([$cv['conversation_id']]);
    require __DIR__ . '/_header.php';
?>
<div class="page-head"><h1><?= e($cv['subject']) ?></h1><a href="<?= url('admin/messages.php') ?>">&larr; All messages</a></div>
<div class="chat-head">
  <?= avatar_html($cv['avatar_url'], $cv['full_name'], 44) ?>
  <?php if ($cv['equipment_id']): ?><img class="thumb" src="<?= e(product_image($cv['image_url'])) ?>" alt=""><?php endif; ?>
  <div>
    <strong><?= e($cv['full_name']) ?></strong> <span class="muted">&lt;<?= e($cv['email']) ?>&gt;</span> · <?= badge($cv['status']) ?><br>
    <span class="muted">Topic:
      <?php if ($cv['equipment_id']): ?><a href="<?= url('product.php?id=' . (int)$cv['equipment_id']) ?>" target="_blank"><?= e(conversation_topic($cv)) ?></a>
      <?php else: ?>General<?php endif; ?></span>
    · <a href="<?= url('admin/customer_view.php?id=' . (int)$cv['customer_id']) ?>">Customer details</a>
  </div>
</div>
<?= message_thread($cv['conversation_id'], 'admin') ?>
<form method="post" class="chat-reply">
  <?= csrf_field() ?><input type="hidden" name="action" value="reply"><input type="hidden" name="conversation_id" value="<?= (int)$cv['conversation_id'] ?>">
  <textarea name="body" maxlength="<?= MESSAGE_MAX ?>" placeholder="Write a reply to the customer..." required></textarea>
  <button class="btn" type="submit">Send reply</button>
</form>
<div class="actions" style="margin-top:16px">
  <form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="conversation_id" value="<?= (int)$cv['conversation_id'] ?>">
    <button class="btn btn-sm btn-outline" type="submit"><?= $cv['status'] === 'open' ? 'Mark as closed' : 'Reopen' ?></button></form>
  <?php if ($_SESSION['admin_role'] === 'admin'): ?>
  <form method="post" class="inline-form" data-confirm="Delete this whole conversation?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="conversation_id" value="<?= (int)$cv['conversation_id'] ?>">
    <button class="btn btn-sm btn-danger" type="submit">Delete</button></form>
  <?php endif; ?>
</div>
<?php
    require __DIR__ . '/_footer.php';
    exit;
}

// ---------- List ----------
$show = in_array($_GET['show'] ?? '', ['open', 'closed', 'unread', 'all'], true) ? $_GET['show'] : 'open';
$sql = 'SELECT cv.*, c.full_name, c.avatar_url, e.name AS equipment_name,
               (SELECT body FROM message m WHERE m.conversation_id = cv.conversation_id ORDER BY message_id DESC LIMIT 1) AS last_body
        FROM conversation cv JOIN customer c ON c.customer_id = cv.customer_id
        LEFT JOIN equipment e ON e.equipment_id = cv.equipment_id';
if ($show === 'open')   $sql .= " WHERE cv.status = 'open'";
if ($show === 'closed') $sql .= " WHERE cv.status = 'closed'";
if ($show === 'unread') $sql .= ' WHERE cv.admin_unread = 1';
$convs = $pdo->query($sql . ' ORDER BY cv.admin_unread DESC, cv.last_message_at DESC')->fetchAll();
require __DIR__ . '/_header.php';
?>
<h1>Messages</h1>
<form class="filter-bar" method="get">
  <select name="show" onchange="this.form.submit()">
    <?php foreach (['open' => 'Open', 'closed' => 'Closed', 'unread' => 'Unread', 'all' => 'All'] as $k => $v): ?>
      <option value="<?= $k ?>" <?= $show === $k ? 'selected' : '' ?>><?= $v ?></option>
    <?php endforeach; ?>
  </select>
</form>
<?php /* Each row is clickable (the subject link is stretched over the row, see .conv-item in style.css);
   the ⋮ menu on the right sits above that layer (.conv-inner) and closes / reopens / deletes from the list. */ ?>
<div class="table-wrap"><table class="conv-table">
  <tr><th>Customer</th><th>Topic</th><th>Subject / last message</th><th>Last activity</th><th>Status</th><th><span class="sr-only">Actions</span></th></tr>
  <?php foreach ($convs as $c): $cid = (int)$c['conversation_id']; ?>
  <tr class="conv-item<?= $c['admin_unread'] ? ' row-unread' : '' ?>">
    <td><div class="conv-who"><?= avatar_html($c['avatar_url'], $c['full_name'], 36) ?><span><?= e($c['full_name']) ?></span></div></td>
    <td><?= e(conversation_topic($c)) ?></td>
    <td><?php if ($c['admin_unread']): ?><span class="inbox-new">New</span><?php endif; ?><a class="conv-open" href="<?= url('admin/messages.php?id=' . $cid) ?>"><strong><?= e($c['subject']) ?></strong></a>
      <div class="muted conv-preview"><?= e(mb_strimwidth((string)$c['last_body'], 0, 90, '…')) ?></div></td>
    <td><?= e(date('M j, Y g:i A', strtotime($c['last_message_at']))) ?></td>
    <td><?= badge($c['status']) ?></td>
    <td class="conv-inner">
      <details class="row-menu">
        <summary aria-label="Actions for this conversation" title="Actions">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true" focusable="false"><circle cx="12" cy="5" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="12" cy="19" r="2"/></svg>
        </summary>
        <div class="row-menu-list">
          <a href="<?= url('admin/messages.php?id=' . $cid) ?>">Open chat</a>
          <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="conversation_id" value="<?= $cid ?>"><input type="hidden" name="back_to" value="<?= e($show) ?>">
            <button type="submit"><?= $c['status'] === 'open' ? 'Mark as closed' : 'Reopen' ?></button></form>
          <?php if ($_SESSION['admin_role'] === 'admin'): ?>
          <form method="post" data-confirm="Delete this whole conversation?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="conversation_id" value="<?= $cid ?>">
            <button type="submit" class="danger">Delete</button></form>
          <?php endif; ?>
        </div>
      </details>
    </td>
  </tr>
  <?php endforeach; ?>
  <?php if (!$convs): ?><tr><td colspan="6" class="empty">No messages here.</td></tr><?php endif; ?>
</table></div>
<?php require __DIR__ . '/_footer.php'; ?>
