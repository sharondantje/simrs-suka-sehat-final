<?php
/*
 * MODUL 7 — IGD / EMERGENCY (database)
 * Stepper: Triase (P1–P4) → Pemeriksaan & Tindakan → Observasi & Keputusan → Finalisasi ICD
 * P1 Merah (kritis) · P2 Kuning (segera) · P3 Hijau (tidak mendesak) · P4 Hitam (meninggal/expectant)
 */
const IGD_STEPS = ['Triase', 'Pemeriksaan & Tindakan', 'Observasi & Keputusan', 'Finalisasi ICD'];
const IGD_MOD   = 'Emergency';
const IGD_LEVEL = ['merah' => 'P1 — Merah (Kritis)', 'kuning' => 'P2 — Kuning (Segera)', 'hijau' => 'P3 — Hijau (Tidak Mendesak)', 'hitam' => 'P4 — Hitam (Meninggal/Expectant)'];

$me    = (int)$user['id'];
$isDoc = $user['role'] === 'dokter';
$myDoc = (int)q_val('SELECT id FROM doctors WHERE user_id=?', [$me]);
$igdPoli = (int)q_val("SELECT id FROM poli WHERE code='IGD'") ?: null;
$vid   = (int)($_GET['v'] ?? 0);
$visit = $vid ? q_one("SELECT v.*, d.name doctor FROM visits v LEFT JOIN doctors d ON d.id=v.doctor_id WHERE v.id=? AND v.visit_type='igd'", [$vid]) : null;
if ($vid && !$visit) redirect(ui_url('igd'));
$tri = $visit ? q_one('SELECT * FROM triages WHERE visit_id=? ORDER BY id DESC LIMIT 1', [$vid]) : null;

function igd_stage(array $v, ?array $t): int
{
    if ($v['status'] === 'selesai' || q_val('SELECT status FROM medical_records WHERE visit_id=? ORDER BY id DESC LIMIT 1', [$v['id']]) === 'final') return 4;
    if (!$t) return 0;
    if (!$t['actions']) return 1;
    if (!$t['observation']) return 2;
    return 3;
}

