<?php
/*
 * MODUL 8 — LABORATORIUM (database)
 * Alur: Order Masuk (dari CPOE dokter) → Verifikasi Spesimen → Input Hasil → Validasi (hasil kritis wajib dikomunikasikan) → Hasil Final
 * Menu: orderlab (semua order), verifikasi, input, validasi, hasil
 */
const LAB_STEPS = ['Order Masuk', 'Verifikasi Spesimen', 'Input Hasil', 'Validasi Hasil', 'Hasil Final'];
const LAB_MOD   = 'Laboratory';

/** Angka dari format Indonesia: "85.000" → 85000, "14,2" → 14.2 */
function lab_num(?string $s): ?float
{
    $s = trim((string)$s);
    if ($s === '') return null;
    if (preg_match('/^\d{1,3}(\.\d{3})+(,\d+)?$/', $s)) $s = str_replace('.', '', $s);
    $s = str_replace(',', '.', $s);
    return is_numeric($s) ? (float)$s : null;
}

/** Hitung flag dari nilai rujukan. Kritis bila jauh di luar rentang (≤50% batas bawah / ≥200% batas atas). */
function lab_flag(?string $value, ?string $range): ?string
{
    if ($value === null || trim($value) === '') return null;
    $range = trim((string)$range);
    $v = lab_num($value);
    if ($v === null || $range === '') {
        $neg = ['negatif', 'normal', 'non reaktif', 'nonreaktif'];
        return in_array(strtolower(trim($value)), $neg, true) || strcasecmp(trim($value), $range) === 0 ? 'normal' : 'tinggi';
    }
    if (preg_match('/^([\d.,]+)\s*-\s*([\d.,]+)$/', $range, $m)) {
        [$lo, $hi] = [lab_num($m[1]), lab_num($m[2])];
        if ($v <= $lo * 0.5 || $v >= $hi * 2) return 'kritis';
        return $v < $lo ? 'rendah' : ($v > $hi ? 'tinggi' : 'normal');
    }
    if (preg_match('/^<\s*([\d.,]+)$/', $range, $m)) {
        $hi = lab_num($m[1]);
        return $v >= $hi * 2 ? 'kritis' : ($v >= $hi ? 'tinggi' : 'normal');
    }
    return 'normal';
}

/** Tahap order: 1 = perlu verifikasi spesimen, 2 = input hasil, 3 = validasi, 5 = final, -1 = batal */
function lab_stage(array $o): int
{
    if ($o['status'] === 'dibatalkan') return -1;
    if (($o['spec_status'] ?? null) !== 'diterima') return 1;
    if ((int)$o['n_empty'] > 0) return 2;
    if ((int)$o['n_wait'] > 0) return 3;
    return 5;
}

