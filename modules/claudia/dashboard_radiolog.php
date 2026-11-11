<?php
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];

if (($user['role'] ?? '') !== 'radiologist') {
    header('Location: dashboard.php');
    exit;
}

$userName = 'Rini Sutijah';
$userRoleLabel = 'Radiolog';
?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Dashboard Radiolog - RS Suka Sehat</title>


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

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    background: #F3FAFC;

    color: #0A4D68;

    font-size: 12px;
}


/* =========================================================
   APP
   ========================================================= */

.app {

    min-height: 100vh;

}


/* =========================================================
   SIDEBAR
   ========================================================= */

.sidebar {

    position: fixed;

    left: 0;
    top: 0;
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

    text-decoration: none;

    color: white;

    padding: 12px 14px;

    margin-bottom: 5px;

    border-radius: 9px;

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
   MAIN
   ========================================================= */

.main-content {

    margin-left: 230px;

    min-height: 100vh;

    padding: 0 30px 30px;

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


/* SEARCH */

.search-top {

    height: 38px;

    flex: 1;

    max-width: 100%;

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


/* USER */

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
   PAGE HEADER
   ========================================================= */

.page-header {

    padding-top: 28px;

}


.page-title {

    margin: 0;

    color: #0A4D68;

    font-size: 28px;

    font-weight: 700;

}


.page-subtitle {

    margin: 6px 0 0;

    color: #6d8997;

    font-size: 12px;

}


/* =========================================================
   DATE
   ========================================================= */

.page-date {

    float: right;

    margin-top: -35px;

    text-align: right;

    color: #75909c;

    font-size: 9px;

}


.page-date strong {

    display: block;

    color: #0A4D68;

    font-size: 13px;

    margin-top: 2px;

}


/* =========================================================
   WELCOME PANEL
   ========================================================= */

.welcome-panel {

    margin-top: 18px;

    padding: 18px 20px;

    background: #EAF6F8;

    border: 1px solid #cfe7ed;

    border-radius: 9px;

    display: flex;

    align-items: center;

    justify-content: space-between;

}


.welcome-title {

    font-size: 17px;

    font-weight: 700;

    color: #0A4D68;

}


.welcome-text {

    margin-top: 5px;

    color: #658392;

    font-size: 11px;

}


.welcome-icon {

    width: 46px;
    height: 46px;

    border-radius: 50%;

    background: #d6f1f3;

    display: flex;

    align-items: center;
    justify-content: center;

    color: #F36A9A;

    font-size: 21px;

}


/* =========================================================
   STAT CARDS
   ========================================================= */

.stats {

    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 14px;

    margin-top: 16px;

}


.stat-card {

    background: white;

    border: 1px solid #D6E8EE;

    border-radius: 9px;

    padding: 17px;

    min-height: 118px;

}


.stat-top {

    display: flex;

    align-items: center;

    justify-content: space-between;

}


.stat-icon {

    width: 38px;
    height: 38px;

    border-radius: 8px;

    display: flex;

    align-items: center;
    justify-content: center;

    font-size: 17px;

}


.blue {

    background: #e4f1ff;

    color: #257bc0;

}


.green {

    background: #def5ec;

    color: #15966c;

}


.orange {

    background: #fff0dc;

    color: #ed8a16;

}


.purple {

    background: #eee6ff;

    color: #7650ce;

}


.stat-title {

    font-size: 11px;

    color: #547080;

    font-weight: 700;

}


.stat-number {

    margin-top: 9px;

    font-size: 25px;

    font-weight: 700;

    color: #0A4D68;

}


.stat-desc {

    margin-top: 4px;

    font-size: 9px;

    color: #78909c;

}


.stat-link {

    display: block;

    margin-top: 9px;

    text-decoration: none;

    color: #0D6E6E;

    font-size: 9px;

    font-weight: 700;

}


/* =========================================================
   TABLE PANEL
   ========================================================= */

.panel {

    background: white;

    border: 1px solid #D6E8EE;

    border-radius: 9px;

    margin-top: 16px;

    overflow: hidden;

}


.panel-header {

    height: 52px;

    padding: 0 14px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    border-bottom: 1px solid #dce9ed;

}


.panel-title {

    display: flex;

    align-items: center;

    gap: 9px;

}


.panel-icon {

    width: 27px;
    height: 27px;

    border-radius: 5px;

    background: #0D6E6E;

    color: white;

    display: flex;

    align-items: center;
    justify-content: center;

    font-size: 13px;

}


.panel-title h2 {

    margin: 0;

    font-size: 13px;

    color: #0A4D68;

}


.order-button {

    background: #0D6E6E;

    color: white;

    border: none;

    border-radius: 5px;

    padding: 8px 12px;

    font-size: 10px;

    font-weight: 600;

    text-decoration: none;

}


.order-button:hover {

    background: #066d75;

}


/* =========================================================
   TABLE
   ========================================================= */

.table-wrapper {

    padding: 0 10px 10px;

    overflow-x: auto;

}


table {

    width: 100%;

    border-collapse: collapse;

}


thead th {

    background: #e8f2fb;

    color: #536f82;

    font-size: 9px;

    text-align: left;

    padding: 8px;

    font-weight: 700;

    white-space: nowrap;

}


tbody td {

    padding: 9px 8px;

    border-bottom: 1px solid #e3ebef;

    font-size: 9px;

    color: #456475;

}


tbody tr:last-child td {

    border-bottom: none;

}


tbody tr:hover {

    background: #f8fbfc;

}


.order-number {

    color: #0D6E6E;

    font-weight: 700;

}


.patient-name {

    color: #315E6E;

    font-weight: 600;

}


.patient-detail {

    display: block;

    color: #8498a2;

    font-size: 8px;

    margin-top: 2px;

}


/* =========================================================
   STATUS
   ========================================================= */

.status {

    display: inline-block;

    padding: 4px 7px;

    border-radius: 4px;

    font-size: 8px;

    font-weight: 600;

}


.status-waiting {

    background: #fff0dc;

    color: #ed8616;

}


.status-done {

    background: #dff5ea;

    color: #18845b;

}


.status-draft {

    background: #eee5ff;

    color: #7651ce;

}


/* =========================================================
   ACTION
   ========================================================= */

.action-button {

    background: white;

    border: 1px solid #d5e3e8;

    border-radius: 4px;

    padding: 4px 9px;

    color: #0D6E6E;

    font-size: 8px;

    cursor: pointer;

}


.action-button:hover {

    background: #EAF6F8;

}


/* =========================================================
   FOOTER TABLE
   ========================================================= */

.table-footer {

    display: flex;

    justify-content: space-between;

    padding: 5px 14px 12px;

    color: #78919d;

    font-size: 9px;

}


.table-footer a {

    color: #0D6E6E;

    text-decoration: none;

    font-weight: 700;

}


/* =========================================================
   RESPONSIVE
   ========================================================= */

@media(max-width: 1000px) {

    .sidebar {

        width: 200px;

    }

    .main-content {

        margin-left: 200px;

        padding-left: 18px;

        padding-right: 18px;

    }

    .stats {

        grid-template-columns:
            repeat(2, 1fr);

    }

}


@media(max-width: 700px) {

    .sidebar {

        position: relative;

        width: 100%;

    }

    .main-content {

        margin-left: 0;

    }

    .stats {

        grid-template-columns: 1fr;

    }

    .page-date {

        float: none;

        margin-top: 10px;

        text-align: left;

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


<div class="app">


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


        <a
            href="dashboard_radiolog.php"
            class="active"
        >

            <span class="menu-icon">
                🏠︎
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


        <a href="daftar_hasil_radiolog.php">

            <span class="menu-icon">
                ☢️
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


<!-- =========================================================
     TOPBAR
========================================================= -->

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



<!-- =========================================================
     PAGE HEADER
========================================================= -->

<section class="page-header">


    <div class="page-date">

        03 Sep 2025

        <strong>
            10:24
        </strong>

    </div>


    <h1 class="page-title">

        Dashboard Radiologi

    </h1>


    <p class="page-subtitle">

        Kelola pemeriksaan, interpretasi, dan laporan radiologi.

    </p>


</section>



<!-- =========================================================
     WELCOME
========================================================= -->

<section class="welcome-panel">


    <div>

        <div class="welcome-title">

            Selamat Datang,
            <?= htmlspecialchars($userName) ?>!

        </div>


        <div class="welcome-text">

            Anda masuk sebagai
            <strong>
                <?= htmlspecialchars($userRoleLabel) ?>
            </strong>.

        </div>

    </div>


    <div class="welcome-icon">

        ♡

    </div>


</section>



<!-- =========================================================
     STAT CARDS
========================================================= -->

<section class="stats">


    <!-- CARD 1 -->

    <div class="stat-card">

        <div class="stat-top">

            <div>

                <div class="stat-title">
                    Order Pasien
                </div>

                <div class="stat-number">
                    12
                </div>

                <div class="stat-desc">
                    Order radiologi hari ini
                </div>

            </div>


            <div class="stat-icon blue">
                ▤
            </div>

        </div>


        <a
            href="order_pasien_radiolog.php"
            class="stat-link"
        >
            Lihat Detail →
        </a>

    </div>



    <!-- CARD 2 -->

    <div class="stat-card">

        <div class="stat-top">

            <div>

                <div class="stat-title">
                    Sudah Diperiksa
                </div>

                <div class="stat-number">
                    5
                </div>

                <div class="stat-desc">
                    Pemeriksaan selesai dilakukan
                </div>

            </div>


            <div class="stat-icon green">
                ✓
            </div>

        </div>


        <a
            href="order_pasien_radiolog.php"
            class="stat-link"
        >
            Lihat Detail →
        </a>

    </div>



    <!-- CARD 3 -->

    <div class="stat-card">

        <div class="stat-top">

            <div>

                <div class="stat-title">
                    Menunggu Interpretasi
                </div>

                <div class="stat-number">
                    3
                </div>

                <div class="stat-desc">
                    Menunggu interpretasi radiolog
                </div>

            </div>


            <div class="stat-icon orange">
                ◷
            </div>

        </div>


        <a
            href="daftar_hasil_radiolog.php"
            class="stat-link"
        >
            Lihat Detail →
        </a>

    </div>



    <!-- CARD 4 -->

    <div class="stat-card">

        <div class="stat-top">

            <div>

                <div class="stat-title">
                    Laporan Selesai
                </div>

                <div class="stat-number">
                    18
                </div>

                <div class="stat-desc">
                    Laporan selesai hari ini
                </div>

            </div>


            <div class="stat-icon purple">
                ✓
            </div>

        </div>


        <a
            href="daftar_hasil_radiolog.php"
            class="stat-link"
        >
            Lihat Detail →
        </a>

    </div>


</section>



<!-- =========================================================
     TABLE
========================================================= -->

<section class="panel">


    <div class="panel-header">


        <div class="panel-title">

            <div class="panel-icon">
                ▣
            </div>


            <h2>
                Pemeriksaan Terbaru
            </h2>

        </div>


        <a
            href="order_pasien_radiolog.php"
            class="order-button"
        >
            + Order Pasien
        </a>


    </div>



    <div class="table-wrapper">


        <table>


            <thead>

                <tr>

                    <th>
                        No
                    </th>

                    <th>
                        No. Order
                    </th>

                    <th>
                        Nama Pasien
                    </th>

                    <th>
                        Jenis Pemeriksaan
                    </th>

                    <th>
                        Tanggal Order
                    </th>

                    <th>
                        Status
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
                        <span class="order-number">
                            RAD20250903001
                        </span>
                    </td>

                    <td>

                        <span class="patient-name">
                            Budi Santoso
                        </span>

                        <span class="patient-detail">
                            45 th • Laki-laki
                        </span>

                    </td>

                    <td>
                        Thorax PA
                    </td>

                    <td>
                        03-09-2025 08:12
                    </td>

                    <td>

                        <span class="status status-waiting">
                            Menunggu Interpretasi
                        </span>

                    </td>

                    <td>

                        <button
                            class="action-button"
                            onclick="lihatOrder('RAD20250903001')"
                        >
                            Lihat
                        </button>

                    </td>

                </tr>



                <tr>

                    <td>
                        2
                    </td>

                    <td>
                        <span class="order-number">
                            RAD20250903002
                        </span>
                    </td>

                    <td>

                        <span class="patient-name">
                            Siti Aminah
                        </span>

                        <span class="patient-detail">
                            38 th • Perempuan
                        </span>

                    </td>

                    <td>
                        Abdomen AP
                    </td>

                    <td>
                        03-09-2025 08:45
                    </td>

                    <td>

                        <span class="status status-waiting">
                            Menunggu Interpretasi
                        </span>

                    </td>

                    <td>

                        <button
                            class="action-button"
                            onclick="lihatOrder('RAD20250903002')"
                        >
                            Lihat
                        </button>

                    </td>

                </tr>



                <tr>

                    <td>
                        3
                    </td>

                    <td>
                        <span class="order-number">
                            RAD20250903003
                        </span>
                    </td>

                    <td>

                        <span class="patient-name">
                            Joko Widodo
                        </span>

                        <span class="patient-detail">
                            52 th • Laki-laki
                        </span>

                    </td>

                    <td>
                        CT Head
                    </td>

                    <td>
                        03-09-2025 09:10
                    </td>

                    <td>

                        <span class="status status-done">
                            Selesai
                        </span>

                    </td>

                    <td>

                        <button
                            class="action-button"
                            onclick="lihatOrder('RAD20250903003')"
                        >
                            Lihat
                        </button>

                    </td>

                </tr>



                <tr>

                    <td>
                        4
                    </td>

                    <td>
                        <span class="order-number">
                            RAD20250903004
                        </span>
                    </td>

                    <td>

                        <span class="patient-name">
                            Maria Lestari
                        </span>

                        <span class="patient-detail">
                            41 th • Perempuan
                        </span>

                    </td>

                    <td>
                        USG Abdomen
                    </td>

                    <td>
                        03-09-2025 09:30
                    </td>

                    <td>

                        <span class="status status-draft">
                            Draft
                        </span>

                    </td>

                    <td>

                        <button
                            class="action-button"
                            onclick="lihatOrder('RAD20250903004')"
                        >
                            Lihat
                        </button>

                    </td>

                </tr>



                <tr>

                    <td>
                        5
                    </td>

                    <td>
                        <span class="order-number">
                            RAD20250903005
                        </span>
                    </td>

                    <td>

                        <span class="patient-name">
                            Agus Setiawan
                        </span>

                        <span class="patient-detail">
                            47 th • Laki-laki
                        </span>

                    </td>

                    <td>
                        Lumbal AP/Lateral
                    </td>

                    <td>
                        03-09-2025 09:50
                    </td>

                    <td>

                        <span class="status status-waiting">
                            Menunggu Interpretasi
                        </span>

                    </td>

                    <td>

                        <button
                            class="action-button"
                            onclick="lihatOrder('RAD20250903005')"
                        >
                            Lihat
                        </button>

                    </td>

                </tr>


            </tbody>


        </table>


    </div>



    <div class="table-footer">

        <span>
            Menampilkan 1 - 5 dari 5 data
        </span>


        <a href="daftar_hasil_radiolog.php">

            Lihat Semua Pemeriksaan →

        </a>

    </div>


</section>


</main>

</div>


<script>

function lihatOrder(order) {

    alert(
        "Membuka pemeriksaan radiologi\n\n" +
        "No. Order: " + order
    );

}

</script>


</body>

</html>