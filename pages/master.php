<?php
/* MASTER DATA — Modul 16: dokter, poli, kamar, tempat tidur, tarif, ICD-10, obat, lab, radiologi, penjamin */
$self = 'portal.php?page=' . $PAGE['key'];

$opt = fn(string $sql) => array_column(q_all($sql), 'label', 'id');
$TABLES = [
    'doctors' => ['Dokter', 'id', [
        'nip' => ['NIP', 'text'], 'name' => ['Nama Dokter', 'text'], 'specialization' => ['Spesialisasi', 'text'],
        'poli_id' => ['Poli', 'select', fn() => $opt('SELECT id, name label FROM poli ORDER BY name')],
        'sip_number' => ['No. SIP', 'text'], 'phone' => ['No. HP', 'text'], 'is_active' => ['Status', 'select', fn() => [1 => 'Aktif', 0 => 'Nonaktif']]],
        'SELECT d.*, p.name poli_id__label FROM doctors d LEFT JOIN poli p ON p.id=d.poli_id ORDER BY d.id', 'stetho'],
    'poli' => ['Poliklinik', 'id', ['code' => ['Kode', 'text'], 'name' => ['Nama Poli', 'text'], 'is_active' => ['Status', 'select', fn() => [1 => 'Aktif', 0 => 'Nonaktif']]],
        'SELECT * FROM poli ORDER BY id', 'hospital'],
    'rooms' => ['Kamar', 'id', ['code' => ['Kode', 'text'], 'name' => ['Nama Kamar', 'text'], 'ward' => ['Bangsal', 'text'],
        'class' => ['Kelas', 'select', fn() => array_combine($c = ['VIP', 'I', 'II', 'III', 'ICU', 'IGD'], $c)], 'tariff_per_day' => ['Tarif / Hari', 'money']],
        'SELECT * FROM rooms ORDER BY id', 'bed'],
    'beds' => ['Tempat Tidur', 'id', ['room_id' => ['Kamar', 'select', fn() => $opt('SELECT id, name label FROM rooms ORDER BY id')], 'bed_number' => ['No. Bed', 'text'],
        'status' => ['Status', 'select', fn() => array_combine($c = ['tersedia', 'terisi', 'dibersihkan', 'maintenance'], $c)]],
        'SELECT b.*, r.name room_id__label FROM beds b JOIN rooms r ON r.id=b.room_id ORDER BY b.room_id, b.bed_number', 'bed'],
    'tariffs' => ['Tarif Layanan', 'id', ['code' => ['Kode', 'text'], 'name' => ['Nama Layanan', 'text'],
        'category' => ['Kategori', 'select', fn() => array_combine($c = ['administrasi', 'konsultasi', 'tindakan', 'laboratorium', 'radiologi', 'kamar', 'lainnya'], $c)], 'price' => ['Tarif', 'money']],
        'SELECT * FROM tariffs ORDER BY category, id', 'money'],
    'icd10' => ['ICD-10', 'code', ['code' => ['Kode ICD', 'text'], 'description' => ['Diagnosis', 'text'], 'category' => ['Kategori', 'text']],
        'SELECT * FROM icd10 ORDER BY code', 'clipboard'],
    'drugs' => ['Obat', 'id', ['code' => ['Kode', 'text'], 'name' => ['Nama Obat', 'text'], 'form' => ['Bentuk', 'text'], 'unit' => ['Satuan', 'text'], 'category' => ['Golongan', 'text'],
        'price' => ['Harga', 'money'], 'stock' => ['Stok', 'number'], 'min_stock' => ['Stok Min', 'number'], 'expiry_date' => ['Kedaluwarsa', 'date']],
        'SELECT * FROM drugs ORDER BY id', 'pill'],
    'lab_tests' => ['Pemeriksaan Lab', 'id', ['code' => ['Kode', 'text'], 'name' => ['Pemeriksaan', 'text'], 'category' => ['Kategori', 'text'], 'unit' => ['Satuan', 'text'],
        'normal_range' => ['Nilai Normal', 'text'], 'price' => ['Tarif', 'money']], 'SELECT * FROM lab_tests ORDER BY id', 'flask'],
    'radiology_exams' => ['Pemeriksaan Radiologi', 'id', ['code' => ['Kode', 'text'], 'name' => ['Pemeriksaan', 'text'],
        'modality' => ['Modalitas', 'select', fn() => array_combine($c = ['X-Ray', 'CT Scan', 'MRI', 'USG', 'Mammografi'], $c)], 'price' => ['Tarif', 'money']],
        'SELECT * FROM radiology_exams ORDER BY id', 'scan'],
    'insurers' => ['Penjamin', 'id', ['code' => ['Kode', 'text'], 'name' => ['Nama Penjamin', 'text'],
        'type' => ['Jenis', 'select', fn() => array_combine($c = ['umum', 'bpjs', 'asuransi', 'perusahaan'], $c)]],
        'SELECT * FROM insurers ORDER BY id', 'shield'],
];

