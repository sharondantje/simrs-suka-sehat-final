<?php
session_start();

/* =========================================================
   CEK LOGIN
========================================================= */

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];

/* Hanya Radiolog */
if (($user['role'] ?? '') !== 'radiologist') {
    header('Location: dashboard.php');
    exit;
}


/* =========================================================
   DATA USER
========================================================= */

$userName = 'Rini Sutijah';
$userRoleLabel = 'Radiolog';


/* =========================================================
   AMBIL NOMOR ORDER
========================================================= */

$orderNumber = $_GET['order'] ?? 'RAD202509030001';


/* =========================================================
   DATA HASIL RADIOLOGI
========================================================= */

$results = [

    'RAD202509030001' => [

        'no_rm' => '2025001234',

        'nama' => 'Budi Santoso',

        'tanggal_lahir' => '12-05-1980',

        'umur' => '45 th',

        'jenis_kelamin' => 'Laki-laki',

        'alamat' => 'Jl. Melati No. 10, Tangerang',

        'no_hp' => '0812-3456-7890',

        'pemeriksaan' => 'Thorax PA',

        'modalitas' => 'CR',

        'tanggal' => '03-09-2025 08:12',

        'dokter_pengirim' => 'dr. Andi Pratama, Sp.PD',

        'indikasi' => 'Batuk kronis',

        'status' => 'Selesai',

        'findings' =>
            'Corakan bronkovaskular dalam batas normal. ' .
            'Tidak tampak infiltrat atau konsolidasi fokal. ' .
            'Tidak tampak efusi pleura. ' .
            'Ukuran jantung dalam batas normal.',

        'impression' =>
            'Tidak tampak kelainan radiologis aktif pada foto thorax.',

        'rekomendasi' =>
            'Korelasi dengan kondisi klinis pasien bila diperlukan.'

    ],

    'RAD202509030002' => [

        'no_rm' => '2025001235',
        'nama' => 'Siti Aminah',
        'tanggal_lahir' => '21-08-1987',
        'umur' => '38 th',
        'jenis_kelamin' => 'Perempuan',
        'alamat' => 'Jl. Mawar No. 15, Tangerang',
        'no_hp' => '0813-4567-8901',

        'pemeriksaan' => 'Abdomen AP',
        'modalitas' => 'CR',
        'tanggal' => '03-09-2025 08:45',
        'dokter_pengirim' => 'dr. Budi Santoso, Sp.PD',
        'indikasi' => 'Nyeri abdomen',
        'status' => 'Menunggu',

        'findings' => '',
        'impression' => '',
        'rekomendasi' => ''

    ],

    'RAD202509030003' => [

        'no_rm' => '2025001236',
        'nama' => 'Joko Widodo',
        'tanggal_lahir' => '15-04-1973',
        'umur' => '52 th',
        'jenis_kelamin' => 'Laki-laki',
        'alamat' => 'Jl. Kenanga No. 8, Tangerang',
        'no_hp' => '0814-5678-9012',

        'pemeriksaan' => 'CT Head',
        'modalitas' => 'CT',
        'tanggal' => '02-09-2025 14:20',
        'dokter_pengirim' => 'dr. Rina Wijaya, Sp.N',
        'indikasi' => 'Nyeri kepala',
        'status' => 'Selesai',

        'findings' =>
            'Tidak tampak perdarahan intrakranial akut.',

        'impression' =>
            'Tidak tampak kelainan intrakranial akut.',

        'rekomendasi' =>
            'Korelasi klinis.'

    ]

];


/* =========================================================
   JIKA ORDER TIDAK ADA
========================================================= */

if (!isset($results[$orderNumber])) {
    $orderNumber = 'RAD202509030001';
}

$data = $results[$orderNumber];

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>
Hasil Radiologi - <?= htmlspecialchars($data['nama']) ?>
</title>


<style>

/* =========================================================
   RESET
========================================================= */

