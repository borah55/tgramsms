<?php
/**
 * Generic helpers: settings, formatting, redirect, validators.
 */
if (!defined('DOGEMINE')) define('DOGEMINE', true);

require_once __DIR__ . '/db.php';

function setting(string $name, $default = null) {
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try {
            foreach (db()->query('SELECT name, value FROM settings') as $row) {
                $cache[$row['name']] = $row['value'];
            }
        } catch (Throwable $e) {}
    }
    return $cache[$name] ?? $default;
}

function setting_set(string $name, string $value): void {
    $stmt = db()->prepare('INSERT INTO settings (name, value) VALUES (?,?)
                            ON DUPLICATE KEY UPDATE value = VALUES(value)');
    $stmt->execute([$name, $value]);
}

function redirect(string $url): void {
    if (!preg_match('#^https?://#i', $url)) {
        $base = defined('SITE_URL') ? rtrim(SITE_URL, '/') : '';
        $url = $base . '/' . ltrim($url, '/');
    }
    header('Location: ' . $url);
    exit;
}

function fmt_doge($amount): string {
    return number_format((float)$amount, 8, '.', '') . ' DOGE';
}

function fmt_short_doge($amount): string {
    return number_format((float)$amount, 4, '.', '') . ' DOGE';
}

function fmt_power($p): string {
    $p = (float)$p;
    if ($p >= 1_000_000) return number_format($p / 1_000_000, 2) . ' PH/s';
    if ($p >= 1_000)     return number_format($p / 1_000, 2)     . ' TH/s';
    return number_format($p, 0) . ' GH/s';
}

function valid_doge_address(string $addr): bool {
    // Legacy DOGE: starts with D, 25-34 base58 chars. Conservative check.
    return (bool)preg_match('/^[A-DK-Za-km-z1-9]{25,40}$/', $addr) && (str_starts_with($addr, 'D') || str_starts_with($addr, 'A') || str_starts_with($addr, '9'));
}

function valid_pin(string $pin): bool {
    return (bool)preg_match('/^\d{4}$/', $pin);
}

function gen_referral_code(int $len = 8): string {
    $chars = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
    $out = '';
    for ($i = 0; $i < $len; $i++) {
        $out .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $out;
}

function maintenance_guard(): void {
    if ((int)setting('maintenance_mode', 0) === 1) {
        $isAdmin = !empty($_SESSION['admin_id'] ?? null);
        $path = $_SERVER['REQUEST_URI'] ?? '';
        if (!$isAdmin && strpos($path, '/admin') === false) {
            http_response_code(503);
            include __DIR__ . '/maintenance.php';
            exit;
        }
    }
}

function pagination(int $total, int $perPage, int $current): array {
    $pages = max(1, (int)ceil($total / max(1,$perPage)));
    $current = max(1, min($pages, $current));
    return ['pages' => $pages, 'current' => $current, 'total' => $total, 'per_page' => $perPage];
}
