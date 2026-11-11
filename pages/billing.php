<?php
/*
 * MODUL 11 — BILLING & ASURANSI (database)
 * billing : Rincian Tagihan → Verifikasi Penjamin → Pembayaran & Klaim → Finalisasi
 *           (tagihan dibuat otomatis dari layanan kunjungan: administrasi, konsultasi, lab, radiologi, obat, tindakan, kamar)
 * rekap   : rekapitulasi biaya & penerimaan per periode
 * riwayat : riwayat transaksi pembayaran
 * invoice : cetak invoice
 */
const BIL_STEPS = ['Rincian Tagihan', 'Verifikasi Penjamin', 'Pembayaran & Klaim', 'Finalisasi'];
const BIL_MOD   = 'Billing';
const BIL_METHOD = ['tunai' => 'Tunai', 'debit' => 'Kartu Debit', 'kredit' => 'Kartu Kredit', 'transfer' => 'Transfer Bank', 'qris' => 'QRIS'];

$key = $PAGE['key'];
$me  = (int)$user['id'];
$id  = (int)($_GET['id'] ?? 0);
$back = ui_url($key, $id ? ['id' => $id] : []);

/** Rincian layanan kunjungan untuk tagihan. */
function bil_items(int $vid): array
{
    $v = q_one('SELECT v.*, d.name doctor FROM visits v LEFT JOIN doctors d ON d.id=v.doctor_id WHERE v.id=?', [$vid]);
    $t = fn($code) => q_one('SELECT name, price FROM tariffs WHERE code=?', [$code]);
    $items = [];
    $adm = $t($v['visit_type'] === 'rawat_inap' ? 'ADM-02' : 'ADM-01'); if ($adm) $items[] = [$adm['name'], 'administrasi', 1, $adm['price']];
    if ($v['visit_type'] === 'igd') { $x = $t('IGD-01'); if ($x) $items[] = [$x['name'], 'tindakan', 1, $x['price']]; }
    elseif ($v['doctor']) { $x = $t(preg_match('/Sp\./', $v['doctor']) ? 'KNS-SP' : 'KNS-UM'); if ($x) $items[] = [$x['name'] . ' — ' . $v['doctor'], 'konsultasi', 1, $x['price']]; }
    foreach (q_all("SELECT t.name, t.price FROM lab_results r JOIN lab_tests t ON t.id=r.lab_test_id JOIN orders o ON o.id=r.order_id WHERE o.visit_id=? AND o.status<>'dibatalkan'", [$vid]) as $r) $items[] = ['Lab: ' . $r['name'], 'laboratorium', 1, $r['price']];
    foreach (q_all("SELECT x.name, x.price FROM radiology_results r JOIN radiology_exams x ON x.id=r.exam_id JOIN orders o ON o.id=r.order_id WHERE o.visit_id=? AND o.status<>'dibatalkan'", [$vid]) as $r) $items[] = ['Radiologi: ' . $r['name'], 'radiologi', 1, $r['price']];
    foreach (q_all("SELECT d.name, pi.qty, d.price FROM prescription_items pi JOIN prescriptions p ON p.id=pi.prescription_id JOIN drugs d ON d.id=pi.drug_id WHERE p.visit_id=? AND p.status<>'ditolak'", [$vid]) as $r) $items[] = [$r['name'], 'obat', (int)$r['qty'], $r['price']];
    foreach (q_all("SELECT t.name, oi.qty, t.price FROM order_items oi JOIN orders o ON o.id=oi.order_id JOIN tariffs t ON t.code=oi.item_code WHERE o.visit_id=? AND o.order_type='tindakan' AND o.status<>'dibatalkan'", [$vid]) as $r) $items[] = [$r['name'], 'tindakan', (int)$r['qty'], $r['price']];
    foreach (q_all('SELECT a.admit_date, a.discharge_date, r.name, r.tariff_per_day FROM admissions a JOIN beds b ON b.id=a.bed_id JOIN rooms r ON r.id=b.room_id WHERE a.visit_id=?', [$vid]) as $r) {
        $days = max(1, (int)ceil((strtotime($r['discharge_date'] ?? 'now') - strtotime($r['admit_date'])) / 86400));
        $items[] = ['Kamar ' . $r['name'], 'kamar', $days, $r['tariff_per_day']];
    }
    return $items;
}

function bil_recalc(int $inv): void
{
    $i = q_one('SELECT * FROM invoices WHERE id=?', [$inv]);
    $total = (float)q_val('SELECT COALESCE(SUM(subtotal),0) FROM invoice_items WHERE invoice_id=?', [$inv]);
    $cover = min((float)$i['insurance_cover'], max(0, $total - (float)$i['discount']));
    q('UPDATE invoices SET total=?, insurance_cover=?, patient_pay=? WHERE id=?', [$total, $cover, max(0, $total - (float)$i['discount'] - $cover), $inv]);
}

