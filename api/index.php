<?php
/*
 * FRONT CONTROLLER UNTUK VERCEL (runtime vercel-php).
 * Vercel hanya menjalankan PHP dari folder api/, jadi semua request halaman .php
 * diarahkan ke file ini (lihat vercel.json), lalu file ini menjalankan halaman
 * aslinya (login.php, portal.php, modules/claudia/..., dst).
 * Di XAMPP file ini tidak dipakai.
 */
$__root = dirname(__DIR__);
$__path = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');

$__notFound = static function (): void {
    http_response_code(404);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><meta charset="utf-8"><title>404 — Halaman tidak ditemukan</title>'
       . '<div style="font-family:Inter,system-ui,sans-serif;max-width:520px;margin:12vh auto;padding:0 16px;color:#0F172A">'
       . '<h1 style="font-size:24px">Halaman tidak ditemukan</h1>'
       . '<p style="color:#475569">Alamat yang dibuka tidak ada di SIMRS RS Suka Sehat.</p>'
       . '<p><a href="/login.php" style="color:#0D6E6E">Kembali ke halaman login</a></p></div>';
    exit;
};

// Folder internal tidak boleh dibuka langsung dari browser
if (preg_match('#(^|/)\.|^/(config|includes|database|api)(/|$)#i', $__path)) $__notFound();

if ($__path === '' || substr($__path, -1) === '/') $__path .= 'index.php';
$__file = realpath($__root . $__path);

if ($__file !== false && is_dir($__file)) {          // /modules/claudia  ->  /modules/claudia/
    $qs = $_SERVER['QUERY_STRING'] ?? '';
    header('Location: ' . $__path . '/' . ($qs !== '' ? '?' . $qs : ''), true, 301);
    exit;
}
if ($__file === false || !is_file($__file) || strncmp($__file, $__root . DIRECTORY_SEPARATOR, strlen($__root) + 1) !== 0
    || strtolower(pathinfo($__file, PATHINFO_EXTENSION)) !== 'php') {
    $__notFound();
}

// Variabel server disesuaikan seolah-olah halaman asli yang dipanggil
$_SERVER['SCRIPT_FILENAME'] = $__file;
$_SERVER['SCRIPT_NAME']     = $__path;
$_SERVER['PHP_SELF']        = $__path;
$_SERVER['DOCUMENT_ROOT']   = $__root;
if (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') $_SERVER['HTTPS'] = 'on';
chdir(dirname($__file));

// Sesi login disimpan di database (serverless tidak punya penyimpanan file permanen)
require_once $__root . '/includes/session_db.php';

unset($__root, $__path, $__notFound);
require $__file;
