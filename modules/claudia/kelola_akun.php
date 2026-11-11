<?php
session_start();

/*
 * KELOLA AKUN PENGGUNA - ADMIN
 * Bisa dibuka langsung untuk demo UI.
 */

$userName = $_SESSION['user']['Herlambang'] ?? 'Herlambang';
$userRole = $_SESSION['user']['role_label'] ?? 'Administrator';


/* =========================================================
   DATA AKUN PENGGUNA DEMO
========================================================= */

$users = [

    [
        'id' => 1,
        'name' => 'Herlembang',
        'username' => 'admin',
        'role' => 'Administrator',
        'unit' => 'Manajemen Sistem',
        'email' => 'admin@rs-sukasehat.com',
        'phone' => '0812-0000-0001',
        'status' => 'Aktif',
        'last_login' => '03-09-2025 10:24'
    ],

    [
        'id' => 2,
        'name' => 'dr. Andi Pratama, Sp.PD',
        'username' => 'andi.pd',
        'role' => 'Dokter',
        'unit' => 'Poliklinik Umum',
        'email' => 'andi.pd@rs-sukasehat.com',
        'phone' => '0812-3456-7890',
        'status' => 'Aktif',
        'last_login' => '03-09-2025 08:12'
    ],

    [
        'id' => 3,
        'name' => 'dr. Maya Sari, Sp.A',
        'username' => 'maya.s',
        'role' => 'Dokter',
        'unit' => 'Poliklinik Anak',
        'email' => 'maya.s@rs-sukasehat.com',
        'phone' => '0812-3456-7891',
        'status' => 'Aktif',
        'last_login' => '03-09-2025 08:05'
    ],

    [
        'id' => 4,
        'name' => 'dr. Kevin Lie, Sp.OG',
        'username' => 'kevin.og',
        'role' => 'Dokter',
        'unit' => 'Poliklinik Kandungan',
        'email' => 'kevin.og@rs-sukasehat.com',
        'phone' => '0812-3456-7892',
        'status' => 'Aktif',
        'last_login' => '03-09-2025 07:58'
    ],

    [
        'id' => 5,
        'name' => 'Rini Sutijah',
        'username' => 'radiolog',
        'role' => 'Petugas Radiologi',
        'unit' => 'Instalasi Radiologi',
        'email' => 'rini.sutijah@rs-sukasehat.com',
        'phone' => '0812-3456-7893',
        'status' => 'Aktif',
        'last_login' => '03-09-2025 09:10'
    ],

    [
        'id' => 6,
        'name' => 'Andi Putra',
        'username' => 'petugas',
        'role' => 'Petugas Registrasi',
        'unit' => 'Pendaftaran',
        'email' => 'andi.putra@rs-sukasehat.com',
        'phone' => '0812-3456-7894',
        'status' => 'Aktif',
        'last_login' => '03-09-2025 08:45'
    ],

    [
        'id' => 7,
        'name' => 'Siti Kep',
        'username' => 'siti.kep',
        'role' => 'Perawat',
        'unit' => 'Rawat Inap',
        'email' => 'siti.kep@rs-sukasehat.com',
        'phone' => '0812-3456-7895',
        'status' => 'Aktif',
        'last_login' => '02-09-2025 15:20'
    ],

    [
        'id' => 8,
        'name' => 'Budi Labor',
        'username' => 'budi.lab',
        'role' => 'Petugas Laboratorium',
        'unit' => 'Laboratorium',
        'email' => 'budi.lab@rs-sukasehat.com',
        'phone' => '0812-3456-7896',
        'status' => 'Nonaktif',
        'last_login' => '28-08-2025 11:30'
    ],

    [
        'id' => 9,
        'name' => 'Maya Putri',
        'username' => 'maya.farmasi',
        'role' => 'Petugas Farmasi',
        'unit' => 'Farmasi',
        'email' => 'maya.farmasi@rs-sukasehat.com',
        'phone' => '0812-3456-7897',
        'status' => 'Aktif',
        'last_login' => '03-09-2025 09:25'
    ],

    [
        'id' => 10,
        'name' => 'Dewi Manager',
        'username' => 'dewi.manager',
        'role' => 'Manajer',
        'unit' => 'Manajemen',
        'email' => 'dewi.manager@rs-sukasehat.com',
        'phone' => '0812-3456-7898',
        'status' => 'Nonaktif',
        'last_login' => '25-08-2025 14:10'
    ]

];


