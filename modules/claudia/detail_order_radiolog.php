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

/* Hanya Radiolog yang boleh mengakses halaman ini */
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
   AMBIL ORDER DARI URL
   Contoh:
   detail_order_radiolog.php?order=RAD20250903001
========================================================= */

$orderNumber = $_GET['order'] ?? 'RAD20250903001';


/* =========================================================
   DATA ORDER DEMO
========================================================= */

$orders = [

    'RAD20250903001' => [
        'no_rm' => '2025001234',
        'nama' => 'Budi Santoso',
        'tanggal_lahir' => '12-05-1980',
        'umur' => '45 th',
        'jenis_kelamin' => 'Laki-laki',
        'alamat' => 'Jl. Melati No. 10, Tangerang',
        'no_hp' => '0812-3456-7890',

        'poliklinik' => 'Poliklinik Umum',
        'dokter' => 'dr. Andi Pratama, Sp.PD',
        'tanggal_order' => '03-09-2025 08:15',
        'jenis_pasien' => 'Rawat Jalan',

        'pemeriksaan' => 'Thorax PA',
        'indikasi' => 'Batuk kronis',
        'prioritas' => 'Rutin',
        'catatan_dokter' => 'Evaluasi paru. R/O TB.'
    ],

    'RAD20250903002' => [
        'no_rm' => '2025001235',
        'nama' => 'Siti Aminah',
        'tanggal_lahir' => '21-08-1987',
        'umur' => '38 th',
        'jenis_kelamin' => 'Perempuan',
        'alamat' => 'Jl. Mawar No. 15, Tangerang',
        'no_hp' => '0813-4567-8901',

        'poliklinik' => 'Poliklinik Penyakit Dalam',
        'dokter' => 'dr. Budi Santoso, Sp.PD',
        'tanggal_order' => '03-09-2025 08:45',
        'jenis_pasien' => 'Rawat Jalan',

        'pemeriksaan' => 'Abdomen AP',
        'indikasi' => 'Nyeri abdomen',
        'prioritas' => 'Rutin',
        'catatan_dokter' => 'Evaluasi abdomen.'
    ],

    'RAD20250903003' => [
        'no_rm' => '2025001236',
        'nama' => 'Joko Widodo',
        'tanggal_lahir' => '15-04-1973',
        'umur' => '52 th',
        'jenis_kelamin' => 'Laki-laki',
        'alamat' => 'Jl. Kenanga No. 8, Tangerang',
        'no_hp' => '0814-5678-9012',

        'poliklinik' => 'Poliklinik Saraf',
        'dokter' => 'dr. Rina Wijaya, Sp.N',
        'tanggal_order' => '03-09-2025 09:10',
        'jenis_pasien' => 'Rawat Jalan',

        'pemeriksaan' => 'CT Head',
        'indikasi' => 'Nyeri kepala',
        'prioritas' => 'Cito',
        'catatan_dokter' => 'Evaluasi kelainan intrakranial.'
    ],

    'RAD20250903004' => [
        'no_rm' => '2025001237',
        'nama' => 'Maria Lestari',
        'tanggal_lahir' => '03-11-1983',
        'umur' => '41 th',
        'jenis_kelamin' => 'Perempuan',
        'alamat' => 'Jl. Anggrek No. 20, Tangerang',
        'no_hp' => '0815-6789-0123',

        'poliklinik' => 'Poliklinik Umum',
        'dokter' => 'dr. Andi Pratama, Sp.PD',
        'tanggal_order' => '03-09-2025 09:30',
        'jenis_pasien' => 'Rawat Jalan',

        'pemeriksaan' => 'USG Abdomen',
        'indikasi' => 'Nyeri perut',
        'prioritas' => 'Rutin',
        'catatan_dokter' => 'Evaluasi organ abdomen.'
    ],

    'RAD20250903005' => [
        'no_rm' => '2025001238',
        'nama' => 'Agus Setiawan',
        'tanggal_lahir' => '11-01-1978',
        'umur' => '47 th',
        'jenis_kelamin' => 'Laki-laki',
        'alamat' => 'Jl. Melati No. 25, Tangerang',
        'no_hp' => '0816-7890-1234',

        'poliklinik' => 'Poliklinik Ortopedi',
        'dokter' => 'dr. Maya Putri, Sp.OT',
        'tanggal_order' => '03-09-2025 09:50',
        'jenis_pasien' => 'Rawat Jalan',

        'pemeriksaan' => 'Lumbal AP/Lateral',
        'indikasi' => 'Nyeri pinggang',
        'prioritas' => 'Rutin',
        'catatan_dokter' => 'Evaluasi tulang belakang lumbal.'
    ]

];


