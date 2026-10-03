<?php
require_once __DIR__ . '/../includes/init.php';
unset($_SESSION['admin_id'], $_SESSION['admin_name'], $_SESSION['admin_role']);
session_regenerate_id(true);
flash('info', 'Logged out.');
redirect('admin/login.php');
