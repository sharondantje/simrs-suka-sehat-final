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


/* =========================================================
   KHUSUS ADMIN
========================================================= */

if (($user['role'] ?? '') !== 'admin') {

    if (($user['role'] ?? '') === 'radiologist') {
        header('Location: dashboard_radiolog.php');
        exit;
    }

    header('Location: dashboard.php');
    exit;
}


/* =========================================================
   DATA USER
========================================================= */

$userName = $user['Herlambang'] ?? 'Herlambang';
$userRole = 'Administrator';

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>
Dashboard Administrator - RS Suka Sehat
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
   BRAND
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

    box-shadow:
        0 2px 7px rgba(30,70,90,0.04);
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
   USER TOPBAR
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

    min-width: 100px;
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

    margin-top: 18px;

    font-size: 11px;
}


.breadcrumb a {

    color: #1D5FA8;

    text-decoration: none;
}


.breadcrumb .separator {

    color: #6d8794;
}


.breadcrumb .current {

    color: #1D5FA8;

    font-weight: 600;
}


/* =========================================================
   PAGE HEADER
========================================================= */

.page-header {

    display: flex;

    justify-content: space-between;

    align-items: flex-end;

    margin-top: 5px;
}


.page-title {

    margin: 0;

    color: #063f61;

    font-size: 29px;

    font-weight: 700;
}


.page-subtitle {

    margin: 5px 0 0;

    color: #66869A;

    font-size: 13px;
}


.page-date {

    text-align: right;

    color: #527687;

    font-size: 9px;

    line-height: 1.5;
}


.page-date strong {

    color: #0A4D68;

    font-size: 11px;
}


/* =========================================================
   WELCOME
========================================================= */

.welcome-card {

    margin-top: 14px;

    padding: 15px 18px;

    background: #eef9fb;

    border: 1px solid #d2e8ed;

    border-radius: 8px;

    display: flex;

    align-items: center;

    justify-content: space-between;
}


.welcome-title {

    font-size: 15px;

    font-weight: 700;

    color: #064963;

    margin-bottom: 5px;
}


.welcome-sub {

    font-size: 10px;

    color: #66869A;
}


.welcome-icon {

    width: 42px;
    height: 42px;

    border-radius: 50%;

    background: #d6f1f2;

    color: #0D6E6E;

    display: flex;

    align-items: center;
    justify-content: center;

    font-size: 20px;
}


/* =========================================================
   QUICK ACTION GRID
========================================================= */

.quick-grid {

    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 10px;

    margin-top: 12px;
}


.quick-card {

    background: white;

    border: 1px solid #D6E8EE;

    border-radius: 7px;

    padding: 13px;

    min-height: 145px;

    text-decoration: none;

    color: inherit;

    position: relative;

    transition: .2s;
}


.quick-card:hover {

    transform: translateY(-2px);

    box-shadow:
        0 5px 14px rgba(30,70,90,.08);
}


.quick-icon {

    width: 45px;
    height: 45px;

    border-radius: 8px;

    background: #e2f3f5;

    color: #0D6E6E;

    display: flex;

    align-items: center;
    justify-content: center;

    font-size: 22px;

    margin-bottom: 10px;
}


.quick-title {

    color: #143f59;

    font-size: 12px;

    font-weight: 700;

    line-height: 1.35;
}


.quick-description {

    color: #75909c;

    font-size: 9px;

    line-height: 1.4;

    margin-top: 5px;
}


.quick-arrow {

    position: absolute;

    right: 12px;
    top: 15px;

    width: 31px;
    height: 31px;

    border-radius: 50%;

    background: #0D6E6E;

    color: white;

    display: flex;

    align-items: center;
    justify-content: center;

    font-size: 16px;
}


/* =========================================================
   STATISTICS
========================================================= */

.stat-number {

    margin-top: 9px;

    font-size: 21px;

    font-weight: 700;

    color: #0A4D68;
}


.stat-label {

    font-size: 8px;

    color: #79929d;
}


.stat-change {

    display: inline-block;

    margin-top: 5px;

    padding: 3px 6px;

    border-radius: 4px;

    background: #e1f6eb;

    color: #18855d;

    font-size: 8px;

    font-weight: 700;
}


/* =========================================================
   AUDIT CARD
========================================================= */

.audit-card {

    margin-top: 12px;

    background: white;

    border: 1px solid #D6E8EE;

    border-radius: 7px;

    padding: 13px;
}


.audit-header {

    display: flex;

    align-items: center;

    gap: 10px;
}