if ($act = ui_post_action()) {
    $f = fn($k) => trim((string)($_POST[$k] ?? '')) ?: null;
    $vitals = function (int $v, int $p) use ($me) {
        $k = ['systolic', 'diastolic', 'pulse', 'respiration', 'temperature', 'spo2'];
        $x = array_map(fn($a) => ($_POST[$a] ?? '') === '' ? null : $_POST[$a], $k);
        if (array_filter($x, fn($a) => $a !== null)) q('INSERT INTO vital_signs (visit_id, patient_id, recorded_by, systolic, diastolic, pulse, respiration, temperature, spo2) VALUES (?,?,?,?,?,?,?,?,?)', [$v, $p, $me, ...$x]);
    };
    if ($act === 'register') {
        $lvl = $_POST['triage_level'] ?? '';
        if (!(int)($_POST['patient_id'] ?? 0) && !$f('name')) { flash('danger', 'Pilih pasien terdaftar atau isi nama pasien baru.'); redirect(ui_url('igd', ['new' => 1])); }
        if (!isset(IGD_LEVEL[$lvl]) || !$f('chief_complaint')) { flash('danger', 'Tingkat kedaruratan dan keluhan utama wajib diisi.'); redirect(ui_url('igd', ['new' => 1])); }
        $newV = ui_tx(function () use ($f, $lvl, $igdPoli, $myDoc, $vitals) {
            $pid = (int)($_POST['patient_id'] ?? 0);
            if (!$pid) {
                if (!$f('name')) throw new RuntimeException('Nama pasien wajib diisi.');
                $mrn = str_pad((string)((int)q_val('SELECT MAX(CAST(mrn AS UNSIGNED)) FROM patients') + 1), 8, '0', STR_PAD_LEFT);
                $nik = preg_replace('/\D/', '', (string)$f('nik'));
                $birth = $f('birth_date') ?: date('Y-m-d', strtotime('-' . max(0, (int)$f('age')) . ' years'));
                q('INSERT INTO patients (mrn, nik, name, gender, birth_date, address, phone, insurer_id) VALUES (?,?,?,?,?,?,?,1)',
                  [$mrn, strlen($nik) === 16 ? $nik : null, $f('name'), ($_POST['gender'] ?? 'L') === 'P' ? 'P' : 'L', $birth, $f('address') ?? 'Belum diketahui', $f('phone')]);
                $pid = (int)db()->lastInsertId();
            }
            $doc = $myDoc ?: ((int)q_val('SELECT id FROM doctors WHERE poli_id=? LIMIT 1', [$igdPoli]) ?: null);
            $v = ui_create_visit($pid, 'igd', $igdPoli, $doc, null, (string)$f('chief_complaint'), 'diperiksa');
            q('INSERT INTO triages (visit_id, patient_id, triage_level, chief_complaint, gcs, arrival_mode, notes) VALUES (?,?,?,?,?,?,?)',
              [$v, $pid, $lvl, $f('chief_complaint'), (int)($_POST['gcs'] ?? 15) ?: 15, in_array($_POST['arrival_mode'] ?? '', ['sendiri', 'ambulans', 'rujukan', 'polisi'], true) ? $_POST['arrival_mode'] : 'sendiri', $f('notes')]);
            $vitals($v, $pid);
            return $v;
        });
        audit('CREATE', IGD_MOD, "Registrasi & triase IGD kunjungan #$newV (" . IGD_LEVEL[$lvl] . ')');
        flash('success', 'Pasien IGD terdaftar dengan triase ' . IGD_LEVEL[$lvl] . '.');
        redirect(ui_url('igd', ['v' => $newV]));
    }
    if (!$visit) redirect(ui_url('igd'));
    $back = ui_url('igd', ['v' => $vid]);
    if ($act === 'retriage' && $tri) {
        $lvl = $_POST['triage_level'] ?? '';
        if (isset(IGD_LEVEL[$lvl])) q('UPDATE triages SET triage_level=?, notes=CONCAT_WS(" | ", notes, ?) WHERE id=?', [$lvl, 'Re-triase: ' . IGD_LEVEL[$lvl], $tri['id']]);
        audit('UPDATE', IGD_MOD, 'Re-triase ' . $visit['visit_no'] . " → $lvl");
    }
    if ($act === 'exam' && $tri) {
        if (!$f('actions') || !$f('diagnosis')) { flash('danger', 'Tindakan yang dilakukan dan diagnosis klinis wajib diisi.'); redirect($back); }
        ui_tx(function () use ($tri, $vid, $visit, $f, $vitals, $myDoc) {
            $vitals($vid, (int)$visit['patient_id']);
            q('UPDATE triages SET actions=? WHERE id=?', [$f('actions'), $tri['id']]);
            $rid = (int)q_val('SELECT id FROM medical_records WHERE visit_id=? LIMIT 1', [$vid]);
            if ($rid) q("UPDATE medical_records SET objective=?, assessment=?, plan=? WHERE id=?", [$f('exam'), $f('diagnosis'), $f('actions'), $rid]);
            else q("INSERT INTO medical_records (visit_id, patient_id, doctor_id, subjective, objective, assessment, plan, status) VALUES (?,?,?,?,?,?,?,'draft')",
                   [$vid, $visit['patient_id'], $myDoc ?: $visit['doctor_id'], $tri['chief_complaint'], $f('exam'), $f('diagnosis'), $f('actions')]);
        });
        audit('UPDATE', IGD_MOD, 'Pemeriksaan & tindakan ' . $visit['visit_no']);
        flash('success', 'Pemeriksaan & tindakan tersimpan.');
    }
    if ($act === 'observe' && $tri) {
        $disp = in_array($_POST['disposition'] ?? '', ['observasi', 'rawat_inap', 'pulang', 'rujuk'], true) ? $_POST['disposition'] : null;
        if (!$f('observation') || !$disp) { flash('danger', 'Hasil observasi dan keputusan pasien wajib diisi.'); redirect($back); }
        q('UPDATE triages SET observation=?, patient_condition=?, disposition=? WHERE id=?', [$f('observation'), $f('condition'), $disp, $tri['id']]);
        audit('UPDATE', IGD_MOD, 'Observasi & keputusan (' . $disp . ') ' . $visit['visit_no']);
        flash('success', 'Observasi tersimpan. Lanjut finalisasi ICD.');
    }
    if ($act === 'finalize' && $tri) {
        $icd = strtoupper((string)$f('icd'));
        if (!$icd || !q_val('SELECT 1 FROM icd10 WHERE code=?', [$icd])) { flash('danger', 'Kode ICD-10 tidak valid / tidak ada di master data.'); redirect($back); }
        ui_tx(function () use ($vid, $visit, $tri, $icd, $f, $myDoc) {
            $rid = (int)q_val('SELECT id FROM medical_records WHERE visit_id=? LIMIT 1', [$vid]);
            if (!$rid) { q("INSERT INTO medical_records (visit_id, patient_id, doctor_id, subjective, assessment, status) VALUES (?,?,?,?,?,'final')", [$vid, $visit['patient_id'], $myDoc ?: $visit['doctor_id'], $tri['chief_complaint'], $f('final_dx')]); $rid = (int)db()->lastInsertId(); }
            q("UPDATE medical_records SET status='final', assessment=COALESCE(?, assessment) WHERE id=?", [$f('final_dx'), $rid]);
            q("DELETE FROM diagnoses WHERE record_id=? AND type='primer'", [$rid]);
            q("INSERT INTO diagnoses (record_id, icd10_code, type) VALUES (?,?,'primer')", [$rid, $icd]);
            if ($tri['disposition'] === 'rawat_inap') {
                if (!q_val("SELECT 1 FROM orders WHERE visit_id=? AND order_type='rawat_inap' AND status='baru'", [$vid])) {
                    q("INSERT INTO orders (order_no, visit_id, patient_id, doctor_id, order_type, priority, status, notes) VALUES (?,?,?,?,'rawat_inap','cito','baru',?)",
                      [ui_next_no('orders', 'order_no', 'ORD-RI-'), $vid, $visit['patient_id'], $myDoc ?: $visit['doctor_id'], 'Dari IGD: ' . ($f('final_dx') ?? $icd)]);
                }
                q("UPDATE visits SET status='diperiksa' WHERE id=?", [$vid]);   // tetap aktif sampai admisi rawat inap
            } else {
                if ($tri['disposition'] === 'rujuk') q("INSERT INTO referrals (visit_id, referral_type, destination, reason) VALUES (?, 'eksternal', ?, ?)", [$vid, $f('destination') ?? 'RS rujukan', $f('final_dx')]);
                q("UPDATE visits SET status='selesai' WHERE id=?", [$vid]);
            }
        });
        audit('VALIDATE', IGD_MOD, 'Finalisasi ICD ' . $icd . ' ' . $visit['visit_no']);
        flash('success', $tri['disposition'] === 'rawat_inap' ? 'Final. Permintaan rawat inap dikirim ke menu Rawat Inap → Admisi Baru.' : 'Pelayanan IGD selesai.');
    }
    redirect($back);
}

