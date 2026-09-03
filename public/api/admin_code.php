<?php
require_once __DIR__ . '/../../includes/config.php';
header('Content-Type: application/json; charset=utf-8');

$admin = require_login_api();
if ($admin['role'] !== 'admin') json_out(['ok' => false, 'message' => 'Khusus admin.'], 403);

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Nonaktifkan / aktifkan ulang presensi hari ini
    $action = json_decode(file_get_contents('php://input'), true)['action'] ?? '';
    $today = date('Y-m-d');
    if ($action === 'toggle') {
        $row = $pdo->prepare("SELECT * FROM attendance_codes WHERE valid_date = :d ORDER BY id DESC LIMIT 1");
        $row->execute([':d' => $today]);
        $r = $row->fetch(PDO::FETCH_ASSOC);
        if ($r) {
            $new = $r['active'] ? 0 : 1;
            $pdo->prepare("UPDATE attendance_codes SET active = :a WHERE id = :id")->execute([':a' => $new, ':id' => $r['id']]);
        }
    } elseif ($action === 'regenerate') {
        $newCode = strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
        $pdo->prepare("INSERT INTO attendance_codes (code, valid_date, branch, active) VALUES (:c, :d, :b, 1)")
            ->execute([':c' => $newCode, ':d' => $today, ':b' => BRANCH_NAME]);
    }
}

$code = gen_daily_code($pdo);
json_out(['ok' => true, 'code' => $code['code'], 'active' => (bool) $code['active'], 'branch' => $code['branch']]);
