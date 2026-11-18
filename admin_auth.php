<?php
// admin_auth.php — проверка, что пользователь — админ
if (session_status() === PHP_SESSION_NONE) session_start();

// Учётные данные администратора
const ADMIN_LOGIN = 'Admin';
const ADMIN_PASS  = '12345678';

function isAdmin(): bool {
    return !empty($_SESSION['admin']);
}

function requireAdmin(): void {
    if (!isAdmin()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Хелпер: проверка статуса «выполнено»
 */
function isCompletedStatus(string $status): bool {
    return in_array($status, ['Выполнена', 'done', 'Банкет завершен'], true);
}

/**
 * Хелпер: русские названия типов заявок
 */
function reqTypeLabel(string $t): string {
    return [
        'repair'     => ' Ремонт',
        'connection' => ' Подключение',
        'question'   => ' Вопрос',
        'complaint'  => ' Жалоба',
    ][$t] ?? $t;
}

/**
 * Хелпер: статус заявки → [текст, css-класс]
 */
function reqStatus(string $s): array {
    return [
        'new'         => ['Новая',        'status-new'],
        'in_progress' => ['В работе',     'status-progress'],
        'done'        => ['Выполнена',    'status-done'],
        'rejected'    => ['Отклонена',    'status-rejected'],
    ][$s] ?? [$s, 'status-new'];
}