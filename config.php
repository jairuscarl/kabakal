<?php
/**
 * config.php  -  THE ONLY FILE YOU EDIT WHEN GOING LIVE.
 *
 * Local (XAMPP):  DB_USER 'root', DB_PASS '' (empty), BASE_URL ''
 * Live hosting :  use the DB details from your hosting panel and set
 *                 BASE_URL to '' (site at domain root) or '/foldername'.
 */

// ---- Database ----
define('DB_HOST', 'localhost');
define('DB_NAME', 'kabakal_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// ---- Site address ----
// Folder path of the site WITHOUT trailing slash. Use '' if the site is at the domain root.
define('BASE_URL', '');

// ---- Other settings ----
define('ROOT_PATH', __DIR__);                  // absolute folder path of the project
define('DEBUG', true);                         // set to false on the live site (hides error details)
define('MAX_IMAGE_SIZE', 3 * 1024 * 1024);     // 3 MB per image
define('MAX_VIDEO_SIZE', 50 * 1024 * 1024);    // 50 MB per promo video
date_default_timezone_set('Asia/Manila');
