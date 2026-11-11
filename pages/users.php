<?php
/* KELOLA AKUN PENGGUNA — Modul 1 Authentication & User Management (CRUD tabel users) */
$self = 'portal.php?page=' . $PAGE['key'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $act = $_POST['act'] ?? '';
    $id  = (int)($_POST['id'] ?? 0);
    $minLen = (int)setting('password_min_length', 8);
    try {
        if ($act === 'save') {
            $data = [trim((string)($_POST['full_name'] ?? '')), trim((string)($_POST['email'] ?? '')) ?: null, trim((string)($_POST['phone'] ?? '')) ?: null, (int)($_POST['role_id'] ?? 0), trim((string)($_POST['unit'] ?? '')) ?: null];
            if ($data[0] === '') throw new RuntimeException('Nama lengkap wajib diisi.');
            if (!q_val('SELECT 1 FROM roles WHERE id=?', [$data[3]])) throw new RuntimeException('Pilih role yang valid.');
            if ($data[1] !== null && !filter_var($data[1], FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Format email tidak valid.');
            if ($id) {
                q('UPDATE users SET full_name=?, email=?, phone=?, role_id=?, unit=? WHERE id=?', array_merge($data, [$id]));
                audit('UPDATE', 'User Management', 'Ubah data akun ID ' . $id);
                flash('success', 'Data akun berhasil diperbarui.');
            } else {
                $username = strtolower(trim($_POST['username']));
                $pass = (string)$_POST['password'];
                if (!preg_match('/^[a-z0-9._]{3,50}$/', $username)) throw new RuntimeException('Username hanya boleh huruf kecil, angka, titik, garis bawah (3–50 karakter).');
                if (strlen($pass) < $minLen) throw new RuntimeException("Password minimal $minLen karakter.");
                if (q_val('SELECT COUNT(*) FROM users WHERE username=?', [$username])) throw new RuntimeException('Username sudah dipakai.');
                q('INSERT INTO users (username, password_hash, full_name, email, phone, role_id, unit) VALUES (?,?,?,?,?,?,?)',
                  array_merge([$username, password_hash($pass, PASSWORD_DEFAULT)], $data));
                audit('CREATE', 'User Management', 'Tambah akun ' . $username);
                flash('success', "Akun $username berhasil dibuat.");
            }
        } elseif ($act === 'reset' && $id) {
            $pass = (string)$_POST['password'];
            if (strlen($pass) < $minLen) throw new RuntimeException("Password minimal $minLen karakter.");
            q('UPDATE users SET password_hash=? WHERE id=?', [password_hash($pass, PASSWORD_DEFAULT), $id]);
            audit('RESET_PASSWORD', 'User Management', 'Reset password akun ID ' . $id);
            flash('success', 'Password berhasil direset.');
        } elseif ($act === 'toggle' && $id) {
            if ($id === (int)$user['id']) throw new RuntimeException('Anda tidak dapat menonaktifkan akun sendiri.');
            q('UPDATE users SET is_active = 1 - is_active WHERE id=?', [$id]);
            audit('UPDATE', 'User Management', 'Ubah status aktif akun ID ' . $id);
            flash('success', 'Status akun diperbarui.');
        } elseif ($act === 'delete' && $id) {
            if ($id === (int)$user['id']) throw new RuntimeException('Anda tidak dapat menghapus akun sendiri.');
            q('DELETE FROM users WHERE id=?', [$id]);
            audit('DELETE', 'User Management', 'Hapus akun ID ' . $id);
            flash('success', 'Akun dihapus.');
        }
    } catch (PDOException $ex) {
        flash('danger', $act === 'delete'
            ? 'Akun tidak dapat dihapus karena sudah dipakai di data lain. Nonaktifkan saja akunnya.'
            : 'Data akun gagal disimpan (kemungkinan email/username sudah dipakai akun lain).');
    } catch (RuntimeException $ex) {
        flash('danger', $ex->getMessage());
    }
    redirect($self);
}

$roles  = q_all('SELECT * FROM roles ORDER BY id');
$search = trim($_GET['q'] ?? '');
$fRole  = (int)($_GET['role'] ?? 0);
$fStat  = $_GET['status'] ?? '';

$where = ['1=1']; $p = [];
if ($search !== '') { $where[] = '(u.username LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)'; array_push($p, "%$search%", "%$search%", "%$search%"); }
if ($fRole) { $where[] = 'u.role_id = ?'; $p[] = $fRole; }
if ($fStat !== '') { $where[] = 'u.is_active = ?'; $p[] = (int)$fStat; }
$users = q_all('SELECT u.*, r.name role_name FROM users u JOIN roles r ON r.id=u.role_id WHERE ' . implode(' AND ', $where) . ' ORDER BY u.id', $p);

$total  = q_val('SELECT COUNT(*) FROM users');
$active = q_val('SELECT COUNT(*) FROM users WHERE is_active=1');

layout_start($PAGE['title'], $PAGE['key'], $PAGE['section']);
?>
<div class="content">
  <div class="page-head">
    <div><h2>Kelola Akun Pengguna</h2><p>Tambah, ubah, reset password, dan nonaktifkan akun pengguna SIMRS.</p></div>
    <div class="page-actions"><button class="btn btn-primary" data-modal="mUser" onclick="editUser(null)"><?= icon('plus', 16) ?> Tambah Akun</button></div>
  </div>
  <?php flashes(); ?>

  <section class="kpi-grid">
    <div class="card kpi"><div class="kpi-icon"><?= icon('users', 22) ?></div><div><div class="kpi-label">Total Akun</div><div class="kpi-value"><?= $total ?></div><div class="kpi-hint">terdaftar di sistem</div></div></div>
    <div class="card kpi"><div class="kpi-icon info"><?= icon('check', 22) ?></div><div><div class="kpi-label">Akun Aktif</div><div class="kpi-value"><?= $active ?></div><div class="kpi-hint">dapat login</div></div></div>
    <div class="card kpi"><div class="kpi-icon danger"><?= icon('power', 22) ?></div><div><div class="kpi-label">Nonaktif</div><div class="kpi-value"><?= $total - $active ?></div><div class="kpi-hint">tidak dapat login</div></div></div>
    <div class="card kpi"><div class="kpi-icon purple"><?= icon('shield', 22) ?></div><div><div class="kpi-label">Role</div><div class="kpi-value"><?= count($roles) ?></div><div class="kpi-hint">jenis hak akses</div></div></div>
  </section>

  <div class="card">
    <form class="toolbar" method="get">
      <input type="hidden" name="page" value="<?= e($PAGE['key']) ?>">
      <input class="input grow" name="q" value="<?= e($search) ?>" placeholder="Cari username, nama, atau email...">
      <select class="input" name="role"><option value="">Semua Role</option><?php foreach ($roles as $r): ?><option value="<?= $r['id'] ?>" <?= $fRole === (int)$r['id'] ? 'selected' : '' ?>><?= e($r['name']) ?></option><?php endforeach; ?></select>
      <select class="input" name="status"><option value="">Semua Status</option><option value="1" <?= $fStat === '1' ? 'selected' : '' ?>>Aktif</option><option value="0" <?= $fStat === '0' ? 'selected' : '' ?>>Nonaktif</option></select>
      <button class="btn"><?= icon('search', 15) ?> Filter</button>
    </form>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>Pengguna</th><th>Username</th><th>Role</th><th>Unit</th><th>Login Terakhir</th><th>Status</th><th style="text-align:right">Aksi</th></tr></thead>
        <tbody>
        <?php if (!$users): ?><tr><td colspan="7" class="empty">Tidak ada akun yang cocok.</td></tr><?php endif; ?>
        <?php foreach ($users as $r): ?>
          <tr>
            <td><div style="display:flex;gap:10px;align-items:center"><div class="avatar" style="width:34px;height:34px;font-size:12px"><?= e(initials($r['full_name'])) ?></div><div><span class="strong"><?= e($r['full_name']) ?></span><small><?= e($r['email']) ?></small></div></div></td>
            <td><?= e($r['username']) ?></td>
            <td><span class="badge info"><?= e($r['role_name']) ?></span></td>
            <td><?= e($r['unit']) ?></td>
            <td class="mono"><?= tgl($r['last_login'], true) ?></td>
            <td><?= $r['is_active'] ? '<span class="badge success">Aktif</span>' : '<span class="badge danger">Nonaktif</span>' ?></td>
            <td><div class="actions">
              <button class="icon-btn" title="Ubah" data-modal="mUser" onclick='editUser(<?= json_encode($r, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'><?= icon('edit', 15) ?></button>
              <button class="icon-btn" title="Reset password" data-modal="mReset" onclick="document.getElementById('resetId').value=<?= $r['id'] ?>;document.getElementById('resetName').textContent='<?= e($r['username']) ?>'"><?= icon('key', 15) ?></button>
              <form method="post" data-confirm="Status aktif akun ini akan diubah. Akun nonaktif tidak bisa login." data-confirm-title="Ubah Status Akun" data-confirm-ok="Ubah Status"><?= csrf_field() ?><input type="hidden" name="act" value="toggle"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="icon-btn" title="Aktif/Nonaktifkan"><?= icon('power', 15) ?></button></form>
              <form method="post" data-confirm="Hapus akun <?= e($r['username']) ?>? Tindakan ini tidak dapat dibatalkan." data-confirm-title="Hapus Akun" data-confirm-ok="Hapus"><?= csrf_field() ?><input type="hidden" name="act" value="delete"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="icon-btn danger" title="Hapus"><?= icon('trash', 15) ?></button></form>
            </div></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="modal" id="mUser"><div class="modal-box">
  <div class="card-head"><h3 id="mUserTitle">Tambah Akun</h3><button class="icon-btn" data-close type="button">✕</button></div>
  <form method="post">
    <?= csrf_field() ?><input type="hidden" name="act" value="save"><input type="hidden" name="id" id="uId">
    <div class="form-grid">
      <div class="field"><label>Username</label><input name="username" id="uUsername" required></div>
      <div class="field" id="uPassWrap"><label>Password</label><input name="password" type="password" id="uPass" minlength="<?= (int)setting('password_min_length', 8) ?>"></div>
      <div class="field full"><label>Nama Lengkap</label><input name="full_name" id="uName" required></div>
      <div class="field"><label>Email</label><input name="email" type="email" id="uEmail"></div>
      <div class="field"><label>No. HP</label><input name="phone" id="uPhone"></div>
      <div class="field"><label>Role</label><select name="role_id" id="uRole"><?php foreach ($roles as $r): ?><option value="<?= $r['id'] ?>"><?= e($r['name']) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label>Unit Kerja</label><input name="unit" id="uUnit"></div>
    </div>
    <div class="modal-foot"><button type="button" class="btn" data-close>Batal</button><button class="btn btn-primary">Simpan</button></div>
  </form>
</div></div>

<div class="modal" id="mReset"><div class="modal-box">
  <div class="card-head"><h3>Reset Password · <span id="resetName"></span></h3><button class="icon-btn" data-close type="button">✕</button></div>
  <form method="post">
    <?= csrf_field() ?><input type="hidden" name="act" value="reset"><input type="hidden" name="id" id="resetId">
    <div class="field"><label>Password Baru</label><input name="password" type="password" required minlength="<?= (int)setting('password_min_length', 8) ?>"></div>
    <div class="modal-foot"><button type="button" class="btn" data-close>Batal</button><button class="btn btn-primary">Reset Password</button></div>
  </form>
</div></div>

<script>
function editUser(u){
  const $=id=>document.getElementById(id);
  $('mUserTitle').textContent=u?'Ubah Akun':'Tambah Akun';
  $('uId').value=u?u.id:''; $('uUsername').value=u?u.username:''; $('uUsername').readOnly=!!u;
  $('uPassWrap').style.display=u?'none':''; $('uPass').required=!u; $('uPass').value='';
  $('uName').value=u?u.full_name:''; $('uEmail').value=u?(u.email||''):''; $('uPhone').value=u?(u.phone||''):'';
  $('uRole').value=u?u.role_id:'2'; $('uUnit').value=u?(u.unit||''):'';
}
</script>
<?php layout_end();
