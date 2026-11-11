<?php
session_start();

/* =====================================================
   CEK LOGIN
   ===================================================== */

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];

$userName = $user['name'] ?? $user['full_name'] ?? 'Andi Putra';
$userRole = $user['role'] ?? 'petugas_registrasi';

$roleLabel = [
    'petugas_registrasi' => 'Petugas Registrasi',
    'radiologist' => 'Radiolog',
    'administrator' => 'Administrator'
];

$userRoleLabel = $roleLabel[$userRole] ?? 'Petugas Registrasi';


/* =====================================================
   DATA PASIEN
   TANPA DATABASE / MYSQL
   ===================================================== */

$patients = [

    [
        'medical_record_no' => '00012345',
        'name'             => 'Budi Santoso',
        'nik'              => '3175010101900012',
        'birth_date'       => '01-01-1990',
        'gender'           => 'Laki-laki',
        'address'          => 'Jl. Melati No. 10, Jakarta'
    ],

    [
        'medical_record_no' => '00012346',
        'name'             => 'Siti Aminah',
        'nik'              => '3175010203920005',
        'birth_date'       => '02-03-1992',
        'gender'           => 'Perempuan',
        'address'           => 'Jl. Kenanga No. 5, Jakarta'
    ],

    [
        'medical_record_no' => '00012347',
        'name'             => 'Roy Kiyoshi',
        'nik'              => '3175011505890008',
        'birth_date'       => '15-05-1989',
        'gender'           => 'Laki-laki',
        'address'           => 'Jl. Mawar No. 8, Jakarta'
    ]

];


/* DATA DARI MYSQL (tabel patients). Data contoh di atas dipakai bila database belum tersedia. */
require_once __DIR__ . '/_db_bridge.php';
if (simrs_db_ok()) {
    try { $patients = simrs_patients(); } catch (Throwable $ex) {}
}


/* =====================================================
   SEARCH
   ===================================================== */

$keyword = trim($_GET['q'] ?? '');

$searchResults = [];

foreach ($patients as $patient) {

    if ($keyword === '') {

        $searchResults[] = $patient;

    } else {

        $search = strtolower($keyword);

        $rm      = strtolower($patient['medical_record_no']);
        $name    = strtolower($patient['name']);
        $nik     = strtolower($patient['nik']);

        if (
            strpos($rm, $search) !== false ||
            strpos($name, $search) !== false ||
            strpos($nik, $search) !== false
        ) {
            $searchResults[] = $patient;
        }
    }
}


/* =====================================================
   HTML ESCAPE
   ===================================================== */

