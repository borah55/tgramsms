<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $title = clean_input((string)($_POST['title'] ?? ''));
        $body  = clean_input((string)($_POST['body'] ?? ''));
        $isOn  = !empty($_POST['is_active']) ? 1 : 0;
        if ($title && $body) {
            db()->prepare('INSERT INTO announcements (title, body, is_active) VALUES (?,?,?)')
                ->execute([$title, $body, $isOn]);
            // Broadcast to all users
            db()->exec(
                'INSERT INTO notifications (user_id, title, message, type)
                 SELECT id, ' . db()->quote('Announcement: ' . $title) . ', ' . db()->quote($body) . ', "info"
                 FROM users WHERE status="active"'
            );
            flash_set('success', 'Announcement published.');
        }
    } elseif ($action === 'toggle') {
        db()->prepare('UPDATE announcements SET is_active = 1 - is_active WHERE id = ?')
            ->execute([(int)$_POST['id']]);
        flash_set('success', 'Toggled.');
    } elseif ($action === 'delete') {
        db()->prepare('DELETE FROM announcements WHERE id=?')->execute([(int)$_POST['id']]);
        flash_set('success', 'Deleted.');
    }
    redirect('admin/announcements.php');
}

$rows = db()->query('SELECT * FROM announcements ORDER BY id DESC')->fetchAll();
$page_title = 'Announcements';
$admin_active = 'announcements';
include __DIR__ . '/_header.php';
?>
<div class="container-fluid">
  <h3 class="mb-3">Announcements</h3>
  <div class="row g-3">
    <div class="col-lg-5">
      <div class="dm-panel">
        <h5><i class="fas fa-bullhorn text-warning"></i> Publish New</h5>
        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="create">
          <div class="mb-2"><input class="form-control" name="title" placeholder="Title" required></div>
          <div class="mb-2"><textarea class="form-control" name="body" rows="4" placeholder="Message" required></textarea></div>
          <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" id="isOn" name="is_active" value="1" checked>
            <label class="form-check-label" for="isOn">Active (visible to users)</label>
          </div>
          <button class="btn btn-warning fw-bold w-100"><i class="fas fa-paper-plane"></i> Publish &amp; Notify Users</button>
        </form>
      </div>
    </div>
    <div class="col-lg-7">
      <div class="dm-panel">
        <h5>Existing</h5>
        <?php if (!$rows): ?>
          <div class="text-secondary small">No announcements yet.</div>
        <?php else: ?>
          <ul class="list-unstyled mb-0">
            <?php foreach ($rows as $r): ?>
              <li class="border-bottom border-secondary py-2 d-flex justify-content-between align-items-start gap-2">
                <div>
                  <strong><?= e($r['title']) ?></strong>
                  <?php if (!$r['is_active']): ?><span class="badge bg-secondary">hidden</span><?php endif; ?>
                  <div class="small text-secondary"><?= e(date('M d, Y', strtotime($r['created_at']))) ?></div>
                  <div class="small mt-1"><?= nl2br(e(mb_substr($r['body'], 0, 200))) ?></div>
                </div>
                <div class="d-flex flex-column gap-1">
                  <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <input type="hidden" name="action" value="toggle">
                    <button class="btn btn-sm btn-outline-warning"><i class="fas fa-toggle-on"></i></button>
                  </form>
                  <form method="post" onsubmit="return confirm('Delete?')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <input type="hidden" name="action" value="delete">
                    <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                  </form>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/_footer.php'; ?>
