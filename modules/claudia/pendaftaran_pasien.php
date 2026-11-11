<?php
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];

$userName = $user['name']
    ?? $user['full_name']
    ?? 'Andi Putra';

$userRole = 'Petugas Registrasi';

/* SIMPAN KE DATABASE MySQL (tabel patients + visits) */
require_once __DIR__ . '/_db_bridge.php';
$simrsResult = null;
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
if (!in_array($user['role'] ?? '', ['petugas_registrasi', 'admin'], true)) {
    http_response_code(403);
    exit('Akses ditolak: halaman ini khusus Petugas Registrasi.');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !hash_equals($_SESSION['csrf'], (string)($_POST['csrf'] ?? ''))) {
    $simrsResult = ['ok' => false, 'msg' => 'Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.'];
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $simrsResult = simrs_db_ok()
        ? simrs_register($_POST)
        : ['ok' => false, 'msg' => 'Database belum terhubung. Import database/simrs_suka_sehat.sql di phpMyAdmin.'];
}
?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Pendaftaran Pasien Baru - RS Suka Sehat</title>

    <style>

        /* =====================================================
           RESET
        ===================================================== */

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #EAF6F8;
            color: #0A4D68;
        }

        button,
        input,
        select,
        textarea {
            font-family: inherit;
        }


        /* =====================================================
           LAYOUT
        ===================================================== */

        .app {
            display: flex;
            min-height: 100vh;
        }


        /* =====================================================
           SIDEBAR
        ===================================================== */

        .sidebar {
            width: 212px;
            min-width: 212px;
            background: #0A4D68;
            color: white;
            min-height: 100vh;
            padding: 16px 12px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 4px 6px 25px;
        }

        .brand-logo {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            object-fit: cover;
            background: white;
            flex-shrink: 0;
        }

        .brand-text strong {
            display: block;
            font-size: 15px;
            line-height: 18px;
        }

        .brand-text strong span {
            color: #F36A9A;
        }

        .brand-text small {
            display: block;
            margin-top: 3px;
            font-size: 9px;
            color: #d8edf2;
        }


        /* SIDEBAR MENU */

        .menu {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .menu a {
            display: flex;
            align-items: center;
            gap: 10px;

            min-height: 38px;

            padding: 9px 12px;

            border-radius: 8px;

            color: white;
            text-decoration: none;

            font-size: 12px;
            font-weight: 600;
        }

        .menu a:hover {
            background: rgba(255,255,255,.10);
        }

        .menu a.active {
            background: #0D6E6E;
        }

        .menu-icon {
            width: 18px;
            text-align: center;
            font-size: 14px;
        }


        /* =====================================================
           MAIN
        ===================================================== */

        .main {
            flex: 1;
            min-width: 0;
            padding: 10px 22px 40px;
        }


        /* =====================================================
           TOPBAR
        ===================================================== */

        .topbar {
            height: 52px;

            background: white;

            border: 1px solid #d7e9ee;
            border-radius: 10px;

            display: flex;
            align-items: center;
            justify-content: space-between;

            padding: 0 14px;

            margin-bottom: 18px;
        }

        .search {
            display: flex;
            align-items: center;
            gap: 9px;

            color: #668898;
            font-size: 12px;
        }

        .search-icon {
            font-size: 15px;
        }

        .top-user {
            display: flex;
            align-items: center;
            gap: 9px;
            height: 42px;
            white-space: nowrap;
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

            font-size: 13px;
        }

        .user-name {
            display: flex;
            flex-direction: column;
            justify-content: center;
            line-height: 1.1;
        }

        .user-name strong {
            color: #0A4D68;
            font-size: 12px;
        }

        .user-name small {
            color: #7896a2;
            font-size: 8px;
            margin-top: 2px;
        }

        .logout {
            color: #0A4D68;
            text-decoration: none;
            font-size: 11px;
            font-weight: 700;
            margin-left: 7px;
        }


        /* =====================================================
           BREADCRUMB
        ===================================================== */

        .breadcrumb {
            font-size: 10px;
            color: #658b9a;
            margin: 4px 0 8px;
        }

        .breadcrumb span {
            margin: 0 5px;
        }


        /* =====================================================
           PAGE HEADER
        ===================================================== */

        .page-header {
            position: relative;
            min-height: 75px;
            margin-bottom: 2px;
            overflow: hidden;
        }

        .page-header h1 {
            margin: 0;
            font-size: 26px;
            color: #0A4D68;
            line-height: 32px;
        }

        .page-header p {
            margin: 5px 0 0;
            color: #6d8e9b;
            font-size: 12px;
        }

        .page-decoration {
            position: absolute;
            right: 15px;
            top: -20px;

            font-size: 80px;

            opacity: .12;
        }


        /* =====================================================
           FORM CARD
        ===================================================== */

        .form-card {
            background: white;

            border: 1px solid #d7e8ed;

            border-radius: 10px;

            padding: 12px 14px;

            margin-bottom: 10px;
        }


        /* =====================================================
           SECTION TITLE
        ===================================================== */

        .section-title {
            height: 39px;

            display: flex;
            align-items: center;

            gap: 10px;

            background: linear-gradient(
                90deg,
                #e4f6f3,
                #eefaf8
            );

            border-radius: 8px;

            padding: 0 10px;

            margin-bottom: 12px;
        }

        .section-icon {
            width: 30px;
            height: 30px;

            border-radius: 50%;

            display: flex;
            align-items: center;
            justify-content: center;

            background: #238f7d;

            color: white;

            font-size: 15px;
        }

        .section-title strong {
            font-size: 14px;
            color: #0A4D68;
        }


        /* =====================================================
           GRID
        ===================================================== */

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .grid-full {
            width: 100%;
        }


        /* =====================================================
           FORM GROUP
        ===================================================== */

        .form-group {
            margin-bottom: 12px;
        }

        .form-label {
            display: block;

            margin-bottom: 5px;

            font-size: 10px;
            font-weight: 700;

            color: #0A4D68;
        }

        .required {
            color: #ef6d70;
        }


        /* =====================================================
           INPUT
        ===================================================== */

        .input-box {
            position: relative;
        }

        .input-box .input-icon {
            position: absolute;

            left: 10px;
            top: 50%;

            transform: translateY(-50%);

            font-size: 13px;

            color: #638da0;

            pointer-events: none;
        }

        input[type="text"],
        input[type="date"],
        input[type="tel"],
        select,
        textarea {

            width: 100%;

            border: 1px solid #d4e4e9;

            border-radius: 6px;

            background: white;

            color: #476f80;

            font-size: 11px;

            outline: none;
        }

        input[type="text"],
        input[type="date"],
        input[type="tel"],
        select {

            height: 34px;

            padding: 0 10px;
        }

        .with-icon {
            padding-left: 35px !important;
        }

        textarea {
            min-height: 78px;
            padding: 10px;
            resize: vertical;
        }

        input::placeholder,
        textarea::placeholder {
            color: #91aab5;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: #48a7a0;

            box-shadow:
                0 0 0 2px rgba(72,167,160,.10);
        }


        /* =====================================================
           RADIO
        ===================================================== */

        .radio-group {
            display: flex;
            align-items: center;
            gap: 20px;

            height: 34px;
        }

        .radio-option {
            display: flex;
            align-items: center;
            gap: 5px;

            color: #315E6E;

            font-size: 11px;
        }

        .radio-option input {
            accent-color: #278d7c;
            width: 15px;
            height: 15px;
        }


        /* =====================================================
           DIVIDER
        ===================================================== */

        .divider {
            border: 0;
            border-top: 1px solid #E6F0F3;

            margin: 6px 0 14px;
        }


        /* =====================================================
           FOOTER BUTTON
        ===================================================== */

        .form-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;

            margin-top: 12px;
        }

        .btn {
            height: 36px;

            border-radius: 6px;

            padding: 0 28px;

            border: 1px solid #cfe1e6;

            background: white;

            color: #315E6E;

            font-size: 11px;
            font-weight: 700;

            cursor: pointer;
        }

        .btn:hover {
            background: #F3FAFC;
        }

        .btn-save {
            border: none;

            background: #0D6E6E;

            color: white;

            min-width: 112px;
        }

        .btn-save:hover {
            background: #076f70;
        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 900px) {

            .sidebar {
                width: 180px;
                min-width: 180px;
            }

            .grid-2 {
                grid-template-columns: 1fr;
                gap: 0;
            }

        }

        @media (max-width: 650px) {

            .sidebar {
                display: none;
            }

            .main {
                padding: 8px 10px 30px;
            }

            .topbar {
                padding: 0 10px;
            }

            .user-name {
                display: none;
            }

            .page-header h1 {
                font-size: 22px;
            }

        }

    </style>


