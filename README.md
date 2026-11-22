# Gawean: Modul Akun, Profil, dan Admin

Stack: PHP 8.1+ native, MySQL/MariaDB (PDO), tanpa framework.

## Instalasi
1. Salin folder `gawean` ke `htdocs` (XAMPP) atau web root lainnya.
2. Impor `database.sql` lewat phpMyAdmin atau `mysql -u root < database.sql`.
3. Sesuaikan `DB_*` dan `BASE_URL` di `config.php`.
4. Buat admin pertama: `php seed_admin.php` (akun `admin@gawean.test` / `Admin12345`), lalu hapus file itu.
5. Buka `http://localhost/gawean/`.

## Struktur
- `register.php`, `login.php`, `logout.php`: modul 1 (akun, role, session)
- `profile.php`: modul 2 (profil penyedia, foto, portofolio, ganti password)
- `admin/index.php`, `admin/users.php`: modul 9 (ringkasan, cari, ubah peran, nonaktifkan, hapus)
- `config.php`: koneksi, helper, CSRF, guard role, upload gambar
