<?php
/**
 * Security helpers: sessions, CSRF, sanitization, rate limiting.
 */
if (!defined('DOGEMINE')) define('DOGEMINE', true);

require_once __DIR__ . '/db.php';

function start_session(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    if (defined('SESSION_NAME')) session_name(SESSION_NAME);
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function csrf_token(): string {
    start_session();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string {
    $name = defined('CSRF_TOKEN_NAME') ? CSRF_TOKEN_NAME : '_csrf';
    return '<input type="hidden" name="' . htmlspecialchars($name) . '" value="' . htmlspecialchars(csrf_token()) . '">';
}

function csrf_check(): bool {
    start_session();
    $name = defined('CSRF_TOKEN_NAME') ? CSRF_TOKEN_NAME : '_csrf';
    $sent = $_POST[$name] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($sent) || empty($_SESSION['csrf'])) return false;
    return hash_equals($_SESSION['csrf'], $sent);
}

function require_csrf(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_check()) {
        http_response_code(403);
        die('Invalid CSRF token.');
    }
}

/** Output-safe escape */
function e($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Recursive XSS-safe input cleaner */
function clean_input($value) {
    if (is_array($value)) {
        return array_map('clean_input', $value);
    }
    if (is_string($value)) {
        $value = trim($value);
        return strip_tags($value);
    }
    return $value;
}

function client_ip(): string {
    $candidates = ['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','HTTP_X_REAL_IP','REMOTE_ADDR'];
    foreach ($candidates as $h) {
        if (!empty($_SERVER[$h])) {
            $ip = explode(',', $_SERVER[$h])[0];
            $ip = trim($ip);
            if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
        }
    }
    return '0.0.0.0';
}

function user_agent(): string {
    return substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 250);
}

function log_security(?int $userId, string $action, array $meta = []): void {
    try {
        $stmt = db()->prepare('INSERT INTO security_logs (user_id, action, ip, user_agent, meta) VALUES (?,?,?,?,?)');
        $stmt->execute([$userId, $action, client_ip(), user_agent(), json_encode($meta)]);
    } catch (Throwable $e) { /* silent */ }
}

/** Anti-bruteforce: check + record */
function record_login_attempt(?string $wallet, bool $success): void {
    try {
        $stmt = db()->prepare('INSERT INTO login_attempts (ip, wallet, success) VALUES (?,?,?)');
        $stmt->execute([client_ip(), $wallet, $success ? 1 : 0]);
    } catch (Throwable $e) {}
}

function is_locked_out(): bool {
    $max = defined('MAX_LOGIN_ATTEMPTS') ? (int)MAX_LOGIN_ATTEMPTS : 5;
    $mins = defined('LOCKOUT_MINUTES') ? (int)LOCKOUT_MINUTES : 15;
    try {
        $stmt = db()->prepare(
            'SELECT COUNT(*) FROM login_attempts
             WHERE ip = ? AND success = 0 AND created_at > (NOW() - INTERVAL ? MINUTE)'
        );
        $stmt->execute([client_ip(), $mins]);
        return ((int)$stmt->fetchColumn()) >= $max;
    } catch (Throwable $e) {
        return false;
    }
}

function flash_set(string $key, string $msg): void {
    start_session();
    $_SESSION['_flash'][$key] = $msg;
}
function flash_get(string $key): ?string {
    start_session();
    if (!empty($_SESSION['_flash'][$key])) {
        $m = $_SESSION['_flash'][$key];
        unset($_SESSION['_flash'][$key]);
        return $m;
    }
    return null;
}
