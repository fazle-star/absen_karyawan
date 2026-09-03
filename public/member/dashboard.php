<?php
require_once __DIR__ . '/../../includes/config.php';
$member = require_role('member');
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Absensi Karyawan</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<link rel="stylesheet" href="/assets/css/style.css">
<link rel="manifest" href="/manifest.json">
<meta name="theme-color" content="#2563eb">
</head>
<script>
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/sw.js');
  }
</script>
<body class="bg-app min-h-screen text-slate-200 pb-24">

<header class="glass sticky top-0 z-30 px-5 py-3 flex items-center justify-between">
  <div class="flex items-center gap-2">
    <div class="w-8 h-8 rounded-lg bg-brand flex items-center justify-center font-black text-black text-sm">AK</div>
    <span class="font-bold text-white">ABSENSI KARYAWAN <span class="text-slate-500 font-normal">· Karyawan</span></span>
  </div>
  <span class="text-xs px-2.5 py-1 rounded-full bg-brand/15 text-brand font-medium">Aktif</span>
</header>

<main class="max-w-md mx-auto p-5">

  <!-- ================= TAB: SCAN ================= -->
  <section id="tab-scan" class="tab-panel space-y-5">
    <!-- Kartu sesi aktif -->
    <div id="activeCard" class="glass rounded-2xl p-6 text-center hidden">
      <p class="text-xs uppercase tracking-wide text-slate-400 mb-1">Sedang Hadir</p>
      <p id="liveTimer" class="text-4xl font-black text-brand font-mono tracking-wider">00:00:00</p>
      <p class="text-xs text-slate-500 mt-1">Check-in pukul <span id="checkinTime">-</span></p>
      <button id="endSessionBtn" class="btn-brand w-full mt-5">Akhiri Sesi</button>
    </div>

    <!-- Kartu scan -->
    <div id="scanCard" class="glass rounded-2xl p-6 hidden">
      <div class="text-center mb-4">
        <div class="w-14 h-14 mx-auto rounded-2xl bg-brand/15 flex items-center justify-center text-2xl">📷</div>
        <h2 class="text-white font-semibold mt-3">Scan QR Presensi</h2>
        <p class="text-xs text-slate-500 mt-1">Arahkan kamera ke QR Code di layar admin</p>
      </div>
      <div id="qr-reader" class="rounded-xl overflow-hidden"></div>
      <button id="startScanBtn" class="btn-brand w-full mt-4">Mulai Scan</button>
      <p id="scanMsg" class="text-center text-xs mt-3 min-h-[1rem]"></p>

      <div class="border-t border-white/10 mt-5 pt-5">
        <p class="text-white text-sm font-medium">Tidak bisa scan?</p>
        <p class="text-xs text-slate-500 mt-1">Masukkan kode presensi dari admin secara manual.</p>
        <form id="manualCheckinForm" class="flex gap-2 mt-3">
          <input id="manualCodeInput" type="text" maxlength="20" autocomplete="one-time-code"
            placeholder="Kode presensi" aria-label="Kode presensi" required
            class="flex-1 min-w-0 rounded-xl bg-white/5 border border-white/10 px-4 py-3 text-white uppercase tracking-widest placeholder:normal-case placeholder:tracking-normal focus:outline-none focus:border-brand">
          <button id="manualCheckinBtn" type="submit" class="btn-brand px-4">Absen</button>
        </form>
        <p id="manualMsg" class="text-center text-xs mt-3 min-h-[1rem]"></p>
      </div>
    </div>

    <div class="glass rounded-2xl p-4 flex items-center justify-between">
      <div>
        <p class="text-sm text-white font-medium">Halo, <?= htmlspecialchars($member['name']) ?> 👋</p>
        <p class="text-xs text-slate-500">No. Member <?= htmlspecialchars($member['member_no']) ?></p>
      </div>
    </div>
  </section>

  <!-- ================= TAB: PRESENSI (riwayat) ================= -->
  <section id="tab-presensi" class="tab-panel hidden">
    <h1 class="text-white font-semibold text-lg mb-1">Riwayat Presensi</h1>
    <p id="riwayatCount" class="text-slate-500 text-sm mb-4">0 kali hadir</p>
    <div id="riwayatList" class="space-y-3"></div>
  </section>

  <!-- ================= TAB: PROFIL ================= -->
  <section id="tab-profil" class="tab-panel hidden">
    <h1 class="text-white font-semibold text-lg mb-4">Profil Saya</h1>
    <div class="flex flex-col items-center mb-5">
      <div class="w-20 h-20 rounded-full bg-gradient-to-br from-brand to-brand-dark flex items-center justify-center text-3xl">🙂</div>
      <p class="text-white font-semibold mt-3"><?= htmlspecialchars($member['name']) ?></p>
      <p class="text-slate-500 text-sm">Karyawan</p>
      <span class="text-xs px-2.5 py-1 rounded-full bg-brand/15 text-brand font-medium mt-2"><?= htmlspecialchars($member['status']) ?></span>
    </div>
    <div class="glass rounded-2xl p-5 space-y-4">
      <p class="text-brand text-sm font-medium">📇 Data Pribadi</p>
      <div class="grid grid-cols-2 gap-4 text-sm">
        <div><p class="text-slate-500 text-xs uppercase">Nama Lengkap</p><p class="text-white mt-0.5"><?= htmlspecialchars($member['name']) ?></p></div>
        <div><p class="text-slate-500 text-xs uppercase">No. Member</p><p class="text-brand mt-0.5"><?= htmlspecialchars($member['member_no']) ?></p></div>
        <div><p class="text-slate-500 text-xs uppercase">Jenis Kelamin</p><p class="text-white mt-0.5"><?= htmlspecialchars($member['gender'] ?: '-') ?></p></div>
        <div><p class="text-slate-500 text-xs uppercase">Username</p><p class="text-white mt-0.5"><?= htmlspecialchars($member['username']) ?></p></div>
      </div>
    </div>
    <a href="/logout.php" class="btn-ghost w-full text-center block mt-5">Keluar</a>
  </section>
