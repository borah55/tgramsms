<?php
require_once __DIR__ . '/includes/bootstrap.php';
$page_title = 'Terms & Conditions';
include __DIR__ . '/includes/header.php';
?>
<section class="container py-5" style="max-width:880px;">
  <h2 class="mb-3">Terms & Conditions</h2>
  <p class="text-secondary">Last updated: <?= e(date('F j, Y')) ?></p>

  <h5>1. Service Description</h5>
  <p><?= e(setting('site_name', 'DogeMine')) ?> ("we", "us") provides a cloud Dogecoin mining simulation platform. By using the service, you accept these terms in full.</p>

  <h5>2. Eligibility</h5>
  <p>You must be at least 18 years old and legally able to enter into binding contracts in your jurisdiction.</p>

  <h5>3. Account & Wallet</h5>
  <p>Your Dogecoin wallet address is your account identity. You are solely responsible for keeping access to your wallet and 4-digit PIN.</p>

  <h5>4. Deposits & Withdrawals</h5>
  <p>Deposits are processed via CoinPayments. Withdrawals are processed manually by admin within 24 hours after approval. Minimum deposit: <?= e(setting('min_deposit', 5)) ?> DOGE. Minimum withdrawal: <?= e(setting('min_withdraw', 50)) ?> DOGE.</p>

  <h5>5. Mining Rewards</h5>
  <p>Daily rewards are estimates and may be modified by admin in response to market conditions. Lifetime free plans remain free for as long as the service operates.</p>

  <h5>6. Prohibited Activities</h5>
  <p>You may not abuse referral systems, automate signups, attempt to break security, or use the service for any unlawful purpose.</p>

  <h5>7. Disclaimer</h5>
  <p>Cryptocurrency markets are volatile. The service is provided "as is" without warranties. We are not responsible for losses caused by market movements or third-party gateways.</p>

  <h5>8. Termination</h5>
  <p>We may suspend or terminate accounts that violate these terms or engage in fraudulent activity.</p>

  <h5>9. Changes</h5>
  <p>We may update these terms at any time. Continued use after changes constitutes acceptance.</p>

  <h5>10. Contact</h5>
  <p>For questions, contact <?= e(setting('site_email', 'support@dogemine.local')) ?>.</p>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
