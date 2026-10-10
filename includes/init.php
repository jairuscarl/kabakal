<?php
/**
 * includes/init.php
 * Loaded at the top of every page. Sets up: session, database (PDO),
 * CSRF protection, and helper functions used all over the site.
 */
require_once __DIR__ . '/../config.php';

// Show or hide PHP errors depending on DEBUG in config.php
ini_set('display_errors', DEBUG ? '1' : '0');
error_reporting(E_ALL);

session_start();

// ---------- Database connection (PDO) ----------
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER, DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // throw errors
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,         // rows as arrays
            PDO::ATTR_EMULATE_PREPARES   => false,                    // real prepared statements
        ]
    );
} catch (PDOException $e) {
    die(DEBUG ? 'Database error: ' . $e->getMessage() : 'Site is temporarily unavailable.');
}

// ---------- Schema self-check ----------
// Databases imported from an older database.sql do not have the "date stock was added"
// column yet. Create (and backfill) it here once so the stock pages always work.
try {
    $has_stock_date = $pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS
                                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'equipment'
                                     AND COLUMN_NAME = 'last_restocked_at'")->fetchColumn();
    if (!$has_stock_date) {
        $pdo->exec('ALTER TABLE equipment ADD COLUMN last_restocked_at DATETIME NULL AFTER created_at');
        $pdo->exec('UPDATE equipment SET last_restocked_at = created_at WHERE stock_quantity > 0');
    }
} catch (PDOException $e) { /* ignored on purpose: the check simply runs again on the next request */ }

// ---------- Small helpers ----------

/** Escape text before printing it in HTML (prevents XSS). */
function e($text) { return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8'); }

/** Format a number as pesos: 1500 -> ₱1,500.00 */
function peso($n) { return '₱' . number_format((float)$n, 2); }

/** Build a URL for a page or file, e.g. url('products.php') */
function url($path = '') { return BASE_URL . '/' . ltrim($path, '/'); }

/** Redirect to a page inside the site and stop. */
function redirect($path) { header('Location: ' . url($path)); exit; }

/** Store a one-time message (type: success | error | info). */
function flash($type, $msg) { $_SESSION['flash'][] = [$type, $msg]; }

/** Print and clear the stored messages. */
function show_flash() {
    foreach ($_SESSION['flash'] ?? [] as [$type, $msg]) {
        echo '<div class="alert alert-' . e($type) . '">' . e($msg) . '</div>';
    }
    unset($_SESSION['flash']);
}

// ---------- CSRF protection ----------
// Every POST form must include csrf_field(). We check the token here automatically.
if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
function csrf_field() { return '<input type="hidden" name="csrf" value="' . e($_SESSION['csrf']) . '">'; }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        http_response_code(400);
        die('Invalid form token. Please go back, refresh the page and try again.');
    }
}

