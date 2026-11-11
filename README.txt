SIMRS RS SUKA SEHAT - VERSI TERINTEGRASI (DATABASE MySQL / phpMyAdmin)
=====================================================================

RINGKASAN
- Satu halaman login, satu tampilan (sidebar + topbar + dashboard) yang SAMA untuk
  semua role: font Inter, warna, dan ukuran teks seragam.
- Isi menu tiap role berbeda sesuai hak akses (RBAC), tetapi bentuk tampilannya sama.
- Data utama (user, role, pasien, kunjungan, master data, audit log, laporan)
  tersimpan di database MySQL "simrs_suka_sehat" yang dikelola lewat phpMyAdmin.
- Modul asli tiap anggota (Claudia, Max, Mikael, Valerie, Sharon) tetap dipakai
  dan dibuka di dalam portal, dengan tema yang sudah diseragamkan.


CARA MENJALANKAN (XAMPP)
1. Salin folder SIMRS_GABUNG_FINAL ke C:\xampp\htdocs\
2. Buka XAMPP Control Panel -> Start "Apache" dan "MySQL".
3. Buka http://localhost/phpmyadmin
   - Klik tab "Import"
   - Pilih file: database\simrs_suka_sehat.sql
   - Klik "Import" (database simrs_suka_sehat beserta 55 tabel, 97 relasi + data contoh dibuat otomatis)
4. Buka http://localhost/SIMRS_GABUNG_FINAL/  (otomatis ke halaman login)

Pengaturan koneksi database ada di config/database.php
(default XAMPP: host 127.0.0.1, port 3306, user root, password kosong).
Jika MySQL Anda memakai password/port lain, ubah di file tersebut.

Untuk mengulang data dari awal: import ulang database\simrs_suka_sehat.sql
(file ini menghapus lalu membuat ulang database).


AKUN LOGIN (juga tersedia sebagai tombol "Akun Demo" di halaman login)
Role                    Username        Password
Administrator           admin           admin123
Petugas Registrasi      petugas         petugas123
Radiolog                radiolog        radiolog123
Dokter                  dokter          dokter123
Perawat                 perawat         perawat123
Kasir                   kasir           kasir123
Farmasi                 farmasi         farmasi123
Laboratorium            laboran         laboran123
Petugas Gudang          gudang          gudang123
Kepala Gudang           kepala_gudang   head123
Eksekutif / Direksi     executive       exec123
Analis Data             analyst         analyst123

Password disimpan sebagai hash bcrypt (password_hash). Password bisa diganti
lewat menu "Profil & Password" atau di-reset admin lewat "Manajemen User".


16 MODUL & LETAK FITURNYA (semua sudah tersimpan ke database MySQL)
 1. Auth & User Mgmt        login.php, pages/users.php, pages/roles.php, pages/audit.php, pages/profile.php
 2. Patient Registration    modules/claudia/pendaftaran_pasien.php & data_pasien.php (tabel patients, visits)
 3. EMR                     pages/emr.php        (SOAP, tanda vital, ICD-10, CDSS, finalisasi)
 4. CPOE                    pages/cpoe.php       (order lab/radiologi/obat, status, pembatalan)
 5. Outpatient              pages/rawatjalan.php (antrean, pelayanan, tindak lanjut, finalisasi)
 6. Inpatient               pages/rawatinap.php  (admisi, kamar & bed, transfer, catatan, pulang)
 7. IGD                     pages/igd.php        (triase, pemeriksaan, observasi, disposisi)
 8. Laboratory              pages/lab.php        (spesimen, input hasil, validasi, nilai kritis)
 9. Radiology               pages/radiologi.php  (jadwal, unggah gambar, expertise)
10. Pharmacy                pages/farmasi.php    (telaah resep, dispensing, stok, retur, laporan)
11. Billing & Insurance     pages/billing.php    (tagihan, penjamin, pembayaran, klaim)
12. Scheduling              pages/jadwal.php     (appointment, check-in, jadwal dokter)
13. Nursing                 pages/nursing.php    (identifikasi, asesmen, askep, MAR, monitoring)
14. Inventory               pages/inventory.php  (penerimaan, mutasi FEFO, stock opname, approval)
15. Reporting & Dashboard   pages/dashboard.php, pages/reports.php, pages/executive.php, pages/analitik.php
16. Master Data & Config    pages/master.php (dokter, poli, ruang, bed, tarif, ICD-10, obat,
                            tes lab, pemeriksaan radiologi, penjamin), pages/settings.php

Alur yang saling tersambung lewat database:
  Registrasi -> Antrean/Appointment -> Rawat Jalan/IGD -> EMR -> CPOE
  -> Lab / Radiologi / Farmasi -> Rawat Inap & Asuhan Keperawatan -> Billing -> Laporan

Stepper (langkah-langkah proses) tampil di atas form pada modul berproses:
  Registrasi, Appointment, EMR, CPOE, Rawat Jalan, Rawat Inap, IGD, Asuhan
  Keperawatan, Lab, Radiologi, Farmasi, Billing, Penerimaan Barang, dan Stock Opname.


