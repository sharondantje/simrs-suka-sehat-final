<?php
/*
 * LAYOUT TUNGGAL — sidebar + topbar yang sama untuk SEMUA role.
 * Dipakai portal.php dan semua file di pages/.
 */

function notif_count(string $role): int
{
    try {
        switch ($role) {
            case 'admin':              return (int)q_val("SELECT COUNT(*) FROM audit_logs WHERE DATE(created_at)=CURDATE()");
            case 'petugas_registrasi': return (int)q_val("SELECT COUNT(*) FROM visits WHERE DATE(visit_date)=CURDATE() AND status='menunggu'");
            case 'radiologist':        return (int)q_val("SELECT COUNT(*) FROM radiology_results WHERE status IN ('terjadwal','menunggu_bacaan')");
            case 'dokter':             return (int)q_val("SELECT COUNT(*) FROM visits WHERE DATE(visit_date)=CURDATE() AND status='menunggu'");
            case 'perawat':            return (int)q_val("SELECT COUNT(*) FROM medication_administrations WHERE status='terjadwal' AND DATE(scheduled_at)=CURDATE()");
            case 'kasir':              return (int)q_val("SELECT COUNT(*) FROM invoices WHERE status IN ('belum_bayar','cicilan')");
            case 'farmasi':            return (int)q_val("SELECT COUNT(*) FROM prescriptions WHERE status='baru'");
            case 'laboratorium':       return (int)q_val("SELECT COUNT(*) FROM lab_results WHERE status<>'tervalidasi'");
            case 'gudang':
            case 'kepala_gudang':      return (int)q_val("SELECT COUNT(*) FROM inventory_items WHERE stock<min_stock");
            default:                   return (int)q_val("SELECT COUNT(*) FROM stock_opnames WHERE status='menunggu_approval'");
        }
    } catch (Throwable $e) { return 0; }
}

function layout_start(string $title, string $activeKey, string $section = ''): void
{
    $u     = current_user();
    $menus = role_menus($u['role']);
    $notif = notif_count($u['role']);
    $hosp  = setting('hospital_name', 'RS Suka Sehat');
    ?>
<!doctype html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($title) ?> · SIMRS <?= e($hosp) ?></title>
<link rel="icon" href="assets/logo.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="assets/simrs.css">
</head>
<body>
<div class="app">
  <aside class="sidebar" id="sidebar">
    <div class="sb-brand">
      <img src="assets/logo.png" alt="Logo">
      <div><strong>RS <span>Suka</span> Sehat</strong><small>SIMRS Terintegrasi</small></div>
    </div>
    <div class="sb-user">
      <div class="avatar"><?= e(initials($u['name'])) ?></div>
      <div><b title="<?= e($u['name']) ?>"><?= e($u['name']) ?></b><small><?= e($u['role_label']) ?></small></div>
    </div>
    <nav class="sb-nav">
      <?php foreach ($menus as [$sec, $items]): ?>
        <div class="sb-section"><?= e($sec) ?></div>
        <?php foreach ($items as $it): ?>
          <a class="sb-link <?= $it[0] === $activeKey ? 'active' : '' ?>" href="portal.php?page=<?= urlencode($it[0]) ?>"><?= icon($it[2]) ?><span><?= e($it[1]) ?></span></a>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </nav>
    <div class="sb-foot"><a class="sb-link" href="logout.php" data-confirm="Anda akan keluar dari SIMRS. Lanjutkan?" data-confirm-title="Logout" data-confirm-ok="Logout"><?= icon('logout') ?><span>Logout</span></a></div>
  </aside>
  <div class="backdrop" onclick="document.body.classList.remove('nav-open')"></div>

  <div class="main">
    <header class="topbar">
      <button class="icon-btn menu-toggle" type="button" onclick="document.body.classList.toggle('nav-open')" aria-label="Menu"><?= icon('menu') ?></button>
      <div class="tb-title">
        <div class="breadcrumb">SIMRS<?= $section ? ' / ' . e($section) : '' ?> / <b><?= e($title) ?></b></div>
        <h1><?= e($title) ?></h1>
      </div>
      <form class="tb-search" action="portal.php" method="get" onsubmit="return false">
        <?= icon('search', 16) ?><input type="search" placeholder="Cari menu..." oninput="filterMenu(this.value)">
      </form>
      <div class="tb-date"><b id="clock"><?= date('H:i') ?></b><?= e(tgl_panjang()) ?></div>
      <a class="icon-btn tb-bell" href="portal.php?page=dashboard" title="Notifikasi"><?= icon('bell', 17) ?><?php if ($notif): ?><span class="dot"><?= $notif > 99 ? '99+' : $notif ?></span><?php endif; ?></a>
      <a class="tb-user" href="portal.php?page=profil">
        <div class="avatar"><?= e(initials($u['name'])) ?></div>
        <div><b><?= e($u['name']) ?></b><small><?= e($u['role_label']) ?></small></div>
      </a>
      <a class="icon-btn tb-logout" href="logout.php" title="Logout" data-confirm="Anda akan keluar dari SIMRS. Lanjutkan?" data-confirm-title="Logout" data-confirm-ok="Logout"><?= icon('logout', 17) ?></a>
    </header>
<?php
}

