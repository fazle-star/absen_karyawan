import React, { useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { QRCodeSVG } from 'qrcode.react';
import { Html5Qrcode } from 'html5-qrcode';
import './style.css';

const api = async (path, options = {}) => {
  const response = await fetch(`/api/${path}`, {
    headers: { 'Content-Type': 'application/json', ...(options.headers || {}) },
    ...options,
  });
  const data = await response.json();
  if (!response.ok) throw new Error(data.message || 'Terjadi kesalahan.');
  return data;
};

function Login({ onLogin }) {
  const [form, setForm] = useState({ username: '', password: '' });
  const [error, setError] = useState('');
  const submit = async (event) => {
    event.preventDefault(); setError('');
    try { const data = await api('auth/login', { method: 'POST', body: JSON.stringify(form) }); onLogin(data.user); }
    catch (err) { setError(err.message); }
  };
  return <main className="auth-shell"><section className="auth-panel"><span className="mark">AK</span><p className="eyebrow">ABSENSI KARYAWAN</p><h1>Hadir dengan<br /><em>lebih sederhana.</em></h1><p className="muted">Masuk untuk mencatat kehadiran hari ini.</p><form onSubmit={submit} className="stack"><label>Username<input required value={form.username} onChange={e => setForm({ ...form, username: e.target.value })} /></label><label>Password<input required type="password" value={form.password} onChange={e => setForm({ ...form, password: e.target.value })} /></label>{error && <p className="error">{error}</p>}<button className="primary">Masuk ke dashboard</button></form></section><aside className="auth-art"><span>GOOD MORNING</span><strong>SHOW UP.<br />DO GOOD<br /><i>WORK.</i></strong><small>Presensi digital untuk tim yang bergerak.</small></aside></main>;
}

function Header({ user, onLogout }) { return <header className="topbar"><div><span className="mark small">AK</span><b>ABSENSI <span>KARYAWAN</span></b></div><div className="user-menu"><span>{user.name}</span><button className="ghost" onClick={onLogout}>Keluar</button></div></header>; }

function MemberDashboard({ user }) {
  const [status, setStatus] = useState(null); const [code, setCode] = useState(''); const [history, setHistory] = useState([]); const [message, setMessage] = useState(''); const [tab, setTab] = useState('home'); const [scanning, setScanning] = useState(false);
  const scanner = React.useRef(null);
  const refresh = async () => { try { const data = await api('member/status'); setStatus(data); } catch (err) { setMessage(err.message); } };
  useEffect(() => { refresh(); }, []);
  const checkin = async (event, scannedCode = code) => { event.preventDefault(); setMessage(''); try { const data = await api('member/checkin', { method: 'POST', body: JSON.stringify({ code: scannedCode }) }); setMessage(data.message); setCode(''); refresh(); } catch (err) { setMessage(err.message); } };
  const checkout = async () => { try { const data = await api('member/checkout', { method: 'POST' }); setMessage(`${data.message} Durasi ${data.duration_text}.`); refresh(); } catch (err) { setMessage(err.message); } };
  const loadHistory = async () => { setTab('history'); try { const data = await api('member/history'); setHistory(data.history); } catch (err) { setMessage(err.message); } };
  const startScanner = async () => { if (scanning) { await scanner.current?.stop(); setScanning(false); return; } scanner.current = new Html5Qrcode('qr-reader'); try { await scanner.current.start({ facingMode: 'environment' }, { fps: 10, qrbox: 220 }, async value => { await scanner.current.stop(); setScanning(false); await checkin({ preventDefault() {} }, value); }); setScanning(true); } catch { setMessage('Kamera tidak dapat diakses. Izinkan akses kamera pada browser.'); } };
  useEffect(() => () => { scanner.current?.stop().catch(() => {}); }, []);
  return <><Header user={user} onLogout={() => api('auth/logout', { method: 'POST' }).then(() => location.reload())} /><main className="app-shell"><div className="welcome"><p className="eyebrow">{new Date().toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long' })}</p><h1>Halo, {user.name.split(' ')[0]}.</h1><p className="muted">Siap mencatat langkah pertama hari ini?</p></div>{tab === 'history' ? <section><div className="section-head"><h2>Riwayat presensi</h2><button className="ghost" onClick={() => setTab('home')}>Kembali</button></div><div className="list">{history.length ? history.map(item => <article className="history-row" key={item.id}><div><b>{item.date}</b><span>{item.branch}</span></div><div><b>{item.check_in} - {item.check_out || 'aktif'}</b><span>{item.duration_text || 'Sedang hadir'}</span></div></article>) : <p className="empty">Belum ada riwayat presensi.</p>}</div></section> : status?.active ? <section className="active-card"><p className="eyebrow">SESI AKTIF</p><div className="timer">{status.session.check_in.slice(11, 16)}</div><p className="muted">Check-in berhasil tercatat.</p><button className="dark-button" onClick={checkout}>Akhiri sesi</button></section> : <section className="checkin-grid"><div className="scanner-card"><div id="qr-reader" className="scanner-placeholder"><span>QR</span></div><h2>Scan QR presensi</h2><p className="muted">Gunakan kamera QR dari perangkat kamu.</p><button className="primary" onClick={startScanner}>{scanning ? 'Hentikan scan' : 'Mulai scan'}</button></div><div className="manual-card"><p className="eyebrow">ALTERNATIF</p><h2>Masukkan kode</h2><p className="muted">Tidak bisa scan? Ketik kode presensi dari admin.</p><form onSubmit={checkin} className="inline-form"><input value={code} onChange={e => setCode(e.target.value.toUpperCase())} maxLength="20" placeholder="Contoh: A1B2C3" required /><button className="dark-button">Absen</button></form></div></section>}{message && <p className="notice">{message}</p>}<nav className="bottom-tabs"><button className={tab === 'home' ? 'selected' : ''} onClick={() => setTab('home')}>Ringkasan</button><button className={tab === 'history' ? 'selected' : ''} onClick={loadHistory}>Riwayat</button><span>{user.member_no}</span></nav></main></>;
}

function AdminDashboard({ user }) {
  const [code, setCode] = useState(null); const [today, setToday] = useState([]); const [query, setQuery] = useState(''); const [message, setMessage] = useState('');
  const load = async () => { try { const [codeData, todayData] = await Promise.all([api('admin/code'), api(`admin/today?q=${encodeURIComponent(query)}`)]); setCode(codeData); setToday(todayData.data); } catch (err) { setMessage(err.message); } };
  useEffect(() => { load(); }, [query]);
  const regenerate = async () => { const data = await api('admin/code', { method: 'POST', body: JSON.stringify({ action: 'regenerate' }) }); setCode(data); setMessage('Kode presensi baru dibuat.'); };
  const toggle = async () => { const data = await api('admin/code', { method: 'POST', body: JSON.stringify({ action: 'toggle' }) }); setCode(data); };
  return <><Header user={user} onLogout={() => api('auth/logout', { method: 'POST' }).then(() => location.reload())} /><main className="app-shell"><div className="welcome"><p className="eyebrow">PANEL ADMIN</p><h1>Ruang kendali.</h1><p className="muted">Pantau kehadiran tim secara langsung.</p></div><section className="admin-grid"><div className="code-card"><div><p className="eyebrow">KODE HARI INI</p><h2>{code?.active ? 'Presensi terbuka' : 'Presensi ditutup'}</h2><p className="code-text">{code?.code || '...'}</p><div className="code-actions"><button className="dark-button" onClick={regenerate}>Buat kode baru</button><button className="ghost" onClick={toggle}>{code?.active ? 'Tutup' : 'Buka'} presensi</button></div></div>{code?.code && <QRCodeSVG value={code.code} size={142} bgColor="#fff" fgColor="#101b2d" />}</div><div className="attendance-card"><div className="section-head"><div><p className="eyebrow">HARI INI</p><h2>{today.length} kehadiran</h2></div><input className="search" value={query} onChange={e => setQuery(e.target.value)} placeholder="Cari nama..." /></div><div className="list">{today.map(item => <article className="history-row" key={item.session_id}><div><b>{item.name}</b><span>{item.member_no}</span></div><div><b>{item.check_in}</b><span className={item.status === 'sedang_latihan' ? 'live' : ''}>{item.status === 'sedang_latihan' ? 'Sedang hadir' : item.check_out || 'Selesai'}</span></div></article>)}{!today.length && <p className="empty">Belum ada presensi hari ini.</p>}</div></div></section>{message && <p className="notice">{message}</p>}</main></>;
}

function App() { const [user, setUser] = useState(undefined); useEffect(() => { api('auth/me').then(data => setUser(data.user)).catch(() => setUser(null)); }, []); if (user === undefined) return <div className="loading">Memuat...</div>; if (!user) return <Login onLogin={setUser} />; return user.role === 'admin' ? <AdminDashboard user={user} /> : <MemberDashboard user={user} />; }

createRoot(document.getElementById('root')).render(<App />);
