<?php
session_start();
require __DIR__ . '/db.php';
require __DIR__ . '/admin_auth.php';
requireAdmin();

$id = (int)($_POST['id'] ?? 0);

if ($id > 0) {
    db()->prepare("DELETE FROM news WHERE id = ?")->execute([$id]);
    $_SESSION['toast'] = 'Новость №' . $id . ' удалена';
}

header('Location: admin_news.php');
exit;