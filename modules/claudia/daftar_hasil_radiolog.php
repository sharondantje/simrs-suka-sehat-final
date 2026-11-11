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
   DATA HASIL RADIOLOGI
========================================================= */

$results = [

    [
        'no_order' => 'RAD202509030001',
        'nama' => 'Budi Santoso',
        'pemeriksaan' => 'Thorax PA',
        'tanggal' => '03-09-2025 08:12',
        'tanggal_filter' => '2025-09-03',
        'modalitas' => 'CR',
        'status' => 'Selesai'
    ],

    [
        'no_order' => 'RAD202509030002',
        'nama' => 'Siti Aminah',
        'pemeriksaan' => 'Abdomen AP',
        'tanggal' => '03-09-2025 08:45',
        'tanggal_filter' => '2025-09-03',
        'modalitas' => 'CR',
        'status' => 'Menunggu'
    ],

    [
        'no_order' => 'RAD202509030003',
        'nama' => 'Joko Widodo',
        'pemeriksaan' => 'CT Head',
        'tanggal' => '02-09-2025 14:20',
        'tanggal_filter' => '2025-09-02',
        'modalitas' => 'CT',
        'status' => 'Selesai'
    ],

    [
        'no_order' => 'RAD202509030004',
        'nama' => 'Maria Lestari',
        'pemeriksaan' => 'USG Abdomen',
        'tanggal' => '02-09-2025 11:10',
        'tanggal_filter' => '2025-09-02',
        'modalitas' => 'USG',
        'status' => 'Selesai'
    ],

    [
        'no_order' => 'RAD202509030005',
        'nama' => 'Agus Setiawan',
        'pemeriksaan' => 'Lumbal AP/Lateral',
        'tanggal' => '01-09-2025 16:30',
        'tanggal_filter' => '2025-09-01',
        'modalitas' => 'CR',
        'status' => 'Selesai'
    ],

    [
        'no_order' => 'RAD202509030006',
        'nama' => 'Dewi Anggraini',
        'pemeriksaan' => 'CT Thorax',
        'tanggal' => '01-09-2025 13:15',
        'tanggal_filter' => '2025-09-01',
        'modalitas' => 'CT',
        'status' => 'Menunggu'
    ],

    [
        'no_order' => 'RAD202509030007',
        'nama' => 'Rizky Pratama',
        'pemeriksaan' => 'Knee AP/Lateral',
        'tanggal' => '31-08-2025 10:05',
        'tanggal_filter' => '2025-08-31',
        'modalitas' => 'CR',
        'status' => 'Selesai'
    ],

    [
        'no_order' => 'RAD202509030008',
        'nama' => 'Lina Marlina',
        'pemeriksaan' => 'USG Obstetri',
        'tanggal' => '31-08-2025 09:40',
        'tanggal_filter' => '2025-08-31',
        'modalitas' => 'USG',
        'status' => 'Selesai'
    ]

];

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>
Daftar Hasil Radiologi - RS Suka Sehat
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

    margin-top: 18px;

    margin-bottom: 5px;

    font-size: 12px;

}


.breadcrumb-home {

    color: #0A4D68;

    font-size: 15px;

}


.breadcrumb a {

    color: #1D5FA8;

    text-decoration: none;

}


.breadcrumb .separator {

    color: #668797;

}


.breadcrumb .current {

    color: #1D5FA8;

    font-weight: 600;

}


/* =========================================================
   PAGE HEADER
========================================================= */

.page-title {

    margin: 7px 0 0;

    color: #063f61;

    font-size: 29px;

    font-weight: 700;

}


.page-subtitle {

    margin: 6px 0 16px;

    color: #66869A;

    font-size: 13px;

}


/* =========================================================
   TOP ACTION
========================================================= */

.page-actions {

    display: flex;

    justify-content: flex-end;

    margin-bottom: 9px;

}


.export-button {

    height: 36px;

    padding: 0 14px;

    border: 1px solid #5b9eaa;

    border-radius: 6px;

    background: white;

    color: #087083;

    font-size: 10px;

    font-weight: 700;

    cursor: pointer;

}


.export-button:hover {

    background: #EAF6F8;

}


