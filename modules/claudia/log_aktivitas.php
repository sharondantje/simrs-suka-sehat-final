<?php

session_start();


/*
 * =========================================================
 * LOG AKTIVITAS ADMIN
 * RS SUKA SEHAT
 * =========================================================
 *
 * Demo UI.
 * Tidak membutuhkan MySQL.
 *
 */


/* =========================================================
   DATA USER ADMIN
========================================================= */

$userName = 'Herlambang';

$userRole = 'Administrator';


/* =========================================================
   DATA LOG AKTIVITAS
========================================================= */

$activities = [

    [
        'id' => 1,
        'datetime' => '03-09-2025 10:24:12',
        'user' => 'Admin Sistem',
        'activity' => 'Login',
        'module' => 'Autentikasi',
        'detail' => 'Berhasil login ke sistem',
        'ip' => '192.168.1.10',
        'status' => 'Berhasil'
    ],

    [
        'id' => 2,
        'datetime' => '03-09-2025 09:15:33',
        'user' => 'dr. Andi Pratama',
        'activity' => 'Tambah Akun',
        'module' => 'User Management',
        'detail' => 'Membuat akun baru (budi.reg)',
        'ip' => '192.168.1.25',
        'status' => 'Berhasil'
    ],

    [
        'id' => 3,
        'datetime' => '03-09-2025 08:47:20',
        'user' => 'Siti Aminah',
        'activity' => 'Ubah Role',
        'module' => 'User Management',
        'detail' => 'Mengubah role pengguna (rina.lab)',
        'ip' => '192.168.1.30',
        'status' => 'Berhasil'
    ],

    [
        'id' => 4,
        'datetime' => '03-09-2025 08:12:01',
        'user' => 'Rina Wijaya',
        'activity' => 'Reset Password',
        'module' => 'User Management',
        'detail' => 'Reset password pengguna (siti.kep)',
        'ip' => '192.168.1.45',
        'status' => 'Berhasil'
    ],

    [
        'id' => 5,
        'datetime' => '02-09-2025 16:21:10',
        'user' => 'Budi Santoso',
        'activity' => 'Ubah Status',
        'module' => 'User Management',
        'detail' => 'Mengubah status akun (Nonaktif)',
        'ip' => '192.168.1.23',
        'status' => 'Berhasil'
    ],

    [
        'id' => 6,
        'datetime' => '02-09-2025 14:30:55',
        'user' => 'Siti Aminah',
        'activity' => 'Login Gagal',
        'module' => 'Autentikasi',
        'detail' => 'Password salah (3 kali)',
        'ip' => '192.168.1.30',
        'status' => 'Gagal'
    ],

    [
        'id' => 7,
        'datetime' => '02-09-2025 11:05:17',
        'user' => 'Admin Sistem',
        'activity' => 'Hapus Akun',
        'module' => 'User Management',
        'detail' => 'Menghapus akun (temp.user)',
        'ip' => '192.168.1.10',
        'status' => 'Berhasil'
    ],

    [
        'id' => 8,
        'datetime' => '01-09-2025 15:18:03',
        'user' => 'Petugas Lab',
        'activity' => 'Logout',
        'module' => 'Autentikasi',
        'detail' => 'Logout dari sistem',
        'ip' => '192.168.1.50',
        'status' => 'Berhasil'
    ],

    [
        'id' => 9,
        'datetime' => '01-09-2025 15:18:03',
        'user' => 'dr. Maya Sari',
        'activity' => 'Ubah Profile',
        'module' => 'User Management',
        'detail' => 'Mengubah data profil',
        'ip' => '192.168.1.27',
        'status' => 'Berhasil'
    ],

    [
        'id' => 10,
        'datetime' => '01-09-2025 08:02:11',
        'user' => 'Rina Wijaya',
        'activity' => 'Login',
        'module' => 'Autentikasi',
        'detail' => 'Berhasil login ke sistem',
        'ip' => '192.168.1.45',
        'status' => 'Berhasil'
    ]

];


