<?php
/** Customer logout (keeps an admin session, if any, untouched). */
require_once __DIR__ . '/includes/init.php';
unset($_SESSION['customer_id'], $_SESSION['customer_name'], $_SESSION['cart']);
session_regenerate_id(true);
flash('info', 'You have been logged out.');
redirect('index.php');
