<?php
require_once __DIR__ . '/includes/bootstrap.php';
$page_title = 'Privacy Policy';
include __DIR__ . '/includes/header.php';
?>
<section class="container py-5" style="max-width:880px;">
  <h2 class="mb-3">Privacy Policy</h2>
  <p class="text-secondary">Last updated: <?= e(date('F j, Y')) ?></p>

  <h5>1. Information We Collect</h5>
  <p>We collect only what is needed to provide the service: your DOGE wallet address, hashed PIN, IP address, and basic browser metadata for security logging.</p>

  <h5>2. How We Use Your Data</h5>
  <ul>
    <li>To authenticate your account and process payments.</li>
    <li>To prevent fraud and comply with legal obligations.</li>
    <li>To improve site reliability and performance.</li>
  </ul>

  <h5>3. Cookies</h5>
  <p>We use essential cookies (session, theme, remember-me, referral) and optionally Google Analytics if enabled by the operator.</p>

  <h5>4. Third-Party Services</h5>
  <p>Payments are processed by <a href="https://www.coinpayments.net/" rel="noopener noreferrer" target="_blank">CoinPayments</a>. Their privacy policy applies to deposit transactions.</p>

  <h5>5. Data Security</h5>
  <p>PINs are stored as bcrypt hashes. All form submissions are CSRF-protected and queries are parameterized.</p>

  <h5>6. Data Retention</h5>
  <p>Account, transaction, and security logs are retained for as long as your account is active or as required by law.</p>

  <h5>7. Your Rights</h5>
  <p>You may request data export or deletion by contacting <?= e(setting('site_email', 'support@dogemine.local')) ?>.</p>

  <h5>8. Contact</h5>
  <p>Questions? Email <?= e(setting('site_email', 'support@dogemine.local')) ?>.</p>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
