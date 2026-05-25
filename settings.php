<?php
require_once __DIR__ . '/includes/bootstrap.php';
$user = require_login();

$error = $success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'change_pin') {
        $cur  = (string)($_POST['current_pin'] ?? '');
        $new  = (string)($_POST['new_pin'] ?? '');
        $new2 = (string)($_POST['new_pin2'] ?? '');

        if (!password_verify($cur, $user['pin_hash'])) {
            $error = 'Current PIN is incorrect.';
        } elseif (!valid_pin($new)) {
            $error = 'New PIN must be exactly 4 digits.';
        } elseif ($new !== $new2) {
            $error = 'New PINs do not match.';
        } else {
            db()->prepare('UPDATE users SET pin_hash = ? WHERE id = ?')
                ->execute([password_hash($new, PASSWORD_BCRYPT), $user['id']]);
            log_security((int)$user['id'], 'pin_changed');
            $success = 'PIN updated.';
        }
    }
}

$active = 'settings';
$page_title = 'Settings';
include __DIR__ . '/includes/dashboard_header.php';
?>
<div class="container-fluid">
  <h3 class="mb-3">Settings</h3>

  <?php if ($error):   ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

  <div class="row g-3">
    <div class="col-lg-6">
      <div class="dm-panel">
        <h5><i class="fas fa-user text-warning"></i> Account</h5>
        <table class="table dm-table mb-0">
          <tbody>
            <tr><th class="text-secondary">Wallet</th><td><?= e($user['wallet']) ?></td></tr>
            <tr><th class="text-secondary">Referral Code</th><td><?= e($user['referral_code']) ?></td></tr>
            <tr><th class="text-secondary">Joined</th><td><?= e(date('M d, Y', strtotime($user['created_at']))) ?></td></tr>
            <tr><th class="text-secondary">Last Login IP</th><td><?= e($user['last_ip']) ?></td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="dm-panel">
        <h5><i class="fas fa-key text-warning"></i> Change PIN</h5>
        <form method="post" autocomplete="off">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="change_pin">
          <div class="mb-2">
            <label class="form-label">Current PIN</label>
            <input class="form-control" type="password" name="current_pin"
                   inputmode="numeric" pattern="\d{4}" maxlength="4" required>
          </div>
          <div class="mb-2">
            <label class="form-label">New PIN</label>
            <input class="form-control" type="password" name="new_pin"
                   inputmode="numeric" pattern="\d{4}" maxlength="4" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Confirm New PIN</label>
            <input class="form-control" type="password" name="new_pin2"
                   inputmode="numeric" pattern="\d{4}" maxlength="4" required>
          </div>
          <button class="btn btn-warning fw-bold"><i class="fas fa-save"></i> Update PIN</button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/includes/dashboard_footer.php'; ?>
