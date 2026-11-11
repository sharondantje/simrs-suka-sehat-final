<?php
/*
 * MODUL 13 — NURSING / ASUHAN KEPERAWATAN (database)
 * Stepper: Identifikasi & Persiapan → Assessment & Vital Sign → Nursing Note → Pemberian Obat → Monitoring Pasien → Finalisasi Dokumentasi
 * Data: nursing_notes (note_type), vital_signs, medication_administrations
 */
const NUR_STEPS = ['Identifikasi & Persiapan', 'Assessment & Vital Sign', 'Nursing Note', 'Pemberian Obat', 'Monitoring Pasien', 'Finalisasi'];
const NUR_TYPES = ['identifikasi', 'assessment', 'catatan', 'obat', 'monitoring', 'finalisasi'];
const NUR_MOD   = 'Nursing';

$me  = (int)$user['id'];
$vid = (int)($_GET['v'] ?? 0);
$visit = $vid ? q_one('SELECT v.*, po.name poli FROM visits v LEFT JOIN poli po ON po.id=v.poli_id WHERE v.id=?', [$vid]) : null;
if ($vid && !$visit) redirect(ui_url('nursing'));
$adm = $visit ? q_one("SELECT a.*, b.bed_number, r.name room FROM admissions a LEFT JOIN beds b ON b.id=a.bed_id LEFT JOIN rooms r ON r.id=b.room_id WHERE a.visit_id=? AND a.status='dirawat'", [$vid]) : null;

/** Progres hari ini: langkah yang sudah terdokumentasi pada kunjungan ini (shift berjalan). */
function nur_done(int $vid): array
{
    $types = array_column(q_all('SELECT DISTINCT note_type FROM nursing_notes WHERE visit_id=? AND DATE(created_at)=CURDATE()', [$vid]), 'note_type');
    $med = (int)q_val("SELECT COUNT(*) FROM medication_administrations m JOIN admissions a ON a.id=m.admission_id WHERE a.visit_id=? AND m.status IN ('diberikan','ditunda','ditolak') AND DATE(COALESCE(m.given_at, m.scheduled_at))=CURDATE()", [$vid]);
    $done = [];
    foreach (NUR_TYPES as $i => $t) $done[$i] = $t === 'obat' ? ($med > 0 || in_array('obat', $types, true)) : in_array($t, $types, true);
    return $done;
}
$done = $visit ? nur_done($vid) : [];
$first = 0; foreach ($done as $i => $d) { if (!$d) { $first = $i; break; } $first = $i + 1; }
$step = isset($_GET['step']) ? max(0, min(5, (int)$_GET['step'])) : min($first, 5);
$go = fn(int $s) => ui_url('nursing', ['v' => $vid, 'step' => $s]);