/* ------------------------------ AKSI ------------------------------ */
if ($act = ui_post_action()) {
    if ($act === 'create') {
        $vid = (int)($_POST['visit_id'] ?? 0);
        $v = q_one('SELECT * FROM visits WHERE id=?', [$vid]);
        if (!$v || q_val('SELECT 1 FROM invoices WHERE visit_id=?', [$vid])) { flash('danger', 'Kunjungan tidak valid atau sudah memiliki tagihan.'); redirect(ui_url('billing')); }
        $new = ui_tx(function () use ($v) {
            $no = ui_next_no('invoices', 'invoice_no', 'INV-');
            $pat = q_one('SELECT insurer_id, insurance_number FROM patients WHERE id=?', [$v['patient_id']]);
            q('INSERT INTO invoices (invoice_no, visit_id, patient_id, insurer_id, total, insurance_number, status) VALUES (?,?,?,?,0,?,\'belum_bayar\')',
              [$no, $v['id'], $v['patient_id'], $v['insurer_id'] ?: $pat['insurer_id'], $pat['insurance_number']]);
            $inv = (int)db()->lastInsertId();
            foreach (bil_items((int)$v['id']) as [$d, $c, $q, $p]) q('INSERT INTO invoice_items (invoice_id, description, category, qty, price, subtotal) VALUES (?,?,?,?,?,?)', [$inv, $d, $c, $q, $p, $q * $p]);
            bil_recalc($inv);
            return $inv;
        });
        audit('CREATE', BIL_MOD, 'Buat tagihan untuk kunjungan ' . $v['visit_no']);
        flash('success', 'Tagihan dibuat dari layanan kunjungan.');
        redirect(ui_url('billing', ['id' => $new]));
    }
    $inv = q_one('SELECT i.*, s.type ins_type, s.name ins_name FROM invoices i LEFT JOIN insurers s ON s.id=i.insurer_id WHERE i.id=?', [(int)($_POST['inv'] ?? 0)]);
    if (!$inv) redirect(ui_url($key));
    $locked = in_array($inv['status'], ['lunas', 'klaim'], true) || q_val('SELECT 1 FROM payments WHERE invoice_id=?', [$inv['id']]);
    if ($act === 'item_add' && !$locked) {
        $t = q_one('SELECT * FROM tariffs WHERE id=?', [(int)$_POST['tariff_id']]); $qty = max(1, (int)$_POST['qty']);
        if ($t) { q('INSERT INTO invoice_items (invoice_id, description, category, qty, price, subtotal) VALUES (?,?,?,?,?,?)', [$inv['id'], $t['name'], $t['category'], $qty, $t['price'], $qty * $t['price']]); bil_recalc((int)$inv['id']); }
        flash('success', 'Item ditambahkan.');
    }
    if ($act === 'item_del' && !$locked) { q('DELETE FROM invoice_items WHERE id=? AND invoice_id=?', [(int)$_POST['item'], $inv['id']]); bil_recalc((int)$inv['id']); flash('success', 'Item dihapus.'); }
    if ($act === 'discount' && !$locked) {
        $d = max(0, (float)$_POST['discount']);
        if ($d > $inv['total']) { flash('danger', 'Diskon melebihi total tagihan.'); redirect($back); }
        q('UPDATE invoices SET discount=? WHERE id=?', [$d, $inv['id']]); bil_recalc((int)$inv['id']);
        audit('UPDATE', BIL_MOD, 'Diskon ' . $inv['invoice_no'] . ' ' . rupiah($d));
        flash('success', 'Diskon diperbarui.');
    }
    if ($act === 'verify' && !$locked) {
        $ins = (int)$_POST['insurer_id'];
        $type = q_val('SELECT type FROM insurers WHERE id=?', [$ins]);
        $net = max(0, $inv['total'] - $inv['discount']);
        if ($type !== 'umum') {
            if (!trim((string)$_POST['insurance_number'])) { flash('danger', 'Nomor anggota / peserta wajib diisi untuk pasien penjamin.'); redirect($back); }
            if (!empty($_POST['valid_until']) && $_POST['valid_until'] < date('Y-m-d')) { flash('danger', 'Kepesertaan penjamin sudah tidak berlaku.'); redirect($back); }
        }
        $cover = $type === 'umum' ? 0 : ($type === 'bpjs' ? $net : min($net, max(0, (float)$_POST['cover'])));
        q('UPDATE invoices SET insurer_id=?, insurance_number=?, policy_no=?, valid_until=?, verified=1, insurance_cover=? WHERE id=?',
          [$ins, trim((string)$_POST['insurance_number']) ?: null, trim((string)($_POST['policy_no'] ?? '')) ?: null, ($_POST['valid_until'] ?? '') ?: null, $cover, $inv['id']]);
        bil_recalc((int)$inv['id']);
        audit('VERIFY', BIL_MOD, 'Verifikasi penjamin ' . $inv['invoice_no']);
        flash('success', 'Penjamin terverifikasi. Tanggungan asuransi ' . rupiah($cover) . '.');
    }
    if ($act === 'pay' && $inv['verified'] && $inv['status'] !== 'lunas') {
        $paid = (float)q_val('SELECT COALESCE(SUM(amount),0) FROM payments WHERE invoice_id=?', [$inv['id']]);
        $rest = $inv['patient_pay'] - $paid; $amt = (float)$_POST['amount'];
        $method = isset(BIL_METHOD[$_POST['method'] ?? '']) ? $_POST['method'] : 'tunai';
        if ($amt <= 0 || $amt > $rest + 0.01) { flash('danger', 'Jumlah bayar harus antara Rp 1 dan sisa tagihan ' . rupiah($rest) . '.'); redirect($back); }
        if (empty($_POST['confirm'])) { flash('danger', 'Konfirmasi metode pembayaran terlebih dahulu.'); redirect($back); }
        q('INSERT INTO payments (invoice_id, amount, method, cashier_id) VALUES (?,?,?,?)', [$inv['id'], $amt, $method, $me]);
        q("UPDATE invoices SET status=? WHERE id=?", [$amt >= $rest - 0.01 ? $inv['status'] : 'cicilan', $inv['id']]);
        if ($amt < $rest - 0.01) q("UPDATE invoices SET status='cicilan' WHERE id=?", [$inv['id']]);
        audit('CREATE', BIL_MOD, 'Pembayaran ' . $inv['invoice_no'] . ' ' . rupiah($amt) . " ($method)");
        flash('success', 'Pembayaran ' . rupiah($amt) . ' diterima.');
    }
    if ($act === 'claim' && $inv['verified'] && $inv['insurance_cover'] > 0 && !q_val('SELECT 1 FROM insurance_claims WHERE invoice_id=?', [$inv['id']])) {
        $code = (string)q_val('SELECT code FROM insurers WHERE id=?', [$inv['insurer_id']]);
        $no = ui_next_no('insurance_claims', 'claim_no', 'KLM-' . $code . '-');
        q("INSERT INTO insurance_claims (claim_no, invoice_id, insurer_id, amount, status) VALUES (?,?,?,?, 'diajukan')", [$no, $inv['id'], $inv['insurer_id'], $inv['insurance_cover']]);
        audit('CREATE', BIL_MOD, "Pengajuan klaim $no");
        flash('success', "Klaim $no diajukan ke " . $inv['ins_name'] . '.');
    }
    if ($act === 'claim_status') {
        $st = $_POST['status'] ?? '';
        if (in_array($st, ['diverifikasi', 'disetujui', 'ditolak', 'dibayar'], true)) { q('UPDATE insurance_claims SET status=? WHERE invoice_id=?', [$st, $inv['id']]); audit('UPDATE', BIL_MOD, 'Status klaim ' . $inv['invoice_no'] . " → $st"); flash('success', 'Status klaim diperbarui.'); }
    }
    if ($act === 'finalize') {
        $paid = (float)q_val('SELECT COALESCE(SUM(amount),0) FROM payments WHERE invoice_id=?', [$inv['id']]);
        $claim = q_val('SELECT status FROM insurance_claims WHERE invoice_id=?', [$inv['id']]);
        if (!$inv['verified']) { flash('danger', 'Penjamin belum diverifikasi.'); redirect($back); }
        if ($paid + 0.01 < $inv['patient_pay']) { flash('danger', 'Tanggungan pasien belum lunas (sisa ' . rupiah($inv['patient_pay'] - $paid) . ').'); redirect($back); }
        if ($inv['insurance_cover'] > 0 && !$claim) { flash('danger', 'Klaim asuransi belum diajukan.'); redirect($back); }
        $status = $inv['insurance_cover'] > 0 && !in_array($claim, ['dibayar'], true) ? 'klaim' : 'lunas';
        q('UPDATE invoices SET status=?, paid_at=NOW() WHERE id=?', [$status, $inv['id']]);
        audit('VALIDATE', BIL_MOD, 'Finalisasi transaksi ' . $inv['invoice_no'] . " ($status)");
        flash('success', 'Transaksi difinalisasi. Invoice siap dicetak.');
    }
    redirect($back);
}

