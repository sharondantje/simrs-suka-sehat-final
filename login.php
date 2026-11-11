<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (current_user()) redirect('portal.php');

$error   = '';
$dbError = !db_ok();

if (!$dbError && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    $row = q_one('SELECT u.*, r.code AS role_code, r.name AS role_name
                  FROM users u JOIN roles r ON r.id = u.role_id WHERE u.username = ?', [$username]);

    if ($row && password_verify($password, $row['password_hash'])) {
        if (!$row['is_active']) {
            $error = 'Akun Anda dinonaktifkan. Hubungi Admin IT.';
            audit('LOGIN_GAGAL', 'Authentication', "Akun nonaktif mencoba login: $username");
        } else {
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id'         => (int)$row['id'],
                'username'   => $row['username'],
                'name'       => $row['full_name'],
                'role'       => $row['role_code'],
                'role_label' => $row['role_name'],
                'unit'       => $row['unit'],
                'last_login' => $row['last_login'],
            ];
            q('UPDATE users SET last_login = NOW() WHERE id = ?', [$row['id']]);
            audit('LOGIN', 'Authentication', 'Login berhasil');
            redirect('portal.php');
        }
    } else {
        $error = 'Username atau password salah.';
        audit('LOGIN_GAGAL', 'Authentication', 'Percobaan login gagal untuk username: ' . mb_substr($username, 0, 40));
    }
}

$demo = [];
if (!$dbError) {
    $demo = q_all('SELECT u.username, r.name AS role FROM users u JOIN roles r ON r.id=u.role_id WHERE u.id <= 12 ORDER BY u.id');
}
$demoPass = [
    'admin'=>'admin123','petugas'=>'petugas123','radiolog'=>'radiolog123','dokter'=>'dokter123','perawat'=>'perawat123',
    'kasir'=>'kasir123','farmasi'=>'farmasi123','laboran'=>'laboran123','gudang'=>'gudang123','kepala_gudang'=>'head123',
    'executive'=>'exec123','analyst'=>'analyst123',
];
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Login · SIMRS RS Suka Sehat</title>
<link rel="icon" href="assets/logo.png">
<link rel="stylesheet" href="assets/simrs.css">
</head>
<body class="login-page">
<main class="login-shell">
  <div class="login-brand">
    <img src="assets/logo.png" alt="Logo RS Suka Sehat">
    <h1>RS <span>Suka</span> Sehat</h1>
    <p>Sistem Informasi Manajemen Rumah Sakit (SIMRS)</p>
  </div>

  <section class="login-card">
    <h2>Masuk ke Sistem</h2>
    <p class="sub">Gunakan akun yang diberikan oleh Admin IT</p>

    <?php if ($dbError): ?>
      <div class="alert danger"><?= icon('alert', 16) ?><span><b>Database belum terhubung.</b><br>
        Start MySQL di XAMPP, lalu import <code>database/simrs_suka_sehat.sql</code> lewat phpMyAdmin (menu Import).
        Cek juga pengaturan di <code>config/database.php</code>.</span></div>
    <?php endif; ?>
    <?php if ($error): ?><div class="alert danger"><?= icon('alert', 16) ?><span><?= e($error) ?></span></div><?php endif; ?>

    <form method="post" autocomplete="off">
      <div class="field">
        <label for="username">Username</label>
        <div class="input-icon"><?= icon('user', 16) ?><input id="username" name="username" required autofocus placeholder="Masukkan username Anda" value="<?= e($_POST['username'] ?? '') ?>"></div>
      </div>
      <div class="field">
        <label for="password">Password</label>
        <div class="input-icon"><?= icon('key', 16) ?><input id="password" name="password" type="password" required placeholder="Masukkan password Anda">
          <button type="button" class="toggle" onclick="const p=document.getElementById('password');p.type=p.type==='password'?'text':'password'" aria-label="Tampilkan password"><?= icon('eye', 16) ?></button></div>
      </div>
      <button class="btn btn-primary" type="submit" <?= $dbError ? 'disabled' : '' ?>><?= icon('logout', 17) ?> Masuk ke Sistem</button>
    </form>
    <p class="login-foot">Lupa password? Hubungi <b style="color:var(--primary)">Admin IT</b></p>

    <?php if ($demo): ?>
    <div class="demo-box">
      <h3>Akun Demo (klik untuk mengisi)</h3>
      <div class="demo-grid">
        <?php foreach ($demo as $d): ?>
          <button type="button" class="demo-account" onclick="fillDemo('<?= e($d['username']) ?>','<?= e($demoPass[$d['username']] ?? '') ?>')">
            <b><?= e($d['username']) ?></b><span><?= e($d['role']) ?></span>
          </button>
        <?php endforeach; ?>
      </div>
      <p class="demo-pass">Password demo: <b>username</b> + <b>123</b> (contoh: admin123). Kepala gudang: head123, executive: exec123.</p>
    </div>
    <?php endif; ?>
  </section>
  <p class="copyright">SIMRS v2.0 © <?= date('Y') ?> RS Suka Sehat</p>
</main>
<script>
function fillDemo(u,p){document.getElementById('username').value=u;document.getElementById('password').value=p;document.getElementById('password').focus();}
</script>
</body>
</html>