$totalActivities = 542;

$activeUsers = 28;

$suspiciousActivities = 5;

$failedLogins = 0;

?>

<!DOCTYPE html>

<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Log Aktivitas - RS Suka Sehat
    </title>


    <style>

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


        /* =====================================================
           SIDEBAR
        ===================================================== */

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

            background:
                rgba(
                    255,
                    255,
                    255,
                    0.10
                );

        }


        .sidebar-menu a.active {

            background: #0d7c7b;

        }


        .menu-icon {

            width: 20px;

            text-align: center;

            font-size: 15px;

        }


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


        /* =====================================================
           MAIN
        ===================================================== */

        .main-content {

            margin-left: 230px;

            min-height: 100vh;

            padding:
                0
                30px
                35px;

        }


        /* =====================================================
           TOPBAR
        ===================================================== */

        .topbar {

            height: 65px;

            margin-top: 18px;

            background: white;

            border:
                1px solid
                #D6E8EE;

            border-radius: 10px;

            display: flex;

            align-items: center;

            justify-content: space-between;

            padding:
                0
                18px;

            box-shadow:
                0 2px 7px
                rgba(
                    30,
                    70,
                    90,
                    0.04
                );

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


        /* =====================================================
           BREADCRUMB
        ===================================================== */

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


        .breadcrumb .current {

            color: #1D5FA8;

            font-weight: 600;

        }


        /* =====================================================
           PAGE HEADER
        ===================================================== */

        .page-head {

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

            margin:
                4px
                0
                0;

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


        /* =====================================================
           EXPORT
        ===================================================== */

        .export-area {

            display: flex;

            justify-content: flex-end;

            margin-top: -35px;

            margin-bottom: 24px;

        }


        .export-button {

            height: 38px;

            min-width: 130px;

            padding:
                0
                12px;

            border:
                1px solid
                #58a9bd;

            background: white;

            color: #17667c;

            border-radius: 6px;

            font-weight: 700;

            cursor: pointer;

        }


        .export-button:hover {

            background: #EAF6F8;

        }


        /* =====================================================
           FILTER PANEL
        ===================================================== */

        .filter-panel {

            background: white;

            border:
                1px solid
                #D6E8EE;

            border-radius: 7px;

            padding: 12px;

            margin-top: 8px;

        }


        .filter-title {

            display: flex;

            align-items: center;

            gap: 8px;

            color: #0A4D68;

            font-size: 12px;

            font-weight: 700;

            margin-bottom: 9px;

        }


        .filter-icon {

            width: 28px;

            height: 28px;

            border-radius: 5px;

            background: #e0f3f5;

            color: #0D6E6E;

            display: flex;

            align-items: center;

            justify-content: center;

        }


        .filter-grid {

            display: grid;

            grid-template-columns:
                1fr
                1fr
                1.1fr
                1.1fr
                1.1fr
                105px;

            gap: 9px;

            align-items: end;

        }


        .filter-group {

            display: flex;

            flex-direction: column;

            gap: 5px;

        }


        .filter-label {

            font-size: 9px;

            color: #4f6c7a;

            font-weight: 700;

        }


        .filter-input,
        .filter-select {

            width: 100%;

            height: 34px;

            border:
                1px solid
                #D6E8EE;

            border-radius: 5px;

            padding:
                0
                9px;

            background: white;

            color: #4d6a78;

            font-size: 9px;

            outline: none;

        }


        .filter-input:focus,
        .filter-select:focus {

            border-color: #66b6c1;

        }


        .search-button {

            height: 34px;

            border: none;

            border-radius: 5px;

            background: #0D6E6E;

            color: white;

            font-weight: 700;

            cursor: pointer;

            font-size: 9px;

        }


        .reset-filter {

            display: block;

            text-align: right;

            margin-top: 6px;

            color: #356879;

            font-size: 9px;

            text-decoration: underline;

            cursor: pointer;

        }


        /* =====================================================
           STAT CARDS
        ===================================================== */

        .stats-grid {

            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 8px;

            margin-top: 10px;

        }


        .stat-card {

            background: white;

            border:
                1px solid
                #D6E8EE;

            border-radius: 7px;

            min-height: 70px;

            padding:
                9px
                11px;

            display: flex;

            align-items: center;

            gap: 9px;

        }


        .stat-icon {

            width: 39px;

            height: 39px;

            flex-shrink: 0;

            border-radius: 7px;

            background: #EAF6F8;

            color: #0D6E6E;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 18px;

        }


        .stat-content {

            min-width: 0;

            flex: 1;

        }


        .stat-number-row {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 4px;

        }


        .stat-number {

            color: #073f60;

            font-size: 20px;

            font-weight: 700;

        }


        .stat-label {

            color: #66869A;

            font-size: 9px;

            margin-top: 2px;

        }


        .stat-change {

            font-size: 8px;

            padding:
                3px
                5px;

            border-radius: 4px;

            white-space: nowrap;

        }


        .stat-change.up {

            color: #19865d;

            background: #e0f6eb;

        }


        .stat-change.down {

            color: #d44a65;

            background: #ffe5e9;

        }


        .stat-change.neutral {

            color: #19865d;

            background: #e0f6eb;

        }


        .stat-note {

            color: #8ba0aa;

            font-size: 7px;

            margin-top: 2px;

        }


        /* =====================================================
           ACTIVITY PANEL
        ===================================================== */

        .activity-panel {

            background: white;

            border:
                1px solid
                #D6E8EE;

            border-radius: 7px;

            margin-top: 10px;

            overflow: hidden;

        }


        .activity-header {

            display: flex;

            align-items: center;

            justify-content: space-between;

            padding:
                10px
                12px;

            border-bottom:
                1px solid
                #e0ebef;

            background: #fbfdfe;

        }


        .activity-title {

            display: flex;

            align-items: center;

            gap: 9px;

        }


        .activity-icon {

            width: 28px;

            height: 28px;

            border-radius: 5px;

            background: #EAF6F8;

            color: #0D6E6E;

            display: flex;

            align-items: center;

            justify-content: center;

        }


        .activity-title-text {

            font-size: 12px;

            font-weight: 700;

            color: #0A4D68;

        }


        .rows-per-page {

            display: flex;

            align-items: center;

            gap: 5px;

            color: #66869A;

            font-size: 9px;

        }


        .rows-per-page select {

            height: 28px;

            border:
                1px solid
                #D6E8EE;

            border-radius: 5px;

            padding:
                0
                8px;

            background: white;

            font-size: 9px;

        }


        .activity-body {

            padding:
                0
                12px
                12px;

        }


        /* =====================================================
           TABLE
        ===================================================== */

        .table-wrap {

            overflow-x: auto;

        }


        .activity-table {

            width: 100%;

            border-collapse: collapse;

            table-layout: fixed;

        }


        .activity-table th {

            background: #e9f4fa;

            color: #315E6E;

            font-size: 9px;

            font-weight: 700;

            padding:
                8px
                6px;

            text-align: left;

        }


        .activity-table td {

            padding:
                7px
                6px;

            border-bottom:
                1px solid
                #E6F0F3;

            color: #315E6E;

            font-size: 8.5px;

            vertical-align: middle;

        }


        .activity-table tbody tr:hover {

            background: #f7fbfc;

        }


        .activity-table th:nth-child(1),
        .activity-table td:nth-child(1) {

            width: 38px;

            text-align: center;

        }


        .activity-table th:nth-child(2),
        .activity-table td:nth-child(2) {

            width: 125px;

        }


        .activity-table th:nth-child(3),
        .activity-table td:nth-child(3) {

            width: 115px;

        }


        .activity-table th:nth-child(4),
        .activity-table td:nth-child(4) {

            width: 100px;

        }


        .activity-table th:nth-child(5),
        .activity-table td:nth-child(5) {

            width: 105px;

        }


        .activity-table th:nth-child(6),
        .activity-table td:nth-child(6) {

            width: 210px;

        }


        .activity-table th:nth-child(7),
        .activity-table td:nth-child(7) {

            width: 115px;

        }


        .activity-table th:nth-child(8),
        .activity-table td:nth-child(8) {

            width: 75px;

        }


        .activity-table th:nth-child(9),
        .activity-table td:nth-child(9) {

            width: 40px;

            text-align: center;

        }


        /* =====================================================
           ACTIVITY BADGES
        ===================================================== */

        .activity-badge {

            display: inline-block;

            padding:
                4px
                7px;

            border-radius: 4px;

            font-size: 8px;

            font-weight: 700;

            white-space: nowrap;

        }


        .activity-login {

            background: #dff7e9;

            color: #19845b;

        }


        .activity-add {

            background: #dff3ff;

            color: #1971a7;

        }


        .activity-role {

            background: #ffeacb;

            color: #c47715;

        }


        .activity-reset {

            background: #eadfff;

            color: #7555b8;

        }


        .activity-status {

            background: #ffe9c8;

            color: #bd7214;

        }


        .activity-failed {

            background: #ffe1e5;

            color: #d14360;

        }


        .activity-delete {

            background: #ffdfe4;

            color: #d14360;

        }


        .activity-logout {

            background: #e8edf1;

            color: #536b77;

        }


        .activity-profile {

            background: #dff0ff;

            color: #2675ad;

        }


        /* =====================================================
           STATUS
        ===================================================== */

        .log-status {

            display: inline-block;

            padding:
                4px
                7px;

            border-radius: 4px;

            font-size: 8px;

            font-weight: 700;

        }


        .log-success {

            background: #dff7e9;

            color: #19845b;

        }


        .log-failed {

            background: #ffe1e5;

            color: #d14360;

        }


        /* =====================================================
           ACTION
        ===================================================== */

        .more-button {

            border: none;

            background: transparent;

            color: #557582;

            cursor: pointer;

            font-weight: 700;

            font-size: 12px;

        }


        /* =====================================================
           TABLE FOOTER
        ===================================================== */

        .table-footer {

            display: flex;

            align-items: center;

            justify-content: space-between;

            margin-top: 10px;

        }


        .table-info {

            color: #78919c;

            font-size: 9px;

        }


        .pagination {

            display: flex;

            align-items: center;

            gap: 4px;

        }


        .page-button {

            width: 27px;

            height: 27px;

            border:
                1px solid
                #d5e4e9;

            background: white;

            border-radius: 5px;

            color: #476978;

            cursor: pointer;

            font-size: 9px;

        }


        .page-button:hover {

            background: #EAF6F8;

        }


        .page-button.active {

            background: #0D6E6E;

            border-color: #0D6E6E;

            color: white;

        }


        .page-dots {

            color: #718b98;

            padding:
                0
                3px;

        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 1200px) {

            .filter-grid {

                grid-template-columns:
                    repeat(3, 1fr);

            }


            .stats-grid {

                grid-template-columns:
                    repeat(2, 1fr);

            }

        }


        @media (max-width: 800px) {

            .sidebar {

                position: relative;

                width: 100%;

            }


            .main-content {

                margin-left: 0;

                padding:
                    0
                    15px
                    25px;

            }


            .page-head {

                display: block;

            }


            .page-date {

                text-align: left;

                margin-top: 8px;

            }


            .export-area {

                margin-top: 12px;

                margin-bottom: 10px;

            }


            .filter-grid {

                grid-template-columns: 1fr;

            }


            .stats-grid {

                grid-template-columns: 1fr;

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

                RS
                <span>Suka</span>
                Sehat

            </div>


            <div class="sidebar-sub">
                SIMRS
            </div>


        </div>


    </div>


    <nav class="sidebar-menu">


        <!-- DASHBOARD -->

        <a href="dashboard_admin.php">

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

        <a
            href="log_aktivitas.php"
            class="active"
        >

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


    <div class="sidebar-footer">


        <div class="heart">
            ♥
        </div>


        Melayani dengan<br>
        Sepenuh Hati


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
                id="menuSearch"
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
         BREADCRUMB
    ====================================================== -->

    <div class="breadcrumb">


        <span>
            ◷
        </span>


        <a href="dashboard_admin.php">
            Dashboard
        </a>


        <span>
            &gt;
        </span>


        <span class="current">
            Log Aktivitas
        </span>


    </div>


    <!-- =====================================================
         PAGE HEADER
    ====================================================== -->

    <div class="page-head">


        <div>


            <h1 class="page-title">
                Log Aktivitas
            </h1>


            <p class="page-subtitle">
                Lihat dan pantau seluruh aktivitas pengguna
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
         EXPORT
    ====================================================== -->

    <div class="export-area">


        <button
            class="export-button"
            onclick="exportData()"
        >

            ⇩
            &nbsp;
            Export
            &nbsp;
           ⌄

        </button>


    </div>


    <!-- =====================================================
         FILTER
    ====================================================== -->

    <section class="filter-panel">


        <div class="filter-title">


            <div class="filter-icon">
                🔎
            </div>


            <span>
                Filter Pencarian
            </span>


        </div>


        <div class="filter-grid">


            <!-- TANGGAL MULAI -->

            <div class="filter-group">


                <label class="filter-label">
                    Tanggal Mulai
                </label>


                <input
                    type="date"
                    id="startDate"
                    class="filter-input"
                    value="2025-09-01"
                >


            </div>


            <!-- TANGGAL AKHIR -->

            <div class="filter-group">


                <label class="filter-label">
                    Tanggal Akhir
                </label>


                <input
                    type="date"
                    id="endDate"
                    class="filter-input"
                    value="2025-09-03"
                >


            </div>


            <!-- PENGGUNA -->

            <div class="filter-group">


                <label class="filter-label">
                    Pengguna
                </label>


                <select
                    id="userFilter"
                    class="filter-select"
                >

                    <option value="">
                        Semua Pengguna
                    </option>

                    <option>
                        Admin Sistem
                    </option>

                    <option>
                        dr. Andi Pratama
                    </option>

                    <option>
                        Siti Aminah
                    </option>

                    <option>
                        Rina Wijaya
                    </option>

                    <option>
                        Budi Santoso
                    </option>

                    <option>
                        Petugas Lab
                    </option>

                    <option>
                        dr. Maya Sari
                    </option>

                </select>


            </div>


            <!-- JENIS AKTIVITAS -->

            <div class="filter-group">


                <label class="filter-label">
                    Jenis Aktivitas
                </label>


                <select
                    id="activityFilter"
                    class="filter-select"
                >

                    <option value="">
                        Semua Aktivitas
                    </option>

                    <option>
                        Login
                    </option>

                    <option>
                        Login Gagal
                    </option>

                    <option>
                        Logout
                    </option>

                    <option>
                        Tambah Akun
                    </option>

                    <option>
                        Ubah Role
                    </option>

                    <option>
                        Reset Password
                    </option>

                    <option>
                        Ubah Status
                    </option>

                    <option>
                        Hapus Akun
                    </option>

                    <option>
                        Ubah Profile
                    </option>

                </select>


            </div>


            <!-- MODUL -->

            <div class="filter-group">


                <label class="filter-label">
                    Modul
                </label>


                <select
                    id="moduleFilter"
                    class="filter-select"
                >

                    <option value="">
                        Semua Modul
                    </option>

                    <option>
                        Autentikasi
                    </option>

                    <option>
                        User Management
                    </option>

                    <option>
                        Pendaftaran
                    </option>

                    <option>
                        Radiologi
                    </option>

                    <option>
                        Laboratorium
                    </option>

                    <option>
                        Farmasi
                    </option>

                </select>


            </div>


            <!-- BUTTON CARI -->

            <button
                class="search-button"
                onclick="applyFilter()"
            >

                🔎
                &nbsp;
                Cari

            </button>


        </div>


        <a
            class="reset-filter"
            onclick="resetFilter()"
        >
            Reset Filter
        </a>


    </section>


    <!-- =====================================================
         STATISTICS
    ====================================================== -->

    <section class="stats-grid">


        <!-- TOTAL AKTIVITAS -->

        <div class="stat-card">


            <div class="stat-icon">
                ⓘ
            </div>


            <div class="stat-content">


                <div class="stat-number-row">


                    <div class="stat-number">
                        <?= $totalActivities ?>
                    </div>


                    <span class="stat-change up">
                        ↑ 12%
                    </span>


                </div>


                <div class="stat-label">
                    Total Aktivitas
                </div>


                <div class="stat-note">
                    dari minggu lalu
                </div>


            </div>


        </div>


        <!-- PENGGUNA AKTIF -->

        <div class="stat-card">


            <div class="stat-icon">
                🟢
            </div>


            <div class="stat-content">


                <div class="stat-number-row">


                    <div class="stat-number">
                        <?= $activeUsers ?>
                    </div>


                    <span class="stat-change up">
                        ↑ 8%
                    </span>


                </div>


                <div class="stat-label">
                    Pengguna Aktif
                </div>


                <div class="stat-note">
                    dari minggu lalu
                </div>


            </div>


        </div>


        <!-- AKTIVITAS MENCURIGAKAN -->

        <div class="stat-card">


            <div class="stat-icon">
                ⚠️
            </div>


            <div class="stat-content">


                <div class="stat-number-row">


                    <div class="stat-number">
                        <?= $suspiciousActivities ?>
                    </div>


                    <span class="stat-change down">
                        ↑ 3
                    </span>


                </div>


                <div class="stat-label">
                    Aktivitas Mencurigakan
                </div>


                <div class="stat-note">
                    perlu ditinjau
                </div>


            </div>


        </div>


        <!-- KEGAGALAN LOGIN -->

        <div class="stat-card">


            <div class="stat-icon">
                👎
            </div>


            <div class="stat-content">


                <div class="stat-number-row">


                    <div class="stat-number">
                        <?= $failedLogins ?>
                    </div>


                    <span class="stat-change neutral">
                        ↓ 100%
                    </span>


                </div>


                <div class="stat-label">
                    Kegagalan Login
                </div>


                <div class="stat-note">
                    dari minggu lalu
                </div>


            </div>


        </div>


    </section>


    <!-- =====================================================
         ACTIVITY TABLE
    ====================================================== -->

    <section class="activity-panel">


        <div class="activity-header">


            <div class="activity-title">


                <div class="activity-icon">
                    ⓘ
                </div>


                <div class="activity-title-text">
                    Daftar Aktivitas
                </div>


            </div>


            <div class="rows-per-page">


                <select id="rowsPerPage">


                    <option value="10">
                        10
                    </option>


                    <option value="25">
                        25
                    </option>


                    <option value="50">
                        50
                    </option>


                </select>


                <span>
                    data per halaman
                </span>


            </div>


        </div>


        <div class="activity-body">


            <div class="table-wrap">


                <table class="activity-table">


                    <thead>


                        <tr>


                            <th>
                                No.
                            </th>


                            <th>
                                Tanggal & Waktu
                            </th>


                            <th>
                                Pengguna
                            </th>


                            <th>
                                Aktivitas
                            </th>


                            <th>
                                Modul
                            </th>


                            <th>
                                Detail
                            </th>


                            <th>
                                IP Address
                            </th>


                            <th>
                                Status
                            </th>


                            <th>
                            </th>


                        </tr>


                    </thead>


                    <tbody id="activityTableBody">


                        <?php foreach (
                            $activities
                            as $activity
                        ): ?>


                            <tr
                                data-user="<?= htmlspecialchars(
                                    $activity['user']
                                ) ?>"
                                data-activity="<?= htmlspecialchars(
                                    $activity['activity']
                                ) ?>"
                                data-module="<?= htmlspecialchars(
                                    $activity['module']
                                ) ?>"
                            >


                                <td>
                                    <?= $activity['id'] ?>
                                </td>


                                <td>
                                    <?= htmlspecialchars(
                                        $activity['datetime']
                                    ) ?>
                                </td>


                                <td>
                                    <?= htmlspecialchars(
                                        $activity['user']
                                    ) ?>
                                </td>


                                <td>


                                    <span
                                        class="activity-badge
                                        <?= getActivityClass(
                                            $activity['activity']
                                        ) ?>"
                                    >

                                        <?= htmlspecialchars(
                                            $activity['activity']
                                        ) ?>

                                    </span>


                                </td>


                                <td>
                                    <?= htmlspecialchars(
                                        $activity['module']
                                    ) ?>
                                </td>


                                <td>
                                    <?= htmlspecialchars(
                                        $activity['detail']
                                    ) ?>
                                </td>


                                <td>
                                    <?= htmlspecialchars(
                                        $activity['ip']
                                    ) ?>
                                </td>


                                <td>


                                    <span
                                        class="log-status
                                        <?= $activity['status'] === 'Berhasil'
                                            ? 'log-success'
                                            : 'log-failed'
                                        ?>"
                                    >

                                        ●

                                        <?= htmlspecialchars(
                                            $activity['status']
                                        ) ?>

                                    </span>


                                </td>


                                <td>


                                    <button
                                        class="more-button"
                                        onclick="viewActivity(
                                            <?= $activity['id'] ?>
                                        )"
                                    >

                                        ...

                                    </button>


                                </td>


                            </tr>


                        <?php endforeach; ?>


                    </tbody>


                </table>


            </div>


            <!-- TABLE FOOTER -->

            <div class="table-footer">


                <div
                    class="table-info"
                    id="tableInfo"
                >

                    Menampilkan
                    1 - 10
                    dari
                    <?= $totalActivities ?>
                    data

                </div>


                <div class="pagination">


                    <button
                        class="page-button"
                        onclick="goPage(1)"
                    >
                        «
                    </button>


                    <button
                        class="page-button"
                        onclick="goPage(1)"
                    >
                        ‹
                    </button>


                    <button
                        class="page-button active"
                    >
                        1
                    </button>


                    <button
                        class="page-button"
                        onclick="goPage(2)"
                    >
                        2
                    </button>


                    <button
                        class="page-button"
                        onclick="goPage(3)"
                    >
                        3
                    </button>


                    <button
                        class="page-button"
                        onclick="goPage(4)"
                    >
                        4
                    </button>


                    <button
                        class="page-button"
                        onclick="goPage(5)"
                    >
                        5
                    </button>


                    <span class="page-dots">
                        ...
                    </span>


                    <button
                        class="page-button"
                        onclick="goPage(55)"
                    >
                        55
                    </button>


                    <button
                        class="page-button"
                        onclick="goPage(2)"
                    >
                        ›
                    </button>


                    <button
                        class="page-button"
                        onclick="goPage(55)"
                    >
                        »
                    </button>


                </div>


            </div>


        </div>


    </section>