// ---------- Settings (shop info, GCash details) ----------
function settings() {
    global $pdo; static $cache = null;
    if ($cache === null) {
        $cache = $pdo->query('SELECT setting_key, setting_value FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    return $cache;
}
function setting($key, $default = '') { return settings()[$key] ?? $default; }

// ---------- Login / roles ----------
function is_logged_in() { return isset($_SESSION['customer_id']); }
function is_admin_logged_in() { return isset($_SESSION['admin_id']); }

/** Customers must log in to check out / see their account. */
function require_login() {
    if (!is_logged_in()) {
        flash('info', 'Please log in to continue.');
        redirect('login.php?next=' . urlencode(basename($_SERVER['SCRIPT_NAME'])));
    }
}

/** Admin pages: must be logged in as admin/staff. $roles limits the page to certain roles. */
function require_admin(array $roles = []) {
    if (!is_admin_logged_in()) { redirect('admin/login.php'); }
    if ($roles && !in_array($_SESSION['admin_role'], $roles, true)) {
        flash('error', 'You do not have permission to open that page.');
        redirect('admin/index.php');
    }
}

// ---------- File uploads (images / videos) ----------

/**
 * Save an uploaded image. Returns [relative_path, error_message].
 * relative_path is null when nothing was uploaded or something was wrong.
 */
function upload_image($field, $folder) {
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) return [null, null];
    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK)  return [null, 'Upload failed (file may be too large for the server).'];
    if ($f['size'] > MAX_IMAGE_SIZE)    return [null, 'Image is too big. Maximum is ' . (MAX_IMAGE_SIZE / 1048576) . ' MB.'];

    // Check the REAL file type (not just the extension)
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    if (!isset($allowed[$mime]) || !@getimagesize($f['tmp_name'])) return [null, 'Only JPG, PNG, WEBP or GIF images are allowed.'];

    $name = bin2hex(random_bytes(12)) . '.' . $allowed[$mime];   // random safe file name
    // Make sure the destination folder exists (it may be missing on a fresh checkout/XAMPP copy).
    $folder = trim($folder, '/');
    if ($folder === '' || str_contains($folder, '..')) return [null, 'Invalid upload folder.'];
    $dir = ROOT_PATH . "/$folder";
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        return [null, 'Could not save the image. Upload folder is missing and could not be created.'];
    }
    if (!is_writable($dir)) return [null, 'Could not save the image. Check folder permissions.'];
    if (!move_uploaded_file($f['tmp_name'], "$dir/$name")) return [null, 'Could not save the image. Check folder permissions.'];
    return ["$folder/$name", null];
}

/** Save an uploaded MP4 video into assets/videos. Returns [relative_path, error]. */
function upload_video($field) {
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) return [null, null];
    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK) return [null, 'Video upload failed. The file may be larger than the server limit (upload_max_filesize).'];
    if ($f['size'] > MAX_VIDEO_SIZE)   return [null, 'Video is too big. Maximum is ' . (MAX_VIDEO_SIZE / 1048576) . ' MB.'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    if ($mime !== 'video/mp4') return [null, 'Only MP4 videos are allowed.'];
    $name = bin2hex(random_bytes(12)) . '.mp4';
    $dir = ROOT_PATH . '/assets/videos';
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        return [null, 'Could not save the video. Upload folder is missing and could not be created.'];
    }
    if (!is_writable($dir)) return [null, 'Could not save the video. Check folder permissions.'];
    if (!move_uploaded_file($f['tmp_name'], "$dir/$name")) return [null, 'Could not save the video. Check folder permissions.'];
    return ["assets/videos/$name", null];
}

/** Delete a file we previously saved (only inside uploads/ or assets/videos/). */
function delete_file($relPath) {
    if ($relPath && preg_match('#^(uploads/(products|proofs|posters|settings|reviews)|assets/videos)/[A-Za-z0-9._-]+$#', $relPath)) {
        @unlink(ROOT_PATH . '/' . $relPath);
    }
}

/** Product image URL, or a placeholder when none. */
function product_image($path) { return url($path ?: 'assets/images/placeholder.svg'); }

/** Only accept Facebook video links. */
function is_facebook_url($u) { return (bool)preg_match('#^https://((www|m|web)\.)?facebook\.com/|^https://fb\.watch/#i', $u); }

/**
 * "Share > Copy link" short links (facebook.com/share/..., fb.watch/...) are only redirects.
 * The embed player cannot read them, so they must be resolved to the real video URL first.
 */
function is_facebook_share_url($u) { return (bool)preg_match('#^https://((www|m|web)\.)?facebook\.com/(share|r)/|^https://fb\.watch/#i', $u); }

/** Follow a Facebook short link to the real video URL (returns the input unchanged if it is already a full link). */
function facebook_canonical_url($u) {
    $u = trim((string)$u);
    if (!is_facebook_share_url($u) || !function_exists('curl_init')) return $u;
    $ch = curl_init($u);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_USERAGENT      => 'Mozilla/5.0',
    ]);
    curl_exec($ch);
    $final = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);
    return facebook_clean_url($final ?: $u);
}

