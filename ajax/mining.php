<?php
/** Live mining update — credits earnings since last call and returns fresh stats. */
require_once __DIR__ . '/../includes/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');

$user = current_user();
if (!$user) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'unauthenticated']);
    exit;
}

$uid = (int)$user['id'];
$mined = update_user_mining($uid);

$u = current_user();
$speed   = user_total_speed($uid);
$daily   = user_total_daily($uid);

echo json_encode([
    'ok'              => true,
    'balance'         => round((float)$u['balance'], 8),
    'total_mined'     => round((float)$u['total_mined'], 8),
    'mining_power'    => round($speed, 4),
    'daily_reward'    => round($daily, 8),
    'per_second'      => round($daily / 86400, 12),
    'mined_this_call' => round($mined, 8),
    'server_time'     => time(),
]);
