<?php
/*
 * MODUL 10 — FARMASI (database)
 * Alur resep: Resep Masuk (CPOE) → Telaah/Verifikasi → Penyiapan (stok berkurang) → Pemeriksaan Akhir → Penyerahan & Edukasi
 * Menu: resep, dispensing, stok, retur, laporan
 */
const FAR_STEPS = ['Telaah Resep', 'Penyiapan Obat', 'Pemeriksaan Akhir', 'Penyerahan & Edukasi'];
const FAR_MOD   = 'Pharmacy';
$FAR_STAGE = ['baru' => 0, 'diverifikasi' => 1, 'disiapkan' => 2, 'diserahkan' => 4, 'ditolak' => 0];

$key = $PAGE['key'];
$id  = (int)($_GET['id'] ?? 0);
$me  = (int)$user['id'];
$back = ui_url($key, $id ? ['id' => $id] : []);

function far_move(int $drug, string $type, int $qty, string $ref, string $note, int $by): void
{
    q('UPDATE drugs SET stock = stock + ? WHERE id=?', [$qty, $drug]);
    q('INSERT INTO drug_stock_movements (drug_id, movement_type, qty, reference, notes, created_by) VALUES (?,?,?,?,?,?)', [$drug, $type, $qty, $ref, $note, $by]);
}

