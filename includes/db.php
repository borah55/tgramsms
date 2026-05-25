<?php
/**
 * Database connection - PDO singleton.
 */
if (!defined('DOGEMINE')) define('DOGEMINE', true);

if (!file_exists(__DIR__ . '/config.php')) {
    if (basename($_SERVER['SCRIPT_NAME'] ?? '') !== 'install.php') {
        header('Location: install.php');
        exit;
    }
    return;
}
require_once __DIR__ . '/config.php';

date_default_timezone_set(defined('TIMEZONE') ? TIMEZONE : 'UTC');

if (defined('DEBUG_MODE') && DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $opts = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $opts);
        } catch (Throwable $e) {
            http_response_code(500);
            die('Database connection failed.' . (defined('DEBUG_MODE') && DEBUG_MODE ? ' ' . $e->getMessage() : ''));
        }
    }
    return $pdo;
}
