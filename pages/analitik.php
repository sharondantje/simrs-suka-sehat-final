<?php
/*
 * MODUL 15 — LAPORAN KHUSUS / REKAP DATA (Analis Data)
 * Parameter Laporan → Pemrosesan Data → Hasil & Unduh (CSV). Data pribadi (NIK, no. HP) selalu dimasking.
 */
const AN_REPORTS = [
    'kunjungan'   => 'Rekap Kunjungan Pasien',
    'pasien'      => 'Data Pasien (dimasking)',
    'diagnosis'   => 'Distribusi Diagnosis ICD-10',
    'pendapatan'  => 'Pendapatan per Kategori & Penjamin',
    'farmasi'     => 'Pemakaian Obat',
    'lab'         => 'Pemeriksaan Laboratorium',
    'inventory'   => 'Pergerakan Stok Gudang',
];
$rep  = $_GET['r'] ?? '';
$from = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['from'] ?? '') ? $_GET['from'] : date('Y-m-01');
$to   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['to'] ?? '') ? $_GET['to'] : date('Y-m-d');
$type = in_array($_GET['type'] ?? '', ['rawat_jalan', 'rawat_inap', 'igd'], true) ? $_GET['type'] : '';
$P = [$from, $to];
$cols = []; $rows = [];

if (isset(AN_REPORTS[$rep])) {
    $tw = $type ? ' AND v.visit_type=?' : ''; $tp = $type ? [$type] : [];
    switch ($rep) {
        case 'kunjungan':
            $cols = ['Tanggal', 'Jenis Layanan', 'Poli / Unit', 'Penjamin', 'Jumlah', 'Selesai', 'Batal'];
            $rows = q_all("SELECT DATE(v.visit_date), v.visit_type, COALESCE(p.name,'-'), COALESCE(i.name,'-'), COUNT(*), SUM(v.status='selesai'), SUM(v.status='batal')
                           FROM visits v LEFT JOIN poli p ON p.id=v.poli_id LEFT JOIN insurers i ON i.id=v.insurer_id WHERE DATE(v.visit_date) BETWEEN ? AND ?$tw
                           GROUP BY DATE(v.visit_date), v.visit_type, p.name, i.name ORDER BY 1 DESC", array_merge($P, $tp), );
            break;
        case 'pasien':
            $cols = ['No. RM', 'NIK', 'Jenis Kelamin', 'Usia', 'Gol. Darah', 'No. HP', 'Penjamin', 'Terdaftar'];
            foreach (q_all('SELECT p.mrn, p.nik, p.gender, p.birth_date, p.blood_type, p.phone, i.name ins, p.created_at FROM patients p LEFT JOIN insurers i ON i.id=p.insurer_id WHERE DATE(p.created_at) <= ? ORDER BY p.mrn', [$to]) as $r)
                $rows[] = [$r['mrn'], ui_mask($r['nik']), $r['gender'], ui_age($r['birth_date']), $r['blood_type'] ?? '-', ui_mask($r['phone'], 3), $r['ins'] ?? '-', substr($r['created_at'], 0, 10)];
            break;
        case 'diagnosis':
            $cols = ['Kode ICD-10', 'Diagnosis', 'Kategori', 'Primer', 'Sekunder', 'Total'];
            $rows = q_all("SELECT d.icd10_code, i.description, i.category, SUM(d.type='primer'), SUM(d.type='sekunder'), COUNT(*) FROM diagnoses d JOIN icd10 i ON i.code=d.icd10_code
                           JOIN medical_records m ON m.id=d.record_id JOIN visits v ON v.id=m.visit_id WHERE DATE(m.created_at) BETWEEN ? AND ?$tw GROUP BY d.icd10_code, i.description, i.category ORDER BY 6 DESC", array_merge($P, $tp));
            break;
        case 'pendapatan':
            $cols = ['Penjamin', 'Kategori', 'Jumlah Item', 'Total (Rp)'];
            $rows = q_all("SELECT COALESCE(s.name,'-'), it.category, COUNT(*), SUM(it.subtotal) FROM invoice_items it JOIN invoices iv ON iv.id=it.invoice_id LEFT JOIN insurers s ON s.id=iv.insurer_id
                           JOIN visits v ON v.id=iv.visit_id WHERE DATE(iv.created_at) BETWEEN ? AND ?$tw GROUP BY s.name, it.category ORDER BY 1, 4 DESC", array_merge($P, $tp));
            break;
        case 'farmasi':
            $cols = ['Kode', 'Obat', 'Kategori', 'Jumlah Keluar', 'Retur', 'Nilai (Rp)'];
            $rows = q_all("SELECT d.code, d.name, d.category, SUM(CASE WHEN m.movement_type='keluar' THEN -m.qty ELSE 0 END), SUM(CASE WHEN m.movement_type='retur' THEN m.qty ELSE 0 END),
                           SUM(CASE WHEN m.movement_type='keluar' THEN -m.qty ELSE 0 END)*d.price FROM drug_stock_movements m JOIN drugs d ON d.id=m.drug_id WHERE DATE(m.created_at) BETWEEN ? AND ? GROUP BY d.id ORDER BY 4 DESC", $P);
            break;
        case 'lab':
            $cols = ['Pemeriksaan', 'Kategori', 'Jumlah', 'Tervalidasi', 'Abnormal', 'Kritis'];
            $rows = q_all("SELECT t.name, t.category, COUNT(*), SUM(r.status='tervalidasi'), SUM(r.flag IN ('tinggi','rendah')), SUM(r.flag='kritis') FROM lab_results r JOIN lab_tests t ON t.id=r.lab_test_id
                           JOIN orders o ON o.id=r.order_id WHERE DATE(o.ordered_at) BETWEEN ? AND ? AND o.status<>'dibatalkan' GROUP BY t.id ORDER BY 3 DESC", $P);
            break;
        case 'inventory':
            $cols = ['Tanggal', 'Barang', 'Jenis', 'Qty', 'Dari', 'Ke', 'Referensi'];
            $rows = q_all("SELECT DATE(m.created_at), i.name, m.movement_type, m.qty, COALESCE(m.from_location,'-'), COALESCE(m.to_location,'-'), COALESCE(m.reference,'-') FROM stock_movements m JOIN inventory_items i ON i.id=m.item_id
                           WHERE DATE(m.created_at) BETWEEN ? AND ? ORDER BY m.created_at DESC", $P);
            break;
    }
    $rows = array_map('array_values', $rows);
    if (isset($_GET['export'])) {
        audit('EXPORT', 'Reporting & Dashboard', 'Ekspor ' . AN_REPORTS[$rep] . " $from s/d $to");
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $rep . '_' . $from . '_' . $to . '.csv"');
        $o = fopen('php://output', 'w');
        fputcsv($o, [AN_REPORTS[$rep], "$from s/d $to", $type ? ui_label($type) : 'Semua layanan']);
        fputcsv($o, $cols);
        foreach ($rows as $r) fputcsv($o, $r);
        exit;
    }
    audit('VIEW', 'Reporting & Dashboard', 'Laporan khusus ' . AN_REPORTS[$rep] . " $from s/d $to");
}

layout_start($PAGE['title'], $PAGE['key'], $PAGE['section']);
?>
<div class="content">
  <div class="page-head"><div><h2>Laporan Khusus / Rekap</h2><p>Susun laporan berdasarkan parameter, tinjau, lalu unduh CSV. Data pribadi pasien dimasking.</p></div></div>
  <div class="card stepper-card no-print"><?= ui_stepper(['Parameter Laporan', 'Pemrosesan Data', 'Hasil & Unduh'], $rep ? 3 : 0) ?></div>
  <form class="card mb no-print" method="get"><input type="hidden" name="page" value="<?= e($PAGE['key']) ?>">
    <div class="card-head"><div><h3>🔍 Parameter Laporan</h3></div></div>
    <div class="card-body"><div class="form-grid" style="grid-template-columns:repeat(4,minmax(0,1fr))">
      <div class="field"><label>Jenis Laporan</label><select name="r" required><option value="">— pilih —</option><?= ui_opt_map(AN_REPORTS, $rep) ?></select></div>
      <div class="field"><label>Kategori Layanan</label><select name="type"><option value="">Semua layanan</option><?= ui_opt_map(['rawat_jalan' => 'Rawat Jalan', 'rawat_inap' => 'Rawat Inap', 'igd' => 'IGD'], $type) ?></select></div>
      <div class="field"><label>Dari Tanggal</label><input type="date" name="from" value="<?= e($from) ?>" required></div>
      <div class="field"><label>Sampai Tanggal</label><input type="date" name="to" value="<?= e($to) ?>" required></div></div>
      <button class="btn btn-primary"><?= icon('chart', 16) ?> Generate Report</button></div></form>

  <?php if ($rep && isset(AN_REPORTS[$rep])): ?>
  <div class="card"><div class="card-head"><div><h3><?= e(AN_REPORTS[$rep]) ?></h3><p><?= tgl($from) ?> – <?= tgl($to) ?> · <?= count($rows) ?> baris<?= $type ? ' · ' . e(ui_label($type)) : '' ?></p></div>
      <div class="actions no-print"><a class="btn btn-sm" href="<?= e(ui_url($PAGE['key'], ['r' => $rep, 'from' => $from, 'to' => $to, 'type' => $type, 'export' => 1])) ?>"><?= icon('download', 14) ?> CSV</a><button class="btn btn-sm" onclick="print()"><?= icon('printer', 14) ?> PDF / Cetak</button></div></div>
    <div class="table-wrap"><table class="table"><thead><tr><?php foreach ($cols as $c): ?><th><?= e($c) ?></th><?php endforeach; ?></tr></thead><tbody>
      <?php if (!$rows): ?><tr><td colspan="<?= count($cols) ?>" class="empty">Tidak ada data untuk parameter ini.</td></tr><?php endif; foreach ($rows as $r): ?><tr><?php foreach ($r as $i => $v): ?>
        <td class="<?= is_numeric($v) || $i === 0 ? 'mono' : '' ?>"><?= is_numeric($v) && str_contains($cols[$i], 'Rp') ? rupiah($v) : e(is_string($v) && in_array($v, ['rawat_jalan', 'rawat_inap', 'igd', 'masuk', 'keluar', 'mutasi', 'penyesuaian'], true) ? ui_label($v) : $v) ?></td><?php endforeach; ?></tr><?php endforeach; ?></tbody></table></div>
    <div class="card-body"><small class="muted">🔐 Proteksi data: NIK dan nomor telepon ditampilkan termasking; ekspor tercatat di audit log.</small></div></div>
  <?php endif; ?>
</div>
<?php layout_end();
