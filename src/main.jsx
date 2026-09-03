import React, { useEffect, useState } from "react";
import { createRoot } from "react-dom/client";
import { QRCodeSVG } from "qrcode.react";
import { Html5Qrcode } from "html5-qrcode";
import "./style.css";

const api = async (path, options = {}) => {
  const response = await fetch(`/api/${path}`, {
    cache: options.method ? undefined : "no-store",
    headers: {
      "Content-Type": "application/json",
      ...(options.method ? {} : { "Cache-Control": "no-cache" }),
      ...(options.headers || {}),
    },
    ...options,
  });
  const data = await response.json();
  if (!response.ok) throw new Error(data.message || "Terjadi kesalahan.");
  return data;
};

function Login({ onLogin }) {
  const [form, setForm] = useState({ username: "", password: "" });
  const [error, setError] = useState("");
  const submit = async (event) => {
    event.preventDefault();
    setError("");
    try {
      const data = await api("auth/login", {
        method: "POST",
        body: JSON.stringify(form),
      });
      onLogin(data.user);
    } catch (err) {
      setError(err.message);
    }
  };
  return (
    <main className="auth-shell">
      <section className="auth-panel">
        <span className="mark">AK</span>
        <p className="eyebrow">ABSENSI KARYAWAN</p>
        <h1>
          Hadir dengan
          <br />
          <em>lebih sederhana.</em>
        </h1>
        <p className="muted">Masuk untuk mencatat kehadiran hari ini.</p>
        <form onSubmit={submit} className="stack">
          <label>
            Username
            <input
              required
              value={form.username}
              onChange={(e) => setForm({ ...form, username: e.target.value })}
            />
          </label>
          <label>
            Password
            <input
              required
              type="password"
              value={form.password}
              onChange={(e) => setForm({ ...form, password: e.target.value })}
            />
          </label>
          {error && <p className="error">{error}</p>}
          <button className="primary">Masuk ke dashboard</button>
        </form>
      </section>
      <aside className="auth-art">
        <span>GOOD MORNING</span>
        <strong>
          SHOW UP.
          <br />
          DO GOOD
          <br />
          <i>WORK.</i>
        </strong>
        <small>Presensi digital untuk tim yang bergerak.</small>
      </aside>
    </main>
  );
}

function Header({ user, onLogout, menuOpen, onToggleMenu }) {
  return (
    <header className="topbar">
      <div>
        <span className="mark small">AK</span>
        <b>
          ABSENSI <span>KARYAWAN</span>
        </b>
      </div>
      <div className="user-menu">
        <span>{user.name}</span>
        {user.role === "admin" ? <button className="ghost" onClick={onLogout}>Keluar</button> : <button className="hamburger header-hamburger" aria-label="Buka menu" onClick={onToggleMenu}>{menuOpen ? "×" : "☰"}</button>}
      </div>
    </header>
  );
}