$totalUsers = count($users);

$activeUsers = 0;
$inactiveUsers = 0;

foreach ($users as $user) {

    if ($user['status'] === 'Aktif') {

        $activeUsers++;

    } else {

        $inactiveUsers++;

    }

}

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
        Kelola Akun Pengguna - RS Suka Sehat
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
           MAIN CONTENT
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
           STATISTICS
        ===================================================== */

        .stats-grid {

            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 10px;

            margin-top: 16px;
        }


        .stat-card {

            background: white;

            border:
                1px solid
                #D6E8EE;

            border-radius: 7px;

            padding: 13px;

            min-height: 92px;

            display: flex;

            align-items: center;

            justify-content: space-between;
        }


        .stat-info {

            display: flex;

            flex-direction: column;

            gap: 5px;
        }


        .stat-label {

            color: #6d8794;

            font-size: 10px;
        }


        .stat-number {

            color: #073f60;

            font-size: 24px;

            font-weight: 700;
        }


        .stat-description {

            color: #8aa0aa;

            font-size: 8px;
        }


        .stat-icon {

            width: 42px;
            height: 42px;

            border-radius: 8px;

            background: #e2f3f5;

            color: #0D6E6E;

            display: flex;

            align-items: center;
            justify-content: center;

            font-size: 20px;
        }


        /* =====================================================
           ACCOUNT PANEL
        ===================================================== */

        .account-panel {

            background: white;

            border:
                1px solid
                #D6E8EE;

            border-radius: 7px;

            overflow: hidden;

            margin-top: 10px;
        }


        .panel-header {

            padding:
                11px
                13px;

            display: flex;

            align-items: center;

            justify-content: space-between;

            border-bottom:
                1px solid
                #e0ebef;

            background: #fbfdfe;
        }


        .panel-header-left {

            display: flex;

            align-items: center;

            gap: 9px;
        }


        .panel-icon {

            width: 29px;
            height: 29px;

            border-radius: 5px;

            background: #EAF6F8;

            color: #0D6E6E;

            display: flex;

            align-items: center;
            justify-content: center;

            font-size: 15px;
        }


        .panel-title {

            font-size: 12px;

            font-weight: 700;

            color: #0A4D68;
        }


        .panel-subtitle {

            font-size: 8px;

            color: #8CA5B0;

            margin-top: 2px;
        }


        .add-user {

            border: none;

            background: #0D6E6E;

            color: white;

            border-radius: 5px;

            padding:
                9px
                13px;

            font-size: 10px;

            font-weight: 700;

            cursor: pointer;
        }


        .add-user:hover {

            background: #066f76;
        }


        .panel-body {

            padding: 12px;
        }


        /* =====================================================
           FILTER
        ===================================================== */

        .filter-row {

            display: grid;

            grid-template-columns:
                1.7fr
                1fr
                1fr
                auto;

            gap: 8px;

            margin-bottom: 10px;
        }


        .filter-input,
        .filter-select {

            height: 35px;

            border:
                1px solid
                #D6E8EE;

            border-radius: 5px;

            padding:
                0
                10px;

            outline: none;

            font-size: 9px;

            color: #4f6d7b;

            background: white;
        }


        .filter-input:focus,
        .filter-select:focus {

            border-color: #66b6c1;
        }


        .btn-search {

            height: 35px;

            padding:
                0
                15px;

            border: none;

            border-radius: 5px;

            background: #0D6E6E;

            color: white;

            font-size: 9px;

            font-weight: 700;

            cursor: pointer;
        }


        /* =====================================================
           TABLE
        ===================================================== */

        .table-wrap {

            overflow-x: auto;
        }


        .account-table {

            width: 100%;

            border-collapse: collapse;

            table-layout: fixed;
        }


        .account-table th {

            background: #e9f4fa;

            color: #315E6E;

            font-size: 9px;

            font-weight: 700;

            padding:
                9px
                7px;

            text-align: left;
        }


        .account-table td {

            padding:
                9px
                7px;

            border-bottom:
                1px solid
                #E6F0F3;

            color: #315E6E;

            font-size: 9px;

            vertical-align: middle;
        }


        .account-table tbody tr {

            transition: 0.15s;
        }


        .account-table tbody tr:hover {

            background: #f6fbfc;
        }


        .account-table th:nth-child(1),
        .account-table td:nth-child(1) {

            width: 38px;

            text-align: center;
        }


        .account-table th:nth-child(2),
        .account-table td:nth-child(2) {

            width: 170px;
        }


        .account-table th:nth-child(3),
        .account-table td:nth-child(3) {

            width: 100px;
        }


        .account-table th:nth-child(4),
        .account-table td:nth-child(4) {

            width: 125px;
        }


        .account-table th:nth-child(5),
        .account-table td:nth-child(5) {

            width: 135px;
        }


        .account-table th:nth-child(6),
        .account-table td:nth-child(6) {

            width: 90px;
        }


        .account-table th:nth-child(7),
        .account-table td:nth-child(7) {

            width: 110px;
        }


        .account-table th:nth-child(8),
        .account-table td:nth-child(8) {

            width: 75px;

            text-align: center;
        }


        .user-name-cell {

            font-weight: 700;

            color: #0A4D68;
        }


        .username {

            color: #76909b;

            font-size: 8px;

            margin-top: 3px;
        }


        .role-badge {

            display: inline-block;

            background: #edf5f8;

            color: #17627c;

            padding:
                4px
                7px;

            border-radius: 4px;

            font-size: 8px;

            font-weight: 700;
        }


        .status {

            display: inline-block;

            padding:
                4px
                7px;

            border-radius: 4px;

            font-size: 8px;

            font-weight: 700;
        }


        .status.active {

            background: #dff7e9;

            color: #18845b;
        }


        .status.inactive {

            background: #ffe4e8;

            color: #d24b68;
        }


        .action-group {

            display: flex;

            align-items: center;

            justify-content: center;

            gap: 4px;
        }


        .action-btn {

            width: 28px;
            height: 25px;

            border:
                1px solid
                #d0e0e6;

            background: white;

            border-radius: 5px;

            cursor: pointer;

            color: #426a7b;

            font-size: 10px;
        }


        .action-btn:hover {

            background: #EAF6F8;
        }


        .action-btn.edit {

            color: #1768c2;
        }


        .action-btn.delete {

            color: #d24b68;
        }


        .table-bottom {

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

            gap: 5px;
        }


        .page-btn {

            width: 28px;
            height: 28px;

            border:
                1px solid
                #d5e4e9;

            background: white;

            color: #476978;

            border-radius: 5px;

            cursor: pointer;

            font-size: 9px;
        }


        .page-btn.active {

            background: #0D6E6E;

            color: white;

            border-color: #0D6E6E;
        }


        /* =====================================================
           MODAL
        ===================================================== */

        .modal {

            display: none;

            position: fixed;

            inset: 0;

            background:
                rgba(
                    0,
                    35,
                    50,
                    0.35
                );

            z-index: 100;

            align-items: center;

            justify-content: center;

            padding: 20px;
        }


        .modal.show {

            display: flex;
        }


        .modal-card {

            width: 520px;

            max-width: 100%;

            background: white;

            border-radius: 10px;

            box-shadow:
                0 15px 50px
                rgba(
                    0,
                    0,
                    0,
                    0.18
                );

            overflow: hidden;
        }


        .modal-header {

            display: flex;

            align-items: center;

            justify-content: space-between;

            padding:
                15px
                18px;

            border-bottom:
                1px solid
                #E6F0F3;
        }


        .modal-title {

            font-size: 15px;

            font-weight: 700;

            color: #073f60;
        }


        .modal-close {

            border: none;

            background: transparent;

            font-size: 20px;

            color: #6e8996;

            cursor: pointer;
        }


        .modal-body {

            padding: 18px;
        }


        .form-grid {

            display: grid;

            grid-template-columns:
                1fr
                1fr;

            gap: 12px;
        }


        .form-group {

            display: flex;

            flex-direction: column;

            gap: 5px;
        }


        .form-group.full {

            grid-column:
                1 /
                -1;
        }


        .form-group label {

            font-size: 9px;

            font-weight: 700;

            color: #66869A;
        }


        .form-group input,
        .form-group select {

            height: 36px;

            border:
                1px solid
                #D6E8EE;

            border-radius: 5px;

            padding:
                0
                10px;

            outline: none;

            font-size: 10px;

            color: #315E6E;
        }


        .modal-footer {

            display: flex;

            justify-content: flex-end;

            gap: 8px;

            padding:
                12px
                18px;

            border-top:
                1px solid
                #E6F0F3;
        }


        .btn-cancel {

            border:
                1px solid
                #C7DDE5;

            background: white;

            color: #66869A;

            border-radius: 5px;

            padding:
                9px
                15px;

            cursor: pointer;

            font-size: 9px;

            font-weight: 700;
        }


        .btn-save {

            border: none;

            background: #0D6E6E;

            color: white;

            border-radius: 5px;

            padding:
                9px
                15px;

            cursor: pointer;

            font-size: 9px;

            font-weight: 700;
        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 1100px) {

            .filter-row {

                grid-template-columns:
                    1fr
                    1fr;
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


            .stats-grid {

                grid-template-columns: 1fr;
            }


            .form-grid {

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

        <a
            href="kelolan_akun.php"
            class="active"
        >

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

                    <?= htmlspecialchars(
                        $userName
                    ) ?>

                </div>


                <div class="user-role">

                    <?= htmlspecialchars(
                        $userRole
                    ) ?>

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
            ⌂
        </span>


        <span>
            ›
        </span>


        <a href="dashboard_admin.php">
            Dashboard
        </a>


        <span>
            ›
        </span>


        <span class="current">
            Kelola Akun Pengguna
        </span>


    </div>


    <!-- =====================================================
         PAGE HEADER
    ====================================================== -->

    <div class="page-head">


        <div>


            <h1 class="page-title">
                Kelola Akun Pengguna
            </h1>


            <p class="page-subtitle">
                Kelola akun, role, status, dan informasi pengguna SIMRS
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
         STATISTICS
    ====================================================== -->

    <div class="stats-grid">


        <div class="stat-card">


            <div class="stat-info">


                <div class="stat-label">
                    Total Pengguna
                </div>


                <div class="stat-number">
                    <?= $totalUsers ?>
                </div>


                <div class="stat-description">
                    Seluruh akun pengguna
                </div>


            </div>


            <div class="stat-icon">
                ♟
            </div>


        </div>


        <div class="stat-card">


            <div class="stat-info">


                <div class="stat-label">
                    Akun Aktif
                </div>


                <div class="stat-number">
                    <?= $activeUsers ?>
                </div>


                <div class="stat-description">
                    Pengguna dapat mengakses sistem
                </div>


            </div>


            <div class="stat-icon">
                ✓
            </div>


        </div>


        <div class="stat-card">


            <div class="stat-info">


                <div class="stat-label">
                    Akun Nonaktif
                </div>


                <div class="stat-number">
                    <?= $inactiveUsers ?>
                </div>


                <div class="stat-description">
                    Akun yang tidak dapat login
                </div>


            </div>


            <div class="stat-icon">
                🔒
            </div>


        </div>


    </div>


    <!-- =====================================================
         ACCOUNT TABLE
    ====================================================== -->

    <section class="account-panel">


        <div class="panel-header">


            <div class="panel-header-left">


                <div class="panel-icon">
                    ♟
                </div>


                <div>


                    <div class="panel-title">
                        Daftar Akun Pengguna
                    </div>


                    <div class="panel-subtitle">
                        Kelola seluruh akun pengguna SIMRS
                    </div>


                </div>


            </div>


            <button
                class="add-user"
                onclick="openAddModal()"
            >

                ＋
                Tambah Pengguna

            </button>


        </div>


        <div class="panel-body">


            <!-- FILTER -->

            <div class="filter-row">


                <input
                    type="text"
                    id="userSearch"
                    class="filter-input"
                    placeholder="🔎 Cari nama, username, atau unit kerja..."
                >


                <select
                    id="roleFilter"
                    class="filter-select"
                >

                    <option value="">
                        Semua Role
                    </option>

                    <option value="Administrator">
                        Administrator
                    </option>

                    <option value="Dokter">
                        Dokter
                    </option>

                    <option value="Perawat">
                        Perawat
                    </option>

                    <option value="Petugas Registrasi">
                        Petugas Registrasi
                    </option>

                    <option value="Petugas Laboratorium">
                        Petugas Laboratorium
                    </option>

                    <option value="Petugas Radiologi">
                        Petugas Radiologi
                    </option>

                    <option value="Petugas Farmasi">
                        Petugas Farmasi
                    </option>

                    <option value="Manajer">
                        Manajer
                    </option>

                </select>


                <select
                    id="statusFilter"
                    class="filter-select"
                >

                    <option value="">
                        Semua Status
                    </option>

                    <option value="Aktif">
                        Aktif
                    </option>

                    <option value="Nonaktif">
                        Nonaktif
                    </option>

                </select>


                <button
                    class="btn-search"
                    onclick="filterUsers()"
                >
                    Cari
                </button>


            </div>


            <!-- TABLE -->

            <div class="table-wrap">


                <table class="account-table">


                    <thead>

                        <tr>


                            <th>
                                No.
                            </th>


                            <th>
                                Nama Pengguna
                            </th>


                            <th>
                                Username
                            </th>


                            <th>
                                Role
                            </th>


                            <th>
                                Unit Kerja
                            </th>


                            <th>
                                Status
                            </th>


                            <th>
                                Login Terakhir
                            </th>


                            <th>
                                Aksi
                            </th>


                        </tr>

                    </thead>


                    <tbody id="userTableBody">


                        <?php foreach (
                            $users
                            as $user
                        ): ?>


                            <tr
                                data-name="<?= htmlspecialchars(
                                    strtolower(
                                        $user['name']
                                    )
                                ) ?>"
                                data-username="<?= htmlspecialchars(
                                    strtolower(
                                        $user['username']
                                    )
                                ) ?>"
                                data-role="<?= htmlspecialchars(
                                    $user['role']
                                ) ?>"
                                data-status="<?= htmlspecialchars(
                                    $user['status']
                                ) ?>"
                                data-unit="<?= htmlspecialchars(
                                    strtolower(
                                        $user['unit']
                                    )
                                ) ?>"
                            >


                                <td>
                                    <?= $user['id'] ?>
                                </td>


                                <td>


                                    <div class="user-name-cell">

                                        <?= htmlspecialchars(
                                            $user['name']
                                        ) ?>

                                    </div>


                                    <div class="username">

                                        <?= htmlspecialchars(
                                            $user['email']
                                        ) ?>

                                    </div>


                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $user['username']
                                    ) ?>

                                </td>


                                <td>


                                    <span class="role-badge">

                                        <?= htmlspecialchars(
                                            $user['role']
                                        ) ?>

                                    </span>


                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $user['unit']
                                    ) ?>

                                </td>


                                <td>


                                    <span
                                        class="status
                                        <?= $user['status'] === 'Aktif'
                                            ? 'active'
                                            : 'inactive'
                                        ?>"
                                    >

                                        ●

                                        <?= htmlspecialchars(
                                            $user['status']
                                        ) ?>

                                    </span>


                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $user['last_login']
                                    ) ?>

                                </td>


                                <td>


                                    <div class="action-group">


                                        <button
                                            type="button"
                                            class="action-btn edit"
                                            title="Edit"
                                            onclick="editUser(
                                                <?= $user['id'] ?>
                                            )"
                                        >
                                            ✎
                                        </button>


                                        <button
                                            type="button"
                                            class="action-btn"
                                            title="Detail"
                                            onclick="viewUser(
                                                <?= $user['id'] ?>
                                            )"
                                        >
                                            👁
                                        </button>


                                    </div>


                                </td>


                            </tr>


                        <?php endforeach; ?>


                    </tbody>


                </table>


            </div>


            <!-- TABLE BOTTOM -->

            <div class="table-bottom">


                <div
                    class="table-info"
                    id="tableInfo"
                >

                    Menampilkan
                    <?= $totalUsers ?>
                    dari
                    <?= $totalUsers ?>
                    pengguna

                </div>


                <div class="pagination">


                    <button
                        class="page-btn"
                        onclick="previousPage()"
                    >
                        ‹
                    </button>


                    <button
                        class="page-btn active"
                    >
                        1
                    </button>


                    <button
                        class="page-btn"
                        onclick="nextPage()"
                    >
                        ›
                    </button>


                </div>


            </div>


        </div>


    </section>