/** Drop the tracking parameters (rdid, share_url, ...) Facebook adds when you copy a link. */
function facebook_clean_url($u) {
    $bits = parse_url((string)$u);
    if (!$bits || empty($bits['host'])) return (string)$u;
    $drop = ['rdid', 'share_url', 'mibextid', 'sfnsn', 'extid', 'refsrc', '_rdr', 'locale'];
    parse_str($bits['query'] ?? '', $q);
    foreach ($drop as $k) unset($q[$k]);
    $clean = 'https://' . $bits['host'] . ($bits['path'] ?? '');
    return $q ? $clean . '?' . http_build_query($q) : $clean;
}

/** Links the Facebook player can really embed: /videos/<id>, /reel/<id>, /watch/?v=<id>. */
function is_embeddable_facebook_url($u) { return (bool)preg_match('#/(videos|reel|watch)/|/(video|permalink)\.php#i', (string)$u); }

/** URL for Facebook's video player iframe. */
function facebook_embed_src($u) {
    return 'https://www.facebook.com/plugins/video.php?show_text=false&href='
         . urlencode(facebook_clean_url(facebook_canonical_url($u)));
}

// ---------- Shopping cart ----------
// Guests: cart lives in the session. Logged-in customers: cart lives in the database
// (tables cart + cart_item). When a guest logs in, the two carts are merged.

function cart_id() {
    global $pdo;
    $st = $pdo->prepare("SELECT cart_id FROM cart WHERE customer_id = ? AND status = 'active' LIMIT 1");
    $st->execute([$_SESSION['customer_id']]);
    $id = $st->fetchColumn();
    if (!$id) {
        $pdo->prepare('INSERT INTO cart (customer_id) VALUES (?)')->execute([$_SESSION['customer_id']]);
        $id = $pdo->lastInsertId();
    }
    return (int)$id;
}

