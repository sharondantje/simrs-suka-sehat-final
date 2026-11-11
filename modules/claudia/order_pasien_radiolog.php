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

/* Hanya Radiolog yang boleh masuk */
if (($user['role'] ?? '') !== 'radiologist') {
    header('Location: dashboard.php');
    exit;
}

/* Nama khusus Radiolog */
$userName = 'Rini Sutijah';
$userRoleLabel = 'Radiolog';
?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Order Pasien - Radiolog | RS Suka Sehat</title>


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
   MAIN CONTENT
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
   FILTER PANEL
========================================================= */

.filter-panel {

    background: white;

    border: 1px solid #D6E8EE;

    border-radius: 9px;

    margin-top: 18px;

    padding: 15px;

}


.filter-title {

    font-size: 13px;

    font-weight: 700;

    color: #0A4D68;

    margin-bottom: 12px;

}


.filter-grid {

    display: grid;

    grid-template-columns:
        1.2fr
        1fr
        1fr
        1fr
        auto;

    gap: 10px;

    align-items: end;

}


.form-group label {

    display: block;

    font-size: 9px;

    color: #607c89;

    font-weight: 600;

    margin-bottom: 5px;

}


.form-control {

    width: 100%;

    height: 35px;

    border: 1px solid #d5e3e8;

    border-radius: 5px;

    padding: 0 9px;

    font-size: 10px;

    color: #486574;

    background: white;

    outline: none;

}


.form-control:focus {

    border-color: #0b7f83;

}


.filter-button {

    height: 35px;

    padding: 0 15px;

    border: none;

    border-radius: 5px;

    background: #0D6E6E;

    color: white;

    font-size: 10px;

    font-weight: 600;

    cursor: pointer;

}


.filter-button:hover {

    background: #066d75;

}


/* =========================================================
   ORDER SUMMARY
========================================================= */

.summary {

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 12px;

    margin-top: 15px;

}


.summary-card {

    background: white;

    border: 1px solid #D6E8EE;

    border-radius: 8px;

    padding: 13px 15px;

}


.summary-label {

    font-size: 9px;

    color: #6c8794;

}


.summary-number {

    font-size: 21px;

    font-weight: 700;

    color: #0A4D68;

    margin-top: 5px;

}


/* =========================================================
   TABLE PANEL
========================================================= */

