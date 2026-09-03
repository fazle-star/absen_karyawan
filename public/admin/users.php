<?php
require_once __DIR__ . '/../../includes/config.php';
$admin = require_role('admin');
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kelola Karyawan — Absensi Karyawan</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="bg-app min-h-screen text-slate-200">

<header class="glass sticky top-0 z-30 px-5 py-3 flex items-center justify-between">
  <div class="flex items-center gap-3">
    <a href="/admin/dashboard.php" class="btn-ghost !px-2.5 !py-1.5 text-sm">← Dashboard</a>
    <span class="font-bold text-white hidden sm:inline">Kelola Karyawan</span>
  </div>
  <div class="flex items-center gap-3">
    <span class="text-sm text-slate-400 hidden sm:inline">Halo, <b class="text-white"><?= htmlspecialchars($admin['name']) ?></b></span>
    <a href="/logout.php" class="btn-ghost text-sm">Keluar</a>
  </div>
</header>

<main class="max-w-4xl mx-auto p-5 space-y-5">

  <div class="glass rounded-2xl p-6">
    <h2 class="font-semibold text-white mb-4">Tambah Akun Karyawan</h2>
    <form id="createForm" class="grid sm:grid-cols-2 gap-4">
      <div>
        <label class="text-xs uppercase tracking-wide text-slate-400">Nama Lengkap</label>
        <input name="name" required class="mt-1 w-full input-field" placeholder="Nama karyawan">
      </div>
      <div>
        <label class="text-xs uppercase tracking-wide text-slate-400">Jenis Kelamin</label>
        <select name="gender" class="mt-1 w-full input-field">
          <option value="Laki-laki">Laki-laki</option>
          <option value="Perempuan">Perempuan</option>
        </select>
      </div>
      <div>
        <label class="text-xs uppercase tracking-wide text-slate-400">Username</label>
        <input name="username" required class="mt-1 w-full input-field" placeholder="username login">
      </div>
      <div>
        <label class="text-xs uppercase tracking-wide text-slate-400">Password</label>
        <input name="password" required class="mt-1 w-full input-field" placeholder="minimal 4 karakter">
      </div>
      <div class="sm:col-span-2">
        <button class="btn-brand">+ Buat Akun Karyawan</button>
      </div>
    </form>
  </div>

  <div class="glass rounded-2xl p-6">
    <div class="flex items-center justify-between mb-4">
      <h2 class="font-semibold text-white">Daftar Karyawan</h2>
      <input id="search" class="input-field text-sm py-1.5 w-56" placeholder="Cari nama / username...">
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="text-left text-slate-500 border-b border-white/10">
            <th class="py-2 pr-3 font-medium">ID</th>
            <th class="py-2 pr-3 font-medium">Nama</th>
            <th class="py-2 pr-3 font-medium">Username</th>
            <th class="py-2 pr-3 font-medium">Status</th>
            <th class="py-2 pr-3 font-medium text-right">Aksi</th>
          </tr>
        </thead>
        <tbody id="tableBody"></tbody>
      </table>
    </div>
  </div>
</main>

<div id="toastWrap" class="fixed bottom-5 right-5 z-50 space-y-2"></div>

<script>
function toast(msg, ok = true) {
  const el = document.createElement('div');
  el.className = `toast-enter glass rounded-xl px-4 py-3 text-sm shadow-xl border-l-4 ${ok ? 'border-l-emerald-400 text-white' : 'border-l-red-400 text-white'}`;
  el.textContent = msg;
  document.getElementById('toastWrap').appendChild(el);
  setTimeout(() => el.remove(), 3500);
}

document.getElementById('createForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const fd = new FormData(e.target);
  const payload = Object.fromEntries(fd.entries());
  payload.action = 'create';
  const res = await fetch('/api/admin_users.php', {
    method: 'POST', headers: {'Content-Type': 'application/json'},
    body: JSON.stringify(payload)
  });
  const data = await res.json();
  toast(data.message, data.ok);
  if (data.ok) { e.target.reset(); loadUsers(); }
});

let searchTimer;
document.getElementById('search').addEventListener('input', () => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(loadUsers, 300);
});

async function loadUsers() {
  const q = document.getElementById('search').value;
  const res = await fetch('/api/admin_users.php?q=' + encodeURIComponent(q));
  const data = await res.json();
  const body = document.getElementById('tableBody');
  body.innerHTML = '';
  if (!data.data.length) {
    body.innerHTML = '<tr><td colspan="5" class="text-center text-slate-500 py-8">Belum ada karyawan.</td></tr>';
    return;
  }
  data.data.forEach(u => {
    const tr = document.createElement('tr');
    tr.className = 'border-b border-white/5';
    const statusBadge = u.status === 'Aktif'
      ? '<span class="text-xs px-2 py-0.5 rounded-full bg-brand/15 text-brand">Aktif</span>'
      : '<span class="text-xs px-2 py-0.5 rounded-full bg-red-500/15 text-red-400">Nonaktif</span>';
    tr.innerHTML = `
      <td class="py-3 pr-3 text-brand font-mono text-xs">${u.member_no}</td>
      <td class="py-3 pr-3 text-white font-medium">${u.name}</td>
      <td class="py-3 pr-3 text-slate-400">${u.username}</td>
      <td class="py-3 pr-3">${statusBadge}</td>
      <td class="py-3 pr-3 text-right space-x-2 whitespace-nowrap">
        <button class="btn-ghost !py-1 !px-2.5 text-xs" data-action="toggle_status" data-id="${u.id}">${u.status === 'Aktif' ? 'Nonaktifkan' : 'Aktifkan'}</button>
        <button class="btn-ghost !py-1 !px-2.5 text-xs" data-action="reset_password" data-id="${u.id}">Reset Password</button>
        <button class="text-slate-500 hover:text-red-400" data-action="delete" data-id="${u.id}" title="Hapus">🗑</button>
      </td>`;
    body.appendChild(tr);
  });

  body.querySelectorAll('[data-action]').forEach(btn => {
    btn.onclick = async () => {
      const action = btn.dataset.action;
      const id = btn.dataset.id;
      let payload = { action, id };

      if (action === 'delete') {
        if (!confirm('Hapus akun karyawan ini beserta seluruh riwayat presensinya?')) return;
      }
      if (action === 'reset_password') {
        const pw = prompt('Masukkan password baru (minimal 4 karakter):');
        if (!pw) return;
        payload.password = pw;
      }

      const res = await fetch('/api/admin_users.php', {
        method: 'POST', headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      toast(data.message, data.ok);
      if (data.ok) loadUsers();
    };
  });
}

loadUsers();
</script>
</body>
</html>
