<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

$rows = db()->query('SELECT * FROM security_logs ORDER BY id DESC LIMIT 200')->fetchAll();
$page_title = 'Security Logs';
$admin_active = 'logs';
include __DIR__ . '/_header.php';
?>
<div class="container-fluid">
  <h3 class="mb-3">Security Logs</h3>
  <div class="dm-panel">
    <div class="table-responsive">
      <table class="table table-sm dm-table mb-0">
        <thead><tr><th>Time</th><th>User</th><th>Action</th><th>IP</th><th>UA</th></tr></thead>
        <tbody>
          <?php if (!$rows): ?>
            <tr><td colspan="5" class="text-center text-secondary small">No logs yet.</td></tr>
          <?php else: foreach ($rows as $r): ?>
            <tr>
              <td class="small"><?= e(date('Y-m-d H:i', strtotime($r['created_at']))) ?></td>
              <td><?= $r['user_id'] ? '#' . (int)$r['user_id'] : '<span class="text-secondary">—</span>' ?></td>
              <td><span class="badge bg-secondary"><?= e($r['action']) ?></span></td>
              <td class="small"><?= e($r['ip']) ?></td>
              <td class="small text-secondary"><?= e(substr($r['user_agent'] ?? '', 0, 64)) ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php include __DIR__ . '/_footer.php'; ?>
