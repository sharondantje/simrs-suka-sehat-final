<?php
/*
 * INSTALASI DATABASE ONLINE (untuk Vercel / hosting tanpa phpMyAdmin)
 * Menjalankan database/simrs_suka_sehat.sql ke database yang diatur di Environment Variable.
 * Keamanan: hanya aktif bila Environment Variable SIMRS_SETUP_KEY diisi, dan kunci yang
 * dimasukkan harus sama. Di XAMPP cukup import file .sql lewat phpMyAdmin.
 */
require_once __DIR__ . '/config/database.php';
header('Content-Type: text/html; charset=utf-8');
header('X-Robots-Tag: noindex');

$setupKey = (string)getenv('SIMRS_SETUP_KEY');
$given    = (string)($_POST['key'] ?? $_GET['key'] ?? '');
$enabled  = strlen($setupKey) >= 6;
$authed   = $enabled && hash_equals($setupKey, $given);
$log = []; $last = []; $status = null; $tables = null; $err = null;

function setup_pdo(): PDO
{
    try { return db(); }
    catch (PDOException $e) {
        if ((int)($e->errorInfo[1] ?? 0) !== 1049) throw $e;          // 1049 = database belum ada
        $srv = new PDO('mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';charset=utf8mb4', DB_USER, DB_PASS, db_options());
        $srv->exec('CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '', DB_NAME) . '` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        return db();
    }
}

function setup_statements(string $sql): array
{
    $sql = preg_replace('/^\s*--.*$/m', '', $sql);                      // buang komentar
    $out = [];
    foreach (preg_split('/;\s*(?:\r?\n|$)/', $sql) as $st) {
        $st = trim($st);
        if ($st === '' || preg_match('/^(CREATE\s+DATABASE|USE\s)/i', $st)) continue;   // pakai database dari konfigurasi
        $out[] = $st;
    }
    return $out;
}

if ($authed) {
    try {
        $pdo = setup_pdo();
        $tables = (int)$pdo->query('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()')->fetchColumn();
        $installed = (bool)$pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'users'")->fetchColumn();
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['act'] ?? '') === 'install') {
            if ($installed && empty($_POST['reset'])) {
                $status = ['warn', 'Database sudah terisi. Centang "Hapus dan isi ulang" bila ingin mengulang dari data contoh.'];
            } else {
                @set_time_limit(120);
                $pending = setup_statements((string)file_get_contents(__DIR__ . '/database/simrs_suka_sehat.sql'));
                $total = count($pending); $ok = 0;
                for ($pass = 1; $pending && $pass <= 4; $pass++) {               // ulangi yang gagal (urutan relasi)
                    $failed = [];
                    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
                    foreach ($pending as $st) {
                        try { $pdo->exec($st); $ok++; }
                        catch (PDOException $e) { $failed[] = $st; $last[md5($st)] = $e->getMessage(); }
                    }
                    if (count($failed) === count($pending)) break;
                    $pending = $failed;
                }
                $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
                $pdo->exec('CREATE TABLE IF NOT EXISTS app_sessions (id VARCHAR(128) NOT NULL PRIMARY KEY, data MEDIUMBLOB NOT NULL,
                            expires_at INT UNSIGNED NOT NULL, KEY idx_app_sessions_expires (expires_at)) DEFAULT CHARSET=utf8mb4');
                $pdo->exec('CREATE TABLE IF NOT EXISTS app_files (id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, name VARCHAR(190) NOT NULL,
                            mime VARCHAR(100) NOT NULL, data LONGBLOB NOT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP) DEFAULT CHARSET=utf8mb4');
                foreach ($pending as $st) $log[] = substr(preg_replace('/\s+/', ' ', $st), 0, 120) . ' … → ' . ($last[md5($st)] ?? '');
                $status = $pending
                    ? ['danger', "Selesai dengan " . count($pending) . " perintah gagal dari $total. Lihat detail di bawah."]
                    : ['ok', "Berhasil! $total perintah SQL dijalankan. Database siap dipakai."];
                $tables = (int)$pdo->query('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()')->fetchColumn();
                $installed = true;
            }
        }
    } catch (Throwable $e) {
        $err = $e->getMessage();
    }
}
$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?><!doctype html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Setup Database — SIMRS RS Suka Sehat</title>
<style>
  :root{--primary:#0D6E6E;--navy:#0F172A;--muted:#475569;--bg:#F2F5F9;--card:#fff;--line:#E2E8F0;--ok:#15803D;--warn:#B45309;--danger:#B91C1C}
  *{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--navy);font:14px/1.6 Inter,system-ui,-apple-system,"Segoe UI",sans-serif}
  .wrap{max-width:640px;margin:6vh auto;padding:0 16px}.card{background:var(--card);border:1px solid var(--line);border-radius:12px;padding:24px;margin-bottom:16px}
  h1{font-size:22px;margin:0 0 4px}p{margin:6px 0}.muted{color:var(--muted)}code,.mono{font-family:"JetBrains Mono",ui-monospace,Consolas,monospace;font-size:12.5px}
  input[type=password],input[type=text]{width:100%;height:40px;padding:0 12px;border:1px solid var(--line);border-radius:8px;font:inherit}
  button{height:40px;padding:0 18px;border:0;border-radius:8px;background:var(--primary);color:#fff;font:600 14px Inter,system-ui,sans-serif;cursor:pointer}
  .msg{padding:12px 14px;border-radius:8px;margin:12px 0;font-weight:500}.ok{background:#DCFCE7;color:var(--ok)}.warn{background:#FEF3C7;color:var(--warn)}.danger{background:#FEE2E2;color:var(--danger)}
  dl{display:grid;grid-template-columns:140px 1fr;gap:6px 12px;margin:12px 0}dt{color:var(--muted)}dd{margin:0;word-break:break-all}
  ul{padding-left:18px}li{margin:4px 0;word-break:break-word}
</style></head>
<body><div class="wrap">
  <div class="card">
    <h1>Setup Database Online</h1>
    <p class="muted">SIMRS RS Suka Sehat — mengisi database online dengan tabel dan data contoh (sekali saja).</p>

    <?php if (!$enabled): ?>
      <div class="msg warn">Halaman setup belum aktif. Tambahkan Environment Variable <code>SIMRS_SETUP_KEY</code>
        (minimal 6 karakter) di Vercel → Settings → Environment Variables, lalu Redeploy.</div>
    <?php elseif (!$authed): ?>
      <?php if ($given !== ''): ?><div class="msg danger">Kunci setup salah.</div><?php endif; ?>
      <form method="post"><p><label for="k">Kunci setup (isi sama dengan SIMRS_SETUP_KEY)</label></p>
        <p><input type="password" id="k" name="key" required autofocus></p><p><button>Lanjut</button></p></form>
    <?php else: ?>
      <dl><dt>Server</dt><dd class="mono"><?= $e(DB_HOST) ?>:<?= $e(DB_PORT) ?></dd>
          <dt>Database</dt><dd class="mono"><?= $e(DB_NAME) ?></dd>
          <dt>SSL</dt><dd><?= DB_SSL ? 'Aktif' : 'Tidak' ?></dd>
          <dt>Jumlah tabel</dt><dd><?= $tables === null ? '-' : $e($tables) ?></dd></dl>
      <?php if ($err): ?><div class="msg danger">Tidak bisa terhubung ke database: <span class="mono"><?= $e($err) ?></span></div>
        <p class="muted">Periksa kembali DATABASE_URL di Vercel (host, port, user, password, nama database), lalu Redeploy.</p>
      <?php else: ?>
        <?php if ($status): ?><div class="msg <?= $e($status[0]) ?>"><?= $e($status[1]) ?></div><?php endif; ?>
        <?php if ($status && $status[0] === 'ok'): ?>
          <p><a href="login.php" style="color:var(--primary);font-weight:600">→ Buka halaman login</a> (contoh: admin / admin123)</p>
        <?php endif; ?>
        <form method="post"><input type="hidden" name="key" value="<?= $e($given) ?>"><input type="hidden" name="act" value="install">
          <?php if (!empty($installed)): ?><p><label><input type="checkbox" name="reset" value="1"> Hapus dan isi ulang dengan data contoh</label></p><?php endif; ?>
          <p><button>Install / Isi Database</button></p></form>
      <?php endif; ?>
    <?php endif; ?>
  </div>
  <?php if ($log): ?><div class="card"><b>Perintah yang gagal</b><ul class="mono"><?php foreach ($log as $l): ?><li><?= $e($l) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
</div></body></html>
