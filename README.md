# ReFindUHO

Sistem pelaporan barang hilang dan temuan Universitas Halu Oleo berbasis PHP dan MySQL.

## Persyaratan

- PHP 8.1 atau lebih baru (PHP 8.2 dari XAMPP sudah sesuai)
- MySQL 8 / MariaDB 10.4 atau lebih baru
- Ekstensi PHP `pdo_mysql` dan `fileinfo`
- XAMPP (Apache tidak wajib jika memakai PHP built-in server)

## Persiapan pertama kali

1. Buka XAMPP Control Panel dan nyalakan **MySQL**.
2. Buka `http://localhost/phpmyadmin`, pilih **Import**, lalu impor `database/schema.sql`.
3. Salin `.env.example` menjadi `.env` di root proyek. Nilai default cocok dengan instalasi XAMPP standar (`root`, tanpa password); sesuaikan jika konfigurasi MySQL berbeda.

## Menjalankan dari VS Code

1. Buka folder proyek `DevReFindUHO` di VS Code.
2. Tekan **Ctrl+Shift+B** lalu pilih **ReFindUHO: Jalankan aplikasi**.
3. Buka `http://127.0.0.1:8000`.

Server berjalan di terminal VS Code. Hentikan dengan **Ctrl+C** pada terminal tersebut.
Task menggunakan PHP XAMPP di `C:\xampp\php\php.exe`; jika PHP terpasang di lokasi lain,
ubah nilai `command` pada `.vscode/tasks.json`.

Alternatif melalui terminal VS Code:

```powershell
C:\xampp\php\php.exe -S 127.0.0.1:8000 -t .
```

## Akun Seed

- Email: `admin@uho.ac.id`
- Password: `admin123`

Segera ubah password administrator setelah instalasi di lingkungan produksi.

## Struktur

- `config/koneksi.php`: koneksi PDO, sesi aman, CSRF, flash, dan helper.
- `models/`: operasi dan aturan data.
- `controllers/`: validasi serta alur proses aplikasi.
- `database/schema.sql`: skema dan data awal.
- `.vscode/tasks.json`: task untuk menjalankan PHP built-in server dari VS Code.
