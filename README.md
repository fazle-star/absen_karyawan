# Absensi Karyawan

Aplikasi presensi dengan React, Vite, Vercel Node Functions, dan PostgreSQL.

## Menjalankan lokal

1. Salin `.env.example` menjadi `.env`.
2. Isi `DATABASE_URL` dengan connection string PostgreSQL dan isi `JWT_SECRET`.
3. Jalankan:

```bash
npm install
npm run dev
```

Database dibuat otomatis saat API pertama kali dipanggil. Akun admin awal memakai username `admin` dan password dari `ADMIN_PASSWORD` (default `admin123`).

## Deploy ke Vercel

Import repository `absen_karyawan` ke Vercel, lalu tambahkan environment variables berikut:

- `DATABASE_URL`: connection string PostgreSQL, termasuk `sslmode=require` bila provider memerlukannya.
- `JWT_SECRET`: secret acak panjang untuk cookie login.
- `ADMIN_PASSWORD`: password admin awal.
- `BRANCH_NAME`: nama cabang, opsional.

Build command: `npm run build`. Vercel otomatis menyajikan hasil Vite dan function `api/[...path].js`.

## Fitur

- Login berbasis cookie JWT dengan role admin dan member.
- Member dapat check-in melalui QR kamera atau kode presensi manual.
- Timer sesi, checkout, dan riwayat presensi.
- Admin dapat membuat ulang atau menutup kode presensi.
- Admin dapat melihat presensi hari ini dan mengelola operasi member melalui API Node.
