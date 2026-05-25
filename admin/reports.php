<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

if (isset($_GET['export'])) {
    $type = $_GET['export'];
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . preg_replace('/\W/', '_', $type) . '_' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');

    if ($type === 'deposits') {
        fputcsv($out, ['ID','UserID','Wallet','Currency','Amount','Status','Gateway','TXID','Created']);
        $st = db()->query(
            'SELECT d.id, d.user_id, u.wallet, d.currency, d.amount, d.status, d.gateway, d.txn_id, d.created_at
             FROM deposits d JOIN users u ON u.id=d.user_id ORDER BY d.id DESC'
        );
        foreach ($st as $r) fputcsv($out, $r);
    } elseif ($type === 'withdrawals') {
        fputcsv($out, ['ID','UserID','Account','Wallet','Amount','Net','Status','TXID','Created']);
        $st = db()->query(
            'SELECT w.id, w.user_id, u.wallet AS account, w.wallet, w.amount, w.net_amount, w.status, w.txn_id, w.created_at
             FROM withdrawals w JOIN users u ON u.id=w.user_id ORDER BY w.id DESC'
        );
        foreach ($st as $r) fputcsv($out, $r);
    } elseif ($type === 'users') {
        fputcsv($out, ['ID','Wallet','Power','Balance','TotalMined','TotalDeposit','TotalWithdraw','Referrals','Status','Created']);
        $st = db()->query(
            'SELECT u.id, u.wallet, u.mining_power, u.balance, u.total_mined, u.total_deposit, u.total_withdraw,
                    (SELECT COUNT(*) FROM users r WHERE r.referred_by=u.id) AS refs, u.status, u.created_at FROM users u ORDER BY u.id ASC'
        );
        foreach ($st as $r) fputcsv($out, $r);
    } elseif ($type === 'mining') {
        fputcsv($out, ['ID','UserID','Amount','MiningPower','Note','Time']);
        $st = db()->query('SELECT id, user_id, amount, mining_power, note, created_at FROM mining_logs ORDER BY id DESC LIMIT 50000');
        foreach ($st as $r) fputcsv($out, $r);
    }
    fclose($out);
    exit;
}

// Earnings report
$range = (int)($_GET['days'] ?? 30);
if (!in_array($range, [7,30,90,365], true)) $range = 30;

$daily = db()->prepare(
    'SELECT DATE(created_at) d, SUM(amount) total
     FROM deposits
     WHERE status="completed" AND created_at >= (NOW() - INTERVAL ? DAY)
     GROUP BY DATE(created_at) ORDER BY d ASC'
);
$daily->execute([$range]);
$dep = $daily->fetchAll();

$daily = db()->prepare(
    'SELECT DATE(created_at) d, SUM(amount) total
     FROM withdrawals
     WHERE status IN ("approved","paid") AND created_at >= (NOW() - INTERVAL ? DAY)
     GROUP BY DATE(created_at) ORDER BY d ASC'
);
$daily->execute([$range]);
$wit = $daily->fetchAll();

$labels = $depMap = $witMap = [];
for ($i = $range - 1; $i >= 0; $i--) {
    $key = date('Y-m-d', strtotime("-$i day"));
    $labels[] = $key;
    $depMap[$key] = 0;
    $witMap[$key] = 0;
}
foreach ($dep as $r) if (isset($depMap[$r['d']])) $depMap[$r['d']] = (float)$r['total'];
foreach ($wit as $r) if (isset($witMap[$r['d']])) $witMap[$r['d']] = (float)$r['total'];

$page_title = 'Reports';
$admin_active = 'reports';
include __DIR__ . '/_header.php';
?>
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center flex-wrap mb-3 gap-2">
    <h3 class="mb-0">Earnings Report</h3>
    <div class="btn-group">
      <?php foreach ([7, 30, 90, 365] as $d): ?>
        <a class="btn btn-sm <?= $range===$d?'btn-warning':'btn-outline-warning' ?>" href="?days=<?= $d ?>"><?= $d ?>d</a>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="dm-panel mb-3">
    <canvas id="repChart" height="80"></canvas>
  </div>

  <div class="dm-panel">
    <h5>Export CSV</h5>
    <a class="btn btn-outline-warning btn-sm" href="?export=users"><i class="fas fa-file-csv"></i> Users</a>
    <a class="btn btn-outline-warning btn-sm" href="?export=deposits"><i class="fas fa-file-csv"></i> Deposits</a>
    <a class="btn btn-outline-warning btn-sm" href="?export=withdrawals"><i class="fas fa-file-csv"></i> Withdrawals</a>
    <a class="btn btn-outline-warning btn-sm" href="?export=mining"><i class="fas fa-file-csv"></i> Mining Logs</a>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('repChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($labels) ?>,
    datasets: [
      { label: 'Deposits',    data: <?= json_encode(array_values($depMap)) ?>, backgroundColor: '#FFD83D' },
      { label: 'Withdrawals', data: <?= json_encode(array_values($witMap)) ?>, backgroundColor: '#ff5c7a' }
    ]
  },
  options: {
    plugins: { legend: { labels: { color: '#ddd' } } },
    scales: {
      x: { ticks: { color: '#aaa' }, grid: { color: '#222' }, stacked: true },
      y: { ticks: { color: '#aaa' }, grid: { color: '#222' }, stacked: true, beginAtZero: true }
    }
  }
});
</script>
<?php include __DIR__ . '/_footer.php'; ?>
