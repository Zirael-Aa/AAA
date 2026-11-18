<?php
session_start();
require __DIR__ . '/db.php';
require __DIR__ . '/admin_auth.php';

header('Content-Type: application/json; charset=utf-8');

if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Нет доступа']);
    exit;
}

$rid     = (int)($_POST['request_id'] ?? 0);
$comment = trim($_POST['admin_comment'] ?? '');
$status  = $_POST['status_after'] ?? 'done';

$allowed = ['new', 'in_progress', 'done', 'rejected'];
if (!in_array($status, $allowed, true)) $status = 'done';

if ($rid <= 0) {
    echo json_encode(['ok' => false, 'error' => 'Неверный ID заявки']);
    exit;
}

try {
    db()->prepare("
        UPDATE requests
        SET admin_comment = ?, status = ?, viewed = 0
        WHERE id = ?
    ")->execute([$comment, $status, $rid]);

    echo json_encode([
        'ok'      => true,
        'id'      => $rid,
        'comment' => $comment,
        'status'  => $status,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Ошибка БД: ' . $e->getMessage()]);
}