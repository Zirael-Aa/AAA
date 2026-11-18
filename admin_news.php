<?php
session_start();
require __DIR__ . '/db.php';
require __DIR__ . '/admin_auth.php';
requireAdmin();

function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$toast = $_SESSION['toast'] ?? '';
unset($_SESSION['toast']);

$news = db()->query("SELECT * FROM news ORDER BY news_date DESC, sort_order ASC, id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Новости — Админ-панель</title>
<link href="https://fonts.googleapis.com/css2?family=Golos+Text:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<style>
.admin-wrap{ max-width: 1400px; margin: 0 auto; padding: 30px 20px; }
.admin-head{ display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:16px;margin-bottom:24px; }
.admin-head h1{ color:#0063a8; font-size:34px; margin:0; font-weight:700; }
.admin-head .sub{ color:#7a8a99; font-size:14px; margin-top:4px; }
.admin-head .actions{ display:flex; gap:10px; flex-wrap:wrap; }

.admin-table-wrap{
  background:#fff;border:1px solid #e1e8f0;border-radius:16px;
  overflow:hidden;box-shadow:0 12px 35px rgba(0,99,168,.05);
  margin-bottom:20px;
}
.admin-table{width:100%;border-collapse:collapse;font-size:14px;}
.admin-table th{
  background:#0063a8;color:#fff;padding:12px 14px;
  text-align:left;font-weight:500;letter-spacing:.3px;font-size:13px;
}
.admin-table td{ padding:12px 14px;border-bottom:1px solid #f0f6fa;color:#2c3e50;vertical-align:top; }
.admin-table tr:last-child td{border-bottom:none;}
.admin-table tr:hover{background:#f5fbff;}

.news-thumb{
  width:80px;height:60px;object-fit:cover;border-radius:8px;
  border:1px solid #e1e8f0;background:#f8fafc;
}

.btn-icon{
  padding:7px 14px;border:none;border-radius:20px;
  font-size:12px;font-weight:600;cursor:pointer;
  transition:.2s;font-family:inherit;
}
.btn-edit{ background:#05148b;color:#fff; }
.btn-edit:hover{ background:#0133d0; }
.btn-toggle-on{ background:#2e6b3a;color:#fff; }
.btn-toggle-off{ background:#f8d7da;color:#842029; }
.btn-toggle-on:hover{ background:#1c4d28; }
.btn-toggle-off:hover{ background:#f1aeb5; }

.badge-active{ display:inline-block;padding:3px 10px;border-radius:10px;
  background:#d4edda;color:#2e6b3a;font-size:12px;font-weight:600; }
.badge-inactive{ display:inline-block;padding:3px 10px;border-radius:10px;
  background:#f8d7da;color:#842029;font-size:12px;font-weight:600; }

.toast{
  position:fixed;top:20px;right:20px;z-index:9999;
  background:#2e6b3a;color:#fff;padding:14px 22px;border-radius:12px;
  box-shadow:0 12px 30px rgba(0,0,0,.25);font-size:15px;
  border-left:6px solid #42b0ff;max-width:360px;
  animation:slideIn .4s ease;
}
@keyframes slideIn{ from{opacity:0;transform:translateX(30px);} to{opacity:1;transform:translateX(0);} }

/* Модалка */
.modal{
  position:fixed;inset:0;background:rgba(30,20,15,.6);
  display:none;align-items:center;justify-content:center;
  z-index:2000;padding:20px;
}
.modal.open{ display:flex; }
.modal-card{
  background:#fff;border-radius:20px;padding:28px;max-width:720px;
  width:100%;max-height:90vh;overflow-y:auto;position:relative;
  box-shadow:0 30px 70px rgba(0,0,0,.3);
}
.modal-card h3{ color:#0063a8;font-size:22px;margin:0 0 16px; }
.modal-close{
  position:absolute;top:12px;right:18px;background:none;border:none;
  font-size:28px;color:#999;cursor:pointer;line-height:1;
}
.form-grid{ display:grid;grid-template-columns:repeat(2,1fr);gap:14px; }
.form-grid .full{ grid-column: 1 / -1; }
.form-field label{
  display:block;color:#2c3e50;font-size:12px;letter-spacing:1.4px;
  text-transform:uppercase;margin-bottom:6px;font-weight:600;
}
.form-field input,
.form-field textarea,
.form-field select{
  width:100%;padding:10px 12px;border:2px solid #e1e8f0;
  border-radius:8px;font-family:inherit;font-size:14px;background:#fff;
  box-sizing:border-box;
}
.form-field textarea{ min-height:120px;resize:vertical; }
.form-field input:focus,
.form-field textarea:focus{ outline:none;border-color:#42b0ff; }
.form-field .hint{ font-size:12px;color:#7a8a99;margin-top:4px; }

.modal-actions{ margin-top:20px;display:flex;gap:10px;flex-wrap:wrap; }
.btn-primary{
  padding:11px 26px;background:#05148b;color:#fff;border:none;border-radius:24px;
  font-size:14px;font-weight:600;cursor:pointer;font-family:inherit;transition:.2s;
}
.btn-primary:hover{ background:#0133d0; }
.btn-secondary{
  padding:11px 26px;background:#fff;color:#0063a8;border:2px solid #e1e8f0;
  border-radius:24px;font-size:14px;font-weight:600;cursor:pointer;font-family:inherit;
}
.btn-secondary:hover{ background:#e3f2fd; }
</style>
</head>
<body>

<?php if ($toast): ?>
  <div class="toast" id="toast"><?= e($toast) ?></div>
  <script>setTimeout(()=>document.getElementById('toast')?.remove(),3000);</script>
<?php endif; ?>

<div class="admin-wrap">

  <div class="admin-head">
    <div>
      <h1>Новости</h1>
      <p class="sub">Управление новостями на сайте</p>
    </div>
    <div class="actions">
      <button type="button" class="btn-red" onclick="openCreateModal()">+ Добавить новость</button>
      <a href="admin.php"         class="btn-outline">К заявкам</a>
      <a href="admin_users.php"   class="btn-outline">Пользователи</a>
      <a href="admin_meter.php"   class="btn-outline">Показания</a>
      <a href="admin_reviews.php" class="btn-outline">Отзывы</a>
      <a href="admin_tariffs.php" class="btn-outline">Тарифы</a>
      <a href="news.php"          class="btn-outline">На сайт</a>
      <a href="admin_logout.php"  class="btn-red">Выйти</a>
    </div>
  </div>

  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead>
        <tr>
          <th style="width:60px;">ID</th>
          <th style="width:100px;">Фото</th>
          <th>Заголовок</th>
          <th style="width:120px;">Дата</th>
          <th>Превью</th>
          <th style="width:100px;">Статус</th>
          <th style="width:220px;">Действия</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$news): ?>
          <tr><td colspan="7" style="padding:40px;text-align:center;color:#7a8a99;">
            Новостей пока нет. Добавьте первую.
          </td></tr>
        <?php else: ?>
          <?php foreach ($news as $n): ?>
            <tr>
              <td><b style="color:#0063a8;">#<?= (int)$n['id'] ?></b></td>
              <td>
                <?php if (!empty($n['image'])): ?>
                  <img src="<?= e($n['image']) ?>" alt="" class="news-thumb"
                       onerror="this.style.display='none'">
                <?php else: ?>
                  <span style="color:#bbb;font-size:12px;">— нет —</span>
                <?php endif; ?>
              </td>
              <td><b><?= e($n['title']) ?></b></td>
              <td><?= e(date('d.m.Y', strtotime($n['news_date']))) ?></td>
              <td style="max-width:340px;font-size:13px;color:#555;">
                <?= e(mb_substr($n['preview'], 0, 120)) ?><?= mb_strlen($n['preview']) > 120 ? '…' : '' ?>
              </td>
              <td>
                <?php if ((int)$n['is_active'] === 1): ?>
                  <span class="badge-active">Активна</span>
                <?php else: ?>
                  <span class="badge-inactive">Скрыта</span>
                <?php endif; ?>
              </td>
              <td>
                <button type="button" class="btn-icon btn-edit"
                  onclick='editNews(<?= json_encode([
                      "id"         => (int)$n["id"],
                      "title"      => $n["title"],
                      "preview"    => $n["preview"],
                      "full_text"  => $n["full_text"],
                      "image"      => $n["image"],
                      "news_date"  => $n["news_date"],
                      "sort_order" => (int)$n["sort_order"],
                      "is_active"  => (int)$n["is_active"],
                  ], JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                  ✎ Изменить
                </button>

                <form method="post" action="admin_news_toggle.php" style="display:inline;"
                      onsubmit="return confirm('<?= (int)$n['is_active'] === 1 ? 'Скрыть новость?' : 'Показать новость?' ?>');">
                  <input type="hidden" name="id" value="<?= (int)$n['id'] ?>">
                  <input type="hidden" name="is_active" value="<?= (int)$n['is_active'] === 1 ? 0 : 1 ?>">
                  <button type="submit"
                          class="btn-icon <?= (int)$n['is_active'] === 1 ? 'btn-toggle-off' : 'btn-toggle-on' ?>">
                    <?= (int)$n['is_active'] === 1 ? '✕ Скрыть' : '✓ Показать' ?>
                  </button>
                </form>

                <form method="post" action="admin_news_delete.php" style="display:inline;"
                      onsubmit="return confirm('Удалить новость №<?= (int)$n['id'] ?>? Действие необратимо.');">
                  <input type="hidden" name="id" value="<?= (int)$n['id'] ?>">
                  <button type="submit" class="btn-icon btn-toggle-off">🗑</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

</div>

<!-- ===== Модалка ===== -->
<div class="modal" id="newsModal">
  <div class="modal-card">
    <button type="button" class="modal-close" onclick="closeModal()">×</button>
    <h3 id="modal-title">Новая новость</h3>

    <form id="newsForm">
      <input type="hidden" name="id" id="edit-id" value="0">

      <div class="form-grid">
        <div class="form-field full">
          <label>Заголовок</label>
          <input type="text" name="title" id="edit-title" required>
        </div>

        <div class="form-field">
          <label>Дата новости</label>
          <input type="date" name="news_date" id="edit-date" required>
        </div>

        <div class="form-field">
          <label>Порядок вывода</label>
          <input type="number" name="sort_order" id="edit-sort" min="0" value="0">
        </div>

        <div class="form-field full">
          <label>Путь к картинке</label>
          <input type="text" name="image" id="edit-image"
                 placeholder="фото/news1.png">
          <div class="hint">Путь относительно корня сайта. Можно оставить пустым.</div>
        </div>

        <div class="form-field full">
          <label>Краткое превью (для списка)</label>
          <textarea name="preview" id="edit-preview" style="min-height:80px;"></textarea>
        </div>

        <div class="form-field full">
          <label>Полный текст</label>
          <textarea name="full_text" id="edit-full"></textarea>
        </div>

        <div class="form-field">
          <label>Статус</label>
          <select name="is_active" id="edit-active">
            <option value="1">Активна</option>
            <option value="0">Скрыта</option>
          </select>
        </div>
      </div>

      <div class="modal-actions">
        <button type="submit" class="btn-primary">Сохранить</button>
        <button type="button" class="btn-secondary" onclick="closeModal()">Отмена</button>
        <span id="save-status" style="align-self:center;font-size:13px;color:#7a8a99;"></span>
      </div>
    </form>
  </div>
</div>

<script>
function todayISO() {
    const d = new Date();
    const p = n => String(n).padStart(2, '0');
    return d.getFullYear() + '-' + p(d.getMonth() + 1) + '-' + p(d.getDate());
}

function openCreateModal() {
    document.getElementById('modal-title').textContent = 'Новая новость';
    document.getElementById('edit-id').value       = 0;
    document.getElementById('edit-title').value    = '';
    document.getElementById('edit-date').value     = todayISO();
    document.getElementById('edit-sort').value     = 0;
    document.getElementById('edit-image').value    = '';
    document.getElementById('edit-preview').value  = '';
    document.getElementById('edit-full').value     = '';
    document.getElementById('edit-active').value   = 1;
    document.getElementById('save-status').textContent = '';
    document.getElementById('newsModal').classList.add('open');
}

function editNews(data) {
    document.getElementById('modal-title').textContent = 'Редактирование новости';
    document.getElementById('edit-id').value       = data.id;
    document.getElementById('edit-title').value    = data.title;
    document.getElementById('edit-date').value     = data.news_date;
    document.getElementById('edit-sort').value     = data.sort_order;
    document.getElementById('edit-image').value    = data.image || '';
    document.getElementById('edit-preview').value  = data.preview || '';
    document.getElementById('edit-full').value     = data.full_text || '';
    document.getElementById('edit-active').value   = data.is_active;
    document.getElementById('save-status').textContent = '';
    document.getElementById('newsModal').classList.add('open');
}

function closeModal() {
    document.getElementById('newsModal').classList.remove('open');
}

document.getElementById('newsModal').addEventListener('click', function (e) {
    if (e.target === this) closeModal();
});

document.getElementById('newsForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    const statusEl = document.getElementById('save-status');
    const btn      = this.querySelector('button[type="submit"]');
    const oldText  = btn.textContent;

    btn.disabled = true;
    btn.textContent = 'Сохраняем…';
    statusEl.textContent = '';

    try {
        const fd  = new FormData(this);
        const res = await fetch('admin_news_save.php', { method: 'POST', body: fd });
        const data = await res.json();

        if (!data.ok) throw new Error(data.error || 'Ошибка');

        statusEl.style.color = '#2e6b3a';
        statusEl.textContent = '✓ Сохранено';
        setTimeout(() => location.reload(), 600);
    } catch (err) {
        statusEl.style.color = '#842029';
        statusEl.textContent = '✕ ' + err.message;
    } finally {
        btn.disabled = false;
        btn.textContent = oldText;
    }
});
</script>

</body>
</html>