</main>

<!-- Bottom nav -->
<nav class="fixed bottom-0 inset-x-0 glass border-t border-white/10 px-6 py-2 flex items-center justify-around z-40">
  <button data-tab="scan" class="tab-btn active flex flex-col items-center gap-1 text-slate-400 text-xs py-1 px-3">
    <span class="tab-icon-wrap w-9 h-9 rounded-xl flex items-center justify-center">📷</span>
    Scan
  </button>
  <button data-tab="presensi" class="tab-btn flex flex-col items-center gap-1 text-slate-400 text-xs py-1 px-3">
    <span class="tab-icon-wrap w-9 h-9 rounded-xl flex items-center justify-center">📋</span>
    Presensi
  </button>
  <button data-tab="profil" class="tab-btn flex flex-col items-center gap-1 text-slate-400 text-xs py-1 px-3">
    <span class="tab-icon-wrap w-9 h-9 rounded-xl flex items-center justify-center">👤</span>
    Profil
  </button>
</nav>

<div id="toastWrap" class="fixed bottom-24 inset-x-0 flex justify-center z-50 px-5"></div>

<!-- Modal notifikasi durasi -->
<div id="durationModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 hidden items-center justify-center px-6">
  <div class="glass rounded-2xl p-6 text-center max-w-xs w-full toast-enter">
    <div class="w-14 h-14 mx-auto rounded-full bg-brand/15 flex items-center justify-center text-2xl">✅</div>
    <p class="text-white font-semibold mt-4">Sesi Selesai</p>
    <p class="text-slate-400 text-sm mt-2">Kamu telah absen selama</p>
    <p id="durationText" class="text-brand text-2xl font-black mt-1">-</p>
    <button id="closeDurationModal" class="btn-brand w-full mt-5">Oke, Mengerti</button>
  </div>
</div>

<script>
// ---------- Tab navigation ----------
document.querySelectorAll('.tab-btn').forEach(btn => {
  btn.onclick = () => {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.add('hidden'));
    document.getElementById('tab-' + btn.dataset.tab).classList.remove('hidden');
    if (btn.dataset.tab === 'presensi') loadRiwayat();
  };
});

function toast(msg, ok = true) {
  const wrap = document.getElementById('toastWrap');
  const el = document.createElement('div');
  el.className = `toast-enter glass rounded-xl px-4 py-3 text-sm shadow-xl border-l-4 ${ok ? 'border-l-emerald-400 text-white' : 'border-l-red-400 text-white'} max-w-xs w-full text-center`;
  el.textContent = msg;
  wrap.appendChild(el);
  setTimeout(() => el.remove(), 3200);
}

// ---------- Sesi aktif ----------
let timerInterval = null;
let html5QrCode = null;
let scanning = false;

async function refreshStatus() {
  const res = await fetch('/api/member_status.php');
  const data = await res.json();
  if (data.active) {
    showActiveCard(data.session.check_in);
  } else {
    showScanCard();
  }
}

function showActiveCard(checkInRaw) {
  document.getElementById('activeCard').classList.remove('hidden');
  document.getElementById('scanCard').classList.add('hidden');
  const start = new Date(checkInRaw.replace(' ', 'T') + '+07:00');
  document.getElementById('checkinTime').textContent = start.toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit'});
  clearInterval(timerInterval);
  const updateTimer = () => {
    const diff = Math.max(0, Math.floor((Date.now() - start.getTime()) / 1000));
    const h = String(Math.floor(diff / 3600)).padStart(2, '0');
    const m = String(Math.floor((diff % 3600) / 60)).padStart(2, '0');
    const s = String(diff % 60).padStart(2, '0');
    document.getElementById('liveTimer').textContent = `${h}:${m}:${s}`;
  };
  updateTimer();
  timerInterval = setInterval(updateTimer, 1000);
}

