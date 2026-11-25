# Website Buku Tamu PASS (Enterprise Visitor Management)

Aplikasi manajemen kunjungan tamu perusahaan berbasis **Native PHP** dan **MySQL (RDBMS)** dengan antarmuka HTML murni (**tanpa CSS**), validasi input client-side & server-side, enkripsi password **bcrypt (`password_hash`)**, serta pembatasan hak akses (**Role-Based Access Control / Middleware**).

## Struktur Folder & Halaman

- `db_visitor_pass.sql` : Skema database `db_visitor_pass` beserta data awal (seed).
- `config/database.php` : Konfigurasi koneksi MySQLi.
- `includes/auth_middleware.php` : Middleware autentikasi, RBAC (`require_role`), perlindungan CSRF, dan sanitasi XSS.
- `includes/header.php` & `includes/footer.php` : Template layout HTML semantik (tanpa CSS).
- `index.php` : **Halaman 1 - Pengajuan Janji Kunjungan (Public)**
- `cek_status.php` : **Halaman 2 - Cek Status & Cetak Visitor Pass (Public)**
- `login.php` : **Halaman 3 - Login Petugas / Pegawai**
- `dashboard.php` : **Halaman 4 - Dashboard Keamanan**
- `approval.php` : **Halaman 5 - Approval Izin Masuk (Admin & Pegawai Tujuan)**
- `pos_keamanan.php` : **Halaman 6 - Pos Keamanan Gate Check-In & Check-Out (Admin & Petugas Keamanan)**
- `log_riwayat.php` : **Halaman 7 - Log Riwayat Akses Gedung**
- `kelola_users.php` : **Halaman Tambahan - Manajemen Akun Pengguna (Khusus Admin)**
- `logout.php` : Proses keluar sesi.

## Cara Instalasi

1. Ekstrak folder `buku_tamu_pass` ke direktori web server Anda (misalnya `C:\xampp\htdocs\buku_tamu_pass` atau `C:\laragon\www\buku_tamu_pass`).
2. Jalankan layanan **Apache** dan **MySQL**.
3. Buka **phpMyAdmin** (`http://localhost/phpmyadmin`), lalu impor file `db_visitor_pass.sql`.
4. Sesuaikan kredensial database pada `config/database.php` jika diperlukan (default: user `root`, password kosong `""`).
5. Akses aplikasi melalui browser di `http://localhost/buku_tamu_pass/`.

## Akun Pengujian Default

| Role | Username | Password | Hak Akses |
| :--- | :--- | :--- | :--- |
| **Admin** | `admin` | `admin123` | Semua halaman & kelola user |
| **Pegawai Tujuan** | `budi` | `pegawai123` | Dashboard, Approval Izin Masuk, Log Riwayat |
| **Pegawai Tujuan** | `sari` | `pegawai123` | Dashboard, Approval Izin Masuk, Log Riwayat |
| **Petugas Keamanan** | `satpam1` | `satpam123` | Dashboard, Pos Keamanan (Check-in/out), Log Riwayat |