function lab_orders(string $where = '1', array $p = []): array
{
    return q_all("SELECT o.*, p.name patient, p.mrn, p.gender, p.birth_date, d.name doctor, v.visit_type,
                         s.status spec_status, s.specimen_type, s.notes spec_notes,
                         (SELECT COUNT(*) FROM lab_results r WHERE r.order_id=o.id) n_test,
                         (SELECT COUNT(*) FROM lab_results r WHERE r.order_id=o.id AND (r.result_value IS NULL OR r.result_value='' OR r.status IN ('menunggu_sampel','diproses'))) n_empty,
                         (SELECT COUNT(*) FROM lab_results r WHERE r.order_id=o.id AND r.status='menunggu_validasi') n_wait,
                         (SELECT COUNT(*) FROM lab_results r WHERE r.order_id=o.id AND r.flag='kritis') n_crit,
                         (SELECT GROUP_CONCAT(t.name SEPARATOR ', ') FROM lab_results r JOIN lab_tests t ON t.id=r.lab_test_id WHERE r.order_id=o.id) tests
                  FROM orders o JOIN patients p ON p.id=o.patient_id LEFT JOIN doctors d ON d.id=o.doctor_id
                  LEFT JOIN visits v ON v.id=o.visit_id LEFT JOIN lab_specimens s ON s.order_id=o.id
                  WHERE o.order_type='laboratorium' AND $where
                  ORDER BY (o.priority='cito') DESC, o.ordered_at DESC", $p);
}

$key  = $PAGE['key'];
$id   = (int)($_GET['id'] ?? 0);
$me   = (int)$user['id'];
$back = ui_url($key, $id ? ['id' => $id] : []);

/* ------------------------------ AKSI ------------------------------ */
if ($act = ui_post_action()) {
    $oid = (int)($_POST['order_id'] ?? 0);
    $o   = q_one("SELECT * FROM orders WHERE id=? AND order_type='laboratorium'", [$oid]);
    if (!$o) { flash('danger', 'Order tidak ditemukan.'); redirect(ui_url($key)); }

    if ($act === 'specimen') {
        $checks = array_intersect((array)($_POST['check'] ?? []), ['Identitas sesuai', 'Jenis spesimen sesuai', 'Kondisi baik', 'Volume cukup']);
        $accept = ($_POST['decision'] ?? '') === 'diterima';
        $notes  = trim((string)($_POST['notes'] ?? ''));
        $type   = trim((string)($_POST['specimen_type'] ?? ''));
        if ($type === '') { flash('danger', 'Jenis spesimen wajib diisi.'); redirect($back); }
        if ($accept && count($checks) < 4) { flash('danger', 'Spesimen hanya dapat diterima bila seluruh checklist kelayakan terpenuhi.'); redirect($back); }
        if (!$accept && $notes === '') { flash('danger', 'Alasan penolakan spesimen wajib diisi.'); redirect($back); }
        ui_tx(function () use ($oid, $type, $checks, $accept, $notes, $me) {
            $bar = 'SPC-' . str_pad((string)$oid, 6, '0', STR_PAD_LEFT);
            q('INSERT INTO lab_specimens (order_id, specimen_type, barcode, received_at, checklist, status, notes, verified_by) VALUES (?,?,?,NOW(),?,?,?,?)
               ON DUPLICATE KEY UPDATE specimen_type=VALUES(specimen_type), received_at=NOW(), checklist=VALUES(checklist), status=VALUES(status), notes=VALUES(notes), verified_by=VALUES(verified_by)',
              [$oid, $type, $bar, implode(', ', $checks), $accept ? 'diterima' : 'ditolak', $notes ?: null, $me]);
            if ($accept) {
                q("UPDATE lab_results SET status='diproses' WHERE order_id=? AND status='menunggu_sampel'", [$oid]);
                q("UPDATE orders SET status='diproses' WHERE id=? AND status='baru'", [$oid]);
            }
        });
        audit($accept ? 'VERIFY' : 'REJECT', LAB_MOD, ($accept ? 'Spesimen diterima ' : 'Spesimen ditolak ') . $o['order_no'] . ($notes ? " ($notes)" : ''));
        flash($accept ? 'success' : 'warning', $accept ? 'Spesimen diterima. Lanjutkan ke input hasil.' : 'Spesimen ditolak — minta pengambilan sampel ulang.');
        redirect($accept ? ui_url('input', ['id' => $oid]) : $back);
    }

    if ($act === 'results') {
        $vals = (array)($_POST['value'] ?? []);
        $rows = q_all('SELECT r.id, t.normal_range FROM lab_results r JOIN lab_tests t ON t.id=r.lab_test_id WHERE r.order_id=?', [$oid]);
        foreach ($rows as $r) if (trim((string)($vals[$r['id']] ?? '')) === '') { flash('danger', 'Semua parameter wajib diisi sebelum dikirim ke validasi.'); redirect($back); }
        ui_tx(function () use ($rows, $vals, $oid) {
            foreach ($rows as $r) {
                $v = trim((string)$vals[$r['id']]);
                $flag = $_POST['flag'][$r['id']] ?? 'auto';
                if (!in_array($flag, ['normal', 'tinggi', 'rendah', 'kritis'], true)) $flag = lab_flag($v, $r['normal_range']);
                q("UPDATE lab_results SET result_value=?, flag=?, status='menunggu_validasi' WHERE id=?", [$v, $flag, $r['id']]);
            }
            q("UPDATE orders SET status='diproses' WHERE id=?", [$oid]);
        });
        audit('INPUT', LAB_MOD, 'Input hasil ' . $o['order_no']);
        flash('success', 'Hasil tersimpan dan menunggu validasi.');
        redirect(ui_url('validasi', ['id' => $oid]));
    }

    if ($act === 'validate') {
        $crit = (int)q_val("SELECT COUNT(*) FROM lab_results WHERE order_id=? AND flag='kritis'", [$oid]);
        $hasComm = (int)q_val('SELECT COUNT(*) FROM lab_communications WHERE order_id=?', [$oid]);
        $rcp = trim((string)($_POST['recipient'] ?? ''));
        if ($crit && !$hasComm && $rcp === '') { flash('danger', 'Terdapat nilai kritis: isi data komunikasi ke dokter/DPJP sebelum validasi.'); redirect($back); }
        ui_tx(function () use ($oid, $me, $rcp, $crit) {
            if ($crit && $rcp !== '') {
                $at = str_replace('T', ' ', (string)($_POST['communicated_at'] ?? '')) ?: date('Y-m-d H:i:s');
                q('INSERT INTO lab_communications (order_id, recipient, communicated_at, note, created_by) VALUES (?,?,?,?,?)',
                  [$oid, $rcp, $at, trim((string)($_POST['comm_note'] ?? '')) ?: null, $me]);
            }
            q("UPDATE lab_results SET status='tervalidasi', validated_by=?, validated_at=NOW() WHERE order_id=?", [$me, $oid]);
            q("UPDATE orders SET status='selesai' WHERE id=?", [$oid]);
        });
        audit('VALIDATE', LAB_MOD, 'Validasi hasil ' . $o['order_no'] . ($crit ? ' (nilai kritis dikomunikasikan)' : ''));
        flash('success', 'Hasil tervalidasi dan dapat dilihat dokter pengirim.');
        redirect(ui_url('hasil', ['id' => $oid]));
    }

    if ($act === 'revise') {
        q("UPDATE lab_results SET status='diproses' WHERE order_id=? AND status='menunggu_validasi'", [$oid]);
        audit('UPDATE', LAB_MOD, 'Hasil dikembalikan ke input ' . $o['order_no']);
        flash('info', 'Hasil dikembalikan ke tahap input untuk dikoreksi.');
        redirect(ui_url('input', ['id' => $oid]));
    }
    redirect($back);
}

/* ------------------------------ DATA ------------------------------ */
$titles = [
    'orderlab'   => ['Order Laboratorium', 'Semua permintaan pemeriksaan laboratorium dari dokter (CPOE).'],
    'verifikasi' => ['Verifikasi Spesimen', 'Periksa kelayakan spesimen sebelum dianalisis.'],
    'input'      => ['Input Hasil Pemeriksaan', 'Masukkan nilai hasil; flag dihitung otomatis dari nilai rujukan.'],
    'validasi'   => ['Validasi Hasil', 'Tinjau hasil, komunikasikan nilai kritis, lalu validasi.'],
    'hasil'      => ['Hasil Laboratorium', 'Hasil yang sudah tervalidasi dan siap dicetak.'],
];
$stageOf = ['verifikasi' => 1, 'input' => 2, 'validasi' => 3, 'hasil' => 5];

$all = lab_orders();
foreach ($all as &$r) $r['stage'] = lab_stage($r);
unset($r);
$list = isset($stageOf[$key]) ? array_values(array_filter($all, fn($r) => $r['stage'] === $stageOf[$key])) : $all;

$fStatus = $_GET['status'] ?? '';
$search  = trim((string)($_GET['q'] ?? ''));
if ($key === 'orderlab' && $fStatus !== '') $list = array_values(array_filter($list, fn($r) => $r['status'] === $fStatus));
if ($search !== '') $list = array_values(array_filter($list, fn($r) => stripos($r['patient'] . ' ' . $r['mrn'] . ' ' . $r['order_no'], $search) !== false));

$order = null;
if ($id) {
    foreach ($all as $r) if ((int)$r['id'] === $id) $order = $r;
    if ($order) {
        $patient = ui_patient_row((int)$order['patient_id']);
        $results = q_all('SELECT r.*, t.code, t.name, t.unit, t.normal_range, t.category, u.full_name validator
                          FROM lab_results r JOIN lab_tests t ON t.id=r.lab_test_id LEFT JOIN users u ON u.id=r.validated_by
                          WHERE r.order_id=? ORDER BY t.category, t.name', [$id]);
        $spec  = q_one('SELECT s.*, u.full_name verifier FROM lab_specimens s LEFT JOIN users u ON u.id=s.verified_by WHERE s.order_id=?', [$id]);
        $comms = q_all('SELECT c.*, u.full_name by_name FROM lab_communications c LEFT JOIN users u ON u.id=c.created_by WHERE c.order_id=? ORDER BY c.communicated_at', [$id]);
    }
}

$count = fn(int $s) => count(array_filter($all, fn($r) => $r['stage'] === $s));
$specTypes = ['Darah vena (serum)', 'Darah EDTA', 'Darah kapiler', 'Urin sewaktu', 'Feses', 'Swab'];

layout_start($PAGE['title'], $key, $PAGE['section']);
?>
<div class="content">
  <div class="page-head">
    <div><h2><?= e($titles[$key][0]) ?></h2><p><?= e($titles[$key][1]) ?></p></div>
    <?php if ($order): ?><div class="page-actions"><a class="btn" href="<?= e(ui_url($key)) ?>">← Kembali ke daftar</a>
      <?php if ($order['stage'] === 5): ?><button class="btn btn-primary" onclick="print()"><?= icon('printer', 16) ?> Cetak Hasil</button><?php endif; ?></div><?php endif; ?>
  </div>
  <?php flashes(); ?>

<?php if (!$order): ?>
  <section class="kpi-grid">
    <?= ui_kpi('Menunggu Verifikasi Spesimen', $count(1), 'test', 'warning', 'order baru / sampel ditolak') ?>
    <?= ui_kpi('Menunggu Input Hasil', $count(2), 'edit', 'info', 'spesimen sudah diterima') ?>
    <?= ui_kpi('Menunggu Validasi', $count(3), 'check', 'purple', 'hasil sudah diinput') ?>
    <?= ui_kpi('Hasil Final', $count(5), 'file', '', 'tervalidasi') ?>
  </section>

  <div class="card">
    <form class="toolbar" method="get">
      <input type="hidden" name="page" value="<?= e($key) ?>">
      <b style="color:var(--heading)"><?= e($titles[$key][0]) ?></b><span class="badge"><?= count($list) ?> order</span>
      <span class="grow"></span>
      <?php if ($key === 'orderlab'): ?><select class="input" name="status" onchange="this.form.submit()"><option value="">Semua status</option><?= ui_opt_map(['baru' => 'Baru', 'diproses' => 'Diproses', 'selesai' => 'Selesai', 'dibatalkan' => 'Dibatalkan'], $fStatus) ?></select><?php endif; ?>
      <input class="input" name="q" value="<?= e($search) ?>" placeholder="Cari pasien / No. RM / No. order">
      <button class="btn"><?= icon('search', 15) ?> Cari</button>
    </form>
    <div class="table-wrap"><table class="table">
      <thead><tr><th>No. Order</th><th>Pasien</th><th>Pemeriksaan</th><th>Dokter</th><th>Prioritas</th><th>Waktu Order</th><th>Tahap</th><th style="text-align:right">Aksi</th></tr></thead>
      <tbody>
      <?php if (!$list): ?><tr><td colspan="8" class="empty">Tidak ada order pada tahap ini.</td></tr><?php endif; ?>
      <?php foreach ($list as $r):
          $target = ['1' => 'verifikasi', '2' => 'input', '3' => 'validasi', '5' => 'hasil'][(string)$r['stage']] ?? 'orderlab'; ?>
        <tr>
          <td class="mono strong"><?= e($r['order_no']) ?></td>
          <td><b><?= e($r['patient']) ?></b><br><small class="mono muted"><?= e($r['mrn']) ?></small></td>
          <td><?= e($r['tests'] ?: '-') ?><?php if ($r['n_crit']): ?> <span class="badge danger">Kritis</span><?php endif; ?></td>
          <td><?= e($r['doctor'] ?? '-') ?></td>
          <td><?= status_badge($r['priority'] === 'cito' ? 'cito' : 'normal') ?></td>
          <td class="mono"><?= tgl($r['ordered_at'], true) ?></td>
          <td><?php if ($r['stage'] === -1): ?><?= status_badge('dibatalkan') ?><?php else: ?><span class="badge <?= $r['stage'] === 5 ? 'success' : ($r['spec_status'] === 'ditolak' ? 'danger' : 'warning') ?>"><?= $r['spec_status'] === 'ditolak' && $r['stage'] === 1 ? 'Sampel Ditolak' : e(LAB_STEPS[min($r['stage'], 4)]) ?></span><?php endif; ?></td>
          <td><div class="actions"><?php if ($r['stage'] !== -1): ?><a class="btn btn-sm <?= $key === $target ? 'btn-primary' : '' ?>" href="<?= e(ui_url($key === 'orderlab' ? $target : $key, ['id' => $r['id']])) ?>"><?= $r['stage'] === 5 ? 'Lihat' : 'Proses' ?></a><?php endif; ?></div></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  </div>

<?php else: $stage = $order['stage']; ?>
  <div class="card stepper-card no-print"><?= ui_stepper(LAB_STEPS, $stage === -1 ? 0 : $stage) ?></div>
  <?= ui_patient($patient) ?>
  <div class="print-only" style="margin-bottom:12px"><b>RS Suka Sehat — Hasil Pemeriksaan Laboratorium</b></div>

  <section class="grid grid-2 mb">
    <div class="card">
      <div class="card-head"><div><h3>Detail Order <span class="mono"><?= e($order['order_no']) ?></span></h3><p>Dikirim melalui CPOE</p></div><?= status_badge($order['status']) ?></div>
      <?= ui_details([
          'Dokter Pengirim' => e($order['doctor'] ?? '-'),
          'Waktu Order'     => '<span class="mono">' . tgl($order['ordered_at'], true) . '</span>',
          'Prioritas'       => status_badge($order['priority'] === 'cito' ? 'cito' : 'normal'),
          'Jenis Layanan'   => e(ui_label($order['visit_type'] ?? '-')),
          'Catatan Klinis'  => e($order['notes'] ?? '-'),
          'Pemeriksaan'     => e($order['tests']),
      ]) ?>
    </div>
    <div class="card">
      <div class="card-head"><div><h3>Spesimen</h3><p>Status verifikasi sampel</p></div><?= $spec ? status_badge($spec['status']) : '<span class="badge warning">Belum diverifikasi</span>' ?></div>
      <?= $spec ? ui_details([
          'Jenis Spesimen' => e($spec['specimen_type']),
          'Barcode'        => '<span class="mono">' . e($spec['barcode']) . '</span>',
          'Diterima'       => '<span class="mono">' . tgl($spec['received_at'], true) . '</span>',
          'Petugas'        => e($spec['verifier'] ?? '-'),
          'Checklist'      => e($spec['checklist'] ?: '-'),
          'Catatan'        => e($spec['notes'] ?? '-'),
      ]) : '<div class="empty">Spesimen belum diterima laboratorium.</div>' ?>
    </div>
  </section>

  <?php if ($stage === 1): ?>
  <div class="card no-print">
    <div class="card-head"><div><h3>Langkah 2 — Verifikasi Spesimen</h3><p>Seluruh checklist wajib terpenuhi agar spesimen dapat diterima.</p></div></div>
    <form method="post" class="card-body" id="fSpec">
      <?= csrf_field() ?><input type="hidden" name="act" value="specimen"><input type="hidden" name="order_id" value="<?= $id ?>">
      <div class="form-grid">
        <div class="field"><label>Jenis Spesimen</label><select name="specimen_type" required><?= ui_opt_map(array_combine($specTypes, $specTypes), $spec['specimen_type'] ?? '') ?></select></div>
        <div class="field"><label>Keputusan</label><select name="decision" id="decision"><option value="diterima">Terima spesimen</option><option value="ditolak">Tolak spesimen (sampel ulang)</option></select></div>
      </div>
      <label class="label" style="display:block;margin-bottom:8px">Checklist Kelayakan</label>
      <div class="checks">
        <?php foreach (['Identitas sesuai', 'Jenis spesimen sesuai', 'Kondisi baik', 'Volume cukup'] as $c): ?>
          <label class="check"><input type="checkbox" name="check[]" value="<?= e($c) ?>" checked> <?= e($c) ?></label>
        <?php endforeach; ?>
      </div>
      <div class="field"><label>Catatan / Alasan Penolakan</label><textarea name="notes" id="specNotes" placeholder="Wajib diisi bila spesimen ditolak, contoh: sampel hemolisis"></textarea></div>
      <div class="card-foot" style="padding:14px 0 0"><button class="btn btn-primary"><?= icon('check', 16) ?> Simpan Verifikasi</button></div>
    </form>
  </div>
  <script>
  document.getElementById('fSpec').addEventListener('submit', ev => {
    const rej = document.getElementById('decision').value === 'ditolak';
    const all = [...document.querySelectorAll('#fSpec [name="check[]"]')].every(c => c.checked);
    const n = document.getElementById('specNotes');
    if (!rej && !all) { ev.preventDefault(); ev.stopImmediatePropagation(); simrsToast('warning', 'Spesimen hanya dapat diterima bila seluruh checklist terpenuhi. Pilih "Tolak" jika tidak layak.'); }
    else if (rej && !n.value.trim()) { ev.preventDefault(); ev.stopImmediatePropagation(); n.closest('.field').classList.add('invalid'); n.focus(); simrsToast('warning', 'Isi alasan penolakan spesimen.'); }
  }, true);
  </script>
  <?php endif; ?>

  <?php if ($stage === 2): ?>
  <div class="card no-print">
    <div class="card-head"><div><h3>Langkah 3 — Input Hasil</h3><p>Flag “Otomatis” dihitung dari nilai rujukan (kritis bila jauh di luar rentang).</p></div></div>
    <form method="post">
      <?= csrf_field() ?><input type="hidden" name="act" value="results"><input type="hidden" name="order_id" value="<?= $id ?>">
      <div class="table-wrap"><table class="table">
        <thead><tr><th>Parameter</th><th>Nilai Rujukan</th><th>Satuan</th><th style="width:180px">Hasil</th><th style="width:150px">Flag</th></tr></thead>
        <tbody><?php foreach ($results as $r): ?>
          <tr><td><b><?= e($r['name']) ?></b><br><small class="mono muted"><?= e($r['code']) ?></small></td>
            <td class="mono"><?= e($r['normal_range'] ?? '-') ?></td><td class="mono"><?= e($r['unit'] ?? '-') ?></td>
            <td><input class="input" name="value[<?= $r['id'] ?>]" value="<?= e($r['result_value']) ?>" required style="height:36px"></td>
            <td><select class="input" name="flag[<?= $r['id'] ?>]" style="height:36px;min-width:0;width:100%"><?= ui_opt_map(['auto' => 'Otomatis', 'normal' => 'Normal', 'tinggi' => 'Tinggi', 'rendah' => 'Rendah', 'kritis' => 'Kritis'], 'auto') ?></select></td></tr>
        <?php endforeach; ?></tbody>
      </table></div>
      <div class="card-foot"><button class="btn btn-primary"><?= icon('check', 16) ?> Simpan & Kirim ke Validasi</button></div>
    </form>
  </div>
  <?php endif; ?>

  <?php if ($stage >= 3): ?>
  <div class="card mb print-area">
    <div class="card-head"><div><h3><?= $stage === 3 ? 'Langkah 4 — Validasi Hasil' : 'Hasil Pemeriksaan' ?></h3><p><?= $stage === 3 ? 'Periksa kembali hasil sebelum dirilis ke dokter.' : 'Tervalidasi oleh ' . e($results[0]['validator'] ?? '-') . ' · ' . tgl($results[0]['validated_at'] ?? null, true) ?></p></div></div>
    <div class="table-wrap"><table class="table">
      <thead><tr><th>Parameter</th><th>Hasil</th><th>Satuan</th><th>Nilai Rujukan</th><th>Flag</th></tr></thead>
      <tbody><?php foreach ($results as $r): ?>
        <tr><td><b><?= e($r['name']) ?></b><br><small class="muted"><?= e($r['category']) ?></small></td>
          <td class="mono strong" style="<?= $r['flag'] === 'kritis' ? 'color:var(--danger)' : '' ?>"><?= e($r['result_value']) ?></td>
          <td class="mono"><?= e($r['unit'] ?? '-') ?></td><td class="mono"><?= e($r['normal_range'] ?? '-') ?></td>
          <td><?= $r['flag'] ? status_badge($r['flag']) : '-' ?></td></tr>
      <?php endforeach; ?></tbody>
    </table></div>
    <?php if ($comms): ?><div class="card-body"><b>Komunikasi Nilai Kritis</b><ul class="list" style="padding:6px 0 0"><?php foreach ($comms as $c): ?><li><span class="l-dot danger"></span><div class="l-main"><b>Disampaikan ke <?= e($c['recipient']) ?></b><span><?= e($c['note'] ?? '') ?> — oleh <?= e($c['by_name'] ?? '-') ?></span></div><time class="mono"><?= tgl($c['communicated_at'], true) ?></time></li><?php endforeach; ?></ul></div><?php endif; ?>
  </div>
  <?php endif; ?>

  <?php if ($stage === 3): ?>
  <div class="card no-print">
    <form method="post" class="card-body">
      <?= csrf_field() ?><input type="hidden" name="act" value="validate"><input type="hidden" name="order_id" value="<?= $id ?>">
      <?php if ($order['n_crit'] && !$comms): ?>
        <div class="alert danger"><?= icon('alert', 18) ?><div><b>Nilai kritis terdeteksi.</b> Sesuai SOP, hasil kritis wajib dilaporkan ke dokter/DPJP (maks. 30 menit) dan dicatat sebelum validasi.</div></div>
        <div class="form-grid">
          <div class="field"><label>Dilaporkan kepada</label><input name="recipient" required value="<?= e(($order['doctor'] ?? '') . ' (DPJP)') ?>"></div>
          <div class="field"><label>Waktu pelaporan</label><input type="datetime-local" name="communicated_at" required value="<?= date('Y-m-d\TH:i') ?>"></div>
          <div class="field full"><label>Catatan</label><input name="comm_note" placeholder="Contoh: dilaporkan via telepon, dokter melakukan read-back"></div>
        </div>
      <?php endif; ?>
      <div class="card-foot" style="padding:14px 0 0;border:0">
        <button class="btn btn-outline" name="act" value="revise" formnovalidate>Kembalikan ke Input</button>
        <button class="btn btn-primary" data-confirm-skip><?= icon('check', 16) ?> Validasi & Rilis Hasil</button>
      </div>
    </form>
  </div>
  <?php endif; ?>
<?php endif; ?>
</div>
<?php layout_end();