</main>


<!-- =========================================================
     MODAL TAMBAH PENGGUNA
========================================================= -->

<div
    class="modal"
    id="userModal"
>


    <div class="modal-card">


        <div class="modal-header">


            <div class="modal-title">
                Tambah Akun Pengguna
            </div>


            <button
                class="modal-close"
                onclick="closeModal()"
            >
                ×
            </button>


        </div>


        <div class="modal-body">


            <div class="form-grid">


                <div class="form-group">

                    <label>
                        Nama Lengkap
                    </label>

                    <input
                        type="text"
                        id="formName"
                        placeholder="Masukkan nama lengkap"
                    >

                </div>


                <div class="form-group">

                    <label>
                        Username
                    </label>

                    <input
                        type="text"
                        id="formUsername"
                        placeholder="Masukkan username"
                    >

                </div>


                <div class="form-group">

                    <label>
                        Role
                    </label>

                    <select id="formRole">

                        <option>
                            Administrator
                        </option>

                        <option>
                            Dokter
                        </option>

                        <option>
                            Perawat
                        </option>

                        <option>
                            Petugas Registrasi
                        </option>

                        <option>
                            Petugas Laboratorium
                        </option>

                        <option>
                            Petugas Radiologi
                        </option>

                        <option>
                            Petugas Farmasi
                        </option>

                        <option>
                            Manajer
                        </option>

                    </select>

                </div>


                <div class="form-group">

                    <label>
                        Unit Kerja
                    </label>

                    <input
                        type="text"
                        id="formUnit"
                        placeholder="Masukkan unit kerja"
                    >

                </div>


                <div class="form-group">

                    <label>
                        Email
                    </label>

                    <input
                        type="email"
                        id="formEmail"
                        placeholder="Masukkan email"
                    >

                </div>


                <div class="form-group">

                    <label>
                        No. Telepon
                    </label>

                    <input
                        type="text"
                        id="formPhone"
                        placeholder="Masukkan nomor telepon"
                    >

                </div>


                <div class="form-group full">

                    <label>
                        Password
                    </label>

                    <input
                        type="password"
                        id="formPassword"
                        placeholder="Masukkan password awal"
                    >

                </div>


            </div>


        </div>


        <div class="modal-footer">


            <button
                class="btn-cancel"
                onclick="closeModal()"
            >
                Batal
            </button>


            <button
                class="btn-save"
                onclick="saveUser()"
            >
                Simpan Pengguna
            </button>


        </div>


    </div>


