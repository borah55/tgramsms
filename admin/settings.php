<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $allowed = [
        'site_name','site_tagline','site_email',
        'signup_bonus_power','signup_bonus_daily','referral_percent',
        'min_deposit','min_withdraw','withdraw_fee','daily_withdraw_limit',
        'maintenance_mode','ga_tracking_id','fake_notifications',
    ];
    foreach ($allowed as $k) {
        if (array_key_exists($k, $_POST)) {
            setting_set($k, (string)$_POST[$k]);
        }
    }
    flash_set('success', 'Settings saved.');
    redirect('admin/settings.php');
}

$s = function (string $k, $d = '') { return setting($k, $d); };
$page_title = 'Site Settings';
$admin_active = 'settings';
include __DIR__ . '/_header.php';
?>
<div class="container-fluid">
  <h3 class="mb-3">Site Settings</h3>

  <form method="post">
    <?= csrf_field() ?>
    <div class="row g-3">
      <div class="col-md-6">
        <div class="dm-panel">
          <h5><i class="fas fa-globe text-warning"></i> Site</h5>
          <div class="mb-2"><label class="form-label">Site Name</label>
            <input class="form-control" name="site_name" value="<?= e($s('site_name')) ?>"></div>
          <div class="mb-2"><label class="form-label">Tagline</label>
            <input class="form-control" name="site_tagline" value="<?= e($s('site_tagline')) ?>"></div>
          <div class="mb-2"><label class="form-label">Support Email</label>
            <input class="form-control" name="site_email" value="<?= e($s('site_email')) ?>"></div>
          <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" id="maint" name="maintenance_mode"
                   value="1" <?= (int)$s('maintenance_mode')===1 ? 'checked' : '' ?>>
            <label class="form-check-label" for="maint">Maintenance Mode</label>
          </div>
          <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" id="fakeN" name="fake_notifications"
                   value="1" <?= (int)$s('fake_notifications')===1 ? 'checked' : '' ?>>
            <label class="form-check-label" for="fakeN">Live Earning Notifications (fake feed)</label>
          </div>
          <div class="mb-2"><label class="form-label">Google Analytics ID</label>
            <input class="form-control" name="ga_tracking_id" placeholder="G-XXXXXX" value="<?= e($s('ga_tracking_id')) ?>"></div>
        </div>
      </div>

      <div class="col-md-6">
        <div class="dm-panel">
          <h5><i class="fas fa-coins text-warning"></i> Mining & Rewards</h5>
          <div class="row g-2">
            <div class="col-md-6"><label class="form-label">Signup Bonus Power (GH/s)</label>
              <input class="form-control" type="number" name="signup_bonus_power" value="<?= e($s('signup_bonus_power')) ?>"></div>
            <div class="col-md-6"><label class="form-label">Signup Daily (DOGE)</label>
              <input class="form-control" type="number" step="0.0001" name="signup_bonus_daily" value="<?= e($s('signup_bonus_daily')) ?>"></div>
            <div class="col-md-6"><label class="form-label">Referral %</label>
              <input class="form-control" type="number" step="0.01" name="referral_percent" value="<?= e($s('referral_percent')) ?>"></div>
          </div>
        </div>

        <div class="dm-panel mt-3">
          <h5><i class="fas fa-money-bill-transfer text-warning"></i> Deposits & Withdrawals</h5>
          <div class="row g-2">
            <div class="col-md-6"><label class="form-label">Min Deposit (DOGE)</label>
              <input class="form-control" type="number" step="0.01" name="min_deposit" value="<?= e($s('min_deposit')) ?>"></div>
            <div class="col-md-6"><label class="form-label">Min Withdrawal (DOGE)</label>
              <input class="form-control" type="number" step="0.01" name="min_withdraw" value="<?= e($s('min_withdraw')) ?>"></div>
            <div class="col-md-6"><label class="form-label">Withdraw Fee (DOGE)</label>
              <input class="form-control" type="number" step="0.01" name="withdraw_fee" value="<?= e($s('withdraw_fee')) ?>"></div>
            <div class="col-md-6"><label class="form-label">Daily Withdraw Limit</label>
              <input class="form-control" type="number" step="0.01" name="daily_withdraw_limit" value="<?= e($s('daily_withdraw_limit')) ?>"></div>
          </div>
        </div>
      </div>
    </div>
    <button class="btn btn-warning fw-bold mt-3"><i class="fas fa-save"></i> Save Settings</button>
  </form>
</div>
<?php include __DIR__ . '/_footer.php'; ?>