.audit-icon {

    width: 38px;
    height: 38px;

    border-radius: 7px;

    background: #EAF6F8;

    color: #0D6E6E;

    display: flex;

    align-items: center;
    justify-content: center;

    font-size: 19px;
}


.audit-title {

    font-size: 12px;

    font-weight: 700;

    color: #0A4D68;
}


.audit-subtitle {

    margin-top: 3px;

    color: #78919d;

    font-size: 9px;
}


.audit-arrow {

    margin-left: auto;

    width: 31px;
    height: 31px;

    border-radius: 50%;

    background: #0D6E6E;

    color: white;

    display: flex;

    align-items: center;
    justify-content: center;
}


.audit-stats {

    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 8px;

    margin-top: 10px;
}


.audit-stat {

    padding: 9px;

    background: #f7fbfc;

    border-radius: 5px;

}


.audit-stat-icon {

    color: #0D6E6E;

    font-size: 14px;

    margin-right: 5px;
}


.audit-stat-number {

    color: #214e65;

    font-size: 14px;

    font-weight: 700;
}


.audit-stat-label {

    color: #7b939e;

    font-size: 8px;

    margin-top: 3px;
}


/* =========================================================
   BOTTOM GRID
========================================================= */

.bottom-grid {

    display: grid;

    grid-template-columns:
        1.25fr
        1fr;

    gap: 12px;

    margin-top: 12px;
}


/* =========================================================
   ACTIVITY
========================================================= */

.activity-card,
.security-card {

    background: white;

    border: 1px solid #D6E8EE;

    border-radius: 7px;

    padding: 13px;
}


.section-header {

    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-bottom: 10px;
}


.section-title {

    display: flex;

    align-items: center;

    gap: 8px;

    font-size: 12px;

    font-weight: 700;

    color: #0A4D68;
}


.section-icon {

    width: 28px;
    height: 28px;

    border-radius: 5px;

    background: #EAF6F8;

    color: #0D6E6E;

    display: flex;

    align-items: center;
    justify-content: center;
}


.section-link {

    color: #0D6E6E;

    font-size: 9px;

    font-weight: 600;

    text-decoration: none;
}


.activity-item {

    display: grid;

    grid-template-columns: 8px 90px 105px 1fr;

    align-items: center;

    gap: 7px;

    padding: 7px 5px;

    border-bottom: 1px solid #edf2f4;

    font-size: 8px;
}


.activity-item:last-child {

    border-bottom: none;
}


.activity-dot {

    width: 7px;
    height: 7px;

    border-radius: 50%;

    background: #0D6E6E;
}


.activity-date {

    color: #7a919d;
}


.activity-user {

    color: #315E6E;

    font-weight: 600;
}


.activity-action {

    color: #66808e;
}


/* =========================================================
   SECURITY
========================================================= */

.security-stats {

    display: grid;

    grid-template-columns:
        1fr 1fr;

    gap: 8px;
}


.security-stat {

    background: #f5fafc;

    border-radius: 6px;

    padding: 10px;

    display: flex;

    align-items: center;

    gap: 8px;
}


.security-stat-icon {

    width: 35px;
    height: 35px;

    border-radius: 7px;

    background: #EAF6F8;

    color: #0D6E6E;

    display: flex;

    align-items: center;
    justify-content: center;

    font-size: 17px;
}


.security-number {

    color: #0A4D68;

    font-size: 15px;

    font-weight: 700;
}


.security-label {

    color: #7a929d;

    font-size: 8px;

    margin-top: 2px;
}


.security-alert {

    display: flex;

    align-items: center;

    gap: 9px;

    margin-top: 8px;

    padding: 10px;

    border-radius: 6px;

    background: #e3f6ed;

    color: #27835f;

    font-size: 9px;
}


.security-alert-icon {

    width: 27px;
    height: 27px;

    border-radius: 50%;

    background: #35ad78;

    color: white;

    display: flex;

    align-items: center;
    justify-content: center;

    flex-shrink: 0;
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 1100px) {

    .quick-grid {

        grid-template-columns:
            repeat(2, 1fr);

    }

}


