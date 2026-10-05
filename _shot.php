<?php
/** TEMPORARY: generate a visible test image + force a login for screenshots. Delete after use. */
require_once __DIR__ . '/includes/init.php';

if (isset($_GET['gen'])) {
    $im = imagecreatetruecolor(400, 280);
    imagefill($im, 0, 0, imagecolorallocate($im, 24, 24, 24));
    imagefilledrectangle($im, 20, 20, 380, 260, imagecolorallocate($im, 255, 166, 0));
    imagestring($im, 5, 118, 132, 'REVIEW PHOTO', imagecolorallocate($im, 0, 0, 0));
    imagepng($im, __DIR__ . '/uploads/reviews/tempshot.png');
    echo 'generated';
    exit;
}
if (($_GET['as'] ?? '') === 'admin') {
    $_SESSION['admin_id'] = 1; $_SESSION['admin_name'] = 'Shop Admin'; $_SESSION['admin_role'] = 'admin';
    redirect(($_GET['to'] ?? '') === 'admin/reviews.php' ? 'admin/reviews.php' : 'reviews.php');
} else {
    $_SESSION['customer_id'] = 2; $_SESSION['customer_name'] = 'Review Tester';
    redirect('reviews.php');
}
