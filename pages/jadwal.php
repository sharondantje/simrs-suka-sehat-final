<?php
/*
 * MODUL 12 — SCHEDULING & APPOINTMENT (database)
 * appointment : daftar janji temu + buat janji (stepper Pasien → Dokter & Poli → Jadwal → Konfirmasi), check-in, reschedule, batal
 * jadwal      : jadwal praktik dokter (CRUD) + kuota terpakai
 */
const SCH_MOD = 'Scheduling';
$key = $PAGE['key'];
$me  = (int)$user['id'];

/** Slot jadwal dokter pada tanggal tertentu (null bila dokter tidak praktik). */
function sch_slot(int $doctor, string $date): ?array
{
    $dow = (int)date('N', strtotime($date));
    return q_one('SELECT * FROM doctor_schedules WHERE doctor_id=? AND day_of_week=? ORDER BY start_time LIMIT 1', [$doctor, $dow]);
}

if ($act = ui_post_action()) {
    if ($act === 'create' || $act === 'reschedule') {
        $date = (string)($_POST['appointment_date'] ?? ''); $time = (string)($_POST['appointment_time'] ?? '');
        $ap = $act === 'reschedule' ? q_one('SELECT * FROM appointments WHERE id=?', [(int)$_POST['id']]) : null;
        $doctor = $ap ? (int)$ap['doctor_id'] : (int)($_POST['doctor_id'] ?? 0);
        $pid    = $ap ? (int)$ap['patient_id'] : (int)($_POST['patient_id'] ?? 0);
        $err = '';
        if (!$pid || !$doctor || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !preg_match('/^\d{2}:\d{2}/', $time)) $err = 'Lengkapi pasien, dokter, tanggal, dan jam.';
        elseif ($date < date('Y-m-d')) $err = 'Tanggal janji tidak boleh di masa lalu.';
        elseif (!($slot = sch_slot($doctor, $date))) $err = 'Dokter tidak praktik pada hari tersebut. Pilih tanggal sesuai jadwal.';
        elseif ($time . ':00' < $slot['start_time'] || $time . ':00' >= $slot['end_time']) $err = 'Jam di luar jam praktik (' . substr($slot['start_time'], 0, 5) . '–' . substr($slot['end_time'], 0, 5) . ').';
        else {
            $used = (int)q_val("SELECT COUNT(*) FROM appointments WHERE doctor_id=? AND appointment_date=? AND status NOT IN ('dibatalkan') AND id<>?", [$doctor, $date, $ap['id'] ?? 0]);
            if ($used >= $slot['quota']) $err = 'Kuota dokter pada tanggal tersebut sudah penuh.';
            elseif ((int)q_val("SELECT COUNT(*) FROM appointments WHERE patient_id=? AND doctor_id=? AND appointment_date=? AND status NOT IN ('dibatalkan') AND id<>?", [$pid, $doctor, $date, $ap['id'] ?? 0]))
                $err = 'Pasien sudah memiliki janji dengan dokter ini pada tanggal tersebut.';
        }
        if ($err) { flash('danger', $err); redirect(ui_url('appointment', $act === 'create' ? ['new' => 1] : [])); }

        $poli = (int)$slot['poli_id'];
        $code = (string)q_val('SELECT code FROM poli WHERE id=?', [$poli]);
        $n    = (int)q_val("SELECT COUNT(*) FROM appointments WHERE poli_id=? AND appointment_date=? AND status<>'dibatalkan'", [$poli, $date]) + 1;
        $queue = strtoupper($code[0]) . '-' . str_pad((string)$n, 3, '0', STR_PAD_LEFT);
        if ($ap) {
            q("UPDATE appointments SET appointment_date=?, appointment_time=?, queue_no=?, status='reschedule', notes=? WHERE id=?",
              [$date, $time, $queue, trim((string)($_POST['notes'] ?? '')) ?: 'Dijadwalkan ulang', $ap['id']]);
            audit('UPDATE', SCH_MOD, 'Reschedule ' . $ap['appointment_no'] . " ke $date $time");
            flash('success', 'Janji temu dijadwalkan ulang. Antrean baru ' . $queue . '.');
        } else {
            $no = ui_next_no('appointments', 'appointment_no', 'APT-');
            q('INSERT INTO appointments (appointment_no, patient_id, doctor_id, poli_id, appointment_date, appointment_time, queue_no, status, notes) VALUES (?,?,?,?,?,?,?,?,?)',
              [$no, $pid, $doctor, $poli, $date, $time, $queue, 'terjadwal', trim((string)($_POST['notes'] ?? '')) ?: null]);
            audit('CREATE', SCH_MOD, "Buat janji temu $no");
            flash('success', "Janji temu $no tersimpan. Nomor antrean $queue.");
        }
        redirect(ui_url('appointment', ['date' => $date]));
    }
    if ($act === 'checkin') {
        $ap = q_one("SELECT * FROM appointments WHERE id=? AND status IN ('terjadwal','reschedule')", [(int)$_POST['id']]);
        if ($ap && $ap['appointment_date'] === date('Y-m-d')) {
            ui_tx(function () use ($ap) {
                ui_create_visit((int)$ap['patient_id'], 'rawat_jalan', (int)$ap['poli_id'], (int)$ap['doctor_id'], null, $ap['notes'] ?? '');
                q("UPDATE appointments SET status='check_in' WHERE id=?", [$ap['id']]);
            });
            audit('UPDATE', SCH_MOD, 'Check-in ' . $ap['appointment_no'] . ' → kunjungan rawat jalan dibuat');
            flash('success', 'Pasien check-in dan masuk antrean rawat jalan.');
        } else flash('danger', 'Check-in hanya dapat dilakukan pada hari janji temu.');
        redirect(ui_url('appointment'));
    }
    if ($act === 'cancel') {
        $why = trim((string)($_POST['reason'] ?? '')) ?: 'Dibatalkan';
        q("UPDATE appointments SET status='dibatalkan', notes=? WHERE id=? AND status IN ('terjadwal','reschedule')", [$why, (int)$_POST['id']]);
        audit('CANCEL', SCH_MOD, 'Batalkan janji #' . (int)$_POST['id'] . ": $why");
        flash('warning', 'Janji temu dibatalkan.');
        redirect(ui_url('appointment'));
    }
    if ($act === 'sched_save') {
        $d = [(int)$_POST['doctor_id'], (int)$_POST['poli_id'], (int)$_POST['day_of_week'], $_POST['start_time'], $_POST['end_time'], max(1, (int)$_POST['quota'])];
        if ($d[3] >= $d[4]) { flash('danger', 'Jam selesai harus setelah jam mulai.'); redirect(ui_url('jadwal')); }
        $sid = (int)($_POST['id'] ?? 0);
        $clash = q_val('SELECT COUNT(*) FROM doctor_schedules WHERE doctor_id=? AND day_of_week=? AND id<>? AND start_time < ? AND end_time > ?', [$d[0], $d[2], $sid, $d[4], $d[3]]);
        if ($clash) { flash('danger', 'Jadwal bentrok dengan jadwal lain dokter tersebut di hari yang sama.'); redirect(ui_url('jadwal')); }
        if ($sid) q('UPDATE doctor_schedules SET doctor_id=?, poli_id=?, day_of_week=?, start_time=?, end_time=?, quota=? WHERE id=?', [...$d, $sid]);
        else q('INSERT INTO doctor_schedules (doctor_id, poli_id, day_of_week, start_time, end_time, quota) VALUES (?,?,?,?,?,?)', $d);
        audit($sid ? 'UPDATE' : 'CREATE', SCH_MOD, 'Jadwal dokter #' . $d[0] . ' hari ' . UI_DAYS[$d[2]]);
        flash('success', 'Jadwal praktik tersimpan.');
        redirect(ui_url('jadwal'));
    }
    if ($act === 'sched_del') {
        q('DELETE FROM doctor_schedules WHERE id=?', [(int)$_POST['id']]);
        audit('DELETE', SCH_MOD, 'Hapus jadwal dokter #' . (int)$_POST['id']);
        flash('success', 'Jadwal dihapus.');
        redirect(ui_url('jadwal'));
    }
}

