<?php
/** Notifications fetch + mark-as-read. */
require_once __DIR__ . '/../includes/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');

$user = current_user();
if (!$user) {
    http_response_code(401);
    echo json_encode(['ok' => false]);
    exit;
}

$action = $_REQUEST['action'] ?? 'list';

if ($action === 'mark_read') {
    require_csrf();
    db()->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0')
        ->execute([$user['id']]);
    echo json_encode(['ok' => true]);
    exit;
}

$stmt = db()->prepare('SELECT id, title, message, type, is_read, created_at
                       FROM notifications
                       WHERE (user_id = ? OR user_id IS NULL)
                       ORDER BY id DESC LIMIT 15');
$stmt->execute([$user['id']]);
$rows = $stmt->fetchAll();

$unread = 0;
foreach ($rows as $r) if (!$r['is_read']) $unread++;

echo json_encode([
    'ok' => true,
    'unread' => $unread,
    'items' => array_map(function ($n) {
        return [
            'id'      => (int)$n['id'],
            'title'   => $n['title'],
            'message' => $n['message'],
            'type'    => $n['type'],
            'is_read' => (int)$n['is_read'],
            'time'    => date('M d, H:i', strtotime($n['created_at'])),
        ];
    }, $rows),
]);
