<?php
require_once __DIR__ . '/../../includes/config.php';
header('Content-Type: application/json; charset=utf-8');

$user = require_login_api();
if ($user['role'] !== 'member') {
    json_out(['ok' => false, 'message' => 'Hanya karyawan yang bisa melakukan presensi.'], 403);
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$scanned = strtoupper(trim($input['code'] ?? ''));

if ($scanned === '') {
    json_out(['ok' => false, 'message' => 'Kode QR tidak terbaca, coba scan ulang.']);
}

$pdo = db();
auto_close_stale_sessions($pdo);

// Cek apakah member sudah punya sesi aktif
$active = $pdo->prepare("SELECT * FROM sessions WHERE user_id = :uid AND status = 'sedang_latihan' LIMIT 1");
$active->execute([':uid' => $user['id']]);
if ($row = $active->fetch(PDO::FETCH_ASSOC)) {
    json_out(['ok' => false, 'message' => 'Kamu masih punya sesi presensi yang aktif.', 'already_active' => true]);
}

// Validasi kode hari ini
$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT * FROM attendance_codes WHERE valid_date = :d AND active = 1 ORDER BY id DESC LIMIT 1");
$stmt->execute([':d' => $today]);
$codeRow = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$codeRow || strtoupper($codeRow['code']) !== $scanned) {
    json_out(['ok' => false, 'message' => 'Kode QR tidak valid atau sudah kedaluwarsa.']);
}

$now = date('Y-m-d H:i:s');
$ins = $pdo->prepare("INSERT INTO sessions (user_id, branch, check_in, status) VALUES (:uid, :b, :ci, 'sedang_latihan')");
$ins->execute([':uid' => $user['id'], ':b' => $codeRow['branch'], ':ci' => $now]);

json_out([
    'ok' => true,
    'message' => 'Check-in berhasil, selamat bekerja!',
    'session' => [
        'id' => (int) $pdo->lastInsertId(),
        'check_in' => $now,
        'status' => 'sedang_latihan',
    ],
]);
