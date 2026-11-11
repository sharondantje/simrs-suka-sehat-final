<?php

session_start();


/* =========================================================
   DATA ADMIN
========================================================= */

$userName = 'Herlambang';

$userRole = 'Administrator';


/* =========================================================
   DATA PENGATURAN
========================================================= */

$settings = [

    'hospital_name' => 'RS Suka Sehat',

    'hospital_code' => 'RSSS',

    'hospital_email' => 'info@rs-sukasehat.com',

    'hospital_phone' => '021-12345678',

    'timezone' => 'Asia/Jakarta',

    'language' => 'Bahasa Indonesia',

    'date_format' => 'DD-MM-YYYY',

    'session_timeout' => '30',

    'login_notification' => true,

    'security_notification' => true,

    'email_notification' => true,

    'maintenance_mode' => false

];

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
        Pengaturan - RS Suka Sehat
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
           SETTINGS GRID
        ===================================================== */

        .settings-grid {

            display: grid;

            grid-template-columns:
                1.15fr
                0.85fr;

            gap: 10px;

            margin-top: 18px;

        }


        /* =====================================================
           PANEL
        ===================================================== */

        .panel {

            background: white;

            border:
                1px solid
                #D6E8EE;

            border-radius: 7px;

            overflow: hidden;

        }


        .panel-header {

            padding:
                11px
                13px;

            display: flex;

            align-items: center;

            gap: 9px;

            border-bottom:
                1px solid
                #e0ebef;

            background: #fbfdfe;

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


        .panel-body {

            padding: 14px;

        }


        /* =====================================================
           FORM
        ===================================================== */

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

            color: #66869A;

            font-size: 9px;

            font-weight: 700;

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

            color: #315E6E;

            font-size: 10px;

            background: white;

        }


        .form-group input:focus,
        .form-group select:focus {

            border-color: #66b6c1;

        }


        .form-help {

            color: #8aa0aa;

            font-size: 8px;

        }


        /* =====================================================
           PROFILE CARD
        ===================================================== */

        .profile-card {

            display: flex;

            align-items: center;

            gap: 12px;

            padding-bottom: 14px;

            margin-bottom: 14px;

            border-bottom:
                1px solid
                #E6F0F3;

        }


        .profile-avatar {

            width: 55px;

            height: 55px;

            border-radius: 50%;

            background: #0A4D68;

            color: white;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 22px;

            font-weight: 700;

        }


        .profile-name {

            font-size: 15px;

            font-weight: 700;

            color: #073f60;

        }


        .profile-role {

            margin-top: 4px;

            font-size: 9px;

            color: #6b8996;

        }


        .profile-status {

            display: inline-block;

            margin-top: 5px;

            background: #dff7e9;

            color: #18845b;

            padding:
                4px
                7px;

            border-radius: 4px;

            font-size: 8px;

            font-weight: 700;

        }


        /* =====================================================
           SECURITY
        ===================================================== */

        .security-item {

            display: flex;

            align-items: center;

            justify-content: space-between;

            padding:
                11px
                0;

            border-bottom:
                1px solid
                #E6F0F3;

        }


        .security-item:last-child {

            border-bottom: none;

        }


        .security-info {

            display: flex;

            flex-direction: column;

            gap: 3px;

        }


        .security-title {

            font-size: 10px;

            font-weight: 700;

            color: #315E6E;

        }


        .security-description {

            font-size: 8px;

            color: #8CA5B0;

        }


        /* =====================================================
           TOGGLE
        ===================================================== */

        .toggle {

            position: relative;

            width: 38px;

            height: 20px;

            flex-shrink: 0;

        }


        .toggle input {

            opacity: 0;

            width: 0;

            height: 0;

        }


        .slider {

            position: absolute;

            inset: 0;

            cursor: pointer;

            background: #C7DDE5;

            border-radius: 20px;

            transition: 0.2s;

        }


        .slider:before {

            content: "";

            position: absolute;

            width: 14px;

            height: 14px;

            left: 3px;

            top: 3px;

            background: white;

            border-radius: 50%;

            transition: 0.2s;

            box-shadow:
                0 1px 3px
                rgba(
                    0,
                    0,
                    0,
                    0.2
                );

        }


        .toggle input:checked + .slider {

            background: #0D6E6E;

        }


        .toggle input:checked + .slider:before {

            transform:
                translateX(18px);

        }


        /* =====================================================
           BUTTONS
        ===================================================== */

        .button-row {

            display: flex;

            justify-content: flex-end;

            gap: 8px;

            margin-top: 15px;

            padding-top: 12px;

            border-top:
                1px solid
                #E6F0F3;

        }


        .btn {

            border-radius: 5px;

            padding:
                9px
                15px;

            font-size: 9px;

            font-weight: 700;

            cursor: pointer;

        }


        .btn-secondary {

            border:
                1px solid
                #C7DDE5;

            background: white;

            color: #66869A;

        }


        .btn-primary {

            border: none;

            background: #0D6E6E;

            color: white;

        }


        .btn-primary:hover {

            background: #066f76;

        }


        /* =====================================================
           SYSTEM STATUS
        ===================================================== */

        .system-status {

            margin-top: 10px;

        }


        .status-row {

            display: flex;

            align-items: center;

            justify-content: space-between;

            padding:
                10px
                0;

            border-bottom:
                1px solid
                #E6F0F3;

        }


        .status-row:last-child {

            border-bottom: none;

        }


        .status-left {

            display: flex;

            align-items: center;

            gap: 8px;

        }


        .status-dot {

            width: 8px;

            height: 8px;

            border-radius: 50%;

            background: #20a66a;

        }


        .status-text {

            font-size: 9px;

            color: #496b7a;

        }


        .status-value {

            font-size: 9px;

            font-weight: 700;

            color: #18845b;

        }


        /* =====================================================
           DANGER ZONE
        ===================================================== */

        .danger-panel {

            margin-top: 10px;

            border:
                1px solid
                #f0ccd3;

            background: #fffafb;

        }


        .danger-panel .panel-header {

            background: #fff7f8;

        }


        .danger-icon {

            background: #ffe4e8;

            color: #d24b68;

        }


        .danger-text {

            color: #8b5660;

            font-size: 9px;

            line-height: 1.5;

        }


        .btn-danger {

            margin-top: 10px;

            border:
                1px solid
                #e58b9a;

            background: white;

            color: #d24b68;

            border-radius: 5px;

            padding:
                8px
                12px;

            font-size: 9px;

            font-weight: 700;

            cursor: pointer;

        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 1000px) {

            .settings-grid {

                grid-template-columns: 1fr;

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


            .form-grid {

                grid-template-columns: 1fr;

            }


            .form-group.full {

                grid-column: auto;

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

        <a href="log_aktivitas.php">

            <span class="menu-icon">
                ◷
            </span>

            <span>
                Log Aktivitas
            </span>

        </a>


        <!-- PENGATURAN -->

        <a
            href="pengaturan_admin.php"
            class="active"
        >

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
            ⚙
        </span>


        <a href="dashboard_admin.php">
            Dashboard
        </a>


        <span>
            &gt;
        </span>


        <span class="current">
            Pengaturan
        </span>


    </div>


    <!-- =====================================================
         PAGE HEADER
    ====================================================== -->

    <div class="page-head">


        <div>


            <h1 class="page-title">
                Pengaturan
            </h1>


            <p class="page-subtitle">
                Kelola pengaturan sistem dan informasi RS Suka Sehat
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
         SETTINGS GRID
    ====================================================== -->

    <div class="settings-grid">


        <!-- =================================================
             LEFT COLUMN
        ================================================== -->

        <div>


            <!-- INFORMASI RUMAH SAKIT -->

            <section class="panel">


                <div class="panel-header">


                    <div class="panel-icon">
                        🏥
                    </div>


                    <div>


                        <div class="panel-title">
                            Informasi Rumah Sakit
                        </div>


                        <div class="panel-subtitle">
                            Informasi dasar yang digunakan oleh sistem
                        </div>


                    </div>


                </div>


                <div class="panel-body">


                    <div class="form-grid">


                        <div class="form-group full">


                            <label>
                                Nama Rumah Sakit
                            </label>


                            <input
                                type="text"
                                id="hospitalName"
                                value="<?= htmlspecialchars(
                                    $settings['hospital_name']
                                ) ?>"
                            >


                        </div>


                        <div class="form-group">


                            <label>
                                Kode Rumah Sakit
                            </label>


                            <input
                                type="text"
                                id="hospitalCode"
                                value="<?= htmlspecialchars(
                                    $settings['hospital_code']
                                ) ?>"
                            >


                        </div>


                        <div class="form-group">


                            <label>
                                Nomor Telepon
                            </label>


                            <input
                                type="text"
                                id="hospitalPhone"
                                value="<?= htmlspecialchars(
                                    $settings['hospital_phone']
                                ) ?>"
                            >


                        </div>


                        <div class="form-group full">


                            <label>
                                Email Rumah Sakit
                            </label>


                            <input
                                type="email"
                                id="hospitalEmail"
                                value="<?= htmlspecialchars(
                                    $settings['hospital_email']
                                ) ?>"
                            >


                        </div>


                    </div>


                </div>


            </section>


            <!-- PENGATURAN SISTEM -->

            <section
                class="panel"
                style="margin-top: 10px;"
            >


                <div class="panel-header">


                    <div class="panel-icon">
                        ⚙
                    </div>


                    <div>


                        <div class="panel-title">
                            Pengaturan Sistem
                        </div>


                        <div class="panel-subtitle">
                            Konfigurasi umum SIMRS
                        </div>


                    </div>


                </div>


                <div class="panel-body">


                    <div class="form-grid">


                        <div class="form-group">


                            <label>
                                Zona Waktu
                            </label>


                            <select id="timezone">


                                <option
                                    value="Asia/Jakarta"
                                    selected
                                >
                                    Asia/Jakarta (WIB)
                                </option>


                                <option value="Asia/Makassar">
                                    Asia/Makassar (WITA)
                                </option>


                                <option value="Asia/Jayapura">
                                    Asia/Jayapura (WIT)
                                </option>


                            </select>


                        </div>


                        <div class="form-group">


                            <label>
                                Bahasa
                            </label>


                            <select id="language">


                                <option selected>
                                    Bahasa Indonesia
                                </option>


                                <option>
                                    English
                                </option>


                            </select>


                        </div>


                        <div class="form-group">


                            <label>
                                Format Tanggal
                            </label>


                            <select id="dateFormat">


                                <option selected>
                                    DD-MM-YYYY
                                </option>


                                <option>
                                    DD/MM/YYYY
                                </option>


                                <option>
                                    YYYY-MM-DD
                                </option>


                            </select>


                        </div>


                        <div class="form-group">


                            <label>
                                Session Timeout
                            </label>


                            <select id="sessionTimeout">


                                <option>
                                    15 menit
                                </option>


                                <option selected>
                                    30 menit
                                </option>


                                <option>
                                    60 menit
                                </option>


                                <option>
                                    120 menit
                                </option>


                            </select>


                        </div>


                    </div>


                    <div class="button-row">


                        <button
                            class="btn btn-secondary"
                            onclick="resetSystemSettings()"
                        >
                            Reset
                        </button>


                        <button
                            class="btn btn-primary"
                            onclick="saveSystemSettings()"
                        >
                            Simpan Perubahan
                        </button>


                    </div>


                </div>


            </section>


        </div>


        <!-- =================================================
             RIGHT COLUMN
        ================================================== -->

        <div>


            <!-- PROFIL ADMIN -->

            <section class="panel">


                <div class="panel-header">


                    <div class="panel-icon">
                        ♟
                    </div>


                    <div>


                        <div class="panel-title">
                            Profil Administrator
                        </div>


                        <div class="panel-subtitle">
                            Informasi akun yang sedang digunakan
                        </div>


                    </div>


                </div>


                <div class="panel-body">


                    <div class="profile-card">


                        <div class="profile-avatar">
                            H
                        </div>


                        <div>


                            <div class="profile-name">
                                Herlambang
                            </div>


                            <div class="profile-role">
                                Administrator
                            </div>


                            <span class="profile-status">
                                ● Aktif
                            </span>


                        </div>


                    </div>


                    <div class="form-grid">


                        <div class="form-group full">


                            <label>
                                Nama Lengkap
                            </label>


                            <input
                                type="text"
                                value="Herlambang"
                                id="adminName"
                            >


                        </div>


                        <div class="form-group full">


                            <label>
                                Username
                            </label>


                            <input
                                type="text"
                                value="admin"
                                disabled
                            >


                        </div>


                        <div class="form-group full">


                            <label>
                                Email
                            </label>


                            <input
                                type="email"
                                value="admin@rs-sukasehat.com"
                                id="adminEmail"
                            >


                        </div>


                    </div>


                    <div class="button-row">


                        <button
                            class="btn btn-primary"
                            onclick="saveProfile()"
                        >
                            Simpan Profil
                        </button>


                    </div>


                </div>


            </section>


            <!-- KEAMANAN -->

            <section
                class="panel"
                style="margin-top: 10px;"
            >


                <div class="panel-header">


                    <div class="panel-icon">
                        🔒
                    </div>


                    <div>


                        <div class="panel-title">
                            Keamanan & Notifikasi
                        </div>


                        <div class="panel-subtitle">
                            Atur pemberitahuan dan keamanan sistem
                        </div>


                    </div>


                </div>


                <div class="panel-body">


                    <div class="security-item">


                        <div class="security-info">


                            <div class="security-title">
                                Notifikasi Login
                            </div>


                            <div class="security-description">
                                Beri tahu admin saat ada login pengguna
                            </div>


                        </div>


                        <label class="toggle">


                            <input
                                type="checkbox"
                                checked
                                id="loginNotification"
                            >


                            <span class="slider"></span>


                        </label>


                    </div>


                    <div class="security-item">


                        <div class="security-info">


                            <div class="security-title">
                                Peringatan Keamanan
                            </div>


                            <div class="security-description">
                                Notifikasi aktivitas yang perlu ditinjau
                            </div>


                        </div>


                        <label class="toggle">


                            <input
                                type="checkbox"
                                checked
                                id="securityNotification"
                            >


                            <span class="slider"></span>


                        </label>


                    </div>


                    <div class="security-item">


                        <div class="security-info">


                            <div class="security-title">
                                Notifikasi Email
                            </div>


                            <div class="security-description">
                                Kirim pemberitahuan penting melalui email
                            </div>


                        </div>


                        <label class="toggle">


                            <input
                                type="checkbox"
                                checked
                                id="emailNotification"
                            >


                            <span class="slider"></span>


                        </label>


                    </div>


                </div>


            </section>


            <!-- STATUS SISTEM -->

            <section
                class="panel system-status"
            >


                <div class="panel-header">


                    <div class="panel-icon">
                        🛡
                    </div>


                    <div>


                        <div class="panel-title">
                            Status Sistem
                        </div>


                        <div class="panel-subtitle">
                            Kondisi layanan SIMRS
                        </div>


                    </div>


                </div>


                <div class="panel-body">


                    <div class="status-row">


                        <div class="status-left">


                            <span class="status-dot"></span>


                            <span class="status-text">
                                SIMRS
                            </span>


                        </div>


                        <span class="status-value">
                            Normal
                        </span>


                    </div>


                    <div class="status-row">


                        <div class="status-left">


                            <span class="status-dot"></span>


                            <span class="status-text">
                                Database
                            </span>


                        </div>


                        <span class="status-value">
                            Terhubung
                        </span>


                    </div>


                    <div class="status-row">


                        <div class="status-left">


                            <span class="status-dot"></span>


                            <span class="status-text">
                                Storage
                            </span>


                        </div>


                        <span class="status-value">
                            Normal
                        </span>


                    </div>


                </div>


            </section>


        </div>


    </div>


    <!-- =====================================================
         DANGER ZONE
    ====================================================== -->

    <section class="panel danger-panel">


        <div class="panel-header">


            <div class="panel-icon danger-icon">
                ⚠
            </div>


            <div>


                <div class="panel-title">
                    Zona Berbahaya
                </div>


                <div class="panel-subtitle">
                    Pengaturan yang memengaruhi akses seluruh sistem
                </div>


            </div>


        </div>


        <div class="panel-body">


            <div class="security-item">


                <div class="security-info">


                    <div class="security-title">
                        Mode Pemeliharaan Sistem
                    </div>


                    <div class="danger-text">
                        Jika diaktifkan, pengguna biasa tidak dapat
                        mengakses SIMRS selama proses pemeliharaan.
                    </div>


                </div>


                <label class="toggle">


                    <input
                        type="checkbox"
                        id="maintenanceMode"
                    >


                    <span class="slider"></span>


                </label>


            </div>


            <button
                class="btn-danger"
                onclick="confirmMaintenance()"
            >
                ⚠ Simpan Pengaturan Zona Berbahaya
            </button>


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
   SAVE SYSTEM SETTINGS
