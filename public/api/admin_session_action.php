<?php
require_once __DIR__ . '/../../includes/config.php';
header('Content-Type: application/json; charset=utf-8');

$admin = require_login_api();
if ($admin['role'] !== 'admin') json_out(['ok' => false, 'message' => 'Khusus admin.'], 403);

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$sessionId = (int) ($input['session_id'] ?? 0);
$action = $input['action'] ?? '';
$pdo = db();

$find = $pdo->prepare("SELECT * FROM sessions WHERE id = :id");
$find->execute([':id' => $sessionId]);
$session = $find->fetch(PDO::FETCH_ASSOC);
if (!$session) json_out(['ok' => false, 'message' => 'Data presensi tidak ditemukan.'], 404);

if ($action === 'checkout') {
    if ($session['status'] === 'selesai') {
        json_out(['ok' => false, 'message' => 'Sesi ini sudah selesai.']);
    }
    $now = new DateTime();
    $checkIn = new DateTime($session['check_in']);
    $minutes = max(1, (int) ceil(($now->getTimestamp() - $checkIn->getTimestamp()) / 60));
    $pdo->prepare("UPDATE sessions SET check_out = :co, duration_minutes = :dur, status = 'selesai' WHERE id = :id")
        ->execute([':co' => $now->format('Y-m-d H:i:s'), ':dur' => $minutes, ':id' => $sessionId]);
    json_out(['ok' => true, 'message' => 'Karyawan berhasil di-checkout.']);
} elseif ($action === 'delete') {
    $pdo->prepare("DELETE FROM sessions WHERE id = :id")->execute([':id' => $sessionId]);
    json_out(['ok' => true, 'message' => 'Data presensi dihapus.']);
} else {
    json_out(['ok' => false, 'message' => 'Aksi tidak dikenal.'], 400);
}
