<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    foreach (['cp_merchant_id','cp_public_key','cp_private_key','cp_ipn_secret'] as $k) {
        if (array_key_exists($k, $_POST)) setting_set($k, (string)$_POST[$k]);
    }
    flash_set('success', 'CoinPayments settings saved.');
    redirect('admin/coinpayments.php');
}

$page_title = 'CoinPayments';
$admin_active = 'coinpayments';
include __DIR__ . '/_header.php';
?>
<div class="container-fluid">
  <h3 class="mb-3">CoinPayments Configuration</h3>
  <div class="dm-panel">
    <p class="text-secondary">
      Get your credentials at
      <a class="text-warning" href="https://www.coinpayments.net/account-keys" target="_blank" rel="noopener">CoinPayments Account &rarr; API Keys</a>.
      Set the IPN URL in CoinPayments to:
    </p>
    <pre class="dm-code"><?= e(SITE_URL) ?>/ipn/coinpayments.php</pre>

    <form method="post">
      <?= csrf_field() ?>
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Merchant ID</label>
          <input class="form-control" name="cp_merchant_id" value="<?= e(setting('cp_merchant_id')) ?>" autocomplete="off">
        </div>
        <div class="col-md-6">
          <label class="form-label">IPN Secret</label>
          <input class="form-control" name="cp_ipn_secret" value="<?= e(setting('cp_ipn_secret')) ?>" autocomplete="off">
        </div>
        <div class="col-md-6">
          <label class="form-label">Public Key</label>
          <input class="form-control" name="cp_public_key" value="<?= e(setting('cp_public_key')) ?>" autocomplete="off">
        </div>
        <div class="col-md-6">
          <label class="form-label">Private Key</label>
          <input class="form-control" name="cp_private_key" value="<?= e(setting('cp_private_key')) ?>" autocomplete="off">
        </div>
      </div>
      <button class="btn btn-warning mt-3 fw-bold"><i class="fas fa-save"></i> Save</button>
    </form>
  </div>
</div>
<?php include __DIR__ . '/_footer.php'; ?>