========================================================= */

function saveSystemSettings()
{

    alert(
        'Pengaturan sistem berhasil disimpan.'
    );

}


/* =========================================================
   RESET SYSTEM SETTINGS
========================================================= */

function resetSystemSettings()
{

    const confirmReset =
        confirm(
            'Apakah Anda yakin ingin mereset pengaturan?'
        );


    if (!confirmReset) {

        return;

    }


    document
        .getElementById('timezone')
        .value =
        'Asia/Jakarta';


    document
        .getElementById('language')
        .value =
        'Bahasa Indonesia';


    document
        .getElementById('dateFormat')
        .value =
        'DD-MM-YYYY';


    document
        .getElementById('sessionTimeout')
        .value =
        '30 menit';


    alert(
        'Pengaturan dikembalikan ke nilai default.'
    );

}


/* =========================================================
   SAVE PROFILE
========================================================= */

function saveProfile()
{

    const name =
        document
            .getElementById('adminName')
            .value
            .trim();


    const email =
        document
            .getElementById('adminEmail')
            .value
            .trim();


    if (
        name === '' ||
        email === ''
    ) {

        alert(
            'Nama dan email wajib diisi.'
        );

        return;

    }


    alert(
        'Profil Administrator berhasil disimpan.'
    );

}


/* =========================================================
   MAINTENANCE MODE
========================================================= */

function confirmMaintenance()
{

    const maintenance =
        document
            .getElementById('maintenanceMode')
            .checked;


    if (maintenance) {

        const confirmAction =
            confirm(
                'Mode pemeliharaan akan mengubah akses pengguna lain. Lanjutkan?'
            );


        if (!confirmAction) {

            document
                .getElementById(
                    'maintenanceMode'
                )
                .checked = false;

            return;

        }

    }


    alert(
        maintenance
            ? 'Mode pemeliharaan diaktifkan.'
            : 'Mode pemeliharaan dinonaktifkan.'
    );

}

</script>


</body>

</html>