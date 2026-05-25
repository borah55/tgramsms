<?php if (!defined('DOGEMINE')) { http_response_code(403); exit; } ?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin — <?= e($page_title ?? 'DogeMine') ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="<?= e(SITE_URL) ?>/assets/css/style.css">
<link rel="stylesheet" href="<?= e(SITE_URL) ?>/assets/css/admin.css">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<meta name="site-url" content="<?= e(SITE_URL) ?>">
</head>
<body class="dm-body dm-admin">
<header class="dm-topbar dm-admin-topbar">
  <button class="btn btn-sm btn-outline-warning d-lg-none" id="sideToggle"><i class="fas fa-bars"></i></button>
  <a class="dm-brand" href="<?= e(SITE_URL) ?>/admin/index.php">
    <i class="fas fa-shield-halved text-warning"></i> Admin
  </a>
  <div class="ms-auto d-flex align-items-center gap-2">
    <a class="btn btn-sm btn-outline-warning" href="<?= e(SITE_URL) ?>/index.php" target="_blank">
      <i class="fas fa-up-right-from-square"></i> View Site
    </a>
    <span class="dm-wallet d-none d-md-inline">
      <i class="fas fa-user-shield"></i>
      <?= e(current_admin()['username'] ?? 'admin') ?>
    </span>
    <a class="btn btn-sm btn-outline-danger" href="<?= e(SITE_URL) ?>/admin/logout.php"><i class="fas fa-right-from-bracket"></i></a>
  </div>
</header>

<aside class="dm-sidebar">
  <div class="dm-side-brand"><i class="fa-brands fa-bitcoin text-warning"></i> <span>Admin Panel</span></div>
  <nav class="dm-side-nav">
    <?php
      $a = $admin_active ?? '';
      $link = function (string $href, string $icon, string $label, string $key) use ($a) {
          $cls = $a === $key ? 'active' : '';
          return '<a class="dm-side-link ' . $cls . '" href="' . e(SITE_URL . '/admin/' . $href) . '"><i class="fas ' . e($icon) . ' fa-fw"></i> ' . e($label) . '</a>';
      };
      echo $link('index.php',         'fa-gauge',           'Dashboard',     'dashboard');
      echo $link('users.php',         'fa-users',           'Users',         'users');
      echo $link('plans.php',         'fa-rocket',          'Mining Plans',  'plans');
      echo $link('deposits.php',      'fa-arrow-down',      'Deposits',      'deposits');
      echo $link('withdrawals.php',   'fa-arrow-up',        'Withdrawals',   'withdrawals');
      echo $link('tickets.php',       'fa-life-ring',       'Tickets',       'tickets');
      echo $link('announcements.php', 'fa-bullhorn',        'Announcements', 'announcements');
      echo $link('reports.php',       'fa-chart-line',      'Reports',       'reports');
      echo $link('logs.php',          'fa-shield',          'Security Logs', 'logs');
      echo $link('coinpayments.php',  'fa-credit-card',     'CoinPayments',  'coinpayments');
      echo $link('settings.php',      'fa-gear',            'Site Settings', 'settings');
    ?>
    <a class="dm-side-link text-danger" href="<?= e(SITE_URL) ?>/admin/logout.php"><i class="fas fa-right-from-bracket fa-fw"></i> Logout</a>
  </nav>
</aside>

<main class="dm-main">
  <?php if ($flash = flash_get('success')): ?><div class="alert alert-success"><?= e($flash) ?></div><?php endif; ?>
  <?php if ($flash = flash_get('error')): ?><div class="alert alert-danger"><?= e($flash) ?></div><?php endif; ?>
