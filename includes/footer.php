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
      <p>
        <?php if (setting('shop_phone') !== ''): ?>Tel: <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', setting('shop_phone'))) ?>"><?= e(setting('shop_phone')) ?></a><br><?php endif; ?>
        <?php if (setting('shop_mobile') !== ''): ?>Mobile: <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', setting('shop_mobile'))) ?>"><?= e(setting('shop_mobile')) ?></a><br><?php endif; ?>
        <?php if (setting('shop_email') !== ''): ?>Email: <a href="mailto:<?= e(setting('shop_email')) ?>"><?= e(setting('shop_email')) ?></a><br><?php endif; ?>
        <?php if (setting('shop_facebook') !== ''): ?>Facebook: <a href="<?= e(setting('shop_facebook')) ?>" target="_blank" rel="noopener noreferrer">Visit our page</a><?php endif; ?>
      </p>
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