/* ------------------------------ AKSI ------------------------------ */
if ($act = ui_post_action()) {
    if (in_array($act, ['verify', 'reject', 'prepare', 'handover'], true)) {
        $rx = q_one('SELECT * FROM prescriptions WHERE id=?', [(int)$_POST['rx']]);
        if (!$rx) { flash('danger', 'Resep tidak ditemukan.'); redirect(ui_url($key)); }
        $items = q_all('SELECT pi.*, d.name, d.stock FROM prescription_items pi JOIN drugs d ON d.id=pi.drug_id WHERE pi.prescription_id=?', [$rx['id']]);

        if ($act === 'verify' && $rx['status'] === 'baru') {
            if (count((array)($_POST['check'] ?? [])) < 3) { flash('danger', 'Telaah administratif, farmasetik, dan klinis wajib dicentang.'); redirect($back); }
            q("UPDATE prescriptions SET status='diverifikasi', verified_by=?, notes=? WHERE id=?", [$me, trim((string)($_POST['notes'] ?? '')) ?: null, $rx['id']]);
            audit('VERIFY', FAR_MOD, 'Telaah resep ' . $rx['prescription_no'] . ' disetujui');
            flash('success', 'Resep terverifikasi. Lanjut ke penyiapan obat.');
            redirect(ui_url('dispensing', ['id' => $rx['id']]));
        }
        if ($act === 'reject' && in_array($rx['status'], ['baru', 'diverifikasi'], true)) {
            $why = trim((string)($_POST['reject_reason'] ?? ''));
            if ($why === '') { flash('danger', 'Alasan penolakan wajib diisi.'); redirect($back); }
            q("UPDATE prescriptions SET status='ditolak', reject_reason=?, verified_by=? WHERE id=?", [$why, $me, $rx['id']]);
            audit('REJECT', FAR_MOD, 'Resep ' . $rx['prescription_no'] . " ditolak: $why");
            flash('warning', 'Resep ditolak dan dikembalikan ke dokter penulis resep.');
            redirect(ui_url('resep'));
        }
        if ($act === 'prepare' && $rx['status'] === 'diverifikasi') {
            foreach ($items as $it) if ($it['stock'] < $it['qty']) { flash('danger', "Stok {$it['name']} tidak cukup ({$it['stock']} tersedia, butuh {$it['qty']})."); redirect($back); }
            ui_tx(function () use ($items, $rx, $me) {
                foreach ($items as $it) far_move((int)$it['drug_id'], 'keluar', -(int)$it['qty'], $rx['prescription_no'], 'Penyiapan resep', $me);
                q("UPDATE prescriptions SET status='disiapkan' WHERE id=?", [$rx['id']]);
            });
            audit('UPDATE', FAR_MOD, 'Obat resep ' . $rx['prescription_no'] . ' disiapkan, stok dikurangi');
            flash('success', 'Obat disiapkan & stok diperbarui. Lakukan pemeriksaan akhir sebelum diserahkan.');
        }
        if ($act === 'handover' && $rx['status'] === 'disiapkan') {
            if (count((array)($_POST['check'] ?? [])) < 5) { flash('danger', 'Pemeriksaan akhir (5 benar) wajib lengkap.'); redirect($back); }
            $receiver = trim((string)($_POST['receiver'] ?? ''));
            if ($receiver === '') { flash('danger', 'Nama penerima obat wajib diisi.'); redirect($back); }
            ui_tx(function () use ($rx, $receiver) {
                $edu = trim((string)($_POST['education'] ?? ''));
                q("UPDATE prescriptions SET status='diserahkan', handed_at=NOW(), notes=CONCAT_WS(' | ', notes, ?) WHERE id=?",
                  ['Diterima: ' . $receiver . ($edu ? '; Edukasi: ' . $edu : ''), $rx['id']]);
                q("UPDATE orders SET status='selesai' WHERE visit_id=? AND order_type='obat' AND status<>'dibatalkan'", [$rx['visit_id']]);
            });
            audit('UPDATE', FAR_MOD, 'Resep ' . $rx['prescription_no'] . " diserahkan kepada $receiver");
            flash('success', 'Obat telah diserahkan kepada pasien/keluarga.');
        }
        redirect(ui_url($key === 'resep' ? 'resep' : 'dispensing', ['id' => $rx['id']]));
    }

    if ($act === 'adjust') {
        $drug = q_one('SELECT * FROM drugs WHERE id=?', [(int)$_POST['drug_id']]);
        $qty  = (int)($_POST['qty'] ?? 0);
        $type = $_POST['type'] === 'masuk' ? 'masuk' : 'penyesuaian';
        $note = trim((string)($_POST['notes'] ?? ''));
        if (!$drug || $qty === 0) { flash('danger', 'Pilih obat dan isi jumlah (tidak boleh 0).'); redirect($back); }
        if ($type === 'masuk' && $qty < 0) { flash('danger', 'Penerimaan obat harus bernilai positif.'); redirect($back); }
        if ($drug['stock'] + $qty < 0) { flash('danger', 'Penyesuaian membuat stok negatif.'); redirect($back); }
        if ($note === '') { flash('danger', 'Keterangan / referensi wajib diisi.'); redirect($back); }
        ui_tx(fn() => far_move((int)$drug['id'], $type, $qty, trim((string)($_POST['reference'] ?? '')) ?: strtoupper($type), $note, $me));
        if ($type === 'masuk' && !empty($_POST['expiry_date'])) q('UPDATE drugs SET expiry_date=? WHERE id=?', [$_POST['expiry_date'], $drug['id']]);
        audit('UPDATE', FAR_MOD, ucfirst($type) . " stok {$drug['name']} ($qty)");
        flash('success', "Stok {$drug['name']} diperbarui.");
        redirect(ui_url('stok'));
    }

    if ($act === 'return_new') {
        $drug = (int)($_POST['drug_id'] ?? 0); $qty = (int)($_POST['qty'] ?? 0);
        $src = trim((string)($_POST['source'] ?? '')); $why = trim((string)($_POST['reason'] ?? ''));
        if (!$drug || $qty <= 0 || $src === '' || $why === '') { flash('danger', 'Lengkapi asal retur, obat, jumlah, dan alasan.'); redirect(ui_url('retur')); }
        $no = ui_next_no('drug_returns', 'return_no', 'RT-');
        q('INSERT INTO drug_returns (return_no, prescription_id, source, drug_id, qty, drug_condition, reason) VALUES (?,?,?,?,?,?,?)',
          [$no, ($_POST['prescription_id'] ?? '') ?: null, $src, $drug, $qty, trim((string)($_POST['drug_condition'] ?? '')) ?: null, $why]);
        audit('CREATE', FAR_MOD, "Retur obat $no dicatat");
        flash('success', "Retur $no dicatat, menunggu verifikasi.");
        redirect(ui_url('retur'));
    }
    if ($act === 'return_verify') {
        $rt = q_one("SELECT * FROM drug_returns WHERE id=? AND status='menunggu'", [(int)$_POST['rt']]);
        if ($rt) {
            $ok = ($_POST['decision'] ?? '') === 'diterima';
            ui_tx(function () use ($rt, $ok, $me) {
                q('UPDATE drug_returns SET status=?, verified_by=? WHERE id=?', [$ok ? 'diterima' : 'ditolak', $me, $rt['id']]);
                if ($ok) far_move((int)$rt['drug_id'], 'retur', (int)$rt['qty'], $rt['return_no'], 'Retur diterima', $me);
            });
            audit($ok ? 'VERIFY' : 'REJECT', FAR_MOD, 'Retur ' . $rt['return_no'] . ($ok ? ' diterima, stok bertambah' : ' ditolak'));
            flash($ok ? 'success' : 'warning', $ok ? 'Retur diterima, stok obat bertambah.' : 'Retur ditolak (obat tidak layak kembali ke stok).');
        }
        redirect(ui_url('retur'));
    }
    redirect($back);
}

