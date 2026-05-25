<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id    = (int)($_POST['id'] ?? 0);
        $name  = clean_input((string)($_POST['name'] ?? ''));
        $speed = (float)($_POST['mining_speed'] ?? 0);
        $price = (float)($_POST['price'] ?? 0);
        $daily = (float)($_POST['daily_reward'] ?? 0);
        $dur   = (int)($_POST['duration_days'] ?? 30);
        $bonus = (float)($_POST['bonus_percent'] ?? 0);
        $stat  = $_POST['status'] === 'active' ? 'active' : 'inactive';

        if ($id > 0) {
            db()->prepare(
                'UPDATE plans SET name=?,mining_speed=?,price=?,daily_reward=?,duration_days=?,bonus_percent=?,status=? WHERE id=?'
            )->execute([$name, $speed, $price, $daily, $dur, $bonus, $stat, $id]);
        } else {
            db()->prepare(
                'INSERT INTO plans (name,mining_speed,price,daily_reward,duration_days,bonus_percent,status) VALUES (?,?,?,?,?,?,?)'
            )->execute([$name, $speed, $price, $daily, $dur, $bonus, $stat]);
        }
        flash_set('success', 'Plan saved.');
    } elseif ($action === 'delete') {
        db()->prepare('DELETE FROM plans WHERE id=?')->execute([(int)$_POST['id']]);
        flash_set('success', 'Plan deleted.');
    }
    redirect('admin/plans.php');
}

$plans = db()->query('SELECT * FROM plans ORDER BY price ASC')->fetchAll();
$page_title = 'Mining Plans';
$admin_active = 'plans';
include __DIR__ . '/_header.php';
?>
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Mining Plans</h3>
    <button class="btn btn-warning fw-bold" data-bs-toggle="modal" data-bs-target="#newPlan"><i class="fas fa-plus"></i> New Plan</button>
  </div>

  <div class="dm-panel">
    <div class="table-responsive">
      <table class="table table-sm dm-table mb-0 align-middle">
        <thead><tr>
          <th>#</th><th>Name</th><th>Speed</th><th>Price</th><th>Daily</th><th>Days</th><th>Bonus</th><th>Status</th><th></th>
        </tr></thead>
        <tbody>
          <?php foreach ($plans as $p): ?>
            <tr>
              <td><?= (int)$p['id'] ?></td>
              <td><?= e($p['name']) ?></td>
              <td><?= number_format($p['mining_speed']) ?> GH/s</td>
              <td><?= number_format($p['price'], 2) ?></td>
              <td><?= number_format($p['daily_reward'], 4) ?></td>
              <td><?= (int)$p['duration_days'] ?></td>
              <td><?= number_format($p['bonus_percent'], 1) ?>%</td>
              <td>
                <span class="badge bg-<?= $p['status']==='active'?'success':'secondary' ?>"><?= e($p['status']) ?></span>
              </td>
              <td>
                <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#p<?= (int)$p['id'] ?>"><i class="fas fa-pen"></i></button>
                <form method="post" class="d-inline" onsubmit="return confirm('Delete plan?')">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                  <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php
$renderPlanModal = function(string $id, ?array $p) {
  $p = $p ?? ['id'=>0,'name'=>'','mining_speed'=>0,'price'=>0,'daily_reward'=>0,'duration_days'=>30,'bonus_percent'=>0,'status'=>'active'];
  ?>
  <div class="modal fade" id="<?= e($id) ?>" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content bg-dark text-light border border-secondary">
        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="save">
          <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
          <div class="modal-header border-secondary">
            <h5 class="modal-title"><?= $p['id'] ? 'Edit Plan' : 'New Plan' ?></h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-2"><label class="form-label">Name</label>
              <input class="form-control" name="name" required value="<?= e($p['name']) ?>"></div>
            <div class="row g-2 mb-2">
              <div class="col-6"><label class="form-label">Mining Speed (GH/s)</label>
                <input class="form-control" type="number" name="mining_speed" min="0" required value="<?= e($p['mining_speed']) ?>"></div>
              <div class="col-6"><label class="form-label">Price (DOGE)</label>
                <input class="form-control" type="number" step="0.01" name="price" min="0" required value="<?= e($p['price']) ?>"></div>
            </div>
            <div class="row g-2 mb-2">
              <div class="col-6"><label class="form-label">Daily Reward</label>
                <input class="form-control" type="number" step="0.0001" name="daily_reward" min="0" required value="<?= e($p['daily_reward']) ?>"></div>
              <div class="col-6"><label class="form-label">Duration (days)</label>
                <input class="form-control" type="number" name="duration_days" min="1" required value="<?= e($p['duration_days']) ?>"></div>
            </div>
            <div class="row g-2 mb-2">
              <div class="col-6"><label class="form-label">Bonus %</label>
                <input class="form-control" type="number" step="0.1" name="bonus_percent" min="0" value="<?= e($p['bonus_percent']) ?>"></div>
              <div class="col-6"><label class="form-label">Status</label>
                <select class="form-select" name="status">
                  <option value="active" <?= $p['status']==='active'?'selected':'' ?>>Active</option>
                  <option value="inactive" <?= $p['status']==='inactive'?'selected':'' ?>>Inactive</option>
                </select></div>
            </div>
          </div>
          <div class="modal-footer border-secondary">
            <button class="btn btn-warning"><i class="fas fa-save"></i> Save</button>
          </div>
        </form>
      </div>
    </div>
  </div>
<?php };
$renderPlanModal('newPlan', null);
foreach ($plans as $p) $renderPlanModal('p' . (int)$p['id'], $p);
?>
<?php include __DIR__ . '/_footer.php'; ?>
