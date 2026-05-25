<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if (current_admin()) redirect('admin/index.php');

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $u = trim((string)($_POST['username'] ?? ''));
    $p = (string)($_POST['password'] ?? '');
    [$ok, $msg] = admin_login($u, $p);
    if ($ok) { redirect('admin/index.php'); }
    $error = $msg;
}
?><!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Login</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="<?= e(SITE_URL) ?>/assets/css/style.css">
</head>
<body class="dm-body">
<section class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
      <div class="dm-auth-card">
        <h3 class="text-center mb-1"><i class="fas fa-shield-halved text-warning"></i> Admin Login</h3>
        <p class="text-center text-secondary mb-4">Restricted area</p>
        <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
        <form method="post" autocomplete="off">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label">Username</label>
            <input class="form-control" name="username" required autofocus>
          </div>
          <div class="mb-3">
            <label class="form-label">Password</label>
            <input class="form-control" type="password" name="password" required>
          </div>
          <button class="btn btn-warning w-100 fw-bold"><i class="fas fa-right-to-bracket"></i> Login</button>
        </form>
      </div>
    </div>
  </div>
</section>
</body>
</html>