function e($value)
{
    return htmlspecialchars(
        $value,
        ENT_QUOTES,
        'UTF-8'
    );
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

    <title>Data Pasien - RS Suka Sehat</title>


    <!-- =================================================
         CSS
         SEMUA ADA DI FILE INI
         ================================================= -->

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #eef8fb;
            color: #0A4D68;
        }


        /* =================================================
           SIDEBAR
           ================================================= */

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;

            width: 218px;

            background: #0A4D68;

            color: white;

            padding: 18px 12px;

            z-index: 1000;
        }


        .sidebar-brand {
            display: flex;
            align-items: center;

            gap: 10px;

            padding: 4px 5px 25px;
        }


        .sidebar-logo {
            width: 48px;
            height: 48px;

            border-radius: 50%;

            object-fit: cover;

            background: white;
        }


        .brand-text {
            line-height: 1.1;
        }


        .brand-name {
            font-size: 17px;
            font-weight: 700;
        }


        .brand-name .pink {
            color: #F36A9A;
        }


        .brand-name .green {
            color: #ffffff;
        }


        .brand-sub {
            font-size: 10px;
            margin-top: 5px;
            color: #d8f1f5;
        }


        /* =================================================
           MENU
           ================================================= */

        .sidebar-menu {
            display: flex;

            flex-direction: column;

            gap: 6px;
        }


        .menu-item {
            display: flex;

            align-items: center;

            gap: 10px;

            width: 100%;

            padding: 12px 14px;

            border-radius: 8px;

            color: white;

            text-decoration: none;

            font-size: 13px;

            font-weight: 600;
        }


        .menu-item:hover {
            background: rgba(255,255,255,0.10);
        }


        .menu-item.active {
            background: #0D6E6E;
        }


        .menu-icon {
            width: 20px;

            text-align: center;

            font-size: 14px;
        }


        /* =================================================
           MAIN
           ================================================= */

        .main-content {
            margin-left: 218px;

            min-height: 100vh;

            padding: 16px 24px 50px;
        }


        /* =================================================
           TOP BAR
           ================================================= */

        .topbar {
            height: 52px;

            background: white;

            border: 1px solid #d5e8ed;

            border-radius: 10px;

            display: flex;

            align-items: center;

            justify-content: space-between;

            padding: 0 16px;

            margin-bottom: 18px;

            box-shadow: 0 2px 8px rgba(0,0,0,0.03);
        }


        .search-top {
            display: flex;

            align-items: center;

            gap: 9px;

            color: #56798a;

            font-size: 13px;
        }


        .top-user {
            display: flex;

            align-items: center;

            gap: 9px;
        }


        .user-icon {
            width: 30px;
            height: 30px;

            border-radius: 50%;

            background: #0A4D68;

            color: white;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 14px;
        }


        .user-name {
            display: flex;

            flex-direction: column;

            line-height: 1.15;
        }


        .user-name strong {
            color: #0A4D68;

            font-size: 13px;
        }


        .user-name small {
            color: #7190a0;

            font-size: 9px;
        }


        .logout {
            color: #0A4D68;

            text-decoration: none;

            font-weight: 700;

            font-size: 12px;

            margin-left: 10px;
        }


        .logout:hover {
            color: #0D6E6E;
        }


        /* =================================================
           BREADCRUMB
           ================================================= */

        .breadcrumb {
            font-size: 11px;

            color: #5c8091;

            margin: 4px 0 16px;
        }


        .breadcrumb a {
            color: #0A4D68;

            text-decoration: none;
        }


        /* =================================================
           PAGE HEADER
           ================================================= */

        .page-header {
            margin-bottom: 20px;
        }


        .page-header h1 {
            margin: 0 0 7px;

            font-size: 30px;

            color: #064c6a;
        }


        .page-header p {
            margin: 0;

            font-size: 13px;

            color: #668899;
        }


        /* =================================================
           SEARCH PANEL
           ================================================= */

        .panel {
            background: white;

            border: 1px solid #D6E8EE;

            border-radius: 10px;

            margin-bottom: 18px;

            overflow: hidden;

            box-shadow: 0 2px 8px rgba(0,0,0,0.02);
        }


        .panel-title {
            background: #e6f6f7;

            min-height: 50px;

            display: flex;

            align-items: center;

            gap: 10px;

            padding: 10px 14px;

            border-bottom: 1px solid #D6E8EE;
        }


        .panel-icon {
            width: 36px;
            height: 36px;

            background: #0D6E6E;

            border-radius: 7px;

            display: flex;

            align-items: center;

            justify-content: center;

            color: white;

            font-size: 19px;
        }


        .panel-title h2 {
            margin: 0;

            font-size: 15px;

            color: #0A4D68;
        }


        .panel-body {
            padding: 16px;
        }


        .search-form {
            display: flex;

            gap: 10px;
        }


        .search-input {
            flex: 1;

            height: 40px;

            border: 1px solid #C7DDE5;

            border-radius: 7px;

            padding: 0 13px;

            font-size: 12px;

            outline: none;
        }


        .search-input:focus {
            border-color: #2e91a7;
        }


        .btn {
            height: 40px;

            border-radius: 7px;

            padding: 0 25px;

            border: none;

            cursor: pointer;

            font-size: 12px;

            font-weight: 700;
        }


        .btn-search {
            background: #0D6E6E;

            color: white;
        }


        .btn-search:hover {
            background: #287d96;
        }


        .btn-reset {
            background: white;

            color: #456D7D;

            border: 1px solid #C7DDE5;

            text-decoration: none;

            display: flex;

            align-items: center;
        }


        /* =================================================
           TABLE
           ================================================= */

        .result-header {
            display: flex;

            align-items: center;

            justify-content: space-between;
        }


        .result-count {
            margin-left: auto;

            color: #658392;

            font-size: 10px;
        }


        .table-wrap {
            overflow-x: auto;

            padding: 0 10px 10px;
        }


        table {
            width: 100%;

            border-collapse: collapse;

            font-size: 11px;
        }


        th {
            background: #e9f6f7;

            color: #0A4D68;

            font-weight: 700;

            text-align: left;

            padding: 12px 10px;

            border-bottom: 1px solid #d3e6eb;
        }


        td {
            padding: 11px 10px;

            border-bottom: 1px solid #e1edf0;

            color: #456D7D;

            vertical-align: middle;
        }


        tr:hover td {
            background: #f8fcfd;
        }


        .gender {
            display: inline-block;

            padding: 5px 10px;

            border-radius: 8px;

            font-size: 10px;

            font-weight: 700;
        }


        .male {
            background: #d7f2ee;

            color: #168c82;
        }


        .female {
            background: #ffe0ed;

            color: #e34d88;
        }


        .btn-pilih {
            display: inline-flex;

            align-items: center;

            justify-content: center;

            padding: 8px 16px;

            border: 1px solid #50c0c5;

            border-radius: 6px;

            background: white;

            color: #087e85;

            text-decoration: none;

            font-weight: 700;

            font-size: 11px;
        }


        .btn-pilih:hover {
            background: #e9fafa;
        }


        .empty {
            text-align: center;

            padding: 45px 20px;

            color: #7793a0;

            font-size: 12px;
        }


        .empty-icon {
            font-size: 38px;

            margin-bottom: 10px;
        }


        .footer-note {
            font-size: 10px;

            color: #7693a0;

            margin-top: 8px;
        }


        /* =================================================
           RESPONSIVE
           ================================================= */

        @media (max-width: 900px) {

            .sidebar {
                width: 190px;
            }

            .main-content {
                margin-left: 190px;
            }

            .search-form {
                flex-wrap: wrap;
            }

            .search-input {
                width: 100%;
                flex-basis: 100%;
            }

        }

    </style>


