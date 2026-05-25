<?php if (!defined('DOGEMINE')) { http_response_code(403); exit; } ?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="<?= htmlspecialchars($_COOKIE['theme'] ?? 'dark') ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($page_title ?? 'Dashboard') ?> — <?= e(setting('site_name','DogeMine')) ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="<?= e(SITE_URL) ?>/assets/css/style.css">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<meta name="site-url" content="<?= e(SITE_URL) ?>">
</head>
<body class="dm-body dm-app">
<header class="dm-topbar">
  <button class="btn btn-sm btn-outline-warning d-lg-none" id="sideToggle" type="button"><i class="fas fa-bars"></i></button>
  <a class="dm-brand" href="<?= e(SITE_URL) ?>/dashboard.php">
    <i class="fa-brands fa-bitcoin text-warning"></i>
    <span><?= e(setting('site_name','DogeMine')) ?></span>
  </a>
  <div class="ms-auto d-flex align-items-center gap-2">
    <button class="btn btn-sm btn-outline-warning" id="themeToggle" type="button" title="Theme"><i class="fas fa-moon"></i></button>
    <div class="dropdown">
      <button class="btn btn-sm btn-outline-warning position-relative" data-bs-toggle="dropdown">
        <i class="fas fa-bell"></i>
        <span class="badge bg-danger position-absolute top-0 start-100 translate-middle" id="notifBadge" style="display:none;">0</span>
      </button>
      <div class="dropdown-menu dropdown-menu-end dm-notif-menu" id="notifMenu" style="min-width:280px;">
        <div class="px-3 py-2 text-secondary small">Loading…</div>
      </div>
    </div>
    <span class="dm-wallet d-none d-md-inline">
      <i class="fas fa-wallet"></i>
      <?php $u = current_user(); echo e(substr($u['wallet'], 0, 6) . '…' . substr($u['wallet'], -4)); ?>
    </span>
  </div>
</header>
<?php include __DIR__ . '/sidebar.php'; ?>
<main class="dm-main">
  <?php if ($flash = flash_get('success')): ?>
    <div class="alert alert-success"><?= e($flash) ?></div>
  <?php endif; ?>
  <?php if ($flash = flash_get('error')): ?>
    <div class="alert alert-danger"><?= e($flash) ?></div>
  <?php endif; ?>