function showScanCard() {
  document.getElementById('activeCard').classList.add('hidden');
  document.getElementById('scanCard').classList.remove('hidden');
  clearInterval(timerInterval);
}

document.getElementById('endSessionBtn').onclick = async (e) => {
  e.target.disabled = true;
  e.target.textContent = 'Memproses...';
  const res = await fetch('/api/checkout.php', { method: 'POST' });
  const data = await res.json();
  e.target.disabled = false;
  e.target.textContent = 'Akhiri Sesi';
  if (data.ok) {
    document.getElementById('durationText').textContent = data.duration_text;
    document.getElementById('durationModal').classList.remove('hidden');
    document.getElementById('durationModal').classList.add('flex');
    showScanCard();
  } else {
    toast(data.message, false);
  }
};

document.getElementById('closeDurationModal').onclick = () => {
  document.getElementById('durationModal').classList.add('hidden');
  document.getElementById('durationModal').classList.remove('flex');
};

document.getElementById('manualCheckinForm').onsubmit = async (e) => {
  e.preventDefault();
  const input = document.getElementById('manualCodeInput');
  const btn = document.getElementById('manualCheckinBtn');
  const msg = document.getElementById('manualMsg');
  const code = input.value.trim();
  if (!code) return;

  btn.disabled = true;
  btn.textContent = 'Memproses...';
  msg.textContent = 'Memvalidasi kode...';
  msg.className = 'text-center text-xs mt-3 text-slate-400';
  await submitCheckin(code, msg);
  btn.disabled = false;
  btn.textContent = 'Absen';
};

// ---------- QR Scanner ----------
document.getElementById('startScanBtn').onclick = async () => {
  const btn = document.getElementById('startScanBtn');
  const msg = document.getElementById('scanMsg');

  if (scanning) {
    await html5QrCode.stop();
    scanning = false;
    btn.textContent = 'Mulai Scan';
    return;
  }

  html5QrCode = new Html5Qrcode('qr-reader');
  btn.textContent = 'Menyalakan kamera...';
  try {
    await html5QrCode.start(
      { facingMode: 'environment' },
      { fps: 10, qrbox: 220 },
      async (decodedText) => {
        if (!scanning) return; // hindari double trigger
        scanning = false;
        await html5QrCode.stop();
        btn.textContent = 'Mulai Scan';
        msg.textContent = 'Memvalidasi kode...';
        msg.className = 'text-center text-xs mt-3 text-slate-400';
        submitCheckin(decodedText, msg);
      },
      () => {}
    );
    scanning = true;
    btn.textContent = 'Hentikan Scan';
  } catch (err) {
    msg.textContent = 'Tidak bisa mengakses kamera. Izinkan akses kamera pada browser.';
    msg.className = 'text-center text-xs mt-3 text-red-400';
    btn.textContent = 'Mulai Scan';
  }
};

async function submitCheckin(code, msg = document.getElementById('scanMsg')) {
  const res = await fetch('/api/checkin.php', {
    method: 'POST', headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({ code })
  });
  const data = await res.json();
  if (data.ok) {
    toast(data.message, true);
    msg.textContent = '';
    document.getElementById('manualCodeInput').value = '';
    refreshStatus();
  } else {
    msg.textContent = data.message;
    msg.className = 'text-center text-xs mt-3 text-red-400';
    if (data.already_active) refreshStatus();
  }
}

// ---------- Riwayat ----------
async function loadRiwayat() {
  const res = await fetch('/api/member_history.php');
  const data = await res.json();
  document.getElementById('riwayatCount').textContent = data.total_hadir + ' kali hadir';
  const list = document.getElementById('riwayatList');
  list.innerHTML = '';
  if (!data.history.length) {
    list.innerHTML = '<p class="text-center text-slate-500 text-sm py-8">Belum ada riwayat presensi.</p>';
    return;
  }
  data.history.forEach(h => {
    const el = document.createElement('div');
    el.className = 'glass rounded-2xl p-4';
    const badge = h.status === 'selesai'
      ? '<span class="text-emerald-400 text-xs font-semibold">✓ Selesai</span>'
      : '<span class="text-brand text-xs font-semibold">● Sedang Hadir</span>';
    el.innerHTML = `
      <div class="flex items-center justify-between">
        <p class="text-white font-semibold text-sm">${h.date}</p>
        ${badge}
      </div>
      <p class="text-brand text-xs mt-1">● ${h.branch}</p>
      <div class="flex items-center gap-4 mt-2 text-xs text-slate-400">
        <span>🕐 Masuk: ${h.check_in}</span>
        ${h.check_out ? `<span>→ Keluar: ${h.check_out}</span>` : ''}
      </div>
      ${h.duration_text ? `<p class="text-xs text-slate-500 mt-1">⏳ Durasi: ${h.duration_text}${h.auto_checkout ? ' (Auto Check-out)' : ''}</p>` : ''}
    `;
    list.appendChild(el);
  });
}

refreshStatus();
setInterval(refreshStatus, 15000);
</script>
</body>
</html>
