<?php
session_start();
require __DIR__ . '/db.php';
require __DIR__ . '/admin_auth.php';
requireAdmin();

function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$search = trim($_GET['q'] ?? '');
$where  = [];
$params = [];
if ($search !== '') {
    $where[]  = '(u.name LIKE ? OR u.login LIKE ? OR u.account_number LIKE ?)';
    $like = '%' . $search . '%';
    $params = [$like, $like, $like];
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$stmt = db()->prepare("
    SELECT m.*, u.name AS user_name, u.login AS user_login,
           u.account_number
    FROM meter_readings m
    JOIN users u ON u.id = m.user_id
    $whereSql
    ORDER BY m.reading_date DESC, m.id DESC
    LIMIT 200
");
$stmt->execute($params);
$readings = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>Показания — Админ-панель</title>
<link rel="stylesheet" href="style.css">
<style>
.admin-wrap{ max-width: 1400px; margin: 0 auto; padding: 30px 20px; }
.admin-head{ display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:16px;margin-bottom:24px; }
.admin-head h1{ color:#0063a8; font-size:34px; margin:0; }
.admin-head .actions{ display:flex; gap:10px; flex-wrap:wrap; }
.admin-filters{
  background:#f8fafc;border:2px solid #e1e8f0;border-radius:14px;
  padding:18px 22px;margin-bottom:22px;display:flex;gap:14px;align-items:end;
}
.admin-filters input{
  flex:1;padding:9px 12px;border:2px solid #e1e8f0;border-radius:8px;
  font-size:14px;font-family:inherit;
}
.admin-filters button,.admin-filters a{
  padding:9px 18px;border-radius:8px;font-size:14px;font-weight:600;
  text-decoration:none;border:none;cursor:pointer;font-family:inherit;
}
.admin-filters button{ background:#05148b;color:#fff; }
.admin-filters a{ background:#fff;color:#0063a8;border:2px solid #e1e8f0; }
.admin-table-wrap{background:#fff;border:1px solid #e1e8f0;border-radius:16px;overflow:hidden;}
.admin-table{width:100%;border-collapse:collapse;font-size:14px;}
.admin-table th{background:#0063a8;color:#fff;padding:12px 14px;text-align:left;font-size:13px;}
.admin-table td{padding:12px 14px;border-bottom:1px solid #f0f6fa;}
.admin-table tr:hover{background:#f5fbff;}
.admin-empty{padding:40px;text-align:center;color:#7a8a99;}
</style>
</head>
<body>
<div class="admin-wrap">
  <div class="admin-head">
    <h1>Показания счётчиков</h1>
    <div class="actions">
      <a href="admin.php" class="btn-outline">К заявкам</a>
      <a href="admin_users.php" class="btn-outline">Пользователи</a>
      <a href="admin_reviews.php" class="btn-outline">Отзывы</a>
      <a href="admin_logout.php" class="btn-red">Выйти</a>
    </div>
  </div>

  <form class="admin-filters" method="get">
    <input type="text" name="q" value="<?= e($search) ?>"
           placeholder="Поиск по ФИО, логину, л/с…">
    <button type="submit">Найти</button>
    <a href="admin_meter.php">Сбросить</a>
  </form>

  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Абонент</th>
          <th>Л/с</th>
          <th>Дата снятия</th>
          <th>ХВС, м³</th>
          <th>ГВС, м³</th>
          <th>Передано</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$readings): ?>
          <tr><td colspan="7" class="admin-empty">Показаний не найдено.</td></tr>
        <?php else: ?>
          <?php foreach ($readings as $r): ?>
            <tr>
              <td><b style="color:#0063a8;">#<?= (int)$r['id'] ?></b></td>
              <td>
                <b><?= e($r['user_name']) ?></b><br>
                <small style="color:#7a8a99;">@<?= e($r['user_login']) ?></small>
              </td>
              <td><?= e($r['account_number']) ?></td>
              <td><?= e(date('d.m.Y', strtotime($r['reading_date']))) ?></td>
              <td><b><?= number_format((float)$r['cold_water'], 2, ',', ' ') ?></b></td>
              <td><b><?= number_format((float)$r['hot_water'], 2, ',', ' ') ?></b></td>
              <td><?= e(date('d.m.Y H:i', strtotime($r['created_at']))) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>