function MemberDashboard({ user }) {
  const [status, setStatus] = useState(null);
  const [code, setCode] = useState("");
  const [history, setHistory] = useState([]);
  const [message, setMessage] = useState("");
  const [tab, setTab] = useState("home");
  const [menuOpen, setMenuOpen] = useState(false);
  const [scanning, setScanning] = useState(false);
  const [elapsed, setElapsed] = useState(0);
  const scanner = React.useRef(null);
  const refresh = async () => {
    try {
      const data = await api("member/status");
      setStatus(data);
    } catch (err) {
      setMessage(err.message);
    }
  };
  useEffect(() => {
    refresh();
  }, []);
  useEffect(() => {
    if (!status?.active) {
      setElapsed(0);
      return undefined;
    }
    const update = () =>
      setElapsed(
        Math.max(
          0,
          Math.floor(
            (Date.now() - new Date(status.session.check_in).getTime()) / 1000,
          ),
        ),
      );
    update();
    const timer = setInterval(update, 1000);
    return () => clearInterval(timer);
  }, [status]);
  const checkin = async (event, scannedCode = code) => {
    event.preventDefault();
    setMessage("");
    try {
      const data = await api("member/checkin", {
        method: "POST",
        body: JSON.stringify({ code: scannedCode }),
      });
      setMessage(data.message);
      setCode("");
      refresh();
    } catch (err) {
      setMessage(err.message);
    }
  };
  const checkout = async () => {
    try {
      const data = await api("member/checkout", { method: "POST" });
      setMessage(`${data.message} Durasi ${data.duration_text}.`);
      refresh();
    } catch (err) {
      setMessage(err.message);
    }
  };
  const loadHistory = async () => {
    setTab("history");
    setMenuOpen(false);
    try {
      const data = await api("member/history");
      setHistory(data.history);
    } catch (err) {
      setMessage(err.message);
    }
  };
  const selectTab = (nextTab) => {
    setTab(nextTab);
    setMenuOpen(false);
  };
  const startScanner = async () => {
    if (scanning) {
      await scanner.current?.stop();
      setScanning(false);
      return;
    }
    scanner.current = new Html5Qrcode("qr-reader");
    try {
      await scanner.current.start(
        { facingMode: "environment" },
        {
          fps: 10,
          qrbox: (width, height) => {
            const size = Math.floor(Math.min(width, height) * 0.72);
            return { width: size, height: size };
          },
        },
        async (value) => {
          await scanner.current.stop();
          setScanning(false);
          await checkin({ preventDefault() {} }, value);
        },
      );
      setScanning(true);
    } catch {
      setMessage(
        "Kamera tidak dapat diakses. Izinkan akses kamera pada browser.",
      );
    }
  };
  useEffect(
    () => () => {
      scanner.current?.stop().catch(() => {});
    },
    [],
  );
  const formatElapsed = (total) =>
    `${String(Math.floor(total / 3600)).padStart(2, "0")}:${String(Math.floor((total % 3600) / 60)).padStart(2, "0")}:${String(total % 60).padStart(2, "0")}`;
  return (
    <>
      <Header
        user={user}
        menuOpen={menuOpen}
        onToggleMenu={() => setMenuOpen(!menuOpen)}
        onLogout={() =>
          api("auth/logout", { method: "POST" }).then(() => location.reload())
        }
      />
      <main className="app-shell">
        <div className="welcome">
          <p className="eyebrow">
            {new Date().toLocaleDateString("id-ID", {
              weekday: "long",
              day: "numeric",
              month: "long",
            })}
          </p>
          <h1>Halo, {user.name.split(" ")[0]}.</h1>
          <p className="muted">Siap mencatat langkah pertama hari ini?</p>
        </div>
        {tab === "profile" ? (
          <section className="profile-card">
            <p className="eyebrow">PROFIL KARYAWAN</p>
            <div className="profile-avatar">{user.name.slice(0, 1).toUpperCase()}</div>
            <h2>{user.name}</h2>
            <p className="muted">Data akun karyawan</p>
            <dl className="profile-details">
              <div><dt>No. member</dt><dd>{user.member_no}</dd></div>
              <div><dt>Username</dt><dd>{user.username || "-"}</dd></div>
              <div><dt>Status</dt><dd>Aktif</dd></div>
            </dl>
          </section>
        ) : tab === "history" ? (
          <section>
            <div className="section-head">
              <h2>Riwayat presensi</h2>
              <button className="ghost" onClick={() => selectTab("home")}>
                Kembali
              </button>
            </div>
            <div className="list">
              {history.length ? (
                history.map((item) => (
                  <article className="history-row" key={item.id}>
                    <div>
                      <b>{item.date}</b>
                      <span>{item.branch}</span>
                    </div>
                    <div>
                      <b>
                        {item.check_in} - {item.check_out || "aktif"}
                      </b>
                      <span>{item.duration_text || "Sedang hadir"}</span>
                    </div>
                  </article>
                ))
              ) : (
                <p className="empty">Belum ada riwayat presensi.</p>
              )}
            </div>
          </section>
        ) : status?.active ? (
          <section className="active-card">
            <p className="eyebrow">SESI AKTIF</p>
            <div className="timer">{formatElapsed(elapsed)}</div>
            <p className="muted">
              Mulai absen pukul{" "}
              {new Date(status.session.check_in).toLocaleTimeString("id-ID", {
                hour: "2-digit",
                minute: "2-digit",
              })}
            </p>
            <button className="dark-button" onClick={checkout}>
              Akhiri sesi
            </button>
          </section>
        ) : (
          <section className="checkin-grid">
            <div className="scanner-card">
              <div id="qr-reader" className="scanner-placeholder">
                <span>QR</span>
              </div>
              <h2>Scan QR presensi</h2>
              <p className="muted">Gunakan kamera QR dari perangkat kamu.</p>
              <button className="primary" onClick={startScanner}>
                {scanning ? "Hentikan scan" : "Mulai scan"}
              </button>
            </div>
            <div className="manual-card">
              <p className="eyebrow">ALTERNATIF</p>
              <h2>Masukkan kode</h2>
              <p className="muted">
                Tidak bisa scan? Ketik kode presensi dari admin.
              </p>
              <form onSubmit={checkin} className="inline-form">
                <input
                  value={code}
                  onChange={(e) => setCode(e.target.value.toUpperCase())}
                  maxLength="20"
                  placeholder="Contoh: A1B2C3"
                  required
                />
                <button className="dark-button">Absen</button>
              </form>
            </div>
          </section>
        )}
        {message && <p className="notice">{message}</p>}
        {menuOpen && <div className="mobile-menu member-menu">
            <button onClick={() => selectTab("home")}>Ringkasan</button>
            <button onClick={loadHistory}>Riwayat absen</button>
            <button onClick={() => selectTab("profile")}>Profil</button>
            <button onClick={() => api("auth/logout", { method: "POST" }).then(() => location.reload())}>Keluar</button>
        </div>}
        <nav className="bottom-tabs desktop-member-nav">
          <button
            className={tab === "home" ? "selected" : ""}
            onClick={() => selectTab("home")}
          >
            Ringkasan
          </button>
          <button
            className={tab === "history" ? "selected" : ""}
            onClick={loadHistory}
          >
            Riwayat
          </button>
          <button
            className={tab === "profile" ? "selected" : ""}
            onClick={() => selectTab("profile")}
          >
            Profil
          </button>
          <span>{user.member_no}</span>
        </nav>
      </main>
    </>
  );
}

