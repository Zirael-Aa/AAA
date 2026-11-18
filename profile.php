<?php
session_start();
require __DIR__ . '/db.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
$user = $_SESSION['user'];

function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

/* ===== Действия ===== */

// Добавить показания
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_meter') {
    $date  = $_POST['reading_date'] ?? date('Y-m-d');
    $cold  = (float)str_replace(',', '.', $_POST['cold_water'] ?? '0');
    $hot   = (float)str_replace(',', '.', $_POST['hot_water'] ?? '0');

    if ($cold < 0 || $hot < 0) {
        $_SESSION['flash'] = 'Показания не могут быть отрицательными.';
        $_SESSION['flash_type'] = 'error';
    } else {
        $stmt = db()->prepare("
            INSERT INTO meter_readings (user_id, reading_date, cold_water, hot_water)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$user['id'], $date, $cold, $hot]);
        $_SESSION['flash'] = 'Показания успешно переданы.';
        $_SESSION['flash_type'] = 'success';
    }
    header('Location: profile.php');
    exit;
}

// Создать заявку
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_request') {
    $type    = in_array($_POST['type'] ?? '', ['repair','connection','question','complaint'], true)
                ? $_POST['type'] : 'question';
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');

    if ($subject === '' || $message === '') {
        $_SESSION['flash'] = 'Заполните тему и текст заявки.';
        $_SESSION['flash_type'] = 'error';
    } else {
        $stmt = db()->prepare("
            INSERT INTO requests (user_id, type, subject, message, address, phone)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$user['id'], $type, $subject, $message, $address, $phone]);
        $_SESSION['flash'] = 'Заявка отправлена. Мы свяжемся с вами.';
        $_SESSION['flash_type'] = 'success';
    }
    header('Location: profile.php');
    exit;
}

// Flash
$flash     = $_SESSION['flash'] ?? '';
$flashType = $_SESSION['flash_type'] ?? '';
unset($_SESSION['flash'], $_SESSION['flash_type']);

/* ===== Данные ===== */

