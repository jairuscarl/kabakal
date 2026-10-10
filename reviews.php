<?php
/**
 * Reviews & Feedback (the "Reviews" nav link, beside Products).
 * - Anyone can READ the reviews.
 * - Logged-in customers post a 1-5 star review (text + optional photo) and
 *   tap the heart to like/unlike (one like per customer, count shown live).
 * - ONLY a logged-in admin/staff account can reply to a review: the reply box
 *   is rendered just for admins and the POST action checks the session again.
 */
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/reviews.php';

// stars_display() lives in includes/init.php so Admin > Reviews can reuse it.

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ---- Post a review: customers only ----
    if ($action === 'review') {
        require_login();
        $rating = (int)($_POST['rating'] ?? 0);
        $text   = trim($_POST['comment'] ?? '');
        $equipment_id = (int)($_POST['equipment_id'] ?? 0) ?: null;
        if ($rating < 1 || $rating > 5)  flash('error', 'Please choose a star rating of 1 to 5.');
        elseif (mb_strlen($text) < 3)    flash('error', 'Please write a few words about your experience.');
        elseif (mb_strlen($text) > 2000) flash('error', 'Your review is too long (2000 characters maximum).');
        else {
            [$img, $err] = upload_image('image', 'uploads/reviews');
            if ($err) flash('error', $err);
            else {
                $pdo->prepare('INSERT INTO review (customer_id, equipment_id, rating, comment, image_url) VALUES (?, ?, ?, ?, ?)')
                    ->execute([(int)$_SESSION['customer_id'], $equipment_id, $rating, $text, $img]);
                flash('success', 'Thank you! Your review has been posted.');
            }
        }
        redirect('reviews.php');
    }

    // ---- Heart button: customers only, one like per review (tap again to remove) ----
    if ($action === 'like') {
        require_login();
        $rid = (int)($_POST['review_id'] ?? 0);
        $cid = (int)$_SESSION['customer_id'];
        $st = $pdo->prepare('SELECT 1 FROM review WHERE review_id = ?'); $st->execute([$rid]);
        if (!$st->fetch()) {
            flash('error', 'That review no longer exists.');
        } else {
            $st = $pdo->prepare('SELECT 1 FROM review_like WHERE review_id = ? AND customer_id = ?');
            $st->execute([$rid, $cid]);
            if ($st->fetch()) $pdo->prepare('DELETE FROM review_like WHERE review_id = ? AND customer_id = ?')->execute([$rid, $cid]);
            else              $pdo->prepare('INSERT INTO review_like (review_id, customer_id) VALUES (?, ?)')->execute([$rid, $cid]);
        }
        redirect('reviews.php');
    }

    // ---- Reply to a review: admin/staff ONLY ----
    if ($action === 'reply') {
        $rid  = (int)($_POST['review_id'] ?? 0);
        $text = trim($_POST['comment'] ?? '');
        if (!is_admin_logged_in()) {
            flash('error', 'Only admin accounts can reply to reviews.');
        } else {
            $st = $pdo->prepare('SELECT 1 FROM review WHERE review_id = ?'); $st->execute([$rid]);
            if (!$st->fetch())                                        flash('error', 'That review no longer exists.');
            elseif (mb_strlen($text) < 1 || mb_strlen($text) > 2000)  flash('error', 'Please write a reply (2000 characters maximum).');
            else {
                $pdo->prepare('INSERT INTO review_reply (review_id, admin_id, comment) VALUES (?, ?, ?)')
                    ->execute([$rid, (int)$_SESSION['admin_id'], $text]);
                flash('success', 'Reply posted.');
            }
        }
        redirect('reviews.php');
    }

    // ---- Delete an admin comment (reply): the admin who wrote it only ----
    if ($action === 'delete_reply') {
        if (!is_admin_logged_in()) {
            flash('error', 'Only admin accounts can delete admin comments.');
        } else {
            $id = (int)($_POST['reply_id'] ?? 0);
            $st = $pdo->prepare('SELECT admin_id FROM review_reply WHERE reply_id = ?'); $st->execute([$id]);
            $owner = $st->fetchColumn();
            if ($owner === false)                               flash('error', 'That comment no longer exists.');
            elseif ((int)$owner !== (int)$_SESSION['admin_id']) flash('error', 'You can only delete your own comments.');
            else {
                $pdo->prepare('DELETE FROM review_reply WHERE reply_id = ?')->execute([$id]);
                flash('success', 'Comment deleted.');
            }
        }
        redirect('reviews.php');
    }
}

