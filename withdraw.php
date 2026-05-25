<?php
require_once __DIR__ . '/includes/bootstrap.php';
$user = require_login();
update_user_mining((int)$user['id']);
$user = current_user();

$minW   = (float)setting('min_withdraw', 50);
$fee    = (float)setting('withdraw_fee', 1);
$daily  = (float)setting('daily_withdraw_limit', 1000);

$error = $success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $amt    = (float)($_POST['amount'] ?? 0);
    $wallet = trim((string)($_POST['wallet'] ?? $user['wallet']));

    if (!valid_doge_address($wallet)) {
        $error = 'Invalid Dogecoin wallet address.';
    } elseif ($amt < $minW) {
        $error = "Minimum withdrawal is {$minW} DOGE.";
    } elseif ($amt > (float)$user['balance']) {
        $error = 'Insufficient balance.';
    } else {
        // Daily limit check
        $sumStmt = db()->prepare(
            'SELECT COALESCE(SUM(amount),0) FROM withdrawals
             WHERE user_id=? AND status IN ("pending","approved","paid") AND created_at >= (NOW() - INTERVAL 1 DAY)'
        );
        $sumStmt->execute([$user['id']]);
        $sum24 = (float)$sumStmt->fetchColumn();
        if (($sum24 + $amt) > $daily) {
            $error = "Daily withdrawal limit reached ({$daily} DOGE / 24h).";
        } else {
            $net = max(0, $amt - $fee);
            db()->beginTransaction();
            try {
                db()->prepare('UPDATE users SET balance = balance - ? WHERE id = ?')
                    ->execute([$amt, $user['id']]);
                db()->prepare(
                    'INSERT INTO withdrawals (user_id, wallet, amount, fee, net_amount, status)
                     VALUES (?,?,?,?,?, "pending")'
                )->execute([$user['id'], $wallet, $amt, $fee, $net]);
                db()->prepare('INSERT INTO notifications (user_id, title, message, type) VALUES (?,?,?,?)')
                    ->execute([$user['id'], 'Withdrawal Submitted',
                        'Your withdrawal of ' . number_format($amt, 4) . ' DOGE is pending approval.', 'info']);
                db()->commit();
                log_security((int)$user['id'], 'withdraw_request', ['amount' => $amt]);
                $success = 'Withdrawal request submitted.';
            } catch (Throwable $e) {
                db()->rollBack();
                $error = 'Failed to submit withdrawal.';
            }
        }
    }
}

$user = current_user();
$ws = db()->prepare('SELECT * FROM withdrawals WHERE user_id = ? ORDER BY id DESC LIMIT 25');
$ws->execute([$user['id']]);
$withdrawals = $ws->fetchAll();

$active = 'withdraw';
$page_title = 'Withdraw';
include __DIR__ . '/includes/dashboard_header.php';
?>
<div class="container-fluid">
  <h3 class="mb-3">Withdraw DOGE</h3>

  <?php if ($error):   ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

  <div class="row g-3">
    <div class="col-lg-6">
      <div class="dm-panel">
        <h5><i class="fas fa-paper-plane text-warning"></i> Withdrawal Request</h5>
        <ul class="list-inline small text-secondary">
          <li class="list-inline-item">Min: <strong><?= number_format($minW, 2) ?></strong> DOGE</li>
          <li class="list-inline-item">·</li>
          <li class="list-inline-item">Fee: <strong><?= number_format($fee, 2) ?></strong> DOGE</li>
          <li class="list-inline-item">·</li>
          <li class="list-inline-item">Daily Limit: <strong><?= number_format($daily, 0) ?></strong></li>
        </ul>
        <form method="post" autocomplete="off">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label">Receiving Wallet</label>
            <input class="form-control" name="wallet" value="<?= e($user['wallet']) ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Amount (DOGE)</label>
            <input class="form-control" type="number" name="amount" min="<?= e($minW) ?>"
                   max="<?= e($user['balance']) ?>" step="0.0001" required>
            <div class="form-text">Available: <strong class="text-warning"><?= number_format($user['balance'], 4) ?> DOGE</strong></div>
          </div>
          <button class="btn btn-warning fw-bold w-100" type="submit"
                  <?= ((float)$user['balance'] < $minW) ? 'disabled' : '' ?>>
            <i class="fas fa-paper-plane"></i> Request Withdrawal
          </button>
        </form>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="dm-panel">
        <h5><i class="fas fa-clock-rotate-left text-warning"></i> Recent Withdrawals</h5>
        <div class="table-responsive">
          <table class="table table-sm dm-table mb-0">
            <thead><tr><th>Date</th><th>Amount</th><th>Net</th><th>Status</th></tr></thead>
            <tbody>
              <?php if (!$withdrawals): ?>
                <tr><td colspan="4" class="text-center text-secondary small">No withdrawals yet.</td></tr>
              <?php else: foreach ($withdrawals as $w): ?>
                <tr>
                  <td class="small"><?= e(date('M d H:i', strtotime($w['created_at']))) ?></td>
                  <td><?= number_format($w['amount'], 4) ?></td>
                  <td><?= number_format($w['net_amount'], 4) ?></td>
                  <td>
                    <?php
                      $cls = ['pending'=>'warning','approved'=>'info','rejected'=>'danger','paid'=>'success'][$w['status']] ?? 'secondary';
                    ?>
                    <span class="badge bg-<?= $cls ?>"><?= e(ucfirst($w['status'])) ?></span>
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
