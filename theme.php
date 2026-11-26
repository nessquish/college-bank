<?php
declare(strict_types=1);

// Сохраняет выбор темы в cookie и возвращает на страницу, откуда пришёл пользователь.
$set  = $_GET['set'] ?? '';
$back = $_GET['back'] ?? 'index.php';

if (in_array($set, ['light', 'dark'], true)) {
    setcookie('theme', $set, [
        'expires'  => time() + 365 * 24 * 3600,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => false,   // нужна скрипту, который сбрасывает выбор при смене темы браузера
        'samesite' => 'Lax',
    ]);
}

// Переход только на известные страницы сайта.
$pages = ['index.php', 'login.php', 'register.php', 'dashboard.php'];
header('Location: ' . (in_array($back, $pages, true) ? $back : 'index.php'));
exit;
