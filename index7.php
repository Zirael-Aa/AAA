<?php
session_start();
require __DIR__ . '/db.php';

function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$isLogged  = !empty($_SESSION['user']['id']);
$flash     = $_SESSION['flash']      ?? '';
$flashType = $_SESSION['flash_type'] ?? '';
unset($_SESSION['flash'], $_SESSION['flash_type']);

/* ====== Отправка вопроса ====== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'ask_question') {

    if (!$isLogged) {
        $_SESSION['flash']      = 'Чтобы задать вопрос, войдите в личный кабинет.';
        $_SESSION['flash_type'] = 'error';
        header('Location: index7.php');
        exit;
    }

    $subject = trim($_POST['subject'] ?? 'Вопрос');
    $message = trim($_POST['question'] ?? '');

    if ($message === '') {
        $_SESSION['flash']      = 'Напишите текст вопроса.';
        $_SESSION['flash_type'] = 'error';
    } else {
        $stmt = db()->prepare("
            INSERT INTO requests (user_id, type, subject, message, address, phone)
            VALUES (?, 'question', ?, ?, NULL, ?)
        ");
        $stmt->execute([
            $_SESSION['user']['id'],
            $subject,
            $message,
            $_SESSION['user']['phone'] ?? ''
        ]);

        $_SESSION['flash']      = 'Ваш вопрос отправлен! Ответ появится здесь.';
        $_SESSION['flash_type'] = 'success';
    }
    header('Location: index7.php#chat');
    exit;
}

/* ============================================================
   СПИСОК ВОПРОСОВ (чатов) — видят все
   ============================================================ */