* {
    box-sizing: border-box;
}

html,
body {
    margin: 0;
    padding: 0;
}

body {
    font-family: Arial, Helvetica, sans-serif;
    background: #F3FAFC;
    color: #0A4D68;
    font-size: 12px;
}


/* =========================================================
   SIDEBAR
========================================================= */

.sidebar {

    position: fixed;

    top: 0;
    left: 0;
    bottom: 0;

    width: 230px;

    background: #0A4D68;

    color: white;

    z-index: 20;
}


.sidebar-brand {

    height: 82px;

    display: flex;

    align-items: center;

    gap: 10px;

    padding: 14px 15px;
}


.sidebar-logo {

    width: 46px;
    height: 46px;

    border-radius: 50%;

    background: white;

    display: flex;

    align-items: center;
    justify-content: center;

    overflow: hidden;
}


.sidebar-logo img {

    width: 42px;
    height: 42px;

    object-fit: contain;
}


.sidebar-brand-name {

    font-size: 15px;

    font-weight: 700;

    color: white;

    line-height: 1.2;
}


.sidebar-brand-name span {

    color: #F36A9A;
}


.sidebar-sub {

    font-size: 9px;

    color: #b9d9e2;

    margin-top: 4px;
}


/* =========================================================
   SIDEBAR MENU
========================================================= */

.sidebar-menu {

    padding: 10px;
}


.sidebar-menu a {

    display: flex;

    align-items: center;

    gap: 10px;

    padding: 12px 14px;

    margin-bottom: 5px;

    border-radius: 9px;

    text-decoration: none;

    color: white;

    font-size: 12px;

    font-weight: 600;

    transition: .2s;
}


.sidebar-menu a:hover {

    background: rgba(255,255,255,.10);
}


.sidebar-menu a.active {

    background: #0d7c7b;
}


.menu-icon {

    width: 20px;

    text-align: center;

    font-size: 15px;
}


/* =========================================================
   SIDEBAR FOOTER
========================================================= */

.sidebar-footer {

    position: absolute;

    left: 0;
    right: 0;
    bottom: 20px;

    text-align: center;

    color: #b9d9e2;

    font-size: 9px;
}


.sidebar-footer .heart {

    color: #F36A9A;

    font-size: 20px;

    margin-bottom: 5px;
}


/* =========================================================
   MAIN
========================================================= */

.main-content {

    margin-left: 230px;

    min-height: 100vh;

    padding: 0 30px 35px;
}


/* =========================================================
   TOPBAR
========================================================= */

.topbar {

    height: 75px;

    margin-top: 20px;

    background: white;

    border: 1px solid #D6E8EE;

    border-radius: 10px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    padding: 0 18px;

    box-shadow:
        0 2px 7px rgba(30,70,90,.04);
}


.search-top {

    height: 38px;

    flex: 1;

    display: flex;

    align-items: center;

    gap: 10px;

    color: #76919f;
}


.search-top input {

    width: 100%;

    border: none;

    outline: none;

    font-size: 12px;
}


.top-user {

    display: flex;

    align-items: center;

    gap: 10px;

    margin-left: 20px;
}


.user-circle {

    width: 34px;
    height: 34px;

    border-radius: 50%;

    background: #0A4D68;

    color: white;

    display: flex;

    align-items: center;
    justify-content: center;
}


.user-name {

    font-size: 12px;

    font-weight: 700;

    color: #0A4D68;
}


.user-role {

    font-size: 9px;

    color: #6b8a98;

    margin-top: 2px;
}


.logout {

    text-decoration: none;

    color: #0A4D68;

    font-size: 11px;

    font-weight: 700;

    margin-left: 8px;
}


/* =========================================================
   BREADCRUMB
========================================================= */

.breadcrumb {

    display: flex;

    align-items: center;

    gap: 8px;

    margin-top: 20px;

    font-size: 12px;
}