/* ------------------------------ DATA ------------------------------ */
$drugs = q_all('SELECT * FROM drugs ORDER BY name');
$rxList = q_all("SELECT r.*, p.name patient, p.mrn, p.allergies, d.name doctor, v.visit_type, u.full_name verifier,
                        (SELECT COUNT(*) FROM prescription_items i WHERE i.prescription_id=r.id) n_item
                 FROM prescriptions r JOIN patients p ON p.id=r.patient_id LEFT JOIN doctors d ON d.id=r.doctor_id
                 LEFT JOIN visits v ON v.id=r.visit_id LEFT JOIN users u ON u.id=r.verified_by ORDER BY r.created_at DESC");

$rx = null;
if ($id && in_array($key, ['resep', 'dispensing'], true)) {
    foreach ($rxList as $r) if ((int)$r['id'] === $id) $rx = $r;
    if ($rx) {
        $patient = ui_patient_row((int)$rx['patient_id']);
        $items = q_all('SELECT pi.*, d.code, d.name, d.form, d.unit, d.stock, d.price, d.expiry_date FROM prescription_items pi JOIN drugs d ON d.id=pi.drug_id WHERE pi.prescription_id=?', [$id]);
    }
}

$titles = [
    'resep'      => ['Daftar Resep', 'Resep elektronik dari dokter (CPOE) — telaah administratif, farmasetik, dan klinis.'],
    'dispensing' => ['Dispensing Resep', 'Penyiapan, pemeriksaan akhir, dan penyerahan obat kepada pasien.'],
    'stok'       => ['Stok Obat', 'Persediaan obat di depo farmasi, penerimaan, dan penyesuaian stok.'],
    'retur'      => ['Retur Obat', 'Pengembalian obat dari pasien / unit dan verifikasinya.'],
    'laporan'    => ['Laporan Farmasi', 'Ringkasan pelayanan resep dan pergerakan stok obat.'],
];

layout_start($PAGE['title'], $key, $PAGE['section']);
?>
<div class="content">
  <div class="page-head">
    <div><h2><?= e($titles[$key][0]) ?></h2><p><?= e($titles[$key][1]) ?></p></div>
    <div class="page-actions">
      <?php if ($rx): ?><a class="btn" href="<?= e(ui_url($key)) ?>">← Kembali</a><?php endif; ?>
      <?php if ($key === 'stok'): ?><button class="btn btn-primary" data-modal="mAdjust"><?= icon('plus', 16) ?> Penerimaan / Penyesuaian</button><?php endif; ?>
      <?php if ($key === 'retur'): ?><button class="btn btn-primary" data-modal="mReturn"><?= icon('plus', 16) ?> Catat Retur</button><?php endif; ?>
    </div>
  </div>
  <?php flashes(); ?>

<?php /* ===================== RESEP & DISPENSING ===================== */
if (in_array($key, ['resep', 'dispensing'], true)):
  if (!$rx):
    $view = $key === 'dispensing' ? array_filter($rxList, fn($r) => in_array($r['status'], ['diverifikasi', 'disiapkan'], true)) : $rxList;
    $fs = $_GET['status'] ?? '';
    if ($fs !== '') $view = array_filter($view, fn($r) => $r['status'] === $fs);
    $c = fn($s) => count(array_filter($rxList, fn($r) => $r['status'] === $s)); ?>
  <section class="kpi-grid">
    <?= ui_kpi('Resep Baru', $c('baru'), 'file', 'warning', 'perlu telaah') ?>
    <?= ui_kpi('Terverifikasi', $c('diverifikasi'), 'check', 'info', 'siap disiapkan') ?>
    <?= ui_kpi('Disiapkan', $c('disiapkan'), 'pill', 'purple', 'menunggu penyerahan') ?>
    <?= ui_kpi('Diserahkan', $c('diserahkan'), 'heart', '', 'selesai') ?>
  </section>
  <div class="card">
    <form class="toolbar" method="get"><input type="hidden" name="page" value="<?= e($key) ?>">
      <b style="color:var(--heading)"><?= $key === 'dispensing' ? 'Antrian Dispensing' : 'Semua Resep' ?></b><span class="badge"><?= count($view) ?> resep</span><span class="grow"></span>
      <?php if ($key === 'resep'): ?><select class="input" name="status" onchange="this.form.submit()"><option value="">Semua status</option><?= ui_opt_map(['baru' => 'Baru', 'diverifikasi' => 'Diverifikasi', 'disiapkan' => 'Disiapkan', 'diserahkan' => 'Diserahkan', 'ditolak' => 'Ditolak'], $fs) ?></select><?php endif; ?>
    </form>
    <div class="table-wrap"><table class="table">
      <thead><tr><th>No. Resep</th><th>Pasien</th><th>Dokter</th><th>Layanan</th><th>Item</th><th>Waktu</th><th>Status</th><th style="text-align:right">Aksi</th></tr></thead>
      <tbody>
      <?php if (!$view): ?><tr><td colspan="8" class="empty">Tidak ada resep.</td></tr><?php endif; ?>
      <?php foreach ($view as $r): $tgt = in_array($r['status'], ['diverifikasi', 'disiapkan'], true) ? 'dispensing' : 'resep'; ?>
        <tr><td class="mono strong"><?= e($r['prescription_no']) ?></td>
          <td><b><?= e($r['patient']) ?></b><br><small class="mono muted"><?= e($r['mrn']) ?></small></td>
          <td><?= e($r['doctor'] ?? '-') ?></td><td><?= e(ui_label($r['visit_type'] ?? '-')) ?></td><td class="mono"><?= (int)$r['n_item'] ?></td>
          <td class="mono"><?= tgl($r['created_at'], true) ?></td><td><?= status_badge($r['status']) ?></td>
          <td><div class="actions"><a class="btn btn-sm <?= in_array($r['status'], ['baru', 'diverifikasi', 'disiapkan'], true) ? 'btn-primary' : '' ?>" href="<?= e(ui_url($key === 'resep' && $tgt === 'dispensing' ? 'dispensing' : $key, ['id' => $r['id']])) ?>"><?= in_array($r['status'], ['diserahkan', 'ditolak'], true) ? 'Lihat' : 'Proses' ?></a></div></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  </div>

<?php else: $st = $FAR_STAGE[$rx['status']]; ?>
  <div class="card stepper-card"><?= ui_stepper(FAR_STEPS, $st) ?></div>
  <?php if ($rx['status'] === 'ditolak'): ?><div class="alert danger"><?= icon('x-circle', 18) ?><div><b>Resep ditolak.</b> Alasan: <?= e($rx['reject_reason'] ?? '-') ?></div></div><?php endif; ?>
  <?= ui_patient($patient) ?>
  <section class="grid grid-2 mb">
    <div class="card">
      <div class="card-head"><div><h3>Resep <span class="mono"><?= e($rx['prescription_no']) ?></span></h3><p><?= e($rx['doctor'] ?? '-') ?> · <?= tgl($rx['created_at'], true) ?></p></div><?= status_badge($rx['status']) ?></div>
      <div class="table-wrap"><table class="table">
        <thead><tr><th>Obat</th><th>Jumlah</th><th>Aturan Pakai</th><th>Stok</th><th>Subtotal</th></tr></thead>
        <tbody><?php $tot = 0; foreach ($items as $it): $tot += $it['qty'] * $it['price']; $low = $it['stock'] < $it['qty'] && in_array($rx['status'], ['baru', 'diverifikasi'], true); ?>
          <tr><td><b><?= e($it['name']) ?></b><br><small class="mono muted"><?= e($it['code']) ?> · <?= e($it['form']) ?></small></td>
            <td class="mono"><?= (int)$it['qty'] ?> <?= e($it['unit']) ?></td><td><?= e($it['dosage']) ?><br><small class="muted"><?= e($it['instructions'] ?? '') ?></small></td>
            <td><?= $low ? '<span class="badge danger">' . (int)$it['stock'] . ' (kurang)</span>' : '<span class="mono">' . (int)$it['stock'] . '</span>' ?></td>
            <td class="mono"><?= rupiah($it['qty'] * $it['price']) ?></td></tr>
        <?php endforeach; ?></tbody>
        <tfoot><tr><td colspan="4" style="text-align:right"><b>Total</b></td><td class="mono strong"><?= rupiah($tot) ?></td></tr></tfoot>
      </table></div>
      <?php if ($rx['notes']): ?><div class="card-body"><small class="muted">Catatan farmasi:</small><br><?= e($rx['notes']) ?></div><?php endif; ?>
    </div>

    <div class="card">
    <?php if ($rx['status'] === 'baru'): ?>
      <div class="card-head"><div><h3>Langkah 1 — Telaah Resep</h3><p>Pastikan kesesuaian sebelum disiapkan.</p></div></div>
      <form method="post" class="card-body"><?= csrf_field() ?><input type="hidden" name="act" value="verify"><input type="hidden" name="rx" value="<?= $id ?>">
        <label class="check"><input type="checkbox" name="check[]" value="adm" required> Administratif (identitas pasien, dokter, tanggal)</label>
        <label class="check"><input type="checkbox" name="check[]" value="far" required> Farmasetik (bentuk, kekuatan, dosis, stabilitas)</label>
        <label class="check" style="margin-bottom:14px"><input type="checkbox" name="check[]" value="kli" required> Klinis (alergi, interaksi, duplikasi)</label>
        <div class="field"><label>Catatan Telaah</label><input name="notes" placeholder="Opsional"></div>
        <button class="btn btn-primary"><?= icon('check', 16) ?> Resep Sesuai — Verifikasi</button>
      </form>
      <form method="post" class="card-foot" style="justify-content:flex-start" data-confirm="Resep akan ditolak dan dikembalikan ke dokter." data-confirm-title="Tolak Resep" data-confirm-ok="Tolak">
        <?= csrf_field() ?><input type="hidden" name="act" value="reject"><input type="hidden" name="rx" value="<?= $id ?>">
        <input class="input" name="reject_reason" placeholder="Alasan penolakan (mis. interaksi obat)" required style="flex:1;height:38px"><button class="btn btn-danger">Tolak</button>
      </form>
    <?php elseif ($rx['status'] === 'diverifikasi'): ?>
      <div class="card-head"><div><h3>Langkah 2 — Penyiapan Obat</h3><p>Stok obat otomatis berkurang saat disiapkan.</p></div></div>
      <div class="card-body"><?= ui_details(['Diverifikasi oleh' => e($rx['verifier'] ?? '-'), 'Jumlah item' => (int)$rx['n_item']]) ?></div>
      <form method="post" class="card-foot"><?= csrf_field() ?><input type="hidden" name="act" value="prepare"><input type="hidden" name="rx" value="<?= $id ?>"><button class="btn btn-primary"><?= icon('pill', 16) ?> Obat Sudah Disiapkan</button></form>
    <?php elseif ($rx['status'] === 'disiapkan'): ?>
      <div class="card-head"><div><h3>Langkah 3–4 — Pemeriksaan Akhir & Penyerahan</h3><p>Cek prinsip 5 benar, lalu serahkan dengan edukasi.</p></div></div>
      <form method="post" class="card-body"><?= csrf_field() ?><input type="hidden" name="act" value="handover"><input type="hidden" name="rx" value="<?= $id ?>">
        <div class="checks"><?php foreach (['Benar pasien', 'Benar obat', 'Benar dosis', 'Benar cara pemberian', 'Benar waktu'] as $c): ?><label class="check"><input type="checkbox" name="check[]" value="<?= e($c) ?>" required> <?= e($c) ?></label><?php endforeach; ?></div>
        <div class="field"><label>Nama Penerima</label><input name="receiver" required value="<?= e($patient['name']) ?>"></div>
        <div class="field"><label>Edukasi yang Diberikan</label><textarea name="education" rows="2" placeholder="Cara minum, efek samping, penyimpanan"></textarea></div>
        <button class="btn btn-primary"><?= icon('check', 16) ?> Serahkan Obat</button>
      </form>
    <?php else: ?>
      <div class="card-head"><div><h3>Riwayat</h3></div></div>
      <?= ui_details(['Diverifikasi oleh' => e($rx['verifier'] ?? '-'), 'Diserahkan' => '<span class="mono">' . tgl($rx['handed_at'], true) . '</span>', 'Catatan' => e($rx['notes'] ?? '-')]) ?>
    <?php endif; ?>
    </div>
  </section>
<?php endif; endif; ?>

<?php /* ===================== STOK ===================== */
if ($key === 'stok'):
  $soon = date('Y-m-d', strtotime('+90 days'));
  $fs = $_GET['f'] ?? '';
  $view = array_filter($drugs, function ($d) use ($fs, $soon) {
      return $fs === '' || ($fs === 'menipis' && $d['stock'] <= $d['min_stock']) || ($fs === 'ed' && $d['expiry_date'] && $d['expiry_date'] <= $soon);
  });
  $moves = q_all('SELECT m.*, d.name, u.full_name FROM drug_stock_movements m JOIN drugs d ON d.id=m.drug_id LEFT JOIN users u ON u.id=m.created_by ORDER BY m.created_at DESC LIMIT 15'); ?>
  <section class="kpi-grid">
    <?= ui_kpi('Jenis Obat', count($drugs), 'pill') ?>
    <?= ui_kpi('Stok Menipis', count(array_filter($drugs, fn($d) => $d['stock'] <= $d['min_stock'])), 'alert', 'warning', '≤ stok minimum') ?>
    <?= ui_kpi('Kedaluwarsa ≤ 90 hari', count(array_filter($drugs, fn($d) => $d['expiry_date'] && $d['expiry_date'] <= $soon)), 'clock', 'danger') ?>
    <?= ui_kpi('Nilai Persediaan', '<span style="font-size:20px">' . rupiah(array_sum(array_map(fn($d) => $d['stock'] * $d['price'], $drugs))) . '</span>', 'wallet', 'info') ?>
  </section>
  <div class="card mb">
    <form class="toolbar" method="get"><input type="hidden" name="page" value="stok"><b style="color:var(--heading)">Persediaan Obat</b><span class="grow"></span>
      <select class="input" name="f" onchange="this.form.submit()"><?= ui_opt_map(['' => 'Semua obat', 'menipis' => 'Stok menipis', 'ed' => 'Kedaluwarsa ≤ 90 hari'], $fs) ?></select></form>
    <div class="table-wrap"><table class="table">
      <thead><tr><th>Kode</th><th>Nama Obat</th><th>Bentuk</th><th>Kategori</th><th>Stok</th><th>Min.</th><th>Kedaluwarsa</th><th>Harga</th><th>Status</th></tr></thead>
      <tbody><?php foreach ($view as $d): $stt = $d['stock'] <= 0 ? ['danger', 'Habis'] : ($d['stock'] <= $d['min_stock'] ? ['warning', 'Menipis'] : (($d['expiry_date'] && $d['expiry_date'] <= $soon) ? ['warning', 'Segera ED'] : ['success', 'Aman'])); ?>
        <tr><td class="mono"><?= e($d['code']) ?></td><td class="strong"><?= e($d['name']) ?></td><td><?= e($d['form']) ?></td><td><?= e($d['category']) ?></td>
          <td class="mono strong"><?= angka($d['stock']) ?> <small class="muted"><?= e($d['unit']) ?></small></td><td class="mono"><?= angka($d['min_stock']) ?></td>
          <td class="mono"><?= tgl($d['expiry_date']) ?></td><td class="mono"><?= rupiah($d['price']) ?></td><td><span class="badge <?= $stt[0] ?>"><?= $stt[1] ?></span></td></tr>
      <?php endforeach; ?></tbody>
    </table></div>
  </div>
  <div class="card">
    <div class="card-head"><div><h3>Riwayat Pergerakan Stok</h3><p>15 transaksi terakhir</p></div></div>
    <div class="table-wrap"><table class="table">
      <thead><tr><th>Waktu</th><th>Obat</th><th>Jenis</th><th>Jumlah</th><th>Referensi</th><th>Keterangan</th><th>Petugas</th></tr></thead>
      <tbody><?php foreach ($moves as $m): ?><tr><td class="mono"><?= tgl($m['created_at'], true) ?></td><td><?= e($m['name']) ?></td><td><?= status_badge($m['movement_type']) ?></td>
        <td class="mono strong" style="color:<?= $m['qty'] < 0 ? 'var(--danger)' : 'var(--success)' ?>"><?= $m['qty'] > 0 ? '+' : '' ?><?= (int)$m['qty'] ?></td><td class="mono"><?= e($m['reference']) ?></td><td><?= e($m['notes']) ?></td><td><?= e($m['full_name'] ?? '-') ?></td></tr><?php endforeach; ?></tbody>
    </table></div>
  </div>
  <div class="modal" id="mAdjust"><div class="modal-box">
    <div class="card-head"><h3>Penerimaan / Penyesuaian Stok</h3><button class="icon-btn" data-close type="button">✕</button></div>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="act" value="adjust">
      <div class="form-grid">
        <div class="field full"><label>Obat</label><select name="drug_id" required><?= ui_options($drugs, 'id', fn($d) => $d['name'] . ' (stok ' . $d['stock'] . ')', null, '— pilih obat —') ?></select></div>
        <div class="field"><label>Jenis</label><select name="type"><option value="masuk">Penerimaan (masuk)</option><option value="penyesuaian">Penyesuaian (+/−)</option></select></div>
        <div class="field"><label>Jumlah</label><input type="number" name="qty" required placeholder="Negatif untuk pengurangan"></div>
        <div class="field"><label>No. Referensi</label><input name="reference" placeholder="No. faktur / BA"></div>
        <div class="field"><label>Tgl. Kedaluwarsa Baru</label><input type="date" name="expiry_date"></div>
        <div class="field full"><label>Keterangan</label><input name="notes" required placeholder="Contoh: pengadaan rutin / obat rusak"></div>
      </div>
      <div class="modal-foot"><button type="button" class="btn" data-close>Batal</button><button class="btn btn-primary">Simpan</button></div>
    </form>
  </div></div>
<?php endif; ?>

<?php /* ===================== RETUR ===================== */
if ($key === 'retur'):
  $rets = q_all('SELECT r.*, d.name drug, d.unit, p.prescription_no, u.full_name verifier FROM drug_returns r JOIN drugs d ON d.id=r.drug_id
                 LEFT JOIN prescriptions p ON p.id=r.prescription_id LEFT JOIN users u ON u.id=r.verified_by ORDER BY r.returned_at DESC');
  $rxDone = array_filter($rxList, fn($r) => $r['status'] === 'diserahkan'); ?>
  <div class="card">
    <div class="card-head"><div><h3>Daftar Retur Obat</h3><p>Retur diterima menambah stok; obat rusak/terbuka ditolak.</p></div><span class="badge"><?= count($rets) ?> retur</span></div>
    <div class="table-wrap"><table class="table">
      <thead><tr><th>No. Retur</th><th>Tanggal</th><th>Asal</th><th>Obat</th><th>Jumlah</th><th>Kondisi</th><th>Alasan</th><th>Status</th><th style="text-align:right">Verifikasi</th></tr></thead>
      <tbody><?php if (!$rets): ?><tr><td colspan="9" class="empty">Belum ada retur.</td></tr><?php endif; foreach ($rets as $r): ?>
        <tr><td class="mono strong"><?= e($r['return_no']) ?></td><td class="mono"><?= tgl($r['returned_at'], true) ?></td>
          <td><?= e($r['source']) ?><?php if ($r['prescription_no']): ?><br><small class="mono muted"><?= e($r['prescription_no']) ?></small><?php endif; ?></td>
          <td><?= e($r['drug']) ?></td><td class="mono"><?= (int)$r['qty'] ?> <?= e($r['unit']) ?></td><td><?= e($r['drug_condition'] ?? '-') ?></td><td><?= e($r['reason']) ?></td>
          <td><?= status_badge($r['status']) ?></td>
          <td><?php if ($r['status'] === 'menunggu'): ?><form method="post" class="actions"><?= csrf_field() ?><input type="hidden" name="act" value="return_verify"><input type="hidden" name="rt" value="<?= $r['id'] ?>">
              <button class="btn btn-sm btn-primary" name="decision" value="diterima">Terima</button><button class="btn btn-sm btn-danger" name="decision" value="ditolak">Tolak</button></form>
            <?php else: ?><small class="muted"><?= e($r['verifier'] ?? '-') ?></small><?php endif; ?></td></tr>
      <?php endforeach; ?></tbody>
    </table></div>
  </div>
  <div class="modal" id="mReturn"><div class="modal-box">
    <div class="card-head"><h3>Catat Retur Obat</h3><button class="icon-btn" data-close type="button">✕</button></div>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="act" value="return_new">
      <div class="form-grid">
        <div class="field"><label>Asal Retur</label><input name="source" required placeholder="Nama pasien / unit"></div>
        <div class="field"><label>No. Resep (opsional)</label><select name="prescription_id"><?= ui_options($rxDone, 'id', fn($r) => $r['prescription_no'] . ' — ' . $r['patient'], null, '— tanpa resep —') ?></select></div>
        <div class="field full"><label>Obat</label><select name="drug_id" required><?= ui_options($drugs, 'id', 'name', null, '— pilih obat —') ?></select></div>
        <div class="field"><label>Jumlah</label><input type="number" name="qty" min="1" required></div>
        <div class="field"><label>Kondisi Obat</label><select name="drug_condition"><?= ui_opt_map(array_combine($k = ['Kemasan utuh, belum dibuka', 'Kemasan terbuka', 'Rusak / kedaluwarsa'], $k)) ?></select></div>
        <div class="field full"><label>Alasan Retur</label><input name="reason" required placeholder="Contoh: terapi dihentikan DPJP"></div>
      </div>
      <div class="modal-foot"><button type="button" class="btn" data-close>Batal</button><button class="btn btn-primary">Simpan</button></div>
    </form>
  </div></div>
<?php endif; ?>

<?php /* ===================== LAPORAN ===================== */
if ($key === 'laporan'):
  $from = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['from'] ?? '') ? $_GET['from'] : date('Y-m-01');
  $to   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['to'] ?? '') ? $_GET['to'] : date('Y-m-d');
  $P = [$from, $to];
  $sum = q_one("SELECT COUNT(*) total, SUM(status='diserahkan') done, SUM(status='ditolak') rej,
                       AVG(CASE WHEN handed_at IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, created_at, handed_at) END) wait
                FROM prescriptions WHERE DATE(created_at) BETWEEN ? AND ?", $P);
  $top = q_all("SELECT d.name, SUM(-m.qty) qty, SUM(-m.qty*d.price) val FROM drug_stock_movements m JOIN drugs d ON d.id=m.drug_id
                WHERE m.movement_type='keluar' AND DATE(m.created_at) BETWEEN ? AND ? GROUP BY d.id ORDER BY qty DESC LIMIT 10", $P);
  $mv  = q_all("SELECT movement_type, COUNT(*) n, SUM(qty) qty FROM drug_stock_movements WHERE DATE(created_at) BETWEEN ? AND ? GROUP BY movement_type", $P);
  audit('VIEW', FAR_MOD, "Laporan farmasi $from s/d $to"); ?>
  <form class="card toolbar mb" method="get" style="border-radius:var(--radius)"><input type="hidden" name="page" value="laporan">
    <b style="color:var(--heading)">Periode</b><input class="input" type="date" name="from" value="<?= e($from) ?>"><input class="input" type="date" name="to" value="<?= e($to) ?>">
    <button class="btn btn-primary">Tampilkan</button><span class="grow"></span><button type="button" class="btn" onclick="print()"><?= icon('printer', 16) ?> Cetak</button></form>
  <section class="kpi-grid">
    <?= ui_kpi('Total Resep', angka($sum['total']), 'file') ?>
    <?= ui_kpi('Diserahkan', angka($sum['done']), 'check', 'info') ?>
    <?= ui_kpi('Ditolak', angka($sum['rej']), 'x-circle', 'danger') ?>
    <?= ui_kpi('Rata-rata Waktu Layanan', $sum['wait'] !== null ? angka($sum['wait']) . ' mnt' : '-', 'clock', 'warning', 'resep masuk → diserahkan') ?>
  </section>
  <section class="grid grid-2e">
    <div class="card"><div class="card-head"><div><h3>10 Obat Terbanyak Keluar</h3><p><?= tgl($from) ?> – <?= tgl($to) ?></p></div></div>
      <div class="table-wrap"><table class="table"><thead><tr><th>Obat</th><th>Jumlah</th><th>Nilai</th></tr></thead><tbody>
      <?php if (!$top): ?><tr><td colspan="3" class="empty">Belum ada pengeluaran obat.</td></tr><?php endif; foreach ($top as $t): ?><tr><td><?= e($t['name']) ?></td><td class="mono"><?= angka($t['qty']) ?></td><td class="mono"><?= rupiah($t['val']) ?></td></tr><?php endforeach; ?>
      </tbody></table></div></div>
    <div class="card"><div class="card-head"><div><h3>Rekap Pergerakan Stok</h3><p>Berdasarkan jenis transaksi</p></div></div>
      <div class="table-wrap"><table class="table"><thead><tr><th>Jenis</th><th>Transaksi</th><th>Total Qty</th></tr></thead><tbody>
      <?php if (!$mv): ?><tr><td colspan="3" class="empty">Tidak ada transaksi.</td></tr><?php endif; foreach ($mv as $m): ?><tr><td><?= status_badge($m['movement_type']) ?></td><td class="mono"><?= (int)$m['n'] ?></td><td class="mono"><?= angka($m['qty']) ?></td></tr><?php endforeach; ?>
      </tbody></table></div></div>
  </section>
<?php endif; ?>
</div>
<?php layout_end();