</main>


<script>

/* =========================================================
   MENU SEARCH
========================================================= */

document
    .getElementById('menuSearch')
    .addEventListener(
        'keydown',
        function(event) {


            if (
                event.key !== 'Enter'
            ) {

                return;

            }


            const value =
                this.value
                    .toLowerCase()
                    .trim();


            const links =
                document.querySelectorAll(
                    '.sidebar-menu a'
                );


            links.forEach(
                link => {


                    const text =
                        link.textContent
                            .toLowerCase();


                    if (
                        text.includes(value)
                    ) {

                        link.click();

                    }

                }
            );


        }
    );


/* =========================================================
   FILTER
========================================================= */

function applyFilter() {


    const user =
        document
            .getElementById('userFilter')
            .value
            .toLowerCase();


    const activity =
        document
            .getElementById('activityFilter')
            .value
            .toLowerCase();


    const module =
        document
            .getElementById('moduleFilter')
            .value
            .toLowerCase();


    let visible = 0;


    document
        .querySelectorAll(
            '#activityTableBody tr'
        )
        .forEach(row => {


            const rowUser =
                row.dataset.user
                    .toLowerCase();


            const rowActivity =
                row.dataset.activity
                    .toLowerCase();


            const rowModule =
                row.dataset.module
                    .toLowerCase();


            const matchUser =
                !user ||
                rowUser === user;


            const matchActivity =
                !activity ||
                rowActivity === activity;


            const matchModule =
                !module ||
                rowModule === module;


            const show =
                matchUser &&
                matchActivity &&
                matchModule;


            row.style.display =
                show
                    ? ''
                    : 'none';


            if (show) {

                visible++;

            }


        });


    document.getElementById(
        'tableInfo'
    ).textContent =

        'Menampilkan 1 - ' +
        visible +
        ' dari ' +
        visible +
        ' data';


}