.breadcrumb a {

    color: #1D5FA8;

    text-decoration: none;
}


.breadcrumb .current {

    color: #1D5FA8;

    font-weight: 600;
}


/* =========================================================
   HEADER
========================================================= */

.page-title {

    margin: 8px 0 5px;

    font-size: 28px;

    color: #064963;
}


.page-subtitle {

    margin: 0;

    color: #66869A;

    font-size: 12px;
}


.back-link {

    display: inline-block;

    margin-top: 9px;

    color: #1769d1;

    text-decoration: none;

    font-size: 12px;
}


/* =========================================================
   PATIENT CARD
========================================================= */

.patient-card {

    background: white;

    border: 1px solid #D6E8EE;

    border-radius: 8px;

    margin-top: 15px;

    padding: 15px;
}


.patient-header {

    display: flex;

    align-items: center;

    justify-content: space-between;

    margin-bottom: 12px;
}


.patient-title {

    display: flex;

    align-items: center;

    gap: 9px;
}


.patient-icon {

    width: 30px;
    height: 30px;

    border-radius: 5px;

    background: #0D6E6E;

    color: white;

    display: flex;

    align-items: center;
    justify-content: center;
}


.patient-title h2 {

    margin: 0;

    font-size: 13px;

    color: #0A4D68;
}


.status-badge {

    padding: 6px 10px;

    border-radius: 5px;

    background: #d9f5e4;

    color: #16885d;

    font-size: 9px;

    font-weight: 700;
}


.patient-grid {

    display: grid;

    grid-template-columns:
        1.4fr
        1fr
        1fr;

    gap: 15px;
}


.info-label {

    display: block;

    color: #77909c;

    font-size: 8px;

    margin-bottom: 3px;
}


.info-value {

    color: #315E6E;

    font-size: 10px;

    font-weight: 600;

    margin-bottom: 9px;
}


/* =========================================================
   EXAM INFO
========================================================= */

.exam-info {

    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 10px;

    margin-top: 12px;
}


.exam-box {

    background: #f3f9fc;

    border-radius: 5px;

    padding: 9px;
}


.exam-box .label {

    color: #718994;

    font-size: 8px;

    margin-bottom: 4px;
}


.exam-box .value {

    color: #315E6E;

    font-size: 9px;

    font-weight: 600;
}


/* =========================================================
   MAIN CONTENT GRID
========================================================= */

.result-grid {

    display: grid;

    grid-template-columns:
        1.45fr
        1fr;

    gap: 15px;

    margin-top: 15px;
}


.panel {

    background: white;

    border: 1px solid #D6E8EE;

    border-radius: 8px;

    overflow: hidden;

    margin-bottom: 15px;
}


.panel-header {

    display: flex;

    align-items: center;

    gap: 9px;

    padding: 11px 13px;

    background: #f8fcfd;

    border-bottom: 1px solid #dce9ed;
}


.panel-icon {

    width: 28px;
    height: 28px;

    border-radius: 5px;

    background: #0D6E6E;

    color: white;

    display: flex;

    align-items: center;
    justify-content: center;
}


.panel-header h2 {

    margin: 0;

    font-size: 13px;

    color: #0A4D68;
}


.panel-body {

    padding: 13px;
}


/* =========================================================
   RADIOLOGY VIEWER
========================================================= */

.viewer {

    height: 380px;

    background: #182126;

    border-radius: 5px;

    position: relative;

    display: flex;

    align-items: center;
    justify-content: center;

    overflow: hidden;
}


.xray-image {

    width: 88%;

    height: 88%;

    position: relative;

    background:
        radial-gradient(
            ellipse at center,
            #dfe4e5 0%,
            #aeb6b8 35%,
            #555f62 68%,
            #171c1e 100%
        );

    border-radius: 50%;

    opacity: .9;
}


/*
   Simulasi gambaran thorax.
   Nanti bisa diganti dengan file foto radiologi asli.
*/

