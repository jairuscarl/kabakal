</main>
<footer class="site-footer">
  <div class="container footer-grid">
    <div>
      <img class="footer-logo" src="<?= url('assets/images/logo.png') ?>" alt="Kabakal Fitness Equipment" width="720" height="229">
      <p><?= e(setting('shop_about')) ?></p>
    </div>
    <div>
      <h3>Visit or contact us</h3>
      <p><?= e(setting('shop_address')) ?></p>
      <p><?= e(setting('shop_phone')) ?><br><?= e(setting('shop_email')) ?></p>
    </div>
    <div>
      <h3>Shop</h3>
      <p><a href="<?= url('products.php') ?>">All products</a><br>
         <a href="<?= url('cart.php') ?>">My cart</a><br>
         <a href="<?= url('admin/login.php') ?>">Staff login</a></p>
    </div>
  </div>
  <div class="copy">&copy; <?= date('Y') ?> <?= e(setting('shop_name')) ?>. All rights reserved.</div>
</footer>
<script src="<?= url('assets/js/main.js') ?>"></script>
</body>
</html>
