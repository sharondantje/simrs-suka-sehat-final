<?php
/* LAPORAN & STATISTIK — Modul 15 Reporting & Dashboard (BOR, ALOS, TOI, BTO, kunjungan, pendapatan, penyakit) */
$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to']   ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = date('Y-m-01');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to))   $to   = date('Y-m-d');
if ($from > $to) [$from, $to] = [$to, $from];
$P  = [$from, $to];
$days = (int)((strtotime($to) - strtotime($from)) / 86400) + 1;
$tt   = (int)q_val('SELECT COUNT(*) FROM beds') ?: (int)setting('total_beds', 40);

$hariRawat = (float)q_val("SELECT COALESCE(SUM(GREATEST(0, DATEDIFF(LEAST(DATE(COALESCE(discharge_date, NOW())), ?), GREATEST(DATE(admit_date), ?)) + 1)),0)
                           FROM admissions WHERE DATE(admit_date) <= ? AND DATE(COALESCE(discharge_date, NOW())) >= ?", [$to, $from, $to, $from]);
$keluar  = (int)q_val('SELECT COUNT(*) FROM admissions WHERE DATE(discharge_date) BETWEEN ? AND ?', $P);
$losKel  = (float)q_val('SELECT COALESCE(SUM(DATEDIFF(discharge_date, admit_date)),0) FROM admissions WHERE DATE(discharge_date) BETWEEN ? AND ?', $P);
$bor  = $tt && $days ? $hariRawat / ($tt * $days) * 100 : 0;
$alos = $keluar ? $losKel / $keluar : (float)q_val('SELECT COALESCE(AVG(DATEDIFF(COALESCE(discharge_date,NOW()), admit_date)),0) FROM admissions');
$toi  = $keluar ? (($tt * $days) - $hariRawat) / $keluar : 0;
$bto  = $tt ? $keluar / $tt : 0;

$visitTotal = (int)q_val('SELECT COUNT(*) FROM visits WHERE DATE(visit_date) BETWEEN ? AND ?', $P);
$revenue    = (float)q_val('SELECT COALESCE(SUM(total),0) FROM invoices WHERE DATE(created_at) BETWEEN ? AND ?', $P);
$received   = (float)q_val('SELECT COALESCE(SUM(amount),0) FROM payments WHERE DATE(paid_at) BETWEEN ? AND ?', $P);
$newPat     = (int)q_val('SELECT COUNT(*) FROM patients WHERE DATE(created_at) BETWEEN ? AND ?', $P);

$byType  = q_all("SELECT visit_type, COUNT(*) n FROM visits WHERE DATE(visit_date) BETWEEN ? AND ? GROUP BY visit_type", $P);
$byPoli  = q_all("SELECT COALESCE(p.name,'-') name, COUNT(*) n FROM visits v LEFT JOIN poli p ON p.id=v.poli_id WHERE DATE(v.visit_date) BETWEEN ? AND ? GROUP BY p.name ORDER BY n DESC", $P);
$byIns   = q_all("SELECT COALESCE(i.name,'-') name, COUNT(*) n FROM visits v LEFT JOIN insurers i ON i.id=v.insurer_id WHERE DATE(v.visit_date) BETWEEN ? AND ? GROUP BY i.name ORDER BY n DESC", $P);
$byCat   = q_all("SELECT it.category, SUM(it.subtotal) total FROM invoice_items it JOIN invoices iv ON iv.id=it.invoice_id WHERE DATE(iv.created_at) BETWEEN ? AND ? GROUP BY it.category ORDER BY total DESC", $P);
$disease = q_all("SELECT i.code, i.description, COUNT(*) n FROM diagnoses d JOIN icd10 i ON i.code=d.icd10_code JOIN medical_records m ON m.id=d.record_id
                  WHERE DATE(m.created_at) BETWEEN ? AND ? GROUP BY i.code, i.description ORDER BY n DESC LIMIT 10", $P);
if (!$disease) $disease = q_all('SELECT i.code, i.description, COUNT(*) n FROM diagnoses d JOIN icd10 i ON i.code=d.icd10_code GROUP BY i.code, i.description ORDER BY n DESC LIMIT 10');

if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="laporan_simrs_' . $from . '_' . $to . '.csv"');
    $o = fopen('php://output', 'w');
    fputcsv($o, ['Laporan SIMRS RS Suka Sehat', "$from s/d $to"]);
    foreach ([['Kunjungan', $visitTotal], ['Pasien baru', $newPat], ['Pendapatan (tagihan)', $revenue], ['Penerimaan kas', $received],
              ['BOR (%)', round($bor, 2)], ['ALOS (hari)', round($alos, 2)], ['TOI (hari)', round($toi, 2)], ['BTO (kali)', round($bto, 2)]] as $r) fputcsv($o, $r);
    fputcsv($o, []); fputcsv($o, ['Kunjungan per poli']); foreach ($byPoli as $r) fputcsv($o, [$r['name'], $r['n']]);
    fputcsv($o, []); fputcsv($o, ['10 besar penyakit']); foreach ($disease as $r) fputcsv($o, [$r['code'], $r['description'], $r['n']]);
    exit;
}
audit('VIEW', 'Reporting & Dashboard', "Lihat laporan $from s/d $to");

$typeLabel = ['rawat_jalan' => 'Rawat Jalan', 'rawat_inap' => 'Rawat Inap', 'igd' => 'IGD'];
$maxPoli = max(1, ...array_map(fn($r) => (int)$r['n'], $byPoli ?: [['n' => 1]]));
$maxCat  = max(1, ...array_map(fn($r) => (float)$r['total'], $byCat ?: [['total' => 1]]));

layout_start($PAGE['title'], $PAGE['key'], $PAGE['section']);
?>
<style>@media print{.sidebar,.topbar,.footer,.no-print{display:none!important}.main{margin:0}.content{padding:0}}</style>
<div class="content">
  <div class="page-head">
    <div><h2>Laporan & Statistik</h2><p>Indikator pelayanan rumah sakit periode <b><?= tgl($from) ?></b> s/d <b><?= tgl($to) ?></b> (<?= $days ?> hari).</p></div>
    <form class="page-actions no-print" method="get">
      <input type="hidden" name="page" value="<?= e($PAGE['key']) ?>">
      <input class="input" type="date" name="from" value="<?= e($from) ?>" style="width:auto">
      <input class="input" type="date" name="to" value="<?= e($to) ?>" style="width:auto">
      <button class="btn btn-primary">Tampilkan</button>
      <a class="btn" href="portal.php?<?= e(http_build_query(['page' => $PAGE['key'], 'from' => $from, 'to' => $to, 'export' => 1])) ?>"><?= icon('download', 16) ?> CSV</a>
      <button class="btn" type="button" onclick="print()"><?= icon('printer', 16) ?> Cetak</button>
    </form>
  </div>

  <section class="kpi-grid">
    <div class="card kpi"><div class="kpi-icon"><?= icon('bed', 22) ?></div><div><div class="kpi-label">BOR (Bed Occupancy Rate)</div><div class="kpi-value"><?= angka($bor, 1) ?>%</div><div class="kpi-hint">ideal 60–85% · <?= $tt ?> tempat tidur</div></div></div>
    <div class="card kpi"><div class="kpi-icon info"><?= icon('clock', 22) ?></div><div><div class="kpi-label">ALOS / LOS</div><div class="kpi-value"><?= angka($alos, 1) ?> hari</div><div class="kpi-hint">ideal 6–9 hari</div></div></div>
    <div class="card kpi"><div class="kpi-icon warning"><?= icon('refresh', 22) ?></div><div><div class="kpi-label">TOI</div><div class="kpi-value"><?= angka($toi, 1) ?> hari</div><div class="kpi-hint">ideal 1–3 hari</div></div></div>
    <div class="card kpi"><div class="kpi-icon purple"><?= icon('trend', 22) ?></div><div><div class="kpi-label">BTO</div><div class="kpi-value"><?= angka($bto, 2) ?> kali</div><div class="kpi-hint"><?= $keluar ?> pasien keluar</div></div></div>
    <div class="card kpi"><div class="kpi-icon"><?= icon('users', 22) ?></div><div><div class="kpi-label">Total Kunjungan</div><div class="kpi-value"><?= angka($visitTotal) ?></div><div class="kpi-hint"><?= $newPat ?> pasien baru</div></div></div>
    <div class="card kpi"><div class="kpi-icon info"><?= icon('receipt', 22) ?></div><div><div class="kpi-label">Pendapatan (Tagihan)</div><div class="kpi-value" style="font-size:20px"><?= rupiah($revenue) ?></div><div class="kpi-hint">total invoice</div></div></div>
    <div class="card kpi"><div class="kpi-icon warning"><?= icon('money', 22) ?></div><div><div class="kpi-label">Penerimaan Kas</div><div class="kpi-value" style="font-size:20px"><?= rupiah($received) ?></div><div class="kpi-hint">pembayaran pasien</div></div></div>
    <div class="card kpi"><div class="kpi-icon purple"><?= icon('hospital', 22) ?></div><div><div class="kpi-label">Hari Perawatan</div><div class="kpi-value"><?= angka($hariRawat) ?></div><div class="kpi-hint">total hari rawat inap</div></div></div>
  </section>

  <section class="grid grid-2e mb">
    <div class="card">
      <div class="card-head"><div><h3>Kunjungan per Poli / Unit</h3><p>Distribusi kunjungan pasien</p></div></div>
      <div class="card-body">
        <?php if (!$byPoli): ?><p class="empty">Tidak ada kunjungan pada periode ini.</p><?php endif; ?>
        <?php foreach ($byPoli as $r): ?>
          <div class="stat-row"><span><?= e($r['name']) ?></span><b><?= $r['n'] ?></b></div>
          <div class="progress"><div style="width:<?= round($r['n'] / $maxPoli * 100) ?>%"></div></div>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="card">
      <div class="card-head"><div><h3>Pendapatan per Kategori</h3><p>Dari rincian invoice</p></div></div>
      <div class="card-body">
        <?php if (!$byCat): ?><p class="empty">Tidak ada transaksi pada periode ini.</p><?php endif; ?>
        <?php foreach ($byCat as $r): ?>
          <div class="stat-row"><span><?= e(ucfirst($r['category'])) ?></span><b><?= rupiah($r['total']) ?></b></div>
          <div class="progress"><div style="width:<?= round($r['total'] / $maxCat * 100) ?>%;background:var(--accent)"></div></div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="grid grid-2">
    <div class="card">
      <div class="card-head"><div><h3>10 Besar Penyakit</h3><p>Berdasarkan diagnosis ICD-10</p></div></div>
      <div class="table-wrap"><table class="table">
        <thead><tr><th>#</th><th>Kode ICD-10</th><th>Diagnosis</th><th>Jumlah Kasus</th></tr></thead>
        <tbody>
          <?php foreach ($disease as $i => $r): ?><tr><td><?= $i + 1 ?></td><td class="strong mono"><?= e($r['code']) ?></td><td><?= e($r['description']) ?></td><td><span class="badge info"><?= $r['n'] ?> kasus</span></td></tr><?php endforeach; ?>
        </tbody>
      </table></div>
    </div>
    <div class="card">
      <div class="card-head"><div><h3>Jenis Layanan & Penjamin</h3><p>Komposisi kunjungan</p></div></div>
      <ul class="list">
        <?php foreach ($byType as $r): ?><li><span class="l-dot info"></span><div class="l-main"><b><?= e($typeLabel[$r['visit_type']] ?? $r['visit_type']) ?></b><span>Jenis layanan</span></div><time><?= $r['n'] ?> kunjungan</time></li><?php endforeach; ?>
        <?php foreach ($byIns as $r): ?><li><span class="l-dot"></span><div class="l-main"><b><?= e($r['name']) ?></b><span>Penjamin</span></div><time><?= $r['n'] ?> kunjungan</time></li><?php endforeach; ?>
      </ul>
    </div>
  </section>
</div>
<?php layout_end();