// ---- Reviews, newest first ----
// Fetch with product name so every review can show which product it belongs to.
$reviews = review_fetch(0);
$replies = review_replies($reviews);

$page_title = 'Reviews';
require __DIR__ . '/includes/header.php';
?>
<h1>Reviews &amp; Feedback</h1>

<div class="review-summary">
  <?php if ($reviews): ?>
    <?php [$total, $avg] = review_stats($reviews); ?>
    <span class="review-avg-num"><?= number_format($avg, 1) ?></span>
    <?= stars_display((int)round($avg)) ?>
    <span class="muted">from <?= (int)$total ?> review<?= $total === 1 ? '' : 's' ?></span>
  <?php else: ?>
    <span class="muted">No reviews yet — be the first to share your experience.</span>
  <?php endif; ?>
</div>

<?php if (is_admin_logged_in()): ?>
  <p class="help">You are signed in as admin: use the reply box under any review to respond. Only admin accounts can post replies &mdash; and each admin can remove their own comments by clicking the trash icon beside them.</p>
<?php endif; ?>

<?php if (is_logged_in()): ?>
<form method="post" class="form-box review-form" enctype="multipart/form-data">
  <h3>Write a review</h3>
  <?= csrf_field() ?><input type="hidden" name="action" value="review">
  <div class="form-group">
    <label for="product">Product (optional)</label>
    <select id="product" name="equipment_id">
      <option value="0">Any product / All equipment</option>
      <?php foreach (review_products() as $prod): ?>
        <option value="<?= (int)$prod['equipment_id'] ?>"><?= e($prod['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="form-group">
    <label id="rating-label">Your rating</label>
    <?php /* Star picker works without JS: radios in 5..1 order so "input:checked ~ label"
             fills exactly N stars, and row-reverse puts them back into 1..5 order on screen. */ ?>
    <div class="star-input" role="radiogroup" aria-labelledby="rating-label">
      <input type="radio" id="star5" name="rating" value="5" required><label for="star5" title="5 stars">&#9733;</label>
      <input type="radio" id="star4" name="rating" value="4"><label for="star4" title="4 stars">&#9733;</label>
      <input type="radio" id="star3" name="rating" value="3"><label for="star3" title="3 stars">&#9733;</label>
      <input type="radio" id="star2" name="rating" value="2"><label for="star2" title="2 stars">&#9733;</label>
      <input type="radio" id="star1" name="rating" value="1"><label for="star1" title="1 star">&#9733;</label>
    </div>
  </div>
  <div class="form-group">
    <label for="rev-text">Your review</label>
    <textarea id="rev-text" name="comment" maxlength="2000" required
      placeholder="How is the equipment holding up? Quality, delivery, after-sales service..."></textarea>
  </div>
  <div class="form-group">
    <label for="rev-img">Photo (optional)</label>
    <img id="rev-prev" alt="" hidden>
    <input type="file" id="rev-img" name="image" accept="image/*" data-preview="#rev-prev">
    <p class="help">JPG, PNG, WEBP or GIF, up to <?= MAX_IMAGE_SIZE / 1048576 ?> MB.</p>
  </div>
  <button class="btn" type="submit">Post review</button>
</form>
<?php else: ?>
<div class="form-box review-form">
  <h3>Write a review</h3>
  <p class="muted">Log in to rate us 1 to 5 stars, write a comment and add a photo of your gear.</p>
  <a class="btn" href="<?= url('login.php?next=reviews.php') ?>">Log in to review</a>
</div>
<?php endif; ?>

<h2>Customer reviews</h2>
<?php if (!$reviews): ?>
  <p class="empty">No reviews yet. Be the first to tell us how your equipment is holding up!</p>
<?php else: ?>
<div class="review-list">
  <?php foreach ($reviews as $r): ?>
  <article class="review">
    <div class="review-head">
      <span class="review-author"><?= e($r['full_name']) ?> <?= stars_display($r['rating']) ?></span>
      <span class="review-date"><?= e(date('M j, Y', strtotime($r['created_at']))) ?></span>
    </div>
    <?php if (!empty($r['equipment_name'])): ?>
      <div class="review-product"><a class="chip" href="<?= e(url('product.php?id=' . (int)$r['equipment_id'])) ?>"><?= e($r['equipment_name']) ?></a></div>
    <?php endif; ?>
    <p class="review-text"><?= e($r['comment']) ?></p>
    <?php if ($r['image_url']): ?>
      <a class="review-img-link js-lightbox" href="<?= e(url($r['image_url'])) ?>">
        <img class="review-img" src="<?= e(url($r['image_url'])) ?>" alt="Photo attached by <?= e($r['full_name']) ?>">
      </a>
    <?php endif; ?>
    <div class="review-foot">
      <form method="post" class="inline-form">
        <?= csrf_field() ?><input type="hidden" name="action" value="like">
        <input type="hidden" name="review_id" value="<?= (int)$r['review_id'] ?>">
        <button class="like-btn<?= $r['liked_by_me'] ? ' liked' : '' ?>" type="submit"
                aria-pressed="<?= $r['liked_by_me'] ? 'true' : 'false' ?>"
                aria-label="Like this review, <?= (int)$r['like_count'] ?> like<?= (int)$r['like_count'] === 1 ? '' : 's' ?>"
                title="<?= is_logged_in() ? ($r['liked_by_me'] ? 'Unlike this review' : 'Like this review') : 'Log in to like this review' ?>">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
          </svg>
          <span class="like-count"><?= (int)$r['like_count'] ?></span>
        </button>
      </form>
    </div>

    <?php if (!empty($replies[$r['review_id']])): ?>
    <div class="review-replies">
      <?php foreach ($replies[$r['review_id']] as $rep): ?>
        <div class="reply">
          <div class="reply-meta"><span class="badge badge-admin">Admin</span>
            <?= e($rep['admin_name']) ?> · <?= e(date('M j, Y', strtotime($rep['created_at']))) ?>
            <?php if (is_admin_logged_in() && (int)$rep['admin_id'] === (int)$_SESSION['admin_id']): ?>
              <form method="post" class="inline-form reply-del" data-confirm="Delete this admin comment?">
                <?= csrf_field() ?><input type="hidden" name="action" value="delete_reply">
                <input type="hidden" name="reply_id" value="<?= (int)$rep['reply_id'] ?>">
                <button class="reply-del-btn" type="submit" title="Delete this comment" aria-label="Delete this comment"><?= trash_icon() ?></button>
              </form>
            <?php endif; ?>
          </div>
          <p class="review-text"><?= e($rep['comment']) ?></p>
        </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (is_admin_logged_in()): ?>
    <form method="post" class="review-reply-form">
      <?= csrf_field() ?><input type="hidden" name="action" value="reply">
      <input type="hidden" name="review_id" value="<?= (int)$r['review_id'] ?>">
      <div class="form-group">
        <label for="rp<?= (int)$r['review_id'] ?>">Reply as admin</label>
        <textarea id="rp<?= (int)$r['review_id'] ?>" name="comment" rows="2" maxlength="2000" required
          placeholder="Write a reply to this review..."></textarea>
      </div>
      <button class="btn btn-sm" type="submit">Post reply</button>
    </form>
    <?php endif; ?>
  </article>
  <?php endforeach; ?>
</div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
<?php /* TEMP screenshot hook (remove after testing): open the lightbox */ if (($_GET['lb'] ?? '') === '1'): ?>
<script>window.addEventListener('load', function () { var a = document.querySelector('.js-lightbox'); if (a) a.click(); });</script>
<?php endif; ?>


