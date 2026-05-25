<?php
/**
 * DogeMine - Web Installer
 * Run once after upload, then delete this file.
 */
define('DOGEMINE', true);
session_start();

$step = (int)($_GET['step'] ?? 1);
$errors = [];
$ok = false;

$configFile = __DIR__ . '/includes/config.php';
$sampleFile = __DIR__ . '/includes/config.sample.php';
$sqlFile    = __DIR__ . '/database.sql';
$lockFile   = __DIR__ . '/includes/.installed';

if (file_exists($lockFile) && !isset($_GET['force'])) {
    $step = 99;
}

function check_requirements(): array {
    return [
        'PHP >= 7.4'        => version_compare(PHP_VERSION, '7.4', '>='),
        'PDO MySQL'         => extension_loaded('pdo_mysql'),
        'OpenSSL'           => extension_loaded('openssl'),
        'JSON'              => extension_loaded('json'),
        'cURL'              => extension_loaded('curl'),
        'mbstring'          => extension_loaded('mbstring'),
        'config writable'   => is_writable(__DIR__ . '/includes'),
    ];
}

if ($step === 2 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = trim($_POST['db_host'] ?? '');
    $name = trim($_POST['db_name'] ?? '');
    $user = trim($_POST['db_user'] ?? '');
    $pass = (string)($_POST['db_pass'] ?? '');
    $url  = rtrim(trim($_POST['site_url'] ?? ''), '/');
    $tz   = trim($_POST['timezone'] ?? 'UTC');

    if (!$host || !$name || !$user || !$url) {
        $errors[] = 'All fields except password are required.';
    }

    if (!$errors) {
        try {
            $pdo = new PDO("mysql:host=$host;dbname=$name;charset=utf8mb4", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
            // Run schema
            $sql = file_get_contents($sqlFile);
            $pdo->exec($sql);
        } catch (Throwable $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }

    if (!$errors) {
        $sample = file_get_contents($sampleFile);
        $key = bin2hex(random_bytes(24));
        $config = strtr($sample, [
            "define('DB_HOST', 'localhost');"  => "define('DB_HOST', " . var_export($host, true) . ");",
            "define('DB_NAME', 'dogemine');"   => "define('DB_NAME', " . var_export($name, true) . ");",
            "define('DB_USER', 'root');"       => "define('DB_USER', " . var_export($user, true) . ");",
            "define('DB_PASS', '');"           => "define('DB_PASS', " . var_export($pass, true) . ");",
            "define('SITE_URL', 'http://localhost/dogemine');" => "define('SITE_URL', " . var_export($url, true) . ");",
            "define('TIMEZONE', 'UTC');"       => "define('TIMEZONE', " . var_export($tz, true) . ");",
            "define('APP_KEY',          'change-me-to-a-long-random-string-32+chars');"
                => "define('APP_KEY',          " . var_export($key, true) . ");",
        ]);
        if (file_put_contents($configFile, $config) === false) {
            $errors[] = 'Cannot write includes/config.php — check directory permissions.';
        } else {
            file_put_contents($lockFile, date('c'));
            $_SESSION['installed'] = true;
            header('Location: install.php?step=3');
            exit;
        }
    }
}

if ($step === 3 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPass = (string)($_POST['admin_password'] ?? '');
    if (strlen($newPass) < 8) {
        $errors[] = 'Admin password must be at least 8 characters.';
    } else {
        require_once $configFile;
        try {
            $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $hash = password_hash($newPass, PASSWORD_BCRYPT);
            $pdo->prepare('UPDATE admins SET password_hash=? WHERE username="admin"')->execute([$hash]);
            $ok = true;
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }
}
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>DogeMine Installer</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<style>
  body { background:#111; color:#eee; }
  .install-card { max-width:720px; margin:3rem auto; background:#1a1a1a; border:1px solid #333; border-radius:14px; }
  .install-card .card-header { background:#FFD83D; color:#111; font-weight:700; }
  .form-control, .form-select { background:#222; color:#eee; border-color:#333; }
  .form-control:focus { background:#222; color:#eee; border-color:#FFD83D; box-shadow:none; }
  .step-pill { background:#222; border-radius:999px; padding:.4rem .9rem; color:#aaa; }
  .step-pill.active { background:#FFD83D; color:#111; font-weight:700; }
  .req-ok { color:#3DDC97; }
  .req-bad { color:#ff5c7a; }
</style>
</head>
<body>
<div class="container">
  <div class="text-center mt-4">
    <h2><i class="fa-brands fa-bitcoin text-warning"></i> DogeMine Installer</h2>
    <div class="d-flex gap-2 justify-content-center my-3">
      <span class="step-pill <?= $step===1?'active':''?>">1. Requirements</span>
      <span class="step-pill <?= $step===2?'active':''?>">2. Database</span>
      <span class="step-pill <?= $step===3?'active':''?>">3. Admin</span>
      <span class="step-pill <?= $step===4||$step===99?'active':''?>">4. Done</span>
    </div>
  </div>

  <div class="card install-card">
    <?php if ($errors): ?>
      <div class="card-body">
        <div class="alert alert-danger mb-0">
          <ul class="mb-0"><?php foreach ($errors as $e) echo '<li>' . htmlspecialchars($e) . '</li>'; ?></ul>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($step === 1): ?>
      <div class="card-header">Server Requirements</div>
      <div class="card-body">
        <table class="table table-dark table-striped">
          <tbody>
          <?php foreach (check_requirements() as $name => $passed): ?>
            <tr>
              <td><?= htmlspecialchars($name) ?></td>
              <td class="text-end"><?= $passed
                ? '<i class="fas fa-circle-check req-ok"></i> OK'
                : '<i class="fas fa-circle-xmark req-bad"></i> Missing' ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <a class="btn btn-warning" href="?step=2">Continue <i class="fas fa-arrow-right"></i></a>
      </div>

    <?php elseif ($step === 2): ?>
      <div class="card-header">Database Configuration</div>
      <div class="card-body">
        <form method="post">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">DB Host</label>
              <input class="form-control" name="db_host" value="<?= htmlspecialchars($_POST['db_host'] ?? 'localhost') ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">DB Name</label>
              <input class="form-control" name="db_name" value="<?= htmlspecialchars($_POST['db_name'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">DB User</label>
              <input class="form-control" name="db_user" value="<?= htmlspecialchars($_POST['db_user'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">DB Password</label>
              <input class="form-control" type="password" name="db_pass" value="">
            </div>
            <div class="col-md-8">
              <label class="form-label">Site URL (no trailing slash)</label>
              <input class="form-control" name="site_url"
                value="<?= htmlspecialchars($_POST['site_url'] ?? (($_SERVER['REQUEST_SCHEME'] ?? 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . rtrim(dirname($_SERVER['PHP_SELF']), '/'))) ?>"
                required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Timezone</label>
              <input class="form-control" name="timezone" value="<?= htmlspecialchars($_POST['timezone'] ?? 'UTC') ?>">
            </div>
          </div>
          <button class="btn btn-warning mt-4" type="submit">
            <i class="fas fa-database"></i> Install Database
          </button>
        </form>
      </div>

    <?php elseif ($step === 3): ?>
      <div class="card-header">Set Admin Password</div>
      <div class="card-body">
        <?php if ($ok): ?>
          <div class="alert alert-success">Admin password updated.</div>
          <a class="btn btn-warning" href="?step=4">Finish</a>
        <?php else: ?>
          <p>Default admin user: <code>admin</code></p>
          <form method="post">
            <label class="form-label">New Admin Password (min 8 chars)</label>
            <input type="password" name="admin_password" class="form-control" required minlength="8">
            <button class="btn btn-warning mt-3" type="submit">Save Password</button>
            <a class="btn btn-link text-muted mt-3" href="?step=4">Skip (keep admin123)</a>
          </form>
        <?php endif; ?>
      </div>

    <?php elseif ($step === 4): ?>
      <div class="card-header">Installation Complete</div>
      <div class="card-body">
        <p class="lead"><i class="fas fa-check-circle text-success"></i> DogeMine is now installed.</p>
        <ul>
          <li>Delete <code>install.php</code> from your server.</li>
          <li>Open the homepage and start mining.</li>
          <li>Configure CoinPayments credentials in the admin panel.</li>
        </ul>
        <a class="btn btn-warning" href="index.php">Go to Site</a>
        <a class="btn btn-outline-light" href="admin/login.php">Admin Login</a>
      </div>

    <?php else: ?>
      <div class="card-header">Already Installed</div>
      <div class="card-body">
        <p>DogeMine appears to be installed. Delete <code>install.php</code> for security.</p>
        <a class="btn btn-warning" href="index.php">Go to Site</a>
      </div>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
