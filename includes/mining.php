<?php
/**
 * Mining engine.
 *
 * Each user accrues DOGE every second based on the sum of `daily_reward` of
 * all their active plans (including the lifetime signup bonus). Earnings are
 * credited on demand whenever update_user_mining() runs.
 */
if (!defined('DOGEMINE')) define('DOGEMINE', true);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

function user_active_plans(int $userId): array {
    $stmt = db()->prepare(
        'SELECT * FROM user_plans
         WHERE user_id = ?
           AND status = "active"
           AND (is_lifetime = 1 OR expires_at > NOW())
         ORDER BY id ASC'
    );
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function user_total_speed(int $userId): float {
    $plans = user_active_plans($userId);
    $sum = 0.0;
    foreach ($plans as $p) $sum += (float)$p['mining_speed'];
    return $sum;
}

function user_total_daily(int $userId): float {
    $plans = user_active_plans($userId);
    $sum = 0.0;
    foreach ($plans as $p) $sum += (float)$p['daily_reward'];
    return $sum;
}

/**
 * Auto-credit mining earnings since last_mining_at.
 * Returns mined amount (float).
 */
function update_user_mining(int $userId): float {
    $pdo = db();
    $u = $pdo->prepare('SELECT id, last_mining_at, balance, total_mined FROM users WHERE id = ?');
    $u->execute([$userId]);
    $user = $u->fetch();
    if (!$user) return 0.0;

    // Expire plans first
    $pdo->prepare(
        'UPDATE user_plans SET status="expired"
         WHERE user_id=? AND status="active" AND is_lifetime=0 AND expires_at <= NOW()'
    )->execute([$userId]);

    $daily = user_total_daily($userId);
    if ($daily <= 0) {
        $pdo->prepare('UPDATE users SET last_mining_at = NOW() WHERE id = ?')->execute([$userId]);
        return 0.0;
    }

    $last = $user['last_mining_at'] ? strtotime($user['last_mining_at']) : time();
    $now  = time();
    $delta = max(0, $now - $last);

    // Cap at 24 hours per call to avoid accidental abuse
    if ($delta > 86400) $delta = 86400;

    $perSecond = $daily / 86400.0;
    $mined = $perSecond * $delta;

    if ($mined <= 0) {
        $pdo->prepare('UPDATE users SET last_mining_at = NOW() WHERE id = ?')->execute([$userId]);
        return 0.0;
    }

    $pdo->prepare('UPDATE users SET balance = balance + ?, total_mined = total_mined + ?, last_mining_at = NOW() WHERE id = ?')
        ->execute([$mined, $mined, $userId]);

    $pdo->prepare('INSERT INTO mining_logs (user_id, amount, mining_power, note) VALUES (?,?,?,?)')
        ->execute([$userId, $mined, user_total_speed($userId), 'auto-mining']);

    return (float)$mined;
}

/** Buy a plan from the user's balance. Returns [bool, msg]. */
function purchase_plan(int $userId, int $planId): array {
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $u = $pdo->prepare('SELECT * FROM users WHERE id=? FOR UPDATE');
        $u->execute([$userId]);
        $user = $u->fetch();
        if (!$user) { $pdo->rollBack(); return [false, 'User not found.']; }

        $p = $pdo->prepare('SELECT * FROM plans WHERE id=? AND status="active"');
        $p->execute([$planId]);
        $plan = $p->fetch();
        if (!$plan) { $pdo->rollBack(); return [false, 'Plan not available.']; }

        if ((float)$user['balance'] < (float)$plan['price']) {
            $pdo->rollBack(); return [false, 'Insufficient balance.'];
        }

        $bonus = (float)$plan['bonus_percent'];
        $speed = (float)$plan['mining_speed'] * (1 + $bonus/100);
        $daily = (float)$plan['daily_reward'] * (1 + $bonus/100);
        $expires = date('Y-m-d H:i:s', strtotime('+' . (int)$plan['duration_days'] . ' days'));

        $pdo->prepare('UPDATE users SET balance = balance - ? WHERE id = ?')
            ->execute([$plan['price'], $userId]);
        $pdo->prepare(
            'INSERT INTO user_plans (user_id, plan_id, mining_speed, daily_reward, expires_at, is_lifetime, status)
             VALUES (?,?,?,?,?,0,"active")'
        )->execute([$userId, $planId, $speed, $daily, $expires]);

        $pdo->prepare(
            'INSERT INTO notifications (user_id, title, message, type) VALUES (?,?,?,?)'
        )->execute([$userId, 'Plan Activated', 'You activated the ' . $plan['name'] . ' mining plan.', 'success']);

        $pdo->commit();
        return [true, 'Plan activated.'];
    } catch (Throwable $e) {
        $pdo->rollBack();
        return [false, 'Failed to purchase plan.'];
    }
}

/** Pay referral commission on a deposit. */
function pay_referral_commission(int $userId, float $depositAmount): void {
    $pdo = db();
    $u = $pdo->prepare('SELECT referred_by FROM users WHERE id=?');
    $u->execute([$userId]);
    $row = $u->fetch();
    if (!$row || empty($row['referred_by'])) return;

    $percent = (float)setting('referral_percent', 15);
    $commission = $depositAmount * ($percent / 100);
    if ($commission <= 0) return;

    $referrerId = (int)$row['referred_by'];
    $pdo->prepare('UPDATE users SET balance = balance + ?, referral_earnings = referral_earnings + ? WHERE id = ?')
        ->execute([$commission, $commission, $referrerId]);
    $pdo->prepare(
        'INSERT INTO referrals (referrer_id, referred_id, level, amount, source) VALUES (?,?,1,?,?)'
    )->execute([$referrerId, $userId, $commission, 'deposit']);
    $pdo->prepare(
        'INSERT INTO notifications (user_id, title, message, type) VALUES (?,?,?,?)'
    )->execute([$referrerId, 'Referral Commission', 'You earned ' . fmt_short_doge($commission) . ' from your referral.', 'success']);
}
