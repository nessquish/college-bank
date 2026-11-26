<?php
declare(strict_types=1);

/** Экранирование данных перед выводом в HTML (защита от XSS). */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Длина строки в символах (без расширения mbstring). */
function str_len(string $value): int
{
    $n = preg_match_all('/./us', $value);
    return $n === false ? strlen($value) : $n;
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

// ---------- CSRF ----------
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(?string $token): bool
{
    return is_string($token)
        && isset($_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], $token);
}

// ---------- Одноразовые сообщения ----------
function flash_set(string $message): void
{
    $_SESSION['flash'] = $message;
}

function flash_get(): ?string
{
    $m = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $m;
}

// ---------- Пользователь и роли ----------
/** Текущий пользователь из БД по id, сохранённому в сессии (или null для гостя). */
function current_user(): ?array
{
    global $pdo;
    static $cache = false;
    if ($cache !== false) {
        return $cache;
    }
    $id = $_SESSION['user_id'] ?? null;
    if (!is_int($id)) {
        return $cache = null;
    }
    $stmt = $pdo->prepare('SELECT id, name, email, created_at FROM users WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $user = $stmt->fetch();
    if (!$user) {
        // учётной записи больше нет — сбрасываем данные входа
        unset($_SESSION['user_id']);
        return $cache = null;
    }
    return $cache = $user;
}

/** Доступ только для авторизованных: гостя перенаправляем на страницу входа. */
function require_login(): array
{
    $user = current_user();
    if ($user === null) {
        redirect('login.php');
    }
    return $user;
}

/** Страницы входа и регистрации не нужны уже вошедшему пользователю. */
function require_guest(): void
{
    if (current_user() !== null) {
        redirect('dashboard.php');
    }
}

/** Количество предложений пользователя (по таблице offers). */
function offers_count(int $userId): int
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM offers WHERE user_id = :id');
    $stmt->execute([':id' => $userId]);
    return (int) $stmt->fetchColumn();
}

/**
 * Роль определяется автоматически: «Автор» — есть хотя бы одно своё
 * предложение, иначе «Пользователь». Гость — вход не выполнен.
 */
function user_role(?int $userId): string
{
    if ($userId === null) {
        return 'Гость';
    }
    return offers_count($userId) > 0 ? 'Автор' : 'Пользователь';
}

/** Дата из БД (UTC) в новосибирском времени. */
function format_date(string $utc): string
{
    $d = new DateTime($utc, new DateTimeZone('UTC'));
    $d->setTimezone(new DateTimeZone(date_default_timezone_get()));
    return $d->format('d.m.Y');
}
