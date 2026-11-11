<?php
/* PROFIL & GANTI PASSWORD — tersedia untuk semua role */
$self = 'portal.php?page=' . $PAGE['key'];
$me   = q_one('SELECT u.*, r.name role_name FROM users u JOIN roles r ON r.id=u.role_id WHERE u.id=?', [$user['id']]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_POST['act'] ?? '') === 'profile') {
        q('UPDATE users SET full_name=?, email=?, phone=? WHERE id=?', [trim($_POST['full_name']), trim($_POST['email']) ?: null, trim($_POST['phone']) ?: null, $user['id']]);
        $_SESSION['user']['name'] = trim($_POST['full_name']);
        audit('UPDATE', 'Profil', 'Memperbarui profil');
        flash('success', 'Profil berhasil diperbarui.');
    } else {
        $min = (int)setting('password_min_length', 8);
        if (!password_verify((string)$_POST['old'], $me['password_hash'])) flash('danger', 'Password lama salah.');
        elseif (strlen((string)$_POST['new']) < $min) flash('danger', "Password baru minimal $min karakter.");
        elseif ($_POST['new'] !== $_POST['confirm']) flash('danger', 'Konfirmasi password tidak sama.');
        else {
            q('UPDATE users SET password_hash=? WHERE id=?', [password_hash((string)$_POST['new'], PASSWORD_DEFAULT), $user['id']]);
            audit('UPDATE', 'Profil', 'Mengganti password');
            flash('success', 'Password berhasil diganti.');
        }
    }
    redirect($self);
}

$logs = q_all('SELECT * FROM audit_logs WHERE user_id=? ORDER BY created_at DESC LIMIT 8', [$user['id']]);
layout_start($PAGE['title'], $PAGE['key'], $PAGE['section']);
?>
<div class="content">
  <div class="page-head"><div><h2>Profil Saya</h2><p>Perbarui data diri dan ganti password akun Anda.</p></div></div>
  <?php flashes(); ?>
  <section class="welcome" style="margin-bottom:20px">
    <div style="display:flex;gap:16px;align-items:center;position:relative;z-index:1">
      <div class="avatar" style="width:64px;height:64px;font-size:22px;background:rgba(255,255,255,.18)"><?= e(initials($me['full_name'])) ?></div>
      <div><h2><?= e($me['full_name']) ?></h2><p>@<?= e($me['username']) ?> · <?= e($me['role_name']) ?> · <?= e($me['unit']) ?></p></div>
    </div>
  </section>
  <section class="grid grid-2e mb">
    <form method="post" class="card">
      <?= csrf_field() ?><input type="hidden" name="act" value="profile">
      <div class="card-head"><h3>Data Diri</h3><button class="btn btn-primary"><?= icon('check', 16) ?> Simpan</button></div>
      <div class="card-body">
        <div class="field"><label>Nama Lengkap</label><input name="full_name" value="<?= e($me['full_name']) ?>" required></div>
        <div class="form-grid">
          <div class="field"><label>Email</label><input name="email" type="email" value="<?= e($me['email']) ?>"></div>
          <div class="field"><label>No. HP</label><input name="phone" value="<?= e($me['phone']) ?>"></div>
        </div>
        <div class="form-grid">
          <div class="field"><label>Username</label><input value="<?= e($me['username']) ?>" disabled></div>
          <div class="field"><label>Role</label><input value="<?= e($me['role_name']) ?>" disabled></div>
        </div>
      </div>
    </form>
    <form method="post" class="card">
      <?= csrf_field() ?><input type="hidden" name="act" value="password">
      <div class="card-head"><h3>Ganti Password</h3><button class="btn btn-primary"><?= icon('key', 16) ?> Ganti</button></div>
      <div class="card-body">
        <div class="field"><label>Password Lama</label><input name="old" type="password" required></div>
        <div class="field"><label>Password Baru</label><input name="new" type="password" required minlength="<?= (int)setting('password_min_length', 8) ?>"></div>
        <div class="field"><label>Konfirmasi Password Baru</label><input name="confirm" type="password" required></div>
      </div>
    </form>
  </section>
  <div class="card">
    <div class="card-head"><div><h3>Aktivitas Terakhir Saya</h3><p>Dari audit log</p></div></div>
    <div class="table-wrap"><table class="table">
      <thead><tr><th>Waktu</th><th>Aksi</th><th>Modul</th><th>Keterangan</th></tr></thead>
      <tbody>
        <?php if (!$logs): ?><tr><td colspan="4" class="empty">Belum ada aktivitas.</td></tr><?php endif; ?>
        <?php foreach ($logs as $l): ?><tr><td class="mono"><?= tgl($l['created_at'], true) ?></td><td><span class="badge info"><?= e($l['action']) ?></span></td><td><?= e($l['module']) ?></td><td><?= e($l['description']) ?></td></tr><?php endforeach; ?>
      </tbody>
    </table></div>
  </div>
</div>
<?php layout_end();