// Показания
$stmt = db()->prepare("
    SELECT * FROM meter_readings
    WHERE user_id = ?
    ORDER BY reading_date DESC, id DESC
    LIMIT 24
");
$stmt->execute([$user['id']]);
$readings = $stmt->fetchAll();

// Последнее показание для расчёта
$lastReading = $readings[0] ?? null;

// Заявки
$stmt = db()->prepare("
    SELECT * FROM requests
    WHERE user_id = ?
    ORDER BY created_at DESC
");
$stmt->execute([$user['id']]);
$requests = $stmt->fetchAll();

// Помечаем ответы админа просмотренными
db()->prepare("UPDATE requests SET viewed = 1 WHERE user_id = ? AND viewed = 0")
    ->execute([$user['id']]);

/* ===== Хелперы ===== */
function reqTypeLabel(string $t): string {
    return [
        'repair'     => ' Ремонт',
        'connection' => ' Подключение',
        'question'   => ' Вопрос',
        'complaint'  => ' Жалоба',
    ][$t] ?? $t;
}

function reqStatus(string $s, int $viewed): array {
    if ($s === 'new') {
        return $viewed === 0
            ? ['Новая', 'status-new']
            : ['Просмотрено', 'status-viewed'];
    }
    return [
        'in_progress' => ['В работе', 'status-progress'],
        'done'        => ['Выполнена', 'status-done'],
        'rejected'    => ['Отклонена', 'status-rejected'],
    ][$s] ?? [$s, 'status-new'];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=1280">
<title>Личный кабинет — ОАО «Исток»</title>
<link rel="stylesheet" href="style.css">
<style>
.profile-page{
  background:#e4ffff;padding:40px 0 60px;
}
.profile-wrap{
  width:1200px;margin:0 auto;
}
.profile-head{
  display:flex;justify-content:space-between;align-items:flex-end;
  flex-wrap:wrap;gap:20px;margin-bottom:24px;
}
.profile-head h2{
  font-size:32px;color:#0063a8;font-weight:700;margin:0;
}
.profile-head .sub{color:#7a8a99;font-size:15px;margin-top:6px;}

.profile-card{
  background:#fff;border:1px solid #d9ecf5;border-radius:24px;
  padding:28px 32px;box-shadow:0 12px 35px rgba(0,99,168,.08);
  margin-bottom:28px;
}
.profile-row{
  display:flex;gap:10px;padding:10px 0;
  border-bottom:1px solid #f0f6fa;font-size:16px;
}
.profile-row:last-child{border-bottom:none;}
.profile-row b{color:#0063a8;min-width:130px;font-weight:600;}

.profile-actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:20px;}

.btn-red, .btn-outline{
  display:inline-block;padding:12px 26px;border-radius:30px;
  font-size:15px;font-weight:500;text-decoration:none;transition:.3s;
  font-family:inherit;cursor:pointer;border:1px solid transparent;
}
.btn-red{background:#05148b;color:#fff;border-color:#05148b;}
.btn-red:hover{background:#0133d0;}
.btn-outline{background:transparent;color:#0063a8;border-color:#42b0ff;}
.btn-outline:hover{background:#0063a8;color:#fff;}

.section-title{
  font-size:24px;color:#2c3e50;font-weight:700;
  margin:40px 0 18px;padding-left:14px;border-left:5px solid #0063a8;
}

.flash{
  padding:14px 20px;border-radius:14px;background:#d4edda;color:#2e6b3a;
  margin-bottom:20px;font-size:15px;border:1px solid #b8dfc3;
}
.flash.error{background:#f8d7da;color:#842029;border-color:#f1aeb5;}

/* ---------- ФОРМА ---------- */
.form-card{
  background:#fff;border:1px solid #d9ecf5;border-radius:20px;
  padding:24px;margin-bottom:24px;
  box-shadow:0 12px 35px rgba(0,99,168,.05);
}
.form-card h3{
  font-size:18px;color:#2c3e50;margin-bottom:18px;font-weight:600;
}
.form-grid{
  display:grid;grid-template-columns:repeat(2,1fr);gap:16px;
}
.form-grid .full{grid-column:1 / -1;}
.form-grid label{
  display:block;font-size:14px;color:#2c3e50;margin-bottom:6px;font-weight:500;
}
.form-grid input,
.form-grid select,
.form-grid textarea{
  width:100%;padding:11px 14px;border:2px solid #e1e8f0;
  border-radius:10px;font-size:15px;background:#f8fafc;
  font-family:inherit;transition:.3s;
}
.form-grid input:focus,
.form-grid select:focus,
.form-grid textarea:focus{
  outline:none;border-color:#42b0ff;background:#fff;
  box-shadow:0 0 0 4px rgba(66,176,255,.15);
}
.form-grid textarea{min-height:100px;resize:vertical;}
.form-btn{
  margin-top:18px;padding:12px 30px;
  background:#05148b;color:#fff;border:none;border-radius:30px;
  font-size:15px;font-weight:600;cursor:pointer;transition:.3s;
}
.form-btn:hover{background:#0133d0;transform:translateY(-2px);}

/* ---------- ТАБЛИЦА ---------- */
.table-wrap{
  background:#fff;border:1px solid #d9ecf5;border-radius:20px;
  overflow:hidden;box-shadow:0 12px 35px rgba(0,99,168,.05);
}
.tbl{width:100%;border-collapse:collapse;font-size:15px;}
.tbl th{
  background:#0063a8;color:#fff;padding:14px 18px;
  text-align:left;font-weight:500;font-size:14px;
}
.tbl td{
  padding:14px 18px;border-bottom:1px solid #f0f6fa;
  color:#2c3e50;vertical-align:middle;
}
.tbl tr:last-child td{border-bottom:none;}
.tbl tr:hover{background:#f5fbff;}

.status{
  display:inline-block;padding:5px 14px;border-radius:20px;
  font-size:13px;font-weight:600;
}
.status-new{background:#fff3cd;color:#8a6d00;}
.status-viewed{background:#e2e3e5;color:#41464b;}
.status-progress{background:#cff4fc;color:#055160;}
.status-done{background:#d4edda;color:#2e6b3a;}
.status-rejected{background:#f8d7da;color:#842029;}

.empty-note{
  color:#7a8a99;padding:24px;font-size:16px;
  background:#fff;border-radius:20px;text-align:center;
  border:1px dashed #c9e1ee;
}
.empty-note a{color:#0063a8;font-weight:600;}
.empty-note a:hover{text-decoration:underline;}

.req-comment{
  padding:12px 18px;background:#f5fbff;border-top:1px solid #e1e8f0;
  font-size:14px;color:#2c3e50;
}
.req-comment b{color:#0063a8;}

/* ---------- КНОПКИ ОПЛАТЫ ---------- */
.pay-btn {
    background-color: #2e6b3a;
    color: #fff;
    padding: 8px 16px;
    border-radius: 20px;
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
    display: inline-block;
    transition: background-color 0.3s;
    cursor: pointer;
    border: none;
    font-family: inherit;
    white-space: nowrap;
}
.pay-btn:hover {
    background-color: #1c4d28;
}
.qr-btn {
    background-color: #0063a8;
    color: #fff;
    padding: 8px 16px;
    border-radius: 20px;
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
    display: inline-block;
    transition: background-color 0.3s;
    cursor: pointer;
    border: none;
    font-family: inherit;
    white-space: nowrap;
}
.qr-btn:hover {
    background-color: #004a80;
}

/* ---------- МОДАЛЬНОЕ ОКНО QR ---------- */
.modal-qr {
    display: none;
    position: fixed;
    z-index: 9999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.6);
    align-items: center;
    justify-content: center;
}
.modal-qr.open {
    display: flex;
}
.modal-qr-content {
    background-color: #fff;
    padding: 24px;
    border-radius: 20px;
    max-width: 400px;
    width: 90%;
    text-align: center;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    position: relative;
}
.modal-qr-content h3 {
    color: #0063a8;
    margin-bottom: 15px;
    font-size: 20px;
    font-weight: 700;
}
.modal-qr-content p {
    color: #555;
    margin-bottom: 15px;
    font-size: 14px;
}
.modal-qr-content img {
    width: 100%;
    max-width: 300px;
    height: auto;
    display: block;
    margin: 0 auto;
    border-radius: 10px;
}
.modal-qr-close {
    position: absolute;
    top: 10px;
    right: 15px;
    font-size: 30px;
    cursor: pointer;
    color: #999;
    line-height: 1;
    background: none;
    border: none;
    font-family: inherit;
}
.modal-qr-close:hover {
    color: #333;
}
</style>
</head>
<body>

<header class="pole1" style="padding:14px 0;">
  <div class="dropdown-menu">
    <a href="index.php">Главная</a>
    <a href="index8.php">Тарифы</a>
    <a href="index4.php">Подключение</a>
    <a href="index5.php">Ремонт</a>
    <a href="index7.php">Вопросы</a>
  </div>
</header>

<main class="profile-page">
  <div class="profile-wrap">

    <!-- Шапка -->
    <div class="profile-head">
      <div>
        <h2>Личный кабинет</h2>
        <p class="sub">Абонент ОАО «Исток»</p>
      </div>
      <div class="profile-actions" style="margin:0;">
        <a href="#add-meter" class="btn-red">+ Передать показания</a>
        <a href="#add-request" class="btn-outline">+ Новая заявка</a>
        <a href="logout.php" class="btn-outline">Выйти</a>
      </div>
    </div>

    <!-- Карточка пользователя -->
    <div class="profile-card">
      <div class="profile-row"><b>ФИО:</b> <span><?= e($user['name']) ?></span></div>
      <div class="profile-row"><b>Логин:</b> <span><?= e($user['login']) ?></span></div>
      <div class="profile-row"><b>Лицевой счёт:</b> <span><?= e($user['account_number'] ?? '—') ?></span></div>
      <div class="profile-row"><b>Адрес:</b> <span><?= e($user['address'] ?? '—') ?></span></div>
      <div class="profile-row"><b>Телефон:</b> <span><?= e($user['phone'] ?? '—') ?></span></div>
      <div class="profile-row"><b>Email:</b> <span><?= e($user['email'] ?? '—') ?></span></div>
    </div>

    <?php if ($flash): ?>
      <div class="flash <?= $flashType === 'error' ? 'error' : '' ?>"><?= e($flash) ?></div>
    <?php endif; ?>

    <!-- ===== Передать показания ===== -->
    <h2 class="section-title" id="add-meter">Передать показания счётчиков</h2>

    <div class="form-card">
      <h3>Новые показания</h3>
      <form method="post">
        <input type="hidden" name="action" value="add_meter">
        <div class="form-grid">
          <div>
            <label>Дата снятия</label>
            <input type="date" name="reading_date"
                   value="<?= e(date('Y-m-d')) ?>" required>
          </div>
          <div>
            <label>Холодная вода, м³</label>
            <input type="text" name="cold_water" placeholder="0.00" required>
          </div>
          <div>
            <label>Горячая вода, м³</label>
            <input type="text" name="hot_water" placeholder="0.00" required>
          </div>
        </div>
        <button type="submit" class="form-btn">Передать показания</button>
      </form>
    </div>

    <!-- История показаний -->
    <h2 class="section-title">История показаний</h2>
    <?php if (!$readings): ?>
      <p class="empty-note">Показания ещё не передавались.</p>
    <?php else: ?>
      <div class="table-wrap">
        <table class="tbl">
          <thead>
            <tr>
              <th>Дата</th>
              <th>Холодная вода, м³</th>
              <th>Горячая вода, м³</th>
              <th>Разница (ХВС)</th>
              <th>Разница (ГВС)</th>
              <th>Оплата</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($readings as $i => $r): ?>
              <?php
                $prev = $readings[$i + 1] ?? null;
                $diffCold = $prev ? ((float)$r['cold_water'] - (float)$prev['cold_water']) : null;
                $diffHot  = $prev ? ((float)$r['hot_water']  - (float)$prev['hot_water'])  : null;
              ?>
              <tr>
                <td><?= e(date('d.m.Y', strtotime($r['reading_date']))) ?></td>
                <td><?= number_format((float)$r['cold_water'], 2, ',', ' ') ?></td>
                <td><?= number_format((float)$r['hot_water'],  2, ',', ' ') ?></td>
                <td><?= $diffCold !== null ? number_format($diffCold, 2, ',', ' ') : '—' ?></td>
                <td><?= $diffHot  !== null ? number_format($diffHot,  2, ',', ' ') : '—' ?></td>
                <td>
                  <?php if (isset($r['is_paid']) && $r['is_paid'] == 1): ?>
                    <span class="status status-done">Оплачено</span>
                  <?php else: ?>
                    <button type="button" class="pay-btn" onclick="showPaymentOptions(this)">Оплатить</button>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

    <!-- ===== Новая заявка ===== -->
    <h2 class="section-title" id="add-request">Новая заявка</h2>

    <div class="form-card">
      <h3>Оставить заявку</h3>
      <form method="post">
        <input type="hidden" name="action" value="add_request">
        <div class="form-grid">
          <div>
            <label>Тип заявки</label>
            <select name="type" required>
              <option value="repair">Ремонт</option>
              <option value="connection">Подключение</option>
              <option value="question">Вопрос</option>
              <option value="complaint">Жалоба</option>
            </select>
          </div>
          <div>
            <label>Тема</label>
            <input type="text" name="subject" placeholder="Кратко о проблеме" required>
          </div>
          <div>
            <label>Адрес (если требуется)</label>
            <input type="text" name="address"
                   value="<?= e($user['address'] ?? '') ?>"
                   placeholder="ул. ..., д. ..., кв. ...">
          </div>
          <div>
            <label>Телефон для связи</label>
            <input type="tel" name="phone"
                   value="<?= e($user['phone'] ?? '') ?>">
          </div>
          <div class="full">
            <label>Описание</label>
            <textarea name="message" placeholder="Опишите проблему подробно" required></textarea>
          </div>
        </div>
        <button type="submit" class="form-btn">Отправить заявку</button>
      </form>
    </div>

    <!-- История заявок -->
    <h2 class="section-title">Мои заявки</h2>
    <?php if (!$requests): ?>
      <p class="empty-note">У вас пока нет заявок.</p>
    <?php else: ?>
      <div class="table-wrap">
        <table class="tbl">
          <thead>
            <tr>
              <th>№</th>
              <th>Тип</th>
              <th>Тема</th>
              <th>Дата</th>
              <th>Статус</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($requests as $r): ?>
              <?php
                $st = reqStatus($r['status'], (int)$r['viewed']);
                // Оплата доступна только для Ремонта и Подключения
                $payableTypes = ['repair', 'connection'];
                $canPay = in_array($r['type'], $payableTypes, true);
              ?>
              <tr>
                <td><?= (int)$r['id'] ?></td>
                <td><?= e(reqTypeLabel($r['type'])) ?></td>
                <td>
                  <b style="color:#0063a8;"><?= e($r['subject']) ?></b><br>
                  <span style="color:#7a8a99;font-size:13px;">
                    <?= e(mb_substr($r['message'], 0, 100)) ?><?= mb_strlen($r['message']) > 100 ? '…' : '' ?>
                  </span>
                </td>
                <td><?= e(date('d.m.Y H:i', strtotime($r['created_at']))) ?></td>
                <td>
                  <?php
                    if ($canPay && $r['status'] === 'done'):
                      if (isset($r['is_paid']) && $r['is_paid'] == 1): ?>
                        <span class="status status-done">Оплачено</span>
                      <?php else: ?>
                        <button type="button" class="pay-btn" onclick="showPaymentOptions(this)">Оплатить</button>
                      <?php endif;
                    else: ?>
                      <span class="status <?= $st[1] ?>"><?= e($st[0]) ?></span>
                    <?php endif; ?>
                </td>
              </tr>
              <?php if (!empty($r['admin_comment'])): ?>
                <tr>
                  <td colspan="5" style="padding:0;">
                    <div class="req-comment">
                      <b>Ответ администратора:</b> <?= e($r['admin_comment']) ?>
                    </div>
                  </td>
                </tr>
              <?php endif; ?>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

  </div>
</main>

<!-- ===== МОДАЛЬНОЕ ОКНО ДЛЯ ОПЛАТЫ (QR-КОД) ===== -->
<div id="paymentModal" class="modal-qr">
    <div class="modal-qr-content">
        <button type="button" class="modal-qr-close" onclick="closePaymentModal()">&times;</button>
        <h3>Оплата заявки</h3>
        <p>Отсканируйте QR-код для оплаты через СБП</p>
        <img src="фото/qr-code.png" alt="QR-код для оплаты">
    </div>
</div>

<footer class="footer">
  <div class="footer-bottom">
    <div class="footer-bottom-container">
      <div class="copyright">© 2024 ОАО "Исток". Все права защищены.</div>
      <div class="footer-policy">
        <a href="#">Политика конфиденциальности</a>
        <a href="#">Пользовательское соглашение</a>
      </div>
    </div>
  </div>
</footer>

<script>
    // Показ вариантов оплаты вместо кнопки «Оплатить»
    function showPaymentOptions(btn) {
        const container = document.createElement('div');
        container.style.display = 'inline-flex';
        container.style.gap = '8px';
        container.style.flexWrap = 'wrap';

        const payLink = document.createElement('a');
        payLink.href = 'https://online.sberbank.ru/CSAFront/index.do?ysclid=mwe44ln40666397862';
        payLink.target = '_blank';
        payLink.rel = 'noopener';
        payLink.className = 'pay-btn';
        payLink.textContent = 'Перейти к оплате';

        const qrBtn = document.createElement('button');
        qrBtn.type = 'button';
        qrBtn.className = 'qr-btn';
        qrBtn.textContent = 'QR-код';
        qrBtn.addEventListener('click', openPaymentModal);

        container.appendChild(payLink);
        container.appendChild(qrBtn);

        btn.replaceWith(container);
    }

    function openPaymentModal() {
        document.getElementById('paymentModal').classList.add('open');
    }

    function closePaymentModal() {
        document.getElementById('paymentModal').classList.remove('open');
    }

    window.addEventListener('click', function (event) {
        const modal = document.getElementById('paymentModal');
        if (event.target === modal) closePaymentModal();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closePaymentModal();
    });
</script>

</body>
</html>