/* =========================================================
   TABS
========================================================= */

.tabs {

    display: flex;

    align-items: stretch;

    margin-bottom: 14px;

}


.tab {

    min-width: 130px;

    height: 38px;

    padding: 0 17px;

    border: 1px solid #D6E8EE;

    border-right: none;

    background: #f8fbfd;

    color: #2e6380;

    font-size: 10px;

    font-weight: 600;

    cursor: pointer;

}


.tab:first-child {

    border-radius: 6px 0 0 6px;

}


.tab:last-child {

    border-right: 1px solid #D6E8EE;

    border-radius: 0 6px 6px 0;

}


.tab.active {

    background: #0D6E6E;

    border-color: #0D6E6E;

    color: white;

}


/* =========================================================
   FILTER PANEL
========================================================= */

.filter-panel {

    background: white;

    border: 1px solid #D6E8EE;

    border-radius: 7px;

    padding: 12px;

    margin-bottom: 14px;

}


.filter-grid {

    display: grid;

    grid-template-columns:
        1.8fr
        1fr
        .75fr
        .75fr;

    gap: 9px;

}


.filter-control {

    height: 34px;

    width: 100%;

    border: 1px solid #d3e2e8;

    border-radius: 5px;

    background: white;

    padding: 0 10px;

    outline: none;

    color: #486574;

    font-size: 9px;

}


.filter-control:focus {

    border-color: #0D6E6E;

}


.search-field {

    position: relative;

}


.search-field span {

    position: absolute;

    left: 10px;

    top: 9px;

    color: #5092ae;

}


.search-field input {

    padding-left: 30px;

}


/* =========================================================
   TABLE PANEL
========================================================= */

.table-panel {

    background: white;

    border: 1px solid #D6E8EE;

    border-radius: 7px;

    overflow: hidden;

}


.table-wrapper {

    width: 100%;

    overflow-x: auto;

}


.results-table {

    width: 100%;

    border-collapse: collapse;

}


.results-table thead th {

    padding: 9px 8px;

    background: #e5f1fa;

    color: #486a7d;

    font-size: 9px;

    font-weight: 700;

    text-align: left;

    white-space: nowrap;

}


.results-table tbody td {

    padding: 9px 8px;

    border-bottom: 1px solid #E6F0F3;

    color: #3f6172;

    font-size: 9px;

    white-space: nowrap;

}


.results-table tbody tr:last-child td {

    border-bottom: none;

}


.results-table tbody tr:hover {

    background: #f8fbfc;

}


.order-number {

    color: #08769a;

    font-weight: 700;

}


.patient-name {

    color: #315E6E;

    font-weight: 600;

}


/* =========================================================
   STATUS BADGES
========================================================= */

.status {

    display: inline-flex;

    align-items: center;

    gap: 4px;

    min-width: 72px;

    justify-content: center;

    padding: 5px 8px;

    border-radius: 5px;

    font-size: 8px;

    font-weight: 700;

}


.status-done {

    background: #d9f5e4;

    color: #13865a;

}


.status-waiting {

    background: #fff0d9;

    color: #e78514;

}


/* =========================================================
   VIEW BUTTON
========================================================= */

.view-button {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 5px;

    min-width: 65px;

    height: 26px;

    padding: 0 9px;

    background: white;

    border: 1px solid #8bbdc9;

    border-radius: 5px;

    color: #08758d;

    text-decoration: none;

    font-size: 9px;

    font-weight: 600;

}


.view-button:hover {

    background: #EAF6F8;

}


/* =========================================================
   TABLE FOOTER
========================================================= */

.table-footer {

    display: flex;

    justify-content: space-between;

    align-items: center;

    padding: 10px 12px;

    border-top: 1px solid #E6F0F3;

    color: #78909d;

    font-size: 9px;

}


.pagination {

    display: flex;

    gap: 5px;

}


.page-button {

    width: 28px;
    height: 27px;

    border: 1px solid #d4e2e7;

    border-radius: 5px;

    background: white;

    color: #507180;

    cursor: pointer;

    font-size: 10px;

}


.page-button:hover {

    background: #EAF6F8;

}


