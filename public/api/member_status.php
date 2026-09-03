<?php
require_once __DIR__ . '/../../includes/config.php';
header('Content-Type: application/json; charset=utf-8');

$user = require_login_api();
$pdo = db();
auto_close_stale_sessions($pdo);

$stmt = $pdo->prepare("SELECT * FROM sessions WHERE user_id = :uid AND status = 'sedang_latihan' ORDER BY id DESC LIMIT 1");
$stmt->execute([':uid' => $user['id']]);
$session = $stmt->fetch(PDO::FETCH_ASSOC);

json_out([
    'ok' => true,
    'active' => (bool) $session,
    'session' => $session ?: null,
]);
