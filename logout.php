<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

// Выход выполняется только POST-запросом с проверкой CSRF-токена.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}
if (!verify_csrf($_POST['csrf'] ?? null)) {
    http_response_code(400);
    exit('Недействительный токен формы.');
}

$_SESSION = [];                                   // очистить данные сессии
if (ini_get('session.use_cookies')) {             // удалить cookie сессии
    $p = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires'  => time() - 42000,
        'path'     => $p['path'],
        'domain'   => $p['domain'],
        'secure'   => $p['secure'],
        'httponly' => $p['httponly'],
        'samesite' => $p['samesite'] ?: 'Lax',
    ]);
}
session_destroy();                                // завершить сессию

redirect('index.php');