if ($act = ui_post_action()) {
    $f = fn($k) => trim((string)($_POST[$k] ?? '')) ?: null;
    $note = function (string $type, ?string $a, ?string $dx = null, ?string $iv = null, ?string $ev = null) use ($vid, $visit, $me) {
        q('INSERT INTO nursing_notes (note_type, visit_id, patient_id, nurse_id, assessment, nursing_diagnosis, intervention, evaluation) VALUES (?,?,?,?,?,?,?,?)',
          [$type, $vid, $visit['patient_id'], $me, $a, $dx, $iv, $ev]);
    };
    $vitals = function () use ($vid, $visit, $me) {
        $k = ['systolic', 'diastolic', 'pulse', 'respiration', 'temperature', 'spo2'];
        $v = array_map(fn($x) => ($_POST[$x] ?? '') === '' ? null : $_POST[$x], $k);
        if (array_filter($v, fn($x) => $x !== null)) q('INSERT INTO vital_signs (visit_id, patient_id, recorded_by, systolic, diastolic, pulse, respiration, temperature, spo2) VALUES (?,?,?,?,?,?,?,?,?)', [$vid, $visit['patient_id'], $me, ...$v]);
    };
    if ($act === 'identifikasi') {
        if (count((array)($_POST['check'] ?? [])) < 3) { flash('danger', 'Lengkapi checklist identifikasi pasien.'); redirect($go(0)); }
        $note('identifikasi', 'Checklist: ' . implode(', ', (array)$_POST['check']) . ($f('notes') ? '. ' . $f('notes') : ''));
        $step = 1;
    } elseif ($act === 'assessment') {
        if (!$f('complaint') || ($_POST['systolic'] ?? '') === '') { flash('danger', 'Keluhan dan tanda vital wajib diisi.'); redirect($go(1)); }
        $vitals();
        $note('assessment', 'Keluhan: ' . $f('complaint') . '; Kondisi umum: ' . ($f('condition') ?? '-') . ($f('history') ? '; Riwayat: ' . $f('history') : ''));
        $step = 2;
    } elseif ($act === 'catatan') {
        if (!$f('diagnosis') || !$f('intervention')) { flash('danger', 'Diagnosis keperawatan dan intervensi wajib diisi.'); redirect($go(2)); }
        $note('catatan', $f('assessment'), $f('diagnosis'), $f('intervention'), $f('evaluation'));
        $step = 3;
    } elseif ($act === 'obat') {
        $mid = (int)($_POST['mid'] ?? 0);
        if ($mid) {
            if (count((array)($_POST['check'] ?? [])) < 4) { flash('danger', 'Verifikasi prinsip benar (pasien, obat, dosis, rute & waktu) sebelum memberikan obat.'); redirect($go(3)); }
            $stt = in_array($_POST['status'] ?? '', ['diberikan', 'ditunda', 'ditolak'], true) ? $_POST['status'] : 'diberikan';
            q('UPDATE medication_administrations SET status=?, given_at=?, nurse_id=?, route=?, response=? WHERE id=?',
              [$stt, $stt === 'diberikan' ? str_replace('T', ' ', (string)($_POST['given_at'] ?: date('Y-m-d H:i'))) : null, $me, $f('route'), $f('response'), $mid]);
            audit('UPDATE', NUR_MOD, "Pemberian obat #$mid: $stt");
            flash('success', 'Pemberian obat tercatat.');
            redirect($go(3));
        }
        $note('obat', 'Tidak ada jadwal pemberian obat / dilewati: ' . ($f('notes') ?? 'pasien bukan rawat inap'));
        $step = 4;
    } elseif ($act === 'schedule' && $adm) {
        q("INSERT INTO medication_administrations (admission_id, drug_id, dose, scheduled_at, status) VALUES (?,?,?,?,'terjadwal')",
          [$adm['id'], (int)$_POST['drug_id'], $f('dose') ?? '-', str_replace('T', ' ', (string)$_POST['scheduled_at'])]);
        flash('success', 'Jadwal pemberian obat ditambahkan.');
        redirect($go(3));
    } elseif ($act === 'monitoring') {
        if (!$f('result')) { flash('danger', 'Hasil monitoring wajib diisi.'); redirect($go(4)); }
        $vitals();
        $note('monitoring', $f('result'), null, null, $f('change'));
        $step = 5;
    } elseif ($act === 'finalisasi') {
        $miss = array_keys(array_filter(array_slice(nur_done($vid), 0, 5), fn($d) => !$d));
        if ($miss) { flash('danger', 'Dokumentasi belum lengkap: ' . implode(', ', array_map(fn($i) => NUR_STEPS[$i], $miss)) . '.'); redirect($go(5)); }
        $note('finalisasi', 'Dokumentasi shift divalidasi' . ($f('handover') ? '. Serah terima: ' . $f('handover') : ''));
        audit('VALIDATE', NUR_MOD, 'Finalisasi dokumentasi keperawatan ' . $visit['visit_no']);
        flash('success', 'Dokumentasi keperawatan shift ini telah difinalisasi.');
        redirect($go(5));
    }
    audit('CREATE', NUR_MOD, 'Asuhan keperawatan (' . $act . ') ' . $visit['visit_no']);
    flash('success', 'Tersimpan. Lanjut ke ' . NUR_STEPS[$step] . '.');
    redirect($go($step));
}

