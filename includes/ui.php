<?php
/*
 * Komponen UI bersama untuk halaman modul (lab, farmasi, EMR, CPOE, billing, gudang, dst).
 * Semua fungsi memakai prefix ui_ agar tidak bentrok dengan fungsi di halaman lain.
 */

/** URL ke halaman portal: ui_url('resep', ['id' => 5]) */
function ui_url(string $key, array $params = []): string
{
    return 'portal.php?' . http_build_query(['page' => $key] + $params);
}

/** Stepper alur multi-langkah. $current = index langkah aktif (0-based); langkah sebelumnya = selesai. */
function ui_stepper(array $steps, int $current): string
{
    $h = '<div class="stepper">';
    foreach (array_values($steps) as $i => $label) {
        $cls = $i < $current ? 'done' : ($i === $current ? 'active' : '');
        $num = $i < $current ? '✓' : (string)($i + 1);
        $h .= '<div class="step ' . $cls . '"><i>' . $num . '</i><span>' . e($label) . '</span></div>';
    }
    return $h . '</div>';
}

/** Kartu KPI kecil. $tone: '' (primary) | info | warning | purple | danger */
function ui_kpi(string $label, $value, string $icon, string $tone = '', string $hint = ''): string
{
    return '<div class="card kpi"><div class="kpi-icon ' . e($tone) . '">' . icon($icon, 22) . '</div><div><div class="kpi-label">' . e($label)
        . '</div><div class="kpi-value">' . $value . '</div>' . ($hint !== '' ? '<div class="kpi-hint">' . e($hint) . '</div>' : '') . '</div></div>';
}

/** Umur dari tanggal lahir. */
function ui_age(?string $birth): string
{
    if (!$birth) return '-';
    $d = (new DateTime($birth))->diff(new DateTime());
    return $d->y > 0 ? $d->y . ' th' : $d->m . ' bln';
}

/** Banner identitas pasien (dipakai EMR, CPOE, nursing, rawat jalan/inap, IGD, billing). */
function ui_patient(array $p, string $extra = ''): string
{
    $alergi = trim((string)($p['allergies'] ?? ''));
    $h  = '<div class="pt-banner"><div class="avatar">' . e(initials($p['name'])) . '</div><div class="pt-main">';
    $h .= '<b>' . e($p['name']) . '</b><div class="pt-meta"><span class="mono">' . e($p['mrn']) . '</span>';
    $h .= '<span>' . ($p['gender'] === 'L' ? 'Laki-laki' : 'Perempuan') . ' · ' . ui_age($p['birth_date'] ?? null) . '</span>';
    if (!empty($p['birth_date'])) $h .= '<span>Lahir ' . tgl($p['birth_date']) . '</span>';
    if (!empty($p['blood_type'])) $h .= '<span>Gol. darah ' . e($p['blood_type']) . '</span>';
    if (!empty($p['insurer_name'])) $h .= '<span>' . e($p['insurer_name']) . '</span>';
    $h .= '</div></div>';
    $h .= $alergi !== '' && strcasecmp($alergi, 'tidak ada') !== 0 && $alergi !== '-'
        ? '<span class="badge danger">' . icon('alert', 13) . ' Alergi: ' . e($alergi) . '</span>'
        : '<span class="badge success">Tidak ada alergi</span>';
    return $h . $extra . '</div>';
}

/** Data pasien lengkap + nama penjamin. */
function ui_patient_row(int $id): ?array
{
    return q_one('SELECT p.*, i.name insurer_name FROM patients p LEFT JOIN insurers i ON i.id = p.insurer_id WHERE p.id = ?', [$id]);
}

/** <option> dari array baris. */
function ui_options(array $rows, string $valKey, $labelKey, $selected = null, string $placeholder = ''): string
{
    $h = $placeholder !== '' ? '<option value="">' . e($placeholder) . '</option>' : '';
    foreach ($rows as $r) {
        $v = $r[$valKey];
        $l = $labelKey instanceof Closure ? $labelKey($r) : $r[$labelKey];
        $h .= '<option value="' . e($v) . '"' . ((string)$v === (string)$selected ? ' selected' : '') . '>' . e($l) . '</option>';
    }
    return $h;
}

/** <option> dari peta value => label. */
function ui_opt_map(array $map, $selected = null): string
{
    $h = '';
    foreach ($map as $v => $l) $h .= '<option value="' . e($v) . '"' . ((string)$v === (string)$selected ? ' selected' : '') . '>' . e($l) . '</option>';
    return $h;
}

/** Nomor dokumen berikutnya: ui_next_no('prescriptions','prescription_no','RSP-', 4) -> RSP-0006 */
function ui_next_no(string $table, string $col, string $prefix, int $pad = 4): string
{
    $max = (int)q_val("SELECT COALESCE(MAX(CAST(SUBSTRING(`$col`, ?) AS UNSIGNED)),0) FROM `$table` WHERE `$col` LIKE ?",
                      [strlen($prefix) + 1, $prefix . '%']);
    return $prefix . str_pad((string)($max + 1), $pad, '0', STR_PAD_LEFT);
}