layout_start($PAGE['title'], 'igd', $PAGE['section']);
?>
<div class="content">
  <div class="page-head"><div><h2>IGD / Emergency</h2><p>Triase, pemeriksaan & tindakan darurat, observasi, dan keputusan pasien.</p></div>
    <div class="page-actions"><?php if ($visit || isset($_GET['new'])): ?><a class="btn" href="<?= e(ui_url('igd')) ?>">← Daftar IGD</a><?php else: ?><a class="btn btn-primary" href="<?= e(ui_url('igd', ['new' => 1])) ?>"><?= icon('plus', 16) ?> Pasien Baru IGD</a><?php endif; ?></div></div>
  <?php flashes(); ?>

<?php if (isset($_GET['new'])):
  $patients = q_all('SELECT id, name, mrn FROM patients ORDER BY name'); ?>
  <div class="card stepper-card"><?= ui_stepper(IGD_STEPS, 0) ?></div>
  <form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="act" value="register">
    <div class="card-head"><div><h3>Pengelolaan Triase Pasien</h3><p>Pilih pasien terdaftar, atau isi identitas singkat untuk pasien baru (dilengkapi kemudian di registrasi).</p></div></div>
    <div class="card-body">
      <div class="form-grid">
        <div class="field full"><label>Pasien Terdaftar</label><select name="patient_id" id="nPid"><?= ui_options($patients, 'id', fn($p) => $p['name'] . ' — ' . $p['mrn'], null, '— pasien baru / tidak dikenal —') ?></select></div>
        <div class="field" data-new><label>Nama Pasien</label><input name="name" placeholder="Nama lengkap / 'Tn. X' bila tidak dikenal"></div>
        <div class="field" data-new><label>Jenis Kelamin</label><select name="gender"><option value="L">Laki-laki</option><option value="P">Perempuan</option></select></div>
        <div class="field" data-new><label>Perkiraan Usia (tahun)</label><input type="number" name="age" min="0" max="120"></div>
        <div class="field" data-new><label>NIK (bila ada)</label><input name="nik" inputmode="numeric" maxlength="16"></div>
      </div>
      <label class="label" style="display:block;margin:4px 0 8px">Tingkat Kedaruratan</label>
      <div class="checks"><?php foreach (IGD_LEVEL as $k => $l): ?><label class="check"><input type="radio" name="triage_level" value="<?= $k ?>" required> <span class="badge <?= ['merah' => 'danger', 'kuning' => 'warning', 'hijau' => 'success', 'hitam' => 'neutral'][$k] ?>"><?= e($l) ?></span></label><?php endforeach; ?></div>
      <div class="form-grid">
        <div class="field full"><label>Keluhan Utama</label><input name="chief_complaint" required></div>
        <div class="field"><label>GCS</label><input type="number" name="gcs" min="3" max="15" value="15"></div>
        <div class="field"><label>Cara Datang</label><select name="arrival_mode"><?= ui_opt_map(['sendiri' => 'Datang sendiri', 'ambulans' => 'Ambulans', 'rujukan' => 'Rujukan', 'polisi' => 'Diantar polisi']) ?></select></div>
      </div>
      <div class="form-grid" style="grid-template-columns:repeat(3,minmax(0,1fr))"><?php foreach (['systolic' => 'Sistolik (mmHg)', 'diastolic' => 'Diastolik (mmHg)', 'pulse' => 'Nadi (bpm)', 'respiration' => 'RR (x/mnt)', 'temperature' => 'Suhu (°C)', 'spo2' => 'SpO₂ (%)'] as $k => $l): ?>
        <div class="field"><label><?= e($l) ?></label><input type="number" step="any" name="<?= $k ?>"></div><?php endforeach; ?></div>
      <div class="field"><label>Catatan Triase</label><input name="notes" placeholder="Keterangan tambahan triase..."></div>
    </div>
    <div class="card-foot"><button class="btn btn-primary"><?= icon('siren', 16) ?> Simpan Triase</button></div></form>
  <script>(function(){const s=document.getElementById('nPid');const f=()=>document.querySelectorAll('[data-new]').forEach(el=>el.classList.toggle('hidden',!!s.value));s.onchange=f;f()})();</script>