$stmt = db()->query("
    SELECT r.id,
           r.subject,
           r.message,
           r.admin_comment,
           r.status,
           r.created_at,
           u.name AS user_name,
           u.id   AS author_id
    FROM requests r
    JOIN users u ON u.id = r.user_id
    WHERE r.type = 'question'
      AND r.status <> 'rejected'
    ORDER BY r.created_at DESC
    LIMIT 100
");
$allQuestions = $stmt->fetchAll();

// Определяем активный чат
$activeChatId = (int)($_GET['chat'] ?? 0);
$activeChat = null;
foreach ($allQuestions as $q) {
    if ((int)$q['id'] === $activeChatId) {
        $activeChat = $q;
        break;
    }
}
// Если не выбран — берём первый
if (!$activeChat && $allQuestions) {
    $activeChat = $allQuestions[0];
    $activeChatId = (int)$activeChat['id'];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Чат с ОАО «Исток»</title>
    <link rel="stylesheet" href="style.css">
    <style>
    /* ============================================================
       ЧАТ — СТИЛИ
       ============================================================ */
    .chat-page {
        width: 100%;
        max-width: 140rem;
        margin: -8rem auto 4rem;
        padding: 0 2rem;
        position: relative;
        z-index: 5;
    }

    .chat-layout {
        display: grid;
        grid-template-columns: 32rem 1fr;
        gap: 2rem;
        background: #fff;
        border-radius: 3rem;
        box-shadow: 0 1rem 3rem rgba(0, 99, 168, .12);
        overflow: hidden;
        height: 70vh;
        min-height: 55rem;
    }

    /* ===== Список диалогов ===== */
    .chat-sidebar {
        background: #f4f9fd;
        border-right: 2px solid #e1e8f0;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .chat-sidebar-header {
        padding: 2rem;
        background: #0063a8;
        color: #fff;
    }

    .chat-sidebar-header h2 {
        font-size: 2rem;
        font-weight: 700;
        margin: 0 0 .5rem;
    }

    .chat-sidebar-header p {
        font-size: 1.3rem;
        opacity: .8;
        margin: 0;
    }

    .chat-list {
        flex: 1;
        overflow-y: auto;
        padding: 1rem;
    }

    .chat-list-item {
        display: block;
        padding: 1.5rem;
        border-radius: 1.5rem;
        margin-bottom: .8rem;
        background: #fff;
        border: 2px solid transparent;
        text-decoration: none;
        transition: all .25s ease;
        cursor: pointer;
    }

    .chat-list-item:hover {
        border-color: #42b0ff;
        background: #eaf6ff;
    }

    .chat-list-item.active {
        border-color: #0063a8;
        background: #e3f2fd;
        box-shadow: 0 0.4rem 1.2rem rgba(0, 99, 168, .15);
    }

    .chat-list-subject {
        font-size: 1.5rem;
        font-weight: 700;
        color: #0063a8;
        margin-bottom: .5rem;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .chat-list-preview {
        font-size: 1.3rem;
        color: #7a8a99;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        margin-bottom: .5rem;
    }

    .chat-list-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 1.2rem;
        color: #9aa8b2;
    }

    .chat-badge {
        display: inline-block;
        padding: .2rem .8rem;
        border-radius: 1rem;
        font-size: 1.1rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .5px;
    }

    .chat-badge-answered {
        background: #d4edda;
        color: #2e6b3a;
    }

    .chat-badge-pending {
        background: #fff3cd;
        color: #8a6d00;
    }

    /* ===== Окно чата ===== */
    .chat-main {
        display: flex;
        flex-direction: column;
        background: #fff;
        overflow: hidden;
    }

    .chat-main-header {
        padding: 1.8rem 2.5rem;
        border-bottom: 2px solid #e1e8f0;
        background: #fff;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .chat-main-header h3 {
        font-size: 1.8rem;
        color: #2c3e50;
        margin: 0;
        font-weight: 700;
    }

    .chat-main-header .chat-author {
        font-size: 1.3rem;
        color: #7a8a99;
        margin-top: .3rem;
    }

    /* ===== Лента сообщений ===== */
    .chat-messages {
        flex: 1;
        overflow-y: auto;
        padding: 2.5rem;
        background: #f8fbfe;
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    .chat-empty {
        text-align: center;
        color: #7a8a99;
        font-size: 1.5rem;
        padding: 4rem 2rem;
    }

    /* ===== Сообщение ===== */
    .chat-msg {
        display: flex;
        gap: 1rem;
        max-width: 75%;
        animation: msgIn .3s ease;
    }

    @keyframes msgIn {
        from { opacity: 0; transform: translateY(8px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    /* Сообщение СВОЁ — справа */
    .chat-msg-mine {
        align-self: flex-end;
        flex-direction: row-reverse;
    }

    /* Сообщение ЧУЖОЕ — слева */
    .chat-msg-other {
        align-self: flex-start;
    }

    .chat-msg-avatar {
        width: 3.6rem;
        height: 3.6rem;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
        font-weight: 700;
        color: #fff;
        flex-shrink: 0;
    }

    .chat-msg-avatar-user {
        background: #42b0ff;
    }

    .chat-msg-avatar-admin {
        background: #0063a8;
        position: relative;
    }

    .chat-msg-avatar-admin::after {
        content: '★';
        position: absolute;
        bottom: -2px;
        right: -2px;
        background: #ffc107;
        color: #5a4500;
        width: 1.6rem;
        height: 1.6rem;
        border-radius: 50%;
        font-size: 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px solid #fff;
    }

    .chat-msg-body {
        display: flex;
        flex-direction: column;
        gap: .4rem;
        min-width: 0;
    }

    .chat-msg-mine .chat-msg-body {
        align-items: flex-end;
    }

    .chat-msg-author {
        font-size: 1.2rem;
        color: #7a8a99;
        font-weight: 600;
        padding: 0 .5rem;
    }

    .chat-msg-bubble {
        padding: 1.2rem 1.6rem;
        border-radius: 1.6rem;
        font-size: 1.5rem;
        line-height: 1.5;
        word-wrap: break-word;
        word-break: break-word;
        white-space: pre-wrap;
        max-width: 100%;
    }

    /* Своё сообщение (пользователь) — синий */
    .chat-msg-mine .chat-msg-bubble {
        background: #05148b;
        color: #fff;
        border-bottom-right-radius: .4rem;
    }

    /* Чужое сообщение (другой пользователь) — светло-серый */
    .chat-msg-other .chat-msg-bubble {
        background: #e8eef3;
        color: #2c3e50;
        border-bottom-left-radius: .4rem;
    }

    /* Ответ админа — зелёный (в палитре сайта) */
    .chat-msg-admin .chat-msg-bubble {
        background: #e6f7ec;
        color: #1c4d28;
        border-left: 4px solid #2e6b3a;
        border-bottom-left-radius: .4rem;
    }

    .chat-msg-admin .chat-msg-author {
        color: #2e6b3a;
    }

    .chat-msg-time {
        font-size: 1.1rem;
        color: #9aa8b2;
        padding: 0 .5rem;
    }

    /* ===== Форма ввода ===== */
    .chat-input-area {
        padding: 1.8rem 2.5rem;
        border-top: 2px solid #e1e8f0;
        background: #fff;
    }

    .chat-input-form {
        display: flex;
        gap: 1rem;
        align-items: flex-end;
    }

    .chat-input-form textarea {
        flex: 1;
        min-height: 4.5rem;
        max-height: 12rem;
        padding: 1.2rem 1.6rem;
        border: 2px solid #e1e8f0;
        border-radius: 1.5rem;
        font-family: inherit;
        font-size: 1.5rem;
        resize: none;
        background: #f8fafc;
        transition: all .25s ease;
        line-height: 1.5;
    }

    .chat-input-form textarea:focus {
        outline: none;
        border-color: #42b0ff;
        background: #fff;
        box-shadow: 0 0 0 3px rgba(66, 176, 255, .15);
    }

    .chat-send-btn {
        padding: 1.2rem 2.4rem;
        background: #05148b;
        color: #fff;
        border: none;
        border-radius: 1.5rem;
        font-family: inherit;
        font-size: 1.5rem;
        font-weight: 600;
        cursor: pointer;
        transition: all .25s ease;
        white-space: nowrap;
        display: flex;
        align-items: center;
        gap: .8rem;
    }

    .chat-send-btn:hover {
        background: #0133d0;
        transform: translateY(-2px);
        box-shadow: 0 0.6rem 1.8rem rgba(0, 99, 168, .3);
    }

    .chat-send-btn:disabled {
        opacity: .6;
        cursor: wait;
        transform: none;
    }

    /* ===== Flash ===== */
    .chat-flash {
        max-width: 140rem;
        margin: 0 auto 1.5rem;
        padding: 1.2rem 2rem;
        border-radius: 1.2rem;
        font-size: 1.5rem;
        background: #d4edda;
        color: #2e6b3a;
        border: 1px solid #b8dfc3;
    }

    .chat-flash.error {
        background: #f8d7da;
        color: #842029;
        border-color: #f1aeb5;
    }

    /* ===== Гостю ===== */
    .chat-guest {
        padding: 4rem 2rem;
        text-align: center;
        background: #f8fafc;
        border-top: 2px solid #e1e8f0;
    }

    .chat-guest p {
        font-size: 1.5rem;
        color: #2c3e50;
        margin-bottom: 1.5rem;
    }

    /* ===== Адаптив ===== */
    @media (max-width: 900px) {
        .chat-layout {
            grid-template-columns: 1fr;
            height: auto;
            min-height: auto;
        }

        .chat-sidebar {
            border-right: none;
            border-bottom: 2px solid #e1e8f0;
            max-height: 30rem;
        }

        .chat-main {
            min-height: 50rem;
        }

        .chat-msg {
            max-width: 90%;
        }
    }

    @media (max-width: 600px) {
        .chat-page { padding: 0 1rem; }

        .chat-layout { border-radius: 2rem; }

        .chat-messages { padding: 1.5rem; }

        .chat-input-area { padding: 1.2rem 1.5rem; }

        .chat-send-btn {
            padding: 1.2rem 1.6rem;
            font-size: 1.4rem;
        }

        .chat-send-btn .send-text { display: none; }
    }
    </style>
</head>
<body>
<?php require 'header.php'; ?>
    <div class="head head-faq"></div>

    <?php if ($flash): ?>
        <div class="chat-flash <?= $flashType === 'error' ? 'error' : '' ?>"
             style="margin-top: 2rem;">
            <?= e($flash) ?>
        </div>
    <?php endif; ?>

    <!-- ===== ЧАТ ===== -->
    <div class="chat-page" id="chat">
        <div class="chat-layout">

            <!-- ===== Список диалогов ===== -->
            <aside class="chat-sidebar">
                <div class="chat-sidebar-header">
                    <h2>Вопросы и ответы</h2>
                    <p>Общайтесь с "Чистой водой"</p>
                </div>

                <div class="chat-list">
                    <?php if (!$allQuestions): ?>
                        <div class="chat-empty" style="padding: 2rem 1rem; font-size: 1.4rem;">
                            Пока нет вопросов.<br>Задайте первый!
                        </div>
                    <?php else: ?>
                        <?php foreach ($allQuestions as $q): ?>
                            <?php
                                $hasAnswer = !empty($q['admin_comment']);
                                $isMine    = $isLogged && ((int)$q['author_id'] === (int)$_SESSION['user']['id']);
                                $isActive  = ((int)$q['id'] === $activeChatId);
                            ?>
                            <a class="chat-list-item <?= $isActive ? 'active' : '' ?>"
                               href="index7.php?chat=<?= (int)$q['id'] ?>#chat">
                                <div class="chat-list-subject">
                                    <?= $isMine ? ' ' : '' ?><?= e($q['subject']) ?>
                                </div>
                                <div class="chat-list-preview">
                                    <?php if ($hasAnswer): ?>
                                        <b>Ответ:</b> <?= e(mb_substr($q['admin_comment'], 0, 60)) ?>…
                                    <?php else: ?>
                                        <?= e(mb_substr($q['message'], 0, 60)) ?>…
                                    <?php endif; ?>
                                </div>
                                <div class="chat-list-meta">
                                    <span><?= e($q['user_name']) ?></span>
                                    <span class="chat-badge <?= $hasAnswer ? 'chat-badge-answered' : 'chat-badge-pending' ?>">
                                        <?= $hasAnswer ? 'Отвечен' : 'Ожидает' ?>
                                    </span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </aside>

            <!-- ===== Окно чата ===== -->
            <main class="chat-main">
                <?php if ($activeChat): ?>
                    <?php
                        $isMineChat = $isLogged && ((int)$activeChat['author_id'] === (int)$_SESSION['user']['id']);
                        $hasAnswer  = !empty($activeChat['admin_comment']);
                        $initials   = mb_strtoupper(mb_substr($activeChat['user_name'], 0, 1, 'UTF-8'), 'UTF-8');
                    ?>

                    <!-- Шапка -->
                    <div class="chat-main-header">
                        <div>
                            <h3><?= e($activeChat['subject']) ?></h3>
                            <div class="chat-author">
                                <?= $isMineChat ? 'Ваш вопрос' : 'Вопрос от ' . e($activeChat['user_name']) ?>
                                · <?= e(date('d.m.Y H:i', strtotime($activeChat['created_at']))) ?>
                            </div>
                        </div>
                        <span class="chat-badge <?= $hasAnswer ? 'chat-badge-answered' : 'chat-badge-pending' ?>">
                            <?= $hasAnswer ? ' Отвечен' : ' Ожидает ответа' ?>
                        </span>
                    </div>

                    <!-- Лента сообщений -->
                    <div class="chat-messages" id="chatMessages">

                        <!-- Сообщение пользователя (вопрос) -->
                        <div class="chat-msg <?= $isMineChat ? 'chat-msg-mine' : 'chat-msg-other' ?>">
                            <div class="chat-msg-avatar chat-msg-avatar-user">
                                <?= e($initials) ?>
                            </div>
                            <div class="chat-msg-body">
                                <div class="chat-msg-author">
                                    <?= $isMineChat ? 'Вы' : e($activeChat['user_name']) ?>
                                </div>
                                <div class="chat-msg-bubble">
                                    <?= nl2br(e($activeChat['message'])) ?>
                                </div>
                                <div class="chat-msg-time">
                                    <?= e(date('d.m.Y H:i', strtotime($activeChat['created_at']))) ?>
                                </div>
                            </div>
                        </div>

                        <!-- Ответ админа -->
                        <?php if ($hasAnswer): ?>
                            <div class="chat-msg chat-msg-other chat-msg-admin">
                                <div class="chat-msg-avatar chat-msg-avatar-admin">И</div>
                                <div class="chat-msg-body">
                                    <div class="chat-msg-author">ОАО «Исток» · Администратор</div>
                                    <div class="chat-msg-bubble">
                                        <?= nl2br(e($activeChat['admin_comment'])) ?>
                                    </div>
                                    <div class="chat-msg-time">
                                        Ответ получен
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="chat-msg chat-msg-other chat-msg-admin">
                                <div class="chat-msg-avatar chat-msg-avatar-admin">И</div>
                                <div class="chat-msg-body">
                                    <div class="chat-msg-author">ОАО «Исток» · Администратор</div>
                                    <div class="chat-msg-bubble" style="font-style: italic; opacity: .8;">
                                         Администратор ещё не ответил на этот вопрос…
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                    </div>

                    <!-- Форма ввода (только для авторизованных) -->
                    <?php if ($isLogged): ?>
                        <div class="chat-input-area">
                            <form class="chat-input-form" method="post"
                                  action="index7.php#chat" id="chatForm">
                                <input type="hidden" name="action" value="ask_question">
                                <input type="hidden" name="subject"
                                       value="<?= e(mb_substr($activeChat['subject'], 0, 50)) ?>">
                                <textarea name="question"
                                          id="chatTextarea"
                                          placeholder="Напишите сообщение…"
                                          required></textarea>
                                <button type="submit" class="chat-send-btn" id="chatSendBtn">
                                    <span>Отправить</span>
                                    <span class="send-text">➤</span>
                                </button>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="chat-guest">
                            <p>Чтобы задать вопрос и общаться с администратором, войдите в личный кабинет.</p>
                            <a href="login.php" class="btn-red">Войти</a>
                            <a href="registration.php" class="btn-outline">Регистрация</a>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <!-- Нет ни одного чата -->
                    <div class="chat-empty" style="flex: 1; display: flex; flex-direction: column; justify-content: center;">
                        <p style="font-size: 1.8rem; margin-bottom: 1rem;">💬</p>
                        <p>Пока нет ни одного вопроса.</p>
                        <p>Станьте первым — задайте вопрос в форме ниже!</p>
                    </div>
                <?php endif; ?>
            </main>

        </div>

        <!-- ===== Форма нового вопроса (если нет активного чата или для нового) ===== -->
        <?php if ($isLogged): ?>
            <div style="margin-top: 2rem; background: #fff; border-radius: 2rem;
                        padding: 2rem 2.5rem; box-shadow: 0 0.6rem 2rem rgba(0,99,168,.08);">
                <h3 style="font-size: 1.8rem; color: #0063a8; margin-bottom: 1.5rem;">
                    ✏ Задать новый вопрос
                </h3>
                <form method="post" action="index7.php#chat"
                      style="display: flex; flex-direction: column; gap: 1.2rem;">
                    <input type="hidden" name="action" value="ask_question">
                    <input type="text" name="subject" placeholder="Тема вопроса"
                           required
                           style="padding: 1.2rem 1.6rem; border: 2px solid #e1e8f0;
                                  border-radius: 1.2rem; font-family: inherit; font-size: 1.5rem;
                                  background: #f8fafc;">
                    <textarea name="question" placeholder="Опишите ваш вопрос подробно…"
                              required
                              style="min-height: 10rem; padding: 1.2rem 1.6rem;
                                     border: 2px solid #e1e8f0; border-radius: 1.2rem;
                                     font-family: inherit; font-size: 1.5rem; resize: vertical;
                                     background: #f8fafc;"></textarea>
                    <button type="submit" class="btn-red"
                            style="align-self: flex-start;">
                        Отправить вопрос
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>

<?php require 'footer.php'; ?>

<script>
// Автопрокрутка чата вниз
document.addEventListener('DOMContentLoaded', function () {
    const messages = document.getElementById('chatMessages');
    if (messages) {
        messages.scrollTop = messages.scrollHeight;
    }

    // Отправка по Ctrl+Enter
    const textarea = document.getElementById('chatTextarea');
    if (textarea) {
        textarea.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) {
                e.preventDefault();
                document.getElementById('chatForm').submit();
            }
        });

        // Автовысота
        textarea.addEventListener('input', function () {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 120) + 'px';
        });
    }
});
</script>
</body>
</html>