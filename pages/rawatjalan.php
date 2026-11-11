<?php
/*
 * MODUL 5 — RAWAT JALAN / OUTPATIENT (database)
 * Antrean: Menunggu → Dipanggil → Diperiksa → Selesai (atau Dibatalkan)
 * Stepper pelayanan: Data Kunjungan → Pelayanan → Tindak Lanjut → Finalisasi
 */
const RJ_STEPS = ['Data Kunjungan', 'Pelayanan', 'Tindak Lanjut', 'Finalisasi'];
const RJ_MOD   = 'Outpatient';

$me    = (int)$user['id'];
$myDoc = (int)q_val('SELECT id FROM doctors WHERE user_id=?', [$me]);
$vid   = (int)($_GET['v'] ?? 0);
$visit = $vid ? q_one("SELECT v.*, po.name poli, d.name doctor FROM visits v LEFT JOIN poli po ON po.id=v.poli_id LEFT JOIN doctors d ON d.id=v.doctor_id WHERE v.id=? AND v.visit_type='rawat_jalan'", [$vid]) : null;
if ($vid && !$visit) redirect(ui_url('rawatjalan'));

function rj_stage(array $v): int
{
    if ($v['status'] === 'selesai') return 4;
    if (in_array($v['status'], ['menunggu', 'dipanggil', 'batal'], true)) return 0;
    if (!q_val('SELECT 1 FROM medical_records WHERE visit_id=?', [$v['id']])) return 1;
    if (!q_val('SELECT 1 FROM referrals WHERE visit_id=?', [$v['id']]) && !q_val("SELECT 1 FROM orders WHERE visit_id=? AND order_type='rawat_inap'", [$v['id']])) return 2;
    return 3;
}

