<?php
require_once __DIR__ . '/includes/bootstrap.php';
$user = require_login();

$error = $success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $subject = clean_input((string)($_POST['subject'] ?? ''));
        $message = clean_input((string)($_POST['message'] ?? ''));
        if (mb_strlen($subject) < 3 || mb_strlen($message) < 10) {
            $error = 'Subject and message are too short.';
        } else {
            db()->prepare('INSERT INTO tickets (user_id, subject) VALUES (?, ?)')
                ->execute([$user['id'], $subject]);
            $tid = (int)db()->lastInsertId();
            db()->prepare('INSERT INTO ticket_messages (ticket_id, from_admin, message) VALUES (?,0,?)')
                ->execute([$tid, $message]);
            $success = 'Ticket #' . $tid . ' created.';
        }
    } elseif ($action === 'reply') {
        $tid     = (int)($_POST['ticket_id'] ?? 0);
        $message = clean_input((string)($_POST['message'] ?? ''));
        $own = db()->prepare('SELECT id FROM tickets WHERE id=? AND user_id=?');
        $own->execute([$tid, $user['id']]);
        if (!$own->fetch() || mb_strlen($message) < 1) {
            $error = 'Invalid ticket or empty message.';
        } else {
            db()->prepare('INSERT INTO ticket_messages (ticket_id, from_admin, message) VALUES (?,0,?)')
                ->execute([$tid, $message]);
            db()->prepare('UPDATE tickets SET status="open" WHERE id=?')->execute([$tid]);
            $success = 'Reply sent.';
        }
    }
}

$tickets = db()->prepare('SELECT * FROM tickets WHERE user_id = ? ORDER BY id DESC LIMIT 50');
$tickets->execute([$user['id']]);
$tickets = $tickets->fetchAll();

$openTid = (int)($_GET['t'] ?? 0);
$thread = $openMessages = null;
if ($openTid) {
    $tStmt = db()->prepare('SELECT * FROM tickets WHERE id=? AND user_id=?');
    $tStmt->execute([$openTid, $user['id']]);
    $thread = $tStmt->fetch();
    if ($thread) {
        $mStmt = db()->prepare('SELECT * FROM ticket_messages WHERE ticket_id=? ORDER BY id ASC');
        $mStmt->execute([$openTid]);
        $openMessages = $mStmt->fetchAll();
    }
}

$active = 'support';
$page_title = 'Support';
include __DIR__ . '/includes/dashboard_header.php';
?>
<div class="container-fluid">
  <h3 class="mb-3">Support</h3>

  <?php if ($error):   ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

  <div class="row g-3">
    <div class="col-lg-5">
      <div class="dm-panel">
        <h5><i class="fas fa-pen text-warning"></i> Open New Ticket</h5>
        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="create">
          <div class="mb-2"><input class="form-control" name="subject" placeholder="Subject" required></div>
          <div class="mb-2"><textarea class="form-control" name="message" rows="5" placeholder="Describe your issue..." required></textarea></div>
          <button class="btn btn-warning fw-bold w-100"><i class="fas fa-paper-plane"></i> Submit</button>
        </form>
      </div>

      <div class="dm-panel mt-3">
        <h6>Your Tickets</h6>
        <ul class="list-unstyled mb-0">
          <?php if (!$tickets): ?>
            <li class="text-secondary small">No tickets yet.</li>
          <?php else: foreach ($tickets as $t):
            $cls = ['open'=>'warning','answered'=>'info','closed'=>'secondary'][$t['status']] ?? 'secondary'; ?>
            <li class="dm-ticket-row d-flex align-items-center justify-content-between">
              <a class="text-light text-decoration-none flex-grow-1" href="?t=<?= (int)$t['id'] ?>">
                #<?= (int)$t['id'] ?> · <?= e($t['subject']) ?>
              </a>
              <span class="badge bg-<?= $cls ?>"><?= e(ucfirst($t['status'])) ?></span>
            </li>
          <?php endforeach; endif; ?>
        </ul>
      </div>
    </div>

    <div class="col-lg-7">
      <?php if ($thread): ?>
        <div class="dm-panel">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">#<?= (int)$thread['id'] ?> · <?= e($thread['subject']) ?></h5>
            <span class="badge bg-info"><?= e(ucfirst($thread['status'])) ?></span>
          </div>
          <div class="dm-thread">
            <?php foreach ($openMessages as $m): ?>
              <div class="dm-msg <?= $m['from_admin'] ? 'dm-msg-admin' : 'dm-msg-user' ?>">
                <div class="dm-msg-meta">
                  <strong><?= $m['from_admin'] ? 'Support' : 'You' ?></strong>
                  <span class="text-secondary small"><?= e(date('M d, H:i', strtotime($m['created_at']))) ?></span>
                </div>
                <div><?= nl2br(e($m['message'])) ?></div>
              </div>
            <?php endforeach; ?>
          </div>
          <?php if ($thread['status'] !== 'closed'): ?>
            <form method="post" class="mt-3">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="reply">
              <input type="hidden" name="ticket_id" value="<?= (int)$thread['id'] ?>">
              <textarea class="form-control mb-2" name="message" rows="3" placeholder="Type a reply..." required></textarea>
              <button class="btn btn-warning"><i class="fas fa-paper-plane"></i> Reply</button>
            </form>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <div class="dm-panel text-center text-secondary py-5">
          <i class="fas fa-life-ring fa-3x text-warning mb-3"></i>
          <h5 class="text-light">Need help? We're here.</h5>
          <p class="mb-0">Open a ticket and our team will respond within 24 hours.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php include __DIR__ . '/includes/dashboard_footer.php'; ?>
