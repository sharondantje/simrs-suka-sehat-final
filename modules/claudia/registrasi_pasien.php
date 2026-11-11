<?php
session_start();
if (!isset($_SESSION['user'])) { header('Location: login.php'); exit; }
if (!in_array($_SESSION['user']['role'] ?? '', ['petugas_registrasi', 'admin'], true)) { http_response_code(403); exit('Akses ditolak: halaman ini khusus Petugas Registrasi.'); }
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
$csrfOk = $_SERVER['REQUEST_METHOD'] !== 'POST' || hash_equals($_SESSION['csrf'], (string)($_POST['csrf'] ?? ''));

/* =========================================================
   REGISTRASI KUNJUNGAN
   DEMO - TANPA DATABASE / MYSQL
   Pasien yang dipilih: Siti Aminah
   ========================================================= */

// Data pasien demo
$patient = [
    'medical_record_no' => '00012346',
    'name' => 'Siti Aminah',
    'nik' => '3175016503890001',
    'birth_date' => '1989-03-15',
    'gender' => 'Perempuan'
];

// Ambil pasien dari MySQL (?patient=No.RM), simpan kunjungan ke tabel visits
require_once __DIR__ . '/_db_bridge.php';
$saved = false;
$savedMsg = '';
if (simrs_db_ok()) {
    $mrn = $_GET['patient'] ?? $patient['medical_record_no'];
    $row = simrs_patient_by_mrn($mrn);
    if ($row) {
        $patient = [
            'id' => (int)$row['id'], 'medical_record_no' => $row['mrn'], 'name' => $row['name'], 'nik' => (string)$row['nik'],
            'birth_date' => $row['birth_date'], 'gender' => $row['gender'] === 'L' ? 'Laki-laki' : 'Perempuan',
        ];
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$csrfOk) {
        $savedMsg = 'Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.';
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($patient['id'])) {
        try {
            $type = ($_POST['service_type'] ?? '') === 'Rawat Inap' ? 'rawat_inap' : 'rawat_jalan';
            $when = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['visit_date'] ?? '') ? $_POST['visit_date'] . ' ' . (preg_match('/^\d{2}:\d{2}$/', $_POST['visit_time'] ?? '') ? $_POST['visit_time'] : date('H:i')) . ':00' : null;
            [$vno, $queue] = simrs_create_visit($patient['id'], simrs_poli_id($_POST['clinic'] ?? ''), simrs_insurer_id($_POST['patient_type'] ?? 'Umum'), $type, trim($_POST['complaint'] ?? ''), $when);
            simrs_audit('CREATE', 'Patient Registration', "Registrasi kunjungan {$patient['name']} ({$patient['medical_record_no']}) $vno");
            $saved = true;
            $savedMsg = "No. Kunjungan: $vno · No. Antrean: $queue";
        } catch (Throwable $ex) { $savedMsg = 'Gagal menyimpan: ' . $ex->getMessage(); }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $saved = true;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Registrasi Kunjungan - RS Suka Sehat</title>

    <!-- CSS utama -->
    <link rel="stylesheet" href="assets/style.css">

    <style>
        /* ===============================
   SIDEBAR - STANDARD RS SUKA SEHAT
   =============================== */

.sidebar {
    width: 220px !important;
    min-width: 220px !important;
    max-width: 220px !important;

    height: 100vh !important;
    min-height: 100vh !important;

    background: #0A4D68 !important;

    flex-shrink: 0 !important;
    position: fixed !important;
    left: 0 !important;
    top: 0 !important;

    overflow-y: auto !important;
    overflow-x: hidden !important;

    color: #ffffff !important;
}

/* BRAND */

.sidebar-brand {
    width: 100% !important;
    height: 82px !important;

    padding: 14px 16px !important;

    display: flex !important;
    align-items: center !important;

    gap: 10px !important;

    box-sizing: border-box !important;
}

.sidebar-logo {
    width: 44px !important;
    height: 44px !important;

    flex: 0 0 44px !important;

    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
}

.sidebar-logo img {
    width: 44px !important;
    height: 44px !important;

    display: block !important;

    object-fit: contain !important;
}

.sidebar-brand-name {
    font-size: 15px !important;
    font-weight: 700 !important;
    color: #ffffff !important;

    line-height: 1.2 !important;
    white-space: nowrap !important;
}

.sidebar-brand-name span {
    color: #F36A9A !important;
}

.sidebar-sub {
    margin-top: 3px !important;

    font-size: 9px !important;
    color: #d5edf2 !important;

    white-space: nowrap !important;
}

/* MENU */

.sidebar-menu {
    width: 100% !important;

    padding: 10px 10px !important;

    display: flex !important;
    flex-direction: column !important;

    gap: 4px !important;

    box-sizing: border-box !important;
}

.sidebar-menu a {
    width: 100% !important;
    height: 40px !important;

    padding: 0 16px !important;

    display: flex !important;
    align-items: center !important;

    gap: 10px !important;

    box-sizing: border-box !important;

    border-radius: 8px !important;

    color: #ffffff !important;

    text-decoration: none !important;

    font-size: 12px !important;
    font-weight: 600 !important;

    white-space: nowrap !important;

    transition: 0.2s ease !important;
}

.sidebar-menu a:hover {
    background: rgba(255,255,255,0.08) !important;
}

.sidebar-menu a.active {
    background: #07858a !important;
    color: #ffffff !important;
}

.sidebar-menu a span {
    white-space: nowrap !important;
}

/* MAIN CONTENT MENYESUAIKAN SIDEBAR */

.main-content {
    margin-left: 220px !important;

    width: calc(100% - 220px) !important;

    min-width: 0 !important;

    box-sizing: border-box !important;
}

        /* =====================================================
           REGISTRASI KUNJUNGAN
           ===================================================== */

        .registration-page {
            padding-bottom: 40px;
        }

        .breadcrumb {
            margin-bottom: 10px;
            font-size: 12px;
            color: #5f8192;
        }

        .page-header {
            margin-bottom: 18px;
        }

        .page-header h1 {
            margin: 0 0 4px;
            font-size: 30px;
            color: #0A4D68;
        }

        .page-header p {
            margin: 0;
            color: #6d8998;
            font-size: 13px;
        }

        .registration-panel {
            background: #ffffff;
            border: 1px solid #D6E8EE;
            border-radius: 12px;
            margin-bottom: 14px;
            overflow: hidden;
        }

        .registration-title {
            background: #EAF6F8;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid #D6E8EE;
        }

        .registration-title .icon {
            width: 30px;
            height: 30px;
            border-radius: 7px;
            background: #0D6E6E;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }

        .registration-title h2 {
            margin: 0;
            font-size: 16px;
            color: #0A4D68;
        }

        .registration-body {
            padding: 14px;
        }

        /* =========================
           SEARCH
           ========================= */

        .search-row {
            display: flex;
            gap: 10px;
        }

        .search-row input {
            flex: 1;
            height: 40px;
            border: 1px solid #cbdfe7;
            border-radius: 7px;
            padding: 0 12px;
            font-size: 13px;
            color: #456978;
            background: #f9fcfd;
        }

        .search-row button {
            width: 130px;
            border: none;
            border-radius: 7px;
            background: #318ca8;
            color: white;
            font-weight: 700;
            cursor: pointer;
        }

        .search-info {
            margin-top: 7px;
            font-size: 11px;
            color: #77919d;
        }

        /* =========================
           PATIENT DATA
           ========================= */

        .patient-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .patient-header-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .patient-icon {
            width: 30px;
            height: 30px;
            border-radius: 7px;
            background: #0D6E6E;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .patient-header h2 {
            margin: 0;
            font-size: 16px;
            color: #0A4D68;
        }

        .btn-edit {
            border: 1px solid #38a5b5;
            background: white;
            color: #087789;
            padding: 7px 13px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
        }

        .patient-grid {
            display: grid;
            grid-template-columns: 1fr 1.2fr 1.3fr 1.5fr 1.2fr;
            gap: 10px;
            margin-top: 12px;
        }

        .patient-field label {
            display: block;
            font-size: 10px;
            color: #587988;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .patient-value {
            background: #edf2fc;
            min-height: 32px;
            border-radius: 5px;
            padding: 8px 10px;
            font-size: 12px;
            color: #284d62;
        }

        /* =========================
           VISIT DATA
           ========================= */

        .visit-grid {
            display: grid;
            grid-template-columns: 1.1fr 1.1fr 1.4fr 1fr 1fr;
            gap: 10px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-size: 10px;
            font-weight: 700;
            color: #4c7180;
        }

        .required {
            color: #e05252;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            box-sizing: border-box;
            border: 1px solid #cadfe8;
            border-radius: 6px;
            background: #fbfdfe;
            padding: 8px 10px;
            font-size: 12px;
            color: #365e70;
        }

        .form-group input,
        .form-group select {
            height: 36px;
        }

        .form-group textarea {
            height: 70px;
            resize: vertical;
        }

        .full-width {
            grid-column: 1 / -1;
        }

        .visit-second-row {
            display: grid;
            grid-template-columns: 1fr 1.6fr 1fr;
            gap: 10px;
            margin-top: 12px;
        }

        .radio-group {
            display: flex;
            align-items: center;
            gap: 18px;
            height: 36px;
        }

        .radio-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 12px;
            color: #466b7a;
        }

        .radio-item input {
            width: auto;
            height: auto;
            accent-color: #0D6E6E;
        }

        .form-hint {
            font-size: 9px;
            color: #839aa4;
            margin-top: 3px;
        }

        /* =========================
           BUTTON
           ========================= */

        .form-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 14px;
        }

        .btn-cancel {
            border: 1px solid #c8dce5;
            background: white;
            color: #4f6f7d;
            padding: 9px 20px;
            border-radius: 6px;
            font-weight: 700;
            cursor: pointer;
        }

        .btn-save {
            border: none;
            background: #0D6E6E;
            color: white;
            padding: 10px 25px;
            border-radius: 6px;
            font-weight: 700;
            cursor: pointer;
        }

        .success-message {
            background: #e5f8ed;
            border: 1px solid #b9e7cd;
            color: #24734b;
            padding: 10px 14px;
            border-radius: 7px;
            margin-bottom: 14px;
            font-size: 12px;
        }

        @media (max-width: 900px) {

            .patient-grid,
            .visit-grid,
            .visit-second-row {
                grid-template-columns: 1fr 1fr;
            }

        }
