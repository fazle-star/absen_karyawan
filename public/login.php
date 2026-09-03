<?php
require_once __DIR__ . '/../includes/config.php';

if (current_user()) {
    header('Location: ' . (current_user()['role'] === 'admin' ? '/admin/dashboard.php' : '/member/dashboard.php'));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = db()->prepare('SELECT * FROM users WHERE username = :u');
    $stmt->execute([':u' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        unset($user['password']);
        $_SESSION['user'] = $user;
        header('Location: ' . ($user['role'] === 'admin' ? '/admin/dashboard.php' : '/member/dashboard.php'));
        exit;
    }
    $error = 'Username atau password salah.';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Masuk — Absensi Karyawan</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="min-h-screen flex items-center justify-center bg-app px-4">
  <div class="w-full max-w-sm">
    <div class="text-center mb-8">
      <div class="inline-flex items-center gap-2 mb-2">
        <div class="w-10 h-10 rounded-xl bg-brand flex items-center justify-center font-black text-black">AK</div>
        <span class="font-bold text-lg tracking-tight text-white">ABSENSI KARYAWAN</span>
      </div>
      <p class="text-slate-400 text-sm">Presensi digital berbasis QR Code</p>
    </div>

    <form method="post" class="glass rounded-2xl p-6 space-y-4">
      <h1 class="text-white font-semibold text-xl mb-1">Masuk</h1>
      <?php if ($error): ?>
        <div class="text-sm bg-red-500/10 text-red-400 border border-red-500/30 rounded-lg px-3 py-2"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <div>
        <label class="text-xs uppercase tracking-wide text-slate-400">Username</label>
        <input name="username" required autofocus class="mt-1 w-full input-field" placeholder="username kamu">
      </div>
      <div>
        <label class="text-xs uppercase tracking-wide text-slate-400">Password</label>
        <input type="password" name="password" required class="mt-1 w-full input-field" placeholder="••••••••">
      </div>

      <button class="w-full btn-brand">Masuk</button>

      <p class="text-center text-xs text-slate-500 pt-1">Akun karyawan dibuat oleh admin. Hubungi admin jika belum punya akun.</p>
      <p class="text-center text-[11px] text-slate-600 pt-2">Admin default — username: <b>admin</b> / password: <b>admin123</b></p>
    </form>
  </div>
</body>
</html>
