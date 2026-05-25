<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (current_user()) redirect('dashboard.php');

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $wallet   = trim((string)($_POST['wallet'] ?? ''));
    $pin      = trim((string)($_POST['pin'] ?? ''));
    $remember = !empty($_POST['remember']);

    [$ok, $msg] = login_user($wallet, $pin, $remember);
    if ($ok) {
        flash_set('success', 'Welcome back!');
        redirect('dashboard.php');
    } else {
        $error = $msg;
    }
}

$page_title = 'Login';
include __DIR__ . '/includes/header.php';
?>
<section class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
      <div class="dm-auth-card">
        <h3 class="text-center mb-1"><i class="fa-brands fa-bitcoin text-warning"></i> Welcome Back</h3>
        <p class="text-center text-secondary mb-4">Login with your Dogecoin wallet and PIN.</p>

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
                     placeholder="DXxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
                     value="<?= e($_POST['wallet'] ?? '') ?>">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">4-Digit PIN</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fas fa-lock"></i></span>
              <input class="form-control" name="pin" type="password" inputmode="numeric"
                     pattern="\d{4}" maxlength="4" required placeholder="••••">
            </div>
          </div>
          <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" id="rem" name="remember" value="1">
            <label class="form-check-label" for="rem">Remember me</label>
          </div>
          <button class="btn btn-warning w-100 fw-bold" type="submit">
            <i class="fas fa-right-to-bracket"></i> Login
          </button>
          <p class="text-center mt-3 mb-0">
            <span class="text-secondary">No account?</span>
            <a href="<?= e(SITE_URL) ?>/signup.php">Sign up</a>
          </p>
        </form>
      </div>
    </div>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
