<?php
require_once __DIR__ . '/includes/bootstrap.php';
$user = require_login();
update_user_mining((int)$user['id']);

// Refresh user
$user = current_user();

$activePlans  = user_active_plans((int)$user['id']);
$totalSpeed   = user_total_speed((int)$user['id']);
$totalDaily   = user_total_daily((int)$user['id']);
$perSecond    = $totalDaily / 86400;

$s = db()->prepare('SELECT COUNT(*) FROM users WHERE referred_by = ?');
$s->execute([$user['id']]);
$refCount = (int)$s->fetchColumn();

$active = 'dashboard';
$page_title = 'Dashboard';
include __DIR__ . '/includes/dashboard_header.php';
?>
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Dashboard</h3>
    <span class="text-secondary small">Last login: <?= e(date('M d, H:i', strtotime($user['last_login_at'] ?? 'now'))) ?></span>
  </div>

  <!-- Stats Cards -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="dm-card dm-glow">
        <div class="dm-card-icon"><i class="fas fa-coins"></i></div>
        <div class="dm-card-label">Balance</div>
        <div class="dm-card-value" data-counter
             data-base="<?= (float)$user['balance'] ?>"
             data-rate="<?= $perSecond ?>"
             data-decimals="8"><?= number_format($user['balance'], 8) ?></div>
        <div class="dm-card-foot">DOGE</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="dm-card">
        <div class="dm-card-icon"><i class="fas fa-microchip"></i></div>
        <div class="dm-card-label">Mining Power</div>
        <div class="dm-card-value"><?= number_format($totalSpeed, 0) ?></div>
        <div class="dm-card-foot">GH/s</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="dm-card">
        <div class="dm-card-icon"><i class="fas fa-pickaxe"></i></div>
        <div class="dm-card-label">Total Mined</div>
        <div class="dm-card-value"><?= number_format($user['total_mined'], 6) ?></div>
        <div class="dm-card-foot">DOGE</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="dm-card">
        <div class="dm-card-icon"><i class="fas fa-people-arrows"></i></div>
        <div class="dm-card-label">Referrals</div>
        <div class="dm-card-value"><?= number_format($refCount) ?></div>
        <div class="dm-card-foot"><?= number_format($user['referral_earnings'], 2) ?> DOGE earned</div>
      </div>
    </div>
  </div>

  <div class="row g-3">
    <!-- Mining Visualizer -->
    <div class="col-lg-8">
      <div class="dm-panel">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="mb-0"><i class="fas fa-bolt text-warning"></i> Live Mining</h5>
          <span class="dm-pulse-text"><span class="dm-pulse"></span> Active</span>
        </div>
        <div class="dm-mining-vis">
          <div class="dm-mining-progress" id="miningProgress" style="width: 12%"></div>
          <div class="dm-mining-info">
            <div>
              <span class="text-secondary small">Earning per second</span>
              <h4 class="text-warning mb-0"><?= number_format($perSecond, 8) ?> DOGE</h4>
            </div>
            <div class="text-end">
              <span class="text-secondary small">Daily Reward</span>
              <h4 class="mb-0"><?= number_format($totalDaily, 4) ?> DOGE</h4>
            </div>
          </div>
        </div>

        <h6 class="mt-4">Active Plans</h6>
        <?php if (!$activePlans): ?>
          <div class="text-secondary small">No active plans yet.</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-sm dm-table mb-0">
              <thead><tr>
                <th>Plan</th><th>Speed</th><th>Daily</th><th>Status</th><th>Expires</th>
              </tr></thead>
              <tbody>
              <?php foreach ($activePlans as $p):
                $name = $p['plan_id']
                  ? (db()->query('SELECT name FROM plans WHERE id=' . (int)$p['plan_id'])->fetchColumn() ?: 'Plan')
                  : 'Welcome Bonus';
              ?>
                <tr>
                  <td><?= e($name) ?></td>
                  <td><?= fmt_power($p['mining_speed']) ?></td>
                  <td><?= number_format($p['daily_reward'], 4) ?></td>
                  <td><span class="badge bg-success">Active</span></td>
                  <td><?= $p['is_lifetime'] ? '<span class="text-warning">Lifetime</span>' : e(date('M d, Y', strtotime($p['expires_at']))) ?></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="col-lg-4">
      <div class="dm-panel">
        <h5><i class="fas fa-bolt text-warning"></i> Quick Actions</h5>
        <div class="d-grid gap-2 mt-3">
          <a class="btn btn-warning fw-bold" href="<?= e(SITE_URL) ?>/plans.php">
            <i class="fas fa-rocket"></i> Upgrade Mining Plan
          </a>
          <a class="btn btn-outline-warning" href="<?= e(SITE_URL) ?>/deposit.php">
            <i class="fas fa-arrow-down-to-bracket"></i> Deposit DOGE
          </a>
          <a class="btn btn-outline-warning" href="<?= e(SITE_URL) ?>/withdraw.php">
            <i class="fas fa-arrow-up-from-bracket"></i> Withdraw
          </a>
          <a class="btn btn-outline-light" href="<?= e(SITE_URL) ?>/referrals.php">
            <i class="fas fa-share-nodes"></i> Share & Earn <?= e(setting('referral_percent', 15)) ?>%
          </a>
        </div>

        <hr class="border-secondary my-4">
        <h6>Your Referral Link</h6>
        <div class="input-group">
          <input class="form-control form-control-sm" id="refLink" readonly
                 value="<?= e(SITE_URL) ?>/signup.php?ref=<?= e($user['referral_code']) ?>">
          <button class="btn btn-warning btn-sm" type="button" onclick="dmCopy('refLink')">
            <i class="fas fa-copy"></i>
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<?php if ((int)setting('fake_notifications', 1) === 1): ?>
<div id="dmFakeFeed"></div>
<?php endif; ?>

<?php include __DIR__ . '/includes/dashboard_footer.php'; ?>
