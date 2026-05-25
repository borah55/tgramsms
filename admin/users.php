<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

$err = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';
    $uid    = (int)($_POST['uid'] ?? 0);

    if ($uid > 0) {
        if ($action === 'toggle') {
            db()->prepare(
                'UPDATE users SET status = CASE WHEN status="active" THEN "disabled" ELSE "active" END WHERE id=?'
            )->execute([$uid]);
            log_security(null, 'admin_user_toggle', ['uid' => $uid]);
            flash_set('success', 'User status updated.');
        } elseif ($action === 'ban') {
            db()->prepare('UPDATE users SET status="banned" WHERE id=?')->execute([$uid]);
            flash_set('success', 'User banned.');
        } elseif ($action === 'adjust_balance') {
            $amt  = (float)($_POST['amount'] ?? 0);
            $note = clean_input((string)($_POST['note'] ?? 'Admin adjustment'));
            db()->prepare('UPDATE users SET balance = GREATEST(0, balance + ?) WHERE id=?')
                ->execute([$amt, $uid]);
            db()->prepare('INSERT INTO notifications (user_id, title, message, type) VALUES (?,?,?,?)')
                ->execute([$uid, 'Balance Adjusted', $note . ': ' . number_format($amt, 4) . ' DOGE', $amt >= 0 ? 'success' : 'warning']);
            log_security(null, 'admin_balance_adjust', ['uid' => $uid, 'amount' => $amt]);
            flash_set('success', 'Balance adjusted.');
        } elseif ($action === 'reset_pin') {
            $newPin = sprintf('%04d', random_int(0, 9999));
            db()->prepare('UPDATE users SET pin_hash=? WHERE id=?')
                ->execute([password_hash($newPin, PASSWORD_BCRYPT), $uid]);
            flash_set('success', "New PIN: $newPin (share with the user securely)");
        } elseif ($action === 'add_power') {
            $power = (float)($_POST['power'] ?? 0);
            $daily = (float)($_POST['daily'] ?? 0);
            $days  = (int)($_POST['days']  ?? 0);
            $expires = $days > 0 ? date('Y-m-d H:i:s', strtotime("+$days days")) : '2099-12-31 00:00:00';
            db()->prepare(
                'INSERT INTO user_plans (user_id, plan_id, mining_speed, daily_reward, expires_at, is_lifetime, status)
                 VALUES (?,0,?,?,?,?,"active")'
            )->execute([$uid, $power, $daily, $expires, $days > 0 ? 0 : 1]);
            flash_set('success', 'Mining power granted.');
        }
    }
    redirect('admin/users.php');
}

// Filtering / search
$q       = trim($_GET['q'] ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));
$per     = 25;
$offset  = ($page - 1) * $per;

$where = '';
$params = [];
if ($q !== '') {
    $where = 'WHERE wallet LIKE ? OR referral_code LIKE ? OR id = ?';
    $params = ["%$q%", "%$q%", (int)$q];
}

$cnt = db()->prepare("SELECT COUNT(*) FROM users $where");
$cnt->execute($params);
$total = (int)$cnt->fetchColumn();

$st = db()->prepare("SELECT * FROM users $where ORDER BY id DESC LIMIT $per OFFSET $offset");
$st->execute($params);
$users = $st->fetchAll();

$pages = max(1, (int)ceil($total / $per));
$page_title = 'Users';
$admin_active = 'users';
include __DIR__ . '/_header.php';
?>
<div class="container-fluid">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h3 class="mb-0">Users <span class="text-secondary">(<?= number_format($total) ?>)</span></h3>
    <form class="d-flex gap-2" method="get">
      <input class="form-control form-control-sm" name="q" value="<?= e($q) ?>" placeholder="Search wallet / id / ref code">
      <button class="btn btn-sm btn-warning"><i class="fas fa-search"></i></button>
    </form>
  </div>

  <div class="dm-panel">
    <div class="table-responsive">
      <table class="table table-sm dm-table mb-0 align-middle">
        <thead><tr>
          <th>#</th><th>Wallet</th><th>Power</th><th>Balance</th><th>Mined</th><th>Status</th><th>Joined</th><th>Actions</th>
        </tr></thead>
        <tbody>
          <?php foreach ($users as $u): ?>
            <tr>
              <td><?= (int)$u['id'] ?></td>
              <td class="small"><?= e(substr($u['wallet'],0,10).'…'.substr($u['wallet'],-6)) ?></td>
              <td><?= number_format($u['mining_power'], 0) ?> GH/s</td>
              <td class="text-warning"><?= number_format($u['balance'], 4) ?></td>
              <td><?= number_format($u['total_mined'], 4) ?></td>
              <td>
                <?php $cls = ['active'=>'success','disabled'=>'warning','banned'=>'danger'][$u['status']]; ?>
                <span class="badge bg-<?= $cls ?>"><?= e(ucfirst($u['status'])) ?></span>
              </td>
              <td class="small"><?= e(date('M d, Y', strtotime($u['created_at']))) ?></td>
              <td>
                <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#m<?= (int)$u['id'] ?>">
                  <i class="fas fa-pen"></i>
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php if ($pages > 1): ?>
      <nav class="mt-3"><ul class="pagination pagination-sm mb-0">
        <?php for ($i = 1; $i <= min($pages, 12); $i++): ?>
          <li class="page-item <?= $i===$page?'active':'' ?>">
            <a class="page-link" href="?q=<?= e($q) ?>&page=<?= $i ?>"><?= $i ?></a>
          </li>
        <?php endfor; ?>
      </ul></nav>
    <?php endif; ?>
  </div>