/** Returns [equipment_id => quantity]. */
function cart_map() {
    global $pdo;
    if (is_logged_in()) {
        $st = $pdo->prepare('SELECT equipment_id, quantity FROM cart_item WHERE cart_id = ?');
        $st->execute([cart_id()]);
        return $st->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    return $_SESSION['cart'] ?? [];
}

/** Set the quantity of one product in the cart (0 removes it). */
function cart_set($eid, $qty) {
    global $pdo;
    $eid = (int)$eid; $qty = (int)$qty;
    if (is_logged_in()) {
        if ($qty <= 0) {
            $pdo->prepare('DELETE FROM cart_item WHERE cart_id = ? AND equipment_id = ?')->execute([cart_id(), $eid]);
        } else {
            $pdo->prepare('INSERT INTO cart_item (cart_id, equipment_id, quantity) VALUES (?, ?, ?)
                           ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)')->execute([cart_id(), $eid, $qty]);
        }
    } else {
        if ($qty <= 0) unset($_SESSION['cart'][$eid]); else $_SESSION['cart'][$eid] = $qty;
    }
}

function cart_clear() {
    global $pdo;
    if (is_logged_in()) { $pdo->prepare('DELETE FROM cart_item WHERE cart_id = ?')->execute([cart_id()]); }
    else { unset($_SESSION['cart']); }
}

/** Cart lines with product info: name, price, image_url, stock_quantity, quantity. */
function cart_items() {
    global $pdo;
    $map = cart_map();
    if (!$map) return [];
    $marks = implode(',', array_fill(0, count($map), '?'));
    $st = $pdo->prepare("SELECT equipment_id, name, price, image_url, stock_quantity FROM equipment
                         WHERE equipment_id IN ($marks) AND status = 'active'");
    $st->execute(array_keys($map));
    $items = $st->fetchAll();
    foreach ($items as &$it) { $it['quantity'] = (int)$map[$it['equipment_id']]; }
    return $items;
}

function cart_count() { return array_sum(cart_map()); }

/** Called right after login: move the guest cart into the customer's saved cart. */
function cart_merge_guest() {
    $guest = $_SESSION['cart'] ?? [];
    unset($_SESSION['cart']);
    $saved = cart_map();
    foreach ($guest as $eid => $qty) { cart_set($eid, ($saved[$eid] ?? 0) + $qty); }
}

// ---------- Inventory helpers ----------
// Stock moves between these statuses:
//   available (= equipment.stock_quantity)  ->  reserved (order placed, not yet paid)
//   reserved -> sold (order paid/shipped/delivered)   |   -> available again if cancelled

/**
 * Change one inventory bucket by $delta.
 * $mark_added = true records the change as stock being ADDED: it stamps
 * equipment.last_restocked_at, the "Date stock added" shown in Admin > Inventory.
 * The stamp only happens when the available quantity actually goes up.
 */
function inv_change($eid, $status, $delta, $admin_id = null, $mark_added = false) {
    global $pdo;
    if ($status === 'available') {
        $pdo->prepare('UPDATE equipment SET stock_quantity = GREATEST(0, CAST(stock_quantity AS SIGNED) + ?),
                              last_restocked_at = IF(?, NOW(), last_restocked_at)
                       WHERE equipment_id = ?')
            ->execute([$delta, ($mark_added && $delta > 0) ? 1 : 0, $eid]);
        // keep the "available" row in the inventory table equal to the product's stock
        $pdo->prepare("INSERT INTO inventory (equipment_id, quantity, status, admin_id)
                       SELECT equipment_id, stock_quantity, 'available', ? FROM equipment WHERE equipment_id = ?
                       ON DUPLICATE KEY UPDATE quantity = VALUES(quantity), admin_id = VALUES(admin_id)")
            ->execute([$admin_id, $eid]);
    } else {
        $pdo->prepare('INSERT INTO inventory (equipment_id, status, quantity, admin_id) VALUES (?, ?, ?, ?)
                       ON DUPLICATE KEY UPDATE quantity = GREATEST(0, CAST(quantity AS SIGNED) + ?), admin_id = VALUES(admin_id)')
            ->execute([$eid, $status, max(0, $delta), $admin_id, $delta]);
    }
}

function inventory_move($eid, $from, $to, $qty, $admin_id = null) {
    if ($from === $to) return;
    inv_change($eid, $from, -$qty, $admin_id);
    inv_change($eid, $to,   +$qty, $admin_id);
}

/** Which inventory bucket an order's items sit in for each order status. */
function order_bucket($status) {
    return ['pending' => 'reserved', 'paid' => 'sold', 'shipped' => 'sold', 'delivered' => 'sold', 'cancelled' => 'available'][$status];
}

/** Change an order's status and move its items between inventory buckets. Returns true/false. */
function set_order_status($order_id, $new, $admin_id = null) {
    global $pdo;
    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare('SELECT status FROM orders WHERE order_id = ? FOR UPDATE');
        $st->execute([$order_id]);
        $old = $st->fetchColumn();
        if ($old === false || $old === $new) { $pdo->rollBack(); return true; }
        if ($old === 'cancelled') { $pdo->rollBack(); return false; }   // cancelled orders stay cancelled

        $from = order_bucket($old); $to = order_bucket($new);
        if ($from !== $to) {
            $items = $pdo->prepare('SELECT equipment_id, quantity FROM order_item WHERE order_id = ?');
            $items->execute([$order_id]);
            foreach ($items->fetchAll() as $it) { inventory_move($it['equipment_id'], $from, $to, $it['quantity'], $admin_id); }
        }
        $pdo->prepare('UPDATE orders SET status = ? WHERE order_id = ?')->execute([$new, $order_id]);
        if ($new === 'shipped') {
            $pdo->prepare('UPDATE orders SET shipping_date = COALESCE(shipping_date, CURDATE()) WHERE order_id = ?')->execute([$order_id]);
        }
        $pdo->commit();
        return true;
    } catch (Throwable $ex) { $pdo->rollBack(); throw $ex; }
}

// ---------- Orders & payments helpers ----------

/**
 * Load orders together with paid amount and number of payments waiting for verification.
 * $where is extra SQL (e.g. 'AND o.customer_id = ?'), $params are its values.
 */
function fetch_orders($where = '', $params = []) {
    global $pdo;
    $st = $pdo->prepare("SELECT o.*, c.full_name AS customer_name,
            COALESCE((SELECT SUM(p.amount) FROM payment p WHERE p.order_id = o.order_id AND p.payment_status = 'paid'), 0) AS paid_amount,
            (SELECT COUNT(*) FROM payment p WHERE p.order_id = o.order_id AND p.payment_status = 'pending') AS pending_count
        FROM orders o JOIN customer c ON c.customer_id = o.customer_id
        WHERE 1=1 $where ORDER BY o.order_id DESC");
    $st->execute($params);
    $rows = $st->fetchAll();
    foreach ($rows as &$r) {
        $r['balance']   = max(0, $r['total_amount'] - $r['paid_amount']);
        $r['pay_state'] = payment_state($r);
    }
    return $rows;
}

/** Returns 'Paid', 'Pending Verification', 'Partially Paid' or 'Unpaid'. */
function payment_state($o) {
    if ($o['paid_amount'] >= $o['total_amount'] && $o['total_amount'] > 0) return 'Paid';
    if ($o['pending_count'] > 0) return 'Pending Verification';
    if ($o['paid_amount'] > 0)   return 'Partially Paid';
    return 'Unpaid';
}

/** After a payment is verified: if the order is fully paid, mark it Paid. */
function refresh_order_after_payment($order_id, $admin_id = null) {
    $rows = fetch_orders('AND o.order_id = ?', [$order_id]);
    if ($rows && $rows[0]['status'] === 'pending' && $rows[0]['pay_state'] === 'Paid') {
        set_order_status($order_id, 'paid', $admin_id);
    }
}

/** Coloured label for a status word. */
function badge($text) {
    $class = strtolower(preg_replace('/[^a-z]+/i', '-', $text));
    return '<span class="badge badge-' . e($class) . '">' . e(ucfirst($text)) . '</span>';
}

/** Five star glyphs for a rating (first $n filled). Used by reviews.php and Admin > Reviews. */
function stars_display($rating) {
    $n = (int)$rating;
    $out = '<span class="stars" role="img" aria-label="' . $n . ' out of 5 stars">';
    for ($i = 1; $i <= 5; $i++) {
        $out .= '<span class="star' . ($i <= $n ? ' on' : '') . '" aria-hidden="true">&#9733;</span>';
    }
    return $out . '</span>';
}

// ---------- Password field with a show/hide (eye) button ----------

/**
 * Password input + label + an eye button so you can see what you typed.
 * The button itself is wired up by assets/js/main.js (toggle in .pw-toggle).
 *
 *   <?= pw_field('password', 'password', 'Password', 'minlength="8" required') ?>
 *
 * $id    = input id (and what the label points at)  $name = form field name
 * $label = text shown above the input              $extra = extra input attributes
 */
function pw_field($id, $name, $label, $extra = '') {
    $eye = '<svg class="icon-eye" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" '
         . 'stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
         . '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
    $eye_off = '<svg class="icon-eye-off" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" '
         . 'stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
         . '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94'
         . 'M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>'
         . '<line x1="1" y1="1" x2="23" y2="23"/></svg>';
    return '<div class="form-group"><label for="' . e($id) . '">' . e($label) . '</label>'
         . '<div class="pw-field"><input type="password" id="' . e($id) . '" name="' . e($name) . '"'
         . ($extra !== '' ? ' ' . $extra : '') . '>'
         . '<button type="button" class="pw-toggle" aria-label="Show password" aria-pressed="false" '
         . 'title="Show password">' . $eye . $eye_off . '</button></div></div>';
}