.xray-image::before {

    content: '';

    position: absolute;

    left: 23%;

    top: 10%;

    width: 54%;

    height: 80%;

    border-left:
        25px solid rgba(30,35,37,.48);

    border-right:
        25px solid rgba(30,35,37,.48);

    border-radius: 45%;
}


.xray-image::after {

    content: '';

    position: absolute;

    left: 43%;

    top: 13%;

    width: 14%;

    height: 72%;

    background: rgba(235,239,240,.55);

    border-radius: 50%;
}


.viewer-label {

    position: absolute;

    top: 10px;
    left: 12px;

    color: white;

    font-size: 9px;

    background: rgba(0,0,0,.45);

    padding: 5px 7px;

    border-radius: 4px;
}


.viewer-controls {

    position: absolute;

    bottom: 10px;

    left: 50%;

    transform: translateX(-50%);

    display: flex;

    gap: 5px;
}


.viewer-control {

    width: 29px;
    height: 27px;

    border: 1px solid #637077;

    border-radius: 4px;

    background: rgba(0,0,0,.55);

    color: white;

    cursor: pointer;

    font-size: 11px;
}


.viewer-control:hover {

    background: rgba(255,255,255,.15);
}


/* =========================================================
   RESULT TEXT
========================================================= */

.result-section {

    margin-bottom: 13px;
}


.result-section:last-child {

    margin-bottom: 0;
}


.result-label {

    font-size: 10px;

    font-weight: 700;

    color: #315E6E;

    margin-bottom: 6px;
}


.result-text {

    min-height: 65px;

    background: #f5f9fb;

    border: 1px solid #dce8ed;

    border-radius: 5px;

    padding: 9px;

    color: #4b6877;

    font-size: 9px;

    line-height: 1.55;
}


.result-text.impression {

    background: #edf8f5;

    border-color: #cce8dc;

    color: #267052;

    font-weight: 600;
}


.result-text.recommendation {

    min-height: 50px;
}


/* =========================================================
   RADIOLOG INFO
========================================================= */

.radiologist-box {

    background: #f3f9fc;

    border-radius: 5px;

    padding: 11px;

}


.radiologist-name {

    font-size: 10px;

    font-weight: 700;

    color: #315E6E;
}


.radiologist-role {

    font-size: 8px;

    color: #7a929d;

    margin-top: 3px;
}


/* =========================================================
   ACTION
========================================================= */

.action-row {

    display: flex;

    justify-content: flex-end;

    gap: 9px;

    margin-top: 2px;
}


.btn {

    height: 38px;

    padding: 0 15px;

    border-radius: 5px;

    font-size: 10px;

    font-weight: 700;

    cursor: pointer;
}


.btn-back {

    background: white;

    border: 1px solid #bdd4dc;

    color: #397083;
}


.btn-report {

    background: #0D6E6E;

    border: 1px solid #0D6E6E;

    color: white;
}


.btn-report:hover {

    background: #066d75;
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 1050px) {

    .result-grid {

        grid-template-columns: 1fr;

    }

}


@media (max-width: 800px) {

    .patient-grid {

        grid-template-columns: 1fr;

    }

    .exam-info {

        grid-template-columns:
            1fr 1fr;

    }

}


@media (max-width: 700px) {

    .sidebar {

        position: relative;

        width: 100%;

    }

    .main-content {

        margin-left: 0;

        padding: 0 15px 25px;

    }

}

</style>


<!-- SIMRS: tema seragam portal (font, warna, ukuran) -->
<script>document.documentElement.dataset.simrsMod="claudia";if(window.top!==window.self)document.documentElement.classList.add("embedded-simrs-module");</script>
<link rel="stylesheet" id="simrs-module-theme" href="../../assets/module-theme.css">
</head>


<body>


<!-- =========================================================
     SIDEBAR