.page-button.active {

    background: #0D6E6E;

    border-color: #0D6E6E;

    color: white;

}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 1100px) {

    .filter-grid {

        grid-template-columns:
            1fr
            1fr;

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

    .topbar {

        margin-top: 10px;

    }

    .tabs {

        overflow-x: auto;

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


        <!-- DASHBOARD -->

        <a href="dashboard_radiolog.php">

            <span class="menu-icon">
                🏠︎
            </span>

            <span>
                Dashboard Radiolog
            </span>

        </a>


        <!-- ORDER PASIEN -->

        <a href="order_pasien_radiolog.php">

            <span class="menu-icon">
                ♟+
            </span>

            <span>
                Order Pasien
            </span>

        </a>


        <!-- DAFTAR HASIL -->

        <a
            href="daftar_hasil_radiolog.php"
            class="active"
        >

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
     MAIN
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
                id="topSearch"
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

        <span class="breadcrumb-home">
            ⌂
        </span>

        <span class="separator">
            &gt;
        </span>

        <a href="dashboard_radiolog.php">
            Radiology
        </a>

        <span class="separator">
            &gt;
        </span>

        <span class="current">
            Daftar Hasil
        </span>

    </div>



    <!-- =====================================================
         PAGE TITLE
    ====================================================== -->

    <h1 class="page-title">
        Daftar Hasil Pemeriksaan Radiologi
    </h1>


    <p class="page-subtitle">
        Lihat dan kelola hasil pemeriksaan radiologi yang tersimpan di SIMRS / RME.
    </p>



    <!-- =====================================================
         EXPORT
    ====================================================== -->

    <div class="page-actions">

        <button
            type="button"
            class="export-button"
            onclick="exportData()"
        >
            ⇩ &nbsp; Ekspor Data &nbsp;⌄
        </button>

    </div>



    <!-- =====================================================
         TABS
    ====================================================== -->

    <div class="tabs">


        <button
            type="button"
            class="tab active"
            data-status="Semua"
            onclick="changeTab(this, 'Semua')"
        >
            Semua
        </button>


        <button
            type="button"
            class="tab"
            data-status="Menunggu"
            onclick="changeTab(this, 'Menunggu')"
        >
            Menunggu Interpretasi
        </button>


        <button
            type="button"
            class="tab"
            data-status="Selesai"
            onclick="changeTab(this, 'Selesai')"
        >
            Selesai
        </button>


    </div>



    <!-- =====================================================
         FILTER
    ====================================================== -->

    <section class="filter-panel">


        <div class="filter-grid">


            <!-- SEARCH -->

            <div class="search-field">

                <span>
                    🔍
                </span>

                <input
                    type="text"
                    id="searchInput"
                    class="filter-control"
                    placeholder="Cari nama pasien / no. order / jenis pemeriksaan..."
                    oninput="filterResults()"
                >

            </div>


            <!-- DATE -->

            <input
                type="date"
                id="dateFilter"
                class="filter-control"
                onchange="filterResults()"
            >


            <!-- STATUS -->

            <select
                id="statusFilter"
                class="filter-control"
                onchange="filterResults()"
            >

                <option value="">
                    Semua Status
                </option>

                <option value="Menunggu">
                    Menunggu
                </option>

                <option value="Selesai">
                    Selesai
                </option>

            </select>


            <!-- MODALITY -->

            <select
                id="modalityFilter"
                class="filter-control"
                onchange="filterResults()"
            >

                <option value="">
                    Semua Modalitas
                </option>

                <option value="CR">
                    CR
                </option>

                <option value="CT">
                    CT
                </option>

                <option value="USG">
                    USG
                </option>

            </select>


        </div>


    </section>



    <!-- =====================================================
         TABLE
    ====================================================== -->

    <section class="table-panel">


        <div class="table-wrapper">


            <table
                class="results-table"
                id="resultsTable"
            >


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
                            Tanggal
                        </th>

                        <th>
                            Modalitas
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


                <?php foreach ($results as $index => $result): ?>

                    <tr
                        data-status="<?= htmlspecialchars($result['status']) ?>"
                        data-date="<?= htmlspecialchars($result['tanggal_filter']) ?>"
                        data-modality="<?= htmlspecialchars($result['modalitas']) ?>"
                    >


                        <td>
                            <?= $index + 1 ?>
                        </td>


                        <td>

                            <span class="order-number">
                                <?= htmlspecialchars($result['no_order']) ?>
                            </span>

                        </td>


                        <td>

                            <span class="patient-name">
                                <?= htmlspecialchars($result['nama']) ?>
                            </span>

                        </td>


                        <td>
                            <?= htmlspecialchars($result['pemeriksaan']) ?>
                        </td>


                        <td>
                            <?= htmlspecialchars($result['tanggal']) ?>
                        </td>


                        <td>
                            <?= htmlspecialchars($result['modalitas']) ?>
                        </td>


                        <td>


                            <?php if ($result['status'] === 'Selesai'): ?>

                                <span class="status status-done">
                                    ↓ &nbsp; Selesai
                                </span>

                            <?php else: ?>

                                <span class="status status-waiting">
                                    ↓ &nbsp; Menunggu
                                </span>

                            <?php endif; ?>


                        </td>


                        <td>

                            <a
                                href="detail_order_radiolog.php?order=<?= urlencode($result['no_order']) ?>"
                                class="view-button"
                            >
                                ◉ &nbsp; Lihat
                            </a>

                        </td>


                    </tr>

                <?php endforeach; ?>


                </tbody>


            </table>


        </div>



        <!-- TABLE FOOTER -->

        <div class="table-footer">


            <span id="resultCount">
                Menampilkan 1 - 8 dari 8 data
            </span>


            <div class="pagination">


                <button
                    type="button"
                    class="page-button"
                    onclick="previousPage()"
                >
                    ‹
                </button>


                <button
                    type="button"
                    class="page-button active"
                >
                    1
                </button>


                <button
                    type="button"
                    class="page-button"
                    onclick="nextPage()"
                >
                    ›
                </button>


            </div>


        </div>


    </section>


</main>



<script>

/* =========================================================
   TAB
========================================================= */

let currentTab = 'Semua';


function changeTab(button, status) {

    currentTab = status;


    document
        .querySelectorAll('.tab')
        .forEach(function(tab) {

            tab.classList.remove('active');

        });


    button.classList.add('active');


    filterResults();

}


/* =========================================================
   FILTER
========================================================= */

function filterResults() {

    const search =
        document
            .getElementById('searchInput')
            .value
            .toLowerCase()
            .trim();


    const date =
        document
            .getElementById('dateFilter')
            .value;


    const status =
        document
            .getElementById('statusFilter')
            .value;


    const modality =
        document
            .getElementById('modalityFilter')
            .value;


    const rows =
        document.querySelectorAll(
            '#resultsTable tbody tr'
        );


    let visible = 0;


    rows.forEach(function(row) {

        const text =
            row.innerText.toLowerCase();


        const rowStatus =
            row.dataset.status;


        const rowDate =
            row.dataset.date;


        const rowModality =
            row.dataset.modality;


        const matchSearch =
            search === '' ||
            text.includes(search);


        const matchTab =
            currentTab === 'Semua' ||
            rowStatus === currentTab;


        const matchStatus =
            status === '' ||
            rowStatus === status;


        const matchDate =
            date === '' ||
            rowDate === date;


        const matchModality =
            modality === '' ||
            rowModality === modality;


        const show =
            matchSearch &&
            matchTab &&
            matchStatus &&
            matchDate &&
            matchModality;


        if (show) {

            row.style.display = '';

            visible++;

        } else {

            row.style.display = 'none';

        }

    });


    document.getElementById('resultCount').textContent =
        'Menampilkan ' + visible + ' dari ' + rows.length + ' data';

}


/* =========================================================
   EXPORT DATA
========================================================= */

function exportData() {

    alert(
        'Fitur ekspor data akan mengunduh daftar hasil pemeriksaan radiologi.'
    );

}


/* =========================================================
   PAGINATION
========================================================= */

function previousPage() {

    alert(
        'Anda berada di halaman pertama.'
    );

}


function nextPage() {

    alert(
        'Halaman berikutnya belum tersedia pada data demo.'
    );

}

</script>


</body>

</html>