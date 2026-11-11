<?php
/*
 * BOOTSTRAP SIMRS RS SUKA SEHAT
 * - koneksi database, sesi login, helper, audit log
 * - konfigurasi menu per role (RBAC) dipakai portal.php
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Jakarta');

define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/config/database.php';

/* ---------------- helper umum ---------------- */
function e($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

function rupiah($n): string { return 'Rp ' . number_format((float)$n, 0, ',', '.'); }

function angka($n, int $dec = 0): string { return number_format((float)$n, $dec, ',', '.'); }

function tgl($date, bool $withTime = false): string
{
    if (!$date) return '-';
    $bulan = [1=>'Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    $t = strtotime($date);
    $s = date('j', $t) . ' ' . $bulan[(int)date('n', $t)] . ' ' . date('Y', $t);
    return $withTime ? $s . ', ' . date('H:i', $t) : $s;
}

function tgl_panjang(): string
{
    $hari  = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    $bulan = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    return $hari[(int)date('w')] . ', ' . date('j') . ' ' . $bulan[(int)date('n')] . ' ' . date('Y');
}

function initials(string $name): string
{
    $name  = preg_replace('/^(dr\.|drg\.|ns\.|apt\.)\s*/i', '', trim($name));
    $parts = preg_split('/[\s,]+/', $name);
    $s = strtoupper(substr($parts[0] ?? '', 0, 1) . substr($parts[1] ?? '', 0, 1));
    return $s !== '' ? $s : 'U';
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . csrf_token() . '">'; }
function csrf_check(): void
{
    if (($_POST['csrf'] ?? '') !== ($_SESSION['csrf'] ?? null)) {
        http_response_code(400);
        exit('Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.');
    }
}

function flash(string $type, string $msg): void { $_SESSION['flash'][] = [$type, $msg]; }
function take_flash(): array { $f = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $f; }

function redirect(string $url): void { header('Location: ' . $url); exit; }

/* ---------------- database helper ---------------- */
function db_ok(): bool
{
    try { db(); return true; } catch (Throwable $e) { return false; }
}
/* File unggahan yang disimpan di database (dipakai bila server tidak bisa menulis ke folder uploads/) */
function app_files_table(): void
{
    db()->exec('CREATE TABLE IF NOT EXISTS app_files (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(190) NOT NULL,
        mime VARCHAR(100) NOT NULL,
        data LONGBLOB NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) DEFAULT CHARSET=utf8mb4');
}
function app_file_store(string $name, string $mime, string $data): int
{
    app_files_table();
    $st = db()->prepare('INSERT INTO app_files (name, mime, data) VALUES (?,?,?)');
    $st->execute([$name, $mime, $data]);
    return (int)db()->lastInsertId();
}
function q(string $sql, array $p = []): PDOStatement { $st = db()->prepare($sql); $st->execute($p); return $st; }
function q_all(string $sql, array $p = []): array { return q($sql, $p)->fetchAll(); }
function q_one(string $sql, array $p = []) { $r = q($sql, $p)->fetch(); return $r === false ? null : $r; }
function q_val(string $sql, array $p = []) { $v = q($sql, $p)->fetchColumn(); return $v === false ? null : $v; }

function audit(string $action, string $module, string $desc = ''): void
{
    try {
        $u = $_SESSION['user'] ?? null;
        q('INSERT INTO audit_logs (user_id, username, action, module, description, ip_address) VALUES (?,?,?,?,?,?)',
          [$u['id'] ?? null, $u['username'] ?? null, $action, $module, $desc, $_SERVER['REMOTE_ADDR'] ?? null]);
    } catch (Throwable $e) { /* log gagal tidak boleh menghentikan aplikasi */ }
}

function setting(string $key, $default = '')
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try { foreach (q_all('SELECT setting_key, setting_value FROM settings') as $r) $cache[$r['setting_key']] = $r['setting_value']; }
        catch (Throwable $e) {}
    }
    return $cache[$key] ?? $default;
}