function layout_end(bool $footer = true): void
{
    ?>
    <?php if ($footer): ?><footer class="footer"><span>© <?= date('Y') ?> RS Suka Sehat · SIMRS Terintegrasi</span><span>v2.0 · MySQL</span></footer><?php endif; ?>
  </div>
</div>
<div class="modal" id="confirmModal"><div class="modal-box confirm-box">
  <div class="c-icon"><?= icon('alert', 26) ?></div>
  <h3 id="cTitle">Konfirmasi</h3><p id="cMsg"></p>
  <div class="c-actions"><button type="button" class="btn btn-outline" data-close>Batal</button><button type="button" class="btn btn-danger" id="cOk">Ya, lanjutkan</button></div>
</div></div>
<script>
function filterMenu(v){v=v.toLowerCase();document.querySelectorAll('.sb-link').forEach(a=>{a.style.display=a.textContent.toLowerCase().includes(v)?'':'none'})}
setInterval(()=>{const c=document.getElementById('clock');if(c){const d=new Date();c.textContent=String(d.getHours()).padStart(2,'0')+':'+String(d.getMinutes()).padStart(2,'0')}},15000);
document.querySelectorAll('[data-modal]').forEach(b=>b.addEventListener('click',()=>document.getElementById(b.dataset.modal).classList.add('open')));
document.querySelectorAll('.modal').forEach(m=>m.addEventListener('click',ev=>{if(ev.target===m||ev.target.closest('[data-close]'))m.classList.remove('open')}));
document.addEventListener('keydown',ev=>{if(ev.key==='Escape')document.querySelectorAll('.modal.open').forEach(m=>m.classList.remove('open'))});
/* Konfirmasi aksi merusak lewat modal (bukan confirm() bawaan browser) */
(function(){
  const m=document.getElementById('confirmModal'),ok=document.getElementById('cOk');let go=null;
  function ask(el,fn){document.getElementById('cTitle').textContent=el.dataset.confirmTitle||'Konfirmasi';document.getElementById('cMsg').textContent=el.dataset.confirm;ok.textContent=el.dataset.confirmOk||'Ya, lanjutkan';go=fn;m.classList.add('open');ok.focus()}
  ok.addEventListener('click',()=>{m.classList.remove('open');if(go)go()});
  document.addEventListener('submit',ev=>{const f=ev.target;if(f.dataset.confirm&&!f.dataset.confirmed){ev.preventDefault();ask(f,()=>{f.dataset.confirmed=1;f.requestSubmit?f.requestSubmit():f.submit()})}},true);
  document.addEventListener('click',ev=>{const a=ev.target.closest('a[data-confirm]');if(a){ev.preventDefault();ask(a,()=>location.href=a.href)}});
})();
/* Validasi inline sebelum submit: field wajib, NIK 16 digit, ICD-10, tanggal lahir */
(function(){
  const rules={nik:[/^\d{16}$/,'NIK harus 16 digit angka'],icd:[/^[A-Z]\d{2}(\.\d{1,2})?$/,'Format ICD-10 tidak valid. Contoh: J18.9']};
  function check(inp){
    const f=inp.closest('.field');if(!f)return true;let msg='';const v=inp.value.trim();
    if(inp.required&&!v)msg=((f.querySelector('label')||{}).textContent||'Field ini').replace('*','').trim()+' wajib diisi';
    else if(v&&inp.dataset.rule&&rules[inp.dataset.rule]&&!rules[inp.dataset.rule][0].test(v))msg=rules[inp.dataset.rule][1];
    else if(v&&inp.type==='date'&&inp.dataset.rule==='past'&&new Date(v)>new Date())msg='Tanggal tidak boleh di masa depan';
    else if(v&&inp.type==='email'&&!inp.checkValidity())msg='Format email tidak valid';
    else if(v&&inp.minLength>0&&v.length<inp.minLength)msg='Minimal '+inp.minLength+' karakter';
    let e=f.querySelector('.field-error');f.classList.toggle('invalid',!!msg);
    if(msg){if(!e){e=document.createElement('div');e.className='field-error';f.appendChild(e)}e.textContent=msg}else if(e)e.remove();
    return !msg}
  document.querySelectorAll('.field label').forEach(l=>{const i=l.parentElement.querySelector('input[required],select[required],textarea[required]');if(i&&!l.querySelector('.req'))l.insertAdjacentHTML('beforeend','<span class="req">*</span>')});
  document.addEventListener('blur',ev=>{if(ev.target.matches&&ev.target.matches('.field input,.field select,.field textarea'))check(ev.target)},true);
  document.addEventListener('input',ev=>{if(ev.target.closest&&ev.target.closest('.field.invalid'))check(ev.target)});
  document.addEventListener('submit',ev=>{const f=ev.target;let bad=null;
    f.querySelectorAll('.field input,.field select,.field textarea').forEach(i=>{if(!i.disabled&&!check(i)&&!bad)bad=i});
    if(bad){ev.preventDefault();ev.stopImmediatePropagation();bad.focus();return}
    const b=f.querySelector('button[type=submit],button:not([type])');if(b&&!f.dataset.confirm)b.classList.add('loading')});
})();
/* Wizard generik: <form data-wizard> berisi .wz-panel, tombol .wz-prev/.wz-next/.wz-submit, stepper di dalam .wz-steps */
document.querySelectorAll('form[data-wizard]').forEach(f=>{const ps=[...f.querySelectorAll('.wz-panel')],st=f.querySelectorAll('.wz-steps .step');let c=0;
  const paint=()=>{ps.forEach((p,i)=>p.classList.toggle('hidden',i!==c));st.forEach((s,i)=>{s.classList.toggle('done',i<c);s.classList.toggle('active',i===c);s.querySelector('i').textContent=i<c?'✓':i+1});
    f.querySelectorAll('.wz-prev').forEach(b=>b.disabled=c===0);f.querySelectorAll('.wz-next').forEach(b=>b.classList.toggle('hidden',c===ps.length-1));f.querySelectorAll('.wz-submit').forEach(b=>b.classList.toggle('hidden',c!==ps.length-1))};
  f.querySelectorAll('.wz-next').forEach(b=>b.addEventListener('click',()=>{const bad=[...ps[c].querySelectorAll('input,select,textarea')].find(i=>!i.disabled&&!i.checkValidity());if(bad){bad.reportValidity();return}c++;paint()}));
  f.querySelectorAll('.wz-prev').forEach(b=>b.addEventListener('click',()=>{c--;paint()}));paint()});
/* Toast hilang otomatis + simrsToast() untuk pesan dari JavaScript */
function simrsBindToast(t){const c=()=>{t.classList.add('hide');setTimeout(()=>t.remove(),300)};t.querySelector('button').onclick=c;setTimeout(c,4500)}
function simrsToast(type,msg){let w=document.querySelector('.toast-wrap');if(!w){w=document.createElement('div');w.className='toast-wrap';w.setAttribute('role','status');document.body.appendChild(w)}
  const t=document.createElement('div');t.className='toast '+type;t.innerHTML='<?= str_replace("'", "'", icon('alert', 18)) ?><span></span><button type="button" aria-label="Tutup">&times;</button>';t.querySelector('span').textContent=msg;w.appendChild(t);simrsBindToast(t);
  document.querySelectorAll('.btn.loading').forEach(b=>b.classList.remove('loading'))}
document.querySelectorAll('.toast').forEach(simrsBindToast);
</script>
</body>
</html>
<?php
}

