<?php
require_once __DIR__ . '/includes/bootstrap.php';
$user = require_login();

$minDep   = (float)setting('min_deposit', 5);
$merchant = setting('cp_merchant_id', '');
$cpPub    = setting('cp_public_key', '');

$error = $success = null;

// Manual deposit (record pending — admin will verify) is also supported
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'manual') {
    require_csrf();
    $txn  = trim((string)($_POST['txn'] ?? ''));
    $amt  = (float)($_POST['amount'] ?? 0);

    if ($amt < $minDep) {
        $error = "Minimum deposit is {$minDep} DOGE.";
    } elseif (!preg_match('/^[A-Za-z0-9-_]{8,128}$/', $txn)) {
        $error = 'Invalid transaction ID.';
    } else {
        try {
            db()->prepare(
                'INSERT INTO deposits (user_id, txn_id, currency, amount, status, gateway)
                 VALUES (?,?,?,?,?,?)'
            )->execute([$user['id'], $txn, 'DOGE', $amt, 'pending', 'manual']);
            $success = 'Deposit submitted. It will be credited after confirmation.';
            log_security((int)$user['id'], 'deposit_manual', ['txn' => $txn, 'amount' => $amt]);
        } catch (Throwable $e) {
            $error = 'This transaction was already submitted.';
        }
    }
}

$deposits = db()->prepare('SELECT * FROM deposits WHERE user_id = ? ORDER BY id DESC LIMIT 25');
$deposits->execute([$user['id']]);
$deposits = $deposits->fetchAll();

$active = 'deposit';
$page_title = 'Deposit';
include __DIR__ . '/includes/dashboard_header.php';
?>
<div class="container-fluid">
  <h3 class="mb-3">Deposit DOGE</h3>

  <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

  <div class="row g-3">
    <div class="col-lg-7">
      <div class="dm-panel">
        <h5><i class="fab fa-bitcoin text-warning"></i> Pay with CoinPayments</h5>
        <p class="text-secondary small mb-3">Minimum deposit: <strong><?= number_format($minDep, 2) ?> DOGE</strong>. Payment is auto-credited via IPN.</p>

        <?php if (empty($merchant)): ?>
          <div class="alert alert-warning">CoinPayments is not yet configured. Please contact admin.</div>
        <?php else: ?>
          <form action="https://www.coinpayments.net/index.php" method="post" target="_blank">
            <input type="hidden" name="cmd" value="_pay_simple">
            <input type="hidden" name="reset" value="1">
            <input type="hidden" name="merchant" value="<?= e($merchant) ?>">
            <input type="hidden" name="currency" value="DOGE">
            <input type="hidden" name="want_shipping" value="0">
            <input type="hidden" name="invoice" value="<?= e('U' . $user['id'] . '-' . bin2hex(random_bytes(4))) ?>">
            <input type="hidden" name="custom" value="<?= e((string)$user['id']) ?>">
            <input type="hidden" name="success_url" value="<?= e(SITE_URL) ?>/deposit.php?paid=1">
            <input type="hidden" name="cancel_url"  value="<?= e(SITE_URL) ?>/deposit.php?cancel=1">
            <input type="hidden" name="ipn_url"     value="<?= e(SITE_URL) ?>/ipn/coinpayments.php">

            <div class="mb-3">
              <label class="form-label">Amount (DOGE)</label>
              <input type="number" class="form-control" name="amountf" step="0.01"
                     min="<?= e($minDep) ?>" value="<?= e($minDep) ?>" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Currency</label>
              <select class="form-select" name="currency2">
                <option value="DOGE">DOGE</option>
                <option value="BTC">BTC</option>
                <option value="LTC">LTC</option>
                <option value="ETH">ETH</option>
                <option value="USDT.TRC20">USDT (TRC20)</option>
              </select>
            </div>
            <button class="btn btn-warning fw-bold" type="submit">
              <i class="fas fa-arrow-up-right-from-square"></i> Pay with CoinPayments
            </button>
          </form>
        <?php endif; ?>
      </div>

      <div class="dm-panel mt-3">
        <h6><i class="fas fa-pen-to-square text-warning"></i> Manual / Direct Deposit</h6>
        <p class="text-secondary small">If you sent DOGE directly, paste the transaction ID below — admin will confirm.</p>
        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="manual">
          <div class="row g-2">
            <div class="col-md-7">
              <input class="form-control" name="txn" placeholder="Transaction ID / TXID" required>
            </div>
            <div class="col-md-3">
              <input class="form-control" type="number" name="amount" placeholder="Amount" min="<?= e($minDep) ?>" step="0.01" required>
            </div>
            <div class="col-md-2">
              <button class="btn btn-outline-warning w-100">Submit</button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <div class="col-lg-5">
      <div class="dm-panel">
        <h5><i class="fas fa-clock-rotate-left text-warning"></i> Recent Deposits</h5>
        <div class="table-responsive">
          <table class="table table-sm dm-table mb-0">
            <thead><tr><th>Date</th><th>Amount</th><th>Status</th></tr></thead>
            <tbody>
              <?php if (!$deposits): ?>
                <tr><td colspan="3" class="text-center text-secondary small">No deposits yet.</td></tr>
              <?php else: foreach ($deposits as $d): ?>
                <tr>
                  <td class="small"><?= e(date('M d H:i', strtotime($d['created_at']))) ?></td>
                  <td><?= number_format($d['amount'], 4) ?> <?= e($d['currency']) ?></td>
                  <td>
                    <?php
                      $cls = ['pending'=>'warning','completed'=>'success','failed'=>'danger','cancelled'=>'secondary'][$d['status']] ?? 'secondary';
                    ?>
                    <span class="badge bg-<?= $cls ?>"><?= e(ucfirst($d['status'])) ?></span>
                  </td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/includes/dashboard_footer.php'; ?>
