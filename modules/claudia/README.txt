RS SUKA SEHAT - SIMRS DEMO
==========================

Versi ini sengaja dibuat TANPA MySQL dan TANPA phpMyAdmin.
Cukup jalankan Apache dari XAMPP.

CARA INSTALL:
1. Copy folder "simrs_suka_sehat_role" ke:
   C:\xampp\htdocs\

2. Pastikan file menjadi:
   C:\xampp\htdocs\simrs_suka_sehat_role\index.php

3. Buka XAMPP.
4. Start Apache.
5. Tidak perlu Start MySQL.
6. Buka browser:
   http://localhost/simrs_suka_sehat_role/

AKUN DEMO:
Petugas Registrasi
username: petugas
password: petugas123

Dokter Radiologi
username: radiolog
password: radiolog123

Administrator
username: admin
password: admin123

ROLE:
- petugas -> Dashboard Petugas Registrasi
- radiolog -> Dashboard Dokter Radiologi
- admin -> Dashboard Administrator

Catatan:
Login demo menggunakan PHP Session dan array akun di login.php.
Untuk tugas UTS ini sudah cukup untuk menunjukkan:
- login
- role-based access
- dashboard berbeda berdasarkan role
- logout

Jika nanti mau disambungkan ke MySQL, bagian akun dapat dipindahkan ke tabel users.
