<?php
/*
 * KONFIGURASI DATABASE (MySQL / phpMyAdmin XAMPP)
 * Default XAMPP: host localhost, user root, password kosong, port 3306.
 * Ubah nilai di bawah jika pengaturan MySQL Anda berbeda.
 */
define('DB_HOST', getenv('SIMRS_DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('SIMRS_DB_PORT') ?: '3306');
define('DB_NAME', getenv('SIMRS_DB_NAME') ?: 'simrs_suka_sehat');
define('DB_USER', getenv('SIMRS_DB_USER') ?: 'root');
define('DB_PASS', getenv('SIMRS_DB_PASS') !== false ? getenv('SIMRS_DB_PASS') : '');

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}
