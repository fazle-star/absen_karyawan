<?php
require_once __DIR__ . '/../../includes/config.php';
header('Content-Type: application/json; charset=utf-8');

$admin = require_login_api();
if ($admin['role'] !== 'admin') json_out(['ok' => false, 'message' => 'Khusus admin.'], 403);

$pdo = db();
auto_close_stale_sessions($pdo);

$search = trim($_GET['q'] ?? '');
$sql = "SELECT s.*, u.name, u.member_no, u.status as member_status
        FROM sessions s
        JOIN users u ON u.id = s.user_id
    WHERE DATE(s.check_in) = :today";
$params = [':today' => date('Y-m-d')];
if ($search !== '') {
    $sql .= " AND u.name LIKE :q";
    $params[':q'] = '%' . $search . '%';
}
$sql .= " ORDER BY s.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$out = array_map(function ($r) {
    return [
        'session_id' => (int) $r['id'],
        'member_no' => $r['member_no'],
        'name' => $r['name'],
        'member_status' => $r['member_status'],
        'check_in' => date('H:i:s', strtotime($r['check_in'])),
        'check_in_raw' => $r['check_in'],
        'check_out' => $r['check_out'] ? date('H:i:s', strtotime($r['check_out'])) : null,
        'status' => $r['status'], // sedang_latihan | selesai
        'duration_text' => $r['duration_minutes'] ? format_duration((int) $r['duration_minutes']) : null,
    ];
}, $rows);

$total = count($out);
$aktif = count(array_filter($out, fn($r) => $r['status'] === 'sedang_latihan'));

json_out(['ok' => true, 'total' => $total, 'sedang_latihan' => $aktif, 'data' => $out]);