<!-- SIMRS: tema seragam portal (font, warna, ukuran) -->
<script>document.documentElement.dataset.simrsMod="claudia";if(window.top!==window.self)document.documentElement.classList.add("embedded-simrs-module");</script>
<link rel="stylesheet" id="simrs-module-theme" href="../../assets/module-theme.css">
</head>


<body>

<div class="app">


    <!-- =====================================================
         SIDEBAR
         ===================================================== -->

    <aside class="sidebar">

        <div class="brand">

            <img
                src="assets/logo.png"
                class="brand-logo"
                alt="RS Suka Sehat"
            >

            <div class="brand-text">

                <strong>
                    RS <span>Suka</span> Sehat
                </strong>

                <small>
                    SIMRS
                </small>

            </div>

        </div>


        <nav class="menu">

            <a href="dashboard.php">

                <span class="menu-icon">🏠︎</span>

                Dashboard

            </a>


            <a href="pendaftaran_pasien.php"
               class="active">

                <span class="menu-icon">♟+</span>

                Pendaftaran Pasien

            </a>


            <a href="registrasi_pasien.php">

                <span class="menu-icon">✍</span>

                Registrasi Kunjungan

            </a>


            <a href="data_pasien.php">

                <span class="menu-icon">📋</span>

                Data Pasien

            </a>

        </nav>

    </aside>


    <!-- =====================================================
         MAIN
         ===================================================== -->

    <main class="main">


        <!-- TOPBAR -->

        <header class="topbar">

            <div class="search">

                <span class="search-icon">🔎</span>

                <span>
                    Cari menu...
                </span>

            </div>


            <div class="top-user">

                <div class="user-icon">
                    👤
                </div>

                <div class="user-name">

                    <strong>
                        <?= htmlspecialchars($userName) ?>
                    </strong>

                    <small>
                        <?= htmlspecialchars($userRole) ?>
                    </small>

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
             ===================================================== -->

        <div class="breadcrumb">

            🏠

            <span>›</span>

            Pendaftaran Pasien

            <span>›</span>

            Pasien Baru

        </div>


        <!-- =====================================================
             PAGE HEADER
             ===================================================== -->

        <div class="page-header">

            <h1>
                Pendaftaran Pasien Baru
            </h1>

            <p>
                Lengkapi data pasien untuk proses pendaftaran
            </p>

            <div class="page-decoration">
                📋
            </div>

        </div>


        <!-- =====================================================
             FORM
             ===================================================== -->

        <?php if ($simrsResult): ?>
        <div style="margin:0 0 16px;padding:12px 14px;border-radius:10px;font-size:13px;<?= $simrsResult['ok'] ? 'background:#D9F3EA;color:#117A68;border:1px solid #B6E6D5' : 'background:#FDE7E7;color:#C0362C;border:1px solid #F5C2BE' ?>">
            <?= $simrsResult['ok'] ? '✓ ' : '⚠ ' ?><?= htmlspecialchars($simrsResult['msg']) ?>
            <?php if ($simrsResult['ok']): ?> &nbsp;<a href="data_pasien.php" style="color:inherit;font-weight:700;text-decoration:underline">Lihat Data Pasien</a><?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Stepper progres pendaftaran (Data Identitas → Alamat & Kontak → Kunjungan → Tersimpan) -->
        <style>
        .reg-stepper{display:flex;gap:0;margin:0 0 18px;padding:16px 20px;background:#fff;border:1px solid #DCE3EC;border-radius:16px;position:sticky;top:0;z-index:5}
        .reg-step{flex:1;display:flex;align-items:center;gap:10px;font-family:'JetBrains Mono',monospace;font-size:12px;color:#64748B}
        .reg-step:not(:last-child)::after{content:"";flex:1;height:2px;background:#DCE3EC;margin:0 10px}
        .reg-step i{width:28px;height:28px;border-radius:50%;display:grid;place-items:center;font-style:normal;font-weight:700;background:#F1F5F9;border:2px solid #DCE3EC;color:#475569;flex-shrink:0}
        .reg-step.done i{background:#15803D;border-color:#15803D;color:#fff}.reg-step.done:not(:last-child)::after{background:#15803D}
        .reg-step.active i{background:#0D6E6E;border-color:#0D6E6E;color:#fff}.reg-step.active{color:#0F172A;font-weight:600}
        @media(max-width:760px){.reg-stepper{flex-direction:column;gap:8px}.reg-step::after{display:none}}
        </style>
        <div class="reg-stepper" id="regStepper" data-saved="<?= !empty($simrsResult['ok']) ? '1' : '0' ?>">
            <div class="reg-step"><i>1</i><span>Data Identitas</span></div>
            <div class="reg-step"><i>2</i><span>Alamat &amp; Kontak</span></div>
            <div class="reg-step"><i>3</i><span>Kunjungan</span></div>
            <div class="reg-step"><i>4</i><span>Tersimpan</span></div>
        </div>

        <form
            method="POST"
            action=""
            autocomplete="off"
        >
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">


            <!-- =================================================
                 DATA IDENTITAS PASIEN
                 ================================================= -->

            <div class="form-card">

                <div class="section-title">

                    <div class="section-icon">
                        👤
                    </div>

                    <strong>
                        Data Identitas Pasien
                    </strong>

                </div>


                <div class="grid-2">


                    <!-- NAMA -->

                    <div class="form-group">

                        <label class="form-label">

                            Nama Lengkap
                            <span class="required">*</span>

                        </label>

                        <div class="input-box">

                            <span class="input-icon">
                                ♙
                            </span>

                            <input
                                type="text"
                                name="nama"
                                class="with-icon"
                                placeholder="Contoh: Budi Santoso"
                            >

                        </div>

                    </div>


                    <!-- NIK -->

                    <div class="form-group">

                        <label class="form-label">

                            NIK (KTP)
                            <span class="required">*</span>

                        </label>

                        <div class="input-box">

                            <span class="input-icon">
                                ▣
                            </span>

                            <input
                                type="text"
                                name="nik"
                                class="with-icon"
                                maxlength="16"
                                placeholder="16 digit angka"
                            >

                        </div>

                    </div>


                    <!-- TANGGAL LAHIR -->

                    <div class="form-group">

                        <label class="form-label">

                            Tanggal lahir
                            <span class="required">*</span>

                        </label>

                        <div class="input-box">

                            <span class="input-icon">
                                ▣
                            </span>

                            <input
                                type="date"
                                name="tanggal_lahir"
                                class="with-icon"
                            >

                        </div>

                    </div>


                    <!-- JENIS KELAMIN -->

                    <div class="form-group">

                        <label class="form-label">

                            Jenis Kelamin
                            <span class="required">*</span>

                        </label>

                        <div class="radio-group">

                            <label class="radio-option">

                                <input
                                    type="radio"
                                    name="jenis_kelamin"
                                    value="Laki-laki"
                                >

                                Laki-laki

                            </label>


                            <label class="radio-option">

                                <input
                                    type="radio"
                                    name="jenis_kelamin"
                                    value="Perempuan"
                                >

                                Perempuan

                            </label>

                        </div>

                    </div>


                    <!-- GOLONGAN DARAH -->

                    <div class="form-group">

                        <label class="form-label">
                            Golongan Darah
                        </label>

                        <select name="golongan_darah">

                            <option value="">
                                Tidak diketahui
                            </option>

                            <option value="A">
                                A
                            </option>

                            <option value="B">
                                B
                            </option>

                            <option value="AB">
                                AB
                            </option>

                            <option value="O">
                                O
                            </option>

                        </select>

                    </div>

                </div>

            </div>


            <!-- =================================================
                 ALAMAT & KONTAK
                 ================================================= -->

            <div class="form-card">

                <div class="section-title">

                    <div class="section-icon">
                        🏠
                    </div>

                    <strong>
                        Alamat & Kontak
                    </strong>

                </div>


                <!-- ALAMAT -->

                <div class="form-group">

                    <label class="form-label">

                        Alamat Lengkap

                    </label>

                    <div class="input-box">

                        <span class="input-icon">
                            📍
                        </span>

                        <textarea
                            name="alamat"
                            class="with-icon"
                            placeholder="Jl. Nama Jalan, RT/RW, Kelurahan, Kecamatan"
                        ></textarea>

                    </div>

                </div>


                <div class="divider"></div>


                <!-- NOMOR HP -->

                <div class="form-group">

                    <label class="form-label">

                        Nomor HP
                        <span class="required">*</span>

                    </label>

                    <div class="input-box">

                        <span class="input-icon">
                            ☎
                        </span>

                        <input
                            type="tel"
                            name="no_hp"
                            class="with-icon"
                            placeholder="08xxxxxxxxxx"
                        >

                    </div>

                </div>

            </div>


            <!-- =================================================
                 KUNJUNGAN INI
                 ================================================= -->

            <div class="form-card">

                <div class="section-title">

                    <div class="section-icon">
                        🏥
                    </div>

                    <strong>
                        Kunjungan Ini
                    </strong>

                </div>


                <div class="grid-2">


                    <!-- POLIKLINIK -->

                    <div class="form-group">

                        <label class="form-label">

                            Tujuan Poliklinik
                            <span class="required">*</span>

                        </label>

                        <select name="poliklinik">

                            <option value="">
                                -- Pilih Poliklinik --
                            </option>

                            <option value="Poliklinik Umum">
                                Poliklinik Umum
                            </option>

                            <option value="Poliklinik Anak">
                                Poliklinik Anak
                            </option>

                            <option value="Poliklinik Penyakit Dalam">
                                Poliklinik Penyakit Dalam
                            </option>

                            <option value="Poliklinik Bedah">
                                Poliklinik Bedah
                            </option>

                            <option value="Poliklinik Kandungan">
                                Poliklinik Kandungan
                            </option>

                        </select>

                    </div>


                    <!-- PENJAMIN -->

                    <div class="form-group">

                        <label class="form-label">

                            Jenis Penjaminan
                            <span class="required">*</span>

                        </label>

                        <div class="radio-group">

                            <label class="radio-option">

                                <input
                                    type="radio"
                                    name="penjamin"
                                    value="Umum"
                                >

                                Umum

                            </label>


                            <label class="radio-option">

                                <input
                                    type="radio"
                                    name="penjamin"
                                    value="BPJS"
                                >

                                BPJS

                            </label>


                            <label class="radio-option">

                                <input
                                    type="radio"
                                    name="penjamin"
                                    value="Asuransi Swasta"
                                >

                                Asuransi Swasta

                            </label>

                        </div>

                    </div>

                </div>


                <!-- BUTTON -->

                <div class="form-actions">

                    <button
                        type="reset"
                        class="btn"
                    >
                        ✕&nbsp; Batal
                    </button>


                    <button
                        type="submit"
                        class="btn btn-save"
                    >
                        ▣&nbsp; Simpan
                    </button>

                </div>

            </div>


        </form>


    </main>

</div>

<!-- SIMRS: validasi inline sebelum submit (Panduan UI: NIK 16 digit, tanggal lahir tidak di masa depan) -->
<style>.simrs-err{display:block;margin-top:6px;font-size:12px;color:#B91C1C;font-weight:500}.simrs-invalid{border-color:#B91C1C!important;box-shadow:0 0 0 3px rgba(185,28,28,.12)!important}</style>
<script>
(function(){
  const form=document.querySelector('form[method="post"],form[method="POST"]');if(!form)return;
  const today=new Date().toISOString().slice(0,10);
  const rules={
    nama:v=>v.trim()?'':'Nama lengkap wajib diisi',
    nik:v=>!v?'NIK wajib diisi':(/^\d{16}$/.test(v)?'':'NIK harus 16 digit angka (sekarang '+v.replace(/\D/g,'').length+' digit)'),
    tanggal_lahir:v=>!v?'Tanggal lahir wajib diisi':(v>today?'Tanggal lahir tidak boleh di masa depan':''),
    no_hp:v=>!v?'Nomor HP wajib diisi':(/^0\d{9,13}$/.test(v)?'':'Nomor HP harus diawali 0 dan berisi 10-14 digit angka'),
    poliklinik:v=>v?'':'Pilih poliklinik tujuan'
  };
  const tl=form.querySelector('[name=tanggal_lahir]');if(tl)tl.max=today;
  const nik=form.querySelector('[name=nik]');if(nik){nik.setAttribute('inputmode','numeric');nik.addEventListener('input',()=>{nik.value=nik.value.replace(/\D/g,'').slice(0,16)})}
  function show(el,msg){
    const box=el.closest('.form-group')||el.parentElement;let e=box.querySelector('.simrs-err');
    el.classList.toggle('simrs-invalid',!!msg);
    if(msg){if(!e){e=document.createElement('small');e.className='simrs-err';box.appendChild(e)}e.textContent=msg}else if(e)e.remove();
    return !msg}
  function check(name){const el=form.querySelector('[name="'+name+'"]');if(!el)return true;return show(el,rules[name](el.value))}
  function checkGender(){const r=form.querySelectorAll('[name=jenis_kelamin]');if(!r.length)return true;const ok=[...r].some(x=>x.checked);const box=r[0].closest('.form-group')||r[0].parentElement;let e=box.querySelector('.simrs-err');if(!ok){if(!e){e=document.createElement('small');e.className='simrs-err';box.appendChild(e)}e.textContent='Pilih jenis kelamin'}else if(e)e.remove();return ok}
  Object.keys(rules).forEach(n=>{const el=form.querySelector('[name="'+n+'"]');if(el){el.addEventListener('blur',()=>check(n));el.addEventListener('change',()=>check(n))}});
  form.querySelectorAll('[name=jenis_kelamin]').forEach(r=>r.addEventListener('change',checkGender));
  form.addEventListener('submit',ev=>{
    let ok=true;Object.keys(rules).forEach(n=>{if(!check(n))ok=false});if(!checkGender())ok=false;
    if(!ok){ev.preventDefault();const f=form.querySelector('.simrs-invalid')||form.querySelector('.simrs-err');if(f)f.scrollIntoView({behavior:'smooth',block:'center'});if(f&&f.focus)f.focus()}
  });
})();
</script>
<script>
/* Stepper mengikuti kelengkapan tiap bagian formulir */
(function(){
  const st=document.querySelectorAll('#regStepper .reg-step'),form=document.querySelector('form[method="POST"]');if(!form||!st.length)return;
  const cards=[...form.querySelectorAll('.form-card')].slice(0,3);
  function complete(card){return [...card.querySelectorAll('[required]')].every(el=>el.type==='radio'?!!card.querySelector('[name="'+el.name+'"]:checked'):el.value.trim()!=='')}
  function paint(){const saved=document.getElementById('regStepper').dataset.saved==='1';let active=saved?4:cards.findIndex(c=>!complete(c));if(active<0)active=3;
    st.forEach((s,i)=>{const done=i<active;s.classList.toggle('done',done);s.classList.toggle('active',i===active);s.querySelector('i').textContent=done?'✓':i+1})}
  form.addEventListener('input',paint);form.addEventListener('change',paint);paint();
})();
</script>
</body>
</html>