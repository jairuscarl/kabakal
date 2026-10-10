<?php
/**
 * includes/reviews.php - shared review code used by product.php, reviews.php and
 * admin/reviews.php.
 *
 *   review_handle_post($back, $equipment_id)  - process one POST action, then redirect
 *   review_fetch($equipment_id = 0, $limit = 0) - review rows with product name, like count,
 *                                                 "liked by me"
 *   review_replies($reviews)                  - admin replies grouped by review_id
 *   review_stats($reviews)                    - [total, average] for a set of rows
 *   review_products()                         - active products (for the picker)
 *   review_card($r, $replies = [], $show_product = true) - render a single review card
 *   review_form($back, $equipment_id = 0)     - the write-reviews form (product picker
 *                                               + star rating + optional photo)
 */

// ---------- Helpers ----------
if (!function_exists('stars_display')) {
    function stars_display($n) {
        $s = '';
        for ($i = 1; $i <= 5; $i++) {
            $s .= $i <= (int)$n ? '&#9733;' : '&#9734;';
        }
        return $s;
    }
}

if (!function_exists('rating_stars')) {
    function rating_stars($n) {
        $s = '';
        for ($i = 1; $i <= 5; $i++) {
            $s .= $i <= (int)$n ? '&#9733;' : '&#9734;';
        }
        return $s;
    }
}

// ---------- POST handler: review / like / reply / delete ----------
if (!function_exists('review_handle_post')) {
    function review_handle_post($back, $equipment_id = 0) {
        global $pdo;
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        // ---- Reply (admin only) ----
        if (($_POST['action'] ?? '') === 'reply' && is_admin_logged_in()) {
            $rid  = (int)($_POST['review_id'] ?? 0);
            $body = trim($_POST['comment'] ?? '');
            if ($rid > 0 && $body !== '') {
                $ts  = date('Y-m-d H:i:s');
                $sql = 'INSERT INTO review_reply (review_id, admin_id, comment, created_at) VALUES (?, ?, ?, ?)';
                $pdo->prepare($sql)->execute([$rid, (int)$_SESSION['admin_id'], $body, $ts]);
            }
            redirect($back);
        }

        // ---- Delete an admin comment (reply): an admin can delete their OWN comment only ----
        if (($_POST['action'] ?? '') === 'delete_reply') {
            require_admin();
            $id = (int)($_POST['reply_id'] ?? 0);
            $st = $pdo->prepare('SELECT admin_id FROM review_reply WHERE reply_id = ?');
            $st->execute([$id]);
            $owner = $st->fetchColumn();
            if ($owner === false)                               flash('error', 'That comment no longer exists.');
            elseif ((int)$owner !== (int)$_SESSION['admin_id']) flash('error', 'You can only delete your own comments.');
            else {
                $pdo->prepare('DELETE FROM review_reply WHERE reply_id = ?')->execute([$id]);
                flash('success', 'Comment deleted.');
            }
            redirect($back);
        }

        // ---- Delete (full admin only) ----
        if (($_POST['action'] ?? '') === 'delete') {
            require_admin(['admin']);
            $id = (int)($_POST['review_id'] ?? 0);
            $st = $pdo->prepare('SELECT image_url FROM review WHERE review_id = ?');
            $st->execute([$id]);
            $img = $st->fetchColumn();
            if ($img !== false) {
                $pdo->prepare('DELETE FROM review WHERE review_id = ?')->execute([$id]);
                delete_file($img);
            }
            redirect($back);
        }

        // ---- Like / unlike ----
        if (($_POST['action'] ?? '') === 'like') {
            $rid = (int)($_POST['review_id'] ?? 0);
            if ($rid > 0 && is_logged_in()) {
                $who = (int)$_SESSION['customer_id'];
                $st  = $pdo->prepare('SELECT COUNT(*) FROM review_like WHERE review_id = ? AND customer_id = ?');
                $st->execute([$rid, $who]);
                $exists = (bool)$st->fetchColumn();

                if ($exists) {
                    $pdo->prepare('DELETE FROM review_like WHERE review_id = ? AND customer_id = ?')->execute([$rid, $who]);
                } else {
                    $pdo->prepare('INSERT INTO review_like (review_id, customer_id) VALUES (?, ?)')->execute([$rid, $who]);
                }
            }
            redirect($back);
        }

        // ---- Post a review ----
        if (($_POST['action'] ?? '') === 'review' && is_logged_in()) {
            $equipment_id = (int)($_POST['equipment_id'] ?? 0) ?: null;
            $rating       = (int)($_POST['rating'] ?? 0);
            $comment      = trim($_POST['comment'] ?? '');
            $image        = null;

            if ($rating > 0 && $rating <= 5 && $comment !== '') {
                $image = null;
                if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                    [$image, $err] = upload_image('image', 'uploads/reviews');
                }
                $sql = 'INSERT INTO review (customer_id, equipment_id, rating, comment, image_url) VALUES (?, ?, ?, ?, ?)';
                if ($image === null) {
                    $pdo->prepare($sql)->execute([(int)$_SESSION['customer_id'], $equipment_id, $rating, $comment, null]);
                } else {
                    $pdo->prepare($sql)->execute([(int)$_SESSION['customer_id'], $equipment_id, $rating, $comment, $image]);
                }
                flash('success', 'Thank you! Your review has been posted.');
            } else {
                flash('error', 'Please choose a star rating of 1 to 5 and write a few words.');
            }
            redirect($back);
        }
    }
}

