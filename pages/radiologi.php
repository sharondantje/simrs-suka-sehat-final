<?php
/*
 * MODUL 9 — RADIOLOGI (database)
 * Alur: Order Masuk (CPOE) → Terjadwal → Pemeriksaan / Akuisisi Gambar → Pembacaan (expertise) → Selesai
 * Menu: order (order masuk & proses), hasil (hasil selesai, cetak)
 */
const RAD_STEPS = ['Order Masuk', 'Penjadwalan', 'Pemeriksaan', 'Pembacaan', 'Selesai'];
const RAD_MOD   = 'Radiology';
$RAD_STAGE = ['terjadwal' => 1, 'pemeriksaan' => 2, 'menunggu_bacaan' => 3, 'selesai' => 5];

$key = $PAGE['key'];
$id  = (int)($_GET['id'] ?? 0);    // id radiology_results
$me  = (int)$user['id'];
$back = ui_url($key, $id ? ['id' => $id] : []);

if ($act = ui_post_action()) {
    $r = q_one('SELECT rr.*, o.order_no, o.id oid FROM radiology_results rr JOIN orders o ON o.id=rr.order_id WHERE rr.id=?', [(int)($_POST['rid'] ?? 0)]);
    if (!$r) { flash('danger', 'Data pemeriksaan tidak ditemukan.'); redirect(ui_url($key)); }
    if ($act === 'schedule' && $r['status'] === 'terjadwal') {
        $at = str_replace('T', ' ', (string)($_POST['scheduled_at'] ?? ''));
        if (!$at) { flash('danger', 'Jadwal pemeriksaan wajib diisi.'); redirect($back); }
        q("UPDATE radiology_results SET scheduled_at=?, status='pemeriksaan' WHERE id=?", [$at, $r['id']]);
        q("UPDATE orders SET status='diproses' WHERE id=?", [$r['oid']]);
        audit('UPDATE', RAD_MOD, 'Pemeriksaan dimulai ' . $r['order_no']);
        flash('success', 'Pasien dalam pemeriksaan. Unggah/catat gambar setelah akuisisi selesai.');
    } elseif ($act === 'acquire' && $r['status'] === 'pemeriksaan') {
        $path = $r['image_path'];
        if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'dcm', 'pdf'], true)) { flash('danger', 'Format gambar harus JPG/PNG/DCM/PDF.'); redirect($back); }
            $dir = dirname(__DIR__) . '/uploads/radiologi';
            $name = $r['order_no'] . '-' . time() . '.' . $ext;
            if ((is_dir($dir) || @mkdir($dir, 0775, true)) && is_writable($dir) && move_uploaded_file($_FILES['image']['tmp_name'], "$dir/$name")) {
                $path = "uploads/radiologi/$name";
            } else {
                // Hosting tanpa penyimpanan file (mis. Vercel): simpan gambar di database
                if ($_FILES['image']['size'] > 4 * 1024 * 1024) { flash('danger', 'Ukuran file maksimal 4 MB.'); redirect($back); }
                $mime = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'pdf' => 'application/pdf', 'dcm' => 'application/dicom'][$ext];
                $path = 'file.php?id=' . app_file_store($name, $mime, (string)file_get_contents($_FILES['image']['tmp_name']));
            }
        }
        q("UPDATE radiology_results SET image_path=?, status='menunggu_bacaan' WHERE id=?", [$path ?: 'PACS:' . $r['order_no'], $r['id']]);
        audit('UPDATE', RAD_MOD, 'Akuisisi gambar selesai ' . $r['order_no']);
        flash('success', 'Gambar tersimpan. Menunggu pembacaan radiolog.');
    } elseif ($act === 'report' && $r['status'] === 'menunggu_bacaan') {
        $f = trim((string)($_POST['findings'] ?? '')); $i = trim((string)($_POST['impression'] ?? ''));
        if ($f === '' || $i === '') { flash('danger', 'Temuan dan kesan wajib diisi.'); redirect($back); }
        ui_tx(function () use ($f, $i, $me, $r) {
            q("UPDATE radiology_results SET findings=?, impression=?, radiologist_id=?, reported_at=NOW(), status='selesai' WHERE id=?", [$f, $i, $me, $r['id']]);
            if (!(int)q_val("SELECT COUNT(*) FROM radiology_results WHERE order_id=? AND status<>'selesai'", [$r['oid']]))
                q("UPDATE orders SET status='selesai' WHERE id=?", [$r['oid']]);
        });
        audit('VALIDATE', RAD_MOD, 'Expertise radiologi ' . $r['order_no']);
        flash('success', 'Expertise tersimpan. Hasil dapat dilihat dokter pengirim.');
        redirect(ui_url('hasil', ['id' => $r['id']]));
    }
    redirect($back);
}

