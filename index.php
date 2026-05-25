<?php
require_once __DIR__ . '/includes/bootstrap.php';

$page_title = setting('site_name', 'DogeMine') . ' — ' . setting('site_tagline', 'Cloud Dogecoin Mining');

// Live stats (cheap aggregates)
try {
    $totalUsers   = (int)db()->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $totalMined   = (float)db()->query('SELECT COALESCE(SUM(total_mined),0) FROM users')->fetchColumn();
    $totalPaid    = (float)db()->query('SELECT COALESCE(SUM(amount),0) FROM withdrawals WHERE status IN ("approved","paid")')->fetchColumn();
    $totalHashRate= (float)db()->query('SELECT COALESCE(SUM(mining_speed),0) FROM user_plans WHERE status="active"')->fetchColumn();
} catch (Throwable $e) {
    $totalUsers = $totalMined = $totalPaid = $totalHashRate = 0;
}

// Active plans for pricing section
$plans = db()->query('SELECT * FROM plans WHERE status="active" ORDER BY price ASC')->fetchAll();

// Recent deposits & withdrawals (mask wallets)
$recentDeposits   = db()->query(
    'SELECT d.amount, d.currency, d.created_at, u.wallet
     FROM deposits d JOIN users u ON u.id=d.user_id
     WHERE d.status="completed" ORDER BY d.id DESC LIMIT 8'
)->fetchAll();
$recentWithdrawals = db()->query(
    'SELECT w.amount, w.created_at, u.wallet
     FROM withdrawals w JOIN users u ON u.id=w.user_id
     WHERE w.status IN ("approved","paid") ORDER BY w.id DESC LIMIT 8'
)->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<!-- HERO -->
<section class="dm-hero">
  <div class="dm-hero-bg"></div>
  <div class="container position-relative py-5">
    <div class="row align-items-center g-5">
      <div class="col-lg-7">
        <span class="badge dm-badge"><i class="fas fa-bolt"></i> Lifetime free 100 GH/s on signup</span>
        <h1 class="display-4 fw-bold mt-3">
          Mine <span class="text-warning">Dogecoin</span> in the cloud.
        </h1>
        <p class="lead text-secondary">
          Start earning DOGE in seconds. No hardware, no electricity, no setup.
          Just sign up with your wallet and watch the coins flow.
        </p>
        <div class="d-flex flex-wrap gap-2 mt-3">
          <a class="btn btn-warning btn-lg fw-bold" href="<?= e(SITE_URL) ?>/signup.php">
            <i class="fas fa-rocket"></i> Start Mining Free
          </a>
          <a class="btn btn-outline-warning btn-lg" href="#plans">View Plans</a>
        </div>
        <div class="d-flex gap-4 mt-4 flex-wrap">
          <div><i class="fas fa-shield-halved text-warning"></i> Secure</div>
          <div><i class="fas fa-bolt text-warning"></i> Fast Payouts</div>
          <div><i class="fas fa-headset text-warning"></i> 24/7 Support</div>
        </div>
      </div>
      <div class="col-lg-5">
        <div class="dm-hero-card">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="text-secondary small">LIVE NETWORK</span>
            <span class="dm-pulse"></span>
          </div>
          <h2 class="text-warning mb-0" id="liveHash"><?= number_format($totalHashRate, 0) ?> GH/s</h2>
          <p class="small text-secondary mb-3">Total active mining power</p>
          <div class="row text-center">
            <div class="col-6 border-end border-secondary">
              <div class="text-secondary small">Miners</div>
              <div class="fw-bold"><?= number_format($totalUsers) ?></div>
            </div>
            <div class="col-6">
              <div class="text-secondary small">Total Mined</div>
              <div class="fw-bold"><?= number_format($totalMined, 2) ?> DOGE</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- STATS -->
