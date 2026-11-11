<?php
/*
 * SESI LOGIN DI DATABASE — dipakai saat aplikasi berjalan di Vercel (serverless).
 * Di Vercel setiap request bisa dilayani server yang berbeda, sehingga sesi file biasa
 * (folder tmp) bisa hilang dan pengguna ter-logout sendiri. Handler ini menyimpan sesi
 * di tabel `app_sessions` (dibuat otomatis bila belum ada).
 * Di XAMPP file ini TIDAK dipakai (sesi tetap memakai file bawaan PHP).
 */
require_once dirname(__DIR__) . '/config/database.php';

final class SimrsDbSessionHandler implements SessionHandlerInterface
{
    private bool $ready = false;

    private function table(): void
    {
        if ($this->ready) return;
        db()->exec('CREATE TABLE IF NOT EXISTS app_sessions (
            id VARCHAR(128) NOT NULL PRIMARY KEY,
            data MEDIUMBLOB NOT NULL,
            expires_at INT UNSIGNED NOT NULL,
            KEY idx_app_sessions_expires (expires_at)
        ) DEFAULT CHARSET=utf8mb4');
        $this->ready = true;
    }

    public function open(string $path, string $name): bool { return true; }
    public function close(): bool { return true; }

    // Bila database belum siap/terputus, sesi dianggap kosong dan halaman tetap tampil
    // (halaman login akan menampilkan pesan "database belum terhubung").
    public function read(string $id): string|false
    {
        try {
            $this->table();
            $st = db()->prepare('SELECT data FROM app_sessions WHERE id = ? AND expires_at > ?');
            $st->execute([$id, time()]);
            $v = $st->fetchColumn();
            return $v === false ? '' : (string)$v;
        } catch (Throwable $e) { return ''; }
    }

    public function write(string $id, string $data): bool
    {
        try {
            $this->table();
            $ttl = (int)ini_get('session.gc_maxlifetime') ?: 7200;
            $st = db()->prepare('INSERT INTO app_sessions (id, data, expires_at) VALUES (?, ?, ?)
                                 ON DUPLICATE KEY UPDATE data = VALUES(data), expires_at = VALUES(expires_at)');
            return $st->execute([$id, $data, time() + $ttl]);
        } catch (Throwable $e) { return true; }
    }

    public function destroy(string $id): bool
    {
        try {
            $this->table();
            return db()->prepare('DELETE FROM app_sessions WHERE id = ?')->execute([$id]);
        } catch (Throwable $e) { return true; }
    }

    public function gc(int $max_lifetime): int|false
    {
        try {
            $this->table();
            $st = db()->prepare('DELETE FROM app_sessions WHERE expires_at < ?');
            $st->execute([time()]);
            return $st->rowCount();
        } catch (Throwable $e) { return 0; }
    }
}

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', '28800');          // 8 jam
    ini_set('session.gc_probability', '1');
    ini_set('session.gc_divisor', '50');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => (($_SERVER['HTTPS'] ?? '') === 'on') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_set_save_handler(new SimrsDbSessionHandler(), true);
}
