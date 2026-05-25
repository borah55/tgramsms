<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';
    $id     = (int)($_POST['id'] ?? 0);

    $pdo = db();
    $w = $pdo->prepare('SELECT * FROM withdrawals WHERE id=?');
    $w->execute([$id]);
    $row = $w->fetch();

    if (!$row) { flash_set('error', 'Withdrawal not found.'); redirect('admin/withdrawals.php'); }

    if ($action === 'approve') {
        $pdo->prepare('UPDATE withdrawals SET status="approved", processed_at=NOW(), admin_note=? WHERE id=?')
            ->execute([clean_input($_POST['note'] ?? ''), $id]);
        $pdo->prepare('INSERT INTO notifications (user_id, title, message, type) VALUES (?,?,?,?)')
            ->execute([$row['user_id'], 'Withdrawal Approved',
                'Your withdrawal of ' . number_format($row['amount'], 4) . ' DOGE has been approved.', 'success']);
        flash_set('success', 'Withdrawal approved.');
    } elseif ($action === 'paid') {
        $txn = clean_input((string)($_POST['txn'] ?? ''));
        $pdo->prepare('UPDATE withdrawals SET status="paid", txn_id=?, processed_at=NOW() WHERE id=?')
            ->execute([$txn, $id]);
        $pdo->prepare('UPDATE users SET total_withdraw = total_withdraw + ? WHERE id=?')
            ->execute([$row['amount'], $row['user_id']]);
        $pdo->prepare('INSERT INTO notifications (user_id, title, message, type) VALUES (?,?,?,?)')
            ->execute([$row['user_id'], 'Withdrawal Paid',
                number_format($row['net_amount'], 4) . ' DOGE has been sent. TXID: ' . $txn, 'success']);
        flash_set('success', 'Marked as paid.');
    } elseif ($action === 'reject') {
        // Refund balance
        $pdo->beginTransaction();
        try {
            $pdo->prepare('UPDATE withdrawals SET status="rejected", admin_note=?, processed_at=NOW() WHERE id=?')
                ->execute([clean_input($_POST['note'] ?? ''), $id]);
            $pdo->prepare('UPDATE users SET balance = balance + ? WHERE id=?')
                ->execute([$row['amount'], $row['user_id']]);
            $pdo->prepare('INSERT INTO notifications (user_id, title, message, type) VALUES (?,?,?,?)')
                ->execute([$row['user_id'], 'Withdrawal Rejected',
                    'Your withdrawal was rejected; ' . number_format($row['amount'], 4) . ' DOGE returned to balance.', 'warning']);
            $pdo->commit();
            flash_set('success', 'Withdrawal rejected and refunded.');
        } catch (Throwable $e) {
            $pdo->rollBack();
            flash_set('error', 'Failed to reject.');
        }
    }
    redirect('admin/withdrawals.php?status=' . ($_GET['status'] ?? 'all'));
}

$status = $_GET['status'] ?? 'all';
$where = 'WHERE 1';
$params = [];
if (in_array($status, ['pending','approved','paid','rejected'], true)) {
    $where .= ' AND w.status = ?';
    $params[] = $status;
}

$st = db()->prepare(
    "SELECT w.*, u.wallet AS account_wallet
     FROM withdrawals w JOIN users u ON u.id = w.user_id
     $where ORDER BY w.id DESC LIMIT 200"
);
$st->execute($params);
$rows = $st->fetchAll();

$page_title = 'Withdrawals';
$admin_active = 'withdrawals';
include __DIR__ . '/_header.php';
?>
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h3 class="mb-0">Withdrawals</h3>
    <div class="btn-group">
      <?php foreach (['all','pending','approved','paid','rejected'] as $s): ?>
        <a class="btn btn-sm <?= $status===$s?'btn-warning':'btn-outline-warning' ?>" href="?status=<?= $s ?>"><?= ucfirst($s) ?></a>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="dm-panel">
    <div class="table-responsive">
      <table class="table table-sm dm-table mb-0 align-middle">
        <thead><tr>
          <th>#</th><th>User</th><th>Amount</th><th>Net</th><th>Wallet</th><th>Status</th><th>Created</th><th></th>
        </tr></thead>
        <tbody>
          <?php if (!$rows): ?><tr><td colspan="8" class="text-center text-secondary small">No withdrawals.</td></tr>
          <?php else: foreach ($rows as $w):
            $cls = ['pending'=>'warning','approved'=>'info','paid'=>'success','rejected'=>'danger'][$w['status']] ?? 'secondary'; ?>
            <tr>
              <td><?= (int)$w['id'] ?></td>
              <td class="small"><?= e(substr($w['account_wallet'],0,10).'…'.substr($w['account_wallet'],-6)) ?></td>
              <td><?= number_format($w['amount'], 4) ?></td>
              <td><?= number_format($w['net_amount'], 4) ?></td>
              <td class="small"><?= e(substr($w['wallet'], 0, 12) . '…' . substr($w['wallet'], -6)) ?></td>
              <td><span class="badge bg-<?= $cls ?>"><?= e($w['status']) ?></span></td>
              <td class="small"><?= e(date('M d H:i', strtotime($w['created_at']))) ?></td>
              <td>
                <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#w<?= (int)$w['id'] ?>"><i class="fas fa-cog"></i></button>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php foreach ($rows as $w): ?>
  <div class="modal fade" id="w<?= (int)$w['id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content bg-dark text-light border border-secondary">
        <div class="modal-header border-secondary">
          <h5 class="modal-title">Withdrawal #<?= (int)$w['id'] ?></h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p><strong>User:</strong> <?= e($w['account_wallet']) ?></p>
          <p><strong>Amount:</strong> <?= number_format($w['amount'], 8) ?> DOGE
            (Net: <?= number_format($w['net_amount'], 8) ?>)</p>
          <p><strong>To Wallet:</strong> <code><?= e($w['wallet']) ?></code></p>
          <p><strong>Status:</strong> <?= e($w['status']) ?></p>

          <?php if (in_array($w['status'], ['pending','approved'], true)): ?>
            <hr class="border-secondary">
            <form method="post" class="row g-2">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int)$w['id'] ?>">
              <input type="hidden" name="action" value="approve">
              <div class="col-9"><input class="form-control form-control-sm" name="note" placeholder="Optional note"></div>
              <div class="col-3"><button class="btn btn-sm btn-info w-100">Approve</button></div>
            </form>
            <form method="post" class="row g-2 mt-2">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int)$w['id'] ?>">
              <input type="hidden" name="action" value="paid">
              <div class="col-9"><input class="form-control form-control-sm" name="txn" placeholder="On-chain TXID" required></div>
              <div class="col-3"><button class="btn btn-sm btn-success w-100">Mark Paid</button></div>
            </form>
            <form method="post" class="row g-2 mt-2">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int)$w['id'] ?>">
              <input type="hidden" name="action" value="reject">
              <div class="col-9"><input class="form-control form-control-sm" name="note" placeholder="Reason"></div>
              <div class="col-3"><button class="btn btn-sm btn-outline-danger w-100">Reject</button></div>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
<?php endforeach; ?>
<?php include __DIR__ . '/_footer.php'; ?>
