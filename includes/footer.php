<?php if (!defined('DOGEMINE')) { http_response_code(403); exit; } ?>
<footer class="dm-footer mt-5 py-4">
  <div class="container">
    <div class="row gy-4">
      <div class="col-md-4">
        <h5 class="text-warning"><i class="fa-brands fa-bitcoin"></i> <?= e(setting('site_name','DogeMine')) ?></h5>
        <p class="text-secondary"><?= e(setting('site_tagline','Mine Dogecoin in the cloud')) ?></p>
      </div>
      <div class="col-md-4">
        <h6>Quick Links</h6>
        <ul class="list-unstyled">
          <li><a href="<?= e(SITE_URL) ?>/index.php#plans">Mining Plans</a></li>
          <li><a href="<?= e(SITE_URL) ?>/faq.php">FAQ</a></li>
          <li><a href="<?= e(SITE_URL) ?>/terms.php">Terms</a></li>
          <li><a href="<?= e(SITE_URL) ?>/privacy.php">Privacy</a></li>
        </ul>
      </div>
      <div class="col-md-4">
        <h6>Support</h6>
        <p class="text-secondary mb-1"><i class="far fa-envelope"></i> <?= e(setting('site_email','support@dogemine.local')) ?></p>
        <?php if (current_user()): ?>
          <a class="btn btn-sm btn-outline-warning" href="<?= e(SITE_URL) ?>/support.php">Open Ticket</a>
        <?php endif; ?>
      </div>
    </div>
    <hr class="border-secondary">
    <div class="text-center text-secondary small">
      &copy; <?= date('Y') ?> <?= e(setting('site_name','DogeMine')) ?>. All rights reserved.
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(SITE_URL) ?>/assets/js/app.js"></script>
</body>
</html>