$tab = $_GET['t'] ?? 'doctors';
if (!isset($TABLES[$tab])) $tab = 'doctors';
[$tLabel, $pk, $fields, $listSql, $tIcon] = $TABLES[$tab];
$back = $self . '&t=' . $tab;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $act = $_POST['act'] ?? '';
    $key = $_POST['key'] ?? '';
    try {
        if ($act === 'save') {
            $vals = [];
            foreach ($fields as $f => $_) { $v = trim((string)($_POST[$f] ?? '')); $vals[$f] = $v === '' ? null : $v; }
            if ($key !== '') {
                $set = implode(', ', array_map(fn($f) => "`$f` = ?", array_keys($vals)));
                q("UPDATE `$tab` SET $set WHERE `$pk` = ?", array_merge(array_values($vals), [$key]));
                audit('UPDATE', 'Master Data', "Ubah $tLabel: $key");
            } else {
                $cols = implode(', ', array_map(fn($f) => "`$f`", array_keys($vals)));
                q("INSERT INTO `$tab` ($cols) VALUES (" . implode(',', array_fill(0, count($vals), '?')) . ')', array_values($vals));
                audit('CREATE', 'Master Data', "Tambah $tLabel: " . ($vals['name'] ?? $vals['code'] ?? $vals['bed_number'] ?? ''));
            }
            flash('success', "Data $tLabel berhasil disimpan.");
        } elseif ($act === 'delete' && $key !== '') {
            q("DELETE FROM `$tab` WHERE `$pk` = ?", [$key]);
            audit('DELETE', 'Master Data', "Hapus $tLabel: $key");
            flash('success', "Data $tLabel dihapus.");
        }
    } catch (PDOException $ex) {
        $msg = $ex->getCode() === '23000' ? 'Data tidak bisa disimpan/dihapus: kode sudah dipakai atau data masih direferensikan transaksi lain.' : 'Gagal menyimpan: ' . $ex->getMessage();
        flash('danger', $msg);
    }
    redirect($back);
}

$rows   = q_all($listSql);
$search = trim($_GET['q'] ?? '');
if ($search !== '') $rows = array_values(array_filter($rows, fn($r) => stripos(implode(' ', array_map('strval', $r)), $search) !== false));
$options = [];
foreach ($fields as $f => $def) if ($def[1] === 'select') $options[$f] = $def[2]();

function master_cell(array $r, string $f, array $def): string
{
    $v = $r[$f] ?? null;
    if (isset($r[$f . '__label'])) return e($r[$f . '__label']);
    if ($def[1] === 'money') return rupiah($v);
    if ($def[1] === 'date') return tgl($v);
    if ($f === 'is_active') return $v ? '<span class="badge success">Aktif</span>' : '<span class="badge danger">Nonaktif</span>';
    if ($f === 'status') return status_badge((string)$v);
    if ($f === 'stock' && isset($r['min_stock']) && $v < $r['min_stock']) return '<span class="badge danger">' . e($v) . '</span>';
    return e($v);
}