/* ---------------- autentikasi ---------------- */
function current_user(): ?array { return $_SESSION['user'] ?? null; }

function require_login(): array
{
    $u = current_user();
    if (!$u) redirect(app_url('login.php'));
    return $u;
}

/** URL relatif ke root aplikasi, aman dipanggil dari subfolder modules/. */
function app_url(string $path): string
{
    $root = str_replace('\\', '/', APP_ROOT);
    $doc  = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '');
    $base = ($doc && stripos($root, $doc) === 0) ? substr($root, strlen($doc)) : '';
    return rtrim($base, '/') . '/' . ltrim($path, '/');
}

/* ---------------- ikon SVG (satu gaya untuk semua menu) ---------------- */
function icon(string $name, int $size = 18): string
{
    $p = [
        'home'     => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/><path d="M9.5 21v-6h5v6"/>',
        'users'    => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c.6-3.6 3.2-5.5 6.5-5.5s5.9 1.9 6.5 5.5"/><circle cx="17.5" cy="9" r="2.5"/><path d="M17 14.6c2.4.2 4 1.8 4.5 4.4"/>',
        'shield'   => '<path d="M12 3 4.5 6v5.5c0 4.6 3.1 8.4 7.5 9.5 4.4-1.1 7.5-4.9 7.5-9.5V6L12 3Z"/><path d="m9 12 2 2 4-4"/>',
        'activity' => '<path d="M3 12h4l3-8 4 16 3-8h4"/>',
        'database' => '<ellipse cx="12" cy="5.5" rx="8" ry="3"/><path d="M4 5.5v13c0 1.7 3.6 3 8 3s8-1.3 8-3v-13"/><path d="M4 12c0 1.7 3.6 3 8 3s8-1.3 8-3"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1Z"/>',
        'chart'    => '<path d="M3 3v18h18"/><rect x="7" y="11" width="3" height="6" rx="1"/><rect x="12" y="7" width="3" height="10" rx="1"/><rect x="17" y="13" width="3" height="4" rx="1"/>',
        'user-plus'=> '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c.6-3.6 3.2-5.5 6.5-5.5s5.9 1.9 6.5 5.5"/><path d="M19 8v6M16 11h6"/>',
        'id-card'  => '<rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="11" r="2"/><path d="M6 16c.5-1.5 1.6-2.2 3-2.2s2.5.7 3 2.2M14 10h4M14 13h3"/>',
        'calendar' => '<rect x="3.5" y="5" width="17" height="15.5" rx="2"/><path d="M3.5 10h17M8 3v4M16 3v4"/>',
        'clock'    => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',
        'scan'     => '<path d="M4 8V5.5A1.5 1.5 0 0 1 5.5 4H8M16 4h2.5A1.5 1.5 0 0 1 20 5.5V8M20 16v2.5a1.5 1.5 0 0 1-1.5 1.5H16M8 20H5.5A1.5 1.5 0 0 1 4 18.5V16"/><path d="M8 12h8"/>',
        'file'     => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8Z"/><path d="M14 3v5h5M9 13h6M9 17h4"/>',
        'clipboard'=> '<rect x="5" y="4.5" width="14" height="16.5" rx="2"/><path d="M9 4.5V3h6v1.5M9 11h6M9 15h4"/>',
        'stetho'   => '<path d="M6 3v5a4 4 0 0 0 8 0V3"/><path d="M10 12v2a5 5 0 0 0 10 0v-1"/><circle cx="20" cy="11" r="2"/>',
        'bed'      => '<path d="M3 18V6M3 14h18v4M21 14v-2a3 3 0 0 0-3-3h-7v5"/><circle cx="7" cy="11" r="2"/>',
        'siren'    => '<path d="M7 18v-6a5 5 0 0 1 10 0v6"/><path d="M5 18h14v3H5zM12 3v2M4.2 6.2l1.4 1.4M19.8 6.2l-1.4 1.4"/>',
        'order'    => '<path d="M9 5h10M9 12h10M9 19h10"/><path d="m3.5 5 1 1 2-2M3.5 12l1 1 2-2M3.5 19l1 1 2-2"/>',
        'refresh'  => '<path d="M20 11a8 8 0 0 0-14.3-4.9L4 8"/><path d="M4 3v5h5M4 13a8 8 0 0 0 14.3 4.9L20 16"/><path d="M20 21v-5h-5"/>',
        'x-circle' => '<circle cx="12" cy="12" r="8.5"/><path d="m9 9 6 6M15 9l-6 6"/>',
        'heart'    => '<path d="M12 20s-7.5-4.4-7.5-10A4.3 4.3 0 0 1 12 7.6 4.3 4.3 0 0 1 19.5 10c0 5.6-7.5 10-7.5 10Z"/><path d="M8 12h2l1-2 2 4 1-2h2"/>',
        'wallet'   => '<path d="M4 7.5A2.5 2.5 0 0 1 6.5 5H18v3"/><rect x="4" y="8" width="17" height="12" rx="2"/><path d="M16 14h2"/>',
        'receipt'  => '<path d="M6 3h12v18l-3-2-3 2-3-2-3 2Z"/><path d="M9 8h6M9 12h6M9 16h3"/>',
        'printer'  => '<path d="M7 9V3h10v6"/><rect x="3" y="9" width="18" height="8" rx="2"/><path d="M7 14h10v7H7z"/>',
        'pill'     => '<rect x="3" y="8.5" width="18" height="7" rx="3.5" transform="rotate(-45 12 12)"/><path d="m9.5 9.5 5 5"/>',
        'box'      => '<path d="M21 8 12 3 3 8v8l9 5 9-5Z"/><path d="m3 8 9 5 9-5M12 13v8"/>',
        'truck'    => '<path d="M3 6h11v10H3zM14 9h4l3 3v4h-7"/><circle cx="7" cy="17.5" r="1.8"/><circle cx="17.5" cy="17.5" r="1.8"/>',
        'swap'     => '<path d="M7 4 3 8l4 4M3 8h14M17 20l4-4-4-4M21 16H7"/>',
        'check'    => '<circle cx="12" cy="12" r="8.5"/><path d="m8.5 12 2.5 2.5 4.5-5"/>',
        'flask'    => '<path d="M9 3h6M10 3v6L4.5 18.5A1.7 1.7 0 0 0 6 21h12a1.7 1.7 0 0 0 1.5-2.5L14 9V3"/><path d="M7 15h10"/>',
        'test'     => '<path d="M9 3h6M10 3v13a2 2 0 0 0 4 0V3M10 10h4"/>',
        'return'   => '<path d="M9 14 4 9l5-5"/><path d="M4 9h10.5a5.5 5.5 0 0 1 0 11H11"/>',
        'trend'    => '<path d="m3 17 6-6 4 4 8-8"/><path d="M15 7h6v6"/>',
        'audit'    => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5M8 11h6M11 8v6"/>',
        'user'     => '<circle cx="12" cy="8" r="4"/><path d="M4 21c.8-4 4-6.5 8-6.5s7.2 2.5 8 6.5"/>',
        'logout'   => '<path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3M10 17l5-5-5-5M15 12H3"/>',
        'bell'     => '<path d="M18 8.5a6 6 0 0 0-12 0c0 7-3 8.5-3 8.5h18s-3-1.5-3-8.5"/><path d="M10.3 21a1.9 1.9 0 0 0 3.4 0"/>',
        'search'   => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'menu'     => '<path d="M3 6h18M3 12h18M3 18h18"/>',
        'plus'     => '<path d="M12 5v14M5 12h14"/>',
        'edit'     => '<path d="M4 20h4L19 9a2.8 2.8 0 0 0-4-4L4 16v4Z"/><path d="m14 6 4 4"/>',
        'trash'    => '<path d="M4 7h16M10 11v6M14 11v6M5 7l1 13h12l1-13M9 7V4h6v3"/>',
        'key'      => '<circle cx="8" cy="15" r="4"/><path d="m11 12 9-9M17 6l3 3M15 8l2 2"/>',
        'power'    => '<path d="M12 3v9M6.3 6.3a8 8 0 1 0 11.4 0"/>',
        'money'    => '<rect x="2.5" y="6" width="19" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6 9v.01M18 15v.01"/>',
        'alert'    => '<path d="M12 3 2 20h20L12 3Z"/><path d="M12 10v4M12 17v.01"/>',
        'download' => '<path d="M12 3v12M7 10l5 5 5-5M4 21h16"/>',
        'eye'      => '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
        'hospital' => '<path d="M4 21V7l8-4 8 4v14"/><path d="M12 8v6M9 11h6M9 21v-4h6v4"/>',
    ];
    $d = $p[$name] ?? $p['file'];
    return '<svg class="ico" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $d . '</svg>';
}

