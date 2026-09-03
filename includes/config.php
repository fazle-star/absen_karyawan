<?php
// ============================================================
// config.php — koneksi database MySQL Aiven, session, helper umum
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('BASE_PATH', dirname(__DIR__));
define('BRANCH_NAME', 'Condong Catur'); // nama cabang default
define('APP_TIMEZONE', 'Asia/Jakarta');
date_default_timezone_set(APP_TIMEZONE);

// ------------------------------------------------------------
// KREDENSIAL DATABASE MYSQL AIVEN CLOUD
// ------------------------------------------------------------
define('DB_HOST', 'mysql-17e7c2c4-absen-karyawan.b.aivencloud.com'); // Host dari Aiven
define('DB_PORT', '21728');                                          // Port dari Aiven
define('DB_NAME', 'defaultdb');                                      // Database default Aiven
define('DB_USER', 'avnadmin');                                       // User default Aiven
define('DB_PASS', '');                  // Ganti dengan password asli Aiven-mu

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        
        // Path ke sertifikat SSL ca.pem yang kamu download dari Aiven
        $ca_cert_path = __DIR__ . '/ca.pem'; 

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Opsi Wajib SSL untuk Aiven Cloud
            PDO::MYSQL_ATTR_SSL_CA       => $ca_cert_path,
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
        ];

        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

        // Buat tabel otomatis jika belum ada di Aiven (sekali saja saat pertama dijalankan)
        $exists = $pdo->query("SHOW TABLES LIKE 'users'")->fetch();
        if (!$exists) {
            init_schema($pdo);
        }
    }
    return $pdo;
}

function init_schema(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            member_no VARCHAR(20) UNIQUE,
            name VARCHAR(150) NOT NULL,
            username VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(20) NOT NULL DEFAULT 'member',
            gender VARCHAR(20),
            religion VARCHAR(50),
            birthplace VARCHAR(100),
            birthdate DATE NULL,
            address VARCHAR(255),
            job VARCHAR(100),
            status VARCHAR(20) NOT NULL DEFAULT 'Aktif',
            branch VARCHAR(100) DEFAULT 'Condong Catur',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS attendance_codes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(20) NOT NULL,
            valid_date DATE NOT NULL,
            branch VARCHAR(100) DEFAULT 'Condong Catur',
            active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS sessions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            branch VARCHAR(100) DEFAULT 'Condong Catur',
            check_in DATETIME NOT NULL,
            check_out DATETIME NULL,
            duration_minutes INT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'sedang_latihan',
            auto_checkout TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // Buat akun admin default supaya bisa langsung login pertama kali
    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (member_no, name, username, password, role, status)
                            VALUES ('ADMIN', 'Administrator', 'admin', :p, 'admin', 'Aktif')");
    $stmt->execute([':p' => $hash]);
}

function json_out(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(): array
{
    $u = current_user();
    if (!$u) {
        header('Location: /login.php');
        exit;
    }
    return $u;
}

function require_role(string $role): array
{
    $u = require_login();
    if ($u['role'] !== $role) {
        header('Location: /login.php');
        exit;
    }
    return $u;
}

function require_login_api(): array
{
    $u = current_user();
    if (!$u) {
        json_out(['ok' => false, 'message' => 'Sesi login habis, silakan login ulang.'], 401);
    }
    return $u;
}

function next_member_no(PDO $pdo): string
{
    $max = (int) $pdo->query("SELECT COALESCE(MAX(CAST(member_no AS UNSIGNED)), 3346) FROM users WHERE member_no REGEXP '^[0-9]+$'")->fetchColumn();
    return (string) ($max + 1);
}

function gen_daily_code(PDO $pdo): array
{
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT * FROM attendance_codes WHERE valid_date = :d AND branch = :b ORDER BY id DESC LIMIT 1");
    $stmt->execute([':d' => $today, ':b' => BRANCH_NAME]);
    $row = $stmt->fetch();
    if ($row) {
        return $row;
    }
    $code = strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
    $ins = $pdo->prepare("INSERT INTO attendance_codes (code, valid_date, branch, active) VALUES (:c, :d, :b, 1)");
    $ins->execute([':c' => $code, ':d' => $today, ':b' => BRANCH_NAME]);
    $stmt->execute([':d' => $today, ':b' => BRANCH_NAME]);
    return $stmt->fetch();
}

/** Auto check-out sesi yang masih terbuka dari hari-hari sebelumnya (dianggap lupa checkout) */
function auto_close_stale_sessions(PDO $pdo): void
{
    $stmt = $pdo->prepare("SELECT * FROM sessions WHERE status = 'sedang_latihan' AND DATE(check_in) < :today");
    $stmt->execute([':today' => date('Y-m-d')]);
    foreach ($stmt->fetchAll() as $s) {
        $checkIn = new DateTime($s['check_in']);
        $checkOut = (clone $checkIn)->modify('+150 minutes'); // batas wajar 2.5 jam
        $duration = 150;
        $upd = $pdo->prepare("UPDATE sessions SET check_out = :co, duration_minutes = :dur, status = 'selesai', auto_checkout = 1 WHERE id = :id");
        $upd->execute([':co' => $checkOut->format('Y-m-d H:i:s'), ':dur' => $duration, ':id' => $s['id']]);
    }
}

function format_duration(int $minutes): string
{
    $h = intdiv($minutes, 60);
    $m = $minutes % 60;
    if ($h > 0 && $m > 0) return "{$h} Jam {$m} Menit";
    if ($h > 0) return "{$h} Jam";
    return "{$m} Menit";
}