<?php elseif (!$visit):
  $rows = q_all("SELECT v.*, p.name, p.mrn, p.birth_date, p.gender, t.triage_level, t.chief_complaint, t.disposition, t.triaged_at, t.actions, t.observation, d.name doctor
                 FROM visits v JOIN patients p ON p.id=v.patient_id LEFT JOIN triages t ON t.visit_id=v.id LEFT JOIN doctors d ON d.id=v.doctor_id
                 WHERE v.visit_type='igd' AND (v.status<>'selesai' OR DATE(v.visit_date)=CURDATE())
                 ORDER BY (v.status='selesai'), FIELD(t.triage_level,'merah','kuning','hijau','hitam'), v.visit_date");
  $c = fn($l) => count(array_filter($rows, fn($r) => $r['triage_level'] === $l && $r['status'] !== 'selesai')); ?>
  <section class="kpi-grid">
    <?= ui_kpi('P1 — Merah', $c('merah'), 'siren', 'danger', 'kritis') ?>
    <?= ui_kpi('P2 — Kuning', $c('kuning'), 'alert', 'warning', 'segera') ?>
    <?= ui_kpi('P3 — Hijau', $c('hijau'), 'heart', '', 'tidak mendesak') ?>
    <?= ui_kpi('Selesai Hari Ini', count(array_filter($rows, fn($r) => $r['status'] === 'selesai')), 'check', 'info') ?>
  </section>
  <div class="card"><div class="table-wrap"><table class="table"><thead><tr><th>Triase</th><th>Pasien</th><th>Keluhan Utama</th><th>Datang</th><th>Tahap</th><th>Keputusan</th><th>Status</th><th style="text-align:right">Aksi</th></tr></thead><tbody>
  <?php if (!$rows): ?><tr><td colspan="8" class="empty">Tidak ada pasien IGD.</td></tr><?php endif; foreach ($rows as $r): $st = igd_stage($r, $r['triage_level'] ? $r : null); ?>
    <tr><td><?= $r['triage_level'] ? '<span class="badge ' . ['merah' => 'danger', 'kuning' => 'warning', 'hijau' => 'success', 'hitam' => 'neutral'][$r['triage_level']] . '">' . e(explode(' (', IGD_LEVEL[$r['triage_level']])[0]) . '</span>' : '<span class="badge neutral">Belum</span>' ?></td>
      <td><b><?= e($r['name']) ?></b><br><small class="mono muted"><?= e($r['mrn']) ?> · <?= $r['gender'] ?> · <?= ui_age($r['birth_date']) ?></small></td><td><?= e($r['chief_complaint'] ?? $r['complaint'] ?? '-') ?></td>
      <td class="mono"><?= tgl($r['visit_date'], true) ?></td><td><?= e(IGD_STEPS[min($st, 3)]) ?></td><td><?= $r['disposition'] ? status_badge($r['disposition']) : '-' ?></td><td><?= status_badge($r['status']) ?></td>
      <td><div class="actions"><a class="btn btn-sm <?= $r['status'] !== 'selesai' ? 'btn-primary' : '' ?>" href="<?= e(ui_url('igd', ['v' => $r['id']])) ?>"><?= $r['status'] === 'selesai' ? 'Lihat' : 'Tangani' ?></a></div></td></tr>
  <?php endforeach; ?></tbody></table></div></div>

<?php else:
  $stage = igd_stage($visit, $tri);
  $patient = ui_patient_row((int)$visit['patient_id']);
  $rec = q_one('SELECT * FROM medical_records WHERE visit_id=? LIMIT 1', [$vid]);
  $vital = q_one('SELECT * FROM vital_signs WHERE visit_id=? ORDER BY recorded_at DESC, id DESC LIMIT 1', [$vid]);
  $hist = q_all("SELECT m.assessment, m.created_at, v.visit_type FROM medical_records m JOIN visits v ON v.id=m.visit_id WHERE m.patient_id=? AND m.visit_id<>? ORDER BY m.created_at DESC LIMIT 4", [$visit['patient_id'], $vid]);
  $lvlCls = ['merah' => 'danger', 'kuning' => 'warning', 'hijau' => 'success', 'hitam' => 'neutral']; ?>
  <div class="card stepper-card"><?= ui_stepper(IGD_STEPS, $stage) ?></div>
  <?= ui_patient($patient, $tri ? '<span class="badge ' . $lvlCls[$tri['triage_level']] . '">' . e(IGD_LEVEL[$tri['triage_level']]) . '</span>' : '') ?>
  <section class="grid grid-2">
    <div>
      <div class="card mb"><div class="card-head"><div><h3>Data Triase</h3><p><span class="mono"><?= e($visit['visit_no']) ?></span> · <?= tgl($tri['triaged_at'] ?? $visit['visit_date'], true) ?></p></div><?= status_badge($visit['status']) ?></div>
        <?= ui_details(['Keluhan Utama' => e($tri['chief_complaint'] ?? '-'), 'GCS' => '<span class="mono">' . e($tri['gcs'] ?? '-') . '</span>', 'Cara Datang' => e(ui_label($tri['arrival_mode'] ?? '-')), 'Catatan Triase' => e($tri['notes'] ?? '-'),
            'Vital Sign' => $vital ? '<span class="mono">TD ' . e($vital['systolic'] . '/' . $vital['diastolic']) . ' · N ' . e($vital['pulse']) . ' · S ' . e($vital['temperature']) . '°C · SpO₂ ' . e($vital['spo2']) . '%</span>' : '-',
            'Pemeriksaan' => e($rec['objective'] ?? '-'), 'Tindakan' => e($tri['actions'] ?? '-'), 'Diagnosis Klinis' => e($rec['assessment'] ?? '-'),
            'Observasi' => e($tri['observation'] ?? '-'), 'Kondisi' => e($tri['patient_condition'] ?? '-'), 'Keputusan' => $tri['disposition'] ? status_badge($tri['disposition']) : '-']) ?>
        <?php if ($tri && $visit['status'] !== 'selesai'): ?><form method="post" class="card-foot" style="justify-content:flex-start"><?= csrf_field() ?><input type="hidden" name="act" value="retriage">
          <select class="input" name="triage_level" style="height:36px;width:auto"><?= ui_opt_map(IGD_LEVEL, $tri['triage_level']) ?></select><button class="btn btn-sm">Re-triase</button></form><?php endif; ?></div>
      <div class="card"><div class="card-head"><h3>Riwayat Rekam Medis</h3></div><ul class="list"><?php if (!$hist): ?><li><span class="muted">Belum ada riwayat.</span></li><?php endif; foreach ($hist as $h): ?>
        <li><span class="l-dot info"></span><div class="l-main"><b><?= e($h['assessment'] ?? '-') ?></b><span><?= e(ui_label($h['visit_type'])) ?></span></div><time class="mono"><?= tgl($h['created_at']) ?></time></li><?php endforeach; ?></ul></div>
    </div>
    <div class="card">
    <?php if ($stage === 1): ?>
      <div class="card-head"><div><h3>Langkah 2 — Pemeriksaan & Tindakan Darurat</h3></div></div>
      <form method="post" class="card-body"><?= csrf_field() ?><input type="hidden" name="act" value="exam">
        <div class="form-grid" style="grid-template-columns:repeat(3,minmax(0,1fr))"><?php foreach (['systolic' => 'Sistolik', 'diastolic' => 'Diastolik', 'pulse' => 'Nadi', 'respiration' => 'RR', 'temperature' => 'Suhu', 'spo2' => 'SpO₂'] as $k => $l): ?><div class="field"><label><?= e($l) ?></label><input type="number" step="any" name="<?= $k ?>"></div><?php endforeach; ?></div>
        <div class="field"><label>Hasil Pemeriksaan</label><textarea name="exam"></textarea></div>
        <div class="field"><label>Tindakan yang Dilakukan</label><input name="actions" required placeholder="Pisahkan dengan koma (contoh: EKG, O2 nasal, Infus NaCl)"></div>
        <div class="field"><label>Diagnosis Klinis</label><input name="diagnosis" required placeholder="Tulis diagnosis klinis..."></div>
        <button class="btn btn-primary">Simpan Pemeriksaan</button> <a class="btn" href="<?= e(ui_url('cpoe', ['v' => $vid, 'step' => 1])) ?>">Order Penunjang (CPOE)</a></form>
    <?php elseif ($stage === 2): ?>
      <div class="card-head"><div><h3>Langkah 3 — Observasi & Keputusan Pasien</h3></div></div>
      <form method="post" class="card-body"><?= csrf_field() ?><input type="hidden" name="act" value="observe">
        <div class="field"><label>Hasil Observasi</label><textarea name="observation" required placeholder="Catatan perkembangan kondisi pasien selama observasi..."></textarea></div>
        <div class="form-grid"><div class="field"><label>Kondisi Pasien</label><select name="condition"><?= ui_opt_map(array_combine($k = ['Membaik', 'Stabil', 'Tidak ada perubahan', 'Memburuk'], $k)) ?></select></div>
          <div class="field"><label>Keputusan</label><select name="disposition" required><?= ui_opt_map(['pulang' => 'Dipulangkan', 'rawat_inap' => 'Rawat Inap', 'rujuk' => 'Rujuk ke RS lain', 'observasi' => 'Lanjut observasi'], $tri['disposition'] ?? null) ?></select></div></div>
        <button class="btn btn-primary">Simpan Observasi</button></form>
    <?php elseif ($stage === 3 && $isDoc): ?>
      <div class="card-head"><div><h3>Langkah 4 — Finalisasi ICD</h3><p>Keputusan: <?= status_badge($tri['disposition']) ?></p></div></div>
      <form method="post" class="card-body"><?= csrf_field() ?><input type="hidden" name="act" value="finalize">
        <div class="form-grid"><div class="field"><label>Kode ICD-10</label><input name="icd" required data-rule="icd" list="icdL" placeholder="Ketik atau cari kode... (contoh: I21.9)"></div>
          <div class="field"><label>Diagnosis Final</label><input name="final_dx" value="<?= e($rec['assessment'] ?? '') ?>" placeholder="Diagnosis klinis final..."></div>
          <?php if ($tri['disposition'] === 'rujuk'): ?><div class="field full"><label>RS Rujukan</label><input name="destination" required></div><?php endif; ?></div>
        <datalist id="icdL"><?php foreach (q_all('SELECT code, description FROM icd10 ORDER BY code') as $i): ?><option value="<?= e($i['code']) ?>"><?= e($i['description']) ?></option><?php endforeach; ?></datalist>
        <button class="btn btn-primary"><?= icon('check', 16) ?> Finalisasi & Simpan</button></form>
    <?php elseif ($stage === 3): ?>
      <div class="card-body"><div class="alert info">Menunggu finalisasi diagnosis ICD-10 oleh dokter IGD.</div></div>
    <?php else: ?>
      <div class="card-head"><div><h3>Pelayanan IGD Selesai</h3></div></div><div class="card-body"><a class="btn" href="<?= e(ui_url('igd')) ?>">Kembali ke daftar</a></div>
    <?php endif; ?>
    </div>
  </section>
<?php endif; ?>
</div>
<?php layout_end();