/* =========================================================
   RESET FILTER
========================================================= */

function resetFilter() {


    document
        .getElementById('startDate')
        .value = '2025-09-01';


    document
        .getElementById('endDate')
        .value = '2025-09-03';


    document
        .getElementById('userFilter')
        .value = '';


    document
        .getElementById('activityFilter')
        .value = '';


    document
        .getElementById('moduleFilter')
        .value = '';


    document
        .querySelectorAll(
            '#activityTableBody tr'
        )
        .forEach(row => {

            row.style.display = '';

        });


    document.getElementById(
        'tableInfo'
    ).textContent =

        'Menampilkan 1 - 10 dari 542 data';


}


/* =========================================================
   VIEW ACTIVITY
========================================================= */

function viewActivity(id) {


    const activities =
        <?= json_encode(
            $activities,
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES
        ) ?>;


    const activity =
        activities.find(
            item => item.id === id
        );


    if (!activity) {

        return;

    }


    alert(

        'DETAIL AKTIVITAS\\n\\n' +

        'Tanggal & Waktu: ' +
        activity.datetime +
        '\\n' +

        'Pengguna: ' +
        activity.user +
        '\\n' +

        'Aktivitas: ' +
        activity.activity +
        '\\n' +

        'Modul: ' +
        activity.module +
        '\\n' +

        'Detail: ' +
        activity.detail +
        '\\n' +

        'IP Address: ' +
        activity.ip +
        '\\n' +

        'Status: ' +
        activity.status

    );


}


