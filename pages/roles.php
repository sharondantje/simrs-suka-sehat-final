<?php
/* ROLE & HAK AKSES — matriks role_permissions (RBAC) */
$self = 'portal.php?page=' . $PAGE['key'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $pdo = db();
    $pdo->beginTransaction();
    $pdo->exec('DELETE FROM role_permissions');
    $ins = $pdo->prepare('INSERT INTO role_permissions (role_id, permission_id) VALUES (?,?)');
    foreach ($_POST['perm'] ?? [] as $roleId => $perms) foreach ($perms as $permId => $_) $ins->execute([(int)$roleId, (int)$permId]);
    $pdo->commit();
    audit('UPDATE', 'Role & Hak Akses', 'Memperbarui matriks hak akses');
    flash('success', 'Hak akses berhasil disimpan.');
    redirect($self);
}

$roles = q_all('SELECT r.*, (SELECT COUNT(*) FROM users u WHERE u.role_id=r.id) n_users FROM roles r ORDER BY r.id');
$perms = q_all('SELECT * FROM permissions ORDER BY module, id');
$has   = [];
foreach (q_all('SELECT * FROM role_permissions') as $rp) $has[$rp['role_id']][$rp['permission_id']] = true;

layout_start($PAGE['title'], $PAGE['key'], $PAGE['section']);
?>
<div class="content">
  <div class="page-head">
    <div><h2>Role & Hak Akses</h2><p>Atur permission setiap role. Menu di sidebar mengikuti role pengguna.</p></div>
  </div>
  <?php flashes(); ?>

  <section class="grid grid-3 mb">
    <?php foreach ($roles as $r): ?>
      <div class="card card-body" style="display:flex;gap:12px;align-items:flex-start">
        <div class="kpi-icon"><?= icon('shield', 20) ?></div>
        <div style="min-width:0"><b style="color:var(--heading)"><?= e($r['name']) ?></b>
          <div class="muted" style="font-size:12px"><?= e($r['description']) ?></div>
          <div style="margin-top:6px;display:flex;gap:6px;flex-wrap:wrap"><span class="badge info"><?= $r['n_users'] ?> pengguna</span><span class="badge"><?= count($has[$r['id']] ?? []) ?> permission</span></div>
        </div>
      </div>
    <?php endforeach; ?>
  </section>

  <form method="post" class="card">
    <?= csrf_field() ?>
    <div class="card-head"><div><h3>Matriks Permission</h3><p>Centang permission yang dimiliki setiap role</p></div><button class="btn btn-primary"><?= icon('check', 16) ?> Simpan Hak Akses</button></div>
    <div class="table-wrap">
      <table class="table perm-table">
        <thead><tr><th>Permission</th><?php foreach ($roles as $r): ?><th title="<?= e($r['name']) ?>"><?= e(mb_strimwidth($r['name'], 0, 12, '…')) ?></th><?php endforeach; ?></tr></thead>
        <tbody>
        <?php $mod = null; foreach ($perms as $p): ?>
          <?php if ($p['module'] !== $mod): $mod = $p['module']; ?><tr><td colspan="<?= count($roles) + 1 ?>" style="background:var(--soft-2);font-weight:700;color:var(--primary)"><?= e($mod) ?></td></tr><?php endif; ?>
          <tr><td><span class="strong"><?= e($p['name']) ?></span><small class="mono"><?= e($p['code']) ?></small></td>
            <?php foreach ($roles as $r): ?><td><input type="checkbox" name="perm[<?= $r['id'] ?>][<?= $p['id'] ?>]" <?= isset($has[$r['id']][$p['id']]) ? 'checked' : '' ?> style="accent-color:var(--primary);width:16px;height:16px"></td><?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </form>
</div>
<?php layout_end();