/* ------------------------------ DATA ------------------------------ */
$insurers = q_all('SELECT * FROM insurers ORDER BY id');
$titles = ['billing' => ['Billing & Klaim', 'Tagihan pasien, verifikasi penjamin, pembayaran, dan klaim asuransi.'], 'rekap' => ['Rekapitulasi Biaya', 'Ringkasan tagihan, penerimaan, dan klaim per periode.'],
           'riwayat' => ['Riwayat Transaksi', 'Seluruh transaksi pembayaran di kasir.'], 'invoice' => ['Cetak Invoice', 'Pilih tagihan untuk dicetak.']];
$inv = null;
if ($id) {
    $inv = q_one('SELECT i.*, s.name ins_name, s.type ins_type, v.visit_no, v.visit_type, v.visit_date, p.name, p.mrn FROM invoices i JOIN patients p ON p.id=i.patient_id
                  LEFT JOIN insurers s ON s.id=i.insurer_id LEFT JOIN visits v ON v.id=i.visit_id WHERE i.id=?', [$id]);
    if ($inv) {
        $items = q_all('SELECT * FROM invoice_items WHERE invoice_id=? ORDER BY id', [$id]);
        $pays  = q_all('SELECT y.*, u.full_name FROM payments y LEFT JOIN users u ON u.id=y.cashier_id WHERE y.invoice_id=? ORDER BY y.paid_at', [$id]);
        $claim = q_one('SELECT * FROM insurance_claims WHERE invoice_id=?', [$id]);
        $paid  = array_sum(array_column($pays, 'amount'));
        $patient = ui_patient_row((int)$inv['patient_id']);
    }
}

layout_start($PAGE['title'], $key, $PAGE['section']);
?>
<div class="content">
  <div class="page-head no-print"><div><h2><?= e($titles[$key][0]) ?></h2><p><?= e($titles[$key][1]) ?></p></div>
    <div class="page-actions"><?php if ($inv): ?><a class="btn" href="<?= e(ui_url($key)) ?>">← Kembali</a><?php if ($key !== 'invoice'): ?><a class="btn" href="<?= e(ui_url('invoice', ['id' => $id])) ?>"><?= icon('printer', 16) ?> Invoice</a><?php else: ?><button class="btn btn-primary" onclick="print()"><?= icon('printer', 16) ?> Cetak</button><?php endif; endif; ?></div></div>
  <?php flashes(); ?>

<?php if (in_array($key, ['billing', 'invoice'], true) && !$inv):
  $fs = $_GET['status'] ?? ''; $q = trim((string)($_GET['q'] ?? ''));
  $list = q_all("SELECT i.*, p.name, p.mrn, s.name ins_name, v.visit_type, (SELECT COALESCE(SUM(amount),0) FROM payments WHERE invoice_id=i.id) paid FROM invoices i JOIN patients p ON p.id=i.patient_id
                 LEFT JOIN insurers s ON s.id=i.insurer_id LEFT JOIN visits v ON v.id=i.visit_id WHERE (?='' OR i.status=?) AND (?='' OR p.name LIKE ? OR p.mrn LIKE ? OR i.invoice_no LIKE ?) ORDER BY i.created_at DESC",
                [$fs, $fs, $q, "%$q%", "%$q%", "%$q%"]);
  $unbilled = $key === 'billing' ? q_all("SELECT v.id, v.visit_no, v.visit_type, v.status, p.name, p.mrn FROM visits v JOIN patients p ON p.id=v.patient_id
                 WHERE v.status IN ('selesai','dirawat','diperiksa') AND NOT EXISTS (SELECT 1 FROM invoices i WHERE i.visit_id=v.id) ORDER BY v.visit_date DESC") : [];
  $all = q_all('SELECT status, COUNT(*) n, SUM(total) t FROM invoices GROUP BY status');
  $sum = fn($s, $k) => array_sum(array_map(fn($r) => in_array($r['status'], (array)$s, true) ? $r[$k] : 0, $all)); ?>
  <section class="kpi-grid">
    <?= ui_kpi('Total Tagihan', '<span style="font-size:20px">' . rupiah($sum(['belum_bayar', 'cicilan', 'lunas', 'klaim'], 't')) . '</span>', 'receipt') ?>
    <?= ui_kpi('Menunggu Bayar', $sum(['belum_bayar', 'cicilan'], 'n'), 'clock', 'warning') ?>
    <?= ui_kpi('Diklaim Asuransi', $sum('klaim', 'n'), 'shield', 'info') ?>
    <?= ui_kpi('Lunas', $sum('lunas', 'n'), 'check') ?>
  </section>
  <?php if ($unbilled): ?>
  <div class="card mb"><div class="card-head"><div><h3>Kunjungan Belum Ditagih</h3><p>Tagihan dibuat otomatis dari layanan yang tercatat (lab, radiologi, obat, tindakan, kamar).</p></div><span class="badge warning"><?= count($unbilled) ?></span></div>
    <div class="table-wrap"><table class="table"><tbody><?php foreach ($unbilled as $u): ?><tr><td class="mono"><?= e($u['visit_no']) ?></td><td><b><?= e($u['name']) ?></b> <small class="mono muted"><?= e($u['mrn']) ?></small></td><td><?= e(ui_label($u['visit_type'])) ?></td><td><?= status_badge($u['status']) ?></td>
      <td><form method="post" class="actions"><?= csrf_field() ?><input type="hidden" name="act" value="create"><input type="hidden" name="visit_id" value="<?= $u['id'] ?>"><button class="btn btn-sm btn-primary"><?= icon('plus', 14) ?> Buat Tagihan</button></form></td></tr><?php endforeach; ?></tbody></table></div></div>
  <?php endif; ?>
  <div class="card">
    <form class="toolbar" method="get"><input type="hidden" name="page" value="<?= e($key) ?>"><b style="color:var(--heading)">Daftar Tagihan</b><span class="grow"></span>
      <select class="input" name="status" onchange="this.form.submit()"><option value="">Semua status</option><?= ui_opt_map(['belum_bayar' => 'Belum Bayar', 'cicilan' => 'Cicilan', 'klaim' => 'Klaim', 'lunas' => 'Lunas'], $fs) ?></select>
      <input class="input" name="q" value="<?= e($q) ?>" placeholder="Cari pasien / No. invoice"><button class="btn"><?= icon('search', 15) ?></button></form>
    <div class="table-wrap"><table class="table"><thead><tr><th>No. Invoice</th><th>Pasien</th><th>Layanan</th><th>Penjamin</th><th>Total</th><th>Tanggung Asuransi</th><th>Tanggung Pasien</th><th>Status</th><th style="text-align:right">Aksi</th></tr></thead><tbody>
    <?php if (!$list): ?><tr><td colspan="9" class="empty">Tidak ada tagihan.</td></tr><?php endif; foreach ($list as $r): ?>
      <tr><td class="mono strong"><?= e($r['invoice_no']) ?></td><td><b><?= e($r['name']) ?></b><br><small class="mono muted"><?= e($r['mrn']) ?></small></td><td><?= e(ui_label($r['visit_type'] ?? '-')) ?></td><td><?= e($r['ins_name'] ?? '-') ?></td>
        <td class="mono"><?= rupiah($r['total']) ?></td><td class="mono"><?= rupiah($r['insurance_cover']) ?></td><td class="mono"><?= rupiah($r['patient_pay']) ?><?php if ($r['status'] === 'cicilan'): ?><br><small class="muted">dibayar <?= rupiah($r['paid']) ?></small><?php endif; ?></td>
        <td><?= status_badge($r['status']) ?></td><td><div class="actions"><a class="btn btn-sm <?= in_array($r['status'], ['belum_bayar', 'cicilan'], true) && $key === 'billing' ? 'btn-primary' : '' ?>" href="<?= e(ui_url($key, ['id' => $r['id']])) ?>"><?= $key === 'invoice' ? 'Cetak' : 'Kelola' ?></a></div></td></tr>
    <?php endforeach; ?></tbody></table></div></div>

<?php elseif ($key === 'billing' && $inv):
  $locked = in_array($inv['status'], ['lunas', 'klaim'], true) || $pays;
  $stage = in_array($inv['status'], ['lunas', 'klaim'], true) ? 4 : (!$inv['verified'] ? ($items ? 1 : 0) : 2);
  $rest = $inv['patient_pay'] - $paid;
  if ($stage === 2 && $rest <= 0.01 && ($inv['insurance_cover'] <= 0 || $claim)) $stage = 3; ?>
  <div class="card stepper-card"><?= ui_stepper(BIL_STEPS, $stage) ?></div>
  <?= ui_patient($patient, '<span class="badge info">' . e($inv['invoice_no']) . '</span>') ?>
  <section class="grid grid-2">
    <div class="card"><div class="card-head"><div><h3>Rincian Tagihan</h3><p><span class="mono"><?= e($inv['visit_no'] ?? '-') ?></span> · <?= e(ui_label($inv['visit_type'] ?? '-')) ?> · <?= tgl($inv['visit_date']) ?></p></div><?= status_badge($inv['status']) ?></div>
      <div class="table-wrap"><table class="table"><thead><tr><th>Uraian</th><th>Kategori</th><th>Qty</th><th>Tarif</th><th>Subtotal</th><th></th></tr></thead><tbody>
      <?php foreach ($items as $it): ?><tr><td><?= e($it['description']) ?></td><td><?= e(ui_label($it['category'])) ?></td><td class="mono"><?= (int)$it['qty'] ?></td><td class="mono"><?= rupiah($it['price']) ?></td><td class="mono strong"><?= rupiah($it['subtotal']) ?></td>
        <td><?php if (!$locked): ?><form method="post" data-confirm="Hapus item tagihan ini?" data-confirm-title="Hapus Item" data-confirm-ok="Hapus"><?= csrf_field() ?><input type="hidden" name="act" value="item_del"><input type="hidden" name="inv" value="<?= $id ?>"><input type="hidden" name="item" value="<?= $it['id'] ?>"><button class="icon-btn danger"><?= icon('trash', 14) ?></button></form><?php endif; ?></td></tr><?php endforeach; ?>
      </tbody></table></div>
      <div class="card-body"><div class="invoice-doc" style="padding:0"><div class="totals">
        <div><span>Total Tagihan</span><b class="mono"><?= rupiah($inv['total']) ?></b></div><div><span>Diskon</span><span class="mono">− <?= rupiah($inv['discount']) ?></span></div>
        <div><span>Tanggungan Asuransi</span><span class="mono">− <?= rupiah($inv['insurance_cover']) ?></span></div><div class="grand"><span>Tanggung Pasien</span><span class="mono"><?= rupiah($inv['patient_pay']) ?></span></div>
        <div><span>Sudah Dibayar</span><span class="mono"><?= rupiah($paid) ?></span></div><div><span><b>Sisa</b></span><b class="mono"><?= rupiah(max(0, $rest)) ?></b></div></div></div></div>
      <?php if (!$locked): ?><div class="card-foot" style="justify-content:space-between">
        <form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="act" value="item_add"><input type="hidden" name="inv" value="<?= $id ?>">
          <select class="input" name="tariff_id"><?= ui_options(q_all('SELECT id, name, price FROM tariffs ORDER BY category, name'), 'id', fn($t) => $t['name'] . ' (' . rupiah($t['price']) . ')') ?></select><input class="input" type="number" name="qty" value="1" min="1" style="width:70px"><button class="btn btn-sm">Tambah</button></form>
        <form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="act" value="discount"><input type="hidden" name="inv" value="<?= $id ?>"><input class="input" type="number" name="discount" min="0" value="<?= (float)$inv['discount'] ?>" style="width:130px"><button class="btn btn-sm">Diskon</button></form></div><?php endif; ?>
    </div>

    <div>
      <form method="post" class="card mb"><?= csrf_field() ?><input type="hidden" name="act" value="verify"><input type="hidden" name="inv" value="<?= $id ?>">
        <div class="card-head"><div><h3>Verifikasi Informasi Penjamin</h3><p><?= $inv['verified'] ? 'Terverifikasi' : 'Belum diverifikasi' ?></p></div><?= $inv['verified'] ? status_badge('diverifikasi') : status_badge('menunggu') ?></div>
        <div class="card-body"><div class="form-grid">
          <div class="field"><label>Jenis Penjamin</label><select name="insurer_id" id="insSel" <?= $locked ? 'disabled' : '' ?>><?php foreach ($insurers as $s): ?><option value="<?= $s['id'] ?>" data-type="<?= $s['type'] ?>" <?= (int)$s['id'] === (int)$inv['insurer_id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option><?php endforeach; ?></select></div>
          <div class="field" data-ins><label>No. Anggota / Peserta</label><input name="insurance_number" value="<?= e($inv['insurance_number']) ?>" placeholder="Nomor anggota / peserta..." <?= $locked ? 'disabled' : '' ?>></div>
          <div class="field" data-ins><label>No. Polis</label><input name="policy_no" value="<?= e($inv['policy_no']) ?>" placeholder="Nomor polis asuransi..." <?= $locked ? 'disabled' : '' ?>></div>
          <div class="field" data-ins><label>Berlaku Hingga</label><input type="date" name="valid_until" value="<?= e($inv['valid_until']) ?>" <?= $locked ? 'disabled' : '' ?>></div>
          <div class="field" data-cover><label>Plafon Ditanggung (Rp)</label><input type="number" name="cover" min="0" value="<?= (float)($inv['insurance_cover'] ?: round(($inv['total'] - $inv['discount']) * 0.8)) ?>" <?= $locked ? 'disabled' : '' ?>><span class="field-hint">BPJS = 100% otomatis; Umum = 0.</span></div></div></div>
        <?php if (!$locked): ?><div class="card-foot"><button class="btn btn-primary"><?= icon('shield', 16) ?> Verifikasi Penjamin</button></div><?php endif; ?></form>
      <script>(function(){const s=document.getElementById('insSel');const f=()=>{const t=s.options[s.selectedIndex].dataset.type;document.querySelectorAll('[data-ins]').forEach(e=>e.classList.toggle('hidden',t==='umum'));document.querySelectorAll('[data-cover]').forEach(e=>e.classList.toggle('hidden',t==='umum'||t==='bpjs'))};s.onchange=f;f()})();</script>

      <?php if ($inv['verified']): ?>
      <div class="card mb"><div class="card-head"><div><h3>Pembayaran & Klaim</h3><p>Sisa tanggung pasien <b class="mono"><?= rupiah(max(0, $rest)) ?></b></p></div></div>
        <?php if ($pays): ?><ul class="list"><?php foreach ($pays as $y): ?><li><span class="l-dot"></span><div class="l-main"><b class="mono"><?= rupiah($y['amount']) ?></b><span><?= e(BIL_METHOD[$y['method']]) ?> · <?= e($y['full_name'] ?? '-') ?></span></div><time class="mono"><?= tgl($y['paid_at'], true) ?></time></li><?php endforeach; ?></ul><?php endif; ?>
        <?php if ($rest > 0.01): ?><form method="post" class="card-body" data-confirm="Pastikan nominal dan metode pembayaran sudah sesuai." data-confirm-title="Konfirmasi Pembayaran" data-confirm-ok="Proses Bayar"><?= csrf_field() ?><input type="hidden" name="act" value="pay"><input type="hidden" name="inv" value="<?= $id ?>">
          <div class="form-grid"><div class="field"><label>Metode Pembayaran</label><select name="method"><?= ui_opt_map(BIL_METHOD) ?></select></div><div class="field"><label>Jumlah (Rp)</label><input type="number" name="amount" min="1" max="<?= $rest ?>" value="<?= $rest ?>" required></div></div>
          <label class="check" style="margin-bottom:12px"><input type="checkbox" name="confirm" value="1" required> Konfirmasi metode pembayaran & nominal</label><button class="btn btn-primary"><?= icon('money', 16) ?> Terima Pembayaran</button></form><?php endif; ?>
        <div class="card-foot" style="justify-content:space-between">
          <?php if ($inv['insurance_cover'] > 0): ?><?php if ($claim): ?><span>Klaim <span class="mono"><?= e($claim['claim_no']) ?></span> <?= status_badge($claim['status']) ?></span>
            <form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="act" value="claim_status"><input type="hidden" name="inv" value="<?= $id ?>"><select class="input" name="status"><?= ui_opt_map(['diverifikasi' => 'Diverifikasi', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak', 'dibayar' => 'Dibayar'], $claim['status']) ?></select><button class="btn btn-sm">Update</button></form>
          <?php else: ?><form method="post"><?= csrf_field() ?><input type="hidden" name="act" value="claim"><input type="hidden" name="inv" value="<?= $id ?>"><button class="btn"><?= icon('shield', 16) ?> Ajukan Klaim <?= rupiah($inv['insurance_cover']) ?></button></form><?php endif; else: ?><span class="muted">Tanpa klaim asuransi.</span><?php endif; ?></div></div>
      <?php if (!in_array($inv['status'], ['lunas', 'klaim'], true)): ?><form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="act" value="finalize"><input type="hidden" name="inv" value="<?= $id ?>">
        <div class="card-head"><div><h3>Finalisasi Transaksi</h3><p>Syarat: penjamin terverifikasi, tanggung pasien lunas, klaim diajukan (bila ada).</p></div></div>
        <div class="card-foot"><button class="btn btn-primary" <?= $stage < 3 ? 'disabled' : '' ?>><?= icon('check', 16) ?> Finalisasi Transaksi</button></div></form>
      <?php else: ?><div class="alert success"><?= icon('check', 18) ?><div>Transaksi final (<?= e(ui_label($inv['status'])) ?>) <?= tgl($inv['paid_at'], true) ?>. <a href="<?= e(ui_url('invoice', ['id' => $id])) ?>">Cetak invoice</a></div></div><?php endif; ?>
      <?php endif; ?>
    </div>
  </section>

<?php elseif ($key === 'invoice' && $inv): ?>
  <div class="card invoice-doc print-area" style="max-width:860px;margin:0 auto">
    <div class="inv-head"><div style="display:flex;gap:12px;align-items:center"><img src="assets/logo.png" alt="Logo RS Suka Sehat"><div><b style="font-size:18px;color:var(--heading)">RS Suka Sehat</b><br><small class="muted"><?= e(setting('hospital_address', 'Jl. Sehat Sejahtera No. 1')) ?></small></div></div>
      <div style="text-align:right"><b style="font-size:20px;color:var(--primary)">INVOICE</b><br><span class="mono"><?= e($inv['invoice_no']) ?></span><br><small class="muted"><?= tgl($inv['created_at'], true) ?></small></div></div>
    <?= ui_details(['Pasien' => e($inv['name']), 'No. RM' => '<span class="mono">' . e($inv['mrn']) . '</span>', 'No. Kunjungan' => '<span class="mono">' . e($inv['visit_no'] ?? '-') . '</span>', 'Layanan' => e(ui_label($inv['visit_type'] ?? '-')),
        'Penjamin' => e($inv['ins_name'] ?? '-') . ($inv['insurance_number'] ? ' · <span class="mono">' . e($inv['insurance_number']) . '</span>' : ''), 'Status' => status_badge($inv['status'])]) ?>
    <div class="table-wrap"><table class="table"><thead><tr><th>No</th><th>Uraian</th><th>Qty</th><th>Tarif</th><th>Subtotal</th></tr></thead><tbody>
    <?php foreach ($items as $i => $it): ?><tr><td class="mono"><?= $i + 1 ?></td><td><?= e($it['description']) ?></td><td class="mono"><?= (int)$it['qty'] ?></td><td class="mono"><?= rupiah($it['price']) ?></td><td class="mono"><?= rupiah($it['subtotal']) ?></td></tr><?php endforeach; ?></tbody></table></div>
    <div class="totals" style="margin-top:14px"><div><span>Total</span><span class="mono"><?= rupiah($inv['total']) ?></span></div><div><span>Diskon</span><span class="mono">− <?= rupiah($inv['discount']) ?></span></div>
      <div><span>Ditanggung Penjamin</span><span class="mono">− <?= rupiah($inv['insurance_cover']) ?></span></div><div class="grand"><span>Tanggung Pasien</span><span class="mono"><?= rupiah($inv['patient_pay']) ?></span></div>
      <div><span>Dibayar</span><span class="mono"><?= rupiah($paid) ?></span></div><div><span>Sisa</span><span class="mono"><?= rupiah(max(0, $inv['patient_pay'] - $paid)) ?></span></div></div>
    <p class="muted" style="margin-top:24px;font-size:12px">Dicetak <?= tgl(date('Y-m-d H:i:s'), true) ?> oleh <?= e($user['name'] ?? '') ?>. Invoice sah tanpa tanda tangan.</p>
  </div>

<?php elseif ($key === 'riwayat'):
  $from = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['from'] ?? '') ? $_GET['from'] : date('Y-m-01');
  $to   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['to'] ?? '') ? $_GET['to'] : date('Y-m-d');
  $fm = $_GET['method'] ?? '';
  $rows = q_all("SELECT y.*, i.invoice_no, p.name, p.mrn, u.full_name FROM payments y JOIN invoices i ON i.id=y.invoice_id JOIN patients p ON p.id=i.patient_id LEFT JOIN users u ON u.id=y.cashier_id
                 WHERE DATE(y.paid_at) BETWEEN ? AND ? AND (?='' OR y.method=?) ORDER BY y.paid_at DESC", [$from, $to, $fm, $fm]); ?>
  <div class="card">
    <form class="toolbar" method="get"><input type="hidden" name="page" value="riwayat"><input class="input" type="date" name="from" value="<?= e($from) ?>"><input class="input" type="date" name="to" value="<?= e($to) ?>">
      <select class="input" name="method"><option value="">Semua metode</option><?= ui_opt_map(BIL_METHOD, $fm) ?></select><button class="btn btn-primary">Tampilkan</button><span class="grow"></span><span class="badge"><?= count($rows) ?> transaksi · <?= rupiah(array_sum(array_column($rows, 'amount'))) ?></span></form>
    <div class="table-wrap"><table class="table"><thead><tr><th>Waktu</th><th>No. Invoice</th><th>Pasien</th><th>Metode</th><th>Jumlah</th><th>Kasir</th><th></th></tr></thead><tbody>
    <?php if (!$rows): ?><tr><td colspan="7" class="empty">Tidak ada transaksi pada periode ini.</td></tr><?php endif; foreach ($rows as $r): ?>
      <tr><td class="mono"><?= tgl($r['paid_at'], true) ?></td><td class="mono strong"><?= e($r['invoice_no']) ?></td><td><?= e($r['name']) ?> <small class="mono muted"><?= e($r['mrn']) ?></small></td><td><?= e(BIL_METHOD[$r['method']]) ?></td><td class="mono strong"><?= rupiah($r['amount']) ?></td><td><?= e($r['full_name'] ?? '-') ?></td>
        <td><a class="btn btn-sm" href="<?= e(ui_url('invoice', ['id' => $r['invoice_id']])) ?>"><?= icon('printer', 14) ?></a></td></tr><?php endforeach; ?></tbody></table></div></div>

<?php elseif ($key === 'rekap'):
  $from = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['from'] ?? '') ? $_GET['from'] : date('Y-m-01');
  $to   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['to'] ?? '') ? $_GET['to'] : date('Y-m-d');
  $P = [$from, $to];
  $tot  = q_one('SELECT COUNT(*) n, COALESCE(SUM(total),0) total, COALESCE(SUM(discount),0) disc, COALESCE(SUM(insurance_cover),0) cover, COALESCE(SUM(patient_pay),0) pp FROM invoices WHERE DATE(created_at) BETWEEN ? AND ?', $P);
  $cash = (float)q_val('SELECT COALESCE(SUM(amount),0) FROM payments WHERE DATE(paid_at) BETWEEN ? AND ?', $P);
  $byCat = q_all('SELECT it.category, SUM(it.subtotal) t FROM invoice_items it JOIN invoices i ON i.id=it.invoice_id WHERE DATE(i.created_at) BETWEEN ? AND ? GROUP BY it.category ORDER BY t DESC', $P);
  $byIns = q_all('SELECT COALESCE(s.name,"-") name, COUNT(*) n, SUM(i.total) t FROM invoices i LEFT JOIN insurers s ON s.id=i.insurer_id WHERE DATE(i.created_at) BETWEEN ? AND ? GROUP BY s.name ORDER BY t DESC', $P);
  $byMet = q_all('SELECT method, COUNT(*) n, SUM(amount) t FROM payments WHERE DATE(paid_at) BETWEEN ? AND ? GROUP BY method ORDER BY t DESC', $P);
  $claims = q_all('SELECT status, COUNT(*) n, SUM(amount) t FROM insurance_claims WHERE DATE(submitted_at) BETWEEN ? AND ? GROUP BY status', $P);
  $maxCat = max(1, ...array_map(fn($r) => (float)$r['t'], $byCat ?: [['t' => 1]])); ?>
  <form class="card toolbar mb no-print" method="get" style="border-radius:var(--radius)"><input type="hidden" name="page" value="rekap"><b style="color:var(--heading)">Periode</b>
    <input class="input" type="date" name="from" value="<?= e($from) ?>"><input class="input" type="date" name="to" value="<?= e($to) ?>"><button class="btn btn-primary">Tampilkan</button><span class="grow"></span><button class="btn" type="button" onclick="print()"><?= icon('printer', 16) ?> Cetak</button></form>
  <section class="kpi-grid">
    <?= ui_kpi('Total Tagihan', '<span style="font-size:20px">' . rupiah($tot['total']) . '</span>', 'receipt', '', $tot['n'] . ' invoice') ?>
    <?= ui_kpi('Tanggungan Asuransi', '<span style="font-size:20px">' . rupiah($tot['cover']) . '</span>', 'shield', 'info') ?>
    <?= ui_kpi('Tanggung Pasien', '<span style="font-size:20px">' . rupiah($tot['pp']) . '</span>', 'user', 'warning', 'diskon ' . rupiah($tot['disc'])) ?>
    <?= ui_kpi('Kas Diterima', '<span style="font-size:20px">' . rupiah($cash) . '</span>', 'money', 'purple') ?>
  </section>
  <section class="grid grid-2e mb">
    <div class="card"><div class="card-head"><h3>Pendapatan per Kategori</h3></div><div class="card-body"><?php if (!$byCat): ?><div class="empty">Belum ada data.</div><?php endif; foreach ($byCat as $r): ?>
      <div class="stat-row"><span><?= e(ui_label($r['category'])) ?></span><b><?= rupiah($r['t']) ?></b></div><div class="progress"><div style="width:<?= round($r['t'] / $maxCat * 100) ?>%"></div></div><?php endforeach; ?></div></div>
    <div class="card"><div class="card-head"><h3>Per Penjamin</h3></div><div class="table-wrap"><table class="table"><thead><tr><th>Penjamin</th><th>Invoice</th><th>Total</th></tr></thead><tbody>
      <?php foreach ($byIns as $r): ?><tr><td><?= e($r['name']) ?></td><td class="mono"><?= (int)$r['n'] ?></td><td class="mono"><?= rupiah($r['t']) ?></td></tr><?php endforeach; ?></tbody></table></div></div>
  </section>
  <section class="grid grid-2e">
    <div class="card"><div class="card-head"><h3>Penerimaan per Metode</h3></div><div class="table-wrap"><table class="table"><thead><tr><th>Metode</th><th>Transaksi</th><th>Total</th></tr></thead><tbody>
      <?php if (!$byMet): ?><tr><td colspan="3" class="empty">Belum ada pembayaran.</td></tr><?php endif; foreach ($byMet as $r): ?><tr><td><?= e(BIL_METHOD[$r['method']]) ?></td><td class="mono"><?= (int)$r['n'] ?></td><td class="mono"><?= rupiah($r['t']) ?></td></tr><?php endforeach; ?></tbody></table></div></div>
    <div class="card"><div class="card-head"><h3>Status Klaim Asuransi</h3></div><div class="table-wrap"><table class="table"><thead><tr><th>Status</th><th>Klaim</th><th>Nilai</th></tr></thead><tbody>
      <?php if (!$claims): ?><tr><td colspan="3" class="empty">Belum ada klaim.</td></tr><?php endif; foreach ($claims as $r): ?><tr><td><?= status_badge($r['status']) ?></td><td class="mono"><?= (int)$r['n'] ?></td><td class="mono"><?= rupiah($r['t']) ?></td></tr><?php endforeach; ?></tbody></table></div></div>
  </section>
<?php else: ?><div class="empty">Tagihan tidak ditemukan.</div><?php endif; ?>
</div>
<?php layout_end();
