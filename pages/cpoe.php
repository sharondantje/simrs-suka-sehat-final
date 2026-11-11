<?php
/*
 * MODUL 4 — CPOE / COMPUTERIZED PHYSICIAN ORDER ENTRY (database)
 * cpoe   : Pilih Pasien → Verifikasi → Kelola Order → Validasi → Review → Submit
 *          (order lab → lab_results, radiologi → radiology_results, obat → prescriptions untuk farmasi)
 * status : pantau status order dan progres di unit penunjang
 * cancel : batalkan order yang belum diproses (wajib alasan)
 */
const CPOE_STEPS = ['Pilih Pasien', 'Verifikasi', 'Kelola Order', 'Validasi', 'Review', 'Submit'];
const CPOE_MOD   = 'CPOE';
const CPOE_TYPES = ['laboratorium' => 'Laboratorium', 'radiologi' => 'Radiologi', 'obat' => 'Obat / Resep', 'tindakan' => 'Tindakan', 'konsultasi' => 'Konsultasi', 'rawat_inap' => 'Rawat Inap'];
const CPOE_PREFIX = ['laboratorium' => 'ORD-LAB-', 'radiologi' => 'ORD-RAD-', 'obat' => 'ORD-OBT-', 'tindakan' => 'ORD-TND-', 'konsultasi' => 'ORD-KNS-', 'rawat_inap' => 'ORD-RI-'];

$key   = $PAGE['key'];
$me    = (int)$user['id'];
$myDoc = (int)q_val('SELECT id FROM doctors WHERE user_id=?', [$me]);
$vid   = (int)($_GET['v'] ?? 0);
$step  = max(0, min(5, (int)($_GET['step'] ?? ($vid ? 1 : 0))));
$visit = $vid ? q_one('SELECT v.*, po.name poli FROM visits v LEFT JOIN poli po ON po.id=v.poli_id WHERE v.id=?', [$vid]) : null;
if ($key === 'cpoe' && $vid && !$visit) redirect(ui_url('cpoe'));
if ($vid && $step > 1 && empty($_SESSION['cpoe_ok'][$vid])) $step = 1;
$cart = &$_SESSION['cpoe'][$vid];
$cart = $cart ?? [];
$go = fn(int $s) => ui_url('cpoe', ['v' => $vid, 'step' => $s]);

/** Validasi keranjang order: [errors, warnings] */
function cpoe_check(array $cart, array $patient, int $vid): array
{
    $err = []; $warn = [];
    if (!$cart) $err[] = 'Belum ada order di daftar.';
    $allergy = strtolower(trim((string)$patient['allergies']));
    foreach ($cart as $c) {
        if ($c['type'] === 'obat') {
            $stock = (int)q_val('SELECT stock FROM drugs WHERE id=?', [$c['ref']]);
            if ($stock < $c['qty']) $err[] = "Stok {$c['name']} tidak mencukupi (tersedia $stock).";
            if ($allergy !== '' && !in_array($allergy, ['-', 'tidak ada'], true))
                foreach (preg_split('/[,;]+/', $allergy) as $a) if (($a = trim($a)) !== '' && stripos($c['name'], $a) !== false) $err[] = "Pasien alergi \"$a\" — {$c['name']} tidak boleh diberikan.";
            if (trim($c['dosage']) === '') $err[] = "Aturan pakai {$c['name']} wajib diisi.";
        }
        if ($c['type'] === 'laboratorium' && q_val("SELECT 1 FROM orders o JOIN lab_results r ON r.order_id=o.id WHERE o.patient_id=? AND r.lab_test_id=? AND o.status<>'dibatalkan' AND o.ordered_at > NOW() - INTERVAL 1 DAY", [$patient['id'], $c['ref']]))
            $warn[] = "Duplikasi: {$c['name']} sudah dipesan dalam 24 jam terakhir.";
        if ($c['type'] === 'radiologi' && q_val("SELECT 1 FROM orders o JOIN radiology_results r ON r.order_id=o.id WHERE o.patient_id=? AND r.exam_id=? AND o.status<>'dibatalkan' AND o.ordered_at > NOW() - INTERVAL 1 DAY", [$patient['id'], $c['ref']]))
            $warn[] = "Duplikasi: {$c['name']} sudah dipesan dalam 24 jam terakhir.";
        if ($c['priority'] === 'cito' && trim($c['notes']) === '') $warn[] = "Order CITO {$c['name']} sebaiknya disertai indikasi klinis.";
    }
    $names = array_map(fn($c) => $c['type'] . $c['ref'], $cart);
    if (count($names) !== count(array_unique($names))) $warn[] = 'Ada item yang sama lebih dari sekali dalam daftar order.';
    return [array_unique($err), array_unique($warn)];
}

