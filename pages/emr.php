<?php
/*
 * MODUL 3 — ELECTRONIC MEDICAL RECORD (database)
 * Stepper: Pilih Pasien → Verifikasi Identitas → Tinjau Data → Input Catatan (SOAP, vital sign, ICD-10, dokumen)
 *          → Konsultasi AI CDSS (opsional, rule-based) → Validasi & Simpan
 * Dokter mengisi SOAP & diagnosis; perawat meninjau rekam medis dan mencatat vital sign.
 */
const EMR_STEPS = ['Pilih Pasien', 'Verifikasi Identitas', 'Tinjau Data', 'Input Catatan', 'AI CDSS', 'Validasi & Simpan'];
const EMR_MOD   = 'EMR';

$isDoc = $user['role'] === 'dokter';
$me    = (int)$user['id'];
$myDoc = (int)q_val('SELECT id FROM doctors WHERE user_id=?', [$me]);
$vid   = (int)($_GET['v'] ?? 0);
$step  = max(0, min(5, (int)($_GET['step'] ?? ($vid ? 1 : 0))));
$visit = $vid ? q_one('SELECT v.*, po.name poli, d.name doctor FROM visits v LEFT JOIN poli po ON po.id=v.poli_id LEFT JOIN doctors d ON d.id=v.doctor_id WHERE v.id=?', [$vid]) : null;
if ($vid && !$visit) redirect(ui_url('emr'));
$verified = $vid && !empty($_SESSION['emr_verified'][$vid]);
if ($vid && $step > 1 && !$verified) $step = 1;                 // wajib verifikasi identitas dahulu
if (!$isDoc && $step > 3) $step = 3;                             // perawat berhenti di input vital sign
$go = fn(int $s, array $x = []) => ui_url('emr', ['v' => $vid, 'step' => $s] + $x);

/** Saran CDSS berbasis aturan dari vital sign, diagnosis, alergi, dan resep aktif. */
function emr_cdss(?array $vs, array $codes, string $allergy, array $drugs, string $text): array
{
    $out = [];
    if ($vs) {
        if ($vs['systolic'] >= 180 || $vs['diastolic'] >= 120) $out[] = ['danger', 'Krisis hipertensi (TD ' . $vs['systolic'] . '/' . $vs['diastolic'] . '). Pertimbangkan penanganan segera / rujuk IGD.'];
        elseif ($vs['systolic'] >= 140 || $vs['diastolic'] >= 90) $out[] = ['warning', 'Tekanan darah tinggi (' . $vs['systolic'] . '/' . $vs['diastolic'] . ' mmHg). Evaluasi hipertensi (I10) dan kepatuhan obat.'];
        if ($vs['temperature'] >= 38) $out[] = ['warning', 'Demam ' . $vs['temperature'] . '°C. Pertimbangkan pemeriksaan darah lengkap / NS1 bila curiga dengue.'];
        if ($vs['spo2'] && $vs['spo2'] < 94) $out[] = ['danger', 'SpO₂ ' . $vs['spo2'] . '% (hipoksemia). Berikan O₂ dan pertimbangkan foto thorax.'];
        if ($vs['pulse'] > 100) $out[] = ['warning', 'Takikardia (nadi ' . $vs['pulse'] . ' x/mnt).'];
        if ($vs['weight'] && $vs['height']) {
            $bmi = $vs['weight'] / pow($vs['height'] / 100, 2);
            if ($bmi >= 25) $out[] = ['info', 'IMT ' . number_format($bmi, 1) . ' (obesitas). Edukasi diet & aktivitas fisik.'];
        }
    }
    $rules = [
        'E11' => 'Diabetes melitus tipe 2: periksa HbA1c / GDP berkala dan skrining komplikasi (mata, ginjal, kaki).',
        'I10' => 'Hipertensi: target TD < 140/90, evaluasi fungsi ginjal & profil lipid.',
        'A90' => 'Demam dengue: pantau trombosit & hematokrit serial, edukasi tanda bahaya.',
        'A91' => 'DBD: pantau trombosit/hematokrit tiap 12–24 jam, cairan adekuat.',
        'J18' => 'Pneumonia: pertimbangkan foto thorax, kultur sputum, dan antibiotik empiris sesuai pedoman.',
        'I21' => 'Infark miokard akut: EKG serial, troponin, konsul jantung segera.',
        'J45' => 'Asma: nilai derajat serangan, pertimbangkan bronkodilator inhalasi.',
        'K29' => 'Gastritis: hindari NSAID, pertimbangkan PPI.',
    ];
    foreach ($codes as $c) foreach ($rules as $k => $t) if (str_starts_with($c, $k)) $out[] = ['info', "[$c] $t"];
    $allergy = strtolower(trim($allergy));
    if ($allergy !== '' && !in_array($allergy, ['-', 'tidak ada'], true)) {
        foreach ($drugs as $d) foreach (preg_split('/[,;]+/', $allergy) as $a) {
            $a = trim($a);
            if ($a !== '' && (stripos($d, $a) !== false || stripos($a, strtok(strtolower($d), ' ')) !== false))
                $out[] = ['danger', "Peringatan alergi: pasien alergi \"$a\" dan mendapat resep $d."];
        }
    }
    if (preg_match('/nyeri dada/i', $text)) $out[] = ['danger', 'Keluhan nyeri dada: singkirkan sindrom koroner akut (EKG 12 sadapan).'];
    if (!$out) $out[] = ['success', 'Tidak ada peringatan klinis dari aturan CDSS untuk data saat ini.'];
    return $out;
}

