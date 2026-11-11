<?php
/* LOG AKTIVITAS — audit trail seluruh modul (tabel audit_logs) */
$search = trim($_GET['q'] ?? '');
$fAct   = $_GET['action'] ?? '';
$fDate  = $_GET['date'] ?? '';
$pg     = max(1, (int)($_GET['p'] ?? 1));
$per    = 15;

$where = ['1=1']; $p = [];
if ($search !== '') { $where[] = '(username LIKE ? OR module LIKE ? OR description LIKE ?)'; array_push($p, "%$search%", "%$search%", "%$search%"); }
if ($fAct !== '')   { $where[] = 'action = ?'; $p[] = $fAct; }
if ($fDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fDate)) { $where[] = 'DATE(created_at) = ?'; $p[] = $fDate; }
$w = implode(' AND ', $where);

$total = (int)q_val("SELECT COUNT(*) FROM audit_logs WHERE $w", $p);
$pages = max(1, (int)ceil($total / $per));
$pg    = min($pg, $pages);
$rows  = q_all("SELECT * FROM audit_logs WHERE $w ORDER BY created_at DESC, id DESC LIMIT $per OFFSET " . (($pg - 1) * $per), $p);
$acts  = q_all('SELECT action, COUNT(*) n FROM audit_logs GROUP BY action ORDER BY n DESC');

if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="log_aktivitas_' . date('Ymd_His') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Waktu', 'Username', 'Aksi', 'Modul', 'Keterangan', 'IP']);
    foreach (q_all("SELECT * FROM audit_logs WHERE $w ORDER BY created_at DESC", $p) as $r) fputcsv($out, [$r['created_at'], $r['username'], $r['action'], $r['module'], $r['description'], $r['ip_address']]);
    exit;
}

$qs = fn($extra) => 'portal.php?' . http_build_query(array_merge(['page' => $PAGE['key'], 'q' => $search, 'action' => $fAct, 'date' => $fDate], $extra));
$tone = ['LOGIN' => 'success', 'LOGOUT' => 'neutral', 'LOGIN_GAGAL' => 'danger', 'DELETE' => 'danger', 'CREATE' => 'info', 'UPDATE' => 'warning', 'RESET_PASSWORD' => 'purple'];

layout_start($PAGE['title'], $PAGE['key'], $PAGE['section']);
?>
<div class="content">
  <div class="page-head">
    <div><h2>Log Aktivitas</h2><p>Audit trail login, perubahan data, dan aktivitas pengguna di seluruh modul.</p></div>
    <div class="page-actions"><a class="btn" href="<?= e($qs(['export' => 1])) ?>"><?= icon('download', 16) ?> Ekspor CSV</a></div>
  </div>

  <section class="kpi-grid">
    <?php foreach (array_slice($acts, 0, 4) as $a): ?>
      <div class="card kpi"><div class="kpi-icon <?= e(['success' => '', 'danger' => 'danger', 'info' => 'info', 'warning' => 'warning', 'purple' => 'purple', 'neutral' => ''][$tone[$a['action']] ?? 'neutral']) ?>"><?= icon('activity', 22) ?></div>
        <div><div class="kpi-label"><?= e($a['action']) ?></div><div class="kpi-value"><?= $a['n'] ?></div><div class="kpi-hint">total tercatat</div></div></div>
    <?php endforeach; ?>
  </section>

  <div class="card">
    <form class="toolbar" method="get">
      <input type="hidden" name="page" value="<?= e($PAGE['key']) ?>">
      <input class="input grow" name="q" value="<?= e($search) ?>" placeholder="Cari pengguna, modul, atau keterangan...">
      <select class="input" name="action"><option value="">Semua Aksi</option><?php foreach ($acts as $a): ?><option <?= $fAct === $a['action'] ? 'selected' : '' ?>><?= e($a['action']) ?></option><?php endforeach; ?></select>
      <input class="input" type="date" name="date" value="<?= e($fDate) ?>">
      <button class="btn"><?= icon('search', 15) ?> Filter</button>
    </form>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>Waktu</th><th>Pengguna</th><th>Aksi</th><th>Modul</th><th>Keterangan</th><th>IP Address</th></tr></thead>
        <tbody>
        <?php if (!$rows): ?><tr><td colspan="6" class="empty">Tidak ada aktivitas.</td></tr><?php endif; ?>
        <?php foreach ($rows as $r): ?>
          <tr><td class="mono"><?= tgl($r['created_at'], true) ?></td><td class="strong"><?= e($r['username'] ?? '-') ?></td>
            <td><span class="badge <?= e($tone[$r['action']] ?? 'neutral') ?>"><?= e($r['action']) ?></span></td>
            <td><?= e($r['module']) ?></td><td><?= e($r['description']) ?></td><td><?= e($r['ip_address']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="toolbar" style="border-top:1px solid var(--line-soft);border-bottom:0;justify-content:space-between">
      <span class="muted">Menampilkan <?= count($rows) ?> dari <?= $total ?> aktivitas</span>
      <div class="page-actions">
        <a class="btn btn-sm" href="<?= e($qs(['p' => max(1, $pg - 1)])) ?>">‹ Sebelumnya</a>
        <span class="btn btn-sm btn-ghost">Hal <?= $pg ?> / <?= $pages ?></span>
        <a class="btn btn-sm" href="<?= e($qs(['p' => min($pages, $pg + 1)])) ?>">Berikutnya ›</a>
      </div>
    </div>
  </div>
</div>
<?php layout_end();
