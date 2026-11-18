<?php
session_start();
require __DIR__ . '/db.php';
require __DIR__ . '/admin_auth.php';
requireAdmin();

$id       = (int)($_POST['id'] ?? 0);
$isActive = (int)($_POST['is_active'] ?? 1) === 1 ? 1 : 0;

if ($id > 0) {
    db()->prepare("UPDATE tariffs SET is_active = ? WHERE id = ?")
        ->execute([$isActive, $id]);
    $_SESSION['toast'] = $isActive
        ? 'Тариф №' . $id . ' показан на сайте'
        : 'Тариф №' . $id . ' скрыт с сайта';
}

header('Location: admin_tariffs.php');
exit;