</div>

<?php foreach ($users as $u): ?>
  <div class="modal fade" id="m<?= (int)$u['id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content bg-dark text-light border border-secondary">
        <div class="modal-header border-secondary">
          <h5 class="modal-title">User #<?= (int)$u['id'] ?></h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p class="mb-1"><strong>Wallet:</strong> <code><?= e($u['wallet']) ?></code></p>
          <p class="mb-3"><strong>Ref Code:</strong> <code><?= e($u['referral_code']) ?></code> ·
            <strong>Last IP:</strong> <code><?= e($u['last_ip']) ?></code></p>

          <div class="row g-3">
            <div class="col-md-6">
              <form method="post" class="d-flex gap-2">
                <?= csrf_field() ?>
                <input type="hidden" name="uid" value="<?= (int)$u['id'] ?>">
                <input type="hidden" name="action" value="toggle">
                <button class="btn btn-sm btn-warning w-100">
                  <i class="fas fa-toggle-on"></i> Toggle Active/Disabled
                </button>
              </form>
            </div>
            <div class="col-md-6">
              <form method="post" class="d-flex gap-2" onsubmit="return confirm('Ban this user?')">
                <?= csrf_field() ?>
                <input type="hidden" name="uid" value="<?= (int)$u['id'] ?>">
                <input type="hidden" name="action" value="ban">
                <button class="btn btn-sm btn-outline-danger w-100"><i class="fas fa-ban"></i> Ban User</button>
              </form>
            </div>
            <div class="col-md-6">
              <form method="post" class="d-flex gap-2" onsubmit="return confirm('Reset PIN?')">
                <?= csrf_field() ?>
                <input type="hidden" name="uid" value="<?= (int)$u['id'] ?>">
                <input type="hidden" name="action" value="reset_pin">
                <button class="btn btn-sm btn-outline-warning w-100"><i class="fas fa-key"></i> Reset PIN</button>
              </form>
            </div>
          </div>

          <hr class="border-secondary">

          <h6>Adjust Balance</h6>
          <form method="post" class="row g-2">
            <?= csrf_field() ?>
            <input type="hidden" name="uid" value="<?= (int)$u['id'] ?>">
            <input type="hidden" name="action" value="adjust_balance">
            <div class="col-md-4">
              <input class="form-control form-control-sm" type="number" step="0.0001" name="amount" placeholder="±DOGE" required>
            </div>
            <div class="col-md-6">
              <input class="form-control form-control-sm" name="note" placeholder="Reason" maxlength="100">
            </div>
            <div class="col-md-2"><button class="btn btn-sm btn-warning w-100">Apply</button></div>
          </form>

          <h6 class="mt-3">Grant Mining Power</h6>
          <form method="post" class="row g-2">
            <?= csrf_field() ?>
            <input type="hidden" name="uid" value="<?= (int)$u['id'] ?>">
            <input type="hidden" name="action" value="add_power">
            <div class="col-md-3"><input class="form-control form-control-sm" type="number" name="power" placeholder="GH/s" required></div>
            <div class="col-md-3"><input class="form-control form-control-sm" type="number" step="0.0001" name="daily" placeholder="DOGE/day" required></div>
            <div class="col-md-3"><input class="form-control form-control-sm" type="number" name="days" placeholder="Days (0=lifetime)"></div>
            <div class="col-md-3"><button class="btn btn-sm btn-warning w-100"><i class="fas fa-plus"></i> Grant</button></div>
          </form>
        </div>
      </div>
    </div>
  </div>
<?php endforeach; ?>
<?php include __DIR__ . '/_footer.php'; ?>
