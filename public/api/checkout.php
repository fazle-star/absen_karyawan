<?php
require_once __DIR__ . '/../../includes/config.php';
header('Content-Type: application/json; charset=utf-8');

$user = require_login_api();
if ($user['role'] !== 'member') {
    json_out(['ok' => false, 'message' => 'Aksi tidak diizinkan.'], 403);
}

$pdo = db();
$stmt = $pdo->prepare("SELECT * FROM sessions WHERE user_id = :uid AND status = 'sedang_latihan' ORDER BY id DESC LIMIT 1");
$stmt->execute([':uid' => $user['id']]);
$session = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$session) {
    json_out(['ok' => false, 'message' => 'Tidak ada sesi presensi yang sedang berjalan.']);
}

$now = new DateTime();
$checkIn = new DateTime($session['check_in']);
$minutes = max(1, (int) ceil(($now->getTimestamp() - $checkIn->getTimestamp()) / 60));

$upd = $pdo->prepare("UPDATE sessions SET check_out = :co, duration_minutes = :dur, status = 'selesai' WHERE id = :id");
$upd->execute([
    ':co' => $now->format('Y-m-d H:i:s'),
    ':dur' => $minutes,
    ':id' => $session['id'],
]);

json_out([
    'ok' => true,
    'message' => 'Sesi presensi berhasil diakhiri.',
    'duration_minutes' => $minutes,
    'duration_text' => format_duration($minutes),
]);