// ---------- Fetch helpers ----------
if (!function_exists('review_fetch')) {
    function review_fetch($equipment_id = 0, $limit = 0) {
        global $pdo;
        global $cid;
        $me = (int)($cid ?? $_SESSION['customer_id'] ?? 0);
        // Always join customer + equipment so full_name / equipment_name always exist.
        $join   = true
            ? 'JOIN customer c ON c.customer_id = r.customer_id LEFT JOIN equipment e ON e.equipment_id = r.equipment_id'
            : '';
        $condition = $equipment_id > 0
            ? 'AND r.equipment_id = ' . (int)$equipment_id
            : '';
        $sql = 'SELECT r.*, c.full_name, e.name AS equipment_name,'
            . ' e.equipment_id AS equipment_id,'
            . ' (SELECT COUNT(*) FROM review_like rl WHERE rl.review_id = r.review_id) AS like_count,'
            . ' EXISTS(SELECT 1 FROM review_like rl2 WHERE rl2.review_id = r.review_id AND rl2.customer_id = ' . $me . ') AS liked_by_me'
            . ' FROM review r'
            . ' ' . $join
            . ' WHERE 1=1 '
            . ' ' . $condition
            . ' ORDER BY r.review_id DESC';
        if ($limit > 0) $sql .= ' LIMIT ' . (int)$limit;
        $st = $pdo->prepare($sql);
        $st->execute();
        return $st->fetchAll();
    }
}

if (!function_exists('review_replies')) {
    function review_replies($reviews) {
        global $pdo;
        $groups = [];
        foreach ($reviews as $r) {
            $groups[$r['review_id']] = [];
        }
        if (empty($reviews)) return $groups;
        $ids = array_keys($groups);
        $marks = implode(',', array_fill(0, count($ids), '?'));
        $st = $pdo->prepare('SELECT rr.*, a.full_name AS admin_name '
            . 'FROM review_reply rr '
            . 'JOIN admin a ON a.admin_id = rr.admin_id '
            . 'WHERE rr.review_id IN (' . $marks . ') ORDER BY rr.reply_id');
        $st->execute($ids);
        foreach ($st->fetchAll() as $rep) {
            $groups[$rep['review_id']][] = $rep;
        }
        return $groups;
    }
}

if (!function_exists('review_stats')) {
    function review_stats($reviews) {
        $total = count($reviews);
        $sum = array_sum(array_column($reviews, 'rating'));
        $avg = $total ? round($sum / $total, 1) : 0.0;
        return [$total, $avg];
    }
}

if (!function_exists('review_products')) {
    function review_products() {
        global $pdo;
        $st = $pdo->query("SELECT equipment_id, name FROM equipment WHERE status = 'active' ORDER BY name");
        return $st->fetchAll();
    }
}

