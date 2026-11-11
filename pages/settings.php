<?php
/* KONFIGURASI SISTEM — tabel settings */
$self = 'portal.php?page=' . $PAGE['key'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($_POST['s'] ?? [] as $k => $v) q('UPDATE settings SET setting_value=? WHERE setting_key=?', [trim((string)$v), $k]);
    audit('UPDATE', 'Konfigurasi Sistem', 'Memperbarui konfigurasi sistem');
    flash('success', 'Konfigurasi berhasil disimpan.');
    redirect($self);
}

$settings = q_all('SELECT * FROM settings');
$dbInfo = [
    ['Database', DB_NAME], ['Server', DB_HOST . ':' . DB_PORT],
    ['Versi MySQL', q_val('SELECT VERSION()')], ['Jumlah Tabel', q_val('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()')],
    ['Ukuran Data', angka(q_val('SELECT SUM(data_length+index_length)/1024 FROM information_schema.tables WHERE table_schema = DATABASE()'), 0) . ' KB'],
    ['Versi PHP', PHP_VERSION],
];

layout_start($PAGE['title'], $PAGE['key'], $PAGE['section']);
?>
<div class="content">
  <div class="page-head"><div><h2>Konfigurasi Sistem</h2><p>Identitas rumah sakit, keamanan akun, dan parameter perhitungan.</p></div></div>
  <?php flashes(); ?>
  <section class="grid grid-2">
    <form method="post" class="card">
      <?= csrf_field() ?>
      <div class="card-head"><div><h3>Pengaturan Umum</h3><p>Disimpan di tabel <code>settings</code></p></div><button class="btn btn-primary"><?= icon('check', 16) ?> Simpan</button></div>
      <div class="card-body form-grid">
        <?php foreach ($settings as $s): ?>
          <div class="field <?= in_array($s['setting_key'], ['hospital_name', 'hospital_address'], true) ? 'full' : '' ?>">
            <label><?= e($s['label']) ?></label>
            <input name="s[<?= e($s['setting_key']) ?>]" value="<?= e($s['setting_value']) ?>">
          </div>
        <?php endforeach; ?>
      </div>
    </form>
    <div class="card">
      <div class="card-head"><div><h3>Informasi Database</h3><p>Koneksi MySQL (phpMyAdmin)</p></div><span class="badge success">Terhubung</span></div>
      <ul class="list">
        <?php foreach ($dbInfo as [$k, $v]): ?><li><span class="l-dot"></span><div class="l-main"><b><?= e($k) ?></b><span><?= e($v) ?></span></div></li><?php endforeach; ?>
      </ul>
    </div>
  </section>
</div>
<?php layout_end();
