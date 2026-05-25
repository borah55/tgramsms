<?php
require_once __DIR__ . '/includes/bootstrap.php';
$user = require_login();

$tab = $_GET['tab'] ?? 'mining';

$active = 'history';
$page_title = 'History';
include __DIR__ . '/includes/dashboard_header.php';
?>
<div class="container-fluid">
  <h3 class="mb-3">History</h3>

  <ul class="nav nav-pills dm-tabs mb-3">
    <li class="nav-item"><a class="nav-link <?= $tab=='mining'?'active':'' ?>" href="?tab=mining"><i class="fas fa-pickaxe"></i> Mining</a></li>
    <li class="nav-item"><a class="nav-link <?= $tab=='deposit'?'active':'' ?>" href="?tab=deposit"><i class="fas fa-arrow-down"></i> Deposits</a></li>
    <li class="nav-item"><a class="nav-link <?= $tab=='withdraw'?'active':'' ?>" href="?tab=withdraw"><i class="fas fa-arrow-up"></i> Withdrawals</a></li>
    <li class="nav-item"><a class="nav-link <?= $tab=='referral'?'active':'' ?>" href="?tab=referral"><i class="fas fa-people-arrows"></i> Referrals</a></li>
  </ul>

  <div class="dm-panel">
    <?php if ($tab === 'mining'):
      $s = db()->prepare('SELECT * FROM mining_logs WHERE user_id=? ORDER BY id DESC LIMIT 100');
      $s->execute([$user['id']]); $rows = $s->fetchAll();
    ?>
      <table class="table table-sm dm-table mb-0">
        <thead><tr><th>Time</th><th>Power</th><th>Earned</th><th>Note</th></tr></thead>
        <tbody>
          <?php if (!$rows): ?><tr><td colspan="4" class="text-center text-secondary small">No mining history yet.</td></tr>
          <?php else: foreach ($rows as $r): ?>
            <tr>
              <td class="small"><?= e(date('Y-m-d H:i', strtotime($r['created_at']))) ?></td>
              <td><?= fmt_power($r['mining_power']) ?></td>
              <td class="text-warning"><?= number_format($r['amount'], 8) ?></td>
              <td class="small text-secondary"><?= e($r['note'] ?? '') ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>

    <?php elseif ($tab === 'deposit'):
      $s = db()->prepare('SELECT * FROM deposits WHERE user_id=? ORDER BY id DESC LIMIT 100');
      $s->execute([$user['id']]); $rows = $s->fetchAll();
    ?>
      <table class="table table-sm dm-table mb-0">
        <thead><tr><th>Time</th><th>Amount</th><th>Currency</th><th>Status</th><th>TXID</th></tr></thead>
        <tbody>
          <?php if (!$rows): ?><tr><td colspan="5" class="text-center text-secondary small">No deposits.</td></tr>
          <?php else: foreach ($rows as $r):
            $cls = ['pending'=>'warning','completed'=>'success','failed'=>'danger','cancelled'=>'secondary'][$r['status']] ?? 'secondary'; ?>
            <tr>
              <td class="small"><?= e(date('Y-m-d H:i', strtotime($r['created_at']))) ?></td>
              <td><?= number_format($r['amount'], 4) ?></td>
              <td><?= e($r['currency']) ?></td>
              <td><span class="badge bg-<?= $cls ?>"><?= e(ucfirst($r['status'])) ?></span></td>
              <td class="small"><?= $r['txn_id'] ? e(substr($r['txn_id'], 0, 12) . '…') : '-' ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>

    <?php elseif ($tab === 'withdraw'):
      $s = db()->prepare('SELECT * FROM withdrawals WHERE user_id=? ORDER BY id DESC LIMIT 100');
      $s->execute([$user['id']]); $rows = $s->fetchAll();
    ?>
      <table class="table table-sm dm-table mb-0">
        <thead><tr><th>Time</th><th>Amount</th><th>Fee</th><th>Net</th><th>Status</th></tr></thead>
        <tbody>
          <?php if (!$rows): ?><tr><td colspan="5" class="text-center text-secondary small">No withdrawals.</td></tr>
          <?php else: foreach ($rows as $r):
            $cls = ['pending'=>'warning','approved'=>'info','rejected'=>'danger','paid'=>'success'][$r['status']] ?? 'secondary'; ?>
            <tr>
              <td class="small"><?= e(date('Y-m-d H:i', strtotime($r['created_at']))) ?></td>
              <td><?= number_format($r['amount'], 4) ?></td>
              <td><?= number_format($r['fee'], 4) ?></td>
              <td><?= number_format($r['net_amount'], 4) ?></td>
              <td><span class="badge bg-<?= $cls ?>"><?= e(ucfirst($r['status'])) ?></span></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>

    <?php else:
      $s = db()->prepare(
        'SELECT r.*, u.wallet FROM referrals r JOIN users u ON u.id=r.referred_id
         WHERE r.referrer_id=? ORDER BY r.id DESC LIMIT 100'
      );
      $s->execute([$user['id']]); $rows = $s->fetchAll();
    ?>
      <table class="table table-sm dm-table mb-0">
        <thead><tr><th>Time</th><th>From</th><th>Source</th><th>Amount</th></tr></thead>
        <tbody>
          <?php if (!$rows): ?><tr><td colspan="4" class="text-center text-secondary small">No referral earnings yet.</td></tr>
          <?php else: foreach ($rows as $r): ?>
            <tr>
              <td class="small"><?= e(date('Y-m-d H:i', strtotime($r['created_at']))) ?></td>
              <td class="small"><?= e(substr($r['wallet'],0,8) . '…' . substr($r['wallet'],-4)) ?></td>
              <td class="small text-secondary"><?= e($r['source']) ?></td>
              <td class="text-warning"><?= number_format($r['amount'], 4) ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>
<?php include __DIR__ . '/includes/dashboard_footer.php'; ?>