if ($act = ui_post_action()) {
    $back = ui_url('rawatjalan', ['v' => $vid]);
    $f = fn($k) => trim((string)($_POST[$k] ?? '')) ?: null;
    if ($act === 'call') {
        q("UPDATE visits SET status='dipanggil' WHERE id=? AND status='menunggu'", [(int)$_POST['id']]);
        audit('UPDATE', RJ_MOD, 'Panggil pasien antrean ' . q_val('SELECT queue_no FROM visits WHERE id=?', [(int)$_POST['id']]));
        flash('success', 'Pasien dipanggil ke ruang periksa.');
        redirect(ui_url('rawatjalan', ['v' => (int)$_POST['id']]));
    }
    if ($act === 'start') {
        q("UPDATE visits SET status='diperiksa', doctor_id=COALESCE(doctor_id, ?) WHERE id=? AND status IN ('menunggu','dipanggil')", [$myDoc ?: null, $vid]);
        audit('UPDATE', RJ_MOD, 'Mulai pemeriksaan ' . $visit['visit_no']);
        redirect($back);
    }
    if ($act === 'cancel') {
        $why = $f('reason');
        if (!$why) { flash('danger', 'Alasan pembatalan wajib diisi.'); redirect($back); }
        q("UPDATE visits SET status='batal', complaint=CONCAT_WS(' | Batal: ', complaint, ?) WHERE id=? AND status IN ('menunggu','dipanggil')", [$why, $vid]);
        audit('CANCEL', RJ_MOD, 'Batalkan kunjungan ' . $visit['visit_no'] . ": $why");
        flash('warning', 'Kunjungan dibatalkan.');
        redirect(ui_url('rawatjalan'));
    }
    if ($act === 'service') {
        $icd = strtoupper((string)$f('icd'));
        if (!$f('result') || !$f('diagnosis')) { flash('danger', 'Hasil pemeriksaan dan diagnosis klinis wajib diisi.'); redirect($back); }
        if ($icd && !q_val('SELECT 1 FROM icd10 WHERE code=?', [$icd])) { flash('danger', "Kode ICD-10 $icd tidak ditemukan di master data."); redirect($back); }
        ui_tx(function () use ($vid, $visit, $myDoc, $f, $icd) {
            $rid = (int)q_val('SELECT id FROM medical_records WHERE visit_id=? ORDER BY id DESC LIMIT 1', [$vid]);
            if ($rid) q("UPDATE medical_records SET objective=?, assessment=?, plan=?, status='draft' WHERE id=?", [$f('result'), $f('diagnosis'), $f('therapy'), $rid]);
            else { q("INSERT INTO medical_records (visit_id, patient_id, doctor_id, subjective, objective, assessment, plan, status) VALUES (?,?,?,?,?,?,?,'draft')",
                     [$vid, $visit['patient_id'], $myDoc ?: $visit['doctor_id'], $visit['complaint'], $f('result'), $f('diagnosis'), $f('therapy')]); $rid = (int)db()->lastInsertId(); }
            if ($icd) { q("DELETE FROM diagnoses WHERE record_id=? AND type='primer'", [$rid]); q("INSERT INTO diagnoses (record_id, icd10_code, type) VALUES (?,?,'primer')", [$rid, $icd]); }
        });
        audit('UPDATE', RJ_MOD, 'Pelayanan ' . $visit['visit_no']);
        flash('success', 'Pelayanan tersimpan. Lanjut tindak lanjut pasien.');
        redirect($back);
    }
    if ($act === 'followup') {
        $type = (string)($_POST['type'] ?? '');
        if ($type === 'rawat_inap') {
            $no = ui_next_no('orders', 'order_no', 'ORD-RI-');
            q("INSERT INTO orders (order_no, visit_id, patient_id, doctor_id, order_type, priority, status, notes) VALUES (?,?,?,?,'rawat_inap',?, 'baru', ?)",
              [$no, $vid, $visit['patient_id'], $myDoc ?: $visit['doctor_id'], ($_POST['priority'] ?? '') === 'cito' ? 'cito' : 'normal', $f('reason') ?? 'Rencana rawat inap']);
            q('INSERT INTO order_items (order_id, item_name, qty) VALUES (?,?,1)', [(int)db()->lastInsertId(), 'Admisi rawat inap']);
        } elseif (in_array($type, ['kontrol_ulang', 'internal', 'eksternal'], true)) {
            if ($type === 'kontrol_ulang' && (empty($_POST['control_date']) || $_POST['control_date'] <= date('Y-m-d'))) { flash('danger', 'Tanggal kontrol ulang harus setelah hari ini.'); redirect($back); }
            if ($type !== 'kontrol_ulang' && !$f('destination')) { flash('danger', 'Tujuan rujukan wajib diisi.'); redirect($back); }
            q('INSERT INTO referrals (visit_id, referral_type, destination, reason, control_date) VALUES (?,?,?,?,?)',
              [$vid, $type, $f('destination') ?? $visit['poli'], $f('reason'), $type === 'kontrol_ulang' ? $_POST['control_date'] : null]);
        } else {
            q("INSERT INTO referrals (visit_id, referral_type, destination, reason) VALUES (?, 'internal', 'Pulang', ?)", [$vid, $f('reason') ?? 'Pulang, berobat jalan']);
        }
        audit('CREATE', RJ_MOD, 'Tindak lanjut ' . $type . ' ' . $visit['visit_no']);
        flash('success', 'Tindak lanjut pasien tersimpan.');
        redirect($back);
    }
    if ($act === 'finalize') {
        $rid = (int)q_val('SELECT id FROM medical_records WHERE visit_id=? ORDER BY id DESC LIMIT 1', [$vid]);
        if (!$rid || !q_val("SELECT 1 FROM diagnoses WHERE record_id=? AND type='primer'", [$rid])) { flash('danger', 'Finalisasi membutuhkan diagnosis ICD-10 primer.'); redirect($back); }
        ui_tx(function () use ($rid, $vid, $f) {
            q("UPDATE medical_records SET status='final', plan=CONCAT_WS('\nInstruksi: ', plan, ?) WHERE id=?", [$f('instruction'), $rid]);
            q("UPDATE visits SET status='selesai' WHERE id=?", [$vid]);
            q("UPDATE appointments a JOIN visits v ON v.patient_id=a.patient_id AND a.appointment_date=DATE(v.visit_date) SET a.status='selesai' WHERE v.id=? AND a.status='check_in'", [$vid]);
        });
        audit('VALIDATE', RJ_MOD, 'Finalisasi kunjungan ' . $visit['visit_no']);
        flash('success', 'Kunjungan selesai. Data diteruskan ke kasir untuk penagihan.');
        redirect($back);
    }
    redirect($back);
}