function flashes(): void
{
    $items = take_flash();
    if (!$items) return;
    echo '<div class="toast-wrap" role="status" aria-live="polite">';
    foreach ($items as [$t, $m]) echo '<div class="toast ' . e($t) . '">' . icon($t === 'success' ? 'check' : 'alert', 18) . '<span>' . e($m) . '</span><button type="button" aria-label="Tutup">&times;</button></div>';
    echo '</div>';
}

function status_badge(string $s): string
{
    $map = [
        'success' => ['selesai','lunas','tervalidasi','diserahkan','disetujui','dibayar','aktif','tersedia','pulang','check_in','diberikan','normal','diterima','dikirim','final','hijau','masuk'],
        'warning' => ['menunggu','menunggu_validasi','menunggu_bacaan','menunggu_sampel','belum_bayar','cicilan','diajukan','menunggu_approval','terjadwal','baru','reschedule','dibersihkan','kuning','tinggi','rendah','draft','ditunda','penyesuaian'],
        'info'    => ['diperiksa','diproses','dirawat','diverifikasi','disiapkan','klaim','pemeriksaan','observasi','dipanggil','mutasi','retur','dirujuk','rawat_inap'],
        'danger'  => ['batal','dibatalkan','ditolak','nonaktif','merah','kritis','maintenance','cito','meninggal'],
    ];
    $cls = 'neutral';
    foreach ($map as $c => $list) if (in_array($s, $list, true)) { $cls = $c; break; }
    return '<span class="badge ' . $cls . '">' . e(ucwords(str_replace('_', ' ', $s))) . '</span>';
}
