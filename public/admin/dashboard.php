<?php
require_once __DIR__ . '/../../includes/config.php';
$admin = require_role('admin');
$codeRow = gen_daily_code(db());
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Admin — Absensi Karyawan</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="bg-app min-h-screen text-slate-200">

<header class="glass sticky top-0 z-30 px-5 py-3 flex items-center justify-between">
  <div class="flex items-center gap-2">
    <div class="w-8 h-8 rounded-lg bg-brand flex items-center justify-center font-black text-black text-sm">AK</div>
    <span class="font-bold text-white">ABSENSI KARYAWAN <span class="text-slate-500 font-normal">· Admin</span></span>
  </div>
  <div class="flex items-center gap-3">
    <a href="/admin/users.php" class="btn-ghost text-sm">👥 Kelola Karyawan</a>
    <span class="text-sm text-slate-400 hidden sm:inline">Halo, <b class="text-white"><?= htmlspecialchars($admin['name']) ?></b></span>
    <a href="/logout.php" class="btn-ghost text-sm">Keluar</a>
  </div>
</header>

<main class="max-w-6xl mx-auto p-5 space-y-5">

  <div class="grid md:grid-cols-2 gap-5">
    <!-- QR Presensi -->
    <div class="glass rounded-2xl p-6">
      <div class="flex items-center justify-between mb-4">
        <div>
          <p class="text-sm text-slate-400">Presensi Aktif</p>
          <div class="flex items-center gap-2 mt-1">
            <span id="statusDot" class="pulse-dot"></span>
            <span id="statusLabel" class="text-sm font-medium text-brand">Presensi Aktif</span>
          </div>
        </div>
        <button id="toggleBtn" class="btn-danger">Nonaktifkan Presensi</button>
      </div>

      <div class="flex flex-col items-center py-4">
        <div id="qrcode" class="bg-white p-4 rounded-2xl"></div>
        <p class="text-xs text-slate-500 mt-4">Kode Presensi</p>
        <p id="codeText" class="text-3xl font-black tracking-[0.3em] text-brand mt-1"><?= htmlspecialchars($codeRow['code']) ?></p>
        <p class="text-xs text-slate-500 mt-1">Kode berlaku <span class="text-brand font-medium">Hari Ini</span> · Cabang <?= htmlspecialchars(BRANCH_NAME) ?></p>
        <button id="regenBtn" class="btn-ghost text-xs mt-4">↻ Buat Kode Baru</button>
      </div>
    </div>

    <!-- Presensi Manual -->
    <div class="glass rounded-2xl p-6">
      <div class="flex items-center gap-2 mb-4">
        <div class="w-8 h-8 rounded-lg bg-brand/15 flex items-center justify-center text-brand">✎</div>
        <h2 class="font-semibold text-white">Presensi Manual</h2>
      </div>
      <label class="text-xs uppercase tracking-wide text-slate-400">Cari Karyawan</label>
      <input id="manualSearch" class="w-full input-field mt-1" placeholder="Ketik nama karyawan...">
      <div id="manualResults" class="mt-3 space-y-2 max-h-64 overflow-y-auto"></div>
    </div>
  </div>

  <!-- Tabel Presensi Hari Ini -->
  <div class="glass rounded-2xl p-6">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
      <div>
        <h2 class="font-semibold text-white">Presensi Hari Ini</h2>
        <p class="text-xs text-slate-500"><?= htmlspecialchars(BRANCH_NAME) ?></p>
      </div>
      <div class="flex items-center gap-4">
        <div class="text-sm text-slate-400">Total: <span id="totalCount" class="text-white font-semibold">0</span></div>
        <div class="text-sm text-slate-400">Sedang Hadir: <span id="activeCount" class="text-brand font-semibold">0</span></div>
        <input id="tableSearch" class="input-field text-sm py-1.5" placeholder="Cari karyawan...">
      </div>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="text-left text-slate-500 border-b border-white/10">
            <th class="py-2 pr-3 font-medium">ID</th>
            <th class="py-2 pr-3 font-medium">Karyawan</th>
            <th class="py-2 pr-3 font-medium">Status / Waktu</th>
            <th class="py-2 pr-3 font-medium text-right">Aksi</th>
          </tr>
        </thead>
        <tbody id="tableBody"></tbody>
      </table>
      <p id="emptyState" class="text-center text-slate-500 py-8 hidden">Belum ada presensi hari ini.</p>
    </div>
  </div>
