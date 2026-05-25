<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (current_user()) redirect('dashboard.php');

$error = null;
$ref   = trim((string)($_GET['ref'] ?? $_COOKIE['ref'] ?? ''));
if ($ref) setcookie('ref', $ref, time() + 60*60*24*30, '/');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $wallet = trim((string)($_POST['wallet'] ?? ''));
    $pin    = trim((string)($_POST['pin'] ?? ''));
    $pin2   = trim((string)($_POST['pin2'] ?? ''));
    $refIn  = trim((string)($_POST['ref'] ?? $ref));

    if ($pin !== $pin2) {
        $error = 'PINs do not match.';
    } else {
        [$ok, $msg] = signup_user($wallet, $pin, $refIn);
        if ($ok) {
            // Auto-login
            login_user($wallet, $pin, true);
            flash_set('success', 'Welcome! Your free 100 GH/s plan is active.');
            redirect('dashboard.php');
        } else {
            $error = $msg;
        }
    }
}

$page_title = 'Sign Up';
include __DIR__ . '/includes/header.php';
?>
<section class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-7 col-lg-6">
      <div class="dm-auth-card">
        <h3 class="text-center mb-1"><i class="fa-brands fa-bitcoin text-warning"></i> Create Account</h3>
        <p class="text-center text-secondary mb-4">Get 100 GH/s mining power free, lifetime.</p>

        <?php if ($error): ?>
          <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" autocomplete="off">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label">Dogecoin Wallet</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fas fa-wallet"></i></span>
              <input class="form-control" name="wallet" required pattern="[A-Za-z0-9]{25,40}"
                     value="<?= e($_POST['wallet'] ?? '') ?>"
                     placeholder="DXxxxxxxxxxxxxxxxxxxxxxxxxxxxx">
            </div>
            <div class="form-text">Your wallet is used as your unique login.</div>
          </div>
          <div class="row g-3 mb-3">
            <div class="col-6">
              <label class="form-label">4-Digit PIN</label>
              <input class="form-control" name="pin" type="password" inputmode="numeric"
                     pattern="\d{4}" maxlength="4" required placeholder="••••">
            </div>
            <div class="col-6">
              <label class="form-label">Confirm PIN</label>
              <input class="form-control" name="pin2" type="password" inputmode="numeric"
                     pattern="\d{4}" maxlength="4" required placeholder="••••">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Referral Code (optional)</label>
            <input class="form-control" name="ref" value="<?= e($_POST['ref'] ?? $ref) ?>">
          </div>
          <button class="btn btn-warning w-100 fw-bold" type="submit">
            <i class="fas fa-rocket"></i> Create Account
          </button>
          <p class="text-center mt-3 mb-0">
            <span class="text-secondary">Already have an account?</span>
            <a href="<?= e(SITE_URL) ?>/login.php">Login</a>
          </p>
          <p class="text-center small text-secondary mt-2 mb-0">
            By signing up, you agree to our
            <a href="<?= e(SITE_URL) ?>/terms.php">Terms</a> and
            <a href="<?= e(SITE_URL) ?>/privacy.php">Privacy</a>.
          </p>
        </form>
      </div>
    </div>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
