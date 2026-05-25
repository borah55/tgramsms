<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($action === 'approve') {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $d = $pdo->prepare('SELECT * FROM deposits WHERE id=? FOR UPDATE');
            $d->execute([$id]);
            $dep = $d->fetch();
            if ($dep && $dep['status'] !== 'completed') {
                $pdo->prepare('UPDATE deposits SET status="completed", confirmed_at=NOW() WHERE id=?')->execute([$id]);
                $pdo->prepare('UPDATE users SET balance = balance + ?, total_deposit = total_deposit + ? WHERE id=?')
                    ->execute([$dep['amount'], $dep['amount'], $dep['user_id']]);
                $pdo->prepare('INSERT INTO notifications (user_id, title, message, type) VALUES (?,?,?,?)')
                    ->execute([$dep['user_id'], 'Deposit Confirmed',
                        'Your deposit of ' . number_format($dep['amount'], 4) . ' ' . $dep['currency'] . ' has been credited.', 'success']);
            }
            $pdo->commit();
            if ($dep) pay_referral_commission((int)$dep['user_id'], (float)$dep['amount']);
            flash_set('success', 'Deposit approved.');
        } catch (Throwable $e) {
            $pdo->rollBack();
            flash_set('error', 'Failed to approve.');
        }
    } elseif ($action === 'reject') {
        db()->prepare('UPDATE deposits SET status="failed" WHERE id=?')->execute([$id]);
        flash_set('success', 'Deposit rejected.');
    }
    redirect('admin/deposits.php?status=' . ($_GET['status'] ?? 'all'));
}

$status = $_GET['status'] ?? 'all';
$where  = "WHERE 1";
$params = [];
if (in_array($status, ['pending','completed','failed','cancelled'], true)) {
    $where .= ' AND d.status = ?';
    $params[] = $status;
}

$st = db()->prepare(
    "SELECT d.*, u.wallet
     FROM deposits d JOIN users u ON u.id = d.user_id
     $where ORDER BY d.id DESC LIMIT 200"
);
$st->execute($params);
$deps = $st->fetchAll();

$page_title = 'Deposits';
$admin_active = 'deposits';
include __DIR__ . '/_header.php';
?>
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h3 class="mb-0">Deposits</h3>
    <div class="btn-group">
      <?php foreach (['all','pending','completed','failed'] as $s): ?>
        <a class="btn btn-sm <?= $status===$s?'btn-warning':'btn-outline-warning' ?>" href="?status=<?= $s ?>"><?= ucfirst($s) ?></a>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="dm-panel">
    <div class="table-responsive">
      <table class="table table-sm dm-table mb-0 align-middle">
        <thead><tr>
          <th>#</th><th>User</th><th>Amount</th><th>Currency</th><th>Status</th><th>Gateway</th><th>TXID</th><th>Created</th><th></th>
        </tr></thead>
        <tbody>
          <?php if (!$deps): ?>
            <tr><td colspan="9" class="text-center text-secondary small">No deposits.</td></tr>
          <?php else: foreach ($deps as $d):
            $cls = ['pending'=>'warning','completed'=>'success','failed'=>'danger','cancelled'=>'secondary'][$d['status']] ?? 'secondary'; ?>
            <tr>
              <td><?= (int)$d['id'] ?></td>
              <td class="small"><?= e(substr($d['wallet'],0,10).'…'.substr($d['wallet'],-6)) ?></td>
              <td><?= number_format($d['amount'], 4) ?></td>
              <td><?= e($d['currency']) ?></td>
              <td><span class="badge bg-<?= $cls ?>"><?= e($d['status']) ?></span></td>
              <td class="small"><?= e($d['gateway']) ?></td>
              <td class="small"><?= e($d['txn_id'] ? substr($d['txn_id'], 0, 16) . '…' : '') ?></td>
              <td class="small"><?= e(date('M d H:i', strtotime($d['created_at']))) ?></td>
              <td>
                <?php if ($d['status'] === 'pending'): ?>
                  <form method="post" class="d-inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                    <input type="hidden" name="action" value="approve">
                    <button class="btn btn-sm btn-success"><i class="fas fa-check"></i></button>
                  </form>
                  <form method="post" class="d-inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                    <input type="hidden" name="action" value="reject">
                    <button class="btn btn-sm btn-outline-danger"><i class="fas fa-xmark"></i></button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php include __DIR__ . '/_footer.php'; ?>
