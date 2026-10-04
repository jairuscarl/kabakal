<?php
/** Customer login. */
require_once __DIR__ . '/includes/init.php';
if (is_logged_in()) redirect('my_account.php');

// "next" = page to open after login (only simple file names like checkout.php are allowed)
$next = $_GET['next'] ?? $_POST['next'] ?? '';
if (!preg_match('/^[a-z_]+\.php$/', $next)) $next = 'my_account.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $st = $pdo->prepare('SELECT customer_id, full_name, password FROM customer WHERE email = ?');
    $st->execute([trim($_POST['email'] ?? '')]);
    $c = $st->fetch();
    if ($c && password_verify($_POST['password'] ?? '', $c['password'])) {
        session_regenerate_id(true);                       // stops session fixation
        $_SESSION['customer_id']   = (int)$c['customer_id'];
        $_SESSION['customer_name'] = $c['full_name'];
        cart_merge_guest();
        redirect($next);
    }
    flash('error', 'Wrong email or password.');
}
$page_title = 'Login';
require __DIR__ . '/includes/header.php';
?>
<div class="form-box">
  <h1>Login</h1>
  <form method="post">
    <?= csrf_field() ?><input type="hidden" name="next" value="<?= e($next) ?>">
    <div class="form-group"><label for="email">Email</label><input type="email" id="email" name="email" required autofocus></div>
    <?= pw_field('password', 'password', 'Password', 'required') ?>
    <button class="btn btn-block" type="submit">Log in</button>
  </form>
  <p class="muted">No account yet? <a href="<?= url('register.php') ?>">Register here</a></p>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
