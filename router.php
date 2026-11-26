<?php
// Роутер для встроенного сервера PHP: запрещает скачивать базу данных и служебные файлы по адресу сайта.
// Запуск: php -S localhost:8000 router.php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (preg_match('#^/(data|\.git)(/|$)#', $path) || preg_match('#\.sqlite$#i', $path)) {
    http_response_code(403);
    echo 'Forbidden';
    return true;
}
return false;   // всё остальное обрабатывается как обычно