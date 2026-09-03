<?php
require_once __DIR__ . '/../../includes/config.php';
header('Content-Type: application/json; charset=utf-8');

$admin = require_login_api();
if ($admin['role'] !== 'admin') json_out(['ok' => false, 'message' => 'Khusus admin.'], 403);

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Cari karyawan untuk presensi manual
    $q = trim($_GET['q'] ?? '');
    $sql = "SELECT id, member_no, name, status FROM users WHERE role = 'member'";
    $params = [];
    if ($q !== '') {
        $sql .= " AND name LIKE :q";
        $params[':q'] = '%' . $q . '%';
    }
    $sql .= " ORDER BY name ASC LIMIT 20";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    json_out(['ok' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

// POST -> check-in manual untuk member tertentu
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$userId = (int) ($input['user_id'] ?? 0);

$u = $pdo->prepare("SELECT * FROM users WHERE id = :id AND role = 'member'");
$u->execute([':id' => $userId]);
if (!$u->fetch()) json_out(['ok' => false, 'message' => 'Karyawan tidak ditemukan.'], 404);

auto_close_stale_sessions($pdo);
$active = $pdo->prepare("SELECT id FROM sessions WHERE user_id = :uid AND status = 'sedang_latihan'");
$active->execute([':uid' => $userId]);
if ($active->fetch()) json_out(['ok' => false, 'message' => 'Karyawan ini masih punya sesi aktif.']);

$now = date('Y-m-d H:i:s');
$pdo->prepare("INSERT INTO sessions (user_id, branch, check_in, status) VALUES (:uid, :b, :ci, 'sedang_latihan')")
    ->execute([':uid' => $userId, ':b' => BRANCH_NAME, ':ci' => $now]);

json_out(['ok' => true, 'message' => 'Check-in manual berhasil dicatat.']);
