<?php if (!defined('DOGEMINE')) { http_response_code(403); exit; } ?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="<?= htmlspecialchars($_COOKIE['theme'] ?? 'dark') ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($page_title ?? setting('site_name','DogeMine')) ?> — <?= e(setting('site_name','DogeMine')) ?></title>
<meta name="description" content="<?= e($page_description ?? setting('site_tagline','Mine Dogecoin in the cloud')) ?>">
<meta property="og:title" content="<?= e($page_title ?? setting('site_name','DogeMine')) ?>">
<meta property="og:description" content="<?= e($page_description ?? setting('site_tagline','Mine Dogecoin in the cloud')) ?>">
<meta property="og:type" content="website">
<meta name="site-url" content="<?= e(SITE_URL) ?>">

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="<?= e(SITE_URL) ?>/assets/css/style.css">

<?php if ($ga = setting('ga_tracking_id')): ?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($ga) ?>"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date()); gtag('config', '<?= e($ga) ?>');
</script>
<?php endif; ?>
</head>
<body class="dm-body">
<nav class="navbar navbar-expand-lg dm-navbar sticky-top">
  <div class="container">
    <a class="navbar-brand fw-bold" href="<?= e(SITE_URL) ?>/index.php">
      <i class="fa-brands fa-bitcoin text-warning"></i> <?= e(setting('site_name','DogeMine')) ?>
    </a>
    <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#mainNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link" href="<?= e(SITE_URL) ?>/index.php">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= e(SITE_URL) ?>/index.php#plans">Plans</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= e(SITE_URL) ?>/faq.php">FAQ</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= e(SITE_URL) ?>/index.php#how">How It Works</a></li>
      </ul>
      <ul class="navbar-nav align-items-lg-center gap-lg-2">
        <li class="nav-item">
          <button class="btn btn-sm btn-outline-warning" id="themeToggle" type="button" title="Toggle theme">
            <i class="fas fa-moon"></i>
          </button>
        </li>
        <?php if (current_user()): ?>
          <li class="nav-item"><a class="nav-link" href="<?= e(SITE_URL) ?>/dashboard.php"><i class="fas fa-gauge"></i> Dashboard</a></li>
          <li class="nav-item"><a class="btn btn-warning fw-bold" href="<?= e(SITE_URL) ?>/logout.php">Logout</a></li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="<?= e(SITE_URL) ?>/login.php">Login</a></li>
          <li class="nav-item"><a class="btn btn-warning fw-bold" href="<?= e(SITE_URL) ?>/signup.php">Sign Up</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<?php if ($flash = flash_get('success')): ?>
  <div class="container mt-3"><div class="alert alert-success"><?= e($flash) ?></div></div>
<?php endif; ?>
<?php if ($flash = flash_get('error')): ?>
  <div class="container mt-3"><div class="alert alert-danger"><?= e($flash) ?></div></div>
<?php endif; ?>
