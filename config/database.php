<?php
/*
 * KONFIGURASI DATABASE (MySQL / phpMyAdmin XAMPP)
 * Default XAMPP: host localhost, user root, password kosong, port 3306.
 * Ubah nilai di bawah jika pengaturan MySQL Anda berbeda.
 *
 * Untuk hosting online (Vercel), isi Environment Variable:
 *   DATABASE_URL = mysql://USER:PASSWORD@HOST:PORT/NAMA_DATABASE
 * (atau SIMRS_DB_HOST / SIMRS_DB_PORT / SIMRS_DB_NAME / SIMRS_DB_USER / SIMRS_DB_PASS).
 * Koneksi ke server online otomatis memakai SSL (wajib untuk TiDB Cloud / Aiven).
 */
$__url = getenv('SIMRS_DATABASE_URL') ?: (getenv('DATABASE_URL') ?: '');
$__u = $__url ? parse_url($__url) : [];
$__env = fn(string $k, $d) => (($v = getenv($k)) !== false && $v !== '') ? $v : $d;

define('DB_HOST', $__env('SIMRS_DB_HOST', $__u['host'] ?? '127.0.0.1'));
define('DB_PORT', $__env('SIMRS_DB_PORT', (string)($__u['port'] ?? '3306')));
define('DB_NAME', $__env('SIMRS_DB_NAME', trim($__u['path'] ?? '', '/') ?: 'simrs_suka_sehat'));
define('DB_USER', $__env('SIMRS_DB_USER', isset($__u['user']) ? rawurldecode($__u['user']) : 'root'));
define('DB_PASS', getenv('SIMRS_DB_PASS') !== false ? getenv('SIMRS_DB_PASS') : (isset($__u['pass']) ? rawurldecode($__u['pass']) : ''));
// SSL: otomatis aktif untuk server selain localhost; bisa dipaksa dengan SIMRS_DB_SSL=1 / 0
define('DB_SSL', ($s = getenv('SIMRS_DB_SSL')) !== false && $s !== ''
    ? in_array(strtolower($s), ['1', 'true', 'yes', 'on'], true)
    : !in_array(DB_HOST, ['127.0.0.1', 'localhost', '::1'], true));
unset($__url, $__u, $__env, $s);

function db_options(): array
{
    $opt = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    if (DB_SSL) {
        $opt[PDO::MYSQL_ATTR_SSL_CA] = getenv('SIMRS_DB_SSL_CA') ?: __DIR__ . '/cacert.pem';   // bundel CA publik (Mozilla)
        $opt[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = getenv('SIMRS_DB_SSL_VERIFY') !== '0';
    }
    return $opt;
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, db_options());
        // Samakan zona waktu database dengan PHP (WIB) dan matikan ONLY_FULL_GROUP_BY (MySQL 8 / TiDB)
        try {
            $pdo->exec("SET time_zone = '+07:00'");
            $pdo->exec("SET SESSION sql_mode = REPLACE(@@SESSION.sql_mode, 'ONLY_FULL_GROUP_BY', '')");
        } catch (Throwable $e) { /* abaikan bila server tidak mendukung */ }
    }
    return $pdo;
}
