<?php
/*
 * JEMBATAN DATABASE untuk halaman Claudia (registrasi & data pasien).
 * Memakai koneksi yang sama dengan portal: config/database.php (MySQL / phpMyAdmin).
 * Semua fungsi diberi awalan simrs_ agar tidak bentrok dengan fungsi di halaman asli.
 */
require_once __DIR__ . '/../../config/database.php';

function simrs_db_ok(): bool
{
    try { db(); return true; } catch (Throwable $e) { return false; }
}

function simrs_q(string $sql, array $p = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute($p);
    return $st;
}

function simrs_audit(string $action, string $module, string $desc): void
{
    try {
        $u = $_SESSION['user'] ?? [];
        simrs_q('INSERT INTO audit_logs (user_id, username, action, module, description, ip_address) VALUES (?,?,?,?,?,?)',
            [$u['id'] ?? null, $u['username'] ?? null, $action, $module, $desc, $_SERVER['REMOTE_ADDR'] ?? null]);
    } catch (Throwable $e) {}
}

/** Semua pasien dalam format array yang dipakai halaman Claudia. */
function simrs_patients(): array
{
    $rows = simrs_q("SELECT mrn, nik, name, DATE_FORMAT(birth_date,'%d-%m-%Y') birth_date, gender, address FROM patients ORDER BY id DESC")->fetchAll();
    return array_map(fn($r) => [
        'medical_record_no' => $r['mrn'],
        'name'              => $r['name'],
        'nik'               => (string)$r['nik'],
        'birth_date'        => $r['birth_date'],
        'gender'            => $r['gender'] === 'L' ? 'Laki-laki' : 'Perempuan',
        'address'           => (string)$r['address'],
    ], $rows);
}

function simrs_patient_by_mrn(string $mrn): ?array
{
    $r = simrs_q('SELECT * FROM patients WHERE mrn = ?', [$mrn])->fetch();
    return $r ?: null;
}

/** Cari id poli dari nama "Poliklinik X" / "Poli X". */
function simrs_poli_id(string $label): ?int
{
    $name = trim(preg_replace('/^Poli(klinik)?\s*/i', '', $label));
    if ($name === '') return null;
    $id = simrs_q('SELECT id FROM poli WHERE name LIKE ? ORDER BY id LIMIT 1', ['%' . $name . '%'])->fetchColumn();
    return $id ? (int)$id : null;
}

function simrs_insurer_id(string $label): int
{
    $l = strtolower($label);
    $type = strpos($l, 'bpjs') !== false ? 'bpjs' : (strpos($l, 'asuransi') !== false ? 'asuransi' : 'umum');
    return (int)(simrs_q('SELECT id FROM insurers WHERE type = ? ORDER BY id LIMIT 1', [$type])->fetchColumn() ?: 1);
}

/** Buat kunjungan + nomor antrean. Mengembalikan [visit_no, queue_no]. */
function simrs_create_visit(int $patientId, ?int $poliId, int $insurerId, string $type = 'rawat_jalan', string $complaint = '', ?string $datetime = null): array
{
    $pdo = db();
    $doctorId = $poliId ? (simrs_q('SELECT id FROM doctors WHERE poli_id = ? AND is_active = 1 ORDER BY id LIMIT 1', [$poliId])->fetchColumn() ?: null) : null;
    $code     = $poliId ? (string)simrs_q('SELECT code FROM poli WHERE id = ?', [$poliId])->fetchColumn() : 'UMM';
    $date     = $datetime ?: date('Y-m-d H:i:s');
    $n        = (int)simrs_q('SELECT COUNT(*) FROM visits WHERE poli_id <=> ? AND DATE(visit_date) = DATE(?)', [$poliId, $date])->fetchColumn() + 1;
    $queue    = strtoupper(substr($code, 0, 1)) . '-' . str_pad((string)$n, 3, '0', STR_PAD_LEFT);
    $prefix   = $type === 'rawat_inap' ? 'RI' : ($type === 'igd' ? 'IGD' : 'RJ');
    $seq      = (int)$pdo->query('SELECT COALESCE(MAX(id),0)+1 FROM visits')->fetchColumn();
    $visitNo  = $prefix . '-' . date('Ymd', strtotime($date)) . '-' . str_pad((string)$seq, 4, '0', STR_PAD_LEFT);
    simrs_q('INSERT INTO visits (visit_no, patient_id, visit_type, poli_id, doctor_id, insurer_id, queue_no, visit_date, complaint, status, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?)',
        [$visitNo, $patientId, $type, $poliId, $doctorId, $insurerId, $queue, $date, $complaint ?: null, $type === 'rawat_inap' ? 'dirawat' : 'menunggu', $_SESSION['user']['id'] ?? null]);
    return [$visitNo, $queue];
}

/**
 * Registrasi pasien baru (atau pasien lama bila NIK sudah terdaftar) + kunjungan.
 * Mengembalikan ['ok'=>bool, 'msg'=>string].
 */
function simrs_register(array $in): array
{
    $nama   = trim($in['nama'] ?? '');
    $nik    = preg_replace('/\D/', '', $in['nik'] ?? '');
    $tgl    = $in['tanggal_lahir'] ?? '';
    $jk     = ($in['jenis_kelamin'] ?? '') === 'Perempuan' ? 'P' : 'L';
    $poli   = $in['poliklinik'] ?? '';
    $penj   = $in['penjamin'] ?? 'Umum';
    if ($nama === '' || strlen($nik) !== 16 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl) || $poli === '' || empty($in['jenis_kelamin'])) {
        return ['ok' => false, 'msg' => 'Lengkapi data wajib: nama, NIK 16 digit, tanggal lahir, jenis kelamin, dan poliklinik.'];
    }
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $existing = simrs_q('SELECT id, mrn FROM patients WHERE nik = ?', [$nik])->fetch();
        $insurer  = simrs_insurer_id($penj);
        if ($existing) {
            $pid = (int)$existing['id']; $mrn = $existing['mrn']; $lama = true;
        } else {
            $mrn = str_pad((string)((int)$pdo->query('SELECT COALESCE(MAX(CAST(mrn AS UNSIGNED)),0)+1 FROM patients')->fetchColumn()), 8, '0', STR_PAD_LEFT);
            simrs_q('INSERT INTO patients (mrn, nik, name, gender, birth_date, blood_type, address, phone, insurer_id) VALUES (?,?,?,?,?,?,?,?,?)',
                [$mrn, $nik, $nama, $jk, $tgl, ($in['golongan_darah'] ?? '') ?: null, trim($in['alamat'] ?? '') ?: null, trim($in['no_hp'] ?? '') ?: null, $insurer]);
            $pid = (int)$pdo->lastInsertId(); $lama = false;
        }
        [$visitNo, $queue] = simrs_create_visit($pid, simrs_poli_id($poli), $insurer);
        $pdo->commit();
        simrs_audit('CREATE', 'Patient Registration', ($lama ? 'Kunjungan pasien lama ' : 'Registrasi pasien baru ') . "$nama ($mrn), antrean $queue");
        return ['ok' => true, 'msg' => ($lama ? 'Pasien sudah terdaftar (NIK sama) — kunjungan baru dibuat. ' : 'Pasien baru tersimpan di database. ')
            . "No. RM: $mrn · No. Kunjungan: $visitNo · No. Antrean: $queue"];
    } catch (Throwable $e) {
        $pdo->rollBack();
        return ['ok' => false, 'msg' => 'Gagal menyimpan ke database: ' . $e->getMessage()];
    }
}
