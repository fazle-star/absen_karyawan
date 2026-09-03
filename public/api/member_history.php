<?php
require_once __DIR__ . '/../../includes/config.php';
header('Content-Type: application/json; charset=utf-8');

$user = require_login_api();
$pdo = db();

$stmt = $pdo->prepare("SELECT * FROM sessions WHERE user_id = :uid ORDER BY id DESC LIMIT 100");
$stmt->execute([':uid' => $user['id']]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$out = array_map(function ($r) {
    return [
        'id' => (int) $r['id'],
        'branch' => $r['branch'],
        'date' => date('d M Y', strtotime($r['check_in'])),
        'check_in' => date('H:i', strtotime($r['check_in'])),
        'check_out' => $r['check_out'] ? date('H:i', strtotime($r['check_out'])) : null,
        'status' => $r['status'],
        'duration_text' => $r['duration_minutes'] ? format_duration((int) $r['duration_minutes']) : null,
        'auto_checkout' => (bool) $r['auto_checkout'],
    ];
}, $rows);

$totalCount = $pdo->prepare("SELECT COUNT(*) c FROM sessions WHERE user_id = :uid AND status = 'selesai'");
$totalCount->execute([':uid' => $user['id']]);
$total = (int) $totalCount->fetch(PDO::FETCH_ASSOC)['c'];

json_out(['ok' => true, 'total_hadir' => $total, 'history' => $out]);