$rows = q_all("SELECT rr.*, o.order_no, o.priority, o.notes indication, o.ordered_at, o.status ostatus, p.name patient, p.mrn, x.name exam, x.modality, d.name doctor, v.visit_type
               FROM radiology_results rr JOIN orders o ON o.id=rr.order_id JOIN patients p ON p.id=o.patient_id JOIN radiology_exams x ON x.id=rr.exam_id
               LEFT JOIN doctors d ON d.id=o.doctor_id LEFT JOIN visits v ON v.id=o.visit_id
               WHERE o.status<>'dibatalkan' ORDER BY (o.priority='cito') DESC, o.ordered_at DESC");
$fStatus = $_GET['status'] ?? '';
$search  = trim((string)($_GET['q'] ?? ''));
$list = array_values(array_filter($rows, function ($r) use ($key, $fStatus, $search) {
    if ($key === 'hasil' && $r['status'] !== 'selesai') return false;
    if ($fStatus !== '' && $r['status'] !== $fStatus) return false;
    return $search === '' || stripos($r['patient'] . ' ' . $r['mrn'] . ' ' . $r['order_no'] . ' ' . $r['exam'], $search) !== false;
}));
$cnt = fn($s) => count(array_filter($rows, fn($r) => $r['status'] === $s));

$cur = null;
if ($id) foreach ($rows as $r) if ((int)$r['id'] === $id) $cur = $r;
if ($cur) {
    $patient = ui_patient_row((int)q_val('SELECT patient_id FROM orders WHERE id=?', [$cur['order_id']]));
    $radName = q_val('SELECT full_name FROM users WHERE id=?', [$cur['radiologist_id']]);
}

layout_start($PAGE['title'], $key, $PAGE['section']);
?>
<div class="content">
  <div class="page-head">
    <div><h2><?= $key === 'hasil' ? 'Daftar Hasil Radiologi' : 'Order Radiologi' ?></h2><p><?= $key === 'hasil' ? 'Hasil expertise radiologi yang sudah selesai.' : 'Order pemeriksaan radiologi dari dokter, diproses sampai expertise selesai.' ?></p></div>
    <?php if ($cur): ?><div class="page-actions"><a class="btn" href="<?= e(ui_url($key)) ?>">← Kembali</a><?php if ($cur['status'] === 'selesai'): ?><button class="btn btn-primary" onclick="print()"><?= icon('printer', 16) ?> Cetak Hasil</button><?php endif; ?></div><?php endif; ?>
  </div>
  <?php flashes(); ?>

<?php if (!$cur): ?>
  <section class="kpi-grid">
    <?= ui_kpi('Terjadwal', $cnt('terjadwal'), 'calendar', 'warning', 'menunggu pemeriksaan') ?>
    <?= ui_kpi('Dalam Pemeriksaan', $cnt('pemeriksaan'), 'scan', 'info', 'akuisisi gambar') ?>
    <?= ui_kpi('Menunggu Bacaan', $cnt('menunggu_bacaan'), 'edit', 'purple', 'perlu expertise') ?>
    <?= ui_kpi('Selesai', $cnt('selesai'), 'check', '', 'hasil terkirim') ?>
  </section>
  <div class="card">
    <form class="toolbar" method="get">
      <input type="hidden" name="page" value="<?= e($key) ?>">
      <b style="color:var(--heading)"><?= $key === 'hasil' ? 'Hasil Radiologi' : 'Order Masuk' ?></b><span class="badge"><?= count($list) ?> data</span><span class="grow"></span>
      <?php if ($key !== 'hasil'): ?><select class="input" name="status" onchange="this.form.submit()"><option value="">Semua status</option><?= ui_opt_map(['terjadwal' => 'Terjadwal', 'pemeriksaan' => 'Pemeriksaan', 'menunggu_bacaan' => 'Menunggu Bacaan', 'selesai' => 'Selesai'], $fStatus) ?></select><?php endif; ?>
      <input class="input" name="q" value="<?= e($search) ?>" placeholder="Cari pasien / No. RM / order"><button class="btn"><?= icon('search', 15) ?> Cari</button>
    </form>
    <div class="table-wrap"><table class="table">
      <thead><tr><th>No. Order</th><th>Pasien</th><th>Pemeriksaan</th><th>Modalitas</th><th>Dokter Pengirim</th><th>Prioritas</th><th><?= $key === 'hasil' ? 'Dilaporkan' : 'Waktu Order' ?></th><th>Status</th><th style="text-align:right">Aksi</th></tr></thead>
      <tbody>
      <?php if (!$list): ?><tr><td colspan="9" class="empty">Tidak ada data.</td></tr><?php endif; ?>
      <?php foreach ($list as $r): ?>
        <tr><td class="mono strong"><?= e($r['order_no']) ?></td>
          <td><b><?= e($r['patient']) ?></b><br><small class="mono muted"><?= e($r['mrn']) ?></small></td>
          <td><?= e($r['exam']) ?></td><td class="mono"><?= e($r['modality']) ?></td><td><?= e($r['doctor'] ?? '-') ?></td>
          <td><?= status_badge($r['priority'] === 'cito' ? 'cito' : 'normal') ?></td>
          <td class="mono"><?= tgl($key === 'hasil' ? $r['reported_at'] : $r['ordered_at'], true) ?></td>
          <td><?= status_badge($r['status']) ?></td>
          <td><div class="actions"><a class="btn btn-sm <?= $r['status'] !== 'selesai' ? 'btn-primary' : '' ?>" href="<?= e(ui_url($key, ['id' => $r['id']])) ?>"><?= $r['status'] === 'selesai' ? 'Lihat' : 'Proses' ?></a></div></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  </div>

<?php else: $st = $RAD_STAGE[$cur['status']]; ?>
  <div class="card stepper-card"><?= ui_stepper(RAD_STEPS, $st) ?></div>
  <div class="print-only" style="margin-bottom:12px"><b>RS Suka Sehat — Hasil Pemeriksaan Radiologi</b></div>
  <?= ui_patient($patient) ?>
  <section class="grid grid-2 mb">
    <div class="card">
      <div class="card-head"><div><h3>Order <span class="mono"><?= e($cur['order_no']) ?></span></h3><p><?= e($cur['exam']) ?> · <?= e($cur['modality']) ?></p></div><?= status_badge($cur['status']) ?></div>
      <?= ui_details([
          'Dokter Pengirim'   => e($cur['doctor'] ?? '-'),
          'Waktu Order'       => '<span class="mono">' . tgl($cur['ordered_at'], true) . '</span>',
          'Prioritas'         => status_badge($cur['priority'] === 'cito' ? 'cito' : 'normal'),
          'Jenis Layanan'     => e(ui_label($cur['visit_type'] ?? '-')),
          'Indikasi Klinis'   => e($cur['indication'] ?? '-'),
          'Jadwal Pemeriksaan'=> '<span class="mono">' . tgl($cur['scheduled_at'], true) . '</span>',
          'Gambar / PACS'     => $cur['image_path'] ? ((str_starts_with($cur['image_path'], 'uploads/') || str_starts_with($cur['image_path'], 'file.php?')) ? '<a href="' . e($cur['image_path']) . '" target="_blank">Lihat gambar</a>' : '<span class="mono">' . e($cur['image_path']) . '</span>') : '-',
          'Radiolog'          => e($radName ?: '-'),
      ]) ?>
    </div>
    <div class="card">
      <?php if ($cur['status'] === 'terjadwal'): ?>
        <div class="card-head"><div><h3>Langkah 2 — Penjadwalan</h3><p>Tentukan waktu dan mulai pemeriksaan.</p></div></div>
        <form method="post" class="card-body"><?= csrf_field() ?><input type="hidden" name="act" value="schedule"><input type="hidden" name="rid" value="<?= $id ?>">
          <div class="field"><label>Waktu Pemeriksaan</label><input type="datetime-local" name="scheduled_at" required value="<?= e(date('Y-m-d\TH:i', strtotime($cur['scheduled_at'] ?: 'now'))) ?>"></div>
          <label class="check" style="margin-bottom:14px"><input type="checkbox" required> Identitas pasien & persiapan (lepas logam, skrining kehamilan) sudah dicek</label>
          <button class="btn btn-primary"><?= icon('scan', 16) ?> Mulai Pemeriksaan</button></form>
      <?php elseif ($cur['status'] === 'pemeriksaan'): ?>
        <div class="card-head"><div><h3>Langkah 3 — Pemeriksaan</h3><p>Unggah gambar hasil akuisisi (opsional, atau tersimpan di PACS).</p></div></div>
        <form method="post" enctype="multipart/form-data" class="card-body"><?= csrf_field() ?><input type="hidden" name="act" value="acquire"><input type="hidden" name="rid" value="<?= $id ?>">
          <div class="field"><label>File Gambar (JPG/PNG/DCM/PDF)</label><input type="file" name="image" accept=".jpg,.jpeg,.png,.dcm,.pdf"><span class="field-hint">Kosongkan bila gambar disimpan langsung di PACS.</span></div>
          <button class="btn btn-primary"><?= icon('check', 16) ?> Akuisisi Selesai</button></form>
      <?php elseif ($cur['status'] === 'menunggu_bacaan'): ?>
        <div class="card-head"><div><h3>Langkah 4 — Pembacaan (Expertise)</h3><p>Diisi oleh dokter spesialis radiologi.</p></div></div>
        <form method="post" class="card-body"><?= csrf_field() ?><input type="hidden" name="act" value="report"><input type="hidden" name="rid" value="<?= $id ?>">
          <div class="field"><label>Temuan (Findings)</label><textarea name="findings" required rows="4"></textarea></div>
          <div class="field"><label>Kesan (Impression)</label><textarea name="impression" required rows="2"></textarea></div>
          <button class="btn btn-primary"><?= icon('check', 16) ?> Simpan & Kirim Hasil</button></form>
      <?php else: ?>
        <div class="card-head"><div><h3>Hasil Expertise</h3><p>Dilaporkan <?= tgl($cur['reported_at'], true) ?></p></div></div>
        <?= ui_details(['Temuan' => nl2br(e($cur['findings'])), 'Kesan' => '<b>' . nl2br(e($cur['impression'])) . '</b>']) ?>
      <?php endif; ?>
    </div>
  </section>
<?php endif; ?>
</div>
<?php layout_end();