function AdminDashboard({ user }) {
  const [code, setCode] = useState(null);
  const [today, setToday] = useState([]);
  const [employees, setEmployees] = useState([]);
  const [query, setQuery] = useState("");
  const [message, setMessage] = useState("");
  const [showForm, setShowForm] = useState(false);
  const [form, setForm] = useState({
    name: "",
    username: "",
    password: "",
    gender: "",
  });
  const load = async () => {
    try {
      const [codeData, todayData, employeeData] = await Promise.all([
        api("admin/code"),
        api(`admin/today?q=${encodeURIComponent(query)}`),
        api("admin/users"),
      ]);
      setCode(codeData);
      setToday(todayData.data);
      setEmployees(employeeData.data);
    } catch (err) {
      setMessage(err.message);
    }
  };
  useEffect(() => {
    load();
    const timer = setInterval(load, 5000);
    return () => clearInterval(timer);
  }, [query]);
  const regenerate = async () => {
    const data = await api("admin/code", {
      method: "POST",
      body: JSON.stringify({ action: "regenerate" }),
    });
    setCode(data);
    setMessage("Kode presensi baru dibuat.");
  };
  const toggle = async () => {
    const data = await api("admin/code", {
      method: "POST",
      body: JSON.stringify({ action: "toggle" }),
    });
    setCode(data);
  };
  const createEmployee = async (event) => {
    event.preventDefault();
    try {
      await api("admin/users", {
        method: "POST",
        body: JSON.stringify({ action: "create", ...form }),
      });
      setForm({ name: "", username: "", password: "", gender: "" });
      setShowForm(false);
      setMessage("Karyawan berhasil ditambahkan.");
      load();
    } catch (err) {
      setMessage(err.message);
    }
  };
  const employeeAction = async (id, action) => {
    const password =
      action === "reset_password"
        ? window.prompt("Masukkan password baru:")
        : null;
    if (action === "reset_password" && !password) return;
    if (action === "delete" && !window.confirm("Hapus karyawan ini?")) return;
    try {
      await api("admin/users", {
        method: "POST",
        body: JSON.stringify({ action, id, password }),
      });
      setMessage("Data karyawan berhasil diperbarui.");
      load();
    } catch (err) {
      setMessage(err.message);
    }
  };
  return (
    <>
      <Header
        user={user}
        onLogout={() =>
          api("auth/logout", { method: "POST" }).then(() => location.reload())
        }
      />
      <main className="app-shell">
        <div className="welcome">
          <p className="eyebrow">PANEL ADMIN</p>
          <h1>Ruang kendali.</h1>
          <p className="muted">Pantau kehadiran tim secara langsung.</p>
        </div>
        <section className="admin-grid">
          <div className="code-card">
            <div>
              <p className="eyebrow">KODE HARI INI</p>
              <h2>{code?.active ? "Presensi terbuka" : "Presensi ditutup"}</h2>
              <p className="code-text">{code?.code || "..."}</p>
              <div className="code-actions">
                <button className="dark-button" onClick={regenerate}>
                  Buat kode baru
                </button>
                <button className="ghost" onClick={toggle}>
                  {code?.active ? "Tutup" : "Buka"} presensi
                </button>
              </div>
            </div>
            {code?.code && (
              <QRCodeSVG
                value={code.code}
                size={142}
                bgColor="#fff"
                fgColor="#101b2d"
              />
            )}
          </div>
          <div className="attendance-card">
            <div className="section-head">
              <div>
                <p className="eyebrow">HARI INI</p>
                <h2>{today.length} kehadiran</h2>
              </div>
              <input
                className="search"
                value={query}
                onChange={(e) => setQuery(e.target.value)}
                placeholder="Cari nama..."
              />
            </div>
            <div className="list attendance-list">
              {today.map((item) => (
                <article className="history-row" key={item.session_id}>
                  <div>
                    <b>{item.name}</b>
                    <span>{item.member_no}</span>
                  </div>
                  <div>
                    <b>{item.check_in}</b>
                    <span
                      className={item.status === "sedang_latihan" ? "live" : ""}
                    >
                      {item.status === "sedang_latihan"
                        ? "Sedang hadir"
                        : item.check_out || "Selesai"}
                    </span>
                  </div>
                </article>
              ))}
              {!today.length && (
                <p className="empty">Belum ada presensi hari ini.</p>
              )}
            </div>
          </div>
        </section>
        <section className="employee-card">
          <div className="section-head">
            <div>
              <p className="eyebrow">DATA TIM</p>
              <h2>Kelola karyawan</h2>
            </div>
            <button
              className="dark-button"
              onClick={() => setShowForm(!showForm)}
            >
              {showForm ? "Tutup form" : "+ Tambah karyawan"}
            </button>
          </div>
          {showForm && (
            <form className="employee-form" onSubmit={createEmployee}>
              <input
                required
                placeholder="Nama lengkap"
                value={form.name}
                onChange={(e) => setForm({ ...form, name: e.target.value })}
              />
              <input
                required
                placeholder="Username"
                value={form.username}
                onChange={(e) => setForm({ ...form, username: e.target.value })}
              />
              <input
                required
                type="password"
                placeholder="Password awal"
                value={form.password}
                onChange={(e) => setForm({ ...form, password: e.target.value })}
              />
              <select
                value={form.gender}
                onChange={(e) => setForm({ ...form, gender: e.target.value })}
              >
                <option value="">Jenis kelamin</option>
                <option>Laki-laki</option>
                <option>Perempuan</option>
              </select>
              <button className="primary" type="submit">
                Simpan karyawan
              </button>
            </form>
          )}
          <div className="list employee-list">
            {employees.map((employee) => (
              <article className="history-row" key={employee.id}>
                <div>
                  <b>{employee.name}</b>
                  <span>
                    {employee.member_no} · @{employee.username}
                  </span>
                </div>
                <div className="employee-actions">
                  <span className={employee.status === "Aktif" ? "live" : ""}>
                    {employee.status}
                  </span>
                  <button
                    className="ghost"
                    onClick={() => employeeAction(employee.id, "toggle_status")}
                  >
                    {employee.status === "Aktif" ? "Nonaktifkan" : "Aktifkan"}
                  </button>
                  <button
                    className="ghost"
                    onClick={() =>
                      employeeAction(employee.id, "reset_password")
                    }
                  >
                    Reset password
                  </button>
                  <button
                    className="danger-button"
                    onClick={() => employeeAction(employee.id, "delete")}
                  >
                    Hapus
                  </button>
                </div>
              </article>
            ))}
            {!employees.length && <p className="empty">Belum ada karyawan.</p>}
          </div>
        </section>
        {message && <p className="notice">{message}</p>}
      </main>
    </>
  );
}

function App() {
  const [user, setUser] = useState(undefined);
  useEffect(() => {
    api("auth/me")
      .then((data) => setUser(data.user))
      .catch(() => setUser(null));
  }, []);
  if (user === undefined) return <div className="loading">Memuat...</div>;
  if (!user) return <Login onLogin={setUser} />;
  return user.role === "admin" ? (
    <AdminDashboard user={user} />
  ) : (
    <MemberDashboard user={user} />
  );
}

createRoot(document.getElementById("root")).render(<App />);