@media (max-width: 850px) {

    .bottom-grid {

        grid-template-columns: 1fr;

    }

    .audit-stats {

        grid-template-columns:
            repeat(2, 1fr);

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

    .quick-grid {

        grid-template-columns: 1fr;

    }

    .page-header {

        display: block;

    }

    .page-date {

        text-align: left;

        margin-top: 8px;

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


    <!-- BRAND -->

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


        <!-- DASHBOARD -->

        <a
            href="dashboard_admin.php"
            class="active"
        >

            <span class="menu-icon">
                🏠︎
            </span>

            <span>
                Dashboard Administrator
            </span>

        </a>


        <!-- HAK AKSES -->

        <a href="hak_akses.php">

            <span class="menu-icon">
                🔓
            </span>

            <span>
                Hak Akses
            </span>

        </a>


        <!-- KELOLA AKUN -->

        <a href="kelola_akun.php">

            <span class="menu-icon">
            👤
            </span>

            <span>
                Kelola Akun Pengguna
            </span>

        </a>


        <!-- LOG AKTIVITAS -->

        <a href="log_aktivitas.php">

            <span class="menu-icon">
                ◷
            </span>

            <span>
                Log Aktivitas
            </span>

        </a>


        <!-- PENGATURAN -->

        <a href="pengaturan_admin.php">

            <span class="menu-icon">
                ⚙
            </span>

            <span>
                Pengaturan
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
                    <?= htmlspecialchars($userRole) ?>
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
         BREADCRUMB + HEADER
    ====================================================== -->

    <div class="breadcrumb">

        <span>
            ⌂
        </span>

        <span class="separator">
            &gt;
        </span>

        <a href="dashboard_admin.php">
            Dashboard
        </a>

        <span class="separator">
            &gt;
        </span>

        <span class="current">
            Authentication & User Management
        </span>

    </div>



    <div class="page-header">


        <div>

            <h1 class="page-title">
                Selamat datang, Admin!
            </h1>

            <p class="page-subtitle">
                Kelola pengguna, hak akses, dan keamanan sistem RS Suka Sehat
            </p>

        </div>


        <div class="page-date">

            Rabu, 03 September 2025<br>

            <strong>
                10:24
            </strong>

        </div>


    </div>



    <!-- =====================================================
         WELCOME
    ====================================================== -->

    <div class="welcome-card">


        <div>

            <div class="welcome-title">
                Halo, <?= htmlspecialchars($userName) ?>!
            </div>

            <div class="welcome-sub">
                Anda masuk sebagai Administrator Sistem.
            </div>

        </div>


        <div class="welcome-icon">
            ♡
        </div>


    </div>



    <!-- =====================================================
         QUICK ACTION CARDS
    ====================================================== -->

    <section class="quick-grid">


        <!-- KELOLA AKUN -->

        <a
            href="kelola_akun.php"
            class="quick-card"
        >

            <div class="quick-icon">
                👤⚙
            </div>

            <div class="quick-arrow">
                →
            </div>

            <div class="quick-title">
                Kelola Akun Pengguna
            </div>

            <div class="quick-description">
                Tambah, ubah, dan kelola data akun pengguna.
            </div>

            <div class="stat-number">
                128
            </div>

            <div class="stat-label">
                Total Pengguna
            </div>

            <span class="stat-change">
                ↑ 12%
            </span>

        </a>



        <!-- LOGIN SIMRS -->

        <a
            href="login.php"
            class="quick-card"
        >

            <div class="quick-icon">
                ⇥
            </div>

            <div class="quick-arrow">
                →
            </div>

            <div class="quick-title">
                Login ke SIMRS
            </div>

            <div class="quick-description">
                Akses sistem menggunakan username dan password.
            </div>

            <div class="stat-number">
                1.245
            </div>

            <div class="stat-label">
                Total Login Hari Ini
            </div>

            <span class="stat-change">
                ↑ 8%
            </span>

        </a>



        <!-- HAK AKSES -->

        <a
            href="hak_akses.php"
            class="quick-card"
        >

            <div class="quick-icon">
                🔓
            </div>

            <div class="quick-arrow">
                →
            </div>

            <div class="quick-title">
                Kelola Hak Akses
            </div>

            <div class="quick-description">
                Atur role dan permission pengguna sesuai kewenangan.
            </div>

            <div class="stat-number">
                8
            </div>

            <div class="stat-label">
                Role Tersedia
            </div>

            <span class="stat-change">
                —
            </span>

        </a>



        <!-- RESET PASSWORD -->

        <a
            href="kelola_akun.php"
            class="quick-card"
        >

            <div class="quick-icon">
                🔒
            </div>

            <div class="quick-arrow">
                →
            </div>

            <div class="quick-title">
                Reset Password & Kelola Status Akun
            </div>

            <div class="quick-description">
                Reset password pengguna dan atur status akun aktif/nonaktif.
            </div>

            <div class="stat-number">
                16
            </div>

            <div class="stat-label">
                Akun Nonaktif
            </div>

            <span
                class="stat-change"
                style="
                    background:#ffe6eb;
                    color:#dc5270;
                "
            >
                ↓ 5%
            </span>

        </a>


    </section>



    <!-- =====================================================
         AUDIT LOG
    ====================================================== -->

    <section class="audit-card">


        <div class="audit-header">


            <div class="audit-icon">
                🗃️
            </div>


            <div>

                <div class="audit-title">
                    Monitor Audit Log
                </div>

                <div class="audit-subtitle">
                    Lihat dan pantau seluruh riwayat aktivitas pengguna pada sistem.
                </div>

            </div>


            <a
                href="log_aktivitas.php"
                class="audit-arrow"
            >
                →
            </a>


        </div>



        <div class="audit-stats">


            <div class="audit-stat">

                <div>

                    <span class="audit-stat-icon">
                        🔑
                    </span>

                    <span class="audit-stat-number">
                        542
                    </span>

                </div>

                <div class="audit-stat-label">
                    Aktivitas Login
                </div>

            </div>


            <div class="audit-stat">

                <div>

                    <span class="audit-stat-icon">
                        ♟
                    </span>

                    <span class="audit-stat-number">
                        123
                    </span>

                </div>

                <div class="audit-stat-label">
                    Perubahan Role
                </div>

            </div>


            <div class="audit-stat">

                <div>

                    <span class="audit-stat-icon">
                        🔒
                    </span>

                    <span class="audit-stat-number">
                        28
                    </span>

                </div>

                <div class="audit-stat-label">
                    Reset Password
                </div>

            </div>


            <div class="audit-stat">

                <div>

                    <span class="audit-stat-icon">
                        ▤
                    </span>

                    <span class="audit-stat-number">
                        17
                    </span>

                </div>

                <div class="audit-stat-label">
                    Perubahan Status Akun
                </div>

            </div>


        </div>


    </section>



    <!-- =====================================================
         BOTTOM GRID
    ====================================================== -->

    <section class="bottom-grid">


        <!-- =================================================
             AKTIVITAS TERBARU
        ================================================== -->

        <div class="activity-card">


            <div class="section-header">


                <div class="section-title">

                    <div class="section-icon">
                        ◷
                    </div>

                    Aktivitas Terbaru

                </div>


                <a
                    href="log_aktivitas.php"
                    class="section-link"
                >
                    Lihat Semua
                </a>


            </div>



            <div class="activity-item">

                <span class="activity-dot"></span>

                <span class="activity-date">
                    03-09-2025 09:15
                </span>

                <span class="activity-user">
                    Admin Sistem
                </span>

                <span class="activity-action">
                    Menambahkan pengguna baru (budi.reg)
                </span>

            </div>


            <div class="activity-item">

                <span class="activity-dot"></span>

                <span class="activity-date">
                    03-09-2025 08:12
                </span>

                <span class="activity-user">
                    dr. Andi Pratama
                </span>

                <span class="activity-action">
                    Login ke sistem
                </span>

            </div>


            <div class="activity-item">

                <span class="activity-dot"></span>

                <span class="activity-date">
                    02-09-2025 16:21
                </span>

                <span class="activity-user">
                    Rina Wijaya
                </span>

                <span class="activity-action">
                    Perubahan status akun (Aktif → Nonaktif)
                </span>

            </div>


            <div class="activity-item">

                <span class="activity-dot"></span>

                <span class="activity-date">
                    02-09-2025 14:30
                </span>

                <span class="activity-user">
                    Admin Sistem
                </span>

                <span class="activity-action">
                    Reset password (siti.kep)
                </span>

            </div>


        </div>



        <!-- =================================================
             KEAMANAN
        ================================================== -->

        <div class="security-card">


            <div class="section-header">

                <div class="section-title">

                    <div class="section-icon">
                        🛡️
                    </div>

                    Ringkasan Keamanan

                </div>

            </div>



            <div class="security-stats">


                <div class="security-stat">

                    <div class="security-stat-icon">
                        🟢
                    </div>

                    <div>

                        <div class="security-number">
                            112
                        </div>

                        <div class="security-label">
                            Akun Aktif
                        </div>

                    </div>

                </div>


                <div class="security-stat">

                    <div class="security-stat-icon">
                        🔴
                    </div>

                    <div>

                        <div class="security-number">
                            16
                        </div>

                        <div class="security-label">
                            Akun Nonaktif
                        </div>

                    </div>

                </div>


            </div>



            <div class="security-alert">


                <div class="security-alert-icon">
                    ✓
                </div>


                <div>

                    <strong>
                        Sistem berjalan dengan aman
                    </strong>

                    <br>

                    Tidak ada aktivitas mencurigakan dalam
                    24 jam terakhir.

                </div>


            </div>


        </div>


    </section>


</main>


</body>

</html>