/* =========================================================
   CEK ORDER
========================================================= */

if (!isset($orders[$orderNumber])) {
    $orderNumber = 'RAD20250903001';
}

$order = $orders[$orderNumber];

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>
Detail Order Pemeriksaan - RS Suka Sehat
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


/* =========================================================
   SIDEBAR BRAND
========================================================= */

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

    transition: 0.2s;

}


.sidebar-menu a:hover {

    background: rgba(255,255,255,0.10);

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
   MAIN CONTENT
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

    box-shadow: 0 2px 7px rgba(30,70,90,0.04);

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

    color: #426372;

}


.search-top input::placeholder {

    color: #839ba6;

}


/* =========================================================
   TOPBAR USER
========================================================= */

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

    font-size: 14px;

}


.user-info {

    min-width: 95px;

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


.logout:hover {

    text-decoration: underline;

}


/* =========================================================
   BREADCRUMB
========================================================= */

.breadcrumb {

    display: flex;

    align-items: center;

    gap: 8px;

    margin-top: 20px;

    margin-bottom: 5px;

    font-size: 13px;

}


.breadcrumb-icon {

    font-size: 22px;

    color: #111;

}


.breadcrumb a {

    color: #1D5FA8;

    text-decoration: none;

}


.breadcrumb a:hover {

    text-decoration: underline;

}


.breadcrumb .separator {

    color: #47748d;

}


.breadcrumb .current {

    color: #1D5FA8;

}


/* =========================================================
   PAGE TITLE
========================================================= */

.page-title {

    margin: 4px 0 0;

    color: #064963;

    font-size: 29px;

    font-weight: 700;

}


.back-link {

    display: inline-block;

    margin-top: 8px;

    color: #1769d1;

    text-decoration: none;

    font-size: 13px;

}


.back-link:hover {

    text-decoration: underline;

}


/* =========================================================
   DETAIL GRID
========================================================= */

.detail-grid {

    display: grid;

    grid-template-columns: 1.55fr 1fr;

    gap: 15px;

    margin-top: 16px;

}


.left-column,
.right-column {

    display: flex;

    flex-direction: column;

    gap: 15px;

}


/* =========================================================
   PANEL
========================================================= */

.panel {

    background: white;

    border: 1px solid #D6E8EE;

    border-radius: 7px;

    overflow: hidden;

}


.panel-header {

    display: flex;

    align-items: center;

    gap: 9px;

    padding: 10px 12px;

    background: #f8fcfd;

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

    font-size: 14px;

}


.panel-header h2 {

    margin: 0;

    font-size: 13px;

    color: #0A4D68;

}


.panel-body {

    padding: 11px 12px 13px;

}


/* =========================================================
   DATA PASIEN
========================================================= */

.patient-grid {

    display: grid;

    grid-template-columns: 1.25fr 1fr;

    gap: 14px;

}


.patient-main {

    display: flex;

    gap: 11px;

}


.patient-avatar {

    width: 53px;
    height: 53px;

    flex-shrink: 0;

    border-radius: 50%;

    background: #d9f0f2;

    display: flex;

    align-items: center;
    justify-content: center;

    color: #0D6E6E;

    font-size: 27px;

}


.patient-name {

    color: #0A4D68;

    font-size: 13px;

    font-weight: 700;

    margin-bottom: 7px;

}


.info-row {

    display: flex;

    margin-bottom: 5px;

    font-size: 9px;

}


.info-label {

    width: 78px;

    flex-shrink: 0;

    color: #64808e;

}


.info-value {

    color: #385b6c;

}


.patient-side {

    background: #f5faff;

    padding: 9px;

    border-radius: 5px;

}


.side-row {

    display: flex;

    gap: 8px;

    margin-bottom: 8px;

}


.side-row:last-child {

    margin-bottom: 0;

}


.side-icon {

    width: 17px;

    color: #0D6E6E;

    font-size: 14px;

}


.side-label {

    color: #708a96;

    font-size: 8px;

}


.side-value {

    color: #385b6c;

    font-size: 9px;

    font-weight: 600;

    margin-top: 2px;

}


.patient-type {

    display: inline-block;

    padding: 3px 7px;

    border-radius: 4px;

    background: #dff5ea;

    color: #18845b;

    font-size: 8px;

    font-weight: 600;

}


/* =========================================================
   STATUS VERIFIKASI
========================================================= */

.status-item {

    display: flex;

    align-items: center;

    justify-content: space-between;

    padding: 8px 10px;

    margin-bottom: 6px;

    border-radius: 4px;

    background: #f4f9fc;

}


.status-item:last-child {

    margin-bottom: 0;

}


.status-label {

    color: #456475;

    font-size: 10px;

}


.status-ok {

    display: inline-flex;

    align-items: center;

    gap: 5px;

    padding: 5px 10px;

    border-radius: 5px;

    background: #dff5ea;

    color: #16885d;

    font-size: 8px;

    font-weight: 700;

}


/* =========================================================
   INFORMASI ORDER
========================================================= */

.order-table {

    width: 100%;

    border-collapse: collapse;

}


.order-table tr:nth-child(odd) {

    background: #f0f7fc;

}


.order-table td {

    padding: 6px 8px;

    font-size: 9px;

}


.order-table td:first-child {

    width: 110px;

    color: #607b89;

}


.order-table td:last-child {

    color: #315E6E;

    font-weight: 500;

}


/* =========================================================
   CATATAN RADIOLOG
========================================================= */

.note-textarea {

    width: 100%;

    height: 90px;

    resize: none;

    border: 1px solid #d2e1e7;

    border-radius: 6px;

    padding: 11px;

    font-family: Arial, Helvetica, sans-serif;

    font-size: 10px;

    color: #456475;

    outline: none;

}


.note-textarea:focus {

    border-color: #0D6E6E;

}


.note-textarea::placeholder {

    color: #8aa0aa;

}


.note-count {

    text-align: right;

    margin-top: 3px;

    color: #7c929c;

    font-size: 9px;

}


/* =========================================================
   DOKUMEN
========================================================= */

.document-table {

    width: 100%;

    border-collapse: collapse;

}


.document-table th {

    padding: 7px;

    background: #e7f2fb;

    color: #536f82;

    text-align: left;

    font-size: 8px;

}


.document-table td {

    padding: 7px;

    border-bottom: 1px solid #e0eaee;

    color: #456475;

    font-size: 8px;

}


.document-table tr:last-child td {

    border-bottom: none;

}


.document-button {

    width: 22px;
    height: 20px;

    border: 1px solid #c9dde4;

    border-radius: 4px;

    background: white;

    color: #0D6E6E;

    cursor: pointer;

}


.document-button:hover {

    background: #EAF6F8;

}


/* =========================================================
   INFO BOX
========================================================= */

.info-box {

    display: flex;

    align-items: flex-start;

    gap: 9px;

    padding: 12px;

    background: #dceeff;

    border-radius: 6px;

    color: #2872b5;

    font-size: 10px;

    line-height: 1.5;

}


.info-icon {

    font-size: 18px;

    font-weight: 700;

}


/* =========================================================
   BUTTONS
========================================================= */

.button-row {

    display: grid;

    grid-template-columns: 1fr 1.3fr;

    gap: 9px;

}


.action-button {

    height: 41px;

    border-radius: 6px;

    font-size: 11px;

    font-weight: 700;

    cursor: pointer;

}


.reject-button {

    background: white;

    border: 1px solid #ef7770;

    color: #df554e;

}


.reject-button:hover {

    background: #fff5f4;

}


.confirm-button {

    background: #0D6E6E;

    border: 1px solid #0D6E6E;

    color: white;

}


.confirm-button:hover {

    background: #066d75;

}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 1050px) {

    .detail-grid {

        grid-template-columns: 1fr;

    }

}


