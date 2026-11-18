<?php
session_start();
require __DIR__ . '/db.php';
require __DIR__ . '/admin_auth.php';
requireAdmin();

function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

/* ============================================================
   POST-ДЕЙСТВИЯ
   ============================================================ */

// ===== Сохранение ответа админа + статуса (главная форма) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['request_id'], $_POST['admin_comment'])) {

    $rid     = (int)$_POST['request_id'];
    $comment = trim($_POST['admin_comment']);
    $status  = $_POST['status_after'] ?? 'in_progress';

    $allowed = ['new', 'in_progress', 'done', 'rejected'];
    if (!in_array($status, $allowed, true)) $status = 'in_progress';

    db()->prepare("
        UPDATE requests
        SET admin_comment = ?, status = ?, viewed = 0
        WHERE id = ?
    ")->execute([$comment, $status, $rid]);

    $_SESSION['toast'] = 'Ответ по заявке №' . $rid . ' сохранён';
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

// ===== Быстрая смена статуса (без ответа) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['request_id'], $_POST['status'])
    && !isset($_POST['admin_comment'])) {

    $allowed   = ['new', 'in_progress', 'done', 'rejected'];
    $newStatus = $_POST['status'];
    $rid       = (int)$_POST['request_id'];

    if (in_array($newStatus, $allowed, true) && $rid > 0) {
        db()->prepare("UPDATE requests SET status = ?, viewed = 1 WHERE id = ?")
            ->execute([$newStatus, $rid]);
        $_SESSION['toast'] = 'Статус заявки №' . $rid . ' обновлён';
    }
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

// ===== Удаление заявки =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $rid = (int)$_POST['delete_id'];
    db()->prepare("DELETE FROM requests WHERE id = ?")->execute([$rid]);
    $_SESSION['toast'] = 'Заявка №' . $rid . ' удалена';
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

// ===== Toast =====
$toast = $_SESSION['toast'] ?? '';
unset($_SESSION['toast']);

/* ============================================================
   ФИЛЬТРЫ / СОРТИРОВКА / ПАГИНАЦИЯ
   ============================================================ */
$filterStatus = trim($_GET['status'] ?? '');
$filterType   = trim($_GET['type']   ?? '');
$search       = trim($_GET['q']      ?? '');
$sort         = $_GET['sort'] ?? 'date_desc';
$page         = max(1, (int)($_GET['page'] ?? 1));
$perPage      = 10;
$offset       = ($page - 1) * $perPage;

$allowedSort = [
    'date_desc' => 'r.created_at DESC, r.id DESC',
    'date_asc'  => 'r.created_at ASC, r.id ASC',
];
$orderBy = $allowedSort[$sort] ?? $allowedSort['date_desc'];

$where  = [];
$params = [];

if ($filterStatus !== '') {
    $where[]  = 'r.status = ?';
    $params[] = $filterStatus;
}
if ($filterType !== '') {
    $where[]  = 'r.type = ?';
    $params[] = $filterType;
}
if ($search !== '') {
    $where[]  = '(u.name LIKE ? OR u.login LIKE ? OR r.subject LIKE ? OR r.message LIKE ?)';
    $like = '%' . $search . '%';
    $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// ===== Счётчик =====
$stmt = db()->prepare("
    SELECT COUNT(*) FROM requests r
    JOIN users u ON u.id = r.user_id
    $whereSql
");
$stmt->execute($params);
$totalRows  = (int)$stmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));

// ===== Выборка страницы =====
$stmt = db()->prepare("
    SELECT r.*,
           u.name  AS user_name,
           u.login AS user_login,
           u.phone AS user_phone,
           u.email AS user_email,
           u.account_number
    FROM requests r
    JOIN users u ON u.id = r.user_id
    $whereSql
    ORDER BY $orderBy
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$requests = $stmt->fetchAll();

/* ===== Счётчики по статусам (для быстрых фильтров) ===== */
$counts = db()->query("
    SELECT status, COUNT(*) AS cnt
    FROM requests
    GROUP BY status
")->fetchAll(PDO::FETCH_KEY_PAIR);

$totalAll = array_sum($counts);

/* ===== Справочники ===== */
$statuses = [
    'new'         => 'Новая',
    'in_progress' => 'В работе',
    'done'        => 'Выполнена',
    'rejected'    => 'Отклонена',
];

$types = [
    'repair'     => ' Ремонт',
    'connection' => ' Подключение',
    'question'   => ' Вопрос',
    'complaint'  => ' Жалоба',
];

function qs(array $override = []): string {
    $params = array_merge($_GET, $override);
    return '?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Админ-панель — ОАО «Исток»</title>
<link href="https://fonts.googleapis.com/css2?family=Golos+Text:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<style>
.admin-wrap{
  max-width: 1400px;
  margin: 0 auto;
  padding: 30px 20px;
  overflow: visible;   /* ← на всякий случай */
}
.admin-head{ display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:16px;margin-bottom:24px; }
.admin-head h1{ color:#0063a8; font-size:34px; margin:0; font-weight:700; }
.admin-head .sub{ color:#7a8a99; font-size:14px; margin-top:4px; }
.admin-head .actions{ display:flex; gap:10px; flex-wrap:wrap; }

/* ---- Быстрые фильтры по статусам ---- */
.status-tabs{
  display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px;
}
.status-tab{
  display:inline-flex;align-items:center;gap:8px;
  padding:10px 18px;border-radius:30px;
  background:#fff;border:2px solid #e1e8f0;
  color:#2c3e50;font-size:14px;font-weight:600;
  text-decoration:none;transition:.2s;
}
.status-tab:hover{ border-color:#42b0ff; background:#f5fbff; }
.status-tab.active{ background:#0063a8; color:#fff; border-color:#0063a8; }
.status-tab .cnt{
  display:inline-block;min-width:22px;padding:1px 7px;
  border-radius:11px;background:#eef4fb;color:#0063a8;
  font-size:12px;font-weight:700;text-align:center;
}
.status-tab.active .cnt{ background:rgba(255,255,255,.25); color:#fff; }

/* ---- Панель поиска ---- */
.admin-filters{
  background:#f8fafc;border:2px solid #e1e8f0;border-radius:14px;
  padding:18px 22px;margin-bottom:22px;
  display:grid;grid-template-columns:2fr 1fr 1fr auto;
  gap:14px;align-items:end;
}
@media (max-width:900px){ .admin-filters{ grid-template-columns:1fr; } }
.admin-filters label{
  display:block;color:#2c3e50;font-size:12px;letter-spacing:1.4px;
  text-transform:uppercase;margin-bottom:6px;font-weight:600;
}
.admin-filters input, .admin-filters select{
  width:100%;padding:9px 12px;border:2px solid #e1e8f0;
  border-radius:8px;font-family:inherit;font-size:14px;background:#fff;box-sizing:border-box;
}
.admin-filters .filter-actions{ display:flex;gap:8px;flex-wrap:wrap; }

/* ---- Карточка заявки ---- */
.req-card{
  background:#fff;border:1px solid #e1e8f0;border-radius:16px;
  margin-bottom:20px;
  box-shadow:0 6px 20px rgba(0,99,168,.06);
  transition:.2s;
  position: relative;   /* ← ДОБАВЛЕНО: для позиционирования выпадающего меню */
  overflow: visible;    /* ← ИЗМЕНЕНО: было hidden */
}
.req-card.unread{ border-left:6px solid #ffc107; }
.req-card:hover{ box-shadow:0 12px 30px rgba(0,99,168,.10); }

.req-header{
  display:flex;justify-content:space-between;align-items:center;
  flex-wrap:wrap;gap:12px;
  padding:16px 22px;background:#f8fafc;border-bottom:1px solid #e1e8f0;
}
.req-id{
  font-size:20px;font-weight:700;color:#0063a8;
}
.req-date{
  color:#7a8a99;font-size:13px;margin-left:10px;
}
.req-header-right{
  display:flex;gap:10px;align-items:center;flex-wrap:wrap;
}

.status{
  display:inline-block;padding:6px 14px;border-radius:20px;
  font-size:13px;font-weight:600;white-space:nowrap;
}
.status-new{background:#fff3cd;color:#8a6d00;}
.status-progress{background:#cff4fc;color:#055160;}
.status-done{background:#d4edda;color:#2e6b3a;}
.status-rejected{background:#f8d7da;color:#842029;}

.unread-badge{
  display:inline-block;padding:3px 10px;border-radius:10px;
  background:#ffc107;color:#5a4500;font-size:11px;font-weight:700;
  letter-spacing:.5px;
}

.type-badge{
  display:inline-block;padding:5px 12px;border-radius:10px;
  background:#eef4fb;color:#0063a8;font-size:13px;font-weight:600;
}

/* ---- Тело карточки: 2 колонки ---- */
.req-body{
  display:grid;grid-template-columns:1fr 1fr;gap:0;
}
@media (max-width:1000px){ .req-body{ grid-template-columns:1fr; } }

.req-info{
  padding:20px 22px;border-right:1px solid #f0f6fa;
}
@media (max-width:1000px){ .req-info{ border-right:none;border-bottom:1px solid #f0f6fa; } }

.req-info-block{ margin-bottom:16px; }
.req-info-block:last-child{ margin-bottom:0; }
.req-info-label{
  font-size:11px;letter-spacing:1.2px;text-transform:uppercase;
  color:#7a8a99;font-weight:600;margin-bottom:6px;
}
.req-info-value{
  font-size:15px;color:#2c3e50;line-height:1.5;
}
.req-info-value b{ color:#0063a8; }

.req-message{
  background:#f8fafc;border-radius:10px;padding:12px 16px;
  font-size:14px;color:#2c3e50;line-height:1.6;
  border-left:4px solid #42b0ff;
}

/* ---- Правая колонка: действия ---- */
.req-actions{
  padding:20px 22px;background:#fbfdff;
}

.action-section{ margin-bottom:22px; }
.action-section:last-child{ margin-bottom:0; }

.action-title{
  font-size:12px;letter-spacing:1.2px;text-transform:uppercase;
  color:#2c3e50;font-weight:700;margin-bottom:10px;
  display:flex;align-items:center;gap:8px;
}
.action-title .hint{
  font-size:11px;font-weight:400;letter-spacing:0;
  text-transform:none;color:#7a8a99;
}

.comment-form textarea{
  width:100%;min-height:80px;padding:10px 12px;
  border:2px solid #e1e8f0;border-radius:10px;
  font-family:inherit;font-size:14px;resize:vertical;
  box-sizing:border-box;transition:.2s;
}
.comment-form textarea:focus{
  outline:none;border-color:#42b0ff;
  box-shadow:0 0 0 3px rgba(66,176,255,.15);
}

.status-select{
  width:100%;padding:10px 12px;margin-top:8px;
  border:2px solid #e1e8f0;border-radius:10px;
  font-family:inherit;font-size:14px;background:#fff;cursor:pointer;
}
.status-select:focus{ outline:none;border-color:#42b0ff; }

.form-row{
  display:flex;gap:8px;align-items:center;margin-top:10px;flex-wrap:wrap;
}

.btn-save{
  padding:10px 22px;border:none;border-radius:24px;
  background:#2e6b3a;color:#fff;font-size:14px;font-weight:600;
  cursor:pointer;font-family:inherit;transition:.2s;
}
.btn-save:hover{ background:#1c4d28; }
.btn-save:disabled{ opacity:.6;cursor:wait; }

.reply-status{
  font-size:13px;color:#7a8a99;
}
.reply-status.ok{ color:#2e6b3a; font-weight:600; }
.reply-status.err{ color:#842029; font-weight:600; }

/* ---- Сохранённый ответ ---- */
.saved-reply{
  margin-top:14px;padding:12px 16px;border-radius:10px;
  background:#f5fff8;border-left:5px solid #2e6b3a;
  font-size:14px;color:#2c3e50;line-height:1.55;
  white-space:pre-wrap;word-break:break-word;
}
.saved-reply.empty{
  background:#f8fafc;border-left-color:#c9d6de;
  color:#9aa8b2;font-style:italic;
}
.saved-reply b{color:#2e6b3a;}

/* ---- Меню «Ещё» ---- */
.more-menu{ position:relative; }
.more-menu summary{
  list-style:none;cursor:pointer;
  padding:8px 14px;border-radius:20px;
  background:#fff;border:2px solid #e1e8f0;
  font-size:13px;font-weight:600;color:#2c3e50;
  display:inline-flex;align-items:center;gap:6px;
  transition:.2s;
}
.more-menu summary::-webkit-details-marker{ display:none; }
.more-menu summary:hover{ border-color:#42b0ff; color:#0063a8; }
.more-menu[open] summary{ border-color:#42b0ff; color:#0063a8; }
.more-menu-content{
  position:absolute;right:0;top:calc(100% + 6px);z-index:20;
  background:#fff;border:1px solid #e1e8f0;border-radius:12px;
  box-shadow:0 12px 30px rgba(0,0,0,.12);
  padding:8px;min-width:220px;
}
.more-menu-content button{
  display:block;width:100%;text-align:left;
  padding:10px 14px;border:none;border-radius:8px;
  background:none;font-family:inherit;font-size:14px;
  color:#842029;cursor:pointer;transition:.2s;
}
.more-menu-content button:hover{ background:#f8d7da; }

/* ---- Пустое состояние ---- */
.admin-empty{
  padding:60px 20px;text-align:center;color:#7a8a99;font-size:16px;
  background:#fff;border-radius:16px;border:2px dashed #d9ecf5;
}
.admin-empty a{ color:#0063a8; font-weight:600; }

/* ---- Пагинация ---- */
.pagination{
  display:flex;gap:6px;justify-content:center;align-items:center;
  margin:24px 0;flex-wrap:wrap;
}
.pagination a, .pagination span{
  display:inline-block;padding:8px 14px;border-radius:8px;
  border:2px solid #e1e8f0;background:#fff;color:#0063a8;
  text-decoration:none;font-size:14px;font-weight:500;transition:.2s;
}
.pagination a:hover{background:#e3f2fd;}
.pagination .current{ background:#0063a8;color:#fff;border-color:#0063a8; }
.pagination .disabled{opacity:.4;pointer-events:none;}

/* ---- Toast ---- */
.toast{
  position:fixed;top:20px;right:20px;z-index:9999;
  background:#2e6b3a;color:#fff;padding:14px 22px;border-radius:12px;
  box-shadow:0 12px 30px rgba(0,0,0,.25);font-size:15px;
  border-left:6px solid #42b0ff;max-width:360px;
  animation: slideIn .4s ease;
}
.toast.hide{opacity:0;transform:translateX(30px);transition:.4s;}
@keyframes slideIn{ from{opacity:0;transform:translateX(30px);} to{opacity:1;transform:translateX(0);} }
</style>
</head>
<body>

<?php if ($toast): ?>
  <div class="toast" id="toast"><?= e($toast) ?></div>
  <script>
    setTimeout(() => document.getElementById('toast')?.classList.add('hide'), 3000);
    setTimeout(() => document.getElementById('toast')?.remove(), 3600);
  </script>
<?php endif; ?>

<div class="admin-wrap">

  <!-- ===== Шапка ===== -->
  <div class="admin-head">
    <div>
      <h1>Заявки абонентов</h1>
      <p class="sub">Обработка обращений и ответы клиентам</p>
    </div>
        <div class="actions">
      <a href="admin_news.php"    class="btn-outline">Новости</a>
      <a href="admin_users.php"   class="btn-outline">Пользователи</a>
      <a href="admin_meter.php"   class="btn-outline">Показания</a>
      <a href="admin_reviews.php" class="btn-outline">Отзывы</a>
      <a href="admin_tariffs.php" class="btn-outline">Тарифы</a>
      <a href="index.php"         class="btn-outline">На сайт</a>
      <a href="admin_logout.php"  class="btn-red">Выйти</a>
    </div>
  </div>

  <!-- ===== Быстрые фильтры по статусам ===== -->
  <div class="status-tabs">
    <a href="<?= qs(['status' => '', 'page' => 1]) ?>"
       class="status-tab <?= $filterStatus === '' ? 'active' : '' ?>">
      Все <span class="cnt"><?= $totalAll ?></span>
    </a>
    <?php foreach ($statuses as $k => $v): ?>
      <a href="<?= qs(['status' => $k, 'page' => 1]) ?>"
         class="status-tab <?= $filterStatus === $k ? 'active' : '' ?>">
        <?= e($v) ?> <span class="cnt"><?= (int)($counts[$k] ?? 0) ?></span>
      </a>
    <?php endforeach; ?>
  </div>

  <!-- ===== Поиск и фильтры ===== -->
  <form class="admin-filters" method="get">
    <div>
      <label>Поиск</label>
      <input type="text" name="q" value="<?= e($search) ?>"
             placeholder="ФИО, логин, тема или текст заявки…">
    </div>

    <div>
      <label>Тип заявки</label>
      <select name="type">
        <option value="">— Все типы —</option>
        <?php foreach ($types as $k => $v): ?>
          <option value="<?= e($k) ?>" <?= $filterType === $k ? 'selected' : '' ?>>
            <?= e($v) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <label>Сортировка</label>
      <select name="sort">
        <option value="date_desc" <?= $sort === 'date_desc' ? 'selected' : '' ?>>Сначала новые</option>
        <option value="date_asc"  <?= $sort === 'date_asc'  ? 'selected' : '' ?>>Сначала старые</option>
      </select>
    </div>

    <div class="filter-actions">
      <button type="submit" class="btn-red" style="padding:10px 20px;">Применить</button>
      <a href="admin.php" class="btn-outline" style="padding:10px 20px;">Сбросить</a>
    </div>
  </form>

  <!-- ===== Список заявок ===== -->
  <?php if (!$requests): ?>
    <div class="admin-empty">
      Заявок по заданным фильтрам не найдено.<br>
      <a href="admin.php">Сбросить фильтры</a>
    </div>
  <?php else: ?>
    <?php foreach ($requests as $r): ?>
      <?php
        $stClass = [
          'new'         => 'status-new',
          'in_progress' => 'status-progress',
          'done'        => 'status-done',
          'rejected'    => 'status-rejected',
        ][$r['status']] ?? 'status-new';

        $isUnread = ((int)$r['viewed'] === 0 && $r['status'] === 'new');
      ?>

      <article class="req-card <?= $isUnread ? 'unread' : '' ?>">

        <!-- ===== Шапка карточки ===== -->
        <header class="req-header">
          <div>
            <span class="req-id">Заявка №<?= (int)$r['id'] ?></span>
            <span class="req-date">
              от <?= e(date('d.m.Y в H:i', strtotime($r['created_at']))) ?>
            </span>
          </div>
          <div class="req-header-right">
            <span class="type-badge"><?= e($types[$r['type']] ?? $r['type']) ?></span>
            <span class="status <?= $stClass ?>"
                  id="status-pill-<?= (int)$r['id'] ?>">
              <?= e($statuses[$r['status']] ?? $r['status']) ?>
            </span>
            <?php if ($isUnread): ?>
              <span class="unread-badge">НОВАЯ</span>
            <?php endif; ?>
          </div>
        </header>

        <!-- ===== Тело: 2 колонки ===== -->
        <div class="req-body">

          <!-- ЛЕВАЯ: информация о заявке и клиенте -->
          <div class="req-info">

            <div class="req-info-block">
              <div class="req-info-label">Клиент</div>
              <div class="req-info-value">
                <b><?= e($r['user_name']) ?></b>
                <span style="color:#7a8a99;">(@<?= e($r['user_login']) ?>)</span>
              </div>
            </div>

            <?php if (!empty($r['account_number'])): ?>
              <div class="req-info-block">
                <div class="req-info-label">Лицевой счёт</div>
                <div class="req-info-value"><?= e($r['account_number']) ?></div>
              </div>
            <?php endif; ?>

            <div class="req-info-block">
              <div class="req-info-label">Тема обращения</div>
              <div class="req-info-value"><b><?= e($r['subject']) ?></b></div>
            </div>

            <div class="req-info-block">
              <div class="req-info-label">Текст заявки</div>
              <div class="req-message"><?= nl2br(e($r['message'])) ?></div>
            </div>

            <div class="req-info-block" style="display:flex;gap:24px;flex-wrap:wrap;">
              <?php if (!empty($r['address'])): ?>
                <div>
                  <div class="req-info-label">Адрес</div>
                  <div class="req-info-value"><?= e($r['address']) ?></div>
                </div>
              <?php endif; ?>
              <?php if (!empty($r['phone'])): ?>
                <div>
                  <div class="req-info-label">Телефон</div>
                  <div class="req-info-value"><?= e($r['phone']) ?></div>
                </div>
              <?php endif; ?>
            </div>

          </div>

          <!-- ПРАВАЯ: действия администратора -->
          <div class="req-actions">

            <!-- ===== Форма ответа ===== -->
            <div class="action-section">
              <div class="action-title">
                ✉ Ответ клиенту
                <span class="hint">— сохраняется и виден в личном кабинете</span>
              </div>

              <form class="comment-form js-reply-form"
                    data-request-id="<?= (int)$r['id'] ?>"
                    method="post">
                <input type="hidden" name="request_id" value="<?= (int)$r['id'] ?>">

                <textarea name="admin_comment"
                          placeholder="Напишите ответ абоненту…"><?= e($r['admin_comment'] ?? '') ?></textarea>

                <label class="req-info-label" style="margin-top:12px;">
                  Что сделать после ответа?
                </label>
                <select name="status_after" class="status-select">
                  <option value="done"        <?= $r['status']==='done'        ? 'selected':'' ?>>
                    ✅ Закрыть заявку (клиент получит ответ)
                  </option>
                  <option value="in_progress" <?= $r['status']==='in_progress' ? 'selected':'' ?>>
                    ⏳ Оставить в работе (ответ черновик)
                  </option>
                  <option value="rejected"    <?= $r['status']==='rejected'    ? 'selected':'' ?>>
                    ⛔ Отклонить заявку
                  </option>
                </select>

                <div class="form-row">
                  <button type="submit" class="btn-save">Сохранить ответ</button>
                  <span class="reply-status"></span>
                </div>
              </form>

              <!-- Уже сохранённый ответ -->
              <div class="saved-reply <?= empty($r['admin_comment']) ? 'empty' : '' ?>"
                   id="reply-box-<?= (int)$r['id'] ?>">
                <?php if (!empty($r['admin_comment'])): ?>
                  <b>Текущий ответ:</b><br><?= nl2br(e($r['admin_comment'])) ?>
                <?php else: ?>
                  <i>Ответ пока не сохранён</i>
                <?php endif; ?>
              </div>
            </div>

            <!-- ===== Быстрая смена статуса без ответа ===== -->
            <div class="action-section">
              <div class="action-title">
                ⚡ Быстрая смена статуса
                <span class="hint">— без ответа</span>
              </div>

              <form class="status-form" method="post"
                    style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                <input type="hidden" name="request_id" value="<?= (int)$r['id'] ?>">
                <select name="status" class="status-select" style="flex:1;min-width:160px;margin-top:0;">
                  <?php foreach ($statuses as $k => $v): ?>
                    <option value="<?= e($k) ?>" <?= $r['status'] === $k ? 'selected' : '' ?>>
                      <?= e($v) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <button type="submit" class="btn-outline"
                        style="padding:10px 18px;border-radius:24px;cursor:pointer;font-family:inherit;font-size:13px;font-weight:600;">
                  Применить
                </button>
              </form>
            </div>

            <!-- ===== Меню «Ещё» ===== -->
            <div class="action-section" style="text-align:right;">
              <details class="more-menu">
                <summary>⋯ Ещё</summary>
                <div class="more-menu-content">
                  <form method="post"
                        onsubmit="return confirm('Удалить заявку №<?= (int)$r['id'] ?>? Действие необратимо.');">
                    <input type="hidden" name="delete_id" value="<?= (int)$r['id'] ?>">
                    <button type="submit">🗑 Удалить заявку</button>
                  </form>
                </div>
              </details>
            </div>

          </div>
        </div>
      </article>
    <?php endforeach; ?>
  <?php endif; ?>

  <!-- ===== Пагинация ===== -->
  <?php if ($totalPages > 1): ?>
    <div class="pagination">
      <a href="<?= qs(['page' => max(1, $page - 1)]) ?>"
         class="<?= $page <= 1 ? 'disabled' : '' ?>">← Назад</a>

      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <?php if ($i === $page): ?>
          <span class="current"><?= $i ?></span>
        <?php else: ?>
          <a href="<?= qs(['page' => $i]) ?>"><?= $i ?></a>
        <?php endif; ?>
      <?php endfor; ?>

      <a href="<?= qs(['page' => min($totalPages, $page + 1)]) ?>"
         class="<?= $page >= $totalPages ? 'disabled' : '' ?>">Вперёд →</a>
    </div>
    <p style="text-align:center;color:#7a8a99;font-size:13px;">
      Показано <?= count($requests) ?> из <?= $totalRows ?> заявок
    </p>
  <?php endif; ?>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.js-reply-form').forEach(function (form) {
        form.addEventListener('submit', async function (e) {
            e.preventDefault();

            const rid      = form.dataset.requestId;
            const statusEl = form.querySelector('.reply-status');
            const btn      = form.querySelector('button[type="submit"]');
            const oldText  = btn.textContent;

            btn.disabled = true;
            btn.textContent = 'Сохраняем…';
            statusEl.textContent = '';
            statusEl.className = 'reply-status';

            try {
                const fd  = new FormData(form);
                const res = await fetch('admin_reply.php', { method: 'POST', body: fd });
                const data = await res.json();

                if (!data.ok) throw new Error(data.error || 'Ошибка');

                // Обновляем блок с ответом
                const box = document.getElementById('reply-box-' + rid);
                if (box) {
                    box.classList.remove('empty');
                    box.innerHTML = '<b>Текущий ответ:</b><br>' +
                        String(data.comment).replace(/\n/g, '<br>');
                }

                // Обновляем статус-пилюлю
                const statusMap = {
                    new:         ['Новая',     'status-new'],
                    in_progress: ['В работе',  'status-progress'],
                    done:        ['Выполнена', 'status-done'],
                    rejected:    ['Отклонена', 'status-rejected'],
                };
                const pill = document.getElementById('status-pill-' + rid);
                if (pill && statusMap[data.status]) {
                    pill.textContent = statusMap[data.status][0];
                    pill.className   = 'status ' + statusMap[data.status][1];
                }

                // Синхронизируем select быстрой смены статуса
                const statusSelect = form.parentElement
                    .parentElement
                    .querySelector('.status-form select[name="status"]');
                if (statusSelect && statusMap[data.status]) {
                    statusSelect.value = data.status;
                }

                statusEl.textContent = '✓ Сохранено';
                statusEl.className = 'reply-status ok';

                if (box) {
                    box.style.transition = 'background .3s';
                    box.style.background = '#c8f0d6';
                    setTimeout(() => box.style.background = '', 700);
                }
            } catch (err) {
                statusEl.textContent = '✕ ' + err.message;
                statusEl.className = 'reply-status err';
            } finally {
                btn.disabled = false;
                btn.textContent = oldText;
            }
        });
    });
});
</script>

</body>
</html>