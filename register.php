<?php
/** Customer registration. */
require_once __DIR__ . '/includes/init.php';
if (is_logged_in()) redirect('my_account.php');

$f = ['full_name' => '', 'email' => '', 'phone' => '', 'address' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($f as $k => $_) { $f[$k] = trim($_POST[$k] ?? ''); }
    $pass = $_POST['password'] ?? ''; $pass2 = $_POST['password2'] ?? '';
    $errors = [];

    // ---- validation ----
    if (strlen($f['full_name']) < 2 || strlen($f['full_name']) > 100) $errors[] = 'Please enter your full name.';
    if (!filter_var($f['email'], FILTER_VALIDATE_EMAIL))               $errors[] = 'Please enter a valid email address.';
    if ($f['phone'] !== '' && !preg_match('/^[0-9+\-\s()]{7,20}$/', $f['phone'])) $errors[] = 'Phone number looks invalid.';
    if (strlen($pass) < 8)                                             $errors[] = 'Password must be at least 8 characters.';
    if ($pass !== $pass2)                                              $errors[] = 'Passwords do not match.';
    if (!$errors) {
        $st = $pdo->prepare('SELECT 1 FROM customer WHERE email = ?'); $st->execute([$f['email']]);
        if ($st->fetch()) $errors[] = 'That email is already registered. Try logging in.';
    }

    if (!$errors) {
        $pdo->prepare('INSERT INTO customer (full_name, email, phone, password, address) VALUES (?, ?, ?, ?, ?)')
            ->execute([$f['full_name'], $f['email'], $f['phone'] ?: null, password_hash($pass, PASSWORD_DEFAULT), $f['address'] ?: null]);
        session_regenerate_id(true);
        $_SESSION['customer_id']   = (int)$pdo->lastInsertId();
        $_SESSION['customer_name'] = $f['full_name'];
        cart_merge_guest();
        flash('success', 'Welcome to Kabakal Fitness! Your account is ready.');
        redirect('index.php');
    }
    foreach ($errors as $er) flash('error', $er);
}
$page_title = 'Register';
require __DIR__ . '/includes/header.php';
?>
<div class="form-box">
  <h1>Create account</h1>
  <form method="post">
    <?= csrf_field() ?>
    <div class="form-group"><label for="full_name">Full name</label><input type="text" id="full_name" name="full_name" value="<?= e($f['full_name']) ?>" required></div>
    <div class="form-group"><label for="email">Email</label><input type="email" id="email" name="email" value="<?= e($f['email']) ?>" required></div>
    <div class="form-group"><label for="phone">Mobile number</label><input type="tel" id="phone" name="phone" value="<?= e($f['phone']) ?>"></div>
    <div class="form-group"><label for="address">Delivery address</label><input type="text" id="address" name="address" value="<?= e($f['address']) ?>"></div>
    <?= pw_field('password', 'password', 'Password', 'minlength="8" required') ?>
    <?= pw_field('password2', 'password2', 'Confirm password', 'minlength="8" required') ?>
    <button class="btn btn-block" type="submit">Register</button>
  </form>
  <p class="muted">Already have an account? <a href="<?= url('login.php') ?>">Log in</a></p>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
