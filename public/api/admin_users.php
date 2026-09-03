<?php
require_once __DIR__ . '/../../includes/config.php';
header('Content-Type: application/json; charset=utf-8');

$admin = require_login_api();
if ($admin['role'] !== 'admin') json_out(['ok' => false, 'message' => 'Khusus admin.'], 403);

$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $q = trim($_GET['q'] ?? '');
    $sql = "SELECT id, member_no, name, username, status, created_at FROM users WHERE role = 'member'";
    $params = [];
    if ($q !== '') {
        $sql .= " AND (name LIKE :q OR username LIKE :q OR member_no LIKE :q)";
        $params[':q'] = '%' . $q . '%';
    }
    $sql .= " ORDER BY id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    json_out(['ok' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? ($method === 'POST' ? 'create' : '');

if ($action === 'create') {
    $name = trim($input['name'] ?? '');
    $username = trim($input['username'] ?? '');
    $password = $input['password'] ?? '';
    $gender = $input['gender'] ?? '';

    if ($name === '' || $username === '' || strlen($password) < 4) {
        json_out(['ok' => false, 'message' => 'Lengkapi nama, username, dan password minimal 4 karakter.']);
    }

    $check = $pdo->prepare('SELECT id FROM users WHERE username = :u');
    $check->execute([':u' => $username]);
    if ($check->fetch()) {
        json_out(['ok' => false, 'message' => 'Username sudah dipakai, pilih yang lain.']);
    }

    $memberNo = next_member_no($pdo);
    $stmt = $pdo->prepare("INSERT INTO users (member_no, name, username, password, role, gender, status)
                            VALUES (:mn, :n, :u, :p, 'member', :g, 'Aktif')");
    $stmt->execute([
        ':mn' => $memberNo,
        ':n' => $name,
        ':u' => $username,
        ':p' => password_hash($password, PASSWORD_DEFAULT),
        ':g' => $gender,
    ]);

    json_out(['ok' => true, 'message' => "Akun karyawan \"$name\" berhasil dibuat.", 'member_no' => $memberNo]);
} elseif ($action === 'toggle_status') {
    $id = (int) ($input['id'] ?? 0);
    $stmt = $pdo->prepare("SELECT status FROM users WHERE id = :id AND role = 'member'");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) json_out(['ok' => false, 'message' => 'Karyawan tidak ditemukan.'], 404);

    $new = $row['status'] === 'Aktif' ? 'Nonaktif' : 'Aktif';
    $pdo->prepare("UPDATE users SET status = :s WHERE id = :id")->execute([':s' => $new, ':id' => $id]);
    json_out(['ok' => true, 'message' => "Status karyawan diubah menjadi $new.", 'status' => $new]);
} elseif ($action === 'reset_password') {
    $id = (int) ($input['id'] ?? 0);
    $newPass = $input['password'] ?? '';
    if (strlen($newPass) < 4) json_out(['ok' => false, 'message' => 'Password minimal 4 karakter.']);
    $stmt = $pdo->prepare("UPDATE users SET password = :p WHERE id = :id AND role = 'member'");
    $stmt->execute([':p' => password_hash($newPass, PASSWORD_DEFAULT), ':id' => $id]);
    json_out(['ok' => true, 'message' => 'Password karyawan berhasil direset.']);
} elseif ($action === 'delete') {
    $id = (int) ($input['id'] ?? 0);
    $pdo->prepare("DELETE FROM users WHERE id = :id AND role = 'member'")->execute([':id' => $id]);
    json_out(['ok' => true, 'message' => 'Akun karyawan dihapus.']);
} else {
    json_out(['ok' => false, 'message' => 'Aksi tidak dikenal.'], 400);
}