/* ===== FIX TOP BAR USER ===== */

.topbar {
    width: 100%;
    min-height: 52px;
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    box-sizing: border-box;
}

.search-top {
    display: flex !important;
    align-items: center !important;
    gap: 10px;
}

.top-user {
    display: flex !important;
    align-items: center !important;
    justify-content: flex-end !important;
    gap: 9px !important;

    height: 42px !important;
    padding: 0 14px !important;

    white-space: nowrap !important;
    box-sizing: border-box !important;
}

.user-icon {
    width: 32px !important;
    height: 32px !important;

    display: flex !important;
    align-items: center !important;
    justify-content: center !important;

    flex-shrink: 0 !important;

    border-radius: 50% !important;
    background: #0b5874 !important;

    font-size: 15px !important;
}

.user-name {
    display: flex !important;
    flex-direction: column !important;
    justify-content: center !important;
    align-items: flex-start !important;

    line-height: 1.1 !important;

    min-width: 105px !important;
}

.user-name strong {
    display: block !important;

    margin: 0 !important;
    padding: 0 !important;

    font-size: 13px !important;
    font-weight: 700 !important;

    color: #0A4D68 !important;

    white-space: nowrap !important;
}

.user-name small {
    display: block !important;

    margin: 2px 0 0 0 !important;
    padding: 0 !important;

    font-size: 9px !important;

    color: #6b8a98 !important;

    white-space: nowrap !important;
}

