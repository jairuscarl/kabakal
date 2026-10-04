<?php
/**
 * Admin > Promo Videos: add (MP4 upload OR Facebook link), show/hide, delete.
 * Note: XAMPP's default upload limit is small (2 MB). See the README to raise it.
 */
require_once __DIR__ . '/../includes/init.php';
require_admin(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? ''; $id = (int)($_POST['video_id'] ?? 0);

    if ($action === 'add') {
        $title = trim($_POST['title'] ?? ''); $type = $_POST['source_type'] ?? 'file'; $fb = trim($_POST['embed_url'] ?? '');
        $err = null; $file = null; $poster = null;
        if ($title === '' || strlen($title) > 150) $err = 'Please enter a title (max 150 characters).';
        elseif ($type === 'facebook') {
            if (!is_facebook_url($fb)) $err = 'Please paste a valid Facebook video link (https://www.facebook.com/... or https://fb.watch/...).';
            else {
                $fb = facebook_canonical_url($fb);   // turn a "Share > Copy link" short link into the real video URL
                if (!is_embeddable_facebook_url($fb)) $err = 'That is not a video page. Open the video on Facebook, then copy the address-bar link (facebook.com/yourpage/videos/123456 or facebook.com/reel/123456).';
            }
        }
        else { [$file, $err] = upload_video('video'); if (!$err && !$file) $err = 'Please choose an MP4 video file.'; }
        if (!$err) { [$poster, $err] = upload_image('poster', 'uploads/posters'); }

        if ($err) { delete_file($file); delete_file($poster); flash('error', $err); }
        else {
            $pdo->prepare('INSERT INTO promo_video (title, source_type, file_path, embed_url, poster) VALUES (?, ?, ?, ?, ?)')
                ->execute([$title, $type === 'facebook' ? 'facebook' : 'file', $file, $type === 'facebook' ? $fb : null, $poster]);
            flash('success', 'Video added.');
        }
    } elseif ($action === 'toggle') {
        $pdo->prepare('UPDATE promo_video SET is_visible = 1 - is_visible WHERE video_id = ?')->execute([$id]);
        flash('success', 'Video visibility changed.');
    } elseif ($action === 'delete') {
        $st = $pdo->prepare('SELECT file_path, poster FROM promo_video WHERE video_id = ?'); $st->execute([$id]);
        if ($v = $st->fetch()) {
            $pdo->prepare('DELETE FROM promo_video WHERE video_id = ?')->execute([$id]);
            delete_file($v['file_path']); delete_file($v['poster']);
            flash('success', 'Video deleted.');
        }
    }
    redirect('admin/videos.php');
}

$videos = $pdo->query('SELECT * FROM promo_video ORDER BY video_id DESC')->fetchAll();
$page_title = 'Promo Videos';
require __DIR__ . '/_header.php';
?>
<h1>Promo Videos</h1>
<form method="post" enctype="multipart/form-data" class="form-box form-wide">
  <h3>Add a video</h3>
  <?= csrf_field() ?><input type="hidden" name="action" value="add">
  <div class="form-group"><label for="t">Title</label><input type="text" id="t" name="title" maxlength="150" required></div>
  <div class="form-group"><label for="st">Video source</label>
    <select id="st" name="source_type" onchange="document.getElementById('f-file').hidden = this.value !== 'file'; document.getElementById('f-fb').hidden = this.value !== 'facebook';">
      <option value="file">Upload MP4 file</option><option value="facebook">Facebook video link</option></select></div>
  <div class="form-group" id="f-file"><label for="v">MP4 file (max <?= MAX_VIDEO_SIZE / 1048576 ?> MB)</label><input type="file" id="v" name="video" accept="video/mp4"></div>
  <div class="form-group" id="f-fb" hidden><label for="fb">Facebook video / reel link</label><input type="url" id="fb" name="embed_url" placeholder="https://www.facebook.com/yourpage/videos/123456789">
    <p class="help">Open the video on Facebook and copy the link in the <strong>address bar</strong>. Do not use "Share &gt; Copy link" (facebook.com/share/...) &mdash; those short links cannot be embedded.</p></div>
  <div class="form-group"><label for="p">Poster image (optional)</label><input type="file" id="p" name="poster" accept="image/*">
    <p class="help">Shown while the video loads. Makes the page faster on slow connections.</p></div>
  <button class="btn" type="submit">Add video</button>
</form>

<div class="table-wrap" style="margin-top:24px"><table>
  <tr><th>Title</th><th>Type</th><th>Visible</th><th>Added</th><th></th></tr>
  <?php foreach ($videos as $v): ?>
  <tr><td><?= e($v['title']) ?></td><td><?= $v['source_type'] === 'facebook' ? 'Facebook link' : 'MP4 file' ?></td>
    <td><?= $v['is_visible'] ? badge('active') : badge('inactive') ?></td><td><?= e(date('M j, Y', strtotime($v['created_at']))) ?></td>
    <td class="actions">
      <form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="video_id" value="<?= (int)$v['video_id'] ?>">
        <button class="btn btn-sm btn-outline" type="submit"><?= $v['is_visible'] ? 'Hide' : 'Show' ?></button></form>
      <form method="post" class="inline-form" data-confirm="Delete this video?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="video_id" value="<?= (int)$v['video_id'] ?>">
        <button class="btn btn-sm btn-danger" type="submit">Delete</button></form></td></tr>
  <?php endforeach; ?>
  <?php if (!$videos): ?><tr><td colspan="5" class="empty">No videos yet. Add your first promo video above.</td></tr><?php endif; ?>
</table></div>
<?php require __DIR__ . '/_footer.php'; ?>