$doctors   = q_all('SELECT d.*, p.name poli FROM doctors d LEFT JOIN poli p ON p.id=d.poli_id WHERE d.is_active=1 AND d.poli_id IS NOT NULL ORDER BY d.name');
$polis     = q_all("SELECT * FROM poli WHERE is_active=1 AND code<>'IGD' ORDER BY name");
$schedules = q_all('SELECT s.*, d.name doctor, p.name poli FROM doctor_schedules s JOIN doctors d ON d.id=s.doctor_id JOIN poli p ON p.id=s.poli_id ORDER BY s.day_of_week, s.start_time');

layout_start($PAGE['title'], $key, $PAGE['section']);
?>
<div class="content">
<?php if ($key === 'appointment'):
  $date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date'] ?? '') ? $_GET['date'] : date('Y-m-d');
  $new  = isset($_GET['new']);
  $aps  = q_all('SELECT a.*, p.name patient, p.mrn, p.phone, d.name doctor, po.name poli FROM appointments a JOIN patients p ON p.id=a.patient_id
                 JOIN doctors d ON d.id=a.doctor_id JOIN poli po ON po.id=a.poli_id WHERE a.appointment_date=? ORDER BY a.appointment_time', [$date]);
  $c = fn($s) => count(array_filter($aps, fn($a) => $a['status'] === $s)); ?>
  <div class="page-head">
    <div><h2><?= $new ? 'Buat Janji Temu' : 'Appointment' ?></h2><p><?= $new ? 'Ikuti langkah berikut untuk mendaftarkan janji temu pasien.' : 'Janji temu pasien per tanggal, check-in, jadwal ulang, dan pembatalan.' ?></p></div>
    <div class="page-actions"><?php if ($new): ?><a class="btn" href="<?= e(ui_url('appointment')) ?>">← Kembali</a><?php else: ?><a class="btn btn-primary" href="<?= e(ui_url('appointment', ['new' => 1])) ?>"><?= icon('plus', 16) ?> Buat Janji Temu</a><?php endif; ?></div>
  </div>
  <?php flashes(); ?>

  <?php if ($new):
    $patients = q_all('SELECT p.id, p.mrn, p.name, p.nik, p.birth_date, p.phone, i.name insurer FROM patients p LEFT JOIN insurers i ON i.id=p.insurer_id ORDER BY p.name');
    $schedJs = [];
    foreach ($schedules as $s) $schedJs[$s['doctor_id']][] = ['d' => (int)$s['day_of_week'], 's' => substr($s['start_time'], 0, 5), 'e' => substr($s['end_time'], 0, 5), 'q' => (int)$s['quota']]; ?>
  <div class="card stepper-card" id="wizStep"><?= ui_stepper(['Pilih Pasien', 'Dokter & Poli', 'Pilih Jadwal', 'Konfirmasi'], 0) ?></div>
  <form method="post" class="card" id="wiz"><?= csrf_field() ?><input type="hidden" name="act" value="create">
    <div class="card-body" data-step="0">
      <h3 style="margin-bottom:12px">Langkah 1 — Pilih Pasien</h3>
      <div class="field"><label>Cari pasien terdaftar (nama / No. RM / NIK)</label><input id="pSearch" placeholder="Ketik minimal 2 huruf..." autocomplete="off"></div>
      <div class="table-wrap" style="max-height:300px;overflow:auto;border:1px solid var(--line);border-radius:10px"><table class="table" id="pTable"><thead><tr><th></th><th>No. RM</th><th>Nama</th><th>Tgl. Lahir</th><th>Penjamin</th></tr></thead><tbody>
      <?php foreach ($patients as $p): ?><tr class="row-link" data-s="<?= e(strtolower($p['name'] . ' ' . $p['mrn'] . ' ' . $p['nik'])) ?>"><td><input type="radio" name="patient_id" value="<?= $p['id'] ?>" data-label="<?= e($p['name'] . ' (' . $p['mrn'] . ')') ?>"></td><td class="mono"><?= e($p['mrn']) ?></td><td class="strong"><?= e($p['name']) ?></td><td class="mono"><?= tgl($p['birth_date']) ?></td><td><?= e($p['insurer'] ?? '-') ?></td></tr><?php endforeach; ?>
      </tbody></table></div>
      <p class="field-hint" style="margin-top:8px">Pasien baru? Daftarkan dahulu lewat menu Registrasi Pasien.</p>
    </div>
    <div class="card-body hidden" data-step="1">
      <h3 style="margin-bottom:12px">Langkah 2 — Dokter & Poli</h3>
      <div class="form-grid">
        <div class="field"><label>Poli</label><select id="poliSel"><option value="">Semua poli</option><?= ui_options($polis, 'id', 'name') ?></select></div>
        <div class="field"><label>Dokter</label><select name="doctor_id" id="docSel"><option value="">— pilih dokter —</option><?php foreach ($doctors as $d): ?><option value="<?= $d['id'] ?>" data-poli="<?= $d['poli_id'] ?>"><?= e($d['name'] . ' — ' . $d['poli']) ?></option><?php endforeach; ?></select></div>
      </div>
      <div id="docDays" class="field-hint"></div>
    </div>
    <div class="card-body hidden" data-step="2">
      <h3 style="margin-bottom:12px">Langkah 3 — Pilih Jadwal</h3>
      <div class="form-grid">
        <div class="field"><label>Tanggal</label><input type="date" name="appointment_date" id="apDate" min="<?= date('Y-m-d') ?>"></div>
        <div class="field"><label>Jam</label><input type="time" name="appointment_time" id="apTime" step="900"></div>
        <div class="field full"><label>Keluhan / Catatan</label><input name="notes" placeholder="Opsional, mis. kontrol ulang"></div>
      </div>
      <div id="slotInfo" class="alert info hidden"></div>
    </div>
    <div class="card-body hidden" data-step="3">
      <h3 style="margin-bottom:12px">Langkah 4 — Konfirmasi</h3>
      <dl class="details" id="confirmBox" style="padding:0"></dl>
      <p class="field-hint">Nomor antrean dibuat otomatis setelah disimpan. Sistem menolak bila kuota penuh atau jadwal bentrok.</p>
    </div>
    <div class="card-foot"><button type="button" class="btn" id="bPrev" disabled>← Sebelumnya</button><button type="button" class="btn btn-primary" id="bNext">Lanjut →</button><button class="btn btn-primary hidden" id="bSave"><?= icon('check', 16) ?> Simpan Janji Temu</button></div>
  </form>
  <script>
  (function(){
    const S=<?= json_encode($schedJs) ?>, DAYS=<?= json_encode(UI_DAYS) ?>, labels=['Pilih Pasien','Dokter & Poli','Pilih Jadwal','Konfirmasi'];
    let cur=0; const steps=[...document.querySelectorAll('[data-step]')], f=document.getElementById('wiz');
    const $=id=>document.getElementById(id);
    function paint(){steps.forEach((s,i)=>s.classList.toggle('hidden',i!==cur));
      $('wizStep').innerHTML='<div class="stepper">'+labels.map((l,i)=>`<div class="step ${i<cur?'done':i===cur?'active':''}"><i>${i<cur?'✓':i+1}</i><span>${l}</span></div>`).join('')+'</div>';
      $('bPrev').disabled=cur===0;$('bNext').classList.toggle('hidden',cur===3);$('bSave').classList.toggle('hidden',cur!==3)}
    function slot(){const d=$('docSel').value,v=$('apDate').value;if(!d||!v)return null;const dow=((new Date(v+'T00:00').getDay()+6)%7)+1;return (S[d]||[]).find(x=>x.d===dow)||false}
    function valid(){
      if(cur===0&&!f.querySelector('[name=patient_id]:checked'))return 'Pilih pasien terlebih dahulu.';
      if(cur===1&&!$('docSel').value)return 'Pilih dokter.';
      if(cur===2){const s=slot();if(!$('apDate').value||!$('apTime').value)return 'Isi tanggal dan jam.';if(s===false)return 'Dokter tidak praktik pada hari tersebut.';if($('apTime').value<s.s||$('apTime').value>=s.e)return `Jam praktik ${s.s}–${s.e}.`}
      return ''}
    $('bNext').onclick=()=>{const m=valid();if(m){simrsToast('warning',m);return}cur++;
      if(cur===3){const p=f.querySelector('[name=patient_id]:checked'),d=$('docSel');
        $('confirmBox').innerHTML=[['Pasien',p.dataset.label],['Dokter',d.options[d.selectedIndex].text],['Tanggal',$('apDate').value],['Jam',$('apTime').value],['Catatan',f.notes.value||'-']].map(([k,v])=>`<div><dt>${k}</dt><dd></dd></div>`).join('');
        [...$('confirmBox').querySelectorAll('dd')].forEach((dd,i)=>dd.textContent=[p.dataset.label,d.options[d.selectedIndex].text,$('apDate').value,$('apTime').value,f.notes.value||'-'][i])}
      paint()};
    $('bPrev').onclick=()=>{cur--;paint()};
    $('pSearch').oninput=e=>{const v=e.target.value.toLowerCase();document.querySelectorAll('#pTable tbody tr').forEach(r=>r.style.display=!v||r.dataset.s.includes(v)?'':'none')};
    document.querySelectorAll('#pTable tbody tr').forEach(r=>r.onclick=()=>{r.querySelector('input').checked=true;document.querySelectorAll('#pTable tr').forEach(x=>x.classList.remove('tr-active'));r.classList.add('tr-active')});
    $('poliSel').onchange=e=>{[...$('docSel').options].forEach(o=>{if(o.value)o.hidden=e.target.value&&o.dataset.poli!==e.target.value});$('docSel').value=''};
    $('docSel').onchange=()=>{const s=S[$('docSel').value]||[];$('docDays').textContent=s.length?'Jadwal praktik: '+s.map(x=>`${DAYS[x.d]} ${x.s}–${x.e} (kuota ${x.q})`).join(' · '):''};
    $('apDate').onchange=()=>{const s=slot(),i=$('slotInfo');i.classList.toggle('hidden',s===null);if(s===false){i.className='alert warning';i.textContent='Dokter tidak praktik pada hari ini.'}else if(s){i.className='alert info';i.textContent=`Praktik ${s.s}–${s.e}, kuota ${s.q} pasien.`;if(!$('apTime').value)$('apTime').value=s.s}};
    paint();
  })();
  </script>

  <?php else: ?>
  <section class="kpi-grid">
    <?= ui_kpi('Total Janji', count($aps), 'calendar', '', tgl($date)) ?>
    <?= ui_kpi('Terjadwal', $c('terjadwal') + $c('reschedule'), 'clock', 'warning') ?>
    <?= ui_kpi('Check-in', $c('check_in'), 'check', 'info') ?>
    <?= ui_kpi('Dibatalkan', $c('dibatalkan'), 'x-circle', 'danger') ?>
  </section>
  <div class="card">
    <form class="toolbar" method="get"><input type="hidden" name="page" value="appointment"><b style="color:var(--heading)">Janji Temu</b><span class="grow"></span>
      <a class="btn btn-sm" href="<?= e(ui_url('appointment', ['date' => date('Y-m-d', strtotime($date . ' -1 day'))])) ?>">‹</a>
      <input class="input" type="date" name="date" value="<?= e($date) ?>" onchange="this.form.submit()">
      <a class="btn btn-sm" href="<?= e(ui_url('appointment', ['date' => date('Y-m-d', strtotime($date . ' +1 day'))])) ?>">›</a></form>
    <div class="table-wrap"><table class="table">
      <thead><tr><th>No.</th><th>Jam</th><th>Antrean</th><th>Pasien</th><th>Dokter / Poli</th><th>Catatan</th><th>Status</th><th style="text-align:right">Aksi</th></tr></thead>
      <tbody><?php if (!$aps): ?><tr><td colspan="8" class="empty">Tidak ada janji temu pada tanggal ini.</td></tr><?php endif; foreach ($aps as $a): $open = in_array($a['status'], ['terjadwal', 'reschedule'], true); ?>
        <tr><td class="mono"><?= e($a['appointment_no']) ?></td><td class="mono strong"><?= substr($a['appointment_time'], 0, 5) ?></td><td class="mono"><?= e($a['queue_no']) ?></td>
          <td><b><?= e($a['patient']) ?></b><br><small class="mono muted"><?= e($a['mrn']) ?></small></td><td><?= e($a['doctor']) ?><br><small class="muted"><?= e($a['poli']) ?></small></td>
          <td><?= e($a['notes'] ?? '-') ?></td><td><?= status_badge($a['status']) ?></td>
          <td><div class="actions"><?php if ($open): ?>
            <?php if ($a['appointment_date'] === date('Y-m-d')): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="act" value="checkin"><input type="hidden" name="id" value="<?= $a['id'] ?>"><button class="btn btn-sm btn-primary">Check-in</button></form><?php endif; ?>
            <button class="btn btn-sm" data-modal="mResch" onclick='resch(<?= json_encode(['id' => $a['id'], 'no' => $a['appointment_no'], 'p' => $a['patient'], 'd' => $a['appointment_date'], 't' => substr($a['appointment_time'], 0, 5)], JSON_HEX_APOS) ?>)'>Jadwal Ulang</button>
            <form method="post" data-confirm="Janji temu <?= e($a['appointment_no']) ?> akan dibatalkan." data-confirm-title="Batalkan Janji Temu" data-confirm-ok="Batalkan"><?= csrf_field() ?><input type="hidden" name="act" value="cancel"><input type="hidden" name="id" value="<?= $a['id'] ?>"><input type="hidden" name="reason" value="Dibatalkan oleh petugas"><button class="icon-btn danger" title="Batalkan"><?= icon('x-circle', 15) ?></button></form>
          <?php endif; ?></div></td></tr>
      <?php endforeach; ?></tbody>
    </table></div>
  </div>
  <div class="modal" id="mResch"><div class="modal-box">
    <div class="card-head"><h3>Jadwal Ulang <span id="rNo" class="mono"></span></h3><button class="icon-btn" data-close type="button">✕</button></div>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="act" value="reschedule"><input type="hidden" name="id" id="rId">
      <p class="muted" id="rP" style="margin:0 0 12px"></p>
      <div class="form-grid"><div class="field"><label>Tanggal Baru</label><input type="date" name="appointment_date" id="rD" min="<?= date('Y-m-d') ?>" required></div>
        <div class="field"><label>Jam</label><input type="time" name="appointment_time" id="rT" required></div>
        <div class="field full"><label>Alasan</label><input name="notes" required placeholder="Contoh: permintaan pasien"></div></div>
      <div class="modal-foot"><button type="button" class="btn" data-close>Batal</button><button class="btn btn-primary">Simpan</button></div></form>
  </div></div>
  <script>function resch(a){rId.value=a.id;rNo.textContent=a.no;rP.textContent=a.p;rD.value=a.d;rT.value=a.t}</script>
  <?php endif; ?>

<?php else: /* ===================== JADWAL DOKTER ===================== */
  $nextUse = [];
  foreach ($schedules as $s) {
      $d = date('Y-m-d', strtotime('today'));
      while ((int)date('N', strtotime($d)) !== (int)$s['day_of_week']) $d = date('Y-m-d', strtotime("$d +1 day"));
      $nextUse[$s['id']] = [$d, (int)q_val("SELECT COUNT(*) FROM appointments WHERE doctor_id=? AND appointment_date=? AND status<>'dibatalkan'", [$s['doctor_id'], $d])];
  }
  $byDay = [];
  foreach ($schedules as $s) $byDay[$s['day_of_week']][] = $s; ?>
  <div class="page-head"><div><h2>Jadwal Dokter</h2><p>Jadwal praktik dokter per poli beserta kuota pasien.</p></div>
    <div class="page-actions"><button class="btn btn-primary" data-modal="mSched" onclick="editS(null)"><?= icon('plus', 16) ?> Tambah Jadwal</button></div></div>
  <?php flashes(); ?>
  <div class="card">
    <div class="table-wrap"><table class="table">
      <thead><tr><th>Hari</th><th>Dokter</th><th>Poli</th><th>Jam Praktik</th><th>Kuota</th><th>Terisi (tanggal terdekat)</th><th style="text-align:right">Aksi</th></tr></thead>
      <tbody><?php foreach (UI_DAYS as $dn => $dl): foreach ($byDay[$dn] ?? [] as $i => $s): [$nd, $used] = $nextUse[$s['id']]; ?>
        <tr><td class="strong"><?= $i === 0 ? e($dl) : '' ?></td><td><?= e($s['doctor']) ?></td><td><?= e($s['poli']) ?></td>
          <td class="mono"><?= substr($s['start_time'], 0, 5) ?>–<?= substr($s['end_time'], 0, 5) ?></td><td class="mono"><?= (int)$s['quota'] ?></td>
          <td style="min-width:160px"><div class="stat-row" style="margin:0 0 4px"><small class="mono muted"><?= tgl($nd) ?></small><b><?= $used ?>/<?= (int)$s['quota'] ?></b></div><div class="progress"><div style="width:<?= min(100, round($used / max(1, $s['quota']) * 100)) ?>%"></div></div></td>
          <td><div class="actions"><button class="icon-btn" data-modal="mSched" onclick='editS(<?= json_encode($s, JSON_HEX_APOS) ?>)' title="Ubah"><?= icon('edit', 15) ?></button>
            <form method="post" data-confirm="Hapus jadwal praktik ini?" data-confirm-title="Hapus Jadwal" data-confirm-ok="Hapus"><?= csrf_field() ?><input type="hidden" name="act" value="sched_del"><input type="hidden" name="id" value="<?= $s['id'] ?>"><button class="icon-btn danger" title="Hapus"><?= icon('trash', 15) ?></button></form></div></td></tr>
      <?php endforeach; endforeach; ?></tbody>
    </table></div>
  </div>
  <div class="modal" id="mSched"><div class="modal-box">
    <div class="card-head"><h3 id="sTitle">Jadwal Praktik</h3><button class="icon-btn" data-close type="button">✕</button></div>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="act" value="sched_save"><input type="hidden" name="id" id="s_id">
      <div class="form-grid">
        <div class="field full"><label>Dokter</label><select name="doctor_id" id="s_doctor_id" required><?= ui_options($doctors, 'id', 'name') ?></select></div>
        <div class="field"><label>Poli</label><select name="poli_id" id="s_poli_id" required><?= ui_options($polis, 'id', 'name') ?></select></div>
        <div class="field"><label>Hari</label><select name="day_of_week" id="s_day_of_week"><?= ui_opt_map(UI_DAYS) ?></select></div>
        <div class="field"><label>Jam Mulai</label><input type="time" name="start_time" id="s_start_time" required></div>
        <div class="field"><label>Jam Selesai</label><input type="time" name="end_time" id="s_end_time" required></div>
        <div class="field"><label>Kuota Pasien</label><input type="number" min="1" name="quota" id="s_quota" required value="20"></div>
      </div>
      <div class="modal-foot"><button type="button" class="btn" data-close>Batal</button><button class="btn btn-primary">Simpan</button></div></form>
  </div></div>
  <script>function editS(s){sTitle.textContent=s?'Ubah Jadwal Praktik':'Tambah Jadwal Praktik';s_id.value=s?s.id:'';
    ['doctor_id','poli_id','day_of_week','quota'].forEach(k=>document.getElementById('s_'+k).value=s?s[k]:(k==='quota'?20:document.getElementById('s_'+k).options?.[0]?.value));
    s_start_time.value=s?s.start_time.slice(0,5):'08:00';s_end_time.value=s?s.end_time.slice(0,5):'12:00'}
    s_doctor_id.addEventListener('change',()=>{const p=<?= json_encode(array_column($doctors, 'poli_id', 'id')) ?>[s_doctor_id.value];if(p)s_poli_id.value=p});</script>
<?php endif; ?>
</div>
<?php layout_end();