</main>

<div id="toastWrap" class="fixed bottom-5 right-5 z-50 space-y-2"></div>

<script>
const qr = new QRCode(document.getElementById('qrcode'), {
  text: <?= json_encode($codeRow['code']) ?>,
  width: 200, height: 200,
  colorDark: '#04140f', colorLight: '#ffffff'
});
let currentActive = <?= $codeRow['active'] ? 'true' : 'false' ?>;
renderStatus();

function toast(msg, ok = true) {
  const el = document.createElement('div');
  el.className = `toast-enter glass rounded-xl px-4 py-3 text-sm shadow-xl border-l-4 ${ok ? 'border-l-emerald-400 text-white' : 'border-l-red-400 text-white'}`;
  el.textContent = msg;
  document.getElementById('toastWrap').appendChild(el);
  setTimeout(() => el.remove(), 3500);
}

function renderStatus() {
  const dot = document.getElementById('statusDot');
  const label = document.getElementById('statusLabel');
  const btn = document.getElementById('toggleBtn');
  if (currentActive) {
    dot.style.background = 'var(--brand)';
    label.textContent = 'Presensi Aktif';
    label.className = 'text-sm font-medium text-brand';
    btn.textContent = 'Nonaktifkan Presensi';
  } else {
    dot.style.background = '#f87171';
    label.textContent = 'Presensi Nonaktif';
    label.className = 'text-sm font-medium text-red-400';
    btn.textContent = 'Aktifkan Presensi';
  }
}

document.getElementById('toggleBtn').onclick = async () => {
  const res = await fetch('/api/admin_code.php', {
    method: 'POST', headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({action: 'toggle'})
  });
  const data = await res.json();
  currentActive = data.active;
  renderStatus();
  toast(currentActive ? 'Presensi diaktifkan kembali.' : 'Presensi dinonaktifkan.', currentActive);
};

document.getElementById('regenBtn').onclick = async () => {
  const res = await fetch('/api/admin_code.php', {
    method: 'POST', headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({action: 'regenerate'})
  });
  const data = await res.json();
  document.getElementById('codeText').textContent = data.code;
  qr.clear(); qr.makeCode(data.code);
  currentActive = data.active;
  renderStatus();
  toast('Kode presensi baru dibuat.');
};

// ==== Presensi manual ====
let searchTimer;
document.getElementById('manualSearch').addEventListener('input', (e) => {
  clearTimeout(searchTimer);
  const q = e.target.value;
  searchTimer = setTimeout(() => loadManualResults(q), 300);
});
async function loadManualResults(q) {
  const res = await fetch('/api/admin_manual.php?q=' + encodeURIComponent(q));
  const data = await res.json();
  const wrap = document.getElementById('manualResults');
  wrap.innerHTML = '';
  if (!data.data.length) {
    wrap.innerHTML = '<p class="text-xs text-slate-500 py-2">Tidak ada karyawan ditemukan.</p>';
    return;
  }
  data.data.forEach(m => {
    const row = document.createElement('div');
    row.className = 'flex items-center justify-between bg-white/5 rounded-xl px-3 py-2';
    row.innerHTML = `
      <div>
        <p class="text-sm text-white font-medium">${m.name}</p>
        <p class="text-xs text-slate-500">ID ${m.member_no}</p>
      </div>
      <button class="btn-brand !py-1.5 !px-3 text-xs" data-id="${m.id}">Check In</button>`;
    row.querySelector('button').onclick = async (ev) => {
      ev.target.disabled = true;
      const r = await fetch('/api/admin_manual.php', {
        method: 'POST', headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({user_id: m.id})
      });
      const d = await r.json();
      toast(d.message, d.ok);
      ev.target.disabled = false;
      if (d.ok) loadTable();
    };
    wrap.appendChild(row);
  });
}