/* ---------------- MENU PER ROLE (RBAC) ----------------
 * Format item: [key, label, icon, target, aksi_js]
 *  - target diawali "page:" = halaman PHP asli di folder pages/
 *  - target lain           = modul anggota yang dibuka di area kerja (iframe)
 *  - aksi_js               = fungsi navigasi di dalam modul tsb (opsional)
 */
function role_menus(string $role): array
{

    $profile = ['Akun', [['profil', 'Profil & Password', 'user', 'page:profile']]];

    $menus = [
        'admin' => [
            ['Manajemen Pengguna', [
                ['akun',   'Kelola Akun Pengguna', 'users',    'page:users'],
                ['akses',  'Role & Hak Akses',     'shield',   'page:roles'],
                ['audit',  'Log Aktivitas',        'activity', 'page:audit'],
            ]],
            ['Master Data & Konfigurasi', [
                ['master',     'Master Data',         'database', 'page:master'],
                ['pengaturan', 'Konfigurasi Sistem',  'settings', 'page:settings'],
            ]],
            ['Laporan', [
                ['laporan', 'Laporan & Statistik', 'chart', 'page:reports'],
            ]],
        ],
        'petugas_registrasi' => [
            ['Registrasi Pasien', [
                ['registrasi', 'Registrasi Pasien', 'user-plus', 'modules/claudia/pendaftaran_pasien.php'],
                ['pasien',     'Data Pasien',       'id-card',   'modules/claudia/data_pasien.php'],
            ]],
            ['Scheduling & Appointment', [
                ['appointment', 'Appointment',   'calendar', 'page:jadwal'],
                ['jadwal',      'Jadwal Dokter', 'clock',    'page:jadwal'],
            ]],
        ],
        'radiologist' => [
            ['Radiologi', [
                ['order', 'Order Radiologi',        'scan', 'page:radiologi'],
                ['hasil', 'Daftar Hasil Radiologi', 'file', 'page:radiologi'],
            ]],
        ],
        'dokter' => [
            ['Pelayanan Pasien', [
                ['rawatjalan', 'Rawat Jalan',   'stetho', 'page:rawatjalan'],
                ['rawatinap',  'Rawat Inap',    'bed',    'page:rawatinap'],
                ['igd',        'IGD / Emergency','siren', 'page:igd'],
            ]],
            ['Rekam Medis & Order', [
                ['emr',    'Electronic Medical Record', 'clipboard', 'page:emr'],
                ['cpoe',   'CPOE / Order',              'order',     'page:cpoe'],
                ['status', 'Pantau Status Order',       'refresh',   'page:cpoe'],
                ['cancel', 'Batalkan Order',            'x-circle',  'page:cpoe'],
            ]],
        ],
        'perawat' => [
            ['Asuhan Keperawatan', [
                ['emr',     'Electronic Medical Record', 'clipboard', 'page:emr'],
                ['nursing', 'Nursing',                   'heart',     'page:nursing'],
            ]],
            ['Pelayanan Pasien', [
                ['rawatinap', 'Rawat Inap',     'bed',   'page:rawatinap'],
                ['igd',       'IGD / Emergency','siren', 'page:igd'],
            ]],
        ],
        'kasir' => [
            ['Billing & Asuransi', [
                ['billing', 'Billing & Klaim',     'wallet',  'page:billing'],
                ['rekap',   'Rekapitulasi Biaya',  'receipt', 'page:billing'],
                ['riwayat', 'Riwayat Transaksi',   'clock',   'page:billing'],
                ['invoice', 'Cetak Invoice',       'printer', 'page:billing'],
            ]],
        ],
        'farmasi' => [
            ['Pelayanan Farmasi', [
                ['resep',      'Daftar Resep',     'file',   'page:farmasi'],
                ['dispensing', 'Dispensing Resep', 'pill',   'page:farmasi'],
                ['stok',       'Stok Obat',        'box',    'page:farmasi'],
                ['retur',      'Retur Obat',       'return', 'page:farmasi'],
                ['laporan',    'Laporan Farmasi',  'chart',  'page:farmasi'],
            ]],
        ],
        'laboratorium' => [
            ['Laboratorium', [
                ['orderlab',   'Order Laboratorium',  'flask', 'page:lab'],
                ['verifikasi', 'Verifikasi Spesimen', 'test',  'page:lab'],
                ['input',      'Input Hasil',         'edit',  'page:lab'],
                ['validasi',   'Validasi Hasil',      'check', 'page:lab'],
                ['hasil',      'Hasil Laboratorium',  'file',  'page:lab'],
            ]],
        ],
        'gudang' => [
            ['Inventory & Gudang', [
                ['inventory',   'Inventory',         'box',       'page:inventory'],
                ['receiving',   'Penerimaan Barang', 'truck',     'page:inventory'],
                ['mutation',    'Mutasi Barang',     'swap',      'page:inventory'],
                ['stockopname', 'Stock Opname',      'clipboard', 'page:inventory'],
            ]],
        ],
        'kepala_gudang' => [
            ['Inventory & Gudang', [
                ['inventory',   'Inventory',            'box',       'page:inventory'],
                ['receiving',   'Penerimaan Barang',    'truck',     'page:inventory'],
                ['mutation',    'Mutasi Barang',        'swap',      'page:inventory'],
                ['stockopname', 'Stock Opname',         'clipboard', 'page:inventory'],
                ['approval',    'Approval Stok Opname', 'check',     'page:inventory'],
                ['audit',       'Audit Log Gudang',     'audit',     'page:inventory'],
            ]],
        ],
        'executive' => [
            ['Manajemen', [
                ['executive', 'Executive Dashboard', 'trend', 'page:executive'],
                ['laporan',   'Laporan & Statistik', 'chart', 'page:reports'],
            ]],
        ],
        'analyst' => [
            ['Analisis Data', [
                ['laporan', 'Laporan & Statistik',    'chart', 'page:reports'],
                ['reports', 'Laporan Khusus / Rekap', 'file',  'page:analitik'],
                ['audit',   'Audit Log',              'audit', 'page:audit'],
            ]],
        ],
    ];

    if (!isset($menus[$role])) return [];
    return array_merge([['Utama', [['dashboard', 'Dashboard', 'home', 'page:dashboard']]]], $menus[$role], [$profile]);
}

/** Semua item menu role dalam satu array datar. */
function flat_menus(string $role): array
{
    $out = [];
    foreach (role_menus($role) as [$section, $items]) foreach ($items as $it) $out[$it[0]] = $it + [4 => ''];
    return $out;
}
