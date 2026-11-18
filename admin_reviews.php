<?php
session_start();
require __DIR__ . '/db.php';
require __DIR__ . '/admin_auth.php';
requireAdmin();

function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// Смена статуса отзыва
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_id'], $_POST['status'])) {
    $allowed = ['published', 'pending', 'rejected'];
    $id = (int)$_POST['review_id'];
    $st = $_POST['status'];
    if (in_array($st, $allowed, true) && $id > 0) {
        db()->prepare("UPDATE reviews SET status = ? WHERE id = ?")->execute([$st, $id]);
        $_SESSION['toast'] = 'Статус отзыва №' . $id . ' обновлён';
    }
    header('Location: admin_reviews.php');
    exit;
}

$toast = $_SESSION['toast'] ?? '';
unset($_SESSION['toast']);

$stmt = db()->query("
    SELECT r.*, u.name AS user_name, u.login AS user_login
    FROM reviews r
    JOIN users u ON u.id = r.user_id
    ORDER BY r.created_at DESC
");
$reviews = $stmt->fetchAll();

$statuses = [
    'published' => 'Опубликован',
    'pending'   => 'На модерации',
    'rejected'  => 'Отклонён',
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>Отзывы — Админ-панель</title>
<link rel="stylesheet" href="style.css">
<style>
.admin-wrap{ max-width: 1400px; margin: 0 auto; padding: 30px 20px; }
.admin-head{ display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:16px;margin-bottom:24px; }
.admin-head h1{ color:#0063a8; font-size:34px; margin:0; }
.admin-head .actions{ display:flex; gap:10px; flex-wrap:wrap; }
.admin-table-wrap{background:#fff;border:1px solid #e1e8f0;border-radius:16px;overflow:hidden;}
.admin-table{width:100%;border-collapse:collapse;font-size:14px;}
.admin-table th{background:#0063a8;color:#fff;padding:12px 14px;text-align:left;font-size:13px;}
.admin-table td{padding:12px 14px;border-bottom:1px solid #f0f6fa;vertical-align:top;}
.admin-table tr:hover{background:#f5fbff;}
.admin-empty{padding:40px;text-align:center;color:#7a8a99;}
.status-form{display:flex;gap:6px;align-items:center;}
.status-form select{
  padding:6px 10px;border:2px solid #e1e8f0;border-radius:20px;
  font-family:inherit;font-size:13px;
}
.status-form button{
  padding:6px 14px;border:none;border-radius:20px;background:#05148b;
  color:#fff;font-size:12px;font-weight:600;cursor:pointer;font-family:inherit;
}
.rating{color:#ffc107;font-size:16px;}
.toast{
  position:fixed;top:20px;right:20px;z-index:9999;
  background:#2e6b3a;color:#fff;padding:14px 22px;border-radius:12px;
  font-size:15px;box-shadow:0 12px 30px rgba(0,0,0,.25);
}
</style>
</head>
<body>
<?php if ($toast): ?>
  <div class="toast" id="toast"><?= e($toast) ?></div>
  <script>setTimeout(()=>document.getElementById('toast')?.remove(),3000);</script>
<?php endif; ?>

<div class="admin-wrap">
  <div class="admin-head">
    <h1>Отзывы</h1>
    <div class="actions">
      <a href="admin.php" class="btn-outline">К заявкам</a>
      <a href="admin_users.php" class="btn-outline">Пользователи</a>
      <a href="admin_meter.php" class="btn-outline">Показания</a>
      <a href="admin_logout.php" class="btn-red">Выйти</a>
    </div>
  </div>

  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Автор</th>
          <th>Оценка</th>
          <th>Текст</th>
          <th>Дата</th>
          <th>Статус</th>
          <th>Изменить</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$reviews): ?>
          <tr><td colspan="7" class="admin-empty">Отзывов пока нет.</td></tr>
        <?php else: ?>
          <?php foreach ($reviews as $r): ?>
            <tr>
              <td><b style="color:#0063a8;">#<?= (int)$r['id'] ?></b></td>
              <td>
                <b><?= e($r['user_name']) ?></b><br>
                <small style="color:#7a8a99;">@<?= e($r['user_login']) ?></small>
              </td>
              <td class="rating">
                <?= str_repeat('★', (int)$r['rating']) . str_repeat('☆', 5 - (int)$r['rating']) ?>
              </td>
              <td style="max-width:420px;"><?= nl2br(e($r['message'])) ?></td>
              <td><?= e(date('d.m.Y H:i', strtotime($r['created_at']))) ?></td>
              <td><?= e($statuses[$r['status']] ?? $r['status']) ?></td>
              <td>
                <form class="status-form" method="post">
                  <input type="hidden" name="review_id" value="<?= (int)$r['id'] ?>">
                  <select name="status">
                    <?php foreach ($statuses as $k => $v): ?>
                      <option value="<?= e($k) ?>" <?= $r['status'] === $k ? 'selected' : '' ?>>
                        <?= e($v) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <button type="submit">OK</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>