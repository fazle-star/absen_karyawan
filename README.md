# Sistem Absensi Karyawan (QR Code) — PHP Native + MySQL

Web dinamis untuk absensi karyawan berbasis QR Code: 2 role (**admin** dan
**karyawan**), dashboard admin dengan QR presensi + kelola akun karyawan +
presensi manual, dan dashboard karyawan dengan scan kamera, sesi berjalan
(live timer), tombol akhiri sesi, notifikasi durasi, serta riwayat presensi.

> **Catatan penting:** hanya admin yang bisa membuat akun karyawan (menu
> **Kelola Karyawan** di dashboard admin). Karyawan tidak bisa mendaftar
> sendiri — mereka hanya bisa login dengan akun yang sudah dibuatkan admin.

## Struktur folder

```
absen-gym/
├── database/
│   └── schema.sql           # opsional: import manual lewat phpMyAdmin
├── includes/
│   └── config.php           # koneksi MySQL (host/user/password), session, helper
└── public/                  # <-- arahkan Laravel Herd / web server ke sini
    ├── index.php
    ├── login.php
    ├── logout.php
    ├── admin/dashboard.php
    ├── admin/users.php      # kelola akun karyawan (khusus admin)
    ├── member/dashboard.php
    ├── api/                # endpoint AJAX (JSON)
    └── assets/css/style.css
```

## Setup database (WAJIB dilakukan dulu)

1. Buka **phpMyAdmin** (biasanya otomatis tersedia di Laravel Herd — cek
   tab "Database" di aplikasi Herd, atau akses lewat `localhost/phpmyadmin`
   tergantung setup kamu).
2. Buat database baru bernama **`absen_karyawan`** (klik "New" / "Baru" →
   isi nama → Create). Tabel-tabel di dalamnya **tidak perlu dibuat
   manual** — aplikasi akan membuatnya otomatis (`users`, `sessions`,
   `attendance_codes`) begitu situsnya pertama kali diakses.
   - Kalau mau tabel langsung ada tanpa menunggu diakses, boleh juga
     import `database/schema.sql` lewat tab **Import** di phpMyAdmin —
     ini opsional, sudah termasuk akun admin default.
3. Buka `includes/config.php`, cek bagian kredensial di atas — defaultnya
   sudah diset untuk MySQL bawaan Laravel Herd (`host: 127.0.0.1`,
   `user: root`, tanpa password). Kalau setup MySQL kamu beda (misal ada
   password root, atau nama database lain), sesuaikan konstanta ini:

   ```php
   define('DB_HOST', '127.0.0.1');
   define('DB_PORT', '3306');
   define('DB_NAME', 'absen_karyawan');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```

## Menjalankan dengan Laravel Herd

1. Copy folder `absen-gym` ke folder situs Herd kamu (mis. `~/Herd/absen-gym`).
2. Buka Herd, pastikan situs `absen-gym` terdeteksi dan **document root**-nya
   diarahkan ke folder `public/` (Herd otomatis mendeteksi folder `public`).
3. Buka `http://absen-gym.test` di browser.

## Menjalankan tanpa Herd (PHP built-in server)

```bash
cd absen-gym
php -S localhost:8000 -t public
```

Buka `http://localhost:8000`.

## Akun admin default

- **Username:** `admin`
- **Password:** `admin123`

Akun karyawan **hanya bisa dibuat oleh admin**, lewat menu **👥 Kelola
Karyawan** di dashboard admin (`/admin/users.php`). Di sana admin bisa:
tambah akun baru, reset password karyawan, nonaktifkan/aktifkan akun, dan
menghapus akun. Karyawan sendiri hanya bisa login — tidak ada halaman
pendaftaran mandiri.

## Alur presensi

1. Admin buka **Dashboard Admin** → QR Code presensi otomatis dibuat untuk
   hari itu (kode 6 karakter, tampil sebagai QR + teks, mis. `F6D1B2`).
2. Karyawan login → tab **Scan** → tekan **Mulai Scan** → arahkan kamera
   HP ke layar admin.
3. Setelah kode tervalidasi, sesi presensi otomatis mulai (tercatat
   `check_in`), karyawan melihat timer berjalan (live).
4. Karyawan menekan **Akhiri Sesi** kapan saja → sistem mencatat
   `check_out` & menghitung durasi → muncul notifikasi *"Kamu telah absen
   selama sekian jam/menit"*.
5. Di **Dashboard Admin**, tabel *Presensi Hari Ini* ter-update otomatis
   (polling tiap 5 detik) — status berubah dari **SEDANG HADIR** ke
   **SELESAI** saat karyawan mengakhiri sesi, dan admin juga bisa
   men-checkout / menghapus data manual.
6. Jika karyawan lupa checkout, sesi yang menginap ke hari berikutnya akan
   otomatis ditutup (Auto Check-out) saat sistem diakses lagi.

## Fitur admin tambahan

- **Kelola Karyawan** — tambah akun, reset password, nonaktifkan/hapus
  akun karyawan (satu-satunya cara membuat akun baru di sistem ini).
- **Nonaktifkan / Aktifkan Presensi** — mematikan sementara validasi kode
  hari itu (karyawan tidak bisa check-in baru).
- **Buat Kode Baru** — regenerate kode QR kapan saja (mis. jika QR lama
  disalahgunakan).
- **Presensi Manual** — cari nama karyawan, klik **Check In** untuk
  mencatat kehadiran tanpa scan (mis. untuk karyawan tanpa HP saat itu).

## Cara melihat isi database (kayak buka phpMyAdmin biasa)

Karena sekarang pakai MySQL, kamu bisa lihat & kelola data langsung lewat
**phpMyAdmin** seperti biasa — buka database `absen_karyawan`, tabel
`users` (daftar akun), `sessions` (riwayat presensi), `attendance_codes`
(kode QR harian). Tidak perlu tool tambahan apa pun.

## Catatan teknis

- Autentikasi berbasis PHP session (`password_hash` / `password_verify`).
- Koneksi database pakai PDO MySQL (`pdo_mysql`) dengan prepared
  statements di semua query — aman dari SQL injection.
- Semua endpoint di `public/api/*` mengembalikan JSON dan memvalidasi role
  (`admin` / `member`) serta status login sebelum memproses.
- QR di sisi admin dirender pakai `qrcodejs` (CDN); scan di sisi member
  pakai `html5-qrcode` (CDN) yang mengakses kamera lewat browser (butuh
  HTTPS atau `localhost`/domain `.test` — kamera tidak jalan di `http://`
  IP biasa selain localhost).