// ==== Tabel presensi hari ini ====
let tableSearchTimer;
document.getElementById('tableSearch').addEventListener('input', (e) => {
  clearTimeout(tableSearchTimer);
  tableSearchTimer = setTimeout(loadTable, 300);
});

async function loadTable() {
  const q = document.getElementById('tableSearch').value;
  const res = await fetch('/api/admin_today.php?q=' + encodeURIComponent(q));
  const data = await res.json();
  document.getElementById('totalCount').textContent = data.total;
  document.getElementById('activeCount').textContent = data.sedang_latihan;

  const body = document.getElementById('tableBody');
  const empty = document.getElementById('emptyState');
  body.innerHTML = '';
  empty.classList.toggle('hidden', data.data.length > 0);

  data.data.forEach(r => {
    const tr = document.createElement('tr');
    tr.className = 'border-b border-white/5';
    const statusHtml = r.status === 'sedang_latihan'
      ? `<span class="inline-flex items-center gap-1.5 text-brand text-xs font-medium"><span class="pulse-dot"></span>SEDANG HADIR</span><br><span class="font-mono text-white text-sm live-timer" data-start="${r.check_in_raw}">--:--:--</span>`
      : `<span class="text-slate-400 text-xs font-medium">SELESAI</span><br><span class="text-sm text-slate-300">${r.check_in} — ${r.check_out}${r.duration_text ? ' · ' + r.duration_text : ''}</span>`;

    const actionHtml = r.status === 'sedang_latihan'
      ? `<button class="btn-danger" data-action="checkout" data-id="${r.session_id}">Check Out</button>`
      : `<span class="text-xs text-slate-600">—</span>`;

    tr.innerHTML = `
      <td class="py-3 pr-3 text-brand font-mono text-xs">${r.member_no}</td>
      <td class="py-3 pr-3">
        <p class="text-white font-medium">${r.name}</p>
        <span class="inline-block text-[10px] px-2 py-0.5 rounded-full bg-brand/15 text-brand mt-0.5">${r.member_status}</span>
      </td>
      <td class="py-3 pr-3">${statusHtml}</td>
      <td class="py-3 pr-3 text-right space-x-2 whitespace-nowrap">
        ${actionHtml}
        <button class="text-slate-500 hover:text-red-400" data-action="delete" data-id="${r.session_id}" title="Hapus">🗑</button>
      </td>`;
    body.appendChild(tr);
  });

  body.querySelectorAll('[data-action]').forEach(btn => {
    btn.onclick = async () => {
      const action = btn.dataset.action;
      if (action === 'delete' && !confirm('Hapus data presensi ini?')) return;
      const r = await fetch('/api/admin_session_action.php', {
        method: 'POST', headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({session_id: btn.dataset.id, action})
      });
      const d = await r.json();
      toast(d.message, d.ok);
      if (d.ok) loadTable();
    };
  });

  tickTimers();
}

function tickTimers() {
  document.querySelectorAll('.live-timer').forEach(el => {
    const start = new Date(el.dataset.start.replace(' ', 'T') + '+07:00');
    const diff = Math.max(0, Math.floor((Date.now() - start.getTime()) / 1000));
    const h = String(Math.floor(diff / 3600)).padStart(2, '0');
    const m = String(Math.floor((diff % 3600) / 60)).padStart(2, '0');
    const s = String(diff % 60).padStart(2, '0');
    el.textContent = `${h}:${m}:${s}`;
  });
}

loadTable();
loadManualResults('');
setInterval(loadTable, 5000);
setInterval(tickTimers, 1000);
</script>
</body>
</html>