DOKUMEN PENDUKUNG (folder docs/) — disusun mengikuti 3 panduan dosen
docs/ERD/   TRD_SIMRS_Suka_Sehat.drawio (buka di draw.io / diagrams.net)
            TRD_00..05_*.png  -> diagram relasi tabel lengkap + per domain
            Kamus_Data.html   -> kamus data seluruh tabel & kolom (PK/FK/UQ, relasi)
docs/UML/   NN_Modul.drawio   -> Use Case Diagram + Activity Diagram (swimlane) tiap modul
            NN_Modul_UseCase.png, NN_Modul_Activity.png
docs/SOP/   SOP_NN_Modul.docx + .pdf -> SOP 16 modul sesuai template (kop RS, Pengertian,
            Tujuan, Kebijakan, Hak Akses, Prosedur, Diagram Alur, Riwayat Perubahan,
            Lampiran UML)
Semua diagram & SOP dibuat dari kode dan skema database yang sebenarnya, jadi
isinya sama dengan yang berjalan di aplikasi.


STRUKTUR FOLDER
config/database.php       koneksi PDO MySQL
database/simrs_suka_sehat.sql   skema + data contoh (import di phpMyAdmin)
includes/bootstrap.php    helper, session, CSRF, audit log, menu per role (RBAC), ikon
includes/layout.php       sidebar + topbar yang dipakai semua halaman
assets/simrs.css          design system utama (warna, font, ukuran)
assets/module-theme.css   penyeragam tampilan modul anggota yang dibuka di portal
pages/                    halaman PHP semua modul (lihat daftar 16 modul di atas)
portal.php                kerangka utama setelah login (memuat page: atau modul anggota)
modules/                  modul asli tiap anggota (registrasi Claudia dipakai langsung)
uploads/radiologi/        file gambar hasil radiologi yang diunggah (dibuat otomatis)
docs/                     ERD/TRD, kamus data, UML, dan SOP


STANDAR TAMPILAN (sama untuk semua role) — mengikuti color palette & Panduan UI SIMRS
Logo        : assets/logo.png (dari "rs suka sehat png.png"), juga favicon & modul anggota
Warna       : Primary   #0D6E6E  aksi utama, menu aktif
              Secondary #0A4D68  sidebar
              Tertiary  #005F73  aksen pendukung
              Neutral   #0F172A  teks utama, tombol inverted
              Latar abu-abu sangat terang #F2F5F9, kartu putih
Font        : Inter untuk judul & isi
              JetBrains Mono untuk label, tombol, badge, header tabel, dan data
              terstruktur (No. RM, kode ICD-10, kode obat, NIP, timestamp)
Ukuran      : judul halaman 24px bold, judul seksi 18px semibold,
              isi/tabel/form 14px, label/caption 12px, angka KPI 28px
Status      : hijau = selesai/tersedia, kuning = menunggu/proses,
              merah = error/habis/hapus, biru/abu = info/terjadwal
Tombol      : Primary (teal), Secondary (lembut), Inverted (navy), Outlined,
              Danger (merah, untuk hapus/batal)
Interaksi   : - konfirmasi hapus/nonaktifkan/logout memakai modal (bukan alert browser)
              - toast notifikasi setelah simpan/hapus
              - validasi inline sebelum submit (NIK 16 digit, ICD-10, tanggal lahir,
                field wajib bertanda *)
              - logout tersedia di sidebar dan header; role tampil di header
              - mode cetak menyembunyikan sidebar/header/tombol


CATATAN MODUL SHARON (React)
Portal memakai hasil build di modules/sharon/dist (sudah disertakan, tidak perlu Node.js).
Jika source Sharon diubah, build ulang:
  cd modules/sharon
  npm install
  npm run build:portal


CATATAN DATA & KEAMANAN
- Semua 16 modul membaca & menyimpan data ke database "simrs_suka_sehat"
  (tidak ada lagi data contoh yang hanya tersimpan di browser).
- Setiap form memakai token CSRF; setiap halaman dicek login dan hak akses role.
  Membuka URL menu milik role lain otomatis dialihkan ke dashboard sendiri.
- Setiap aksi penting (tambah/ubah/hapus/validasi) tercatat di Audit Log.
- Unggahan gambar radiologi hanya menerima JPG/PNG/DCM/PDF; folder uploads/
  dilindungi .htaccess agar tidak bisa menjalankan skrip.

PENGUJIAN YANG SUDAH DILAKUKAN (11 November 2026)
- Import database\simrs_suka_sehat.sql ke database kosong: 55 tabel, 97 relasi, tanpa error.
- 76 halaman menu dari 12 role dibuka satu per satu: tanpa error PHP.
- Uji alur end-to-end (184 pengecekan, semua lulus): registrasi -> appointment -> rawat jalan
  -> EMR -> CPOE -> lab (nilai kritis & revisi) -> radiologi (unggah gambar) -> farmasi
  -> IGD -> rawat inap (transfer, pulang) -> asuhan keperawatan & MAR -> billing umum
  (cicilan, pelunasan) & asuransi (klaim) -> gudang (penerimaan, mutasi FEFO, stock opname,
  approval kepala gudang) -> admin (akun, reset password, master data, audit log).
  Termasuk uji negatif: data wajib kosong, NIK salah, jadwal bentrok, stok minus,
  ICD-10 tidak valid, bayar melebihi sisa, akses role lain, token CSRF salah.