/* ------------------------------ AKSI ------------------------------ */
if ($act = ui_post_action()) {
    if ($act === 'verify') {
        if (count((array)($_POST['check'] ?? [])) < 2) { flash('danger', 'Cocokkan minimal dua identitas pasien.'); redirect($go(1)); }
        $_SESSION['cpoe_ok'][$vid] = true;
        redirect($go(2));
    }
    if ($act === 'add') {
        $type = (string)($_POST['type'] ?? '');
        if (!isset(CPOE_TYPES[$type])) redirect($go(2));
        $ref = (int)($_POST['ref_' . $type] ?? 0); $name = ''; $code = null;
        if ($type === 'laboratorium') { $r = q_one('SELECT code, name FROM lab_tests WHERE id=?', [$ref]); }
        elseif ($type === 'radiologi') { $r = q_one('SELECT code, name FROM radiology_exams WHERE id=?', [$ref]); }
        elseif ($type === 'obat') { $r = q_one('SELECT code, name FROM drugs WHERE id=?', [$ref]); }
        elseif ($type === 'tindakan') { $r = q_one("SELECT code, name FROM tariffs WHERE id=? AND category='tindakan'", [$ref]); }
        else { $txt = trim((string)($_POST['text'] ?? '')); $r = $txt !== '' ? ['code' => null, 'name' => $txt] : null; $ref = 0; }
        if (!$r) { flash('danger', 'Pilih item order terlebih dahulu.'); redirect($go(2)); }
        $cart[] = ['type' => $type, 'ref' => $ref, 'code' => $r['code'], 'name' => $r['name'], 'qty' => max(1, (int)($_POST['qty'] ?? 1)),
                   'dosage' => trim((string)($_POST['dosage'] ?? '')), 'instructions' => trim((string)($_POST['instructions'] ?? '')),
                   'priority' => ($_POST['priority'] ?? '') === 'cito' ? 'cito' : 'normal', 'notes' => trim((string)($_POST['notes'] ?? ''))];
        flash('success', $r['name'] . ' ditambahkan ke daftar order.');
        redirect($go(2));
    }
    if ($act === 'remove') { unset($cart[(int)$_POST['i']]); $cart = array_values($cart); redirect($go(2)); }
    if ($act === 'submit') {
        $patient = ui_patient_row((int)$visit['patient_id']);
        [$err] = cpoe_check($cart, $patient, $vid);
        if ($err) { flash('danger', 'Order belum valid: ' . $err[0]); redirect($go(3)); }
        $groups = [];
        foreach ($cart as $c) $groups[$c['type'] . '|' . $c['priority']][] = $c;
        $made = ui_tx(function () use ($groups, $visit, $myDoc, $vid) {
            $made = [];
            foreach ($groups as $g => $items) {
                [$type, $prio] = explode('|', $g);
                $no = ui_next_no('orders', 'order_no', CPOE_PREFIX[$type]);
                $notes = implode('; ', array_filter(array_column($items, 'notes'))) ?: null;
                q('INSERT INTO orders (order_no, visit_id, patient_id, doctor_id, order_type, priority, status, notes) VALUES (?,?,?,?,?,?,?,?)',
                  [$no, $vid, $visit['patient_id'], $myDoc ?: $visit['doctor_id'], $type, $prio, 'baru', $notes]);
                $oid = (int)db()->lastInsertId();
                foreach ($items as $c) q('INSERT INTO order_items (order_id, item_code, item_name, qty, notes) VALUES (?,?,?,?,?)', [$oid, $c['code'], $c['name'], $c['qty'], $c['dosage'] ?: null]);
                if ($type === 'laboratorium') foreach ($items as $c) q("INSERT INTO lab_results (order_id, lab_test_id, status) VALUES (?,?,'menunggu_sampel')", [$oid, $c['ref']]);
                if ($type === 'radiologi') foreach ($items as $c) q("INSERT INTO radiology_results (order_id, exam_id, scheduled_at, status) VALUES (?,?,NOW() + INTERVAL 1 HOUR,'terjadwal')", [$oid, $c['ref']]);
                if ($type === 'obat') {
                    $rxNo = ui_next_no('prescriptions', 'prescription_no', 'RSP-');
                    q("INSERT INTO prescriptions (prescription_no, order_id, visit_id, patient_id, doctor_id, status) VALUES (?,?,?,?,?,'baru')", [$rxNo, $oid, $vid, $visit['patient_id'], $myDoc ?: $visit['doctor_id']]);
                    $rx = (int)db()->lastInsertId();
                    foreach ($items as $c) q('INSERT INTO prescription_items (prescription_id, drug_id, qty, dosage, instructions) VALUES (?,?,?,?,?)', [$rx, $c['ref'], $c['qty'], $c['dosage'], $c['instructions'] ?: null]);
                }
                $made[] = $no;
            }
            return $made;
        });
        audit('CREATE', CPOE_MOD, 'Order ' . implode(', ', $made) . ' untuk kunjungan ' . $visit['visit_no']);
        $cart = [];
        unset($_SESSION['cpoe_ok'][$vid]);
        flash('success', count($made) . ' order terkirim ke unit terkait: ' . implode(', ', $made));
        redirect(ui_url('cpoe', ['v' => $vid, 'step' => 5, 'sent' => implode(',', $made)]));
    }
    if ($act === 'cancel') {
        $o = q_one("SELECT * FROM orders WHERE id=? AND status='baru'", [(int)$_POST['id']]);
        $why = trim((string)($_POST['reason'] ?? ''));
        if (!$o) { flash('danger', 'Order sudah diproses unit penunjang dan tidak dapat dibatalkan.'); redirect(ui_url('cancel')); }
        if (mb_strlen($why) < 5) { flash('danger', 'Alasan pembatalan wajib diisi (min. 5 karakter).'); redirect(ui_url('cancel')); }
        ui_tx(function () use ($o, $why) {
            q("UPDATE orders SET status='dibatalkan', cancel_reason=?, cancelled_at=NOW() WHERE id=?", [$why, $o['id']]);
            q("DELETE FROM lab_results WHERE order_id=? AND status='menunggu_sampel'", [$o['id']]);
            q("DELETE FROM radiology_results WHERE order_id=? AND status='terjadwal'", [$o['id']]);
            q("UPDATE prescriptions SET status='ditolak', reject_reason=? WHERE order_id=? AND status='baru'", ['Order dibatalkan dokter: ' . $why, $o['id']]);
        });
        audit('CANCEL', CPOE_MOD, 'Batalkan order ' . $o['order_no'] . ": $why");
        flash('success', 'Order ' . $o['order_no'] . ' dibatalkan.');
        redirect(ui_url('cancel'));
    }
    redirect(ui_url($key));
}

