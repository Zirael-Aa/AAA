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

$id          = (int)($_POST['id'] ?? 0);
$name        = trim($_POST['name'] ?? '');
$price       = (float)str_replace(',', '.', $_POST['price'] ?? '0');
$unit        = trim($_POST['unit'] ?? 'за 1 кубический метр');
$description = trim($_POST['description'] ?? '');
$features    = trim($_POST['features'] ?? '');
$sortOrder   = (int)($_POST['sort_order'] ?? 0);
$isActive    = (int)($_POST['is_active'] ?? 1) === 1 ? 1 : 0;

// ===== Валидация =====
if ($name === '') {
    echo json_encode(['ok' => false, 'error' => 'Название обязательно']);
    exit;
}
if ($price < 0) {
    echo json_encode(['ok' => false, 'error' => 'Цена не может быть отрицательной']);
    exit;
}

try {
    if ($id > 0) {
        // ===== Обновление существующего тарифа =====
        $stmt = db()->prepare("
            UPDATE tariffs
            SET name = ?, price = ?, unit = ?, description = ?,
                features = ?, sort_order = ?, is_active = ?
            WHERE id = ?
        ");
        $stmt->execute([$name, $price, $unit, $description, $features, $sortOrder, $isActive, $id]);
        $newId = $id;
    } else {
        // ===== Создание нового тарифа =====
        $stmt = db()->prepare("
            INSERT INTO tariffs (name, price, unit, description, features, sort_order, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$name, $price, $unit, $description, $features, $sortOrder, $isActive]);
        $newId = (int)db()->lastInsertId();
    }

    echo json_encode(['ok' => true, 'id' => $newId]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Ошибка БД: ' . $e->getMessage()]);
}