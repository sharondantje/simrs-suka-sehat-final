<?php
/*
 * DASHBOARD — SATU TEMPLATE UNTUK SEMUA ROLE
 * Struktur, font, warna, dan ukuran selalu sama:
 *   1. Banner sambutan  2. 4 kartu KPI  3. Grafik 7 hari + Akses cepat
 *   4. Tabel kerja utama + Panel informasi
 * Yang berbeda hanya DATA (diambil dari MySQL sesuai role).
 */
$u    = $user;
$role = $u['role'];

/* ---------- helper ---------- */
function last7(string $table, string $dateCol, string $where = '1=1', array $p = [], string $agg = 'COUNT(*)'): array
{
    $rows = q_all("SELECT DATE($dateCol) d, $agg v FROM $table WHERE $where AND $dateCol >= CURDATE() - INTERVAL 6 DAY GROUP BY DATE($dateCol)", $p);
    $map  = [];
    foreach ($rows as $r) $map[$r['d']] = (float)$r['v'];
    $hari = ['Min','Sen','Sel','Rab','Kam','Jum','Sab'];
    $out  = [];
    for ($i = 6; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i day"));
        $out[] = [$i === 0 ? 'Hari ini' : $hari[(int)date('w', strtotime($d))], $map[$d] ?? 0];
    }
    return $out;
}
function short_num($n): string
{
    if ($n >= 1e9) return angka($n / 1e9, 1) . ' M';
    if ($n >= 1e6) return angka($n / 1e6, 1) . ' jt';
    if ($n >= 1e3) return angka($n / 1e3, 0) . ' rb';
    return angka($n);
}
function kpi(string $label, $value, string $hint, string $icon, string $tone = ''): array { return compact('label', 'value', 'hint', 'icon', 'tone'); }
function patient_cell(array $r): string { return '<span class="strong">' . e($r['name']) . '</span><small class="mono">RM ' . e($r['mrn']) . '</small>'; }

$doctorId = (int)q_val('SELECT id FROM doctors WHERE user_id = ?', [$u['id']]);
$bedsTotal = (int)q_val('SELECT COUNT(*) FROM beds');
$bedsUsed  = (int)q_val("SELECT COUNT(*) FROM beds WHERE status='terisi'");
$bor       = $bedsTotal ? $bedsUsed / $bedsTotal * 100 : 0;

$D = ['kpis' => [], 'chart' => [], 'table' => [], 'info' => []];

