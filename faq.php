<?php
require_once __DIR__ . '/includes/bootstrap.php';
$page_title = 'FAQ';
include __DIR__ . '/includes/header.php';

$faqs = [
  ['How do I sign up?', 'Click "Sign Up", enter your DOGE wallet and a 4-digit PIN. That\'s it.'],
  ['Is the 100 GH/s really free?', 'Yes. Every new user receives 100 GH/s for life — paying 0.2 DOGE every day forever.'],
  ['How is mining calculated?', 'You earn the sum of all active plan rewards per day, accrued every second.'],
  ['What is the minimum deposit?', 'Set by admin. Default: ' . setting('min_deposit', 5) . ' DOGE.'],
  ['What is the minimum withdrawal?', 'Set by admin. Default: ' . setting('min_withdraw', 50) . ' DOGE.'],
  ['How long do withdrawals take?', 'Up to 24 hours after admin approval.'],
  ['Do you charge any fees?', 'A small network fee of ' . setting('withdraw_fee', 1) . ' DOGE is deducted from withdrawals.'],
  ['Is my account secure?', 'Yes. PINs are bcrypt-hashed; we use CSRF protection, prepared statements, and IP-based lockouts.'],
  ['What payment methods do you accept?', 'CoinPayments — DOGE, BTC, ETH, LTC and many more cryptocurrencies.'],
  ['How does the referral program work?', 'You earn ' . setting('referral_percent', 15) . '% of every deposit your referrals make, for life.'],
];
?>
<section class="container py-5">
  <h2 class="text-center mb-4">Frequently Asked Questions</h2>
  <div class="accordion accordion-flush dm-accordion mx-auto" style="max-width:820px;" id="faqAcc">
    <?php foreach ($faqs as $i => $f): ?>
      <div class="accordion-item">
        <h2 class="accordion-header">
          <button class="accordion-button <?= $i ? 'collapsed' : '' ?>" data-bs-toggle="collapse" data-bs-target="#fq<?= $i ?>">
            <?= e($f[0]) ?>
          </button>
        </h2>
        <div id="fq<?= $i ?>" class="accordion-collapse collapse <?= $i ? '' : 'show' ?>" data-bs-parent="#faqAcc">
          <div class="accordion-body text-secondary"><?= e($f[1]) ?></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