/* ------------------------------ AKSI ------------------------------ */
if ($act = ui_post_action()) {
    if ($act === 'verify') {
        if (count((array)($_POST['check'] ?? [])) < 2) { flash('danger', 'Minimal dua identitas (nama & tanggal lahir/No. RM) wajib dicocokkan.'); redirect($go(1)); }
        $_SESSION['emr_verified'][$vid] = true;
        audit('VERIFY', EMR_MOD, 'Verifikasi identitas pasien kunjungan ' . $visit['visit_no']);
        redirect($go(2));
    }
    if ($act === 'vitals' || $act === 'note') {
        $vs = array_combine(['systolic', 'diastolic', 'pulse', 'respiration', 'temperature', 'spo2', 'weight', 'height'], array_map(fn($k) => ($_POST[$k] ?? '') === '' ? null : $_POST[$k], ['systolic', 'diastolic', 'pulse', 'respiration', 'temperature', 'spo2', 'weight', 'height']));
        if (array_filter($vs, fn($x) => $x !== null)) {
            q('INSERT INTO vital_signs (visit_id, patient_id, recorded_by, systolic, diastolic, pulse, respiration, temperature, spo2, weight, height) VALUES (?,?,?,?,?,?,?,?,?,?,?)',
              [$vid, $visit['patient_id'], $me, ...array_values($vs)]);
        }
        if ($act === 'vitals') {
            audit('CREATE', EMR_MOD, 'Vital sign ' . $visit['visit_no']);
            flash('success', 'Vital sign tersimpan ke rekam medis.');
            redirect($go(2));
        }
        if (!$isDoc) redirect($go(2));
        $f = fn($k) => trim((string)($_POST[$k] ?? '')) ?: null;
        if (!$f('subjective') || !$f('assessment')) { flash('danger', 'Keluhan (S) dan Assessment (A) wajib diisi.'); redirect($go(3)); }
        $codes = array_values(array_unique(array_filter(array_map('trim', (array)($_POST['icd'] ?? [])))));
        foreach ($codes as $c) if (!q_val('SELECT 1 FROM icd10 WHERE code=?', [$c])) { flash('danger', "Kode ICD-10 $c tidak ada di master data."); redirect($go(3)); }
        $rid = ui_tx(function () use ($vid, $visit, $myDoc, $f, $codes) {
            $rid = (int)q_val('SELECT id FROM medical_records WHERE visit_id=? ORDER BY id DESC LIMIT 1', [$vid]);
            $data = [$f('subjective'), $f('objective'), $f('assessment'), $f('plan'), $f('exam_result')];
            if ($rid) q("UPDATE medical_records SET subjective=?, objective=?, assessment=?, plan=?, exam_result=?, status='draft' WHERE id=?", [...$data, $rid]);
            else { q("INSERT INTO medical_records (visit_id, patient_id, doctor_id, subjective, objective, assessment, plan, exam_result, status) VALUES (?,?,?,?,?,?,?,?, 'draft')",
                     [$vid, $visit['patient_id'], $myDoc ?: $visit['doctor_id'], ...$data]); $rid = (int)db()->lastInsertId(); }
            q('DELETE FROM diagnoses WHERE record_id=?', [$rid]);
            foreach ($codes as $i => $c) q('INSERT INTO diagnoses (record_id, icd10_code, type) VALUES (?,?,?)', [$rid, $c, $i === 0 ? 'primer' : 'sekunder']);
            if (in_array($visit['status'], ['menunggu', 'dipanggil'], true)) q("UPDATE visits SET status='diperiksa' WHERE id=?", [$vid]);
            return $rid;
        });
        if (!empty($_FILES['doc']['name']) && $_FILES['doc']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['doc']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'], true) && $_FILES['doc']['size'] <= 5 * 1024 * 1024) {
                $dir = dirname(__DIR__) . '/uploads/dokumen'; if (!is_dir($dir)) mkdir($dir, 0775, true);
                $name = 'DOC-' . $vid . '-' . time() . '.' . $ext;
                move_uploaded_file($_FILES['doc']['tmp_name'], "$dir/$name");
                q('INSERT INTO patient_documents (patient_id, visit_id, title, file_path) VALUES (?,?,?,?)', [$visit['patient_id'], $vid, trim((string)($_POST['doc_title'] ?? '')) ?: $_FILES['doc']['name'], "uploads/dokumen/$name"]);
            } else flash('warning', 'Dokumen tidak diunggah: hanya PDF/JPG/PNG maks. 5 MB.');
        }
        audit('UPDATE', EMR_MOD, 'Simpan draft rekam medis ' . $visit['visit_no']);
        flash('success', 'Catatan medis tersimpan sebagai draft.');
        redirect($go(($_POST['next'] ?? '') === 'cdss' ? 4 : 3));
    }
    if ($act === 'apply_cdss') {
        $add = trim((string)($_POST['suggestion'] ?? ''));
        if ($add !== '') q("UPDATE medical_records SET plan=CONCAT_WS('\n', plan, ?) WHERE visit_id=? ORDER BY id DESC LIMIT 1", ['[CDSS] ' . $add, $vid]);
        audit('UPDATE', EMR_MOD, 'Saran CDSS diterapkan ' . $visit['visit_no']);
        flash('success', 'Saran CDSS ditambahkan ke Plan.');
        redirect($go(4));
    }
    if ($act === 'finalize') {
        $rec = q_one('SELECT * FROM medical_records WHERE visit_id=? ORDER BY id DESC LIMIT 1', [$vid]);
        if (!$rec || !(int)q_val('SELECT COUNT(*) FROM diagnoses WHERE record_id=?', [$rec['id']])) { flash('danger', 'Rekam medis belum lengkap: minimal satu diagnosis ICD-10.'); redirect($go(3)); }
        if (empty($_POST['confirm'])) { flash('danger', 'Centang pernyataan validasi sebelum menyimpan.'); redirect($go(5)); }
        q("UPDATE medical_records SET status='final' WHERE id=?", [$rec['id']]);
        unset($_SESSION['emr_verified'][$vid]);
        audit('VALIDATE', EMR_MOD, 'Finalisasi rekam medis ' . $visit['visit_no']);
        flash('success', 'Rekam medis tervalidasi dan tersimpan (final).');
        redirect(ui_url('emr', ['v' => $vid, 'step' => 5, 'done' => 1]));
    }
    redirect($go($step));
}

