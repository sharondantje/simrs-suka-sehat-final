<?php
/*
 * MODUL 15 — EXECUTIVE DASHBOARD (database)
 * Indikator manajerial: BOR, ALOS, TOI, BTO, kunjungan, pendapatan, klaim, tren harian, unit & penyakit terbanyak.
 */
$from = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['from'] ?? '') ? $_GET['from'] : date('Y-m-d', strtotime('-13 days'));
$to   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['to'] ?? '') ? $_GET['to'] : date('Y-m-d');
if ($from > $to) [$from, $to] = [$to, $from];
if ((strtotime($to) - strtotime($from)) / 86400 > 92) $from = date('Y-m-d', strtotime("$to -92 days"));
$type = in_array($_GET['type'] ?? '', ['rawat_jalan', 'rawat_inap', 'igd'], true) ? $_GET['type'] : '';
$P = [$from, $to];
$days = (int)((strtotime($to) - strtotime($from)) / 86400) + 1;

$beds = (int)q_val('SELECT COUNT(*) FROM beds') ?: 1;
$hari = (float)q_val('SELECT COALESCE(SUM(GREATEST(0, DATEDIFF(LEAST(DATE(COALESCE(discharge_date, NOW())), ?), GREATEST(DATE(admit_date), ?)) + 1)),0) FROM admissions WHERE DATE(admit_date) <= ? AND DATE(COALESCE(discharge_date, NOW())) >= ?', [$to, $from, $to, $from]);
$out  = (int)q_val('SELECT COUNT(*) FROM admissions WHERE DATE(discharge_date) BETWEEN ? AND ?', $P);
$los  = (float)q_val('SELECT COALESCE(SUM(DATEDIFF(discharge_date, admit_date)),0) FROM admissions WHERE DATE(discharge_date) BETWEEN ? AND ?', $P);
$bor  = $hari / ($beds * $days) * 100;
$alos = $out ? $los / $out : (float)q_val('SELECT COALESCE(AVG(DATEDIFF(COALESCE(discharge_date,NOW()), admit_date)),0) FROM admissions');
$toi  = $out ? (($beds * $days) - $hari) / $out : 0;
$bto  = $out / $beds;
$occNow = (int)q_val("SELECT COUNT(*) FROM beds WHERE status='terisi'");

$tw = $type ? ' AND visit_type=?' : '';
$tp = $type ? [$type] : [];
$visits  = (int)q_val("SELECT COUNT(*) FROM visits WHERE DATE(visit_date) BETWEEN ? AND ? AND status<>'batal'$tw", array_merge($P, $tp));
$revenue = (float)q_val('SELECT COALESCE(SUM(i.total),0) FROM invoices i JOIN visits v ON v.id=i.visit_id WHERE DATE(i.created_at) BETWEEN ? AND ?' . ($type ? ' AND v.visit_type=?' : ''), array_merge($P, $tp));
$cash    = (float)q_val('SELECT COALESCE(SUM(amount),0) FROM payments WHERE DATE(paid_at) BETWEEN ? AND ?', $P);
$claimOpen = q_one("SELECT COUNT(*) n, COALESCE(SUM(amount),0) t FROM insurance_claims WHERE status IN ('diajukan','diverifikasi','disetujui')");

$visitTrend = ui_daily("SELECT DATE(visit_date) d, COUNT(*) v FROM visits WHERE status<>'batal'" . ($type ? ' AND visit_type=?' : '') . ' AND DATE(visit_date) BETWEEN ? AND ? GROUP BY DATE(visit_date)', $from, $to, $tp);
$revTrend   = ui_daily('SELECT DATE(created_at) d, SUM(total) v FROM invoices WHERE DATE(created_at) BETWEEN ? AND ? GROUP BY DATE(created_at)', $from, $to);
$byType = q_all("SELECT visit_type, COUNT(*) n FROM visits WHERE DATE(visit_date) BETWEEN ? AND ? AND status<>'batal' GROUP BY visit_type", $P);
$byPoli = q_all("SELECT COALESCE(p.name,'-') name, COUNT(*) n FROM visits v LEFT JOIN poli p ON p.id=v.poli_id WHERE DATE(v.visit_date) BETWEEN ? AND ? AND v.status<>'batal'$tw GROUP BY p.name ORDER BY n DESC LIMIT 8", array_merge($P, $tp));
$disease = q_all('SELECT d.icd10_code code, i.description, COUNT(*) n FROM diagnoses d JOIN icd10 i ON i.code=d.icd10_code GROUP BY d.icd10_code, i.description ORDER BY n DESC LIMIT 8');
$wards = q_all("SELECT r.ward, COUNT(*) total, SUM(b.status='terisi') used FROM beds b JOIN rooms r ON r.id=b.room_id GROUP BY r.ward ORDER BY r.ward");
$maxPoli = max(1, ...array_map(fn($r) => (int)$r['n'], $byPoli ?: [['n' => 1]]));
audit('VIEW', 'Reporting & Dashboard', "Executive dashboard $from s/d $to");
$short = fn($v) => $v >= 1e6 ? angka($v / 1e6, 1) . 'jt' : ($v >= 1e3 ? angka($v / 1e3) . 'rb' : angka($v));