layout_start($PAGE['title'], 'nursing', $PAGE['section']);
?>
<div class="content">
  <div class="page-head"><div><h2>Asuhan Keperawatan</h2><p>Dokumentasi keperawatan per shift: identifikasi, assessment, catatan, pemberian obat, monitoring, dan finalisasi.</p></div>
    <?php if ($visit): ?><div class="page-actions"><a class="btn" href="<?= e(ui_url('nursing')) ?>">← Ganti Pasien</a></div><?php endif; ?></div>
  <?php flashes(); ?>

<?php if (!$visit):
  $rows = q_all("SELECT v.*, p.name, p.mrn, p.birth_date, po.name poli, b.bed_number, r.name room,
                        (SELECT COUNT(DISTINCT note_type) FROM nursing_notes n WHERE n.visit_id=v.id AND DATE(n.created_at)=CURDATE()) prog,
                        (SELECT COUNT(*) FROM nursing_notes n WHERE n.visit_id=v.id AND n.note_type='finalisasi' AND DATE(n.created_at)=CURDATE()) fin
                 FROM visits v JOIN patients p ON p.id=v.patient_id LEFT JOIN poli po ON po.id=v.poli_id
                 LEFT JOIN admissions a ON a.visit_id=v.id AND a.status='dirawat' LEFT JOIN beds b ON b.id=a.bed_id LEFT JOIN rooms r ON r.id=b.room_id
                 WHERE v.status IN ('dirawat','diperiksa','menunggu','dipanggil') ORDER BY (v.visit_type='rawat_inap') DESC, (v.visit_type='igd') DESC, v.visit_date DESC"); ?>
  <div class="card"><div class="card-head"><div><h3>Pasien Aktif</h3><p>Pilih pasien untuk mulai/melanjutkan dokumentasi shift ini.</p></div><span class="badge"><?= count($rows) ?> pasien</span></div>
    <div class="table-wrap"><table class="table"><thead><tr><th>Pasien</th><th>Layanan</th><th>Ruang / Bed</th><th>Progres Shift</th><th>Status</th><th style="text-align:right">Aksi</th></tr></thead><tbody>
    <?php foreach ($rows as $r): ?><tr><td><b><?= e($r['name']) ?></b><br><small class="mono muted"><?= e($r['mrn']) ?> · <?= ui_age($r['birth_date']) ?></small></td>
      <td><?= e(ui_label($r['visit_type'])) ?></td><td><?= $r['room'] ? e($r['room']) . ' · <span class="mono">' . e($r['bed_number']) . '</span>' : e($r['poli'] ?? '-') ?></td>
      <td style="min-width:150px"><div class="progress"><div style="width:<?= $r['fin'] ? 100 : min(100, round($r['prog'] / 6 * 100)) ?>%"></div></div><small class="muted mono"><?= $r['fin'] ? 'Final' : (int)$r['prog'] . '/6 langkah' ?></small></td>
      <td><?= status_badge($r['status']) ?></td><td><div class="actions"><a class="btn btn-sm btn-primary" href="<?= e(ui_url('nursing', ['v' => $r['id']])) ?>">Pilih</a></div></td></tr><?php endforeach; ?>
    </tbody></table></div></div>

<?php else: $patient = ui_patient_row((int)$visit['patient_id']); ?>
  <div class="card stepper-card"><div class="stepper"><?php foreach (NUR_STEPS as $i => $l): ?>
    <a class="step <?= $i === $step ? 'active' : ($done[$i] ? 'done' : '') ?>" href="<?= e($go($i)) ?>"><i><?= $done[$i] && $i !== $step ? '✓' : $i + 1 ?></i><span><?= e($l) ?></span></a><?php endforeach; ?></div></div>
  <?= ui_patient($patient, $adm ? '<span class="badge info">' . e($adm['room']) . ' · ' . e($adm['bed_number']) . '</span>' : '<span class="badge info">' . e(ui_label($visit['visit_type'])) . '</span>') ?>

  <?php if ($step === 0): ?>
  <form method="post" class="card" style="max-width:760px"><?= csrf_field() ?><input type="hidden" name="act" value="identifikasi">
    <div class="card-head"><div><h3>Identifikasi & Persiapan</h3><p>Verifikasi identitas pasien sebelum tindakan keperawatan.</p></div></div>
    <div class="card-body"><div class="checks"><?php foreach (['Gelang identitas sesuai (nama & tgl lahir)', 'Alergi telah diperiksa', 'Risiko jatuh dinilai', 'Informed consent / edukasi diberikan'] as $c): ?>
      <label class="check"><input type="checkbox" name="check[]" value="<?= e($c) ?>"> <?= e($c) ?></label><?php endforeach; ?></div>
      <div class="field"><label>Catatan</label><input name="notes" placeholder="Opsional"></div></div>
    <div class="card-foot"><button class="btn btn-primary">Simpan & Lanjut →</button></div></form>

  <?php elseif ($step === 1): ?>
  <form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="act" value="assessment">
    <div class="card-head"><div><h3>Assessment & Vital Sign</h3><p>Pengkajian awal keperawatan.</p></div></div>
    <div class="card-body"><div class="form-grid" style="grid-template-columns:repeat(3,minmax(0,1fr))">
      <?php foreach (['systolic' => 'Sistolik (mmHg)', 'diastolic' => 'Diastolik (mmHg)', 'pulse' => 'Nadi (x/mnt)', 'respiration' => 'RR (x/mnt)', 'temperature' => 'Suhu (°C)', 'spo2' => 'SpO₂ (%)'] as $k => $l): ?>
        <div class="field"><label><?= e($l) ?></label><input type="number" step="any" name="<?= $k ?>" <?= in_array($k, ['systolic', 'diastolic', 'pulse', 'temperature'], true) ? 'required' : '' ?>></div><?php endforeach; ?></div>
      <div class="form-grid"><div class="field"><label>Keluhan Utama</label><textarea name="complaint" required><?= e($visit['complaint'] ?? '') ?></textarea></div>
        <div class="field"><label>Riwayat yang Relevan</label><textarea name="history"></textarea></div>
        <div class="field"><label>Kondisi Umum</label><select name="condition"><?= ui_opt_map(array_combine($k = ['Baik', 'Sedang', 'Lemah', 'Buruk'], $k)) ?></select></div></div></div>
    <div class="card-foot"><button class="btn btn-primary">Simpan Assessment →</button></div></form>

  <?php elseif ($step === 2): ?>
  <form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="act" value="catatan">
    <div class="card-head"><div><h3>Nursing Note</h3><p>Diagnosis keperawatan, intervensi, dan evaluasi.</p></div></div>
    <div class="card-body"><div class="form-grid">
      <div class="field"><label>Data Fokus / Assessment</label><textarea name="assessment"></textarea></div>
      <div class="field"><label>Diagnosis Keperawatan</label><input name="diagnosis" required list="dxList" placeholder="Contoh: Nyeri akut"><datalist id="dxList"><?php foreach (['Nyeri akut', 'Bersihan jalan napas tidak efektif', 'Hipertermia', 'Risiko perdarahan', 'Risiko jatuh', 'Intoleransi aktivitas', 'Ansietas', 'Risiko infeksi'] as $d): ?><option value="<?= e($d) ?>"><?php endforeach; ?></datalist></div>
      <div class="field"><label>Intervensi</label><textarea name="intervention" required></textarea></div>
      <div class="field"><label>Evaluasi</label><textarea name="evaluation"></textarea></div></div></div>
    <div class="card-foot"><button class="btn btn-primary">Simpan Catatan →</button></div></form>

  <?php elseif ($step === 3):
    $meds = $adm ? q_all('SELECT m.*, d.name drug FROM medication_administrations m JOIN drugs d ON d.id=m.drug_id WHERE m.admission_id=? AND DATE(m.scheduled_at) >= CURDATE() - INTERVAL 1 DAY ORDER BY m.scheduled_at', [$adm['id']]) : []; ?>
  <div class="card mb"><div class="card-head"><div><h3>Pemberian Obat</h3><p><?= $adm ? 'Jadwal pemberian obat pasien rawat inap.' : 'Pasien bukan rawat inap — tidak ada jadwal MAR.' ?></p></div>
    <?php if ($adm): ?><button class="btn btn-sm" data-modal="mSched"><?= icon('plus', 14) ?> Jadwal Obat</button><?php endif; ?></div>
    <div class="table-wrap"><table class="table"><thead><tr><th>Jadwal</th><th>Obat</th><th>Dosis</th><th>Rute</th><th>Status</th><th>Respons</th><th style="text-align:right">Aksi</th></tr></thead><tbody>
    <?php if (!$meds): ?><tr><td colspan="7" class="empty">Tidak ada jadwal pemberian obat.</td></tr><?php endif; foreach ($meds as $m): ?>
      <tr><td class="mono"><?= tgl($m['scheduled_at'], true) ?></td><td class="strong"><?= e($m['drug']) ?></td><td><?= e($m['dose']) ?></td><td><?= e($m['route'] ?? '-') ?></td><td><?= status_badge($m['status']) ?></td><td><?= e($m['response'] ?? '-') ?></td>
        <td><div class="actions"><?php if ($m['status'] === 'terjadwal'): ?><button class="btn btn-sm btn-primary" data-modal="mGive" onclick='give(<?= json_encode(['id' => $m['id'], 'd' => $m['drug'] . ' ' . $m['dose']], JSON_HEX_APOS) ?>)'>Berikan</button><?php endif; ?></div></td></tr><?php endforeach; ?>
    </tbody></table></div>
    <form method="post" class="card-foot"><?= csrf_field() ?><input type="hidden" name="act" value="obat"><input class="input" name="notes" placeholder="Keterangan (opsional)" style="height:38px;flex:1">
      <button class="btn btn-primary"><?= $meds ? 'Lanjut ke Monitoring →' : 'Lewati — Lanjut ke Monitoring →' ?></button></form></div>
  <div class="modal" id="mGive"><div class="modal-box"><div class="card-head"><h3>Data Pemberian Obat</h3><button class="icon-btn" data-close type="button">✕</button></div>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="act" value="obat"><input type="hidden" name="mid" id="gId">
      <p class="strong" id="gD" style="margin:0 0 10px"></p><label class="label">Verifikasi sebelum pemberian</label>
      <div class="checks" style="margin-top:8px"><?php foreach (['Pasien benar', 'Obat benar', 'Dosis benar', 'Rute & waktu benar'] as $c): ?><label class="check"><input type="checkbox" name="check[]" value="<?= e($c) ?>"> <?= e($c) ?></label><?php endforeach; ?></div>
      <div class="form-grid"><div class="field"><label>Status</label><select name="status"><?= ui_opt_map(['diberikan' => 'Diberikan', 'ditunda' => 'Ditunda', 'ditolak' => 'Ditolak pasien']) ?></select></div>
        <div class="field"><label>Rute</label><select name="route"><?= ui_opt_map(array_combine($k = ['Oral', 'IV', 'IM', 'SC', 'Inhalasi', 'Topikal'], $k)) ?></select></div>
        <div class="field"><label>Waktu Pemberian</label><input type="datetime-local" name="given_at" value="<?= date('Y-m-d\TH:i') ?>"></div>
        <div class="field"><label>Respons Pasien / Observasi</label><input name="response" placeholder="Contoh: tidak ada reaksi alergi"></div></div>
      <div class="modal-foot"><button type="button" class="btn" data-close>Batal</button><button class="btn btn-primary">Simpan Pemberian Obat</button></div></form></div></div>
  <?php if ($adm): ?><div class="modal" id="mSched"><div class="modal-box"><div class="card-head"><h3>Tambah Jadwal Obat</h3><button class="icon-btn" data-close type="button">✕</button></div>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="act" value="schedule"><div class="form-grid">
      <div class="field full"><label>Obat</label><select name="drug_id" required><?= ui_options(q_all('SELECT id, name FROM drugs ORDER BY name'), 'id', 'name') ?></select></div>
      <div class="field"><label>Dosis</label><input name="dose" required placeholder="500 mg PO"></div><div class="field"><label>Jadwal</label><input type="datetime-local" name="scheduled_at" required value="<?= date('Y-m-d\TH:00', strtotime('+1 hour')) ?>"></div></div>
      <div class="modal-foot"><button type="button" class="btn" data-close>Batal</button><button class="btn btn-primary">Simpan</button></div></form></div></div><?php endif; ?>
  <script>function give(m){gId.value=m.id;gD.textContent=m.d}</script>

  <?php elseif ($step === 4): ?>
  <form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="act" value="monitoring">
    <div class="card-head"><div><h3>Monitoring Pasien</h3><p>Vital sign ulang dan perubahan kondisi.</p></div></div>
    <div class="card-body"><div class="form-grid" style="grid-template-columns:repeat(3,minmax(0,1fr))">
      <?php foreach (['systolic' => 'Sistolik', 'diastolic' => 'Diastolik', 'pulse' => 'Nadi', 'respiration' => 'RR', 'temperature' => 'Suhu', 'spo2' => 'SpO₂'] as $k => $l): ?><div class="field"><label><?= e($l) ?></label><input type="number" step="any" name="<?= $k ?>"></div><?php endforeach; ?></div>
      <div class="form-grid"><div class="field"><label>Hasil Monitoring</label><textarea name="result" required></textarea></div><div class="field"><label>Perubahan Kondisi</label><select name="change"><?= ui_opt_map(array_combine($k = ['Tidak ada perubahan', 'Membaik', 'Memburuk — dilaporkan ke DPJP'], $k)) ?></select></div></div></div>
    <div class="card-foot"><button class="btn btn-primary">Simpan Monitoring →</button></div></form>

  <?php else:
    $notes = q_all('SELECT n.*, u.full_name FROM nursing_notes n LEFT JOIN users u ON u.id=n.nurse_id WHERE n.visit_id=? AND DATE(n.created_at)=CURDATE() ORDER BY n.created_at', [$vid]);
    $vit = q_all('SELECT * FROM vital_signs WHERE visit_id=? AND DATE(recorded_at)=CURDATE() ORDER BY recorded_at', [$vid]);
    $final = in_array('finalisasi', array_column($notes, 'note_type'), true); ?>
  <div class="card"><div class="card-head"><div><h3>Review & Finalisasi Dokumentasi</h3><p>Seluruh dokumentasi shift hari ini.</p></div><?= $final ? status_badge('final') : status_badge('draft') ?></div>
    <ul class="list"><?php foreach ($notes as $n): ?><li><span class="l-dot <?= $n['note_type'] === 'finalisasi' ? '' : 'info' ?>"></span><div class="l-main"><b><?= e(ui_label($n['note_type'])) ?><?= $n['nursing_diagnosis'] ? ' — ' . e($n['nursing_diagnosis']) : '' ?></b>
      <span><?= e(implode(' · ', array_filter([$n['assessment'], $n['intervention'], $n['evaluation']]))) ?> — <?= e($n['full_name'] ?? '-') ?></span></div><time class="mono"><?= date('H:i', strtotime($n['created_at'])) ?></time></li><?php endforeach; ?>
    <?php foreach ($vit as $v): ?><li><span class="l-dot warning"></span><div class="l-main"><b>Vital sign</b><span class="mono">TD <?= e($v['systolic'] . '/' . $v['diastolic']) ?> · N <?= e($v['pulse']) ?> · RR <?= e($v['respiration']) ?> · S <?= e($v['temperature']) ?> · SpO₂ <?= e($v['spo2']) ?></span></div><time class="mono"><?= date('H:i', strtotime($v['recorded_at'])) ?></time></li><?php endforeach; ?></ul>
    <?php if (!$final): ?><form method="post" class="card-foot" style="justify-content:space-between"><?= csrf_field() ?><input type="hidden" name="act" value="finalisasi">
      <input class="input" name="handover" placeholder="Catatan serah terima shift (opsional)" style="flex:1;height:38px"><button class="btn btn-primary"><?= icon('check', 16) ?> Finalisasi Dokumentasi</button></form><?php endif; ?></div>
  <?php endif; ?>
<?php endif; ?>
</div>
<?php layout_end();