<section class="container py-5">
  <div class="row g-3 text-center">
    <div class="col-6 col-md-3">
      <div class="dm-stat">
        <i class="fas fa-users text-warning fa-2x"></i>
        <div class="dm-stat-value"><?= number_format($totalUsers) ?></div>
        <div class="dm-stat-label">Miners</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="dm-stat">
        <i class="fas fa-microchip text-warning fa-2x"></i>
        <div class="dm-stat-value"><?= number_format($totalHashRate, 0) ?></div>
        <div class="dm-stat-label">GH/s Hashrate</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="dm-stat">
        <i class="fa-brands fa-bitcoin text-warning fa-2x"></i>
        <div class="dm-stat-value"><?= number_format($totalMined, 2) ?></div>
        <div class="dm-stat-label">DOGE Mined</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="dm-stat">
        <i class="fas fa-coins text-warning fa-2x"></i>
        <div class="dm-stat-value"><?= number_format($totalPaid, 2) ?></div>
        <div class="dm-stat-label">DOGE Paid Out</div>
      </div>
    </div>
  </div>
</section>

<!-- HOW IT WORKS -->
<section class="container py-5" id="how">
  <h2 class="text-center mb-4">How It Works</h2>
  <div class="row g-4">
    <div class="col-md-4">
      <div class="dm-feature">
        <div class="dm-feature-num">1</div>
        <h5><i class="fas fa-user-plus text-warning"></i> Create Account</h5>
        <p class="text-secondary mb-0">Sign up with your Dogecoin wallet and a 4-digit PIN. No email needed.</p>
      </div>
    </div>
    <div class="col-md-4">
      <div class="dm-feature">
        <div class="dm-feature-num">2</div>
        <h5><i class="fas fa-rocket text-warning"></i> Get 100 GH/s Free</h5>
        <p class="text-secondary mb-0">Earn 0.2 DOGE daily for life. Upgrade for higher returns anytime.</p>
      </div>
    </div>
    <div class="col-md-4">
      <div class="dm-feature">
        <div class="dm-feature-num">3</div>
        <h5><i class="fas fa-money-bill-trend-up text-warning"></i> Withdraw Anytime</h5>
        <p class="text-secondary mb-0">Cash out to your wallet once you reach the minimum withdrawal.</p>
      </div>
    </div>
  </div>
</section>

<!-- PLANS -->
<section class="container py-5" id="plans">
  <h2 class="text-center mb-2">Mining Plans</h2>
  <p class="text-center text-secondary mb-4">Boost your hashrate with affordable upgrades.</p>
  <div class="row g-3">
    <?php foreach ($plans as $i => $p): ?>
      <div class="col-md-6 col-lg-<?= count($plans) >= 5 ? '' : '4' ?>" style="<?= count($plans) >= 5 ? 'flex: 0 0 20%; max-width: 20%;' : '' ?>">
        <div class="dm-plan <?= $i == 2 ? 'dm-plan-featured' : '' ?>">
          <?php if ($i == 2): ?><div class="dm-plan-flag">POPULAR</div><?php endif; ?>
          <h4 class="mb-1 text-warning"><?= e($p['name']) ?></h4>
          <div class="dm-plan-price">
            <span class="display-6 fw-bold"><?= number_format($p['price'], 2) ?></span>
            <small class="text-secondary"> DOGE</small>
          </div>
          <ul class="list-unstyled small my-3">
            <li><i class="fas fa-microchip text-warning fa-fw"></i> <?= fmt_power($p['mining_speed']) ?></li>
            <li><i class="fas fa-coins text-warning fa-fw"></i> <?= number_format($p['daily_reward'], 4) ?> DOGE / day</li>
            <li><i class="fas fa-calendar text-warning fa-fw"></i> <?= (int)$p['duration_days'] ?> days</li>
            <?php if ((float)$p['bonus_percent'] > 0): ?>
              <li><i class="fas fa-gift text-warning fa-fw"></i> +<?= (float)$p['bonus_percent'] ?>% bonus</li>
            <?php endif; ?>
          </ul>
          <a href="<?= e(SITE_URL) ?>/signup.php" class="btn btn-warning w-100 fw-bold">Get Started</a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- REFERRAL -->
