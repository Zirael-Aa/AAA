<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход / Регистрация</title>
    <link href="https://fonts.googleapis.com/css2?family=Golos+Text:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <div class="auth-wrapper">
        <div class="container">
            <div class="form-toggle">
                <button class="toggle-btn active" onclick="showForm('login')">Вход</button>
                <button class="toggle-btn" onclick="showForm('register')">Регистрация</button>
            </div>

            <div class="form-container">
                <div class="form active" id="login-form">
                    <h2 class="form-title">Добро пожаловать</h2>
                    <form>
                        <div class="input-group">
                            <input type="email" id="login-email" placeholder=" " required>
                            <label for="login-email">Email</label>
                        </div>

                        <div class="input-group">
                            <input type="password" id="login-password" placeholder=" " required>
                            <label for="login-password">Пароль</label>
                        </div>

                        <div class="additional-options">
                            <div class="remember-me">
                                <input type="checkbox" id="remember">
                                <label for="remember">Запомнить меня</label>
                            </div>
                            <a href="#" class="forgot-password">Забыли пароль?</a>
                        </div>

                        <button type="submit" class="btn">Войти</button>
                    </form>
                </div>

                <div class="form" id="register-form">
                    <h2 class="form-title">Создать аккаунт</h2>
                    <form>
                        <div class="input-group">
                            <input type="text" id="register-name" placeholder=" " required>
                            <label for="register-name">Имя</label>
                        </div>

                        <div class="input-group">
                            <input type="email" id="register-email" placeholder=" " required>
                            <label for="register-email">Email</label>
                        </div>

                        <div class="input-group">
                            <input type="password" id="register-password" placeholder=" " required>
                            <label for="register-password">Пароль</label>
                        </div>

                        <div class="input-group">
                            <input type="password" id="register-confirm" placeholder=" " required>
                            <label for="register-confirm">Подтвердите пароль</label>
                        </div>

                        <button type="submit" class="btn">Зарегистрироваться</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="head" id="head-block" style="display: none;"></div>

    <script>
        function showForm(formType) {
            const forms = document.querySelectorAll('.form');
            const buttons = document.querySelectorAll('.toggle-btn');

            const activeForm = document.querySelector('.form.active');
            if (activeForm) {
                activeForm.style.opacity = '0';
                activeForm.style.transform = 'translateX(-20px)';

                setTimeout(() => {
                    activeForm.classList.remove('active');

                    if (formType === 'login') {
                        document.getElementById('login-form').classList.add('active');
                        buttons[0].classList.add('active');
                        buttons[1].classList.remove('active');

                        document.getElementById('head-block').style.display = 'none';
                    } else {
                        document.getElementById('register-form').classList.add('active');
                        buttons[1].classList.add('active');
                        buttons[0].classList.remove('active');
                    }

                    setTimeout(() => {
                        const newActiveForm = document.querySelector('.form.active');
                        newActiveForm.style.opacity = '1';
                        newActiveForm.style.transform = 'translateX(0)';
                    }, 50);
                }, 300);
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const registerForm = document.querySelector('#register-form form');
            const loginForm = document.querySelector('#login-form form');

            registerForm.addEventListener('submit', function(e) {
                e.preventDefault();
                document.getElementById('head-block').style.display = 'block';
                alert('Регистрация прошла успешно!');
            });

            loginForm.addEventListener('submit', function(e) {
                e.preventDefault();
                alert('Вход выполнен!');
            });

            const inputs = document.querySelectorAll('.input-group input');

            inputs.forEach(input => {
                if (input.value) {
                    input.parentNode.querySelector('label').classList.add('active');
                }

                input.addEventListener('focus', function() {
                    this.parentNode.querySelector('label').classList.add('active');
                });

                input.addEventListener('blur', function() {
                    if (!this.value) {
                        this.parentNode.querySelector('label').classList.remove('active');
                    }
                });
            });

            showForm('login');
        });
    </script>
</body>
</html>