layout_start($PAGE['title'], $PAGE['key'], $PAGE['section']);
?>
<div class="content">
  <div class="page-head">
    <div><h2>Master Data</h2><p>Data referensi yang dipakai seluruh modul SIMRS.</p></div>
    <div class="page-actions"><button class="btn btn-primary" data-modal="mMaster" onclick="editRow(null)"><?= icon('plus', 16) ?> Tambah <?= e($tLabel) ?></button></div>
  </div>
  <?php flashes(); ?>

  <nav class="tabs">
    <?php foreach ($TABLES as $k => $t): ?>
      <a class="tab <?= $k === $tab ? 'active' : '' ?>" href="<?= e($self . '&t=' . $k) ?>"><?= e($t[0]) ?></a>
    <?php endforeach; ?>
  </nav>

  <div class="card">
    <form class="toolbar" method="get">
      <input type="hidden" name="page" value="<?= e($PAGE['key']) ?>"><input type="hidden" name="t" value="<?= e($tab) ?>">
      <span class="kpi-icon" style="width:36px;height:36px"><?= icon($tIcon, 18) ?></span>
      <b style="color:var(--heading)"><?= e($tLabel) ?></b><span class="badge"><?= count($rows) ?> data</span>
      <span class="grow"></span>
      <input class="input" name="q" value="<?= e($search) ?>" placeholder="Cari <?= e(strtolower($tLabel)) ?>...">
      <button class="btn"><?= icon('search', 15) ?> Cari</button>
    </form>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><?php foreach ($fields as $def): ?><th><?= e($def[0]) ?></th><?php endforeach; ?><th style="text-align:right">Aksi</th></tr></thead>
        <tbody>
        <?php if (!$rows): ?><tr><td colspan="<?= count($fields) + 1 ?>" class="empty">Belum ada data.</td></tr><?php endif; ?>
        <?php foreach ($rows as $r): ?>
          <tr>
            <?php $first = true; foreach ($fields as $f => $def): ?><td class="<?= $first ? 'strong' : '' ?><?= in_array($f, ['code', 'nip', 'bed_number', 'sip_number'], true) ? ' mono' : '' ?>"><?= master_cell($r, $f, $def) ?></td><?php $first = false; endforeach; ?>
            <td><div class="actions">
              <button class="icon-btn" title="Ubah" data-modal="mMaster" onclick='editRow(<?= json_encode(array_intersect_key($r, $fields + [$pk => 1]), JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'><?= icon('edit', 15) ?></button>
              <form method="post" data-confirm="Data yang dihapus tidak dapat dikembalikan. Hapus data ini?" data-confirm-title="Hapus Data" data-confirm-ok="Hapus"><?= csrf_field() ?><input type="hidden" name="act" value="delete"><input type="hidden" name="key" value="<?= e($r[$pk]) ?>"><button class="icon-btn danger" title="Hapus"><?= icon('trash', 15) ?></button></form>
            </div></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="modal" id="mMaster"><div class="modal-box">
  <div class="card-head"><h3 id="mTitle"><?= e($tLabel) ?></h3><button class="icon-btn" data-close type="button">✕</button></div>
  <form method="post">
    <?= csrf_field() ?><input type="hidden" name="act" value="save"><input type="hidden" name="key" id="mKey">
    <div class="form-grid">
      <?php foreach ($fields as $f => $def): ?>
        <div class="field <?= in_array($f, ['name', 'description'], true) ? 'full' : '' ?>"><label><?= e($def[0]) ?></label>
          <?php if ($def[1] === 'select'): ?>
            <select name="<?= e($f) ?>" id="f_<?= e($f) ?>"><?php foreach ($options[$f] as $ov => $ol): ?><option value="<?= e($ov) ?>"><?= e($ol) ?></option><?php endforeach; ?></select>
          <?php else: ?>
            <input name="<?= e($f) ?>" id="f_<?= e($f) ?>" type="<?= $def[1] === 'money' || $def[1] === 'number' ? 'number' : ($def[1] === 'date' ? 'date' : 'text') ?>" <?= in_array($f, ['code', 'name', 'nip', 'bed_number', 'description'], true) ? 'required' : '' ?><?= $tab === 'icd10' && $f === 'code' ? ' data-rule="icd" placeholder="Contoh: J18.9"' : '' ?> step="any">
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="modal-foot"><button type="button" class="btn" data-close>Batal</button><button class="btn btn-primary">Simpan</button></div>
  </form>
</div></div>
<script>
const FIELDS=<?= json_encode(array_keys($fields)) ?>, PK=<?= json_encode($pk) ?>;
function editRow(r){
  document.getElementById('mTitle').textContent=(r?'Ubah ':'Tambah ')+<?= json_encode($tLabel) ?>;
  document.getElementById('mKey').value=r?r[PK]:'';
  FIELDS.forEach(f=>{const el=document.getElementById('f_'+f); if(!el)return; el.value=r&&r[f]!==null?r[f]:(el.tagName==='SELECT'?el.options[0].value:'');});
}
</script>
<?php layout_end();