@media (max-width: 750px) {

    .sidebar {

        position: relative;

        width: 100%;

    }

    .main-content {

        margin-left: 0;

        padding: 0 15px 25px;

    }

    .patient-grid {

        grid-template-columns: 1fr;

    }

    .topbar {

        margin-top: 10px;

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


    <!-- LOGO -->

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


    <!-- MENU -->

    <nav class="sidebar-menu">


        <a href="dashboard_radiolog.php">

            <span class="menu-icon">
                🏠︎
            </span>

            <span>
                Dashboard Radiolog
            </span>

        </a>


        <a
            href="order_pasien_radiolog.php"
            class="active"
        >

            <span class="menu-icon">
                ♟+
            </span>

            <span>
                Order Pasien
            </span>

        </a>


        <a href="daftar_hasil_radiolog.php">

            <span class="menu-icon">
                ☢️
            </span>

            <span>
                Daftar Hasil
            </span>

        </a>


    </nav>


    <!-- FOOTER -->

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


    <!-- =====================================================
         TOPBAR
    ====================================================== -->

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


            <div class="user-info">

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



    <!-- =====================================================
         BREADCRUMB
    ====================================================== -->

    <div class="breadcrumb">

        <span class="breadcrumb-icon">
            📷
        </span>

        <a href="dashboard_radiolog.php">
            Radiologi
        </a>

        <span class="separator">
            &gt;
        </span>

        <span class="current">
            Detail Order
        </span>

    </div>



    <!-- =====================================================
         PAGE TITLE
    ====================================================== -->

    <h1 class="page-title">
        Detail Order Pemeriksaan
    </h1>


    <a
        href="order_pasien_radiolog.php"
        class="back-link"
    >
        ← Kembali
    </a>



    <!-- =====================================================
         DETAIL CONTENT
    ====================================================== -->

    <section class="detail-grid">


        <!-- =================================================
             LEFT COLUMN
        ================================================== -->

        <div class="left-column">


            <!-- DATA PASIEN -->

            <div class="panel">


                <div class="panel-header">

                    <div class="panel-icon">
                        ♟
                    </div>

                    <h2>
                        Data Pasien
                    </h2>

                </div>


                <div class="panel-body">


                    <div class="patient-grid">


                        <div class="patient-main">


                            <div class="patient-avatar">
                                ♟
                            </div>


                            <div>


                                <div class="patient-name">
                                    <?= htmlspecialchars($order['nama']) ?>
                                </div>


                                <div class="info-row">

                                    <span class="info-label">
                                        No. RM
                                    </span>

                                    <span class="info-value">
                                        :
                                        <?= htmlspecialchars($order['no_rm']) ?>
                                    </span>

                                </div>


                                <div class="info-row">

                                    <span class="info-label">
                                        Tanggal Lahir
                                    </span>

                                    <span class="info-value">
                                        :
                                        <?= htmlspecialchars($order['tanggal_lahir']) ?>
                                        (<?= htmlspecialchars($order['umur']) ?>)
                                    </span>

                                </div>


                                <div class="info-row">

                                    <span class="info-label">
                                        Jenis Kelamin
                                    </span>

                                    <span class="info-value">
                                        :
                                        <?= htmlspecialchars($order['jenis_kelamin']) ?>
                                    </span>

                                </div>


                                <div class="info-row">

                                    <span class="info-label">
                                        Alamat
                                    </span>

                                    <span class="info-value">
                                        :
                                        <?= htmlspecialchars($order['alamat']) ?>
                                    </span>

                                </div>


                                <div class="info-row">

                                    <span class="info-label">
                                        No. HP
                                    </span>

                                    <span class="info-value">
                                        :
                                        <?= htmlspecialchars($order['no_hp']) ?>
                                    </span>

                                </div>


                            </div>


                        </div>


                        <div class="patient-side">


                            <div class="side-row">

                                <div class="side-icon">
                                    ♙
                                </div>

                                <div>

                                    <div class="side-label">
                                        Poliklinik Asal
                                    </div>

                                    <div class="side-value">
                                        <?= htmlspecialchars($order['poliklinik']) ?>
                                    </div>

                                </div>

                            </div>


                            <div class="side-row">

                                <div class="side-icon">
                                    ♙
                                </div>

                                <div>

                                    <div class="side-label">
                                        Dokter Peminta
                                    </div>

                                    <div class="side-value">
                                        <?= htmlspecialchars($order['dokter']) ?>
                                    </div>

                                </div>

                            </div>


                            <div class="side-row">

                                <div class="side-icon">
                                    ▣
                                </div>

                                <div>

                                    <div class="side-label">
                                        Tanggal Order
                                    </div>

                                    <div class="side-value">
                                        <?= htmlspecialchars($order['tanggal_order']) ?>
                                    </div>

                                </div>

                            </div>


                            <div class="side-row">

                                <div class="side-icon">
                                    ♟
                                </div>

                                <div>

                                    <div class="side-label">
                                        Jenis Pasien
                                    </div>

                                    <div class="patient-type">
                                        <?= htmlspecialchars($order['jenis_pasien']) ?>
                                    </div>

                                </div>

                            </div>


                        </div>


                    </div>


                </div>

            </div>



            <!-- INFORMASI ORDER -->

            <div class="panel">


                <div class="panel-header">

                    <div class="panel-icon">
                        ▤
                    </div>

                    <h2>
                        Informasi Order
                    </h2>

                </div>


                <div class="panel-body">


                    <table class="order-table">


                        <tr>

                            <td>
                                No. Order
                            </td>

                            <td>
                                <?= htmlspecialchars($orderNumber) ?>
                            </td>

                        </tr>


                        <tr>

                            <td>
                                Jenis Pemeriksaan
                            </td>

                            <td>
                                <?= htmlspecialchars($order['pemeriksaan']) ?>
                            </td>

                        </tr>


                        <tr>

                            <td>
                                Indikasi Klinis
                            </td>

                            <td>
                                <?= htmlspecialchars($order['indikasi']) ?>
                            </td>

                        </tr>


                        <tr>

                            <td>
                                Prioritas
                            </td>

                            <td>
                                <?= htmlspecialchars($order['prioritas']) ?>
                            </td>

                        </tr>


                        <tr>

                            <td>
                                Catatan Dokter
                            </td>

                            <td>
                                <?= htmlspecialchars($order['catatan_dokter']) ?>
                            </td>

                        </tr>


                    </table>


                </div>

            </div>



            <!-- DOKUMEN PENDUKUNG -->

            <div class="panel">


                <div class="panel-header">

                    <div class="panel-icon">
                        📎
                    </div>

                    <h2>
                        Dokumen Pendukung
                    </h2>

                </div>


                <div class="panel-body">


                    <table class="document-table">


                        <thead>

                            <tr>

                                <th>
                                    No.
                                </th>

                                <th>
                                    Nama Dokumen
                                </th>

                                <th>
                                    Tanggal Upload
                                </th>

                                <th>
                                    Diunggah oleh
                                </th>

                                <th>
                                    Aksi
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                            <tr>

                                <td>
                                    1
                                </td>

                                <td>
                                    Surat Pengantar
                                </td>

                                <td>
                                    03-09-2025 08:16
                                </td>

                                <td>
                                    <?= htmlspecialchars($order['dokter']) ?>
                                </td>

                                <td>

                                    <button
                                        type="button"
                                        class="document-button"
                                        onclick="lihatDokumen('Surat Pengantar')"
                                    >
                                        ◉
                                    </button>

                                </td>

                            </tr>


                            <tr>

                                <td>
                                    2
                                </td>

                                <td>
                                    Form Persetujuan
                                </td>

                                <td>
                                    03-09-2025 08:16
                                </td>

                                <td>
                                    <?= htmlspecialchars($order['dokter']) ?>
                                </td>

                                <td>

                                    <button
                                        type="button"
                                        class="document-button"
                                        onclick="lihatDokumen('Form Persetujuan')"
                                    >
                                        ◉
                                    </button>

                                </td>

                            </tr>


                        </tbody>


                    </table>


                </div>

            </div>


        </div>



        <!-- =================================================
             RIGHT COLUMN
        ================================================== -->

        <div class="right-column">


            <!-- STATUS VERIFIKASI -->

            <div class="panel">


                <div class="panel-header">

                    <div class="panel-icon">
                        ✓
                    </div>

                    <h2>
                        Status Verifikasi
                    </h2>

                </div>


                <div class="panel-body">


                    <div class="status-item">

                        <span class="status-label">
                            Identitas Pasien
                        </span>

                        <span class="status-ok">
                            ✓ &nbsp; Sesuai
                        </span>

                    </div>


                    <div class="status-item">

                        <span class="status-label">
                            Order Pemeriksaan
                        </span>

                        <span class="status-ok">
                            ✓ &nbsp; Valid
                        </span>

                    </div>


                    <div class="status-item">

                        <span class="status-label">
                            Persyaratan
                        </span>

                        <span class="status-ok">
                            ✓ &nbsp; Lengkap
                        </span>

                    </div>


                </div>

            </div>



            <!-- CATATAN RADIOLOG -->

            <div class="panel">


                <div class="panel-header">

                    <div class="panel-icon">
                        ▤
                    </div>

                    <h2>
                        Catatan Radiolog
                    </h2>

                </div>


                <div class="panel-body">


                    <textarea
                        id="catatanRadiolog"
                        class="note-textarea"
                        maxlength="500"
                        placeholder="Tambahkan catatan (opsional)..."
                        oninput="updateCounter()"
                    ></textarea>


                    <div
                        class="note-count"
                        id="noteCounter"
                    >
                        0/500
                    </div>


                </div>

            </div>



            <!-- INFO -->

            <div class="info-box">

                <div class="info-icon">
                    ℹ
                </div>

                <div>
                    Pastikan identitas pasien dan order sudah sesuai
                    sebelum melanjutkan ke pemeriksaan.
                </div>

            </div>



            <!-- BUTTON -->

            <div class="button-row">


                <button
                    type="button"
                    class="action-button reject-button"
                    onclick="tolakOrder()"
                >
                    ✕ &nbsp; Tolak Order
                </button>


                <button
                    type="button"
                    class="action-button confirm-button"
                    onclick="konfirmasiOrder()"
                >
                    ✓ &nbsp; Konfirmasi Order
                </button>


            </div>


        </div>


    </section>


</main>



<script>

/* =========================================================
   COUNTER CATATAN
========================================================= */

function updateCounter() {

    const textarea =
        document.getElementById('catatanRadiolog');

    const counter =
        document.getElementById('noteCounter');

    counter.textContent =
        textarea.value.length + '/500';

}


/* =========================================================
   LIHAT DOKUMEN
========================================================= */

function lihatDokumen(namaDokumen) {

    alert(
        'Membuka dokumen:\n\n' +
        namaDokumen
    );

}


/* =========================================================
   TOLAK ORDER
========================================================= */

function tolakOrder() {

    const alasan = prompt(
        'Masukkan alasan penolakan order:'
    );


    if (alasan === null) {
        return;
    }


    if (alasan.trim() === '') {

        alert(
            'Alasan penolakan harus diisi.'
        );

        return;
    }


    alert(
        'Order <?= htmlspecialchars($orderNumber, ENT_QUOTES) ?> berhasil ditolak.\n\n' +
        'Alasan: ' + alasan
    );

}


/* =========================================================
   KONFIRMASI ORDER
========================================================= */

function konfirmasiOrder() {

    const yakin = confirm(
        'Apakah Anda yakin ingin mengonfirmasi order ' +
        '<?= htmlspecialchars($orderNumber, ENT_QUOTES) ?>?'
    );


    if (!yakin) {
        return;
    }


    alert(
        'Order <?= htmlspecialchars($orderNumber, ENT_QUOTES) ?> berhasil dikonfirmasi.'
    );

}

</script>


</body>

</html>