/** Tab navigasi antar sub-halaman. $tabs = [key => label] */
function ui_tabs(array $tabs, string $current, callable $href): string
{
    $h = '<nav class="tabs">';
    foreach ($tabs as $k => $l) $h .= '<a class="tab ' . ($k === $current ? 'active' : '') . '" href="' . e($href($k)) . '">' . $l . '</a>';
    return $h . '</nav>';
}

/** Pasangan label–nilai (detail). $pairs = [label => html] */
function ui_details(array $pairs): string
{
    $h = '<dl class="details">';
    foreach ($pairs as $k => $v) $h .= '<div><dt>' . e($k) . '</dt><dd>' . ($v === '' || $v === null ? '-' : $v) . '</dd></div>';
    return $h . '</dl>';
}

/** Awal POST handler: validasi CSRF; kembalikan aksi. */
function ui_post_action(): ?string
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return null;
    csrf_check();
    return (string)($_POST['act'] ?? '');
}

/** Jalankan beberapa query dalam transaksi. */
function ui_tx(callable $fn)
{
    $pdo = db();
    $pdo->beginTransaction();
    try { $r = $fn(); $pdo->commit(); return $r; }
    catch (Throwable $e) { $pdo->rollBack(); throw $e; }
}

/** Teks pendek label status (tanpa badge). */
function ui_label(string $s): string { return ucwords(str_replace('_', ' ', $s)); }

/** Masking data sensitif (NIK / no. HP) untuk laporan analis. */
function ui_mask(?string $s, int $keep = 4): string
{
    $s = (string)$s;
    if (strlen($s) <= $keep) return $s;
    return str_repeat('•', strlen($s) - $keep) . substr($s, -$keep);
}

/** Buat kunjungan + nomor antrean (format sama dengan registrasi Claudia). Mengembalikan id kunjungan. */
function ui_create_visit(int $patientId, string $type, ?int $poliId, ?int $doctorId, ?int $insurerId, string $complaint = '', string $status = 'menunggu'): int
{
    $code   = $poliId ? (string)q_val('SELECT code FROM poli WHERE id=?', [$poliId]) : ($type === 'igd' ? 'IGD' : 'UMM');
    $n      = (int)q_val('SELECT COUNT(*) FROM visits WHERE poli_id <=> ? AND DATE(visit_date)=CURDATE()', [$poliId]) + 1;
    $queue  = strtoupper(substr($code, 0, 1)) . '-' . str_pad((string)$n, 3, '0', STR_PAD_LEFT);
    $prefix = $type === 'rawat_inap' ? 'RI' : ($type === 'igd' ? 'IGD' : 'RJ');
    $seq    = (int)q_val('SELECT COALESCE(MAX(id),0)+1 FROM visits');
    q('INSERT INTO visits (visit_no, patient_id, visit_type, poli_id, doctor_id, insurer_id, queue_no, visit_date, complaint, status, created_by) VALUES (?,?,?,?,?,?,?,NOW(),?,?,?)',
      [$prefix . '-' . date('Ymd') . '-' . str_pad((string)$seq, 4, '0', STR_PAD_LEFT), $patientId, $type, $poliId, $doctorId,
       $insurerId ?: (int)(q_val('SELECT insurer_id FROM patients WHERE id=?', [$patientId]) ?: 1), $queue, $complaint ?: null, $status, $_SESSION['user']['id'] ?? null]);
    return (int)db()->lastInsertId();
}

const UI_DAYS = [1 => 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

/** Grafik batang sederhana (CSS). $points = [label => nilai]; batang terakhir disorot. */
function ui_bars(array $points, ?callable $fmt = null): string
{
    $max = max(1, ...array_map('floatval', array_values($points) ?: [1]));
    $h = '<div class="chart">'; $n = count($points); $i = 0;
    foreach ($points as $label => $v) {
        $i++;
        $txt = $fmt ? $fmt($v) : angka($v);
        $h .= '<div class="bar-col"><div class="bar' . ($i === $n ? ' today' : '') . '" style="height:' . max(2, round($v / $max * 100)) . '%" title="' . e($label . ': ' . $txt) . '">'
            . ($n <= 12 ? '<span>' . e($txt) . '</span>' : '') . '</div><div class="bar-label">' . e($label) . '</div></div>';
    }
    return $h . '</div>';
}

/** Deret harian [tgl => nilai] untuk rentang tanggal. */
function ui_daily(string $sql, string $from, string $to, array $p = []): array
{
    $rows = array_column(q_all($sql, array_merge($p, [$from, $to])), 'v', 'd');
    $out = [];
    for ($d = $from; $d <= $to; $d = date('Y-m-d', strtotime("$d +1 day"))) $out[date('d/m', strtotime($d))] = (float)($rows[$d] ?? 0);
    return $out;
}
