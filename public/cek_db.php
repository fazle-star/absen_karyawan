<?php
// cek_db.php
require_once __DIR__ . '/../includes/config.php';

try {
    $pdo = db();
    echo "<h1 style='font-family:sans-serif;'>🔍 Cek Database Aiven</h1>";

    // 1. Cek Daftar Tabel
    echo "<h3>1. Daftar Tabel di Database:</h3><ul>";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    if (empty($tables)) {
        echo "<li style='color:red;'>Belum ada tabel yang dibuat!</li>";
    } else {
        foreach ($tables as $table) {
            echo "<li style='color:green;'>Tabel: <b>{$table}</b></li>";
        }
    }
    echo "</ul><hr>";

    // 2. Cek Data Users (Pengguna/Karyawan)
    echo "<h3>2. Data Tabel Users:</h3>";
    $users = $pdo->query("SELECT id, member_no, name, username, role, status, created_at FROM users ORDER BY id DESC")->fetchAll();
    if ($users) {
        echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse:collapse; font-family:sans-serif;'>";
        echo "<tr style='background:#f2f2f2;'><th>ID</th><th>No Member</th><th>Nama</th><th>Username</th><th>Role</th><th>Status</th><th>Waktu Dibuat</th></tr>";
        foreach ($users as $u) {
            echo "<tr>
                    <td>{$u['id']}</td>
                    <td><b>{$u['member_no']}</b></td>
                    <td>{$u['name']}</td>
                    <td>{$u['username']}</td>
                    <td>{$u['role']}</td>
                    <td>{$u['status']}</td>
                    <td>{$u['created_at']}</td>
                  </tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:orange;'>Tabel users masih kosong.</p>";
    }
    echo "<hr>";

    // 3. Cek Data Sessions (Riwayat Absensi/Scan)
    echo "<h3>3. Data Tabel Sessions (Riwayat Absen Terbaru):</h3>";
    $sessions = $pdo->query("SELECT * FROM sessions ORDER BY id DESC LIMIT 10")->fetchAll();
    if ($sessions) {
        echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse:collapse; font-family:sans-serif;'>";
        echo "<tr style='background:#f2f2f2;'><th>ID</th><th>User ID</th><th>Cabang</th><th>Check In</th><th>Check Out</th><th>Durasi</th><th>Status</th></tr>";
        foreach ($sessions as $s) {
            echo "<tr>
                    <td>{$s['id']}</td>
                    <td><b>{$s['user_id']}</b></td>
                    <td>{$s['branch']}</td>
                    <td>{$s['check_in']}</td>
                    <td>" . ($s['check_out'] ?? '<i>Belum Checkout</i>') . "</td>
                    <td>" . ($s['duration_minutes'] ? $s['duration_minutes'] . ' Menit' : '-') . "</td>
                    <td><b>{$s['status']}</b></td>
                  </tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:orange;'>Belum ada data absensi/scan yang masuk.</p>";
    }

} catch (Exception $e) {
    echo "<h2 style='color:red;'>❌ Gagal terhubung ke database: " . $e->getMessage() . "</h2>";
}
?>