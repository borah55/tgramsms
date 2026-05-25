<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

$pdo = db();
$totalUsers       = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$activeUsers      = (int)$pdo->query('SELECT COUNT(*) FROM users WHERE status="active"')->fetchColumn();
$disabledUsers    = (int)$pdo->query('SELECT COUNT(*) FROM users WHERE status<>"active"')->fetchColumn();
$totalMined       = (float)$pdo->query('SELECT COALESCE(SUM(total_mined),0) FROM users')->fetchColumn();
$totalDeposits    = (float)$pdo->query('SELECT COALESCE(SUM(amount),0) FROM deposits WHERE status="completed"')->fetchColumn();
$totalWithdrawals = (float)$pdo->query('SELECT COALESCE(SUM(amount),0) FROM withdrawals WHERE status IN ("approved","paid")')->fetchColumn();
$pendingDep       = (int)$pdo->query('SELECT COUNT(*) FROM deposits WHERE status="pending"')->fetchColumn();
$pendingWit       = (int)$pdo->query('SELECT COUNT(*) FROM withdrawals WHERE status="pending"')->fetchColumn();
$openTickets      = (int)$pdo->query('SELECT COUNT(*) FROM tickets WHERE status="open"')->fetchColumn();
$totalHashRate    = (float)$pdo->query('SELECT COALESCE(SUM(mining_speed),0) FROM user_plans WHERE status="active"')->fetchColumn();

// 7-day chart data
$signupsRows = $pdo->query(
    'SELECT DATE(created_at) d, COUNT(*) c FROM users WHERE created_at >= (NOW() - INTERVAL 7 DAY) GROUP BY DATE(created_at)'
)->fetchAll();
$series = [];
for ($i = 6; $i >= 0; $i--) {
    $key = date('Y-m-d', strtotime("-$i day"));
    $series[$key] = 0;
}
foreach ($signupsRows as $r) {
    if (isset($series[$r['d']])) $series[$r['d']] = (int)$r['c'];
}

$page_title = 'Dashboard';
$admin_active = 'dashboard';
include __DIR__ . '/_header.php';
?>
<div class="container-fluid">
  <h3 class="mb-3">Admin Dashboard</h3>

  <div class="row g-3 mb-3">
    <div class="col-6 col-md-3"><div class="dm-card"><div class="dm-card-icon"><i class="fas fa-users"></i></div>
      <div class="dm-card-label">Total Users</div><div class="dm-card-value"><?= number_format($totalUsers) ?></div>
      <div class="dm-card-foot text-success"><?= $activeUsers ?> active</div></div></div>
    <div class="col-6 col-md-3"><div class="dm-card"><div class="dm-card-icon"><i class="fas fa-microchip"></i></div>
      <div class="dm-card-label">Active Hashrate</div><div class="dm-card-value"><?= number_format($totalHashRate, 0) ?></div>
      <div class="dm-card-foot">GH/s</div></div></div>
    <div class="col-6 col-md-3"><div class="dm-card dm-glow"><div class="dm-card-icon"><i class="fas fa-arrow-down"></i></div>
      <div class="dm-card-label">Total Deposits</div><div class="dm-card-value"><?= number_format($totalDeposits, 2) ?></div>
      <div class="dm-card-foot">DOGE</div></div></div>
    <div class="col-6 col-md-3"><div class="dm-card"><div class="dm-card-icon"><i class="fas fa-arrow-up"></i></div>
      <div class="dm-card-label">Total Paid Out</div><div class="dm-card-value"><?= number_format($totalWithdrawals, 2) ?></div>
      <div class="dm-card-foot">DOGE</div></div></div>
  </div>

  <div class="row g-3">
    <div class="col-md-8">
      <div class="dm-panel">
        <h5><i class="fas fa-chart-line text-warning"></i> Signups (Last 7 Days)</h5>
        <canvas id="adminChart" height="80"></canvas>
      </div>
    </div>
    <div class="col-md-4">
      <div class="dm-panel">
        <h5><i class="fas fa-list-check text-warning"></i> Tasks</h5>
        <ul class="list-unstyled mb-0">
          <li class="d-flex justify-content-between border-bottom border-secondary py-2">
            <span>Pending Deposits</span>
            <a class="badge bg-warning text-dark" href="<?= e(SITE_URL) ?>/admin/deposits.php?status=pending"><?= $pendingDep ?></a>
          </li>
          <li class="d-flex justify-content-between border-bottom border-secondary py-2">
            <span>Pending Withdrawals</span>
            <a class="badge bg-danger" href="<?= e(SITE_URL) ?>/admin/withdrawals.php?status=pending"><?= $pendingWit ?></a>
          </li>
          <li class="d-flex justify-content-between border-bottom border-secondary py-2">
            <span>Open Tickets</span>
            <a class="badge bg-info" href="<?= e(SITE_URL) ?>/admin/tickets.php"><?= $openTickets ?></a>
          </li>
          <li class="d-flex justify-content-between py-2">
            <span>Total Mined</span>
            <span class="text-warning fw-bold"><?= number_format($totalMined, 2) ?> DOGE</span>
          </li>
        </ul>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
const labels = <?= json_encode(array_keys($series)) ?>;
const data   = <?= json_encode(array_values($series)) ?>;
const ctx = document.getElementById('adminChart');
if (ctx) new Chart(ctx, {
  type: 'line',
  data: {
    labels,
    datasets: [{
      label: 'Signups',
      data,
      borderColor: '#FFD83D',
      backgroundColor: 'rgba(255,216,61,.15)',
      tension: .35, fill: true, pointRadius: 4
    }]
  },
  options: {
    plugins: { legend: { labels: { color: '#ddd' } } },
    scales: {
      x: { ticks: { color: '#aaa' }, grid: { color: '#222' } },
      y: { ticks: { color: '#aaa' }, grid: { color: '#222' }, beginAtZero: true }
    }
  }
});
</script>
<?php include __DIR__ . '/_footer.php'; ?>
