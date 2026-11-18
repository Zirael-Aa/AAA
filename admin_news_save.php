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

$id         = (int)($_POST['id'] ?? 0);
$title      = trim($_POST['title'] ?? '');
$preview    = trim($_POST['preview'] ?? '');
$fullText   = trim($_POST['full_text'] ?? '');
$image      = trim($_POST['image'] ?? '');
$newsDate   = trim($_POST['news_date'] ?? date('Y-m-d'));
$sortOrder  = (int)($_POST['sort_order'] ?? 0);
$isActive   = (int)($_POST['is_active'] ?? 1) === 1 ? 1 : 0;

if ($title === '') {
    echo json_encode(['ok' => false, 'error' => 'Заголовок обязателен']);
    exit;
}

// Проверим дату
$ts = strtotime($newsDate);
if ($ts === false) {
    echo json_encode(['ok' => false, 'error' => 'Некорректная дата']);
    exit;
}
$newsDate = date('Y-m-d', $ts);

try {
    if ($id > 0) {
        $stmt = db()->prepare("
            UPDATE news
            SET title = ?, preview = ?, full_text = ?, image = ?,
                news_date = ?, sort_order = ?, is_active = ?
            WHERE id = ?
        ");
        $stmt->execute([$title, $preview, $fullText, $image, $newsDate, $sortOrder, $isActive, $id]);
        $newId = $id;
    } else {
        $stmt = db()->prepare("
            INSERT INTO news (title, preview, full_text, image, news_date, sort_order, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$title, $preview, $fullText, $image, $newsDate, $sortOrder, $isActive]);
        $newId = (int)db()->lastInsertId();
    }

    echo json_encode(['ok' => true, 'id' => $newId]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Ошибка БД: ' . $e->getMessage()]);
}