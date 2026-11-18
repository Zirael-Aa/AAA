<?php
session_start();
require __DIR__ . '/db.php';

function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$error = '';
$ok    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login    = trim($_POST['login'] ?? '');
    $name     = trim($_POST['name'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $address  = trim($_POST['address'] ?? '');
    $account  = trim($_POST['account_number'] ?? '');
    $pass     = $_POST['password'] ?? '';
    $pass2    = $_POST['password2'] ?? '';

    if ($login === '' || $name === '' || $pass === '') {
        $error = 'Заполните обязательные поля: логин, ФИО, пароль.';
    } elseif (mb_strlen($login) < 3) {
        $error = 'Логин должен быть не короче 3 символов.';
    } elseif (strlen($pass) < 6) {
        $error = 'Пароль должен быть не короче 6 символов.';
    } elseif ($pass !== $pass2) {
        $error = 'Пароли не совпадают.';
    } else {
        $stmt = db()->prepare("SELECT id FROM users WHERE login = ?");
        $stmt->execute([$login]);
        if ($stmt->fetch()) {
            $error = 'Такой логин уже занят.';
        } else {
            // Автогенерация лицевого счёта, если не указан
            if ($account === '') {
                $account = 'ЛС-' . str_pad((string)random_int(1, 9999999), 7, '0', STR_PAD_LEFT);
            }

            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = db()->prepare("
                INSERT INTO users (login, password, name, phone, email, address, account_number)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$login, $hash, $name, $phone, $email, $address, $account]);

            $ok = 'Регистрация прошла успешно. Теперь вы можете <a href="login.php">войти</a>.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=1280">
<title>Регистрация — ОАО «Исток»</title>
<link rel="stylesheet" href="style.css">
<style>
.auth-wrapper{
  display:flex;align-items:center;justify-content:center;
  min-height:100vh;padding:40px 20px;background:#e4ffff;
}
.auth-card{
  background:#fff;border-radius:30px;padding:50px 60px;
  box-shadow:0 25px 50px rgba(0,0,0,.15);
  width:100%;max-width:600px;
}
.auth-card h1{
  font-size:28px;color:#0063a8;text-align:center;margin-bottom:8px;font-weight:700;
}
.auth-card .sub{
  text-align:center;color:#7a8a99;font-size:14px;margin-bottom:30px;
}
.auth-field{margin-bottom:18px;}
.auth-field label{
  display:block;font-size:14px;color:#2c3e50;margin-bottom:8px;font-weight:500;
}
.auth-field input{
  width:100%;padding:14px 18px;border:2px solid #e1e8f0;
  border-radius:12px;font-size:15px;background:#f8fafc;
  transition:all .3s ease;font-family:inherit;
}
.auth-field input:focus{
  outline:none;border-color:#42b0ff;background:#fff;
  box-shadow:0 0 0 4px rgba(66,176,255,.15);
}
.auth-submit{
  width:100%;padding:15px;background:#05148b;color:#fff;
  border:none;border-radius:12px;font-size:16px;font-weight:600;
  cursor:pointer;transition:all .3s;margin-top:10px;
}
.auth-submit:hover{
  background:#0133d0;transform:translateY(-3px);
  box-shadow:0 10px 25px rgba(0,99,168,.3);
}
.auth-message{
  padding:12px 18px;border-radius:10px;margin-bottom:18px;font-size:14px;
}
.auth-message.error{background:#f8d7da;color:#842029;border:1px solid #f1aeb5;}
.auth-message.success{background:#d4edda;color:#2e6b3a;border:1px solid #b8dfc3;}
.auth-switch{
  text-align:center;margin-top:22px;font-size:14px;color:#7a8a99;
}
.auth-switch a{color:#0063a8;font-weight:600;}
.auth-switch a:hover{text-decoration:underline;}
.auth-back{
  display:inline-block;color:#0063a8;font-size:14px;
  margin-bottom:16px;text-decoration:none;
}
.auth-back:hover{text-decoration:underline;}
.req{color:#dc3545;}
</style>
</head>
<body>
<main class="auth-wrapper">
  <section class="auth-card">
    <a class="auth-back" href="index.php">← На главную</a>
    <h1>Регистрация</h1>
    <p class="sub">Личный кабинет абонента ОАО «Исток»</p>

    <?php if ($error): ?>
      <div class="auth-message error"><?= e($error) ?></div>
    <?php elseif ($ok): ?>
      <div class="auth-message success"><?= $ok ?></div>
    <?php endif; ?>

    <form method="post" novalidate>
      <div class="auth-field">
        <label for="login">Логин <span class="req">*</span></label>
        <input id="login" name="login" type="text"
               value="<?= e($_POST['login'] ?? '') ?>"
               placeholder="Придумайте логин" required>
      </div>

      <div class="auth-field">
        <label for="name">ФИО <span class="req">*</span></label>
        <input id="name" name="name" type="text"
               value="<?= e($_POST['name'] ?? '') ?>"
               placeholder="Иванов Иван Иванович" required>
      </div>

      <div class="auth-field">
        <label for="phone">Телефон</label>
        <input id="phone" name="phone" type="tel"
               value="<?= e($_POST['phone'] ?? '') ?>"
               placeholder="+7 (___) ___-__-__">
      </div>

      <div class="auth-field">
        <label for="email">Email</label>
        <input id="email" name="email" type="email"
               value="<?= e($_POST['email'] ?? '') ?>"
               placeholder="you@example.com">
      </div>

      <div class="auth-field">
        <label for="address">Адрес (город, улица, дом, кв.)</label>
        <input id="address" name="address" type="text"
               value="<?= e($_POST['address'] ?? '') ?>"
               placeholder="г. Каменск-Шахтинский, ул. ..., д. ..., кв. ...">
      </div>

      <div class="auth-field">
        <label for="account_number">Лицевой счёт (если есть)</label>
        <input id="account_number" name="account_number" type="text"
               value="<?= e($_POST['account_number'] ?? '') ?>"
               placeholder="Оставьте пустым — сгенерируем автоматически">
      </div>

      <div class="auth-field">
        <label for="password">Пароль <span class="req">*</span></label>
        <input id="password" name="password" type="password"
               placeholder="Минимум 6 символов" required>
      </div>

      <div class="auth-field">
        <label for="password2">Повторите пароль <span class="req">*</span></label>
        <input id="password2" name="password2" type="password"
               placeholder="Повторите пароль" required>
      </div>

      <button class="auth-submit" type="submit">Зарегистрироваться</button>

      <div class="auth-switch">
        Уже есть аккаунт? <a href="login.php">Войти</a>
      </div>
    </form>
  </section>
</main>
</body>
</html>