if (!function_exists('review_card')) {
    function review_card($r, $replies = [], $show_product = true) {
        if (!$r) return '';
        $html = '<article class="review">';
        $html .= '<div class="review-head">';
        $html .= '<span class="review-author">' . e($r['full_name'] ?? '') . ' ' . stars_display($r['rating']) . '</span>';
        $html .= '<span class="review-date">' . e(date('M j, Y', strtotime($r['created_at']))) . '</span>';
        $html .= '</div>';

        if ($show_product && !empty($r['equipment_name'])) {
            $html .= '<div class="review-product"><a class="chip" href="' . e(url('product.php?id=' . (int)$r['equipment_id'])) . '">' . e($r['equipment_name']) . '</a></div>';
        }

        $html .= '<p class="review-text">' . e($r['comment']) . '</p>';

        if (!empty($r['image_url'])) {
            $html .= '<a class="review-img-link js-lightbox" href="' . e(url($r['image_url'])) . '">';
            $html .= '<img class="review-img" src="' . e(url($r['image_url'])) . '" alt="Photo attached by ' . e($r['full_name'] ?? 'guest') . '">';
            $html .= '</a>';
        }

        $html .= '<div class="review-foot">';
        $html .= '<form method="post" class="inline-form">';
        $html .= csrf_field() . '<input type="hidden" name="action" value="like">';
        $html .= '<input type="hidden" name="review_id" value="' . (int)$r['review_id'] . '">';
        $html .= '<button class="like-btn' . ($r['liked_by_me'] ? ' liked' : '') . '" type="submit"';
        $html .= ' aria-pressed="' . ($r['liked_by_me'] ? 'true' : 'false') . '"';
        $html .= ' aria-label="Like this review, ' . (int)$r['like_count'] . ' like' . ((int)$r['like_count'] === 1 ? '' : 's') . '"';
        $html .= ' title="' . (is_logged_in() ? ($r['liked_by_me'] ? 'Unlike this review' : 'Like this review') : 'Log in to like this review') . '">';
        $html .= '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">';
        $html .= '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />';
        $html .= '</svg>';
        $html .= '<span class="like-count">' . (int)$r['like_count'] . '</span>';
        $html .= '</button></form></div>';

        if (!empty($replies[$r['review_id']])) {
            $html .= '<div class="review-replies">';
            foreach ($replies[$r['review_id']] as $rep) {
                $html .= '<div class="reply">';
                $html .= '<div class="reply-meta"><span class="badge badge-admin">Admin</span> ' . e($rep['admin_name']) . ' &middot; ' . e(date('M j, Y', strtotime($rep['created_at'])));
                if (is_admin_logged_in() && (int)$rep['admin_id'] === (int)$_SESSION['admin_id']) {
                    // The admin who wrote this comment may delete it (trash-icon button).
                    $html .= '<form method="post" class="inline-form reply-del" data-confirm="Delete this admin comment?">';
                    $html .= csrf_field() . '<input type="hidden" name="action" value="delete_reply">';
                    $html .= '<input type="hidden" name="reply_id" value="' . (int)$rep['reply_id'] . '">';
                    $html .= '<button class="reply-del-btn" type="submit" title="Delete this comment" aria-label="Delete this comment">' . trash_icon() . '</button></form>';
                }
                $html .= '</div>';
                $html .= '<p class="review-text">' . e($rep['comment']) . '</p></div>';
            }
            $html .= '</div>';
        }

        return $html;
    }
}

if (!function_exists('review_form')) {
    function review_form($back, $equipment_id = 0) {
        $html = '<form method="post" class="form-box review-form" enctype="multipart/form-data">';
        $html .= csrf_field() . '<input type="hidden" name="action" value="review">';
        if ($equipment_id > 0) {
            $html .= '<input type="hidden" name="equipment_id" value="' . (int)$equipment_id . '">';
        } else {
            // No product preselected: show the picker (same options as the Reviews page).
            $html .= '<div class="form-group"><label for="review-product">Product (optional)</label>';
            $html .= '<select id="review-product" name="equipment_id">';
            $html .= '<option value="0">Any product / All equipment</option>';
            foreach (review_products() as $prod) {
                $html .= '<option value="' . (int)$prod['equipment_id'] . '">' . e($prod['name']) . '</option>';
            }
            $html .= '</select></div>';
        }
        $html .= '<div class="form-group">';
        $html .= '<label id="rating-label">Your rating</label>';
        $html .= '<div class="star-input" role="radiogroup" aria-labelledby="rating-label">';
        for ($i = 5; $i >= 1; $i--) {
            $html .= '<input type="radio" id="star' . $i . '" name="rating" value="' . $i . '" required>';
            $html .= '<label for="star' . $i . '" title="' . $i . ' stars">&#9733;</label>';
        }
        $html .= '</div></div>';
        $html .= '<div class="form-group"><label for="rev-text">Your review</label>';
        $html .= '<textarea id="rev-text" name="comment" maxlength="2000" required';
        $html .= ' placeholder="How is the equipment holding up? Quality, delivery, after-sales service..."></textarea></div>';
        $html .= '<div class="form-group"><label for="rev-img">Photo (optional)</label>';
        $html .= '<img id="rev-prev" alt="" hidden>';
        $html .= '<input type="file" id="rev-img" name="image" accept="image/*" data-preview="#rev-prev">';
        $html .= '<p class="help">JPG, PNG, WEBP or GIF, up to ' . (MAX_IMAGE_SIZE / 1048576) . ' MB.</p></div>';
        $html .= '<button class="btn" type="submit">Post review</button>';
        $html .= '</form>';
        return $html;
    }
}