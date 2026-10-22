</main>
<?php
/* Small line icons for the footer (24x24 outline drawings, coloured by the text). */
$ficon = function ($name) {
    $paths = [
        'pin'       => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
        'phone'     => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>',
        'mobile'    => '<rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/>',
        'mail'      => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>',
        'facebook'  => '<path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>',
        'instagram' => '<rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/>',
        'bag'       => '<path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/>',
        'cart'      => '<circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>',
    ];
    return '<svg class="footer-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" '
         . 'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths[$name] . '</svg>';
};
$tel = fn($n) => 'tel:' . preg_replace('/[^0-9+]/', '', $n);
?>
<footer class="site-footer">
  <div class="container footer-grid">
    <div>
      <img class="footer-logo" src="<?= url('assets/images/logo.png') ?>" alt="Kabakal Fitness Equipment" width="720" height="229">
      <p><?= e(setting('shop_about')) ?></p>
      <?php if (setting('shop_facebook') !== '' || setting('shop_instagram') !== ''): ?>
      <div class="footer-social">
        <?php if (setting('shop_facebook') !== ''): ?><a href="<?= e(setting('shop_facebook')) ?>" target="_blank" rel="noopener noreferrer" aria-label="Facebook" title="Facebook"><?= $ficon('facebook') ?></a><?php endif; ?>
        <?php if (setting('shop_instagram') !== ''): ?><a href="<?= e(setting('shop_instagram')) ?>" target="_blank" rel="noopener noreferrer" aria-label="Instagram" title="Instagram"><?= $ficon('instagram') ?></a><?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
    <div>
      <h3>Visit or contact us</h3>
      <ul class="footer-list">
        <?php if (setting('shop_address') !== ''): ?><li><?= $ficon('pin') ?><span class="sr-only">Address: </span>
          <?php if (setting('shop_map') !== ''): ?><a href="<?= e(setting('shop_map')) ?>" target="_blank" rel="noopener noreferrer"><?= e(setting('shop_address')) ?></a>
          <?php else: ?><span><?= e(setting('shop_address')) ?></span><?php endif; ?></li><?php endif; ?>
        <?php if (setting('shop_phone') !== ''): ?><li><?= $ficon('phone') ?><span class="sr-only">Telephone: </span><a href="<?= e($tel(setting('shop_phone'))) ?>"><?= e(setting('shop_phone')) ?></a></li><?php endif; ?>
        <?php if (setting('shop_mobile') !== ''): ?><li><?= $ficon('mobile') ?><span class="sr-only">Mobile: </span><a href="<?= e($tel(setting('shop_mobile'))) ?>"><?= e(setting('shop_mobile')) ?></a></li><?php endif; ?>
        <?php if (setting('shop_email') !== ''): ?><li><?= $ficon('mail') ?><span class="sr-only">Email: </span><a href="mailto:<?= e(setting('shop_email')) ?>"><?= e(setting('shop_email')) ?></a></li><?php endif; ?>
      </ul>
    </div>
    <div>
      <h3>Shop</h3>
      <ul class="footer-list">
        <li><?= $ficon('bag') ?><a href="<?= url('products.php') ?>">All products</a></li>
        <li><?= $ficon('cart') ?><a href="<?= url('cart.php') ?>">My cart</a></li>
      </ul>
      <!--<a href="<?= url('admin/login.php') ?>">Staff login</a> -->
    </div>
  </div>
  <div class="copy">&copy; <?= date('Y') ?> <?= e(setting('shop_name')) ?>. All rights reserved.</div>
</footer>
<script src="<?= url('assets/js/main.js') ?>"></script>
</body>
</html>
