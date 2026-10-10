<?php
/** Admin > Reviews: read every public review and delete the bad ones. */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/reviews.php';   // review_replies() for the admin comments below
require_admin();

// ---------- Delete a review (full admins only, same rule as deleting a product) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['action'] ?? '') === 'delete') {
        require_admin(['admin']);
        $id = (int)($_POST['review_id'] ?? 0);
        $st = $pdo->prepare('SELECT image_url FROM review WHERE review_id = ?'); $st->execute([$id]);
        $img = $st->fetchColumn();
        if ($img === false) {
            flash('error', 'That review no longer exists.');
        } else {
            // likes + replies vanish with it (ON DELETE CASCADE), photo with the row
            $pdo->prepare('DELETE FROM review WHERE review_id = ?')->execute([$id]);
            delete_file($img);
            flash('success', 'Review deleted.');
        }
    }
    // ---- Delete an admin comment (reply): each admin can delete their OWN one ----
    if (($_POST['action'] ?? '') === 'delete_reply') {
        $id = (int)($_POST['reply_id'] ?? 0);
        $st = $pdo->prepare('SELECT admin_id FROM review_reply WHERE reply_id = ?'); $st->execute([$id]);
        $owner = $st->fetchColumn();
        if ($owner === false)                                flash('error', 'That comment no longer exists.');
        elseif ((int)$owner !== (int)$_SESSION['admin_id'])  flash('error', 'You can only delete your own comments.');
        else {
            $pdo->prepare('DELETE FROM review_reply WHERE reply_id = ?')->execute([$id]);
            flash('success', 'Comment deleted.');
        }
    }
    redirect('admin/reviews.php');
}

$reviews = $pdo->query(
    'SELECT r.*, c.full_name, e.name AS equipment_name,
            (SELECT COUNT(*) FROM review_like  l WHERE l.review_id = r.review_id) AS like_count,
            (SELECT COUNT(*) FROM review_reply p WHERE p.review_id = r.review_id) AS reply_count
       FROM review r
       JOIN customer c ON c.customer_id = r.customer_id LEFT JOIN equipment e ON e.equipment_id = r.equipment_id
      ORDER BY r.created_at DESC, r.review_id DESC'
)->fetchAll();
$replies = review_replies($reviews);                 // admin comments grouped per review

$page_title = 'Reviews';
require __DIR__ . '/_header.php';
?>
<div class="page-head">
  <h1>Reviews &amp; Feedback</h1>
  <a class="btn" href="<?= url('reviews.php') ?>" target="_blank">View public page</a>
</div>
<div class="table-wrap"><table>
  <tr><th>Photo</th><th>Customer</th><th>Rating</th><th>Review</th><th>Product</th><th>Likes</th><th>Replies</th><th>Date</th><th></th></tr>
  <?php foreach ($reviews as $r): ?>
  <tr>
    <td><?php if ($r['image_url']): ?>
      <a class="js-lightbox" href="<?= e(url($r['image_url'])) ?>">
        <img class="thumb" src="<?= e(url($r['image_url'])) ?>" alt="Photo by <?= e($r['full_name']) ?>"></a>
    <?php else: ?><span class="muted">&mdash;</span><?php endif; ?></td>
    <td><?= e($r['full_name']) ?></td>
    <td><?= stars_display($r['rating']) ?></td>
    <td style="max-width:360px; white-space:pre-wrap; overflow-wrap:anywhere"><?= e($r['comment']) ?></td><td><?= !empty($r['equipment_name']) ? e($r['equipment_name']) : '<span class="muted">&mdash;</span>' ?></td>
    <td><?= (int)$r['like_count'] ?></td>
    <td style="max-width:320px">
      <?php if (empty($replies[$r['review_id']])): ?>
        <span class="muted">0</span>
      <?php else: foreach ($replies[$r['review_id']] as $rep): ?>
        <div class="reply" style="margin-bottom:8px">
          <div class="reply-meta"><span class="badge badge-admin">Admin</span>
            <?= e($rep['admin_name']) ?> &middot; <?= e(date('M j, Y', strtotime($rep['created_at']))) ?></div>
          <p class="review-text"><?= e($rep['comment']) ?></p>
          <?php if ((int)$rep['admin_id'] === (int)$_SESSION['admin_id']): ?>
            <form method="post" class="inline-form reply-del" data-confirm="Delete this admin comment?">
              <?= csrf_field() ?><input type="hidden" name="action" value="delete_reply">
              <input type="hidden" name="reply_id" value="<?= (int)$rep['reply_id'] ?>">
              <button class="reply-del-btn" type="submit" title="Delete this comment" aria-label="Delete this comment"><?= trash_icon() ?></button>
            </form>
          <?php endif; ?>
        </div>
      <?php endforeach; endif; ?>
    </td>
    <td><?= e(date('M j, Y', strtotime($r['created_at']))) ?></td>
    <td class="actions">
      <?php if ($_SESSION['admin_role'] === 'admin'): ?>
      <form method="post" class="inline-form" data-confirm="Delete this review and its replies?">
        <?= csrf_field() ?><input type="hidden" name="action" value="delete">
        <input type="hidden" name="review_id" value="<?= (int)$r['review_id'] ?>">
        <button class="btn btn-sm btn-danger" type="submit">Delete</button>
      </form>
      <?php endif; ?>
    </td>
  </tr>
  <?php endforeach; ?>
  <?php if (!$reviews): ?><tr><td colspan="9" class="empty">No reviews yet.</td></tr><?php endif; ?>
</table></div>
<?php require __DIR__ . '/_footer.php'; ?>