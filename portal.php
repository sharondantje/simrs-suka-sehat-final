<?php
/*
 * PORTAL SIMRS — satu shell untuk semua role.
 *  - Menu diambil dari role_menus() (RBAC, includes/bootstrap.php)
 *  - Halaman "page:xxx" = file PHP asli di pages/xxx.php (data MySQL)
 *  - Halaman lain       = modul anggota, dibuka di area kerja (iframe) dengan tema seragam
 */
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/ui.php';

$user = require_login();
if (!db_ok()) { $_SESSION = []; redirect('login.php'); }

$items = flat_menus($user['role']);
if (!$items) { $_SESSION = []; redirect('login.php'); }

$key = $_GET['page'] ?? 'dashboard';
if (!isset($items[$key])) $key = 'dashboard';      // menu role lain tidak bisa diakses
[$key, $label, $icon, $target, $action] = $items[$key];

// cari nama seksi untuk breadcrumb
$section = '';
foreach (role_menus($user['role']) as [$sec, $list]) foreach ($list as $it) if ($it[0] === $key) $section = $sec;

if (strpos($target, 'page:') === 0) {
    $file = __DIR__ . '/pages/' . basename(substr($target, 5)) . '.php';
    $PAGE = ['key' => $key, 'title' => $label, 'section' => $section];
    require $file;
    exit;
}

// ----- modul anggota di area kerja -----
layout_start($label, $key, $section);
?>
<div class="content frame">
  <iframe id="moduleFrame" class="module-frame" src="<?= e($target) ?>" title="<?= e($label) ?>"></iframe>
</div>
<script>
(function(){
  const frame = document.getElementById('moduleFrame');
  const action = <?= json_encode($action) ?>;
  frame.addEventListener('load', () => {
    try {
      const d = frame.contentDocument, w = frame.contentWindow;
      d.documentElement.classList.add('embedded-simrs-module');
      if (!d.getElementById('simrs-module-theme')) {
        const l = d.createElement('link');
        l.id = 'simrs-module-theme'; l.rel = 'stylesheet';
        l.href = new URL('assets/module-theme.css', location.href).href;
        d.head.appendChild(l);
      }
      if (action) setTimeout(() => { try { w.eval(action); } catch (e) { console.warn('Aksi modul gagal:', action, e); } }, 150);
    } catch (e) {}
  });
})();
</script>
<?php
layout_end(false);