layout_start($PAGE['title'], 'rawatjalan', $PAGE['section']);
?>
<div class="content">
  <div class="page-head"><div><h2>Rawat Jalan</h2><p>Antrean pasien poliklinik, pelayanan, tindak lanjut, dan finalisasi kunjungan.</p></div>
    <?php if ($visit): ?><div class="page-actions"><a class="btn" href="<?= e(ui_url('rawatjalan')) ?>">← Antrean</a></div><?php endif; ?></div>
  <?php flashes(); ?>

<?php if (!$visit):
  $fs = $_GET['status'] ?? '';
  $date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date'] ?? '') ? $_GET['date'] : date('Y-m-d');
  $rows = q_all("SELECT v.*, p.name, p.mrn, p.birth_date, p.gender, po.name poli, d.name doctor, i.name insurer FROM visits v JOIN patients p ON p.id=v.patient_id
                 LEFT JOIN poli po ON po.id=v.poli_id LEFT JOIN doctors d ON d.id=v.doctor_id LEFT JOIN insurers i ON i.id=v.insurer_id
                 WHERE v.visit_type='rawat_jalan' AND DATE(v.visit_date)=? ORDER BY FIELD(v.status,'dipanggil','diperiksa','menunggu','selesai','batal'), v.queue_no", [$date]);
  $c = fn($s) => count(array_filter($rows, fn($r) => $r['status'] === $s));
  $view = $fs ? array_filter($rows, fn($r) => $r['status'] === $fs) : $rows; ?>
  <section class="kpi-grid">
    <?= ui_kpi('Menunggu', $c('menunggu'), 'clock', 'warning') ?>
    <?= ui_kpi('Dipanggil / Diperiksa', $c('dipanggil') + $c('diperiksa'), 'stetho', 'info') ?>
    <?= ui_kpi('Selesai', $c('selesai'), 'check') ?>
    <?= ui_kpi('Dibatalkan', $c('batal'), 'x-circle', 'danger') ?>
  </section>
  <div class="card">
    <form class="toolbar" method="get"><input type="hidden" name="page" value="rawatjalan"><b style="color:var(--heading)">Antrean Poliklinik</b><span class="grow"></span>
      <select class="input" name="status" onchange="this.form.submit()"><option value="">Semua status</option><?= ui_opt_map(['menunggu' => 'Menunggu', 'dipanggil' => 'Dipanggil', 'diperiksa' => 'Diperiksa', 'selesai' => 'Selesai', 'batal' => 'Dibatalkan'], $fs) ?></select>
      <input class="input" type="date" name="date" value="<?= e($date) ?>" onchange="this.form.submit()"></form>
    <div class="table-wrap"><table class="table"><thead><tr><th>Antrean</th><th>Pasien</th><th>Poli</th><th>Dokter</th><th>Penjamin</th><th>Keluhan</th><th>Status</th><th style="text-align:right">Aksi</th></tr></thead><tbody>
    <?php if (!$view): ?><tr><td colspan="8" class="empty">Tidak ada kunjungan.</td></tr><?php endif; foreach ($view as $r): ?>
      <tr><td class="mono strong"><?= e($r['queue_no']) ?></td><td><b><?= e($r['name']) ?></b><br><small class="mono muted"><?= e($r['mrn']) ?> · <?= $r['gender'] ?> · <?= ui_age($r['birth_date']) ?></small></td>
        <td><?= e($r['poli'] ?? '-') ?></td><td><?= e($r['doctor'] ?? '-') ?></td><td><?= e($r['insurer'] ?? '-') ?></td><td><?= e($r['complaint'] ?? '-') ?></td><td><?= status_badge($r['status']) ?></td>
        <td><div class="actions"><?php if ($r['status'] === 'menunggu'): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="act" value="call"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn btn-sm btn-primary">Panggil</button></form><?php endif; ?>
          <a class="btn btn-sm" href="<?= e(ui_url('rawatjalan', ['v' => $r['id']])) ?>"><?= in_array($r['status'], ['selesai', 'batal'], true) ? 'Lihat' : 'Layani' ?></a></div></td></tr>
    <?php endforeach; ?></tbody></table></div></div>

<?php else:
  $stage = rj_stage($visit);
  $patient = ui_patient_row((int)$visit['patient_id']);
  $rec = q_one('SELECT * FROM medical_records WHERE visit_id=? ORDER BY id DESC LIMIT 1', [$vid]);
  $dx  = $rec ? q_one("SELECT d.icd10_code, i.description FROM diagnoses d JOIN icd10 i ON i.code=d.icd10_code WHERE d.record_id=? AND d.type='primer'", [$rec['id']]) : null;
  $ref = q_all('SELECT * FROM referrals WHERE visit_id=?', [$vid]);
  $riOrder = q_one("SELECT * FROM orders WHERE visit_id=? AND order_type='rawat_inap' ORDER BY id DESC LIMIT 1", [$vid]);
  $vital = q_one('SELECT * FROM vital_signs WHERE visit_id=? ORDER BY recorded_at DESC LIMIT 1', [$vid]); ?>
  <div class="card stepper-card"><?= ui_stepper(RJ_STEPS, $stage) ?></div>
  <?php if ($visit['status'] === 'batal'): ?><div class="alert danger"><?= icon('x-circle', 18) ?><div>Kunjungan ini dibatalkan.</div></div><?php endif; ?>
  <?= ui_patient($patient, '<span class="badge info">Antrean ' . e($visit['queue_no']) . '</span>') ?>
  <section class="grid grid-2">
    <div class="card"><div class="card-head"><div><h3>Detail Kunjungan</h3><p><span class="mono"><?= e($visit['visit_no']) ?></span></p></div><?= status_badge($visit['status']) ?></div>
      <?= ui_details(['Poli' => e($visit['poli'] ?? '-'), 'Dokter' => e($visit['doctor'] ?? '-'), 'Waktu Daftar' => '<span class="mono">' . tgl($visit['visit_date'], true) . '</span>', 'Keluhan' => e($visit['complaint'] ?? '-'),
          'Vital Sign' => $vital ? '<span class="mono">TD ' . e($vital['systolic'] . '/' . $vital['diastolic']) . ' · N ' . e($vital['pulse']) . ' · S ' . e($vital['temperature']) . '°C</span>' : '-',
          'Hasil Pemeriksaan' => e($rec['objective'] ?? '-'), 'Diagnosis' => $rec ? e($rec['assessment']) . ($dx ? ' <span class="mono">(' . e($dx['icd10_code']) . ')</span>' : '') : '-', 'Resep / Tindakan' => nl2br(e($rec['plan'] ?? '-')),
          'Tindak Lanjut' => $ref ? implode('<br>', array_map(fn($r) => e(ui_label($r['referral_type']) . ' — ' . $r['destination'] . ($r['control_date'] ? ' (' . tgl($r['control_date']) . ')' : '')), $ref)) : ($riOrder ? 'Rencana rawat inap ' . status_badge($riOrder['status']) : '-')]) ?></div>

    <div class="card">
    <?php if ($stage === 0 && $visit['status'] !== 'batal'): ?>
      <div class="card-head"><div><h3>Langkah 1 — Data Kunjungan</h3><p>Pastikan identitas sesuai, lalu mulai pemeriksaan.</p></div></div>
      <div class="card-body"><form method="post"><?= csrf_field() ?><input type="hidden" name="act" value="start"><button class="btn btn-primary"><?= icon('stetho', 16) ?> Mulai Pemeriksaan</button></form></div>
      <form method="post" class="card-foot" style="justify-content:flex-start" data-confirm="Kunjungan akan dibatalkan dan keluar dari antrean." data-confirm-title="Batalkan Kunjungan" data-confirm-ok="Batalkan">
        <?= csrf_field() ?><input type="hidden" name="act" value="cancel"><input class="input" name="reason" required placeholder="Alasan pembatalan" style="flex:1;height:38px"><button class="btn btn-danger">Batalkan Kunjungan</button></form>
    <?php elseif ($stage === 1): ?>
      <div class="card-head"><div><h3>Langkah 2 — Pelayanan</h3><p>Hasil pemeriksaan & diagnosis. Order penunjang/resep lengkap via menu CPOE.</p></div></div>
      <form method="post" class="card-body"><?= csrf_field() ?><input type="hidden" name="act" value="service">
        <div class="field"><label>Hasil Pemeriksaan</label><textarea name="result" required></textarea></div>
        <div class="form-grid"><div class="field"><label>Diagnosis Klinis</label><input name="diagnosis" required placeholder="Diagnosis klinis..."></div>
          <div class="field"><label>Kode ICD-10</label><input name="icd" data-rule="icd" list="icdL" placeholder="Contoh: J18.9"></div></div>
        <div class="field"><label>Resep / Tindakan</label><textarea name="therapy" placeholder="Nama obat, dosis, frekuensi..."></textarea></div>
        <datalist id="icdL"><?php foreach (q_all('SELECT code, description FROM icd10 ORDER BY code') as $i): ?><option value="<?= e($i['code']) ?>"><?= e($i['description']) ?></option><?php endforeach; ?></datalist>
        <button class="btn btn-primary">Simpan Pelayanan</button> <a class="btn" href="<?= e(ui_url('cpoe', ['v' => $vid, 'step' => 1])) ?>">Buka CPOE</a></form>
    <?php elseif ($stage === 2): ?>
      <div class="card-head"><div><h3>Langkah 3 — Tindak Lanjut Pasien</h3><p>Pilih rencana setelah pemeriksaan.</p></div></div>
      <form method="post" class="card-body" id="fFu"><?= csrf_field() ?><input type="hidden" name="act" value="followup">
        <div class="field"><label>Tindak Lanjut</label><select name="type" id="fuType"><?= ui_opt_map(['pulang' => 'Pulang / berobat jalan', 'kontrol_ulang' => 'Kontrol Ulang', 'internal' => 'Rujukan Internal (poli lain)', 'eksternal' => 'Rujukan Eksternal (RS lain)', 'rawat_inap' => 'Rawat Inap']) ?></select></div>
        <div class="field hidden" data-fu="kontrol_ulang"><label>Tanggal Kontrol</label><input type="date" name="control_date" min="<?= date('Y-m-d', strtotime('+1 day')) ?>"></div>
        <div class="field hidden" data-fu="internal eksternal"><label>Tujuan Rujukan</label><input name="destination" placeholder="RS/Poli tujuan rujukan..."></div>
        <div class="field"><label>Alasan / Instruksi</label><input name="reason" placeholder="Instruksi untuk pasien..."></div>
        <button class="btn btn-primary">Simpan Tindak Lanjut</button></form>
      <script>(function(){const s=document.getElementById('fuType');const f=()=>document.querySelectorAll('#fFu [data-fu]').forEach(el=>el.classList.toggle('hidden',!el.dataset.fu.split(' ').includes(s.value)));s.onchange=f;f()})();</script>
    <?php elseif ($stage === 3): ?>
      <div class="card-head"><div><h3>Langkah 4 — Finalisasi Pelayanan</h3><p>Rekam medis dikunci dan tagihan siap diproses kasir.</p></div></div>
      <form method="post" class="card-body"><?= csrf_field() ?><input type="hidden" name="act" value="finalize">
        <?php if (!$dx): ?><div class="alert warning">Diagnosis ICD-10 primer belum diisi. Lengkapi di langkah Pelayanan atau menu EMR.</div><?php endif; ?>
        <div class="field"><label>Instruksi / Catatan untuk Pasien</label><textarea name="instruction" placeholder="Instruksi untuk pasien..."></textarea></div>
        <button class="btn btn-primary" <?= $dx ? '' : 'disabled' ?>><?= icon('check', 16) ?> Finalisasi Kunjungan</button></form>
    <?php else: ?>
      <div class="card-head"><div><h3>Kunjungan <?= $visit['status'] === 'batal' ? 'Dibatalkan' : 'Selesai' ?></h3></div></div>
      <div class="card-body"><a class="btn" href="<?= e(ui_url('rawatjalan')) ?>">Kembali ke Antrean</a></div>
    <?php endif; ?>
    </div>
  </section>
<?php endif; ?>
</div>
<?php layout_end();
