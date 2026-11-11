<?php
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];
$role = $user['role'];

function e($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$roleConfig = [
    'petugas_registrasi' => [
        'title' => 'Dashboard Petugas Registrasi',
        'subtitle' => 'Kelola pendaftaran pasien dan registrasi kunjungan.',
        'menu' => [
            ['icon' => '♙+', 'label' => 'Pendaftaran Pasien', 'link' => 'pendaftaran_pasien.php'],
            ['icon' => '✍', 'label' => 'Registrasi Kunjungan', 'link' => 'registrasi_pasien.php'],
           ['icon' => '📑', 'label' => 'Data Pasien', 'link' => 'data_pasien.php'],
        ],
        'cards' => [
            ['label' => 'Pasien Hari Ini', 'value' => '12', 'note' => 'Terdaftar hari ini'],
            ['label' => 'Dalam Proses', 'value' => '5', 'note' => 'Sedang diproses'],
            ['label' => 'Menunggu', 'value' => '3', 'note' => 'Menunggu pelayanan'],
            ['label' => 'Selesai', 'value' => '18', 'note' => 'Hari ini'],
        ]
    ],
    'radiologist' => [
        'title' => 'Dashboard Dokter Radiologi',
        'subtitle' => 'Kelola pemeriksaan, interpretasi, dan laporan radiologi.',
        'menu' => [
            ['icon' => '▣', 'label' => 'Order Pemeriksaan', 'link' => '#'],
            ['icon' => '◉', 'label' => 'Pemeriksaan', 'link' => '#'],
            ['icon' => '▤', 'label' => 'Daftar Hasil', 'link' => '#'],
            ['icon' => '▥', 'label' => 'Laporan Radiologi', 'link' => '#'],
            ['icon' => '♙', 'label' => 'Pasien', 'link' => '#'],
        ],
        'cards' => [
            ['label' => 'Order Baru', 'value' => '12', 'note' => 'Hari ini'],
            ['label' => 'Dalam Pemeriksaan', 'value' => '5', 'note' => 'Sedang diproses'],
            ['label' => 'Menunggu Interpretasi', 'value' => '3', 'note' => 'Perlu interpretasi'],
            ['label' => 'Laporan Selesai', 'value' => '18', 'note' => 'Hari ini'],
        ]
    ],
    'admin' => [
        'title' => 'Dashboard Administrator',
        'subtitle' => 'Kelola pengguna, hak akses, dan aktivitas sistem SIMRS.',
        'menu' => [
            ['icon' => '♙+', 'label' => 'Kelola Pengguna', 'link' => '#'],
            ['icon' => '▣', 'label' => 'Hak Akses', 'link' => '#'],
            ['icon' => '⌕', 'label' => 'Activity Log', 'link' => '#'],
            ['icon' => '⚙', 'label' => 'Pengaturan Sistem', 'link' => '#'],
        ],
        'cards' => [
            ['label' => 'Total Pengguna', 'value' => '24', 'note' => 'Akun terdaftar'],
            ['label' => 'Aktif', 'value' => '21', 'note' => 'Pengguna aktif'],
            ['label' => 'Tidak Aktif', 'value' => '3', 'note' => 'Perlu ditinjau'],
            ['label' => 'Aktivitas Hari Ini', 'value' => '48', 'note' => 'Audit log'],
        ]
    ]
];

$config = $roleConfig[$role] ?? $roleConfig['petugas_registrasi'];
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($config['title']) ?> - RS Suka Sehat</title>
    <link rel="stylesheet" href="assets/style.css">

<!-- SIMRS: tema seragam portal (font, warna, ukuran) -->
<script>document.documentElement.dataset.simrsMod="claudia";if(window.top!==window.self)document.documentElement.classList.add("embedded-simrs-module");</script>
<link rel="stylesheet" id="simrs-module-theme" href="../../assets/module-theme.css">
</head>
<body class="app-page">

<aside class="sidebar">
    <div class="sidebar-brand">
        <img src="assets/logo.png" alt="Logo">
        <div>
            <strong>RS <span>Suka</span> Sehat</strong>
            <small>SIMRS</small>
        </div>
    </div>

    <nav class="sidebar-nav">
        <a class="nav-item active" href="dashboard.php">
            <span>🏠︎</span> Dashboard
        </a>

        <?php foreach ($config['menu'] as $item): ?>
            <a class="nav-item" href="<?= e($item['link']) ?>">
                <span><?= $item['icon'] ?></span>
                <?= e($item['label']) ?>
            </a>
        <?php endforeach; ?>
    </nav>
</aside>

<main class="main-content">

    <header class="topbar">
        <div class="search-box">
            <span>⌕</span>
            <input type="text" placeholder="Cari menu..." />
        </div>

        <div class="profile">
            <span class="profile-icon">♙</span>
            <div>
                <strong><?= e($user['name']) ?></strong>
                <small><?= e($user['role_label']) ?></small>
            </div>
            <a href="logout.php" class="logout">Logout</a>
        </div>
    </header>

    <section class="page-header">
        <div>
            <h1><?= e($config['title']) ?></h1>
            <p><?= e($config['subtitle']) ?></p>
        </div>
        <div class="date-box">
            <?= date('d M Y') ?><br>
            <strong><?= date('H:i') ?></strong>
        </div>
    </section>

    <section class="welcome-box">
        <div>
            <h2>Selamat Datang, <?= e($user['name']) ?>!</h2>
            <p>Anda masuk sebagai <strong><?= e($user['role_label']) ?></strong>.</p>
        </div>
        <div class="welcome-icon">♡</div>
    </section>

    <section class="stats-grid">
        <?php foreach ($config['cards'] as $card): ?>
            <div class="stat-card">
                <span class="stat-label"><?= e($card['label']) ?></span>
                <strong><?= e($card['value']) ?></strong>
                <small><?= e($card['note']) ?></small>
            </div>
        <?php endforeach; ?>
    </section>

    <section class="content-grid">
<div class="panel">

    <div class="panel-title">
        <div>
            <h2>Pendaftaran Terbaru</h2>
            <p>Data pendaftaran pasien terbaru.</p>
        </div>
    </div>

    <div class="table-wrap">
        <table>

            <thead>
                <tr>
                    <th>No.</th>
                    <th>No. RM</th>
                    <th>Nama Pasien</th>
                    <th>Jenis Pelayanan</th>
                    <th>Waktu</th>
                    <th>Status</th>
                </tr>
            </thead>

            <tbody>

<tr>
    <td>1</td>
    <td>00012345</td>
    <td>Budi Santoso</td>
    <td>Rawat Jalan</td>
    <td>12:05</td>
    <td>
        <span class="badge success">Terdaftar</span>
    </td>
</tr>

<tr>
    <td>2</td>
    <td>00012346</td>
    <td>Siti Aminah</td>
    <td>Rawat Jalan</td>
    <td>11:48</td>
    <td>
        <span class="badge success">Terdaftar</span>
    </td>
</tr>

<tr>
    <td>3</td>
    <td>00012347</td>
    <td>Roy Kiyoshi</td>
    <td>Rawat Jalan</td>
    <td>11:32</td>
    <td>
        <span class="badge warning">Menunggu</span>
    </td>
</tr>

            </tbody>

        </table>
    </div>

</div>

        <div class="panel quick-panel">
            <div class="panel-title">
                <div>
                    <h2>Akses Cepat</h2>
                    <p>Menu sesuai kewenangan Anda.</p>
                </div>
            </div>

            <?php foreach (array_slice($config['menu'], 0, 4) as $item): ?>
                <a class="quick-link" href="<?= e($item['link']) ?>">
                    <span class="quick-icon"><?= $item['icon'] ?></span>
                    <span><?= e($item['label']) ?></span>
                    <b>›</b>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

</main>
</body>
</html>
