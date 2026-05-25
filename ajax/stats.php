<?php
/** Public live network stats + fake earning feed. */
require_once __DIR__ . '/../includes/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
    $totalUsers   = (int)db()->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $totalMined   = (float)db()->query('SELECT COALESCE(SUM(total_mined),0) FROM users')->fetchColumn();
    $totalPaid    = (float)db()->query('SELECT COALESCE(SUM(amount),0) FROM withdrawals WHERE status IN ("approved","paid")')->fetchColumn();
    $totalHashRate= (float)db()->query('SELECT COALESCE(SUM(mining_speed),0) FROM user_plans WHERE status="active"')->fetchColumn();

    $deposits = db()->query(
        'SELECT d.amount, d.currency, d.created_at, u.wallet
         FROM deposits d JOIN users u ON u.id=d.user_id
         WHERE d.status="completed" ORDER BY d.id DESC LIMIT 6'
    )->fetchAll();

    $withdrawals = db()->query(
        'SELECT w.amount, w.created_at, u.wallet
         FROM withdrawals w JOIN users u ON u.id=w.user_id
         WHERE w.status IN ("approved","paid") ORDER BY w.id DESC LIMIT 6'
    )->fetchAll();
} catch (Throwable $e) {
    $totalUsers = $totalMined = $totalPaid = $totalHashRate = 0;
    $deposits = $withdrawals = [];
}

$mask = function (string $w): string {
    return substr($w, 0, 6) . '…' . substr($w, -4);
};

$fakeEnabled = (int)setting('fake_notifications', 1) === 1;
$fake = null;

if ($fakeEnabled) {
    $names = ['DUbsP5','DKwj1m','D7rzNn','DPq8Fh','DCsbXk','DAxHdd','D9ZJfM','DRtY7v'];
    $verbs = ['mined', 'withdrew', 'deposited', 'earned'];
    $name  = $names[array_rand($names)] . str_pad((string)random_int(100, 999), 3, '0');
    $verb  = $verbs[array_rand($verbs)];
    $amt   = round(random_int(20, 35000) / 100, 4);
    $fake = ['user' => $name . '…' . substr(strtoupper(bin2hex(random_bytes(2))),0,4), 'action' => $verb, 'amount' => $amt];
}

echo json_encode([
    'ok' => true,
    'totals' => [
        'users'    => $totalUsers,
        'mined'    => round($totalMined, 4),
        'paid'     => round($totalPaid, 4),
        'hashrate' => round($totalHashRate, 0),
    ],
    'recent' => [
        'deposits' => array_map(function ($d) use ($mask) {
            return [
                'wallet'   => $mask($d['wallet']),
                'amount'   => (float)$d['amount'],
                'currency' => $d['currency'],
                'time'     => date('M d, H:i', strtotime($d['created_at'])),
            ];
        }, $deposits),
        'withdrawals' => array_map(function ($w) use ($mask) {
            return [
                'wallet' => $mask($w['wallet']),
                'amount' => (float)$w['amount'],
                'time'   => date('M d, H:i', strtotime($w['created_at'])),
            ];
        }, $withdrawals),
    ],
    'fake' => $fake,
    'server_time' => time(),
]);