/* ------------------------------ DATA ------------------------------ */
if ($visit) $patient = ui_patient_row((int)$visit['patient_id']);
/** Progres order di unit penunjang. */
function cpoe_progress(array $o): string
{
    if ($o['order_type'] === 'laboratorium') {
        $r = q_one("SELECT COUNT(*) n, SUM(status='tervalidasi') ok, SUM(flag='kritis') kr FROM lab_results WHERE order_id=?", [$o['id']]);
        return $r['n'] ? (int)$r['ok'] . '/' . (int)$r['n'] . ' tervalidasi' . ($r['kr'] ? ' · <span class="badge danger">Kritis</span>' : '') : '-';
    }
    if ($o['order_type'] === 'radiologi') { $s = q_val('SELECT status FROM radiology_results WHERE order_id=? LIMIT 1', [$o['id']]); return $s ? status_badge($s) : '-'; }
    if ($o['order_type'] === 'obat') { $s = q_val('SELECT status FROM prescriptions WHERE order_id=? LIMIT 1', [$o['id']]); return $s ? 'Resep ' . status_badge($s) : '-'; }
    return '-';
}

layout_start($PAGE['title'], $key, $PAGE['section']);
?>
<div class="content">
<?php if ($key === 'cpoe'): ?>
  <div class="page-head"><div><h2>CPOE / Order</h2><p>Order elektronik laboratorium, radiologi, obat, tindakan, konsultasi, dan rawat inap.</p></div>
    <?php if ($visit): ?><div class="page-actions"><a class="btn" href="<?= e(ui_url('cpoe')) ?>">← Ganti Pasien</a></div><?php endif; ?></div>
  <?php flashes(); ?>
  <div class="card stepper-card"><?= ui_stepper(CPOE_STEPS, isset($_GET['sent']) ? 6 : $step) ?></div>

  <?php if (!$visit):
    $q = trim((string)($_GET['q'] ?? ''));
    $visits = q_all("SELECT v.*, p.name, p.mrn, p.birth_date, po.name poli, d.name doctor FROM visits v JOIN patients p ON p.id=v.patient_id
                     LEFT JOIN poli po ON po.id=v.poli_id LEFT JOIN doctors d ON d.id=v.doctor_id
                     WHERE v.status IN ('menunggu','dipanggil','diperiksa','dirawat') AND (?='' OR p.name LIKE ? OR p.mrn LIKE ?)
                     ORDER BY (v.doctor_id=?) DESC, v.visit_date DESC", [$q, "%$q%", "%$q%", $myDoc]); ?>
  <div class="card">
    <form class="toolbar" method="get"><input type="hidden" name="page" value="cpoe"><b style="color:var(--heading)">Langkah 1 — Pilih Pasien</b><span class="grow"></span>
      <input class="input" name="q" value="<?= e($q) ?>" placeholder="Cari nama / No. RM"><button class="btn"><?= icon('search', 15) ?> Cari</button></form>
    <div class="table-wrap"><table class="table"><thead><tr><th>No. Kunjungan</th><th>Pasien</th><th>Layanan</th><th>Poli / Unit</th><th>DPJP</th><th>Status</th><th style="text-align:right">Aksi</th></tr></thead><tbody>
    <?php if (!$visits): ?><tr><td colspan="7" class="empty">Tidak ada pasien aktif.</td></tr><?php endif; foreach ($visits as $v): ?>
      <tr><td class="mono"><?= e($v['visit_no']) ?></td><td><b><?= e($v['name']) ?></b><br><small class="mono muted"><?= e($v['mrn']) ?></small></td><td><?= e(ui_label($v['visit_type'])) ?></td>
        <td><?= e($v['poli'] ?? '-') ?></td><td><?= e($v['doctor'] ?? '-') ?></td><td><?= status_badge($v['status']) ?></td>
        <td><div class="actions"><a class="btn btn-sm btn-primary" href="<?= e(ui_url('cpoe', ['v' => $v['id'], 'step' => 1])) ?>">Pilih</a></div></td></tr>
    <?php endforeach; ?></tbody></table></div>
  </div>

  <?php else: ?>
  <?= ui_patient($patient, '<span class="badge info">' . e($visit['visit_no']) . '</span>') ?>

  <?php if ($step === 1): ?>
  <div class="card" style="max-width:720px"><div class="card-head"><div><h3>Langkah 2 — Verifikasi Pasien</h3><p>Pastikan order dibuat untuk pasien yang benar.</p></div></div>
    <form method="post" class="card-body"><?= csrf_field() ?><input type="hidden" name="act" value="verify">
      <?= ui_details(['Nama' => e($patient['name']), 'No. RM' => '<span class="mono">' . e($patient['mrn']) . '</span>', 'Tanggal Lahir' => tgl($patient['birth_date']), 'Alergi' => e($patient['allergies'] ?: '-')]) ?>
      <label class="check"><input type="checkbox" name="check[]" value="1"> Nama & No. RM sesuai</label>
      <label class="check"><input type="checkbox" name="check[]" value="2"> Tanggal lahir sesuai</label>
      <label class="check" style="margin-bottom:14px"><input type="checkbox" name="check[]" value="3"> Alergi telah diperiksa</label>
      <button class="btn btn-primary">Verifikasi & Lanjut</button></form></div>

  <?php elseif ($step === 2):
    $labs = q_all('SELECT id, name, category FROM lab_tests ORDER BY category, name');
    $rads = q_all('SELECT id, name, modality FROM radiology_exams ORDER BY modality, name');
    $drugs = q_all('SELECT id, name, stock, unit FROM drugs ORDER BY name');
    $tind = q_all("SELECT id, name FROM tariffs WHERE category='tindakan' ORDER BY name"); ?>
  <section class="grid grid-2">
    <div class="card"><div class="card-head"><div><h3>Langkah 3 — Kelola Order</h3><p>Tambahkan satu per satu, lalu lanjut ke validasi.</p></div></div>
      <form method="post" class="card-body" id="fAdd"><?= csrf_field() ?><input type="hidden" name="act" value="add">
        <div class="form-grid">
          <div class="field"><label>Jenis Order</label><select name="type" id="oType"><?= ui_opt_map(CPOE_TYPES) ?></select></div>
          <div class="field"><label>Prioritas</label><select name="priority"><option value="normal">Routine</option><option value="cito">Urgent (CITO)</option></select></div>
          <div class="field full" data-t="laboratorium"><label>Pemeriksaan Laboratorium</label><select name="ref_laboratorium"><?= ui_options($labs, 'id', fn($r) => $r['category'] . ' — ' . $r['name'], null, '— pilih —') ?></select></div>
          <div class="field full hidden" data-t="radiologi"><label>Pemeriksaan Radiologi</label><select name="ref_radiologi"><?= ui_options($rads, 'id', fn($r) => $r['modality'] . ' — ' . $r['name'], null, '— pilih —') ?></select></div>
          <div class="field full hidden" data-t="obat"><label>Obat</label><select name="ref_obat"><?= ui_options($drugs, 'id', fn($r) => $r['name'] . ' (stok ' . $r['stock'] . ' ' . $r['unit'] . ')', null, '— pilih —') ?></select></div>
          <div class="field hidden" data-t="obat"><label>Jumlah</label><input type="number" name="qty" min="1" value="10"></div>
          <div class="field hidden" data-t="obat"><label>Dosis / Aturan Pakai</label><input name="dosage" placeholder="3 x 1 tablet"></div>
          <div class="field full hidden" data-t="obat"><label>Instruksi</label><input name="instructions" placeholder="Sesudah makan"></div>
          <div class="field full hidden" data-t="tindakan"><label>Tindakan</label><select name="ref_tindakan"><?= ui_options($tind, 'id', 'name', null, '— pilih —') ?></select></div>
          <div class="field full hidden" data-t="konsultasi rawat_inap"><label>Uraian</label><input name="text" placeholder="Contoh: Konsul Sp.JP / rawat inap kelas II"></div>
          <div class="field full"><label>Instruksi Klinis / Indikasi</label><input name="notes" placeholder="Contoh: curiga DBD hari ke-3"></div>
        </div>
        <button class="btn btn-primary"><?= icon('plus', 16) ?> Tambah ke Daftar</button>
      </form></div>
    <div class="card"><div class="card-head"><div><h3>Daftar Order</h3><p><?= count($cart) ?> item belum dikirim</p></div></div>
      <ul class="list"><?php if (!$cart): ?><li><span class="muted">Belum ada order.</span></li><?php endif; foreach ($cart as $i => $c): ?>
        <li><span class="l-dot <?= $c['priority'] === 'cito' ? 'danger' : 'info' ?>"></span><div class="l-main"><b><?= e($c['name']) ?><?= $c['type'] === 'obat' ? ' × ' . (int)$c['qty'] : '' ?></b>
          <span><?= e(CPOE_TYPES[$c['type']]) ?><?= $c['dosage'] ? ' · ' . e($c['dosage']) : '' ?><?= $c['priority'] === 'cito' ? ' · CITO' : '' ?></span></div>
          <form method="post"><?= csrf_field() ?><input type="hidden" name="act" value="remove"><input type="hidden" name="i" value="<?= $i ?>"><button class="icon-btn danger" title="Hapus"><?= icon('trash', 14) ?></button></form></li>
      <?php endforeach; ?></ul>
      <div class="card-foot"><a class="btn btn-primary <?= $cart ? '' : 'disabled' ?>" href="<?= $cart ? e($go(3)) : '#' ?>">Validasi Order →</a></div></div>
  </section>
  <script>
  (function(){const t=document.getElementById('oType');function f(){document.querySelectorAll('#fAdd [data-t]').forEach(el=>{const on=el.dataset.t.split(' ').includes(t.value);el.classList.toggle('hidden',!on);el.querySelectorAll('input,select').forEach(i=>i.disabled=!on)})}t.onchange=f;f()})();
  </script>

  <?php elseif ($step === 3 || $step === 4): [$err, $warn] = cpoe_check($cart, $patient, $vid); ?>
  <div class="card">
    <div class="card-head"><div><h3><?= $step === 3 ? 'Langkah 4 — Validasi Order' : 'Langkah 5 — Review Order' ?></h3><p><?= $step === 3 ? 'Sistem memeriksa stok, alergi, duplikasi, dan kelengkapan.' : 'Periksa kembali sebelum dikirim ke unit penunjang.' ?></p></div></div>
    <div class="card-body">
      <?php if ($step === 3): foreach ($err as $m): ?><div class="alert danger"><?= icon('x-circle', 18) ?><div><?= e($m) ?></div></div><?php endforeach;
        foreach ($warn as $m): ?><div class="alert warning"><?= icon('alert', 18) ?><div><?= e($m) ?></div></div><?php endforeach;
        if (!$err && !$warn): ?><div class="alert success"><?= icon('check', 18) ?><div>Semua order valid — tidak ada masalah stok, alergi, maupun duplikasi.</div></div><?php endif; endif; ?>
      <div class="table-wrap"><table class="table"><thead><tr><th>Jenis</th><th>Item</th><th>Jumlah / Aturan</th><th>Prioritas</th><th>Instruksi</th></tr></thead><tbody>
      <?php foreach ($cart as $c): ?><tr><td><?= e(CPOE_TYPES[$c['type']]) ?></td><td class="strong"><?= e($c['name']) ?></td><td><?= $c['type'] === 'obat' ? (int)$c['qty'] . ' · ' . e($c['dosage']) : '-' ?></td>
        <td><?= status_badge($c['priority']) ?></td><td><?= e($c['notes'] ?: '-') ?></td></tr><?php endforeach; ?></tbody></table></div>
    </div>
    <div class="card-foot"><a class="btn" href="<?= e($go(2)) ?>">← Ubah Order</a>
      <?php if ($step === 3): ?><a class="btn btn-primary <?= $err ? 'disabled' : '' ?>" href="<?= $err ? '#' : e($go(4)) ?>">Lanjut ke Review →</a>
      <?php else: ?><form method="post"><?= csrf_field() ?><input type="hidden" name="act" value="submit"><button class="btn btn-primary"><?= icon('check', 16) ?> Submit Order</button></form><?php endif; ?></div>
  </div>

  <?php elseif ($step === 5): ?>
  <div class="card"><div class="card-body" style="text-align:center;padding:36px">
    <div class="kpi-icon success" style="margin:0 auto 12px"><?= icon('check', 24) ?></div>
    <h3>Order terkirim</h3><p class="muted">Nomor order: <span class="mono"><?= e($_GET['sent'] ?? '-') ?></span>. Pantau progres di menu Pantau Status Order.</p>
    <a class="btn btn-primary" href="<?= e(ui_url('status')) ?>">Pantau Status Order</a> <a class="btn" href="<?= e(ui_url('cpoe')) ?>">Order Pasien Lain</a>
  </div></div>
  <?php endif; endif; ?>

<?php else: /* ===================== STATUS & CANCEL ===================== */
  $mine = ($_GET['all'] ?? '') !== '1' && $myDoc;
  $ft = $_GET['type'] ?? ''; $fs = $_GET['status'] ?? ''; $q = trim((string)($_GET['q'] ?? ''));
  $where = ['1']; $p = [];
  if ($mine) { $where[] = 'o.doctor_id=?'; $p[] = $myDoc; }
  if ($key === 'cancel') $where[] = "o.status='baru'";
  if ($ft !== '') { $where[] = 'o.order_type=?'; $p[] = $ft; }
  if ($fs !== '' && $key === 'status') { $where[] = 'o.status=?'; $p[] = $fs; }
  if ($q !== '') { $where[] = '(p.name LIKE ? OR p.mrn LIKE ? OR o.order_no LIKE ?)'; array_push($p, "%$q%", "%$q%", "%$q%"); }
  $orders = q_all('SELECT o.*, p.name patient, p.mrn, d.name doctor, (SELECT GROUP_CONCAT(item_name SEPARATOR ", ") FROM order_items WHERE order_id=o.id) items
                   FROM orders o JOIN patients p ON p.id=o.patient_id LEFT JOIN doctors d ON d.id=o.doctor_id WHERE ' . implode(' AND ', $where) . ' ORDER BY o.ordered_at DESC', $p); ?>
  <div class="page-head"><div><h2><?= $key === 'cancel' ? 'Batalkan Order' : 'Pantau Status Order' ?></h2>
    <p><?= $key === 'cancel' ? 'Hanya order berstatus Baru (belum diproses unit penunjang) yang dapat dibatalkan.' : 'Status order dan progres pemeriksaan di unit penunjang.' ?></p></div></div>
  <?php flashes(); ?>
  <div class="card">
    <form class="toolbar" method="get"><input type="hidden" name="page" value="<?= e($key) ?>">
      <select class="input" name="all" onchange="this.form.submit()"><?= ui_opt_map(['' => 'Order saya', '1' => 'Semua dokter'], $mine ? '' : '1') ?></select>
      <select class="input" name="type" onchange="this.form.submit()"><option value="">Semua jenis</option><?= ui_opt_map(CPOE_TYPES, $ft) ?></select>
      <?php if ($key === 'status'): ?><select class="input" name="status" onchange="this.form.submit()"><option value="">Semua status</option><?= ui_opt_map(['baru' => 'Baru', 'diproses' => 'Diproses', 'selesai' => 'Selesai', 'dibatalkan' => 'Dibatalkan'], $fs) ?></select><?php endif; ?>
      <span class="grow"></span><input class="input" name="q" value="<?= e($q) ?>" placeholder="Cari pasien / No. order"><button class="btn"><?= icon('search', 15) ?></button></form>
    <div class="table-wrap"><table class="table">
      <thead><tr><th>No. Order</th><th>Pasien</th><th>Jenis</th><th>Item</th><th>Prioritas</th><th>Waktu</th><th>Status</th><th><?= $key === 'cancel' ? '' : 'Progres' ?></th><th style="text-align:right"><?= $key === 'cancel' ? 'Aksi' : '' ?></th></tr></thead>
      <tbody><?php if (!$orders): ?><tr><td colspan="9" class="empty">Tidak ada order.</td></tr><?php endif; foreach ($orders as $o): ?>
        <tr><td class="mono strong"><?= e($o['order_no']) ?></td><td><b><?= e($o['patient']) ?></b><br><small class="mono muted"><?= e($o['mrn']) ?></small></td>
          <td><?= e(CPOE_TYPES[$o['order_type']]) ?></td><td><?= e($o['items'] ?? '-') ?></td><td><?= status_badge($o['priority'] === 'cito' ? 'cito' : 'normal') ?></td>
          <td class="mono"><?= tgl($o['ordered_at'], true) ?></td><td><?= status_badge($o['status']) ?><?php if ($o['cancel_reason']): ?><br><small class="muted"><?= e($o['cancel_reason']) ?></small><?php endif; ?></td>
          <td><?= $key === 'status' ? cpoe_progress($o) : '' ?></td>
          <td><div class="actions"><?php if ($key === 'cancel'): ?><button class="btn btn-sm btn-danger" data-modal="mCancel" onclick='cc(<?= json_encode(['id' => $o['id'], 'no' => $o['order_no'], 'p' => $o['patient']], JSON_HEX_APOS) ?>)'>Batalkan</button><?php endif; ?></div></td></tr>
      <?php endforeach; ?></tbody>
    </table></div>
  </div>
  <?php if ($key === 'cancel'): ?>
  <div class="modal" id="mCancel"><div class="modal-box"><div class="card-head"><h3>Batalkan Order <span class="mono" id="cNo"></span></h3><button class="icon-btn" data-close type="button">✕</button></div>
    <form method="post" data-confirm="Order akan dibatalkan dan unit penunjang tidak akan memprosesnya." data-confirm-title="Konfirmasi Pembatalan" data-confirm-ok="Ya, batalkan"><?= csrf_field() ?><input type="hidden" name="act" value="cancel"><input type="hidden" name="id" id="cId">
      <p class="muted" id="cP" style="margin:0 0 12px"></p>
      <div class="field"><label>Alasan Pembatalan</label><textarea name="reason" required minlength="5" placeholder="Contoh: salah pilih pemeriksaan / kondisi pasien berubah"></textarea></div>
      <div class="modal-foot"><button type="button" class="btn" data-close>Kembali</button><button class="btn btn-danger">Batalkan Order</button></div></form></div></div>
  <script>function cc(o){cId.value=o.id;cNo.textContent=o.no;cP.textContent='Pasien: '+o.p}</script>
  <?php endif; ?>
<?php endif; ?>
</div>
<?php layout_end();