/* =========================================================
   EXPORT
========================================================= */

function exportData() {


    alert(
        'Data log aktivitas siap diekspor.'
    );


}


/* =========================================================
   PAGINATION DEMO
========================================================= */

function goPage(page) {


    if (page === 1) {

        alert(
            'Anda sedang berada di halaman 1.'
        );

        return;

    }


    alert(
        'Halaman ' +
        page +
        ' dipilih. Pagination demo.'
    );


}


/* =========================================================
   ACTIVITY CLASS
========================================================= */

function getActivityClass(
    activity
) {

    return '';

}

</script>


<?php


/* =========================================================
   ACTIVITY CLASS PHP
========================================================= */

function getActivityClass(
    $activity
) {

    switch ($activity) {


        case 'Login':

            return 'activity-login';


        case 'Tambah Akun':

            return 'activity-add';


        case 'Ubah Role':

            return 'activity-role';


        case 'Reset Password':

            return 'activity-reset';


        case 'Ubah Status':

            return 'activity-status';


        case 'Login Gagal':

            return 'activity-failed';


        case 'Hapus Akun':

            return 'activity-delete';


        case 'Logout':

            return 'activity-logout';


        case 'Ubah Profile':

            return 'activity-profile';


        default:

            return 'activity-login';

    }

}

?>

</body>

</html>