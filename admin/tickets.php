<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

$openId = (int)($_GET['t'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';
    $tid    = (int)($_POST['ticket_id'] ?? 0);

    if ($action === 'reply' && $tid > 0) {
        $msg = clean_input((string)($_POST['message'] ?? ''));
        if ($msg !== '') {
            db()->prepare('INSERT INTO ticket_messages (ticket_id, from_admin, message) VALUES (?,1,?)')
                ->execute([$tid, $msg]);
            db()->prepare('UPDATE tickets SET status="answered" WHERE id=?')->execute([$tid]);
            $own = db()->prepare('SELECT user_id FROM tickets WHERE id=?');
            $own->execute([$tid]); $r = $own->fetch();
            if ($r) {
                db()->prepare('INSERT INTO notifications (user_id, title, message, type) VALUES (?,?,?,?)')
                    ->execute([(int)$r['user_id'], 'Support Reply', 'Admin replied to ticket #' . $tid, 'info']);
            }
            flash_set('success', 'Reply sent.');
        }
    } elseif ($action === 'close' && $tid > 0) {
        db()->prepare('UPDATE tickets SET status="closed" WHERE id=?')->execute([$tid]);
        flash_set('success', 'Ticket closed.');
    }
    redirect('admin/tickets.php' . ($tid ? '?t=' . $tid : ''));
}

$tickets = db()->query(
    'SELECT t.*, u.wallet FROM tickets t JOIN users u ON u.id=t.user_id ORDER BY t.id DESC LIMIT 100'
)->fetchAll();

$thread = $messages = null;
if ($openId) {
    $tStmt = db()->prepare('SELECT t.*, u.wallet FROM tickets t JOIN users u ON u.id=t.user_id WHERE t.id=?');
    $tStmt->execute([$openId]);
    $thread = $tStmt->fetch();
    if ($thread) {
        $mStmt = db()->prepare('SELECT * FROM ticket_messages WHERE ticket_id=? ORDER BY id ASC');
        $mStmt->execute([$openId]);
        $messages = $mStmt->fetchAll();
    }
}

$page_title = 'Tickets';
$admin_active = 'tickets';
include __DIR__ . '/_header.php';
?>
<div class="container-fluid">
  <h3 class="mb-3">Support Tickets</h3>
  <div class="row g-3">
    <div class="col-lg-5">
      <div class="dm-panel">
        <h5>All Tickets</h5>
        <?php if (!$tickets): ?>
          <div class="text-secondary small">No tickets.</div>
        <?php else: ?>
          <ul class="list-unstyled mb-0">
          <?php foreach ($tickets as $t):
            $cls = ['open'=>'warning','answered'=>'info','closed'=>'secondary'][$t['status']] ?? 'secondary'; ?>
            <li class="dm-ticket-row d-flex justify-content-between align-items-center">
              <a class="text-light text-decoration-none flex-grow-1" href="?t=<?= (int)$t['id'] ?>">
                #<?= (int)$t['id'] ?> · <?= e($t['subject']) ?>
                <div class="small text-secondary"><?= e(substr($t['wallet'],0,10).'…'.substr($t['wallet'],-6)) ?></div>
              </a>
              <span class="badge bg-<?= $cls ?>"><?= e($t['status']) ?></span>
            </li>
          <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>
    <div class="col-lg-7">
      <?php if ($thread): ?>
        <div class="dm-panel">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="mb-0">#<?= (int)$thread['id'] ?> · <?= e($thread['subject']) ?></h5>
            <form method="post" class="d-inline">
              <?= csrf_field() ?>
              <input type="hidden" name="ticket_id" value="<?= (int)$thread['id'] ?>">
              <input type="hidden" name="action" value="close">
              <button class="btn btn-sm btn-outline-secondary">Close</button>
            </form>
          </div>
          <div class="dm-thread">
            <?php foreach ($messages as $m): ?>
              <div class="dm-msg <?= $m['from_admin'] ? 'dm-msg-admin' : 'dm-msg-user' ?>">
                <div class="dm-msg-meta">
                  <strong><?= $m['from_admin'] ? 'Admin' : 'User' ?></strong>
                  <span class="small text-secondary"><?= e(date('M d, H:i', strtotime($m['created_at']))) ?></span>
                </div>
                <div><?= nl2br(e($m['message'])) ?></div>
              </div>
            <?php endforeach; ?>
          </div>
          <form method="post" class="mt-3">
            <?= csrf_field() ?>
            <input type="hidden" name="ticket_id" value="<?= (int)$thread['id'] ?>">
            <input type="hidden" name="action" value="reply">
            <textarea class="form-control mb-2" rows="3" name="message" placeholder="Reply…" required></textarea>
            <button class="btn btn-warning"><i class="fas fa-paper-plane"></i> Reply</button>
          </form>
        </div>
      <?php else: ?>
        <div class="dm-panel text-center text-secondary py-5">
          <i class="fas fa-inbox fa-3x text-warning mb-3"></i>
          <p>Select a ticket to view its conversation.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php include __DIR__ . '/_footer.php'; ?>