========================================================= -->

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


        <a href="dashboard_radiolog.php">

            <span class="menu-icon">
                ⌂
            </span>

            <span>
                Dashboard Radiolog
            </span>

        </a>


        <a href="order_pasien_radiolog.php">

            <span class="menu-icon">
                ♟+
            </span>

            <span>
                Order Pasien
            </span>

        </a>


        <a
            href="daftar_hasil_radiolog.php"
            class="active"
        >

            <span class="menu-icon">
                ▣
            </span>

            <span>
                Daftar Hasil
            </span>

        </a>


    </nav>


    <div class="sidebar-footer">

        <div class="heart">
            ♥
        </div>

        <span>
            Melayani dengan<br>
            Sepenuh Hati
        </span>

    </div>


</aside>



<!-- =========================================================
     MAIN CONTENT
========================================================= -->

<main class="main-content">


    <!-- TOPBAR -->

    <header class="topbar">


        <div class="search-top">

            <span>
                🔎
            </span>

            <input
                type="text"
                placeholder="Cari menu..."
            >

        </div>


        <div class="top-user">


            <div class="user-circle">
                ♟
            </div>


            <div>

                <div class="user-name">
                    <?= htmlspecialchars($userName) ?>
                </div>

                <div class="user-role">
                    <?= htmlspecialchars($userRoleLabel) ?>
                </div>

            </div>


            <a
                href="logout.php"
                class="logout"
            >
                Logout
            </a>


        </div>


    </header>



    <!-- BREADCRUMB -->

    <div class="breadcrumb">

        <span>
            ⌂
        </span>

        <span>
            &gt;
        </span>

        <a href="dashboard_radiolog.php">
            Radiology
        </a>

        <span>
            &gt;
        </span>

        <a href="daftar_hasil_radiolog.php">
            Daftar Hasil
        </a>

        <span>
            &gt;
        </span>

        <span class="current">
            Hasil Pemeriksaan
        </span>

    </div>



    <!-- TITLE -->

    <h1 class="page-title">
        Hasil Pemeriksaan Radiologi
    </h1>


    <p class="page-subtitle">
        Hasil interpretasi pemeriksaan radiologi pasien.
    </p>


    <a
        href="daftar_hasil_radiolog.php"
        class="back-link"
    >
        ← Kembali ke Daftar Hasil
    </a>



    <!-- =====================================================
         PATIENT CARD
    ====================================================== -->

    <section class="patient-card">


        <div class="patient-header">


            <div class="patient-title">

                <div class="patient-icon">
                    ♟
                </div>

                <h2>
                    Data Pemeriksaan
                </h2>

            </div>


            <div class="status-badge">
                ✓ <?= htmlspecialchars($data['status']) ?>
            </div>


        </div>



        <div class="patient-grid">


            <div>

                <span class="info-label">
                    Nama Pasien
                </span>

                <div class="info-value">
                    <?= htmlspecialchars($data['nama']) ?>
                </div>


                <span class="info-label">
                    No. RM
                </span>

                <div class="info-value">
                    <?= htmlspecialchars($data['no_rm']) ?>
                </div>


                <span class="info-label">
                    Tanggal Lahir
                </span>

                <div class="info-value">
                    <?= htmlspecialchars($data['tanggal_lahir']) ?>
                    (<?= htmlspecialchars($data['umur']) ?>)
                </div>

            </div>


            <div>

                <span class="info-label">
                    Jenis Kelamin
                </span>

                <div class="info-value">
                    <?= htmlspecialchars($data['jenis_kelamin']) ?>
                </div>


                <span class="info-label">
                    Alamat
                </span>

                <div class="info-value">
                    <?= htmlspecialchars($data['alamat']) ?>
                </div>


                <span class="info-label">
                    Dokter Pengirim
                </span>

                <div class="info-value">
                    <?= htmlspecialchars($data['dokter_pengirim']) ?>
                </div>

            </div>


            <div>

                <span class="info-label">
                    No. Order
                </span>

                <div class="info-value">
                    <?= htmlspecialchars($orderNumber) ?>
                </div>


                <span class="info-label">
                    Tanggal Pemeriksaan
                </span>

                <div class="info-value">
                    <?= htmlspecialchars($data['tanggal']) ?>
                </div>


                <span class="info-label">
                    Indikasi Klinis
                </span>

                <div class="info-value">
                    <?= htmlspecialchars($data['indikasi']) ?>
                </div>

            </div>


        </div>



        <div class="exam-info">


            <div class="exam-box">

                <div class="label">
                    Jenis Pemeriksaan
                </div>

                <div class="value">
                    <?= htmlspecialchars($data['pemeriksaan']) ?>
                </div>

            </div>


            <div class="exam-box">

                <div class="label">
                    Modalitas
                </div>

                <div class="value">
                    <?= htmlspecialchars($data['modalitas']) ?>
                </div>

            </div>


            <div class="exam-box">

                <div class="label">
                    Tanggal Pemeriksaan
                </div>

                <div class="value">
                    <?= htmlspecialchars($data['tanggal']) ?>
                </div>

            </div>


            <div class="exam-box">

                <div class="label">
                    Status
                </div>

                <div class="value">
                    <?= htmlspecialchars($data['status']) ?>
                </div>

            </div>


        </div>


    </section>



    <!-- =====================================================
         RESULT GRID
    ====================================================== -->

    <section class="result-grid">


        <!-- =================================================
             LEFT COLUMN
        ================================================== -->

        <div>


            <!-- RADIOLOGY VIEWER -->

            <div class="panel">


                <div class="panel-header">

                    <div class="panel-icon">
                        ◉
                    </div>

                    <h2>
                        Foto Pemeriksaan Radiologi
                    </h2>

                </div>


                <div class="panel-body">


                    <div class="viewer">


                        <div class="viewer-label">
                            <?= htmlspecialchars($data['pemeriksaan']) ?>
                            • <?= htmlspecialchars($data['modalitas']) ?>
                        </div>


                        <div class="xray-image"></div>


                        <div class="viewer-controls">


                            <button
                                type="button"
                                class="viewer-control"
                                onclick="zoomIn()"
                            >
                                +
                            </button>


                            <button
                                type="button"
                                class="viewer-control"
                                onclick="zoomOut()"
                            >
                                −
                            </button>


                            <button
                                type="button"
                                class="viewer-control"
                                onclick="resetViewer()"
                            >
                                ↺
                            </button>


                            <button
                                type="button"
                                class="viewer-control"
                                onclick="fullscreenViewer()"
                            >
                                ⛶
                            </button>


                        </div>


                    </div>


                </div>

            </div>



            <!-- FINDINGS -->

            <div class="panel">


                <div class="panel-header">

                    <div class="panel-icon">
                        ▤
                    </div>

                    <h2>
                        Hasil Pemeriksaan
                    </h2>

                </div>


                <div class="panel-body">


                    <div class="result-section">


                        <div class="result-label">
                            Findings
                        </div>


                        <div class="result-text">

                            <?= htmlspecialchars(
                                $data['findings'] !== ''
                                ? $data['findings']
                                : 'Hasil interpretasi belum tersedia.'
                            ) ?>

                        </div>


                    </div>



                    <div class="result-section">


                        <div class="result-label">
                            Impression / Kesimpulan
                        </div>


                        <div class="result-text impression">

                            <?= htmlspecialchars(
                                $data['impression'] !== ''
                                ? $data['impression']
                                : 'Kesimpulan belum tersedia.'
                            ) ?>

                        </div>


                    </div>



                    <div class="result-section">


                        <div class="result-label">
                            Rekomendasi
                        </div>


                        <div class="result-text recommendation">

                            <?= htmlspecialchars(
                                $data['rekomendasi'] !== ''
                                ? $data['rekomendasi']
                                : 'Belum ada rekomendasi.'
                            ) ?>

                        </div>


                    </div>


                </div>

            </div>


        </div>



        <!-- =================================================
             RIGHT COLUMN
        ================================================== -->

        <div>


            <!-- DETAIL -->

            <div class="panel">


                <div class="panel-header">

                    <div class="panel-icon">
                        ▣
                    </div>

                    <h2>
                        Detail Pemeriksaan
                    </h2>

                </div>


                <div class="panel-body">


                    <div class="result-section">

                        <div class="result-label">
                            Indikasi Klinis
                        </div>

                        <div class="result-text">

                            <?= htmlspecialchars($data['indikasi']) ?>

                        </div>

                    </div>


                    <div class="result-section">

                        <div class="result-label">
                            Dokter Pengirim
                        </div>

                        <div class="result-text">

                            <?= htmlspecialchars($data['dokter_pengirim']) ?>

                        </div>

                    </div>


                    <div class="result-section">

                        <div class="result-label">
                            Modalitas
                        </div>

                        <div class="result-text">

                            <?= htmlspecialchars($data['modalitas']) ?>

                        </div>

                    </div>


                </div>

            </div>



            <!-- RADIOLOG -->

            <div class="panel">


                <div class="panel-header">

                    <div class="panel-icon">
                        ♟
                    </div>

                    <h2>
                        Interpretasi Radiolog
                    </h2>

                </div>


                <div class="panel-body">


                    <div class="radiologist-box">


                        <div class="radiologist-name">
                            <?= htmlspecialchars($userName) ?>
                        </div>


                        <div class="radiologist-role">
                            <?= htmlspecialchars($userRoleLabel) ?>
                        </div>


                    </div>


                </div>

            </div>



            <!-- INFO -->

            <div class="panel">


                <div class="panel-header">

                    <div class="panel-icon">
                        ✓
                    </div>

                    <h2>
                        Status Laporan
                    </h2>

                </div>


                <div class="panel-body">


                    <div class="status-badge">
                        ✓ Laporan Telah Selesai
                    </div>


                    <p
                        style="
                            font-size:9px;
                            color:#6f8995;
                            line-height:1.5;
                            margin-bottom:0;
                        "
                    >
                        Hasil pemeriksaan telah tersimpan
                        dan dapat dilihat melalui SIMRS / RME.
                    </p>


                </div>

            </div>


        </div>


    </section>



    <!-- =====================================================
         ACTION
    ====================================================== -->

    <div class="action-row">


        <a
            href="daftar_hasil_radiolog.php"
            class="btn btn-back"
            style="
                display:flex;
                align-items:center;
                text-decoration:none;
            "
        >
            ← Kembali
        </a>


        <button
            type="button"
            class="btn btn-report"
            onclick="cetakLaporan()"
        >
            🖨 Cetak Laporan
        </button>


    </div>


</main>



<script>

/* =========================================================
   VIEWER
========================================================= */

let zoomLevel = 1;


function zoomIn() {

    zoomLevel += 0.1;

    if (zoomLevel > 1.8) {
        zoomLevel = 1.8;
    }

    applyZoom();

}


function zoomOut() {

    zoomLevel -= 0.1;

    if (zoomLevel < 0.7) {
        zoomLevel = 0.7;
    }

    applyZoom();

}


function resetViewer() {

    zoomLevel = 1;

    applyZoom();

}


function applyZoom() {

    const image =
        document.querySelector('.xray-image');

    image.style.transform =
        'scale(' + zoomLevel + ')';

}


function fullscreenViewer() {

    const viewer =
        document.querySelector('.viewer');

    if (viewer.requestFullscreen) {

        viewer.requestFullscreen();

    }

}


/* =========================================================
   CETAK
========================================================= */

function cetakLaporan() {

    window.print();

}

</script>


</body>

</html>