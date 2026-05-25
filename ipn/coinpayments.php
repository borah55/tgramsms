<?php
/**
 * CoinPayments IPN handler.
 * Configure your IPN URL in CoinPayments to: https://your-domain/ipn/coinpayments.php
 *
 * Supported IPN types:
 *   - "deposit"         (incoming wallet payments)
 *   - merchant API IPN  (status >= 100 = completed)
 */
require_once __DIR__ . '/../includes/bootstrap.php';

$logFile = __DIR__ . '/cp_ipn.log';
function cp_log(string $msg): void {
    @file_put_contents(__DIR__ . '/cp_ipn.log', '[' . date('c') . "] $msg\n", FILE_APPEND);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('IPN only accepts POST.');
}

$ipnSecret = setting('cp_ipn_secret', '');
$merchant  = setting('cp_merchant_id', '');

if (empty($ipnSecret) || empty($merchant)) {
    cp_log('Missing IPN secret/merchant id in settings.');
    http_response_code(500);
    die('IPN not configured.');
}

// Required headers per CoinPayments spec
$hmacHeader = $_SERVER['HTTP_HMAC'] ?? '';
$rawPost    = file_get_contents('php://input');
$expectedHmac = hash_hmac('sha512', $rawPost, $ipnSecret);

if (empty($hmacHeader) || !hash_equals($expectedHmac, $hmacHeader)) {
    cp_log('Invalid HMAC. ip=' . client_ip());
    http_response_code(403);
    die('Invalid HMAC.');
}

if (empty($_POST['merchant']) || !hash_equals($merchant, (string)$_POST['merchant'])) {
    cp_log('Merchant mismatch.');
    http_response_code(403);
    die('Merchant mismatch.');
}

$ipnType = $_POST['ipn_type']     ?? '';
$status  = (int)($_POST['status'] ?? -99);
$txnId   = $_POST['txn_id']       ?? '';
$custom  = $_POST['custom']       ?? '';
$amount  = (float)($_POST['amount']    ?? 0);
$amount1 = (float)($_POST['amount1']   ?? $amount); // amount in DOGE
$currency1 = $_POST['currency1']  ?? 'DOGE';
$buyer   = $_POST['address']      ?? null;

$userId = (int)$custom;

if ($userId <= 0 || empty($txnId)) {
    cp_log('Missing user/txn. data=' . json_encode($_POST));
    http_response_code(400);
    die('Invalid payload.');
}

$pdo = db();
$pdo->beginTransaction();
try {
    // Idempotent insert/lookup
    $exists = $pdo->prepare('SELECT * FROM deposits WHERE txn_id = ? FOR UPDATE');
    $exists->execute([$txnId]);
    $row = $exists->fetch();

    if (!$row) {
        $pdo->prepare(
            'INSERT INTO deposits (user_id, txn_id, address, currency, amount, amount_usd, status, gateway, meta)
             VALUES (?,?,?,?,?,?,?,?,?)'
        )->execute([
            $userId, $txnId, $buyer, $currency1, $amount1, $amount,
            'pending', 'coinpayments', json_encode($_POST),
        ]);
        $depId = (int)$pdo->lastInsertId();
    } else {
        $depId = (int)$row['id'];
        if ($row['status'] === 'completed') {
            $pdo->commit();
            echo 'IPN OK (already credited)';
            exit;
        }
    }

    // status >= 100  -> completed
    // status ==   2  -> "queued" (treat as pending)
    // status <    0  -> failed/cancelled
    if ($status >= 100 || $status === 2) {
        // For "queued" we wait. Only credit on completed.
        if ($status >= 100) {
            $pdo->prepare(
                'UPDATE deposits SET status="completed", confirmed_at=NOW(), meta=? WHERE id=?'
            )->execute([json_encode($_POST), $depId]);

            $pdo->prepare(
                'UPDATE users SET balance = balance + ?, total_deposit = total_deposit + ? WHERE id = ?'
            )->execute([$amount1, $amount1, $userId]);

            $pdo->prepare(
                'INSERT INTO notifications (user_id, title, message, type) VALUES (?,?,?,?)'
            )->execute([$userId, 'Deposit Confirmed',
                'Your deposit of ' . number_format($amount1, 4) . ' ' . $currency1 . ' has been credited.', 'success']);

            $pdo->commit();

            // Pay referral commission outside of the deposit txn
            pay_referral_commission($userId, (float)$amount1);

            cp_log("Credited user=$userId amount=$amount1 txn=$txnId");
            echo 'IPN OK';
            exit;
        }
    } elseif ($status < 0) {
        $pdo->prepare('UPDATE deposits SET status="failed", meta=? WHERE id=?')
            ->execute([json_encode($_POST), $depId]);
    }

    $pdo->commit();
    echo 'IPN OK';
} catch (Throwable $e) {
    $pdo->rollBack();
    cp_log('IPN error: ' . $e->getMessage());
    http_response_code(500);
    die('IPN error.');
}
