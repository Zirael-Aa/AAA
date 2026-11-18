<?php
session_start();
require __DIR__ . '/db.php';
require __DIR__ . '/admin_auth.php';

function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login    = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($login === '') {
        $error = 'Введите логин.';
    } elseif ($password === '') {
        $error = 'Введите пароль.';
    } else {
        // ===== Проверка админа =====
        if ($login === ADMIN_LOGIN && $password === ADMIN_PASS) {
            $_SESSION['admin'] = [
                'login' => ADMIN_LOGIN,
                'name'  => 'Администратор',
            ];
            header('Location: admin.php');
            exit;
        }

        // ===== Проверка обычного пользователя =====
        $stmt = db()->prepare("SELECT * FROM users WHERE login = ?");
        $stmt->execute([$login]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            $error = 'Неверный логин или пароль.';
        } else {
            $_SESSION['user'] = [
                'id'             => (int)$user['id'],
                'login'          => $user['login'],
                'name'           => $user['name'],
                'phone'          => $user['phone'],
                'email'          => $user['email'],
                'account_number' => $user['account_number'],
                'address'        => $user['address'],
            ];
            header('Location: profile.php');
            exit;
        }
    }
}

if (!empty($_SESSION['user']['id'])) {
    header('Location: profile.php');
    exit;
}
if (!empty($_SESSION['admin'])) {
    header('Location: admin.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=1280">
<title>Вход — ОАО «Исток»</title>
<link rel="stylesheet" href="style.css">
<style>
.auth-wrapper{
  display:flex;align-items:center;justify-content:center;
  min-height:100vh;padding:40px 20px;background:#e4ffff;
}
.auth-card{
  background:#fff;border-radius:30px;padding:50px 60px;
  box-shadow:0 25px 50px rgba(0,0,0,.15);
  width:100%;max-width:520px;
}
.auth-card h1{
  font-size:28px;color:#0063a8;text-align:center;margin-bottom:8px;font-weight:700;
}
.auth-card .sub{
  text-align:center;color:#7a8a99;font-size:14px;margin-bottom:30px;
}
.auth-field{margin-bottom:20px;}
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
  padding:12px 18px;border-radius:10px;margin-bottom:18px;
  font-size:14px;background:#f8d7da;color:#842029;border:1px solid #f1aeb5;
}
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
</style>
</head>
<body>
<main class="auth-wrapper">
  <section class="auth-card">
    <a class="auth-back" href="index.php">← На главную</a>
    <h1>Личный кабинет</h1>
    <p class="sub">ОАО «Исток» — Чистая вода</p>

    <?php if ($error): ?>
      <div class="auth-message"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" novalidate>
      <div class="auth-field">
        <label for="login">Логин</label>
        <input id="login" name="login" type="text"
               value="<?= e($_POST['login'] ?? '') ?>"
               placeholder="Введите логин" required>
      </div>

      <div class="auth-field">
        <label for="password">Пароль</label>
        <input id="password" name="password" type="password"
               placeholder="Введите пароль" required>
      </div>

      <button class="auth-submit" type="submit">Войти</button>

      <div class="auth-switch">
        Ещё не зарегистрированы?
        <a href="registration.php">Регистрация</a>
      </div>
    </form>
  </section>
</main>
</body>
</html>