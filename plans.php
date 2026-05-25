<?php
require_once __DIR__ . '/includes/bootstrap.php';
$user = require_login();
update_user_mining((int)$user['id']);
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $planId = (int)($_POST['plan_id'] ?? 0);
    [$ok, $msg] = purchase_plan((int)$user['id'], $planId);
    flash_set($ok ? 'success' : 'error', $msg);
    redirect('plans.php');
}

$plans = db()->query('SELECT * FROM plans WHERE status="active" ORDER BY price ASC')->fetchAll();
$active = 'plans';
$page_title = 'Mining Plans';
include __DIR__ . '/includes/dashboard_header.php';
?>
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Mining Plans</h3>
    <span class="text-secondary">Balance: <strong class="text-warning"><?= number_format($user['balance'], 4) ?> DOGE</strong></span>
  </div>

  <?php if (!$plans): ?>
    <div class="alert alert-warning">No plans available right now.</div>
  <?php endif; ?>

  <div class="row g-3">
    <?php foreach ($plans as $i => $p): ?>
      <div class="col-md-6 col-lg-4">
        <div class="dm-plan <?= $i == 1 ? 'dm-plan-featured' : '' ?>">
          <?php if ($i == 1): ?><div class="dm-plan-flag">POPULAR</div><?php endif; ?>
          <h4 class="mb-1 text-warning"><?= e($p['name']) ?></h4>
          <div class="dm-plan-price">
            <span class="display-6 fw-bold"><?= number_format($p['price'], 2) ?></span>
            <small class="text-secondary"> DOGE</small>
          </div>
          <ul class="list-unstyled small my-3">
            <li><i class="fas fa-microchip text-warning fa-fw"></i> <?= fmt_power($p['mining_speed']) ?></li>
            <li><i class="fas fa-coins text-warning fa-fw"></i> <?= number_format($p['daily_reward'], 4) ?> DOGE / day</li>
            <li><i class="fas fa-calendar text-warning fa-fw"></i> <?= (int)$p['duration_days'] ?> days</li>
            <?php if ((float)$p['bonus_percent'] > 0): ?>
              <li><i class="fas fa-gift text-warning fa-fw"></i> +<?= (float)$p['bonus_percent'] ?>% bonus</li>
            <?php endif; ?>
          </ul>
          <form method="post" onsubmit="return confirm('Activate <?= e($p['name']) ?> for <?= number_format($p['price'], 2) ?> DOGE?')">
            <?= csrf_field() ?>
            <input type="hidden" name="plan_id" value="<?= (int)$p['id'] ?>">
            <button class="btn btn-warning w-100 fw-bold" type="submit"
                    <?= ((float)$user['balance'] < (float)$p['price']) ? 'disabled' : '' ?>>
              <?= ((float)$user['balance'] < (float)$p['price']) ? 'Insufficient Balance' : 'Activate Plan' ?>
            </button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php include __DIR__ . '/includes/dashboard_footer.php'; ?>