</div>


<script>

/* =========================================================
   DATA USER
========================================================= */

const userData =
    <?= json_encode(
        $users,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    ) ?>;


/* =========================================================
   FILTER USER
========================================================= */

function filterUsers() {


    const search =
        document
            .getElementById('userSearch')
            .value
            .toLowerCase()
            .trim();


    const role =
        document
            .getElementById('roleFilter')
            .value;


    const status =
        document
            .getElementById('statusFilter')
            .value;


    let visible = 0;


    document
        .querySelectorAll(
            '#userTableBody tr'
        )
        .forEach(row => {


            const name =
                row.dataset.name;


            const username =
                row.dataset.username;


            const unit =
                row.dataset.unit;


            const rowRole =
                row.dataset.role;


            const rowStatus =
                row.dataset.status;


            const matchSearch =
                !search ||
                name.includes(search) ||
                username.includes(search) ||
                unit.includes(search);


            const matchRole =
                !role ||
                rowRole === role;


            const matchStatus =
                !status ||
                rowStatus === status;


            const show =
                matchSearch &&
                matchRole &&
                matchStatus;


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

        'Menampilkan ' +
        visible +
        ' dari ' +
        userData.length +
        ' pengguna';

}


/* =========================================================
   LIVE SEARCH
========================================================= */

document
    .getElementById('userSearch')
    .addEventListener(
        'input',
        filterUsers
    );


document
    .getElementById('roleFilter')
    .addEventListener(
        'change',
        filterUsers
    );


document
    .getElementById('statusFilter')
    .addEventListener(
        'change',
        filterUsers
    );


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
   MODAL
========================================================= */

function openAddModal() {

    document
        .getElementById('userModal')
        .classList.add('show');

}


function closeModal() {

    document
        .getElementById('userModal')
        .classList.remove('show');

}


/* =========================================================
   SAVE USER DEMO
========================================================= */

function saveUser() {


    const name =
        document
            .getElementById('formName')
            .value
            .trim();


    const username =
        document
            .getElementById('formUsername')
            .value
            .trim();


    const role =
        document
            .getElementById('formRole')
            .value;


    if (
        name === '' ||
        username === ''
    ) {

        alert(
            'Nama lengkap dan username wajib diisi.'
        );

        return;

    }


    alert(
        'Akun pengguna berhasil ditambahkan.\\n\\n' +
        'Nama: ' +
        name +
        '\\n' +
        'Username: ' +
        username +
        '\\n' +
        'Role: ' +
        role
    );


    closeModal();

}


/* =========================================================
   DETAIL USER
========================================================= */

function viewUser(id) {


    const user =
        userData.find(
            item => item.id === id
        );


    if (!user) {

        return;

    }


    alert(

        'DETAIL AKUN PENGGUNA\\n\\n' +

        'Nama: ' +
        user.name +
        '\\n' +

        'Username: ' +
        user.username +
        '\\n' +

        'Role: ' +
        user.role +
        '\\n' +

        'Unit Kerja: ' +
        user.unit +
        '\\n' +

        'Email: ' +
        user.email +
        '\\n' +

        'No. Telepon: ' +
        user.phone +
        '\\n' +

        'Status: ' +
        user.status +
        '\\n' +

        'Login Terakhir: ' +
        user.last_login

    );

}


/* =========================================================
   EDIT USER
========================================================= */

function editUser(id) {


    const user =
        userData.find(
            item => item.id === id
        );


    if (!user) {

        return;

    }


    alert(

        'Edit akun pengguna:\\n\\n' +

        user.name +
        '\\n' +

        'Username: ' +
        user.username +
        '\\n' +

        'Role: ' +
        user.role

    );

}


/* =========================================================
   PAGINATION DEMO
========================================================= */

function previousPage() {

    alert(
        'Ini adalah halaman pertama.'
    );

}


function nextPage() {

    alert(
        'Halaman berikutnya belum tersedia pada demo.'
    );

}


/* =========================================================
   CLOSE MODAL WHEN CLICK OUTSIDE
========================================================= */

document
    .getElementById('userModal')
    .addEventListener(
        'click',
        function(event) {


            if (
                event.target === this
            ) {

                closeModal();

            }

        }
    );

</script>


</body>

</html>