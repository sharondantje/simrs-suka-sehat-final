<?php
/*
 * MODUL 6 — RAWAT INAP / INPATIENT (database)
 * Tab: Pasien Aktif · Admisi Baru · Manajemen Kamar
 * Alur pasien: Admisi → Perawatan → Transfer (opsional) → Finalisasi & Keluar
 */
const RI_STEPS = ['Admisi', 'Perawatan', 'Transfer Ruangan', 'Finalisasi & Keluar'];
const RI_MOD   = 'Inpatient';

$me    = (int)$user['id'];
$isDoc = $user['role'] === 'dokter';
$tab   = $_GET['tab'] ?? 'aktif';
$aid   = (int)($_GET['a'] ?? 0);
$back  = ui_url('rawatinap', $aid ? ['a' => $aid] : ['tab' => $tab]);

if ($act = ui_post_action()) {
    $f = fn($k) => trim((string)($_POST[$k] ?? '')) ?: null;
    if ($act === 'admit') {
        $pid = (int)($_POST['patient_id'] ?? 0); $bed = (int)($_POST['bed_id'] ?? 0); $doc = (int)($_POST['doctor_id'] ?? 0);
        if (!$pid || !$bed || !$doc || !$f('diagnosis')) { flash('danger', 'Pasien, DPJP, kamar/bed, dan diagnosis masuk wajib diisi.'); redirect(ui_url('rawatinap', ['tab' => 'admisi'])); }
        if (q_val("SELECT 1 FROM admissions WHERE patient_id=? AND status='dirawat'", [$pid])) { flash('danger', 'Pasien masih tercatat dirawat.'); redirect(ui_url('rawatinap', ['tab' => 'admisi'])); }
        if (q_val('SELECT status FROM beds WHERE id=?', [$bed]) !== 'tersedia') { flash('danger', 'Bed yang dipilih sudah tidak tersedia.'); redirect(ui_url('rawatinap', ['tab' => 'admisi'])); }
        $newId = ui_tx(function () use ($pid, $bed, $doc, $f) {
            $poli = (int)q_val('SELECT poli_id FROM doctors WHERE id=?', [$doc]) ?: null;
            $src  = (int)($_POST['source_visit'] ?? 0);
            $v = ui_create_visit($pid, 'rawat_inap', $poli, $doc, null, (string)$f('diagnosis'), 'dirawat');
            $no = ui_next_no('admissions', 'admission_no', 'ADM-');
            q("INSERT INTO admissions (admission_no, visit_id, patient_id, bed_id, doctor_id, admit_date, diagnosis, status) VALUES (?,?,?,?,?,NOW(),?,'dirawat')", [$no, $v, $pid, $bed, $doc, $f('diagnosis')]);
            $id = (int)db()->lastInsertId();
            q("UPDATE beds SET status='terisi' WHERE id=?", [$bed]);
            if ($src) {
                q("UPDATE orders SET status='selesai' WHERE visit_id=? AND order_type='rawat_inap' AND status<>'dibatalkan'", [$src]);
                q("UPDATE visits SET status='selesai' WHERE id=? AND status<>'selesai'", [$src]);
            }
            return $id;
        });
        audit('CREATE', RI_MOD, 'Admisi rawat inap #' . $newId);
        flash('success', 'Pasien berhasil diadmisi ke rawat inap.');
        redirect(ui_url('rawatinap', ['a' => $newId]));
    }
    if ($act === 'bed_status') {
        $st = $_POST['status'] ?? '';
        if (in_array($st, ['tersedia', 'dibersihkan', 'maintenance'], true)) q("UPDATE beds SET status=? WHERE id=? AND status<>'terisi'", [$st, (int)$_POST['id']]);
        audit('UPDATE', RI_MOD, 'Status bed #' . (int)$_POST['id'] . " → $st");
        flash('success', 'Status bed diperbarui.');
        redirect(ui_url('rawatinap', ['tab' => 'kamar']));
    }
    $adm = q_one("SELECT * FROM admissions WHERE id=? AND status='dirawat'", [$aid]);
    if (!$adm) redirect($back);
    if ($act === 'note') {
        if (!$f('note')) redirect($back);
        q("INSERT INTO nursing_notes (note_type, visit_id, patient_id, nurse_id, assessment, intervention) VALUES ('catatan',?,?,?,?,?)", [$adm['visit_id'], $adm['patient_id'], $me, $f('note'), $f('instruction')]);
        audit('CREATE', RI_MOD, 'Catatan perawatan ' . $adm['admission_no']);
        flash('success', 'Catatan perawatan tersimpan.');
    }
    if ($act === 'transfer') {
        $to = (int)($_POST['to_bed'] ?? 0);
        if (!$to || !$f('reason')) { flash('danger', 'Pilih bed tujuan dan isi alasan transfer.'); redirect($back); }
        if (q_val('SELECT status FROM beds WHERE id=?', [$to]) !== 'tersedia') { flash('danger', 'Bed tujuan tidak tersedia.'); redirect($back); }
        ui_tx(function () use ($adm, $to, $f) {
            q('INSERT INTO bed_transfers (admission_id, from_bed_id, to_bed_id, reason) VALUES (?,?,?,?)', [$adm['id'], $adm['bed_id'], $to, $f('reason')]);
            q("UPDATE beds SET status='dibersihkan' WHERE id=?", [$adm['bed_id']]);
            q("UPDATE beds SET status='terisi' WHERE id=?", [$to]);
            q('UPDATE admissions SET bed_id=? WHERE id=?', [$to, $adm['id']]);
        });
        audit('UPDATE', RI_MOD, 'Transfer ruangan ' . $adm['admission_no']);
        flash('success', 'Pasien dipindahkan. Bed lama ditandai perlu dibersihkan.');
    }
    if ($act === 'discharge') {
        $st = in_array($_POST['status'] ?? '', ['pulang', 'dirujuk', 'meninggal'], true) ? $_POST['status'] : 'pulang';
        $icd = strtoupper((string)$f('icd'));
        if (!$f('summary') || !$icd) { flash('danger', 'Diagnosis akhir (ICD-10) dan ringkasan pulang wajib diisi.'); redirect($back); }
        if (!q_val('SELECT 1 FROM icd10 WHERE code=?', [$icd])) { flash('danger', "Kode ICD-10 $icd tidak ditemukan."); redirect($back); }
        ui_tx(function () use ($adm, $st, $icd, $f) {
            $date = str_replace('T', ' ', (string)($_POST['discharge_date'] ?: date('Y-m-d H:i')));
            q('UPDATE admissions SET status=?, discharge_date=?, discharge_summary=? WHERE id=?', [$st, $date, $f('summary'), $adm['id']]);
            q("UPDATE beds SET status='dibersihkan' WHERE id=?", [$adm['bed_id']]);
            q("UPDATE visits SET status='selesai' WHERE id=?", [$adm['visit_id']]);
            $rid = (int)q_val('SELECT id FROM medical_records WHERE visit_id=? ORDER BY id DESC LIMIT 1', [$adm['visit_id']]);
            if (!$rid) { q("INSERT INTO medical_records (visit_id, patient_id, doctor_id, assessment, plan, status) VALUES (?,?,?,?,?,'final')", [$adm['visit_id'], $adm['patient_id'], $adm['doctor_id'], $adm['diagnosis'], $f('summary')]); $rid = (int)db()->lastInsertId(); }
            else q("UPDATE medical_records SET status='final' WHERE id=?", [$rid]);
            q("DELETE FROM diagnoses WHERE record_id=? AND type='primer'", [$rid]);
            q("INSERT INTO diagnoses (record_id, icd10_code, type) VALUES (?,?,'primer')", [$rid, $icd]);
            if ($st === 'dirujuk') q("INSERT INTO referrals (visit_id, referral_type, destination, reason) VALUES (?, 'eksternal', ?, ?)", [$adm['visit_id'], $f('destination') ?? 'RS rujukan', $f('summary')]);
        });
        audit('UPDATE', RI_MOD, 'Pasien keluar (' . $st . ') ' . $adm['admission_no']);
        flash('success', 'Pelayanan rawat inap difinalisasi. Tagihan dapat diproses kasir.');
    }
    redirect($back);
}