layout_start($PAGE['title'], $PAGE['key'], $PAGE['section']);
?>
<div class="content">
  <div class="page-head"><div><h2>Executive Dashboard</h2><p>Indikator kinerja rumah sakit <b><?= tgl($from) ?></b> – <b><?= tgl($to) ?></b> (<?= $days ?> hari).</p></div>
    <form class="page-actions" method="get"><input type="hidden" name="page" value="<?= e($PAGE['key']) ?>">
      <select class="input" name="type" style="width:auto"><option value="">Semua layanan</option><?= ui_opt_map(['rawat_jalan' => 'Rawat Jalan', 'rawat_inap' => 'Rawat Inap', 'igd' => 'IGD'], $type) ?></select>
      <input class="input" type="date" name="from" value="<?= e($from) ?>" style="width:auto"><input class="input" type="date" name="to" value="<?= e($to) ?>" style="width:auto">
      <button class="btn btn-primary">Terapkan Filter</button><button class="btn" type="button" onclick="print()"><?= icon('printer', 16) ?></button></form></div>

  <section class="kpi-grid">
    <?= ui_kpi('BOR', angka($bor, 1) . '%', 'bed', $bor > 85 ? 'danger' : ($bor < 60 ? 'warning' : ''), 'ideal 60–85% · terisi kini ' . $occNow . '/' . $beds) ?>
    <?= ui_kpi('ALOS', angka($alos, 1) . ' hari', 'clock', 'info', 'ideal 6–9 hari') ?>
    <?= ui_kpi('TOI', angka($toi, 1) . ' hari', 'refresh', 'warning', 'ideal 1–3 hari') ?>
    <?= ui_kpi('BTO', angka($bto, 2) . ' kali', 'trend', 'purple', $out . ' pasien keluar') ?>
    <?= ui_kpi('Kunjungan', angka($visits), 'users', '', $type ? ui_label($type) : 'semua layanan') ?>
    <?= ui_kpi('Pendapatan (Tagihan)', '<span style="font-size:20px">' . rupiah($revenue) . '</span>', 'receipt', 'info') ?>
    <?= ui_kpi('Kas Diterima', '<span style="font-size:20px">' . rupiah($cash) . '</span>', 'money', 'warning') ?>
    <?= ui_kpi('Klaim Berjalan', (int)$claimOpen['n'], 'shield', 'purple', rupiah($claimOpen['t'])) ?>
  </section>

  <section class="grid grid-2e mb">
    <div class="card"><div class="card-head"><div><h3>Tren Kunjungan</h3><p>Per hari</p></div></div><?= ui_bars($visitTrend) ?></div>
    <div class="card"><div class="card-head"><div><h3>💰 Grafik Pendapatan</h3><p>Total tagihan per hari</p></div></div><?= ui_bars($revTrend, $short) ?></div>
  </section>
  <section class="grid grid-3 mb">
    <div class="card"><div class="card-head"><h3>Kunjungan per Poli / Unit</h3></div><div class="card-body"><?php if (!$byPoli): ?><div class="empty">Belum ada data.</div><?php endif; foreach ($byPoli as $r): ?>
      <div class="stat-row"><span><?= e($r['name']) ?></span><b><?= (int)$r['n'] ?></b></div><div class="progress"><div style="width:<?= round($r['n'] / $maxPoli * 100) ?>%"></div></div><?php endforeach; ?></div></div>
    <div class="card"><div class="card-head"><h3>📈 Okupansi per Bangsal</h3></div><div class="card-body"><?php foreach ($wards as $w): $pct = $w['total'] ? $w['used'] / $w['total'] * 100 : 0; ?>
      <div class="stat-row"><span><?= e($w['ward']) ?></span><b><?= (int)$w['used'] ?>/<?= (int)$w['total'] ?> · <?= angka($pct) ?>%</b></div><div class="progress"><div style="width:<?= round($pct) ?>%;<?= $pct > 85 ? 'background:var(--danger)' : '' ?>"></div></div><?php endforeach; ?></div></div>
    <div class="card"><div class="card-head"><h3>Penyakit Terbanyak</h3></div><ul class="list"><?php foreach ($disease as $d): ?><li><span class="l-dot info"></span><div class="l-main"><b><span class="mono"><?= e($d['code']) ?></span> <?= e($d['description']) ?></b></div><time class="mono"><?= (int)$d['n'] ?></time></li><?php endforeach; ?></ul></div>
  </section>
  <div class="card"><div class="card-head"><h3>Komposisi Layanan</h3></div><div class="card-body" style="display:flex;gap:24px;flex-wrap:wrap">
    <?php foreach ($byType as $t): ?><div><div class="kpi-label"><?= e(ui_label($t['visit_type'])) ?></div><div class="kpi-value"><?= (int)$t['n'] ?></div></div><?php endforeach; ?></div></div>
</div>
<?php layout_end();
