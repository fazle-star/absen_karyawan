<?php
require_once __DIR__ . '/../includes/config.php';
$u = current_user();
if (!$u) {
    header('Location: /login.php');
} elseif ($u['role'] === 'admin') {
    header('Location: /admin/dashboard.php');
} else {
    header('Location: /member/dashboard.php');
}
exit;
