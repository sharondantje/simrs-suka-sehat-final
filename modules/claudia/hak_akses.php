<?php
session_start();

/*
 * HAK AKSES ADMIN
 * Bisa dibuka langsung untuk demo UI.
 */

$userName = $_SESSION['user']['name'] ?? 'Administrator';
$userRole = $_SESSION['user']['role_label'] ?? 'Administrator';


/* =========================================================
   DATA ROLE DEMO
========================================================= */

$roles = [

    [
        'id' => 1,
        'name' => 'Administrator',
        'description' => 'Akses penuh ke seluruh modul sistem',
        'users' => 3,
        'status' => 'Aktif',

        'permissions' => [

            'Pendaftaran Pasien' => [
                'Lihat Data Pasien',
                'Tambah Pasien',
                'Ubah Data Pasien',
                'Hapus Data Pasien'
            ],

            'Rawat Jalan' => [
                'Lihat Data Kunjungan',
                'Input Tindakan Medis',
                'Input Resep Obat',
                'Hapus Kunjungan'
            ],

            'Rawat Inap' => [
                'Lihat Data Rawat Inap',
                'Kelola Data Rawat Inap'
            ],

            'Laboratorium' => [
                'Lihat Hasil Laboratorium',
                'Kelola Pemeriksaan Laboratorium'
            ],

            'Radiologi' => [
                'Lihat Hasil Radiologi',
                'Kelola Pemeriksaan Radiologi'
            ],

            'Farmasi' => [
                'Lihat Stok Obat',
                'Kelola Obat',
                'Kelola Resep'
            ],

            'Laporan' => [
                'Lihat Laporan',
                'Export Laporan'
            ]

        ]
    ],

    [
        'id' => 2,
        'name' => 'Dokter',
        'description' => 'Akses modul pelayanan medis',
        'users' => 28,
        'status' => 'Aktif',

        'permissions' => [

            'Pendaftaran Pasien' => [
                'Lihat Data Pasien'
            ],

            'Rawat Jalan' => [
                'Lihat Data Kunjungan',
                'Input Tindakan Medis',
                'Input Resep Obat'
            ],

            'Rawat Inap' => [
                'Lihat Data Rawat Inap',
                'Input Tindakan Medis'
            ],

            'Laboratorium' => [
                'Lihat Hasil Laboratorium'
            ],

            'Radiologi' => [
                'Lihat Hasil Radiologi'
            ],

            'Farmasi' => [
                'Lihat Resep Obat'
            ],

            'Laporan' => [
                'Lihat Laporan'
            ]

        ]
    ],

    [
        'id' => 3,
        'name' => 'Perawat',
        'description' => 'Akses modul keperawatan',
        'users' => 34,
        'status' => 'Aktif',

        'permissions' => [

            'Pendaftaran Pasien' => [
                'Lihat Data Pasien'
            ],

            'Rawat Jalan' => [
                'Lihat Data Kunjungan',
                'Input Tindakan Keperawatan'
            ],

            'Rawat Inap' => [
                'Lihat Data Rawat Inap',
                'Input Tindakan Keperawatan'
            ],

            'Laboratorium' => [
                'Lihat Hasil Laboratorium'
            ],

            'Radiologi' => [
                'Lihat Hasil Radiologi'
            ]

        ]
    ],

    [
        'id' => 4,
        'name' => 'Petugas Registrasi',
        'description' => 'Akses modul pendaftaran pasien',
        'users' => 18,
        'status' => 'Aktif',

        'permissions' => [

            'Pendaftaran Pasien' => [
                'Lihat Data Pasien',
                'Tambah Pasien',
                'Ubah Data Pasien'
            ],

            'Rawat Jalan' => [
                'Lihat Data Kunjungan'
            ]

        ]
    ],

    [
        'id' => 5,
        'name' => 'Petugas Laboratorium',
        'description' => 'Akses modul laboratorium',
        'users' => 12,
        'status' => 'Aktif',

        'permissions' => [

            'Laboratorium' => [
                'Lihat Pemeriksaan',
                'Input Hasil Laboratorium',
                'Validasi Hasil'
            ]

        ]
    ],

    [
        'id' => 6,
        'name' => 'Petugas Radiologi',
        'description' => 'Akses modul radiologi',
        'users' => 10,
        'status' => 'Aktif',

        'permissions' => [

            'Radiologi' => [
                'Lihat Order Pasien',
                'Kelola Pemeriksaan',
                'Input Catatan Radiografer',
                'Lihat Hasil Radiologi'
            ]

        ]
    ],

    [
        'id' => 7,
        'name' => 'Petugas Farmasi',
        'description' => 'Akses modul farmasi',
        'users' => 9,
        'status' => 'Aktif',

        'permissions' => [

            'Farmasi' => [
                'Lihat Resep',
                'Proses Resep',
                'Kelola Stok Obat'
            ]

        ]
    ],

    [
        'id' => 8,
        'name' => 'Manajer',
        'description' => 'Akses laporan dan manajemen data',
        'users' => 5,
        'status' => 'Nonaktif',

        'permissions' => [

            'Laporan' => [
                'Lihat Laporan',
                'Export Laporan'
            ]

        ]
    ]

];