<!-- SIMRS: tema seragam portal (font, warna, ukuran) -->
<script>document.documentElement.dataset.simrsMod="claudia";if(window.top!==window.self)document.documentElement.classList.add("embedded-simrs-module");</script>
<link rel="stylesheet" id="simrs-module-theme" href="../../assets/module-theme.css">
</head>


<body>


<!-- =====================================================
     SIDEBAR
     ===================================================== -->

<aside class="sidebar">

    <div class="sidebar-brand">

        <img
         src="assets/logo.png"
         alt="RS Suka Sehat"
        class="sidebar-logo"
        >

        <div class="brand-text">

            <div class="brand-name">
                RS <span class="pink">Suka</span> <span class="green">Sehat</span>
            </div>

            <div class="brand-sub">
                SIMRS
            </div>

        </div>

    </div>


    <nav class="sidebar-menu">

        <a
            href="dashboard.php"
            class="menu-item"
        >
            <span class="menu-icon">🏠︎</span>
            <span>Dashboard</span>
        </a>


        <a
            href="pendaftaran_pasien.php"
            class="menu-item"
        >
            <span class="menu-icon">♟+</span>
            <span>Pendaftaran Pasien</span>
        </a>


        <a
            href="registrasi_pasien.php"
            class="menu-item"
        >
            <span class="menu-icon">✍</span>
            <span>Registrasi Kunjungan</span>
        </a>


        <a
            href="data_pasien.php"
            class="menu-item active"
        >
            <span class="menu-icon">📋</span>
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
                👤
            </div>


            <div class="user-name">

                <strong><?= e($userName) ?></strong>

                <small><?= e($userRoleLabel) ?></small>

            </div>


            <a
                href="logout.php"
                class="logout"
            >
                Logout
            </a>

        </div>

    </header>



    <!-- =================================================
         BREADCRUMB
         ================================================= -->

    <div class="breadcrumb">

        🏠

        &nbsp;›&nbsp;

        <a href="pendaftaran_pasien.php">
            Pendaftaran Pasien
        </a>

        &nbsp;›&nbsp;

        <strong>Pencarian Data Pasien</strong>

    </div>



    <!-- =================================================
         HEADER
         ================================================= -->

    <div class="page-header">

        <h1>
            Pencarian Data Pasien
        </h1>

        <p>
            Cari data pasien berdasarkan kriteria tertentu
        </p>

    </div>



    <!-- =================================================
         SEARCH
         ================================================= -->

    <section class="panel">

        <div class="panel-title">

            <div class="panel-icon">
                🔎
            </div>

            <h2>
                Kriteria Pencarian
            </h2>

        </div>


        <div class="panel-body">

            <form
                method="GET"
                action="data_pasien.php"
                class="search-form"
            >

                <input
                    type="text"
                    name="q"
                    class="search-input"
                    value="<?= e($keyword) ?>"
                    placeholder="Masukkan No. RM, NIK, atau nama pasien..."
                    autocomplete="off"
                >


                <button
                    type="submit"
                    class="btn btn-search"
                >
                    🔎 &nbsp; Cari
                </button>


                <a
                    href="data_pasien.php"
                    class="btn btn-reset"
                >
                    Reset
                </a>

            </form>

        </div>

    </section>



    <!-- =================================================
         HASIL PENCARIAN
         ================================================= -->

    <section class="panel">


        <div class="panel-title result-header">

            <div style="display:flex;align-items:center;gap:10px;">

                <div class="panel-icon">
                    🔎
                </div>

                <h2>
                    Hasil Pencarian
                </h2>

            </div>


            <div class="result-count">

                Menampilkan
                <?= count($searchResults) ?>
                dari
                <?= count($patients) ?>
                hasil

            </div>

        </div>



        <?php if (count($searchResults) > 0): ?>


            <div class="table-wrap">

                <table>

                    <thead>

                        <tr>

                            <th style="width:50px;">
                                No.
                            </th>

                            <th>
                                No. RM
                            </th>

                            <th>
                                Nama Pasien
                            </th>

                            <th>
                                NIK
                            </th>

                            <th>
                                Tanggal Lahir
                            </th>

                            <th>
                                Jenis Kelamin
                            </th>

                            <th>
                                Alamat
                            </th>

                            <th style="text-align:center;">
                                Aksi
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php foreach ($searchResults as $index => $patient): ?>

                        <tr>

                            <td>
                                <?= $index + 1 ?>
                            </td>


                            <td>
                                <?= e($patient['medical_record_no']) ?>
                            </td>


                            <td>
                                <strong>
                                    <?= e($patient['name']) ?>
                                </strong>
                            </td>


                            <td>
                                <?= e($patient['nik']) ?>
                            </td>


                            <td>
                                <?= e($patient['birth_date']) ?>
                            </td>


                            <td>

                                <?php if ($patient['gender'] === 'Perempuan'): ?>

                                    <span class="gender female">
                                        Perempuan
                                    </span>

                                <?php else: ?>

                                    <span class="gender male">
                                        Laki-laki
                                    </span>

                                <?php endif; ?>

                            </td>


                            <td>
                                <?= e($patient['address']) ?>
                            </td>


                            <td style="text-align:center;">

                                <a
                                    href="registrasi_pasien.php?patient=<?= urlencode($patient['medical_record_no']) ?>"
                                    class="btn-pilih"
                                >
                                    👤 &nbsp; Pilih
                                </a>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>


            <div class="footer-note" style="padding:0 10px 14px;">

                Menampilkan
                1 -
                <?= count($searchResults) ?>
                dari
                <?= count($searchResults) ?>
                data

            </div>


        <?php else: ?>


            <div class="empty">

                <div class="empty-icon">
                    🔎
                </div>

                <strong>
                    Tidak ada data pasien.
                </strong>

                <br>

                Silakan coba kata kunci pencarian yang berbeda.

            </div>


        <?php endif; ?>


    </section>


</main>


</body>
</html>