<?php
declare(strict_types=1);

// Часовой пояс отображения дат (в базе время хранится в UTC).
date_default_timezone_set('Asia/Novosibirsk');

// ---------- Сессия ----------
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');

session_set_cookie_params([
    'lifetime' => 0,          // cookie живёт до закрытия браузера
    'path'     => '/',
    'secure'   => $isHttps,   // Secure при HTTPS
    'httponly' => true,       // недоступна из JavaScript
    'samesite' => 'Lax',
]);
session_start();

// ---------- База данных SQLite ----------
$dataDir = __DIR__ . '/data';
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0775, true);
}

$pdo = new PDO('sqlite:' . $dataDir . '/college_bank.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$pdo->exec('PRAGMA foreign_keys = ON');

// Схема БД по заданию: users, categories, offers, requests.
$pdo->exec('
    CREATE TABLE IF NOT EXISTS users (
        id            INTEGER PRIMARY KEY AUTOINCREMENT,
        name          TEXT NOT NULL,
        email         TEXT NOT NULL UNIQUE,
        password_hash TEXT NOT NULL,
        created_at    TEXT NOT NULL DEFAULT (datetime(\'now\'))
    );
    CREATE TABLE IF NOT EXISTS categories (
        id   INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL UNIQUE
    );
    CREATE TABLE IF NOT EXISTS offers (
        id          INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id     INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        category_id INTEGER REFERENCES categories(id),
        title       TEXT NOT NULL,
        description TEXT NOT NULL DEFAULT \'\',
        status      TEXT NOT NULL DEFAULT \'active\'
    );
    CREATE TABLE IF NOT EXISTS requests (
        id         INTEGER PRIMARY KEY AUTOINCREMENT,
        offer_id   INTEGER NOT NULL REFERENCES offers(id) ON DELETE CASCADE,
        user_id    INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        message    TEXT NOT NULL DEFAULT \'\',
        status     TEXT NOT NULL DEFAULT \'new\',
        created_at TEXT NOT NULL DEFAULT (datetime(\'now\'))
    );
');

// Начальные категории (добавляются один раз).
if ((int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn() === 0) {
    $ins = $pdo->prepare('INSERT INTO categories (name) VALUES (:name)');
    foreach (['Программирование', 'Дизайн', 'Математика', 'Языки', 'Другое'] as $c) {
        $ins->execute([':name' => $c]);
    }
}