.top-user > a {
    display: flex !important;
    align-items: center !important;

    margin-left: 8px !important;
    padding: 0 !important;

    font-size: 12px !important;
    font-weight: 700 !important;

    color: #0A4D68 !important;

    text-decoration: none !important;

    white-space: nowrap !important;
}

.top-user > a:hover {
    text-decoration: underline !important;
}
    </style>


<!-- SIMRS: tema seragam portal (font, warna, ukuran) -->
<script>document.documentElement.dataset.simrsMod="claudia";if(window.top!==window.self)document.documentElement.classList.add("embedded-simrs-module");</script>
<link rel="stylesheet" id="simrs-module-theme" href="../../assets/module-theme.css">
</head>


<body>

<div class="app">

    <!-- =====================================================
         SIDEBAR
         ===================================================== -->

    <aside class="sidebar">

        <div class="sidebar-brand">

            <div class="sidebar-logo">

                <img
                    src="assets/logo.png"
                    alt="Logo RS Suka Sehat"
                >

            </div>

            <div>

                <div class="sidebar-brand-name">
                    RS <span>Suka</span> Sehat
                </div>

                <div class="sidebar-sub">
                    SIMRS
                </div>

            </div>

        </div>


        <nav class="sidebar-menu">

            <a href="dashboard.php">
                🏠︎
                <span>Dashboard</span>
            </a>

            <a href="pendaftaran_pasien.php">
                ♟+
                <span>Pendaftaran Pasien</span>
            </a>

            <a href="Registrasi_pasien.php" class="active">
                ✍
                <span>Registrasi Kunjungan</span>
            </a>

            <a href="data_pasien.php">
                📋
                <span>Data Pasien</span>
            </a>

        </nav>

    </aside>


    <!-- =====================================================
         MAIN CONTENT
         ===================================================== -->

    <main class="main-content">


        <!-- TOP BAR -->

        <header class="topbar">

            <div class="search-top">
                🔎
                <span>Cari menu...</span>
            </div>

            <div class="top-user">

                <div class="user-icon">
                    🙍‍♂️
                </div>

                <div class="user-name">

                    <strong>Andi Putra</strong>

                    <small>Petugas Registrasi</small>

                </div>

                <a href="logout.php">
                    Logout
                </a>

            </div>

        </header>


        <!-- PAGE -->

        <section class="registration-page">


            <!-- BREADCRUMB -->

            <div class="breadcrumb">

                🏠
                &nbsp;›&nbsp;
                Pendaftaran Pasien
                &nbsp;›&nbsp;
                <strong>Registrasi Kunjungan</strong>

            </div>


            <!-- HEADER -->

            <div class="page-header">

                <h1>
                    Registrasi Kunjungan
                </h1>

                <p>
                    Lengkapi data kunjungan pasien untuk melanjutkan proses pendaftaran.
                </p>

            </div>


            <?php if ($saved): ?>

                <div class="success-message">

                    ✓ Registrasi kunjungan berhasil disimpan. <?= htmlspecialchars($savedMsg) ?>

                </div>

            <?php elseif ($savedMsg !== ''): ?>

                <div class="success-message" style="background:#FEE2E2;border-color:#FCA5A5;color:#B91C1C">✕ <?= htmlspecialchars($savedMsg) ?></div>

            <?php endif; ?>


            <!-- =================================================
                 CARI PASIEN
                 ================================================= -->

            <div class="registration-panel">

                <div class="registration-title">

                    <div class="icon">
                        🔎
                    </div>

                    <h2>
                        Cari Pasien
                    </h2>

                </div>


                <div class="registration-body">

                    <div class="search-row">

                        <input
                            type="text"
                            value="Siti Aminah"
                            placeholder="Masukkan No. RM, NIK, atau Nama Pasien..."
                        >

                        <button type="button">
                            🔎 &nbsp; Cari Pasien
                        </button>

                    </div>

                    <div class="search-info">

                        Pasien ditemukan dan telah dipilih.

                    </div>

                </div>

            </div>


            <!-- =================================================
                 DATA PASIEN
                 ================================================= -->

            <div class="registration-panel">

                <div class="registration-body">

                    <div class="patient-header">

                        <div class="patient-header-left">

                            <div class="patient-icon">
                                ♟
                            </div>

                            <h2>
                                Data Pasien
                            </h2>

                        </div>


                        <button
                            type="button"
                            class="btn-edit"
                        >
                            ✎ &nbsp; Ubah Data Pasien
                        </button>

                    </div>


                    <div class="patient-grid">


                        <div class="patient-field">

                            <label>
                                No. RM
                            </label>

                            <div class="patient-value">
                                <?= htmlspecialchars($patient['medical_record_no']) ?>
                            </div>

                        </div>


                        <div class="patient-field">

                            <label>
                                Nama Pasien
                            </label>

                            <div class="patient-value">
                                <?= htmlspecialchars($patient['name']) ?>
                            </div>

                        </div>


                        <div class="patient-field">

                            <label>
                                NIK
                            </label>

                            <div class="patient-value">
                                <?= htmlspecialchars($patient['nik']) ?>
                            </div>

                        </div>


                        <div class="patient-field">

                            <label>
                                Tgl Lahir
                            </label>

                            <div class="patient-value">
                                15-03-1989 (35 tahun)
                            </div>

                        </div>


                        <div class="patient-field">

                            <label>
                                Jenis Kelamin
                            </label>

                            <div class="patient-value">
                                <?= htmlspecialchars($patient['gender']) ?>
                            </div>

                        </div>


                    </div>

                </div>

            </div>


            <!-- =================================================
                 DATA KUNJUNGAN
                 ================================================= -->

            <form method="POST">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">


                <div class="registration-panel">

                    <div class="registration-title">

                        <div class="icon">
                            ▣
                        </div>

                        <h2>
                            Data Kunjungan
                        </h2>

                    </div>


                    <div class="registration-body">


                        <!-- ROW 1 -->

                        <div class="visit-grid">


                            <div class="form-group">

                                <label>
                                    Jenis Pelayanan
                                    <span class="required">*</span>
                                </label>

                                <select name="service_type">

                                    <option value="Rawat Jalan">
                                        Rawat Jalan
                                    </option>

                                    <option value="Rawat Inap">
                                        Rawat Inap
                                    </option>

                                </select>

                            </div>


                            <div class="form-group">

                                <label>
                                    Poliklinik
                                    <span class="required">*</span>
                                </label>

                                <select name="clinic">

                                    <option>
                                        Poliklinik Umum
                                    </option>

                                    <option>
                                        Poliklinik Penyakit Dalam
                                    </option>

                                    <option>
                                        Poliklinik Bedah
                                    </option>

                                    <option>
                                        Poliklinik Anak
                                    </option>

                                </select>

                            </div>


                            <div class="form-group">

                                <label>
                                    Dokter
                                    <span class="required">*</span>
                                </label>

                                <select name="doctor">

                                    <option>
                                        dr. Sinta, Sp.PD
                                    </option>

                                    <option>
                                        dr. Budi Santoso, Sp.A
                                    </option>

                                    <option>
                                        dr. Siti Rahma, Sp.B
                                    </option>

                                </select>

                            </div>


                            <div class="form-group">

                                <label>
                                    Tanggal Kunjungan
                                    <span class="required">*</span>
                                </label>

                                <input
                                    type="date"
                                    name="visit_date"
                                    value="2025-09-03"
                                >

                            </div>


                            <div class="form-group">

                                <label>
                                    Jam Kunjungan
                                    <span class="required">*</span>
                                </label>

                                <input
                                    type="time"
                                    name="visit_time"
                                    value="10:00"
                                >

                            </div>


                        </div>


                        <!-- ROW 2 -->

                        <div class="visit-second-row">


                            <div class="form-group">

                                <label>
                                    Jenis Pasien
                                    <span class="required">*</span>
                                </label>

                                <div class="radio-group">

                                    <label class="radio-item">

                                        <input
                                            type="radio"
                                            name="patient_type"
                                            value="BPJS"
                                            checked
                                        >

                                        BPJS

                                    </label>


                                    <label class="radio-item">

                                        <input
                                            type="radio"
                                            name="patient_type"
                                            value="Umum"
                                        >

                                        Umum

                                    </label>


                                    <label class="radio-item">

                                        <input
                                            type="radio"
                                            name="patient_type"
                                            value="Asuransi Lain"
                                        >

                                        Asuransi Lain

                                    </label>

                                </div>

                            </div>


                            <div class="form-group">

                                <label>
                                    No. Kartu BPJS
                                </label>

                                <input
                                    type="text"
                                    name="bpjs_number"
                                    placeholder="Masukkan nomor kartu"
                                    maxlength="13"
                                >

                                <div class="form-hint">
                                    13 digit angka
                                </div>

                            </div>


                            <div class="form-group">

                                <label>
                                    Kelas
                                    <span class="required">*</span>
                                </label>

                                <select name="class">

                                    <option>
                                        Kelas 1
                                    </option>

                                    <option>
                                        Kelas 2
                                    </option>

                                    <option>
                                        Kelas 3
                                    </option>

                                    <option>
                                        VIP
                                    </option>

                                </select>

                            </div>


                        </div>


                        <!-- KELUHAN -->

                        <div
                            class="form-group"
                            style="margin-top:12px;"
                        >

                            <label>
                                Keluhan Utama
                            </label>

                            <textarea
                                name="complaint"
                                maxlength="255"
                                placeholder="Masukkan keluhan utama pasien (opsional)..."
                            ></textarea>

                            <div
                                class="form-hint"
                                style="text-align:right;"
                            >
                                0/255
                            </div>

                        </div>


                        <!-- ACTION -->

                        <div class="form-actions">

                            <button
                                type="button"
                                class="btn-cancel"
                                onclick="history.back()"
                            >
                                ✕ &nbsp; Batal
                            </button>


                            <button
                                type="submit"
                                class="btn-save"
                            >
                                ▣ &nbsp; Simpan Registrasi
                            </button>

                        </div>


                    </div>

                </div>


            </form>


        </section>

    </main>

</div>

</body>
</html>