/* ------------------------------ DATA ------------------------------ */
if ($visit) {
    $patient = ui_patient_row((int)$visit['patient_id']);
    $record  = q_one('SELECT * FROM medical_records WHERE visit_id=? ORDER BY id DESC LIMIT 1', [$vid]);
    $diags   = $record ? q_all('SELECT d.*, i.description FROM diagnoses d JOIN icd10 i ON i.code=d.icd10_code WHERE d.record_id=? ORDER BY d.type, d.id', [$record['id']]) : [];
    $vital   = q_one('SELECT * FROM vital_signs WHERE visit_id=? ORDER BY recorded_at DESC, id DESC LIMIT 1', [$vid]);
    $history = q_all("SELECT m.*, v.visit_no, v.visit_type, d.name doctor, (SELECT GROUP_CONCAT(icd10_code SEPARATOR ', ') FROM diagnoses WHERE record_id=m.id) codes
                      FROM medical_records m JOIN visits v ON v.id=m.visit_id LEFT JOIN doctors d ON d.id=m.doctor_id WHERE m.patient_id=? AND m.visit_id<>? ORDER BY m.created_at DESC LIMIT 5", [$visit['patient_id'], $vid]);
    $labs    = q_all("SELECT t.name, r.result_value, t.unit, r.flag, r.status, o.ordered_at FROM lab_results r JOIN lab_tests t ON t.id=r.lab_test_id JOIN orders o ON o.id=r.order_id
                      WHERE o.patient_id=? AND r.status='tervalidasi' ORDER BY o.ordered_at DESC LIMIT 8", [$visit['patient_id']]);
    $rads    = q_all("SELECT x.name, rr.impression, rr.reported_at FROM radiology_results rr JOIN radiology_exams x ON x.id=rr.exam_id JOIN orders o ON o.id=rr.order_id
                      WHERE o.patient_id=? AND rr.status='selesai' ORDER BY rr.reported_at DESC LIMIT 5", [$visit['patient_id']]);
    $docs    = q_all('SELECT * FROM patient_documents WHERE patient_id=? ORDER BY uploaded_at DESC LIMIT 6', [$visit['patient_id']]);
    $rxDrugs = array_column(q_all("SELECT DISTINCT d.name FROM prescription_items pi JOIN prescriptions p ON p.id=pi.prescription_id JOIN drugs d ON d.id=pi.drug_id WHERE p.visit_id=? AND p.status<>'ditolak'", [$vid]), 'name');
}

layout_start($PAGE['title'], 'emr', $PAGE['section']);
?>
<div class="content">
  <div class="page-head">
    <div><h2>Electronic Medical Record</h2><p><?= $isDoc ? 'Dokumentasi medis pasien: SOAP, diagnosis ICD-10, vital sign, dan dokumen pendukung.' : 'Tinjau rekam medis pasien dan catat vital sign.' ?></p></div>
    <?php if ($visit): ?><div class="page-actions"><a class="btn" href="<?= e(ui_url('emr')) ?>">← Ganti Pasien</a></div><?php endif; ?>
  </div>
  <?php flashes(); ?>
  <div class="card stepper-card"><?= ui_stepper($isDoc ? EMR_STEPS : array_slice(EMR_STEPS, 0, 4), isset($_GET['done']) ? 6 : $step) ?></div>

<?php if (!$visit):
  $q = trim((string)($_GET['q'] ?? ''));
  $visits = q_all("SELECT v.*, p.name, p.mrn, p.birth_date, p.gender, po.name poli, d.name doctor,
                          (SELECT status FROM medical_records m WHERE m.visit_id=v.id ORDER BY id DESC LIMIT 1) rec
                   FROM visits v JOIN patients p ON p.id=v.patient_id LEFT JOIN poli po ON po.id=v.poli_id LEFT JOIN doctors d ON d.id=v.doctor_id
                   WHERE v.status IN ('menunggu','dipanggil','diperiksa','dirawat') AND (? = '' OR p.name LIKE ? OR p.mrn LIKE ?)
                   ORDER BY (v.doctor_id = ?) DESC, v.visit_date DESC", [$q, "%$q%", "%$q%", $myDoc]); ?>
  <div class="card">
    <form class="toolbar" method="get"><input type="hidden" name="page" value="emr"><b style="color:var(--heading)">Langkah 1 — Pilih Pasien Aktif</b><span class="badge"><?= count($visits) ?> kunjungan</span><span class="grow"></span>
      <input class="input" name="q" value="<?= e($q) ?>" placeholder="Cari nama / No. RM"><button class="btn"><?= icon('search', 15) ?> Cari</button></form>
    <div class="table-wrap"><table class="table">
      <thead><tr><th>No. Kunjungan</th><th>Pasien</th><th>Layanan</th><th>Poli / Unit</th><th>DPJP</th><th>Status</th><th>Rekam Medis</th><th style="text-align:right">Aksi</th></tr></thead>
      <tbody><?php if (!$visits): ?><tr><td colspan="8" class="empty">Tidak ada pasien aktif.</td></tr><?php endif; foreach ($visits as $v): ?>
        <tr><td class="mono"><?= e($v['visit_no']) ?></td><td><b><?= e($v['name']) ?></b><br><small class="mono muted"><?= e($v['mrn']) ?> · <?= ui_age($v['birth_date']) ?></small></td>
          <td><?= e(ui_label($v['visit_type'])) ?></td><td><?= e($v['poli'] ?? '-') ?></td><td><?= e($v['doctor'] ?? '-') ?></td><td><?= status_badge($v['status']) ?></td>
          <td><?= $v['rec'] ? status_badge($v['rec']) : '<span class="badge neutral">Belum ada</span>' ?></td>
          <td><div class="actions"><a class="btn btn-sm btn-primary" href="<?= e(ui_url('emr', ['v' => $v['id'], 'step' => 1])) ?>">Pilih</a></div></td></tr>
      <?php endforeach; ?></tbody>
    </table></div>
  </div>

<?php else: ?>
  <?= ui_patient($patient, '<span class="badge info">' . e($visit['visit_no']) . ' · ' . e(ui_label($visit['visit_type'])) . '</span>') ?>

  <?php if ($step === 1): ?>
  <div class="card" style="max-width:720px">
    <div class="card-head"><div><h3>Langkah 2 — Verifikasi Identitas Pasien</h3><p>Cocokkan minimal dua identitas dengan gelang/kartu pasien.</p></div></div>
    <form method="post" class="card-body"><?= csrf_field() ?><input type="hidden" name="act" value="verify">
      <?= ui_details(['Nama Lengkap' => e($patient['name']), 'Nomor Rekam Medis' => '<span class="mono">' . e($patient['mrn']) . '</span>', 'Tanggal Lahir' => tgl($patient['birth_date']), 'NIK' => '<span class="mono">' . e($patient['nik']) . '</span>']) ?>
      <label class="check"><input type="checkbox" name="check[]" value="nama"> Nama lengkap sesuai</label>
      <label class="check"><input type="checkbox" name="check[]" value="lahir"> Tanggal lahir / No. RM sesuai</label>
      <label class="check" style="margin-bottom:14px"><input type="checkbox" name="check[]" value="alergi"> Alergi telah diperiksa</label>
      <button class="btn btn-primary"><?= icon('check', 16) ?> Verifikasi & Lanjut</button>
    </form>
  </div>

  <?php elseif ($step === 2): ?>
  <section class="grid grid-2e mb">
    <div class="card"><div class="card-head"><div><h3>Tanda Vital Kunjungan Saat Ini</h3><p><?= $vital ? 'Dicatat ' . tgl($vital['recorded_at'], true) : 'Belum ada vital sign' ?></p></div>
      <button class="btn btn-sm" data-modal="mVital"><?= icon('plus', 14) ?> Vital Sign</button></div>
      <?= $vital ? ui_details(['Tekanan Darah' => '<span class="mono">' . e($vital['systolic'] . '/' . $vital['diastolic']) . ' mmHg</span>', 'Nadi' => '<span class="mono">' . e($vital['pulse']) . ' x/mnt</span>',
          'RR' => '<span class="mono">' . e($vital['respiration']) . ' x/mnt</span>', 'Suhu' => '<span class="mono">' . e($vital['temperature']) . ' °C</span>', 'SpO₂' => '<span class="mono">' . e($vital['spo2']) . ' %</span>',
          'BB / TB' => '<span class="mono">' . e(($vital['weight'] ?? '-') . ' kg / ' . ($vital['height'] ?? '-') . ' cm') . '</span>']) : '<div class="empty">Belum ada data.</div>' ?></div>
    <div class="card"><div class="card-head"><div><h3>Catatan Medis Kunjungan Saat Ini</h3><p>Keluhan awal: <?= e($visit['complaint'] ?? '-') ?></p></div><?= $record ? status_badge($record['status']) : '' ?></div>
      <?= $record ? ui_details(['S — Subjektif' => nl2br(e($record['subjective'])), 'O — Objektif' => nl2br(e($record['objective'])), 'A — Assessment' => nl2br(e($record['assessment'])), 'P — Plan' => nl2br(e($record['plan'])),
          'Diagnosis' => implode('<br>', array_map(fn($d) => '<span class="mono">' . e($d['icd10_code']) . '</span> ' . e($d['description']) . ' <small class="muted">(' . $d['type'] . ')</small>', $diags))]) : '<div class="empty">Belum ada catatan.</div>' ?></div>
  </section>
  <section class="grid grid-3 mb">
    <div class="card"><div class="card-head"><h3>Riwayat Kunjungan</h3></div><ul class="list"><?php if (!$history): ?><li><span class="muted">Belum ada riwayat.</span></li><?php endif; foreach ($history as $h): ?>
      <li><span class="l-dot info"></span><div class="l-main"><b><?= e($h['assessment'] ?: '-') ?></b><span><?= e($h['doctor'] ?? '-') ?> · <span class="mono"><?= e($h['codes'] ?? '') ?></span></span></div><time class="mono"><?= tgl($h['created_at']) ?></time></li><?php endforeach; ?></ul></div>
    <div class="card"><div class="card-head"><h3>Hasil Lab Tervalidasi</h3></div><ul class="list"><?php if (!$labs): ?><li><span class="muted">Belum ada hasil.</span></li><?php endif; foreach ($labs as $l): ?>
      <li><span class="l-dot <?= $l['flag'] === 'normal' ? '' : 'danger' ?>"></span><div class="l-main"><b><?= e($l['name']) ?>: <span class="mono"><?= e($l['result_value'] . ' ' . $l['unit']) ?></span></b><span><?= e(ui_label($l['flag'] ?? '-')) ?></span></div><time class="mono"><?= tgl($l['ordered_at']) ?></time></li><?php endforeach; ?></ul></div>
    <div class="card"><div class="card-head"><h3>Radiologi & Dokumen</h3></div><ul class="list"><?php if (!$rads && !$docs): ?><li><span class="muted">Belum ada data.</span></li><?php endif; foreach ($rads as $r): ?>
      <li><span class="l-dot info"></span><div class="l-main"><b><?= e($r['name']) ?></b><span><?= e($r['impression']) ?></span></div><time class="mono"><?= tgl($r['reported_at']) ?></time></li><?php endforeach; foreach ($docs as $d): ?>
      <li><span class="l-dot"></span><div class="l-main"><b><a href="<?= e($d['file_path']) ?>" target="_blank"><?= e($d['title']) ?></a></b><span>Lampiran dokumen</span></div><time class="mono"><?= tgl($d['uploaded_at']) ?></time></li><?php endforeach; ?></ul></div>
  </section>
  <div class="card"><div class="card-foot"><?php if ($isDoc): ?><a class="btn btn-primary" href="<?= e($go(3)) ?>">Lanjut ke Input Catatan →</a><?php else: ?><button class="btn btn-primary" data-modal="mVital"><?= icon('plus', 16) ?> Catat Vital Sign</button><?php endif; ?></div></div>

  <?php elseif ($step === 3): ?>
  <?php $icd = q_all('SELECT code, description FROM icd10 ORDER BY code'); ?>
  <form method="post" enctype="multipart/form-data" class="card"><?= csrf_field() ?><input type="hidden" name="act" value="<?= $isDoc ? 'note' : 'vitals' ?>">
    <div class="card-head"><div><h3>Langkah 4 — <?= $isDoc ? 'Input Catatan Medis' : 'Input Vital Sign' ?></h3><p><?= $isDoc ? 'Format SOAP. Diagnosis pertama dicatat sebagai diagnosis primer.' : 'Catat tanda vital pasien.' ?></p></div></div>
    <div class="card-body">
      <label class="label" style="display:block;margin-bottom:8px">Tanda Vital</label>
      <div class="form-grid" style="grid-template-columns:repeat(4,minmax(0,1fr))">
        <?php foreach (['systolic' => 'Sistolik (mmHg)', 'diastolic' => 'Diastolik (mmHg)', 'pulse' => 'Nadi (x/mnt)', 'respiration' => 'RR (x/mnt)', 'temperature' => 'Suhu (°C)', 'spo2' => 'SpO₂ (%)', 'weight' => 'Berat (kg)', 'height' => 'Tinggi (cm)'] as $k => $l): ?>
          <div class="field"><label><?= e($l) ?></label><input type="number" step="any" name="<?= $k ?>" <?= !$isDoc && in_array($k, ['systolic', 'diastolic', 'pulse', 'temperature'], true) ? 'required' : '' ?>></div>
        <?php endforeach; ?>
      </div>
      <?php if ($isDoc): ?>
      <div class="form-grid">
        <div class="field"><label>S — Keluhan Utama / Subjektif</label><textarea name="subjective" required><?= e($record['subjective'] ?? $visit['complaint'] ?? '') ?></textarea></div>
        <div class="field"><label>O — Pemeriksaan Fisik / Objektif</label><textarea name="objective"><?= e($record['objective'] ?? '') ?></textarea></div>
        <div class="field"><label>A — Assessment</label><textarea name="assessment" required><?= e($record['assessment'] ?? '') ?></textarea></div>
        <div class="field"><label>P — Plan / Instruksi Klinis</label><textarea name="plan"><?= e($record['plan'] ?? '') ?></textarea></div>
        <div class="field full"><label>Hasil Pemeriksaan Penunjang</label><input name="exam_result" value="<?= e($record['exam_result'] ?? '') ?>" placeholder="Ringkasan hasil lab/radiologi yang relevan"></div>
      </div>
      <label class="label" style="display:block;margin-bottom:8px">Diagnosis ICD-10</label>
      <datalist id="icdList"><?php foreach ($icd as $i): ?><option value="<?= e($i['code']) ?>"><?= e($i['description']) ?></option><?php endforeach; ?></datalist>
      <div class="form-grid">
        <?php $cur = array_column($diags, 'icd10_code'); for ($i = 0; $i < 3; $i++): ?>
          <div class="field"><label><?= $i === 0 ? 'Diagnosis Primer' : 'Diagnosis Sekunder ' . $i ?></label><input name="icd[]" list="icdList" data-rule="icd" placeholder="Contoh: J18.9" value="<?= e($cur[$i] ?? '') ?>" <?= $i === 0 ? 'required' : '' ?>></div>
        <?php endfor; ?>
      </div>
      <div class="form-grid">
        <div class="field"><label>Lampiran Dokumen (PDF/JPG/PNG, maks. 5 MB)</label><input type="file" name="doc" accept=".pdf,.jpg,.jpeg,.png"></div>
        <div class="field"><label>Judul Dokumen</label><input name="doc_title" placeholder="Contoh: Surat rujukan / hasil EKG"></div>
      </div>
      <?php endif; ?>
    </div>
    <div class="card-foot"><a class="btn" href="<?= e($go(2)) ?>">← Kembali</a>
      <?php if ($isDoc): ?><button class="btn btn-outline" name="next" value="draft">Simpan Draft</button><button class="btn btn-primary" name="next" value="cdss">Simpan & Konsultasi AI CDSS →</button>
      <?php else: ?><button class="btn btn-primary">Simpan Vital Sign</button><?php endif; ?></div>
  </form>

  <?php elseif ($step === 4): $codes = array_column($diags, 'icd10_code');
    $tips = emr_cdss($vital, $codes, (string)$patient['allergies'], $rxDrugs, ($record['subjective'] ?? '') . ' ' . ($visit['complaint'] ?? '')); ?>
  <div class="card">
    <div class="card-head"><div><h3>Langkah 5 — Konsultasi AI CDSS</h3><p>Clinical Decision Support berbasis aturan (vital sign, diagnosis, alergi, resep). Bersifat saran — keputusan tetap pada dokter.</p></div></div>
    <div class="card-body">
      <?php if (!$record): ?><div class="alert warning">Simpan catatan medis terlebih dahulu.</div><?php endif; ?>
      <?php foreach ($tips as [$lvl, $t]): ?>
        <div class="alert <?= $lvl ?>" style="align-items:center"><?= icon($lvl === 'success' ? 'check' : 'alert', 18) ?><div style="flex:1"><?= e($t) ?></div>
          <?php if ($lvl !== 'success'): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="act" value="apply_cdss"><input type="hidden" name="suggestion" value="<?= e($t) ?>"><button class="btn btn-sm">Tambahkan ke Plan</button></form><?php endif; ?></div>
      <?php endforeach; ?>
    </div>
    <div class="card-foot"><a class="btn" href="<?= e($go(3)) ?>">← Perbaiki Data</a><a class="btn btn-primary" href="<?= e($go(5)) ?>">Lanjut ke Validasi →</a></div>
  </div>

  <?php elseif ($step === 5): ?>
  <div class="card">
    <div class="card-head"><div><h3>Langkah 6 — Review & Validasi</h3><p>Periksa kembali sebelum rekam medis dikunci (final).</p></div><?= $record ? status_badge($record['status']) : '' ?></div>
    <?= $record ? ui_details(['S — Subjektif' => nl2br(e($record['subjective'])), 'O — Objektif' => nl2br(e($record['objective'])), 'A — Assessment' => nl2br(e($record['assessment'])), 'P — Plan' => nl2br(e($record['plan'])),
        'Hasil Penunjang' => e($record['exam_result'] ?? '-'), 'Diagnosis' => implode('<br>', array_map(fn($d) => '<span class="mono">' . e($d['icd10_code']) . '</span> ' . e($d['description']) . ' <small class="muted">(' . $d['type'] . ')</small>', $diags)),
        'Vital Sign' => $vital ? '<span class="mono">TD ' . e($vital['systolic'] . '/' . $vital['diastolic']) . ' · N ' . e($vital['pulse']) . ' · S ' . e($vital['temperature']) . '°C · SpO₂ ' . e($vital['spo2']) . '%</span>' : '-',
        'DPJP' => e($visit['doctor'] ?? '-')]) : '<div class="empty">Belum ada catatan medis.</div>' ?>
    <?php if ($record && $record['status'] === 'draft'): ?>
    <form method="post" class="card-foot" style="justify-content:space-between"><?= csrf_field() ?><input type="hidden" name="act" value="finalize">
      <label class="check"><input type="checkbox" name="confirm" value="1" required> Saya menyatakan data rekam medis ini benar dan lengkap</label>
      <span><a class="btn" href="<?= e($go(3)) ?>">Perbaiki Data</a> <button class="btn btn-primary"><?= icon('check', 16) ?> Validasi & Simpan EMR</button></span></form>
    <?php elseif ($record): ?><div class="card-foot"><a class="btn btn-primary" href="<?= e(ui_url('emr')) ?>">Selesai — Pilih Pasien Lain</a></div><?php endif; ?>
  </div>
  <?php endif; ?>

  <div class="modal" id="mVital"><div class="modal-box">
    <div class="card-head"><h3>Catat Vital Sign</h3><button class="icon-btn" data-close type="button">✕</button></div>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="act" value="vitals">
      <div class="form-grid"><?php foreach (['systolic' => 'Sistolik (mmHg)', 'diastolic' => 'Diastolik (mmHg)', 'pulse' => 'Nadi (x/mnt)', 'respiration' => 'RR (x/mnt)', 'temperature' => 'Suhu (°C)', 'spo2' => 'SpO₂ (%)', 'weight' => 'Berat (kg)', 'height' => 'Tinggi (cm)'] as $k => $l): ?>
        <div class="field"><label><?= e($l) ?></label><input type="number" step="any" name="<?= $k ?>" <?= in_array($k, ['systolic', 'diastolic', 'pulse', 'temperature'], true) ? 'required' : '' ?>></div><?php endforeach; ?></div>
      <div class="modal-foot"><button type="button" class="btn" data-close>Batal</button><button class="btn btn-primary">Simpan</button></div></form>
  </div></div>
<?php endif; ?>
</div>
<?php layout_end();
