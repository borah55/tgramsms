<?php if (!defined('DOGEMINE')) { http_response_code(403); exit; }
$active = $active ?? '';
$item = function (string $href, string $icon, string $label, string $key) use ($active) {
    $a = $active === $key ? 'active' : '';
    return '<a class="dm-side-link ' . $a . '" href="' . e(SITE_URL . '/' . $href) . '"><i class="fas ' . e($icon) . ' fa-fw"></i> ' . e($label) . '</a>';
};
?>
<aside class="dm-sidebar">
  <div class="dm-side-brand">
    <i class="fa-brands fa-bitcoin text-warning"></i>
    <span><?= e(setting('site_name','DogeMine')) ?></span>
  </div>
  <nav class="dm-side-nav">
    <?= $item('dashboard.php', 'fa-gauge', 'Dashboard', 'dashboard') ?>
    <?= $item('plans.php',     'fa-rocket', 'Mining Plans', 'plans') ?>
    <?= $item('deposit.php',   'fa-arrow-down-to-bracket', 'Deposit', 'deposit') ?>
    <?= $item('withdraw.php',  'fa-arrow-up-from-bracket',  'Withdraw', 'withdraw') ?>
    <?= $item('referrals.php', 'fa-users', 'Referrals', 'referrals') ?>
    <?= $item('history.php',   'fa-clock-rotate-left', 'History', 'history') ?>
    <?= $item('support.php',   'fa-life-ring', 'Support', 'support') ?>
    <?= $item('settings.php',  'fa-gear', 'Settings', 'settings') ?>
    <a class="dm-side-link text-danger" href="<?= e(SITE_URL) ?>/logout.php"><i class="fas fa-right-from-bracket fa-fw"></i> Logout</a>
  </nav>
</aside>