<section class="dm-ref-section py-5">
  <div class="container">
    <div class="row align-items-center g-4">
      <div class="col-md-7">
        <h2 class="text-warning"><i class="fas fa-people-arrows"></i> Earn <?= e(setting('referral_percent', 15)) ?>% Referral Commission</h2>
        <p class="text-secondary">Invite friends and earn lifetime commissions on every deposit they make.</p>
        <ul class="list-unstyled">
          <li><i class="fas fa-check text-warning"></i> Lifetime commissions</li>
          <li><i class="fas fa-check text-warning"></i> Live referral dashboard</li>
          <li><i class="fas fa-check text-warning"></i> Multi-level statistics</li>
        </ul>
      </div>
      <div class="col-md-5 text-center">
        <i class="fas fa-share-nodes text-warning" style="font-size:8rem;opacity:.4;"></i>
      </div>
    </div>
  </div>
</section>

<!-- LIVE FEED -->
<section class="container py-5">
  <div class="row g-4">
    <div class="col-md-6">
      <h5><i class="fas fa-arrow-down-to-bracket text-warning"></i> Recent Deposits</h5>
      <div class="dm-feed" id="depositFeed">
        <?php if (!$recentDeposits): ?>
          <div class="text-secondary small">No recent deposits.</div>
        <?php else: foreach ($recentDeposits as $d): ?>
          <div class="dm-feed-row">
            <span class="dm-mask"><?= e(substr($d['wallet'],0,6).'…'.substr($d['wallet'],-4)) ?></span>
            <span class="text-warning fw-bold">+ <?= number_format($d['amount'], 4) ?> <?= e($d['currency']) ?></span>
            <span class="small text-secondary"><?= e(date('M d, H:i', strtotime($d['created_at']))) ?></span>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
    <div class="col-md-6">
      <h5><i class="fas fa-arrow-up-from-bracket text-warning"></i> Recent Withdrawals</h5>
      <div class="dm-feed" id="withdrawFeed">
        <?php if (!$recentWithdrawals): ?>
          <div class="text-secondary small">No recent withdrawals.</div>
        <?php else: foreach ($recentWithdrawals as $w): ?>
          <div class="dm-feed-row">
            <span class="dm-mask"><?= e(substr($w['wallet'],0,6).'…'.substr($w['wallet'],-4)) ?></span>
            <span class="text-warning fw-bold">- <?= number_format($w['amount'], 4) ?> DOGE</span>
            <span class="small text-secondary"><?= e(date('M d, H:i', strtotime($w['created_at']))) ?></span>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>
</section>

<!-- FAQ -->
<section class="container py-5" id="faq">
  <h2 class="text-center mb-4">Frequently Asked Questions</h2>
  <div class="accordion accordion-flush dm-accordion mx-auto" style="max-width:780px;" id="faqAcc">
    <?php
    $faqs = [
      ['Is signup really free?',          'Yes — every new user receives 100 GH/s of mining power for free, lifetime, paying 0.2 DOGE daily forever.'],
      ['What is the minimum withdrawal?', 'The minimum withdrawal is set in the admin panel. By default it is ' . setting('min_withdraw', 50) . ' DOGE.'],
      ['How fast are payouts?',           'Withdrawals are processed manually by admin within 24 hours after approval.'],
      ['How does the referral system work?','You earn ' . setting('referral_percent', 15) . '% on every deposit your referred users make. Commissions are credited automatically.'],
      ['Do I need a Dogecoin wallet?',    'Yes. You sign up with your existing Dogecoin wallet address. There is no email required.'],
    ];
    foreach ($faqs as $i => $f):
    ?>
      <div class="accordion-item">
        <h2 class="accordion-header">
          <button class="accordion-button <?= $i ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#faq<?= $i ?>">
            <?= e($f[0]) ?>
          </button>
        </h2>
        <div id="faq<?= $i ?>" class="accordion-collapse collapse <?= $i ? '' : 'show' ?>" data-bs-parent="#faqAcc">
          <div class="accordion-body text-secondary"><?= e($f[1]) ?></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- CTA -->
<section class="container py-5">
  <div class="dm-cta text-center p-5">
    <h2 class="fw-bold">Ready to start mining?</h2>
    <p class="text-secondary">Get 100 GH/s free instantly. No credit card. No KYC. Just your DOGE wallet.</p>
    <a class="btn btn-warning btn-lg fw-bold" href="<?= e(SITE_URL) ?>/signup.php">
      <i class="fas fa-bolt"></i> Create Free Account
    </a>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