switch ($role) {
case 'admin':
    $D['kpis'] = [
        kpi('Pengguna Aktif', q_val('SELECT COUNT(*) FROM users WHERE is_active=1'), 'dari ' . q_val('SELECT COUNT(*) FROM users') . ' akun terdaftar', 'users'),
        kpi('Role & Hak Akses', q_val('SELECT COUNT(*) FROM roles'), q_val('SELECT COUNT(*) FROM permissions') . ' permission', 'shield', 'info'),
        kpi('Login Hari Ini', q_val("SELECT COUNT(*) FROM audit_logs WHERE action='LOGIN' AND DATE(created_at)=CURDATE()"), q_val("SELECT COUNT(*) FROM audit_logs WHERE action='LOGIN_GAGAL' AND DATE(created_at)=CURDATE()") . ' percobaan gagal', 'key', 'warning'),
        kpi('Aktivitas Hari Ini', q_val('SELECT COUNT(*) FROM audit_logs WHERE DATE(created_at)=CURDATE()'), 'tercatat di audit log', 'activity', 'purple'),
    ];
    $D['chart'] = ['Aktivitas Sistem', 'Jumlah aktivitas audit log 7 hari terakhir', last7('audit_logs', 'created_at')];
    $D['table'] = ['Log Aktivitas Terbaru', 'Aktivitas pengguna di seluruh modul', 'audit', ['Waktu', 'Pengguna', 'Aksi', 'Modul', 'Keterangan'], array_map(fn($r) => [
        '<span class="mono">' . tgl($r['created_at'], true) . '</span>', '<span class="strong">' . e($r['username'] ?? '-') . '</span>', '<span class="badge info">' . e($r['action']) . '</span>', e($r['module']), e($r['description'])
    ], q_all('SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT 6'))];
    $D['info'] = ['Ringkasan Master Data', array_map(fn($r) => ['', $r[0], $r[1] . ' data', ''], [
        ['Dokter', q_val('SELECT COUNT(*) FROM doctors')], ['Poliklinik', q_val('SELECT COUNT(*) FROM poli')],
        ['Kamar / Tempat Tidur', q_val('SELECT COUNT(*) FROM rooms') . ' kamar · ' . $bedsTotal], ['Tarif Layanan', q_val('SELECT COUNT(*) FROM tariffs')],
        ['Kode ICD-10', q_val('SELECT COUNT(*) FROM icd10')], ['Obat', q_val('SELECT COUNT(*) FROM drugs')],
    ])];
    break;

case 'petugas_registrasi':
    $D['kpis'] = [
        kpi('Kunjungan Hari Ini', q_val('SELECT COUNT(*) FROM visits WHERE DATE(visit_date)=CURDATE()'), 'rawat jalan & IGD', 'users'),
        kpi('Pasien Baru', q_val('SELECT COUNT(*) FROM patients WHERE DATE(created_at)=CURDATE()'), 'terdaftar hari ini', 'user-plus', 'info'),
        kpi('Appointment Hari Ini', q_val('SELECT COUNT(*) FROM appointments WHERE appointment_date=CURDATE()'), q_val("SELECT COUNT(*) FROM appointments WHERE appointment_date>CURDATE() AND status='terjadwal'") . ' terjadwal ke depan', 'calendar', 'purple'),
        kpi('Menunggu Dilayani', q_val("SELECT COUNT(*) FROM visits WHERE DATE(visit_date)=CURDATE() AND status='menunggu'"), 'dalam antrean', 'clock', 'warning'),
    ];
    $D['chart'] = ['Kunjungan Pasien', 'Jumlah kunjungan 7 hari terakhir', last7('visits', 'visit_date')];
    $D['table'] = ['Antrean Pasien Hari Ini', 'Urut berdasarkan waktu registrasi', 'pasien', ['Antrean', 'Pasien', 'Poli / Unit', 'Penjamin', 'Status'], array_map(fn($r) => [
        '<span class="strong">' . e($r['queue_no']) . '</span><small>' . substr($r['visit_date'], 11, 5) . '</small>', patient_cell($r), e($r['poli']) . '<small>' . e($r['doctor']) . '</small>', e($r['insurer']), status_badge($r['status'])
    ], q_all("SELECT v.*, p.name, p.mrn, po.name poli, d.name doctor, i.name insurer FROM visits v JOIN patients p ON p.id=v.patient_id
              LEFT JOIN poli po ON po.id=v.poli_id LEFT JOIN doctors d ON d.id=v.doctor_id LEFT JOIN insurers i ON i.id=v.insurer_id
              WHERE DATE(v.visit_date)=CURDATE() ORDER BY v.visit_date LIMIT 7"))];
    $dow = (int)date('N');
    $D['info'] = ['Jadwal Dokter Hari Ini', array_map(fn($r) => ['', $r['name'], $r['poli'] . ' · kuota ' . $r['quota'], substr($r['start_time'], 0, 5) . '–' . substr($r['end_time'], 0, 5)],
        q_all('SELECT d.name, p.name poli, s.* FROM doctor_schedules s JOIN doctors d ON d.id=s.doctor_id JOIN poli p ON p.id=s.poli_id WHERE s.day_of_week=? ORDER BY s.start_time', [$dow]))];
    break;

case 'radiologist':
    $D['kpis'] = [
        kpi('Order Radiologi Aktif', q_val("SELECT COUNT(*) FROM radiology_results WHERE status<>'selesai'"), 'belum selesai', 'scan'),
        kpi('Menunggu Bacaan', q_val("SELECT COUNT(*) FROM radiology_results WHERE status='menunggu_bacaan'"), 'perlu ekspertise', 'file', 'warning'),
        kpi('Laporan Selesai', q_val("SELECT COUNT(*) FROM radiology_results WHERE status='selesai'"), 'total laporan', 'check', 'info'),
        kpi('Order CITO', q_val("SELECT COUNT(*) FROM radiology_results r JOIN orders o ON o.id=r.order_id WHERE o.priority='cito' AND r.status<>'selesai'"), 'prioritas tinggi', 'alert', 'danger'),
    ];
    $D['chart'] = ['Order Radiologi', 'Jumlah order radiologi 7 hari terakhir', last7('orders', 'ordered_at', "order_type='radiologi'")];
    $D['table'] = ['Daftar Order Radiologi', 'Order masuk dari dokter (CPOE)', 'order', ['No. Order', 'Pasien', 'Pemeriksaan', 'Prioritas', 'Status'], array_map(fn($r) => [
        '<span class="strong mono">' . e($r['order_no']) . '</span><small>' . tgl($r['ordered_at'], true) . '</small>', patient_cell($r), e($r['exam']) . '<small>' . e($r['modality']) . '</small>', status_badge($r['priority']), status_badge($r['status'])
    ], q_all('SELECT o.order_no, o.ordered_at, o.priority, r.status, p.name, p.mrn, x.name exam, x.modality FROM radiology_results r JOIN orders o ON o.id=r.order_id
              JOIN patients p ON p.id=o.patient_id JOIN radiology_exams x ON x.id=r.exam_id ORDER BY o.ordered_at DESC LIMIT 7'))];
    $D['info'] = ['Jadwal Pemeriksaan', array_map(fn($r) => [$r['status'] === 'menunggu_bacaan' ? 'warning' : 'info', $r['name'], $r['exam'], $r['scheduled_at'] ? substr($r['scheduled_at'], 11, 5) : '-'],
        q_all("SELECT p.name, x.name exam, r.scheduled_at, r.status FROM radiology_results r JOIN orders o ON o.id=r.order_id JOIN patients p ON p.id=o.patient_id JOIN radiology_exams x ON x.id=r.exam_id WHERE r.status<>'selesai' ORDER BY r.scheduled_at"))];
    break;

case 'dokter':
    $D['kpis'] = [
        kpi('Pasien Saya Hari Ini', q_val('SELECT COUNT(*) FROM visits WHERE doctor_id=? AND DATE(visit_date)=CURDATE()', [$doctorId]), 'rawat jalan', 'users'),
        kpi('Menunggu Diperiksa', q_val("SELECT COUNT(*) FROM visits WHERE doctor_id=? AND DATE(visit_date)=CURDATE() AND status='menunggu'", [$doctorId]), 'dalam antrean', 'clock', 'warning'),
        kpi('Pasien Rawat Inap', q_val("SELECT COUNT(*) FROM admissions WHERE doctor_id=? AND status='dirawat'", [$doctorId]), 'dalam perawatan saya', 'bed', 'info'),
        kpi('Order Aktif (CPOE)', q_val("SELECT COUNT(*) FROM orders WHERE doctor_id=? AND status IN ('baru','diproses')", [$doctorId]), 'lab, radiologi, obat', 'order', 'purple'),
    ];
    $D['chart'] = ['Kunjungan Pasien Saya', 'Jumlah pasien 7 hari terakhir', last7('visits', 'visit_date', 'doctor_id=?', [$doctorId])];
    $D['table'] = ['Antrean Pasien Hari Ini', 'Poli ' . ($u['unit'] ?? ''), 'rawatjalan', ['Antrean', 'Pasien', 'Keluhan', 'Penjamin', 'Status'], array_map(fn($r) => [
        '<span class="strong">' . e($r['queue_no']) . '</span><small>' . substr($r['visit_date'], 11, 5) . '</small>', patient_cell($r), e($r['complaint']), e($r['insurer']), status_badge($r['status'])
    ], q_all('SELECT v.*, p.name, p.mrn, i.name insurer FROM visits v JOIN patients p ON p.id=v.patient_id LEFT JOIN insurers i ON i.id=v.insurer_id
              WHERE v.doctor_id=? AND DATE(v.visit_date)=CURDATE() ORDER BY v.visit_date', [$doctorId]))];
    $D['info'] = ['Pasien Rawat Inap Saya', array_map(fn($r) => ['info', $r['name'], $r['room'] . ' · ' . $r['diagnosis'], 'hari ke-' . ($r['days'] + 1)],
        q_all("SELECT p.name, a.diagnosis, CONCAT(rm.name,' / Bed ',b.bed_number) room, DATEDIFF(NOW(),a.admit_date) days FROM admissions a JOIN patients p ON p.id=a.patient_id
               LEFT JOIN beds b ON b.id=a.bed_id LEFT JOIN rooms rm ON rm.id=b.room_id WHERE a.doctor_id=? AND a.status='dirawat'", [$doctorId]))];
    break;

case 'perawat':
    $D['kpis'] = [
        kpi('Pasien Dirawat', q_val("SELECT COUNT(*) FROM admissions WHERE status='dirawat'"), 'rawat inap aktif', 'bed'),
        kpi('Obat Terjadwal Hari Ini', q_val("SELECT COUNT(*) FROM medication_administrations WHERE DATE(scheduled_at)=CURDATE() AND status='terjadwal'"), q_val("SELECT COUNT(*) FROM medication_administrations WHERE DATE(scheduled_at)=CURDATE() AND status='diberikan'") . ' sudah diberikan', 'pill', 'warning'),
        kpi('Pasien IGD Hari Ini', q_val("SELECT COUNT(*) FROM visits WHERE visit_type='igd' AND DATE(visit_date)=CURDATE()"), 'sudah ditriase', 'siren', 'danger'),
        kpi('Catatan Keperawatan', q_val('SELECT COUNT(*) FROM nursing_notes WHERE DATE(created_at)=CURDATE()'), 'dibuat hari ini', 'heart', 'info'),
    ];
    $D['chart'] = ['Tanda Vital Tercatat', 'Jumlah pemeriksaan tanda vital 7 hari terakhir', last7('vital_signs', 'recorded_at')];
    $D['table'] = ['Pasien Rawat Inap', 'Daftar pasien yang sedang dirawat', 'rawatinap', ['Pasien', 'Kamar / Bed', 'Diagnosis', 'DPJP', 'Lama Rawat'], array_map(fn($r) => [
        patient_cell($r), e($r['room']) . '<small>Bed ' . e($r['bed_number']) . '</small>', e($r['diagnosis']), e($r['doctor']), '<span class="badge info">' . ($r['days'] + 1) . ' hari</span>'
    ], q_all("SELECT p.name, p.mrn, a.diagnosis, rm.name room, b.bed_number, d.name doctor, DATEDIFF(NOW(),a.admit_date) days FROM admissions a JOIN patients p ON p.id=a.patient_id
              LEFT JOIN beds b ON b.id=a.bed_id LEFT JOIN rooms rm ON rm.id=b.room_id LEFT JOIN doctors d ON d.id=a.doctor_id WHERE a.status='dirawat' ORDER BY a.admit_date"))];
    $D['info'] = ['Jadwal Pemberian Obat Hari Ini', array_map(fn($r) => [$r['status'] === 'diberikan' ? '' : 'warning', $r['name'], $r['drug'] . ' · ' . $r['dose'] . ' (' . str_replace('_', ' ', $r['status']) . ')', substr($r['scheduled_at'], 11, 5)],
        q_all('SELECT p.name, dr.name drug, m.dose, m.status, m.scheduled_at FROM medication_administrations m JOIN admissions a ON a.id=m.admission_id JOIN patients p ON p.id=a.patient_id
               JOIN drugs dr ON dr.id=m.drug_id WHERE DATE(m.scheduled_at)=CURDATE() ORDER BY m.scheduled_at'))];
    break;

case 'kasir':
    $D['kpis'] = [
        kpi('Penerimaan Hari Ini', short_num(q_val('SELECT COALESCE(SUM(amount),0) FROM payments WHERE DATE(paid_at)=CURDATE()')), q_val('SELECT COUNT(*) FROM payments WHERE DATE(paid_at)=CURDATE()') . ' transaksi', 'money'),
        kpi('Tagihan Belum Lunas', q_val("SELECT COUNT(*) FROM invoices WHERE status IN ('belum_bayar','cicilan')"), rupiah(q_val("SELECT COALESCE(SUM(patient_pay),0) FROM invoices WHERE status IN ('belum_bayar','cicilan')")), 'receipt', 'warning'),
        kpi('Invoice Hari Ini', q_val('SELECT COUNT(*) FROM invoices WHERE DATE(created_at)=CURDATE()'), 'dibuat hari ini', 'file', 'info'),
        kpi('Klaim Penjamin Proses', q_val("SELECT COUNT(*) FROM insurance_claims WHERE status IN ('diajukan','diverifikasi')"), 'BPJS & asuransi', 'shield', 'purple'),
    ];
    $D['chart'] = ['Penerimaan Kas', 'Total pembayaran pasien 7 hari terakhir (Rp)', last7('payments', 'paid_at', '1=1', [], 'SUM(amount)')];
    $D['table'] = ['Tagihan Terbaru', 'Invoice pasien rawat jalan, IGD dan rawat inap', 'billing', ['No. Invoice', 'Pasien', 'Penjamin', 'Total', 'Status'], array_map(fn($r) => [
        '<span class="strong mono">' . e($r['invoice_no']) . '</span><small>' . tgl($r['created_at'], true) . '</small>', patient_cell($r), e($r['insurer']), rupiah($r['total']), status_badge($r['status'])
    ], q_all('SELECT iv.*, p.name, p.mrn, i.name insurer FROM invoices iv JOIN patients p ON p.id=iv.patient_id LEFT JOIN insurers i ON i.id=iv.insurer_id ORDER BY iv.created_at DESC LIMIT 7'))];
    $D['info'] = ['Status Klaim Penjamin', array_map(fn($r) => [in_array($r['status'], ['ditolak']) ? 'danger' : (in_array($r['status'], ['dibayar','disetujui']) ? '' : 'warning'), $r['claim_no'], $r['insurer'] . ' · ' . rupiah($r['amount']), ucfirst($r['status'])],
        q_all('SELECT c.claim_no, c.amount, c.status, i.name insurer FROM insurance_claims c JOIN insurers i ON i.id=c.insurer_id ORDER BY c.submitted_at DESC LIMIT 6'))];
    break;

case 'farmasi':
    $D['kpis'] = [
        kpi('Resep Baru', q_val("SELECT COUNT(*) FROM prescriptions WHERE status='baru'"), 'perlu verifikasi', 'file', 'warning'),
        kpi('Dalam Proses', q_val("SELECT COUNT(*) FROM prescriptions WHERE status IN ('diverifikasi','disiapkan')"), 'verifikasi & penyiapan', 'pill', 'info'),
        kpi('Diserahkan Hari Ini', q_val("SELECT COUNT(*) FROM prescriptions WHERE status='diserahkan' AND DATE(created_at)=CURDATE()"), 'resep selesai', 'check'),
        kpi('Stok Menipis', q_val('SELECT COUNT(*) FROM drugs WHERE stock < min_stock'), 'di bawah stok minimum', 'alert', 'danger'),
    ];
    $D['chart'] = ['Resep Masuk', 'Jumlah resep 7 hari terakhir', last7('prescriptions', 'created_at')];
    $D['table'] = ['Daftar Resep Terbaru', 'Resep elektronik dari dokter', 'resep', ['No. Resep', 'Pasien', 'Dokter', 'Item', 'Status'], array_map(fn($r) => [
        '<span class="strong mono">' . e($r['prescription_no']) . '</span><small>' . tgl($r['created_at'], true) . '</small>', patient_cell($r), e($r['doctor']), $r['items'] . ' obat', status_badge($r['status'])
    ], q_all('SELECT pr.*, p.name, p.mrn, d.name doctor, (SELECT COUNT(*) FROM prescription_items x WHERE x.prescription_id=pr.id) items FROM prescriptions pr
              JOIN patients p ON p.id=pr.patient_id LEFT JOIN doctors d ON d.id=pr.doctor_id ORDER BY pr.created_at DESC LIMIT 7'))];
    $D['info'] = ['Peringatan Stok & Kedaluwarsa', array_map(fn($r) => [$r['stock'] < $r['min_stock'] ? 'danger' : 'warning', $r['name'],
        $r['stock'] < $r['min_stock'] ? 'Stok ' . $r['stock'] . ' ' . $r['unit'] . ' (min ' . $r['min_stock'] . ')' : 'Kedaluwarsa ' . tgl($r['expiry_date']), $r['stock'] < $r['min_stock'] ? 'Menipis' : 'ED'],
        q_all('SELECT * FROM drugs WHERE stock < min_stock OR expiry_date <= CURDATE() + INTERVAL 60 DAY ORDER BY stock/min_stock'))];
    break;

case 'laboratorium':
    $D['kpis'] = [
        kpi('Order Lab Hari Ini', q_val("SELECT COUNT(*) FROM orders WHERE order_type='laboratorium' AND DATE(ordered_at)=CURDATE()"), 'dari CPOE dokter', 'flask'),
        kpi('Menunggu Sampel', q_val("SELECT COUNT(*) FROM lab_results WHERE status='menunggu_sampel'"), 'spesimen belum diterima', 'test', 'warning'),
        kpi('Menunggu Validasi', q_val("SELECT COUNT(*) FROM lab_results WHERE status='menunggu_validasi'"), 'hasil sudah diinput', 'edit', 'info'),
        kpi('Tervalidasi', q_val("SELECT COUNT(*) FROM lab_results WHERE status='tervalidasi'"), 'siap dilihat dokter', 'check', 'purple'),
    ];
    $D['chart'] = ['Order Laboratorium', 'Jumlah order lab 7 hari terakhir', last7('orders', 'ordered_at', "order_type='laboratorium'")];
    $D['table'] = ['Pemeriksaan Laboratorium', 'Hasil per jenis tes', 'orderlab', ['No. Order', 'Pasien', 'Pemeriksaan', 'Hasil', 'Status'], array_map(fn($r) => [
        '<span class="strong mono">' . e($r['order_no']) . '</span><small>' . ($r['priority'] === 'cito' ? 'CITO' : 'Normal') . '</small>', patient_cell($r), e($r['test']),
        $r['result_value'] ? e($r['result_value']) . ' ' . e($r['unit']) . ($r['flag'] && $r['flag'] !== 'normal' ? ' ' . status_badge($r['flag']) : '') : '<span class="muted">-</span>', status_badge($r['status'])
    ], q_all('SELECT o.order_no, o.priority, p.name, p.mrn, t.name test, t.unit, l.result_value, l.flag, l.status FROM lab_results l JOIN orders o ON o.id=l.order_id
              JOIN patients p ON p.id=o.patient_id JOIN lab_tests t ON t.id=l.lab_test_id ORDER BY o.ordered_at DESC LIMIT 7'))];
    $D['info'] = ['Perlu Perhatian', array_map(fn($r) => [$r['priority'] === 'cito' ? 'danger' : 'warning', $r['name'], $r['test'] . ($r['flag'] ? ' · hasil ' . $r['flag'] : ''), $r['priority'] === 'cito' ? 'CITO' : 'Abnormal'],
        q_all("SELECT p.name, t.name test, l.flag, o.priority FROM lab_results l JOIN orders o ON o.id=l.order_id JOIN patients p ON p.id=o.patient_id JOIN lab_tests t ON t.id=l.lab_test_id
               WHERE (o.priority='cito' AND l.status<>'tervalidasi') OR l.flag IN ('tinggi','rendah','kritis') ORDER BY o.priority='cito' DESC"))];
    break;

case 'gudang':
case 'kepala_gudang':
    $D['kpis'] = [
        kpi('Total Item Barang', q_val('SELECT COUNT(*) FROM inventory_items'), 'medis & non-medis', 'box'),
        kpi('Total Stok', angka(q_val('SELECT SUM(stock) FROM inventory_items')), 'unit di semua lokasi', 'database', 'info'),
        kpi('Di Bawah Minimum', q_val('SELECT COUNT(*) FROM inventory_items WHERE stock < min_stock'), 'perlu pengadaan', 'alert', 'danger'),
        $role === 'kepala_gudang'
            ? kpi('Menunggu Approval', q_val("SELECT COUNT(*) FROM stock_opnames WHERE status='menunggu_approval'") + q_val("SELECT COUNT(*) FROM purchase_requests WHERE status='diajukan'"), 'stok opname & PR', 'check', 'warning')
            : kpi('Mendekati Kedaluwarsa', q_val('SELECT COUNT(*) FROM inventory_items WHERE expiry_date <= CURDATE() + INTERVAL 60 DAY'), '≤ 60 hari', 'clock', 'warning'),
    ];
    $D['chart'] = ['Mutasi Stok', 'Jumlah transaksi barang masuk/keluar/mutasi 7 hari terakhir', last7('stock_movements', 'created_at')];
    $D['table'] = ['Stok Barang', 'Diurutkan dari stok paling kritis', 'inventory', ['Kode', 'Nama Barang', 'Lokasi', 'Stok', 'Status'], array_map(fn($r) => [
        '<span class="strong mono">' . e($r['code']) . '</span>', e($r['name']) . '<small>' . e(ucfirst($r['category'])) . '</small>', e($r['location']), angka($r['stock']) . ' ' . e($r['unit']) . '<small>min ' . $r['min_stock'] . '</small>',
        $r['stock'] < $r['min_stock'] ? '<span class="badge danger">Menipis</span>' : '<span class="badge success">Aman</span>'
    ], q_all('SELECT * FROM inventory_items ORDER BY stock/min_stock LIMIT 7'))];
    $D['info'] = ['Pengadaan & Stok Opname', array_merge(
        array_map(fn($r) => [$r['status'] === 'diajukan' ? 'warning' : '', $r['pr_no'], 'Purchase request · ' . $r['notes'], ucfirst($r['status'])], q_all('SELECT * FROM purchase_requests ORDER BY created_at DESC')),
        array_map(fn($r) => [$r['status'] === 'menunggu_approval' ? 'warning' : '', $r['opname_no'], 'Stok opname · ' . $r['location'], ucwords(str_replace('_', ' ', $r['status']))], q_all('SELECT * FROM stock_opnames ORDER BY created_at DESC'))
    )];
    break;

case 'executive':
case 'analyst':
default:
    $alos = q_val("SELECT AVG(DATEDIFF(COALESCE(discharge_date,NOW()),admit_date)) FROM admissions");
    $D['kpis'] = $role === 'executive' ? [
        kpi('BOR', angka($bor, 1) . '%', $bedsUsed . ' dari ' . $bedsTotal . ' tempat tidur terisi', 'bed'),
        kpi('ALOS', angka($alos, 1) . ' hari', 'rata-rata lama rawat', 'clock', 'info'),
        kpi('Kunjungan Bulan Ini', q_val("SELECT COUNT(*) FROM visits WHERE DATE_FORMAT(visit_date,'%Y-%m')=DATE_FORMAT(CURDATE(),'%Y-%m')"), 'RJ, RI dan IGD', 'users', 'purple'),
        kpi('Pendapatan Bulan Ini', short_num(q_val("SELECT COALESCE(SUM(total),0) FROM invoices WHERE DATE_FORMAT(created_at,'%Y-%m')=DATE_FORMAT(CURDATE(),'%Y-%m')")), 'total tagihan', 'money', 'warning'),
    ] : [
        kpi('Total Pasien', q_val('SELECT COUNT(*) FROM patients'), 'terdaftar di rekam medis', 'id-card'),
        kpi('Kunjungan Bulan Ini', q_val("SELECT COUNT(*) FROM visits WHERE DATE_FORMAT(visit_date,'%Y-%m')=DATE_FORMAT(CURDATE(),'%Y-%m')"), 'RJ, RI dan IGD', 'users', 'info'),
        kpi('Rekam Medis (SOAP)', q_val('SELECT COUNT(*) FROM medical_records'), q_val('SELECT COUNT(*) FROM diagnoses') . ' diagnosis ICD-10', 'clipboard', 'purple'),
        kpi('BOR', angka($bor, 1) . '%', 'ALOS ' . angka($alos, 1) . ' hari', 'bed', 'warning'),
    ];
    $D['chart'] = ['Kunjungan Pasien', 'Jumlah kunjungan seluruh layanan 7 hari terakhir', last7('visits', 'visit_date')];
    $D['table'] = ['10 Besar Penyakit', 'Berdasarkan diagnosis ICD-10', 'laporan', ['Kode', 'Diagnosis', 'Kategori', 'Kasus'], array_map(fn($r) => [
        '<span class="strong mono">' . e($r['code']) . '</span>', e($r['description']), e($r['category']), '<span class="badge info">' . $r['n'] . ' kasus</span>'
    ], q_all('SELECT i.code, i.description, i.category, COUNT(*) n FROM diagnoses d JOIN icd10 i ON i.code=d.icd10_code GROUP BY i.code, i.description, i.category ORDER BY n DESC LIMIT 10'))];
    $D['info'] = ['Kunjungan per Penjamin', array_map(fn($r) => ['', $r['name'], $r['n'] . ' kunjungan', ''],
        q_all('SELECT i.name, COUNT(*) n FROM visits v JOIN insurers i ON i.id=v.insurer_id GROUP BY i.name ORDER BY n DESC'))];
    break;
}

/* ---------- akses cepat dari menu role ---------- */
$quick = array_values(array_filter(flat_menus($role), fn($m) => !in_array($m[0], ['dashboard', 'profil'], true)));
$quick = array_slice($quick, 0, 6);

$jam   = (int)date('G');
$salam = $jam < 11 ? 'Selamat pagi' : ($jam < 15 ? 'Selamat siang' : ($jam < 19 ? 'Selamat sore' : 'Selamat malam'));
$max   = max(1, max(array_map(fn($x) => $x[1], $D['chart'][2])));
$isMoney = $role === 'kasir';

layout_start('Dashboard', 'dashboard', 'Utama');
?>
<div class="content">
  <?php flashes(); ?>

  <section class="welcome">
    <div>
      <h2><?= e($salam) ?>, <?= e($u['name']) ?>!</h2>
      <p>Selamat datang di Dashboard SIMRS RS Suka Sehat. Berikut ringkasan pekerjaan Anda hari ini.</p>
      <div class="chips">
        <span class="chip"><?= e($u['role_label']) ?></span>
        <?php if ($u['unit']): ?><span class="chip"><?= e($u['unit']) ?></span><?php endif; ?>
        <span class="chip"><?= e(tgl_panjang()) ?></span>
        <?php if ($u['last_login']): ?><span class="chip">Login terakhir: <?= e(tgl($u['last_login'], true)) ?></span><?php endif; ?>
      </div>
    </div>
    <div class="w-icon"><?= icon('hospital', 34) ?></div>
  </section>

  <section class="kpi-grid">
    <?php foreach ($D['kpis'] as $k): ?>
      <div class="card kpi">
        <div class="kpi-icon <?= e($k['tone']) ?>"><?= icon($k['icon'], 22) ?></div>
        <div>
          <div class="kpi-label"><?= e($k['label']) ?></div>
          <div class="kpi-value"><?= e($k['value']) ?></div>
          <div class="kpi-hint"><?= e($k['hint']) ?></div>
        </div>
      </div>
    <?php endforeach; ?>
  </section>

  <section class="grid grid-2 mb">
    <div class="card">
      <div class="card-head"><div><h3><?= e($D['chart'][0]) ?></h3><p><?= e($D['chart'][1]) ?></p></div><span class="badge">7 hari</span></div>
      <div class="chart">
        <?php foreach ($D['chart'][2] as $i => [$lbl, $val]): ?>
          <div class="bar-col">
            <div class="bar <?= $i === 6 ? 'today' : '' ?>" style="height:<?= max(2, round($val / $max * 100)) ?>%"><span><?= $isMoney ? e(short_num($val)) : e(angka($val)) ?></span></div>
            <div class="bar-label"><?= e($lbl) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="legend"><span><i></i>6 hari sebelumnya</span><span><i class="pink"></i>Hari ini</span></div>
    </div>

    <div class="card">
      <div class="card-head"><div><h3>Akses Cepat</h3><p>Menu kerja sesuai hak akses Anda</p></div></div>
      <div class="quick-grid">
        <?php foreach ($quick as $m): ?>
          <a class="quick" href="portal.php?page=<?= urlencode($m[0]) ?>"><span class="q-icon"><?= icon($m[2]) ?></span><?= e($m[1]) ?></a>
        <?php endforeach; ?>
      </div>
      <div class="card-body" style="padding-top:0">
        <div class="stat-row"><span>Keterisian tempat tidur (BOR)</span><b><?= angka($bor, 1) ?>%</b></div>
        <div class="progress"><div style="width:<?= min(100, round($bor)) ?>%"></div></div>
        <div class="kpi-hint" style="margin-top:6px"><?= $bedsUsed ?> terisi · <?= $bedsTotal - $bedsUsed ?> tersedia/dalam perawatan</div>
      </div>
    </div>
  </section>

  <section class="grid grid-2">
    <div class="card">
      <?php [$tTitle, $tSub, $tLink, $tCols, $tRows] = $D['table']; ?>
      <div class="card-head">
        <div><h3><?= e($tTitle) ?></h3><p><?= e($tSub) ?></p></div>
        <?php if (isset(flat_menus($role)[$tLink])): ?><a class="link" href="portal.php?page=<?= urlencode($tLink) ?>">Lihat semua →</a><?php endif; ?>
      </div>
      <div class="table-wrap">
        <table class="table">
          <thead><tr><?php foreach ($tCols as $c): ?><th><?= e($c) ?></th><?php endforeach; ?></tr></thead>
          <tbody>
            <?php if (!$tRows): ?><tr><td colspan="<?= count($tCols) ?>" class="empty">Belum ada data.</td></tr><?php endif; ?>
            <?php foreach ($tRows as $row): ?><tr><?php foreach ($row as $cell): ?><td><?= $cell ?></td><?php endforeach; ?></tr><?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card">
      <div class="card-head"><div><h3><?= e($D['info'][0]) ?></h3><p>Diperbarui otomatis dari database</p></div></div>
      <ul class="list">
        <?php if (!$D['info'][1]): ?><li><div class="l-main"><span>Tidak ada data untuk ditampilkan.</span></div></li><?php endif; ?>
        <?php foreach ($D['info'][1] as [$tone, $title, $sub, $time]): ?>
          <li><span class="l-dot <?= e($tone) ?>"></span><div class="l-main"><b><?= e($title) ?></b><span><?= e($sub) ?></span></div><?php if ($time !== ''): ?><time><?= e($time) ?></time><?php endif; ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </section>
</div>
<?php layout_end();