.panel {

    background: white;

    border: 1px solid #D6E8EE;

    border-radius: 9px;

    margin-top: 15px;

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


.panel-subtitle {

    font-size: 9px;

    color: #7a929d;

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

    padding: 9px 8px;

    font-weight: 700;

    white-space: nowrap;

}


tbody td {

    padding: 10px 8px;

    border-bottom: 1px solid #e3ebef;

    font-size: 9px;

    color: #456475;

    vertical-align: middle;

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


.status-new {

    background: #e4f1ff;

    color: #2478ba;

}


.status-process {

    background: #fff0dc;

    color: #e78412;

}


.status-waiting {

    background: #fff0dc;

    color: #e78412;

}


.status-done {

    background: #dff5ea;

    color: #18845b;

}


/* =========================================================
   ACTION
========================================================= */

.action-button {

    background: white;

    border: 1px solid #d5e3e8;

    border-radius: 4px;

    padding: 5px 9px;

    color: #0D6E6E;

    font-size: 8px;

    cursor: pointer;

}


.action-button:hover {

    background: #EAF6F8;

}


/* =========================================================
   TABLE FOOTER
========================================================= */

.table-footer {

    display: flex;

    justify-content: space-between;

    align-items: center;

    padding: 6px 14px 12px;

    color: #78919d;

    font-size: 9px;

}


.pagination {

    display: flex;

    gap: 4px;

}


.page-btn {

    width: 25px;
    height: 24px;

    border: 1px solid #d6e3e8;

    background: white;

    color: #587481;

    border-radius: 4px;

    cursor: pointer;

    font-size: 9px;

}


.page-btn.active {

    background: #0D6E6E;

    color: white;

    border-color: #0D6E6E;

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

    .filter-grid {

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

    .filter-grid {

        grid-template-columns: 1fr;

    }

    .summary {

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


        <!-- DAFTAR HASIL -->

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


    <h1 class="page-title">
        Order Pasien
    </h1>


    <p class="page-subtitle">
        Daftar order pemeriksaan radiologi yang perlu diproses.
    </p>


</section>



<!-- =========================================================
     FILTER
========================================================= -->

<section class="filter-panel">


    <div class="filter-title">
        Cari dan Filter Order
    </div>


    <div class="filter-grid">


        <div class="form-group">

            <label>
                Cari Pasien / No. Order
            </label>

            <input
                type="text"
                class="form-control"
                id="searchOrder"
                placeholder="Nama pasien atau nomor order..."
            >

        </div>


        <div class="form-group">

            <label>
                Jenis Pemeriksaan
            </label>

            <select
                class="form-control"
                id="jenisPemeriksaan"
            >

                <option value="">
                    Semua Pemeriksaan
                </option>

                <option value="Thorax PA">
                    Thorax PA
                </option>

                <option value="Abdomen AP">
                    Abdomen AP
                </option>

                <option value="CT Head">
                    CT Head
                </option>

                <option value="USG Abdomen">
                    USG Abdomen
                </option>

                <option value="Lumbal AP/Lateral">
                    Lumbal AP/Lateral
                </option>

            </select>

        </div>


        <div class="form-group">

            <label>
                Status
            </label>

            <select
                class="form-control"
                id="statusFilter"
            >

                <option value="">
                    Semua Status
                </option>

                <option value="Order Baru">
                    Order Baru
                </option>

                <option value="Dalam Pemeriksaan">
                    Dalam Pemeriksaan
                </option>

                <option value="Menunggu Interpretasi">
                    Menunggu Interpretasi
                </option>

                <option value="Selesai">
                    Selesai
                </option>

            </select>

        </div>


        <div class="form-group">

            <label>
                Tanggal
            </label>

            <input
                type="date"
                class="form-control"
                value="2025-09-03"
            >

        </div>


        <div>

            <button
                type="button"
                class="filter-button"
                onclick="filterOrders()"
            >
                Cari
            </button>

        </div>


    </div>

</section>



<!-- =========================================================
     SUMMARY
========================================================= -->

<section class="summary">


    <div class="summary-card">

        <div class="summary-label">
            Total Order Hari Ini
        </div>

        <div class="summary-number">
            12
        </div>

    </div>


    <div class="summary-card">

        <div class="summary-label">
            Menunggu Pemeriksaan
        </div>

        <div class="summary-number">
            5
        </div>

    </div>


    <div class="summary-card">

        <div class="summary-label">
            Menunggu Interpretasi
        </div>

        <div class="summary-number">
            3
        </div>

    </div>


</section>



<!-- =========================================================
     ORDER TABLE
========================================================= -->

<section class="panel">


    <div class="panel-header">


        <div class="panel-title">

            <div class="panel-icon">
                ▣
            </div>


            <div>

                <h2>
                    Daftar Order Pasien
                </h2>

                <div class="panel-subtitle">
                    Order pemeriksaan radiologi
                </div>

            </div>

        </div>


    </div>



    <div class="table-wrapper">


        <table id="orderTable">


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
                        Dokter Pengirim
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


                <!-- ROW 1 -->

                <tr>

                    <td>1</td>

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
                            RM 2025001234 • 45 th • Laki-laki
                        </span>

                    </td>

                    <td>
                        Thorax PA
                    </td>

                    <td>
                        dr. Andi Pratama, Sp.PD
                    </td>

                    <td>
                        03-09-2025 08:15
                    </td>

                    <td>

                        <span class="status status-new">
                            Order Baru
                        </span>

                    </td>

                    <td>

                        <a
    href="detail_order_radiolog.php?order=RAD20250903001"
    class="action-button"
>
    Lihat
</a>
                    </td>

                </tr>


                <!-- ROW 2 -->

                <tr>

                    <td>2</td>

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
                            RM 2025001235 • 38 th • Perempuan
                        </span>

                    </td>

                    <td>
                        Abdomen AP
                    </td>

                    <td>
                        dr. Budi Santoso, Sp.PD
                    </td>

                    <td>
                        03-09-2025 08:45
                    </td>

                    <td>

                        <span class="status status-process">
                            Dalam Pemeriksaan
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


                <!-- ROW 3 -->

                <tr>

                    <td>3</td>

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
                            RM 2025001236 • 52 th • Laki-laki
                        </span>

                    </td>

                    <td>
                        CT Head
                    </td>

                    <td>
                        dr. Rina Wijaya, Sp.N
                    </td>

                    <td>
                        03-09-2025 09:10
                    </td>

                    <td>

                        <span class="status status-waiting">
                            Menunggu Interpretasi
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


                <!-- ROW 4 -->

                <tr>

                    <td>4</td>

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
                            RM 2025001237 • 41 th • Perempuan
                        </span>

                    </td>

                    <td>
                        USG Abdomen
                    </td>

                    <td>
                        dr. Andi Pratama, Sp.PD
                    </td>

                    <td>
                        03-09-2025 09:30
                    </td>

                    <td>

                        <span class="status status-waiting">
                            Menunggu Interpretasi
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


                <!-- ROW 5 -->

                <tr>

                    <td>5</td>

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
                            RM 2025001238 • 47 th • Laki-laki
                        </span>

                    </td>

                    <td>
                        Lumbal AP/Lateral
                    </td>

                    <td>
                        dr. Maya Putri, Sp.OT
                    </td>

                    <td>
                        03-09-2025 09:50
                    </td>

                    <td>

                        <span class="status status-new">
                            Order Baru
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
            Menampilkan 1 - 5 dari 5 order
        </span>


        <div class="pagination">

            <button class="page-btn active">
                1
            </button>

            <button class="page-btn">
                2
            </button>

            <button class="page-btn">
                3
            </button>

        </div>


    </div>


</section>


</main>



<script>

/* =========================================================
   LIHAT ORDER
========================================================= */

function lihatOrder(order) {

    alert(
        "Detail Order Pemeriksaan\n\n" +
        "No. Order: " + order +
        "\n\nHalaman detail order dapat dilanjutkan."
    );

}


/* =========================================================
   FILTER ORDER
========================================================= */

function filterOrders() {

    const search =
        document
            .getElementById('searchOrder')
            .value
            .toLowerCase();

    const jenis =
        document
            .getElementById('jenisPemeriksaan')
            .value
            .toLowerCase();

    const status =
        document
            .getElementById('statusFilter')
            .value
            .toLowerCase();

    const rows =
        document.querySelectorAll(
            '#orderTable tbody tr'
        );

    rows.forEach(function(row) {

        const text =
            row.innerText.toLowerCase();

        const cocokSearch =
            search === '' ||
            text.includes(search);

        const cocokJenis =
            jenis === '' ||
            text.includes(jenis);

        const cocokStatus =
            status === '' ||
            text.includes(status);

        if (
            cocokSearch &&
            cocokJenis &&
            cocokStatus
        ) {

            row.style.display = '';

        } else {

            row.style.display = 'none';

        }

    });

}

</script>


</body>

</html>