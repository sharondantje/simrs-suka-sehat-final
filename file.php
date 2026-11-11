<?php
/* Menampilkan file unggahan yang disimpan di database (gambar radiologi di hosting Vercel). Hanya untuk pengguna yang login. */
require_once __DIR__ . '/includes/bootstrap.php';
require_login();
$f = q_one('SELECT name, mime, data FROM app_files WHERE id=?', [(int)($_GET['id'] ?? 0)]);
if (!$f) { http_response_code(404); exit('File tidak ditemukan.'); }
header('Content-Type: ' . $f['mime']);
header('Content-Length: ' . strlen($f['data']));
header('Content-Disposition: inline; filename="' . preg_replace('/[^\w.\-]/', '_', $f['name']) . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=3600');
echo $f['data'];
