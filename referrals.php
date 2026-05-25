<?php
require_once __DIR__ . '/includes/bootstrap.php';
$user = require_login();

$link = SITE_URL . '/signup.php?ref=' . $user['referral_code'];

// Direct referrals
$ref1 = db()->prepare('SELECT id, wallet, created_at, total_deposit FROM users WHERE referred_by = ? ORDER BY id DESC LIMIT 50');
$ref1->execute([$user['id']]);
$referredUsers = $ref1->fetchAll();

// Counters
$cnt = db()->prepare('SELECT COUNT(*) FROM users WHERE referred_by = ?');
$cnt->execute([$user['id']]);
$total = (int)$cnt->fetchColumn();

$earnStmt = db()->prepare('SELECT COALESCE(SUM(amount),0) FROM referrals WHERE referrer_id = ?');
$earnStmt->execute([$user['id']]);
$totalEarned = (float)$earnStmt->fetchColumn();

$lvl2Stmt = db()->prepare(
    'SELECT COUNT(*) FROM users u WHERE u.referred_by IN (SELECT id FROM users WHERE referred_by = ?)'
);
$lvl2Stmt->execute([$user['id']]);
$lvl2 = (int)$lvl2Stmt->fetchColumn();

$active = 'referrals';
$page_title = 'Referrals';
include __DIR__ . '/includes/dashboard_header.php';
?>
<div class="container-fluid">
  <h3 class="mb-3">Refer & Earn</h3>

  <div class="row g-3 mb-3">
    <div class="col-md-3">
      <div class="dm-card"><div class="dm-card-icon"><i class="fas fa-people-arrows"></i></div>
        <div class="dm-card-label">Direct Referrals</div>
        <div class="dm-card-value"><?= number_format($total) ?></div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="dm-card"><div class="dm-card-icon"><i class="fas fa-network-wired"></i></div>
        <div class="dm-card-label">Level 2</div>
        <div class="dm-card-value"><?= number_format($lvl2) ?></div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="dm-card"><div class="dm-card-icon"><i class="fas fa-percent"></i></div>
        <div class="dm-card-label">Commission</div>
        <div class="dm-card-value"><?= e(setting('referral_percent', 15)) ?>%</div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="dm-card dm-glow"><div class="dm-card-icon"><i class="fas fa-coins"></i></div>
        <div class="dm-card-label">Total Earned</div>
        <div class="dm-card-value"><?= number_format($totalEarned, 4) ?></div>
        <div class="dm-card-foot">DOGE</div>
      </div>
    </div>
  </div>

  <div class="dm-panel">
    <h5><i class="fas fa-share-nodes text-warning"></i> Your Referral Link</h5>
    <div class="input-group">
      <input class="form-control" id="refLink" readonly value="<?= e($link) ?>">
      <button class="btn btn-warning" onclick="dmCopy('refLink')"><i class="fas fa-copy"></i> Copy</button>
    </div>
    <div class="mt-3 d-flex flex-wrap gap-2">
      <a class="btn btn-sm btn-outline-warning" target="_blank" rel="noopener noreferrer"
         href="https://twitter.com/intent/tweet?text=<?= rawurlencode('Mine Dogecoin in the cloud — get 100 GH/s free: ' . $link) ?>">
        <i class="fab fa-x-twitter"></i> Twitter
      </a>
      <a class="btn btn-sm btn-outline-warning" target="_blank" rel="noopener noreferrer"
         href="https://t.me/share/url?url=<?= rawurlencode($link) ?>&text=<?= rawurlencode('Free Dogecoin cloud mining!') ?>">
        <i class="fab fa-telegram"></i> Telegram
      </a>
      <a class="btn btn-sm btn-outline-warning" target="_blank" rel="noopener noreferrer"
         href="https://wa.me/?text=<?= rawurlencode('Free Dogecoin cloud mining: ' . $link) ?>">
        <i class="fab fa-whatsapp"></i> WhatsApp
      </a>
    </div>
  </div>

  <div class="dm-panel mt-3">
    <h5><i class="fas fa-users text-warning"></i> Your Referrals</h5>
    <div class="table-responsive">
      <table class="table table-sm dm-table mb-0">
        <thead><tr><th>#</th><th>Wallet</th><th>Joined</th><th>Total Deposit</th></tr></thead>
        <tbody>
          <?php if (!$referredUsers): ?>
            <tr><td colspan="4" class="text-center text-secondary small">No referrals yet. Share your link!</td></tr>
          <?php else: foreach ($referredUsers as $i => $r): ?>
            <tr>
              <td><?= $i + 1 ?></td>
              <td><?= e(substr($r['wallet'], 0, 10) . '…' . substr($r['wallet'], -6)) ?></td>
              <td class="small"><?= e(date('M d, Y', strtotime($r['created_at']))) ?></td>
              <td><?= number_format($r['total_deposit'], 4) ?> DOGE</td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php include __DIR__ . '/includes/dashboard_footer.php'; ?>