$selectedRoleId = isset($_GET['role'])
    ? (int) $_GET['role']
    : 2;


$selectedRole = $roles[1];


foreach ($roles as $role) {

    if ($role['id'] === $selectedRoleId) {

        $selectedRole = $role;

        break;
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
        Hak Akses - RS Suka Sehat
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
            font-family: Arial, Helvetica, sans-serif;
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

            background: rgba(255, 255, 255, 0.10);
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

            padding: 0 30px 35px;
        }


        /* =====================================================
           TOPBAR
        ===================================================== */

        .topbar {

            height: 65px;

            margin-top: 18px;

            background: white;

            border: 1px solid #D6E8EE;

            border-radius: 10px;

            display: flex;

            align-items: center;

            justify-content: space-between;

            padding: 0 18px;

            box-shadow:
                0 2px 7px rgba(30, 70, 90, 0.04);
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

            margin: 4px 0 0;

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
           ADD ROLE
        ===================================================== */

        .add-role {

            margin-top: 8px;

            display: flex;

            justify-content: flex-end;
        }


        .btn-add {

            border: none;

            background: #0D6E6E;

            color: white;

            padding: 10px 17px;

            border-radius: 6px;

            font-weight: 700;

            cursor: pointer;

            font-size: 11px;
        }


        /* =====================================================
           CONTENT GRID
        ===================================================== */

        .content-grid {

            display: grid;

            grid-template-columns:
                1.55fr
                0.85fr;

            gap: 10px;

            margin-top: 8px;
        }


        /* =====================================================
           PANEL
        ===================================================== */

        .panel {

            background: white;

            border: 1px solid #D6E8EE;

            border-radius: 7px;

            overflow: hidden;
        }


        .panel-header {

            padding: 10px 12px;

            display: flex;

            align-items: center;

            gap: 9px;

            border-bottom: 1px solid #e0ebef;

            background: #fbfdfe;
        }


        .panel-icon {

            width: 28px;
            height: 28px;

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


        .panel-body {

            padding: 10px;
        }


        /* =====================================================
           TOOLBAR
        ===================================================== */

        .toolbar {

            display: flex;

            gap: 8px;

            margin-bottom: 8px;
        }


        .search-box {

            flex: 1;

            height: 34px;

            border: 1px solid #D6E8EE;

            border-radius: 5px;

            padding: 0 10px;

            outline: none;

            font-size: 10px;
        }


        .status-filter {

            width: 110px;

            height: 34px;

            border: 1px solid #D6E8EE;

            border-radius: 5px;

            padding: 0 8px;

            outline: none;

            font-size: 10px;

            color: #456575;
        }


        /* =====================================================
           ROLE TABLE
        ===================================================== */

        .table-wrap {

            overflow-x: auto;
        }


        .role-table {

            width: 100%;

            border-collapse: collapse;

            table-layout: fixed;
        }


        .role-table th {

            background: #e9f4fa;

            color: #315E6E;

            font-size: 9px;

            font-weight: 700;

            padding: 8px 6px;

            text-align: left;
        }


        .role-table td {

            padding: 7px 6px;

            border-bottom: 1px solid #E6F0F3;

            color: #315E6E;

            font-size: 9px;

            vertical-align: middle;
        }


        .role-table tbody tr {

            cursor: pointer;

            transition: 0.15s;
        }


        .role-table tbody tr:hover {

            background: #f5fbfc;
        }


        .role-table tbody tr.selected {

            background: #e7f4f7;
        }


        .role-table th:nth-child(1),
        .role-table td:nth-child(1) {

            width: 38px;

            text-align: center;
        }


        .role-table th:nth-child(2),
        .role-table td:nth-child(2) {

            width: 125px;
        }


        .role-table th:nth-child(3),
        .role-table td:nth-child(3) {

            width: 170px;
        }


        .role-table th:nth-child(4),
        .role-table td:nth-child(4) {

            width: 85px;

            text-align: center;
        }


        .role-table th:nth-child(5),
        .role-table td:nth-child(5) {

            width: 70px;

            text-align: center;
        }


        .role-table th:nth-child(6),
        .role-table td:nth-child(6) {

            width: 45px;

            text-align: center;
        }


        /* =====================================================
           STATUS
        ===================================================== */

        .status {

            display: inline-block;

            padding: 4px 7px;

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


        .action-btn {

            width: 28px;

            height: 25px;

            border: 1px solid #d0e0e6;

            background: white;

            border-radius: 5px;

            cursor: pointer;

            color: #426a7b;
        }


        .table-footer {

            color: #78919c;

            font-size: 9px;

            padding: 9px 3px 2px;
        }


        /* =====================================================
           DETAIL
        ===================================================== */

        .detail-box {

            padding: 12px;
        }


        .detail-head {

            display: flex;

            align-items: center;

            justify-content: space-between;

            margin-bottom: 13px;
        }


        .detail-title {

            font-size: 12px;

            font-weight: 700;

            color: #0A4D68;
        }


        .btn-edit {

            border: 1px solid #9ccde1;

            color: #1768c2;

            background: white;

            border-radius: 5px;

            padding: 7px 10px;

            cursor: pointer;

            font-size: 9px;

            font-weight: 700;
        }


        .detail-row {

            display: grid;

            grid-template-columns: 75px 1fr;

            gap: 8px;

            margin-bottom: 10px;
        }


        .detail-label {

            color: #6e8996;

            font-size: 9px;

            font-weight: 700;
        }


        .detail-value {

            color: #315E6E;

            font-size: 9px;

            line-height: 1.4;
        }


        /* =====================================================
           PERMISSION
        ===================================================== */

        .permission-list {

            display: flex;

            flex-direction: column;

            gap: 0;
        }


        .permission-module {

            border-bottom: 1px solid #E6F0F3;

            padding: 7px 0;
        }


        .permission-module:last-child {

            border-bottom: none;
        }


        .module-title {

            display: flex;

            align-items: center;

            gap: 7px;

            color: #315E6E;

            font-size: 9px;

            font-weight: 700;
        }


        .module-title input {

            width: 14px;

            height: 14px;

            accent-color: #0D6E6E;
        }


        .permission-items {

            padding: 6px 0 0 28px;

            display: flex;

            flex-direction: column;

            gap: 5px;
        }


        .permission-item {

            display: flex;

            align-items: center;

            gap: 7px;

            color: #587582;

            font-size: 8px;
        }


        .permission-item input {

            width: 13px;

            height: 13px;

            accent-color: #0D6E6E;
        }


        /* =====================================================
           USERS
        ===================================================== */

        .users-panel {

            margin-top: 10px;
        }


        .user-search {

            height: 32px;

            width: 240px;

            border: 1px solid #D6E8EE;

            border-radius: 5px;

            padding: 0 9px;

            font-size: 9px;

            outline: none;
        }


        .add-user {

            border: none;

            background: #0D6E6E;

            color: white;

            border-radius: 5px;

            padding: 8px 11px;

            font-size: 9px;

            font-weight: 700;

            cursor: pointer;
        }


        .user-toolbar {

            display: flex;

            justify-content: flex-end;

            gap: 7px;

            margin-bottom: 8px;
        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 1100px) {

            .content-grid {

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

                padding: 0 15px 25px;
            }


            .page-head {

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


        <a href="dashboard_admin.php">

            <span class="menu-icon">
                🏠︎
            </span>

            <span>
                Dashboard Administrator
            </span>

        </a>


        <a
            href="hak_akses.php"
            class="active"
        >

            <span class="menu-icon">
                🔓
            </span>

            <span>
                Hak Akses
            </span>

        </a>


        <a href="kelola_akun.php">

            <span class="menu-icon">
                👤
            </span>

            <span>
                Kelola Akun Pengguna
            </span>

        </a>


        <a href="log_aktivitas.php">

            <span class="menu-icon">
                ◷
            </span>

            <span>
                Log Aktivitas
            </span>

        </a>


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


    <!-- TOPBAR -->

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


    <!-- BREADCRUMB -->

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
            Hak Akses
        </span>

    </div>


    <!-- PAGE HEADER -->

    <div class="page-head">


        <div>

            <h1 class="page-title">
                Hak Akses
            </h1>

            <p class="page-subtitle">
                Kelola peran dan hak akses pengguna
            </p>

        </div>


        <div class="page-date">

            Rabu, 03 September 2025<br>

            <strong>
                10:24
            </strong>

        </div>


    </div>


    <!-- TAMBAH ROLE -->

    <div class="add-role">

        <button
            class="btn-add"
            onclick="addRole()"
        >
            ＋ &nbsp; Tambah Role
        </button>

    </div>


    <!-- =====================================================
         ROLE + DETAIL
    ====================================================== -->

    <div class="content-grid">


        <!-- DAFTAR ROLE -->

        <section class="panel">


            <div class="panel-header">

                <div class="panel-icon">
                    🔰
                </div>

                <div class="panel-title">
                    Daftar Role
                </div>

            </div>


            <div class="panel-body">


                <div class="toolbar">


                    <input
                        type="text"
                        id="roleSearch"
                        class="search-box"
                        placeholder="🔎 Cari nama role..."
                    >


                    <select
                        id="statusFilter"
                        class="status-filter"
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


                </div>


                <div class="table-wrap">


                    <table class="role-table">


                        <thead>

                            <tr>

                                <th>
                                    No.
                                </th>

                                <th>
                                    Nama Role
                                </th>

                                <th>
                                    Deskripsi
                                </th>

                                <th>
                                    Jumlah Pengguna
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Aksi
                                </th>

                            </tr>

                        </thead>


                        <tbody id="roleTableBody">


                            <?php foreach ($roles as $role): ?>

                                <tr
                                    class="<?= $role['id'] === $selectedRole['id']
                                        ? 'selected'
                                        : ''
                                    ?>"
                                    data-id="<?= $role['id'] ?>"
                                    data-name="<?= htmlspecialchars(
                                        strtolower($role['name'])
                                    ) ?>"
                                    data-status="<?= htmlspecialchars(
                                        $role['status']
                                    ) ?>"
                                    onclick="selectRole(
                                        <?= $role['id'] ?>
                                    )"
                                >


                                    <td>
                                        <?= $role['id'] ?>
                                    </td>


                                    <td>

                                        <strong>
                                            <?= htmlspecialchars(
                                                $role['name']
                                            ) ?>
                                        </strong>

                                    </td>


                                    <td>

                                        <?= htmlspecialchars(
                                            $role['description']
                                        ) ?>

                                    </td>


                                    <td>
                                        <?= $role['users'] ?>
                                    </td>


                                    <td>

                                        <span
                                            class="status
                                            <?= $role['status'] === 'Aktif'
                                                ? 'active'
                                                : 'inactive'
                                            ?>"
                                        >
                                            ●
                                            <?= htmlspecialchars(
                                                $role['status']
                                            ) ?>
                                        </span>

                                    </td>


                                    <td>

                                        <button
                                            type="button"
                                            class="action-btn"
                                            onclick="event.stopPropagation();
                                            selectRole(
                                                <?= $role['id'] ?>
                                            )"
                                        >
                                            •••
                                        </button>

                                    </td>


                                </tr>

                            <?php endforeach; ?>


                        </tbody>


                    </table>


                </div>


                <div
                    class="table-footer"
                    id="roleCount"
                >
                    Menampilkan 1 -
                    <?= count($roles) ?>
                    dari
                    <?= count($roles) ?>
                    data
                </div>


            </div>


        </section>


        <!-- DETAIL ROLE -->

        <section>


            <div class="panel">


                <div class="detail-box">


                    <div class="detail-head">


                        <div class="detail-title">
                            Detail Role
                        </div>


                        <button
                            type="button"
                            class="btn-edit"
                            onclick="editRole()"
                        >
                            ✎ &nbsp; Ubah Role
                        </button>


                    </div>


                    <div class="detail-row">


                        <div class="detail-label">
                            Nama Role
                        </div>


                        <div
                            class="detail-value"
                            id="detailName"
                        >
                            <?= htmlspecialchars(
                                $selectedRole['name']
                            ) ?>
                        </div>


                    </div>


                    <div class="detail-row">


                        <div class="detail-label">
                            Deskripsi
                        </div>


                        <div
                            class="detail-value"
                            id="detailDescription"
                        >
                            <?= htmlspecialchars(
                                $selectedRole['description']
                            ) ?>
                        </div>


                    </div>


                    <div class="detail-row">


                        <div class="detail-label">
                            Status
                        </div>


                        <div class="detail-value">


                            <span
                                id="detailStatus"
                                class="status
                                <?= $selectedRole['status'] === 'Aktif'
                                    ? 'active'
                                    : 'inactive'
                                ?>"
                            >
                                ●
                                <?= htmlspecialchars(
                                    $selectedRole['status']
                                ) ?>
                            </span>


                        </div>


                    </div>


                </div>


            </div>


            <!-- HAK AKSES MENU -->

            <div
                class="panel"
                style="margin-top: 10px;"
            >


                <div class="panel-header">


                    <div class="panel-icon">
                        ♟
                    </div>


                    <div class="panel-title">
                        Hak Akses Menu
                    </div>


                </div>


                <div class="panel-body">


                    <div
                        class="permission-list"
                        id="permissionList"
                    >


                        <?php foreach (
                            $selectedRole['permissions']
                            as $module => $permissions
                        ): ?>


                            <div class="permission-module">


                                <div class="module-title">

                                    <span>
                                        ⌄
                                    </span>


                                    <input
                                        type="checkbox"
                                        checked
                                        onchange="toggleModule(this)"
                                    >


                                    <span>
                                        <?= htmlspecialchars(
                                            $module
                                        ) ?>
                                    </span>

                                </div>


                                <div class="permission-items">


                                    <?php foreach (
                                        $permissions
                                        as $permission
                                    ): ?>


                                        <label
                                            class="permission-item"
                                        >

                                            <input
                                                type="checkbox"
                                                checked
                                            >

                                            <?= htmlspecialchars(
                                                $permission
                                            ) ?>

                                        </label>


                                    <?php endforeach; ?>


                                </div>


                            </div>


                        <?php endforeach; ?>


                    </div>


                </div>


            </div>


        </section>


    </div>


    <!-- =====================================================
         PENGGUNA DENGAN ROLE TERPILIH
    ====================================================== -->

    <section class="panel users-panel">


        <div class="panel-header">


            <div class="panel-icon">
                ♟
            </div>


            <div class="panel-title">
                Pengguna dengan Role Terpilih
            </div>


        </div>


        <div class="panel-body">


            <div class="user-toolbar">


                <input
                    type="text"
                    class="user-search"
                    placeholder="🔎 Cari nama atau username..."
                    onkeyup="filterUsers(this)"
                >


                <button
                    class="add-user"
                    onclick="addUser()"
                >
                    ＋ &nbsp; Tambah Pengguna
                </button>


            </div>


            <div class="table-wrap">


                <table class="role-table">


                    <thead>

                        <tr>

                            <th>
                                No.
                            </th>

                            <th>
                                Nama Lengkap
                            </th>

                            <th>
                                Username
                            </th>

                            <th>
                                Unit Kerja
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Aksi
                            </th>

                        </tr>

                    </thead>


                    <tbody id="userTableBody">


                        <tr>

                            <td>
                                1
                            </td>

                            <td>
                                dr. Melinda Lie
                            </td>

                            <td>
                                Melinda.um
                            </td>

                            <td>
                                Poliklinik Umum
                            </td>

                            <td>

                                <span class="status active">
                                    ● Aktif
                                </span>

                            </td>

                            <td>

                                <button class="action-btn">
                                    •••
                                </button>

                            </td>

                        </tr>


                        <tr>

                            <td>
                                2
                            </td>

                            <td>
                                dr. Maya Sari, Sp.A
                            </td>

                            <td>
                                maya.s
                            </td>

                            <td>
                                Poliklinik Anak
                            </td>

                            <td>

                                <span class="status active">
                                    ● Aktif
                                </span>

                            </td>

                            <td>

                                <button class="action-btn">
                                    •••
                                </button>

                            </td>

                        </tr>


                        <tr>

                            <td>
                                3
                            </td>

                            <td>
                                dr. Kevin Lie, Sp.OG
                            </td>

                            <td>
                                kevin.og
                            </td>

                            <td>
                                Poliklinik Kandungan
                            </td>

                            <td>

                                <span class="status active">
                                    ● Aktif
                                </span>

                            </td>

                            <td>

                                <button class="action-btn">
                                    •••
                                </button>

                            </td>

                        </tr>


                    </tbody>


                </table>


            </div>


            <div class="table-footer">
                Menampilkan 1 - 3 dari 3 data
            </div>


        </div>


    </section>


</main>


<script>

/* =========================================================
   DATA ROLE
========================================================= */

const roleData = <?= json_encode(
    $roles,
    JSON_UNESCAPED_UNICODE |
    JSON_UNESCAPED_SLASHES
) ?>;


/* =========================================================
   PILIH ROLE
========================================================= */

function selectRole(id) {

    const role = roleData.find(
        item => item.id === id
    );


    if (!role) {

        return;

    }


    document
        .querySelectorAll('#roleTableBody tr')
        .forEach(row => {

            row.classList.remove('selected');


            if (
                Number(row.dataset.id) === id
            ) {

                row.classList.add('selected');

            }

        });


    document.getElementById(
        'detailName'
    ).textContent = role.name;


    document.getElementById(
        'detailDescription'
    ).textContent = role.description;


    const status =
        document.getElementById(
            'detailStatus'
        );


    status.textContent =
        '● ' + role.status;


    status.className =
        'status ' +
        (
            role.status === 'Aktif'
                ? 'active'
                : 'inactive'
        );


    renderPermissions(role);


    const url =
        new URL(window.location.href);


    url.searchParams.set(
        'role',
        id
    );


    window.history.replaceState(
        {},
        '',
        url
    );

}


/* =========================================================
   RENDER PERMISSION
========================================================= */

function renderPermissions(role) {

    const container =
        document.getElementById(
            'permissionList'
        );


    container.innerHTML = '';


    Object.entries(
        role.permissions
    ).forEach(
        ([module, permissions]) => {


            const moduleDiv =
                document.createElement(
                    'div'
                );


            moduleDiv.className =
                'permission-module';


            const title =
                document.createElement(
                    'div'
                );


            title.className =
                'module-title';


            title.innerHTML = `
                <span>⌄</span>

                <input
                    type="checkbox"
                    checked
                    onchange="toggleModule(this)"
                >

                <span>
                    ${escapeHtml(module)}
                </span>
            `;


            moduleDiv.appendChild(
                title
            );


            const items =
                document.createElement(
                    'div'
                );


            items.className =
                'permission-items';


            permissions.forEach(
                permission => {


                    const label =
                        document.createElement(
                            'label'
                        );


                    label.className =
                        'permission-item';


                    label.innerHTML = `
                        <input
                            type="checkbox"
                            checked
                        >

                        ${escapeHtml(
                            permission
                        )}
                    `;


                    items.appendChild(
                        label
                    );

                }
            );


            moduleDiv.appendChild(
                items
            );


            container.appendChild(
                moduleDiv
            );

        }
    );

}


/* =========================================================
   TOGGLE MODULE
========================================================= */

function toggleModule(
    moduleCheckbox
) {

    const module =
        moduleCheckbox.closest(
            '.permission-module'
        );


    const childCheckboxes =
        module.querySelectorAll(
            '.permission-item input'
        );


    childCheckboxes.forEach(
        checkbox => {

            checkbox.checked =
                moduleCheckbox.checked;

        }
    );

}


/* =========================================================
   SEARCH ROLE
========================================================= */

const roleSearch =
    document.getElementById(
        'roleSearch'
    );


const statusFilter =
    document.getElementById(
        'statusFilter'
    );


function filterRoles() {

    const search =
        roleSearch.value
            .toLowerCase()
            .trim();


    const status =
        statusFilter.value;


    let visible = 0;


    document
        .querySelectorAll(
            '#roleTableBody tr'
        )
        .forEach(row => {


            const name =
                row.dataset.name;


            const rowStatus =
                row.dataset.status;


            const matchSearch =
                name.includes(
                    search
                );


            const matchStatus =
                !status ||
                rowStatus === status;


            const show =
                matchSearch &&
                matchStatus;


            row.style.display =
                show ? '' : 'none';


            if (show) {

                visible++;

            }

        });


    document.getElementById(
        'roleCount'
    ).textContent =
        'Menampilkan ' +
        visible +
        ' dari ' +
        roleData.length +
        ' data';

}


roleSearch.addEventListener(
    'input',
    filterRoles
);


statusFilter.addEventListener(
    'change',
    filterRoles
);


/* =========================================================
   USER SEARCH
========================================================= */

function filterUsers(input) {

    const search =
        input.value
            .toLowerCase()
            .trim();


    document
        .querySelectorAll(
            '#userTableBody tr'
        )
        .forEach(row => {


            const text =
                row.textContent
                    .toLowerCase();


            row.style.display =
                text.includes(search)
                    ? ''
                    : 'none';

        });

}


/* =========================================================
   BUTTON TAMBAH ROLE
========================================================= */

function addRole() {

    alert(
        'Form Tambah Role siap digunakan.'
    );

}


/* =========================================================
   BUTTON UBAH ROLE
========================================================= */

function editRole() {

    const name =
        document.getElementById(
            'detailName'
        ).textContent;


    alert(
        'Edit Role: ' +
        name
    );

}


/* =========================================================
   BUTTON TAMBAH USER
========================================================= */

function addUser() {

    alert(
        'Form Tambah Pengguna siap digunakan.'
    );

}


/* =========================================================
   SEARCH MENU
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


                    if (
                        link.textContent
                            .toLowerCase()
                            .includes(value)
                    ) {

                        link.click();

                    }

                }
            );

        }
    );


/* =========================================================
   ESCAPE HTML
========================================================= */

function escapeHtml(value) {

    return String(value)
        .replace(
            /&/g,
            '&amp;'
        )
        .replace(
            /</g,
            '&lt;'
        )
        .replace(
            />/g,
            '&gt;'
        )
        .replace(
            /"/g,
            '&quot;'
        )
        .replace(
            /'/g,
            '&#039;'
        );

}

</script>

</body>

</html>