$beds = q_all("SELECT b.*, r.name room, r.ward, r.class, r.tariff_per_day, p.name patient FROM beds b JOIN rooms r ON r.id=b.room_id
               LEFT JOIN admissions a ON a.bed_id=b.id AND a.status='dirawat' LEFT JOIN patients p ON p.id=a.patient_id ORDER BY r.id, b.bed_number");
$free = array_values(array_filter($beds, fn($b) => $b['status'] === 'tersedia'));
$bedOpt = fn($sel = null) => ui_options($free, 'id', fn($b) => $b['room'] . ' · ' . $b['bed_number'] . ' (' . rupiah($b['tariff_per_day']) . '/hari)', $sel, '— pilih bed tersedia —');

layout_start($PAGE['title'], 'rawatinap', $PAGE['section']);
?>
<div class="content">
  <div class="page-head"><div><h2>Rawat Inap</h2><p>Admisi, perawatan, transfer ruangan, manajemen kamar, dan pemulangan pasien.</p></div>
    <?php if ($aid): ?><div class="page-actions"><a class="btn" href="<?= e(ui_url('rawatinap')) ?>">← Daftar Pasien</a></div><?php endif; ?></div>
  <?php flashes(); ?>

<?php if (!$aid):
  echo ui_tabs(['aktif' => 'Pasien Aktif', 'admisi' => 'Admisi Baru', 'kamar' => 'Manajemen Kamar', 'riwayat' => 'Selesai'], $tab, fn($k) => ui_url('rawatinap', ['tab' => $k]));
  $bc = fn($s) => count(array_filter($beds, fn($b) => $b['status'] === $s)); ?>
  <section class="kpi-grid">
    <?= ui_kpi('Pasien Dirawat', $bc('terisi'), 'bed', 'info') ?>
    <?= ui_kpi('Bed Tersedia', $bc('tersedia'), 'check') ?>
    <?= ui_kpi('Perlu Dibersihkan', $bc('dibersihkan'), 'refresh', 'warning') ?>
    <?= ui_kpi('BOR Saat Ini', angka($bc('terisi') / max(1, count($beds)) * 100, 1) . '%', 'trend', 'purple', count($beds) . ' bed') ?>
  </section>

  <?php if ($tab === 'aktif' || $tab === 'riwayat'):
    $rows = q_all("SELECT a.*, p.name, p.mrn, p.birth_date, b.bed_number, r.name room, d.name doctor, DATEDIFF(COALESCE(a.discharge_date, NOW()), a.admit_date) los
                   FROM admissions a JOIN patients p ON p.id=a.patient_id LEFT JOIN beds b ON b.id=a.bed_id LEFT JOIN rooms r ON r.id=b.room_id LEFT JOIN doctors d ON d.id=a.doctor_id
                   WHERE " . ($tab === 'aktif' ? "a.status='dirawat'" : "a.status<>'dirawat'") . ' ORDER BY a.admit_date DESC'); ?>
  <div class="card"><div class="table-wrap"><table class="table"><thead><tr><th>No. Admisi</th><th>Pasien</th><th>Ruang / Bed</th><th>DPJP</th><th>Diagnosis</th><th>Masuk</th><th>LOS</th><th>Status</th><th style="text-align:right">Aksi</th></tr></thead><tbody>
  <?php if (!$rows): ?><tr><td colspan="9" class="empty">Tidak ada data.</td></tr><?php endif; foreach ($rows as $r): ?>
    <tr><td class="mono"><?= e($r['admission_no']) ?></td><td><b><?= e($r['name']) ?></b><br><small class="mono muted"><?= e($r['mrn']) ?> · <?= ui_age($r['birth_date']) ?></small></td>
      <td><?= e($r['room'] ?? '-') ?> · <span class="mono"><?= e($r['bed_number'] ?? '') ?></span></td><td><?= e($r['doctor'] ?? '-') ?></td><td><?= e($r['diagnosis'] ?? '-') ?></td>
      <td class="mono"><?= tgl($r['admit_date'], true) ?></td><td class="mono"><?= (int)$r['los'] ?> hr</td><td><?= status_badge($r['status']) ?></td>
      <td><div class="actions"><a class="btn btn-sm <?= $r['status'] === 'dirawat' ? 'btn-primary' : '' ?>" href="<?= e(ui_url('rawatinap', ['a' => $r['id']])) ?>"><?= $r['status'] === 'dirawat' ? 'Kelola' : 'Lihat' ?></a></div></td></tr>
  <?php endforeach; ?></tbody></table></div></div>

  <?php elseif ($tab === 'admisi'):
    $pending = q_all("SELECT v.id vid, v.visit_no, v.visit_type, p.id pid, p.name, p.mrn, o.notes, o.priority, d.id doc FROM orders o JOIN visits v ON v.id=o.visit_id JOIN patients p ON p.id=o.patient_id LEFT JOIN doctors d ON d.id=o.doctor_id
                      WHERE o.order_type='rawat_inap' AND o.status='baru'
                      UNION SELECT v.id, v.visit_no, v.visit_type, p.id, p.name, p.mrn, t.chief_complaint, 'cito', v.doctor_id FROM triages t JOIN visits v ON v.id=t.visit_id JOIN patients p ON p.id=t.patient_id
                      WHERE t.disposition='rawat_inap' AND v.status NOT IN ('selesai','dirawat','batal')");
    $patients = q_all("SELECT id, name, mrn FROM patients WHERE id NOT IN (SELECT patient_id FROM admissions WHERE status='dirawat') ORDER BY name");
    $doctors = q_all('SELECT id, name FROM doctors WHERE is_active=1 AND poli_id IS NOT NULL ORDER BY name'); ?>
  <section class="grid grid-2">
    <div class="card"><div class="card-head"><div><h3>Permintaan Rawat Inap</h3><p>Dari order dokter (CPOE / poli) dan keputusan IGD.</p></div></div>
      <ul class="list"><?php if (!$pending): ?><li><span class="muted">Tidak ada permintaan.</span></li><?php endif; foreach ($pending as $p): ?>
        <li><span class="l-dot <?= $p['priority'] === 'cito' ? 'danger' : 'warning' ?>"></span><div class="l-main"><b><?= e($p['name']) ?> <small class="mono muted"><?= e($p['mrn']) ?></small></b><span><?= e(ui_label($p['visit_type'])) ?> · <?= e($p['notes'] ?? '-') ?></span></div>
          <button class="btn btn-sm btn-primary" type="button" onclick='pick(<?= json_encode(['p' => $p['pid'], 'v' => $p['vid'], 'd' => $p['doc'], 'n' => $p['notes']], JSON_HEX_APOS) ?>)'>Pilih</button></li><?php endforeach; ?></ul></div>
    <form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="act" value="admit"><input type="hidden" name="source_visit" id="aSrc">
      <div class="card-head"><div><h3>Admisi Pasien Rawat Inap</h3><p><?= count($free) ?> bed tersedia</p></div></div>
      <div class="card-body">
        <div class="field"><label>Pasien</label><select name="patient_id" id="aP" required><?= ui_options($patients, 'id', fn($p) => $p['name'] . ' — ' . $p['mrn'], null, '— pilih pasien —') ?></select></div>
        <div class="field"><label>Dokter DPJP</label><select name="doctor_id" id="aD" required><?= ui_options($doctors, 'id', 'name', null, '— pilih DPJP —') ?></select></div>
        <div class="field"><label>Kamar / Bed</label><select name="bed_id" required><?= $bedOpt() ?></select><?php if (!$free): ?><span class="field-error">Tidak ada kamar tersedia saat ini.</span><?php endif; ?></div>
        <div class="field"><label>Diagnosis Masuk</label><input name="diagnosis" id="aDx" required placeholder="Diagnosis klinis..."></div>
      </div>
      <div class="card-foot"><button class="btn btn-primary" <?= $free ? '' : 'disabled' ?>><?= icon('bed', 16) ?> Admisi Pasien</button></div></form>
  </section>
  <script>function pick(o){aP.value=o.p;aSrc.value=o.v;if(o.d)aD.value=o.d;aDx.value=o.n||''}</script>

  <?php elseif ($tab === 'kamar'):
    $byRoom = []; foreach ($beds as $b) $byRoom[$b['room'] . ' — Kelas ' . $b['class']][] = $b; ?>
  <?php foreach ($byRoom as $room => $list): ?>
    <div class="card mb"><div class="card-head"><div><h3><?= e($room) ?></h3><p><?= rupiah($list[0]['tariff_per_day']) ?> / hari</p></div>
      <span class="badge"><?= count(array_filter($list, fn($b) => $b['status'] === 'tersedia')) ?>/<?= count($list) ?> tersedia</span></div>
      <div class="bed-grid"><?php foreach ($list as $b): ?><div class="bed <?= e($b['status']) ?>"><b><?= e($b['bed_number']) ?></b><?= e($b['patient'] ?? ui_label($b['status'])) ?>
        <?php if ($b['status'] !== 'terisi'): ?><form method="post" style="margin-top:6px"><?= csrf_field() ?><input type="hidden" name="act" value="bed_status"><input type="hidden" name="id" value="<?= $b['id'] ?>">
          <select class="input" name="status" onchange="this.form.submit()" style="height:30px;font-size:12px;padding:0 6px"><?= ui_opt_map(['tersedia' => 'Tersedia', 'dibersihkan' => 'Dibersihkan', 'maintenance' => 'Maintenance'], $b['status']) ?></select></form><?php endif; ?></div><?php endforeach; ?></div></div>
  <?php endforeach; endif; ?>

<?php else:
  $a = q_one('SELECT a.*, b.bed_number, r.name room, r.class, d.name doctor FROM admissions a LEFT JOIN beds b ON b.id=a.bed_id LEFT JOIN rooms r ON r.id=b.room_id LEFT JOIN doctors d ON d.id=a.doctor_id WHERE a.id=?', [$aid]);
  if (!$a) { echo '<div class="empty">Data tidak ditemukan.</div></div>'; layout_end(); return; }
  $patient = ui_patient_row((int)$a['patient_id']);
  $trans = q_all('SELECT t.*, fb.bed_number fb, fr.name fr, tb.bed_number tb, tr.name tr FROM bed_transfers t LEFT JOIN beds fb ON fb.id=t.from_bed_id LEFT JOIN rooms fr ON fr.id=fb.room_id LEFT JOIN beds tb ON tb.id=t.to_bed_id LEFT JOIN rooms tr ON tr.id=tb.room_id WHERE t.admission_id=? ORDER BY t.transferred_at', [$aid]);
  $notes = q_all('SELECT n.*, u.full_name FROM nursing_notes n LEFT JOIN users u ON u.id=n.nurse_id WHERE n.visit_id=? ORDER BY n.created_at DESC LIMIT 8', [$a['visit_id']]);
  $stage = $a['status'] !== 'dirawat' ? 4 : ($trans ? 3 : 1); ?>
  <div class="card stepper-card"><?= ui_stepper(RI_STEPS, $stage) ?></div>
  <?= ui_patient($patient, '<span class="badge info">' . e($a['room']) . ' · ' . e($a['bed_number']) . '</span>') ?>
  <section class="grid grid-2">
    <div>
      <div class="card mb"><div class="card-head"><div><h3>Admisi <span class="mono"><?= e($a['admission_no']) ?></span></h3><p>DPJP <?= e($a['doctor'] ?? '-') ?></p></div><?= status_badge($a['status']) ?></div>
        <?= ui_details(['Tanggal Masuk' => '<span class="mono">' . tgl($a['admit_date'], true) . '</span>', 'Lama Rawat' => (int)((strtotime($a['discharge_date'] ?? 'now') - strtotime($a['admit_date'])) / 86400) . ' hari',
            'Diagnosis Masuk' => e($a['diagnosis'] ?? '-'), 'Kelas' => e($a['class']), 'Tanggal Keluar' => '<span class="mono">' . tgl($a['discharge_date'], true) . '</span>', 'Ringkasan Pulang' => e($a['discharge_summary'] ?? '-')]) ?></div>
      <div class="card"><div class="card-head"><div><h3>Catatan Perawatan & Transfer</h3></div></div>
        <ul class="list"><?php foreach ($trans as $t): ?><li><span class="l-dot warning"></span><div class="l-main"><b>Transfer <?= e($t['fr'] . ' ' . $t['fb']) ?> → <?= e($t['tr'] . ' ' . $t['tb']) ?></b><span><?= e($t['reason']) ?></span></div><time class="mono"><?= tgl($t['transferred_at'], true) ?></time></li><?php endforeach;
          foreach ($notes as $n): ?><li><span class="l-dot info"></span><div class="l-main"><b><?= e($n['nursing_diagnosis'] ?: ui_label($n['note_type'])) ?></b><span><?= e(implode(' · ', array_filter([$n['assessment'], $n['intervention'], $n['evaluation']]))) ?> — <?= e($n['full_name'] ?? '') ?></span></div><time class="mono"><?= tgl($n['created_at'], true) ?></time></li><?php endforeach;
          if (!$trans && !$notes): ?><li><span class="muted">Belum ada catatan.</span></li><?php endif; ?></ul>
        <?php if ($a['status'] === 'dirawat'): ?><form method="post" class="card-foot"><?= csrf_field() ?><input type="hidden" name="act" value="note"><input class="input" name="note" required placeholder="Catatan perawatan / perkembangan" style="flex:1;height:38px"><input class="input" name="instruction" placeholder="Instruksi" style="flex:1;height:38px"><button class="btn">Simpan</button></form><?php endif; ?></div>
    </div>
    <?php if ($a['status'] === 'dirawat'): ?>
    <div>
      <form method="post" class="card mb"><?= csrf_field() ?><input type="hidden" name="act" value="transfer">
        <div class="card-head"><div><h3>Transfer Ruangan</h3><p>Opsional — bed lama otomatis ditandai perlu dibersihkan.</p></div></div>
        <div class="card-body"><div class="field"><label>Bed Tujuan</label><select name="to_bed" required><?= $bedOpt() ?></select></div>
          <div class="field"><label>Alasan Transfer</label><input name="reason" required placeholder="Alasan transfer ruangan..."></div>
          <button class="btn"><?= icon('swap', 16) ?> Konfirmasi Transfer</button></div></form>
      <?php if ($isDoc): ?>
      <form method="post" class="card" data-confirm="Pasien akan dicatat keluar dan bed dikosongkan." data-confirm-title="Finalisasi Rawat Inap" data-confirm-ok="Finalisasi"><?= csrf_field() ?><input type="hidden" name="act" value="discharge">
        <div class="card-head"><div><h3>Finalisasi & Keluar Pasien</h3><p>Diisi DPJP.</p></div></div>
        <div class="card-body"><div class="form-grid">
          <div class="field"><label>Kondisi Saat Keluar</label><select name="status"><?= ui_opt_map(['pulang' => 'Pulang (sembuh / perbaikan)', 'dirujuk' => 'Dirujuk ke RS lain', 'meninggal' => 'Meninggal Dunia']) ?></select></div>
          <div class="field"><label>Tanggal Keluar</label><input type="datetime-local" name="discharge_date" value="<?= date('Y-m-d\TH:i') ?>"></div>
          <div class="field"><label>Diagnosis Akhir (ICD-10)</label><input name="icd" required data-rule="icd" placeholder="Contoh: J18.9"></div>
          <div class="field"><label>RS Rujukan (bila dirujuk)</label><input name="destination"></div>
          <div class="field full"><label>Ringkasan / Instruksi Pulang</label><textarea name="summary" required placeholder="Instruksi / catatan kepulangan..."></textarea></div></div></div>
        <div class="card-foot"><button class="btn btn-primary"><?= icon('check', 16) ?> Finalisasi Pelayanan</button></div></form>
      <?php else: ?><div class="alert info">Finalisasi & pemulangan dilakukan oleh dokter DPJP.</div><?php endif; ?>
    </div>
    <?php endif; ?>
  </section>
<?php endif; ?>
</div>
<?php layout_end();
