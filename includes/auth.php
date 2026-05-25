<?php
/**
 * Authentication: signup, login, sessions, remember-me.
 */
if (!defined('DOGEMINE')) define('DOGEMINE', true);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/functions.php';

function current_user(): ?array {
    static $user = null;
    if ($user !== null) return $user ?: null;
    start_session();

    $uid = $_SESSION['user_id'] ?? null;

    // Try remember-me cookie
    if (!$uid && !empty($_COOKIE[REMEMBER_COOKIE])) {
        $token = $_COOKIE[REMEMBER_COOKIE];
        $stmt = db()->prepare('SELECT * FROM users WHERE remember_token = ? LIMIT 1');
        $stmt->execute([hash('sha256', $token)]);
        $u = $stmt->fetch();
        if ($u && $u['status'] === 'active') {
            $_SESSION['user_id'] = (int)$u['id'];
            $uid = (int)$u['id'];
        }
    }

    if (!$uid) { $user = false; return null; }

    $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$uid]);
    $u = $stmt->fetch();
    if (!$u || $u['status'] !== 'active') {
        logout_user();
        $user = false; return null;
    }
    $user = $u;
    return $u;
}

function require_login(): array {
    $u = current_user();
    if (!$u) redirect('login.php');
    return $u;
}

function logout_user(): void {
    start_session();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    if (defined('REMEMBER_COOKIE') && !empty($_COOKIE[REMEMBER_COOKIE])) {
        try {
            db()->prepare('UPDATE users SET remember_token = NULL WHERE remember_token = ?')
               ->execute([hash('sha256', $_COOKIE[REMEMBER_COOKIE])]);
        } catch (Throwable $e) {}
        setcookie(REMEMBER_COOKIE, '', time() - 3600, '/');
    }
    session_destroy();
}

/**
 * Sign up new user.
 * @return array [success(bool), userId|errorMsg]
 */
function signup_user(string $wallet, string $pin, ?string $referralCode = null): array {
    $wallet = trim($wallet);
    if (!valid_doge_address($wallet)) return [false, 'Invalid Dogecoin wallet address.'];
    if (!valid_pin($pin))             return [false, 'PIN must be exactly 4 digits.'];

    $stmt = db()->prepare('SELECT id FROM users WHERE wallet = ?');
    $stmt->execute([$wallet]);
    if ($stmt->fetch()) return [false, 'This wallet is already registered.'];

    $referrerId = null;
    if (!empty($referralCode)) {
        $s = db()->prepare('SELECT id FROM users WHERE referral_code = ?');
        $s->execute([strtoupper($referralCode)]);
        $r = $s->fetch();
        if ($r) $referrerId = (int)$r['id'];
    }

    // Unique referral code
    do {
        $code = gen_referral_code(8);
        $s = db()->prepare('SELECT id FROM users WHERE referral_code = ?');
        $s->execute([$code]);
    } while ($s->fetch());

    $bonusPower  = (float)setting('signup_bonus_power', 100);
    $bonusDaily  = (float)setting('signup_bonus_daily', 0.20);
    $pinHash     = password_hash($pin, PASSWORD_BCRYPT);

    $ins = db()->prepare(
        'INSERT INTO users (wallet, pin_hash, referral_code, referred_by, mining_power, daily_reward, last_mining_at, last_ip, last_login_at)
         VALUES (?,?,?,?,?,?,NOW(),?,NOW())'
    );
    $ins->execute([$wallet, $pinHash, $code, $referrerId, $bonusPower, $bonusDaily, client_ip()]);
    $uid = (int)db()->lastInsertId();

    // Lifetime signup bonus plan
    db()->prepare(
        'INSERT INTO user_plans (user_id, plan_id, mining_speed, daily_reward, expires_at, is_lifetime, status)
         VALUES (?,0,?,?,?,1,"active")'
    )->execute([$uid, $bonusPower, $bonusDaily, '2099-12-31 00:00:00']);

    db()->prepare('INSERT INTO notifications (user_id, title, message, type) VALUES (?,?,?,?)')
        ->execute([$uid, 'Welcome!', 'You received a free '.$bonusPower.' GH/s lifetime mining bonus.', 'success']);

    log_security($uid, 'signup');
    return [true, $uid];
}

/**
 * Login user.
 */
function login_user(string $wallet, string $pin, bool $remember): array {
    if (is_locked_out()) return [false, 'Too many failed attempts. Try again later.'];
    if (!valid_doge_address($wallet) || !valid_pin($pin)) {
        record_login_attempt($wallet, false);
        return [false, 'Invalid wallet or PIN.'];
    }

    $stmt = db()->prepare('SELECT * FROM users WHERE wallet = ? LIMIT 1');
    $stmt->execute([$wallet]);
    $u = $stmt->fetch();

    if (!$u || !password_verify($pin, $u['pin_hash'])) {
        record_login_attempt($wallet, false);
        return [false, 'Invalid wallet or PIN.'];
    }
    if ($u['status'] !== 'active') {
        return [false, 'Your account is disabled or banned.'];
    }

    record_login_attempt($wallet, true);
    start_session();
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$u['id'];

    db()->prepare('UPDATE users SET last_ip=?, last_login_at=NOW() WHERE id=?')
        ->execute([client_ip(), $u['id']]);

    if ($remember) {
        $token = bin2hex(random_bytes(32));
        $hash = hash('sha256', $token);
        db()->prepare('UPDATE users SET remember_token = ? WHERE id = ?')->execute([$hash, $u['id']]);
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        setcookie(REMEMBER_COOKIE, $token, [
            'expires'  => time() + 60*60*24*30,
            'path'     => '/',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    log_security((int)$u['id'], 'login');
    return [true, (int)$u['id']];
}

/**
 * Admin auth helpers.
 */
function current_admin(): ?array {
    start_session();
    $aid = $_SESSION['admin_id'] ?? null;
    if (!$aid) return null;
    $stmt = db()->prepare('SELECT * FROM admins WHERE id = ?');
    $stmt->execute([$aid]);
    $a = $stmt->fetch();
    return $a ?: null;
}
function require_admin(): array {
    $a = current_admin();
    if (!$a) redirect('login.php');
    return $a;
}
function admin_login(string $username, string $password): array {
    if (is_locked_out()) return [false, 'Too many failed attempts. Try again later.'];
    $stmt = db()->prepare('SELECT * FROM admins WHERE username = ?');
    $stmt->execute([$username]);
    $a = $stmt->fetch();
    if (!$a || !password_verify($password, $a['password_hash'])) {
        record_login_attempt($username, false);
        return [false, 'Invalid credentials.'];
    }
    record_login_attempt($username, true);
    start_session();
    session_regenerate_id(true);
    $_SESSION['admin_id'] = (int)$a['id'];
    db()->prepare('UPDATE admins SET last_login_at=NOW(), last_ip=? WHERE id=?')
        ->execute([client_ip(), $a['id']]);
    log_security(null, 'admin_login', ['admin_id' => (int)$a['id']]);
    return [true, (int)$a['id']];
}
function admin_logout(): void {
    start_session();
    unset($_SESSION['admin_id']);
}
