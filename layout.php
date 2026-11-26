<?php
declare(strict_types=1);

/**
 * Общая шапка страницы.
 * $nav: 'main' — обычная навигация, 'auth' — упрощённая (окна входа и регистрации).
 */
function page_header(string $title, string $nav = 'main'): void
{
    $user = current_user();
    ?>
<!doctype html>
<html lang="ru"<?= in_array($_COOKIE['theme'] ?? '', ['light', 'dark'], true) ? ' data-theme="' . e($_COOKIE['theme']) . '"' : '' ?>>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="dark light">
<title><?= e($title) ?> — Колледж.Банк</title>
<link rel="stylesheet" href="style.css">
<script>
/* Смена темы в браузере/системе сразу применяется на сайте:
   ручной выбор сбрасывается, и снова действует тема браузера. */
matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function () {
    document.cookie = 'theme=; path=/; max-age=0; samesite=lax';
    document.documentElement.removeAttribute('data-theme');
});
/* При включённом «уменьшении движения» анимация внутри SVG останавливается. */
if (matchMedia('(prefers-reduced-motion: reduce)').matches) {
    addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.art svg').forEach(function (s) { s.pauseAnimations(); });
    });
}
</script>
</head>
<body>
<?php
// Фаза анимации фона считается от времени: при переходе между страницами
// переливы продолжаются с того же места, а не начинаются заново.
$tm = microtime(true);
$bgStyle = sprintf('--d1:-%.2fs;--d2:-%.2fs;--d3:-%.2fs', fmod($tm, 38), fmod($tm, 50), fmod($tm, 44));
?>
<div class="bg" aria-hidden="true" style="<?= $bgStyle ?>"><i class="g1"></i><i class="g2"></i><i class="g3"></i></div>
<div class="wrap">
<header class="nav">
    <a class="logo" href="index.php">колледж<i>.</i>банк<i>.</i></a>
<?php if ($nav === 'auth'): ?>
    <div class="acts"><?= theme_toggle() ?><a class="btn sm" href="index.php">На главную</a></div>
<?php else: ?>
<?php if ($user): ?>
    <nav class="links" aria-label="Основное меню">
        <a href="index.php">Главная</a>
        <a href="dashboard.php">Личный кабинет</a>
    </nav>
<?php endif; ?>
    <div class="acts">
        <?= theme_toggle() ?>
<?php if ($user): ?>
        <span class="who"><?= e($user['name']) ?></span>
        <form method="post" action="logout.php">
            <?= csrf_field() ?>
            <button class="btn sm" type="submit">Выйти</button>
        </form>
<?php else: ?>
        <a class="btn sm" href="login.php">Войти</a>
        <a class="btn pr sm" href="register.php">Регистрация</a>
<?php endif; ?>
    </div>
<?php endif; ?>
</header>
<?php
}

function page_footer(): void
{
    ?>
</div>
</body>
</html>
<?php
}

/**
 * Переключатель темы без JavaScript. Выбор хранится в cookie (theme.php).
 * Пока выбор не сделан, тема берётся из настроек браузера/системы (prefers-color-scheme).
 * Две ссылки: CSS показывает ту, что ведёт на противоположную текущей теме.
 */
function theme_toggle(): string
{
    return '<a class="ib t-to-light" href="theme.php?set=light&amp;back=' . e(basename(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: 'index.php')) . '" aria-label="Включить светлую тему" title="Светлая тема">' . icon('sun') . '</a>'
         . '<a class="ib t-to-dark" href="theme.php?set=dark&amp;back=' . e(basename(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: 'index.php')) . '" aria-label="Включить тёмную тему" title="Тёмная тема">' . icon('moon') . '</a>';
}

/** Иконки полей (линейные, без заливки). */
function icon(string $name): string
{
    $paths = [
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21c1-4 4-6 8-6s7 2 8 6"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="3"/><path d="M3.5 7l8.5 6 8.5-6"/>',
        'lock' => '<rect x="5" y="11" width="14" height="10" rx="3"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/>',
        'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
        'moon' => '<path d="M20 14.5A8 8 0 0 1 9.5 4 8 8 0 1 0 20 14.5z"/>',
        'arrow' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
    ];
    return '<svg class="i" viewBox="0 0 24 24" aria-hidden="true">' . ($paths[$name] ?? '') . '</svg>';
}

/** Поле формы: подпись, иконка, ввод и сообщение об ошибке под полем. */
function field(string $name, string $label, string $type, string $icon, string $placeholder, string $value, ?string $error, string $autocomplete): void
{
    $id = 'f-' . $name;
    ?>
<div class="fl">
    <label for="<?= e($id) ?>"><?= e($label) ?></label>
    <div class="in<?= $error ? ' bad' : '' ?>">
        <?= icon($icon) ?>
        <input id="<?= e($id) ?>" name="<?= e($name) ?>" type="<?= e($type) ?>"
               value="<?= e($value) ?>" placeholder="<?= e($placeholder) ?>"
               autocomplete="<?= e($autocomplete) ?>"
               <?= $error ? 'aria-invalid="true" aria-describedby="' . e($id) . '-err"' : '' ?>>
    </div>
<?php if ($error): ?>
    <p class="err" id="<?= e($id) ?>-err"><?= e($error) ?></p>
<?php endif; ?>
</div>
<?php
}
