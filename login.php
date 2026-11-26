<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

require_guest();

// Хеш-заглушка: пароль проверяется даже для несуществующей почты,
// чтобы время ответа не выдавало, есть ли такой адрес в системе.
const DUMMY_HASH = '$2y$12$FbxbpH5PRdpfiPirPXpgcuqtYH8qIXD16YMK5HYjeh5hTh.80v8wK';

$errors = [];
$email = '';
$notice = flash_get();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? null)) {
        $errors['form'] = 'Форма устарела. Обновите страницу и попробуйте ещё раз.';
    } else {
        $email    = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');

        if ($email === '') {
            $errors['email'] = 'Введите адрес электронной почты';
        }
        if ($password === '') {
            $errors['password'] = 'Введите пароль';
        }

        if (!$errors) {
            $stmt = $pdo->prepare('SELECT id, password_hash FROM users WHERE email = :email');
            $stmt->execute([':email' => $email]);
            $account = $stmt->fetch();

            $ok = password_verify($password, $account['password_hash'] ?? DUMMY_HASH);

            if ($account && $ok) {
                session_regenerate_id(true);          // новый id сессии после входа
                $_SESSION['user_id'] = (int) $account['id'];
                unset($_SESSION['csrf']);             // новый токен для новой сессии
                redirect('dashboard.php');
            }
            // Общее сообщение: не уточняем, что именно введено неверно.
            $errors['form'] = 'Неверная электронная почта или пароль';
        }
    }
}

page_header('Вход', 'auth');
?>
<main class="auth">
    <section class="hero">
        <h1 class="dsp">Рады видеть тебя <em>снова</em></h1>
        <p class="mu lead">Войди, чтобы публиковать предложения, откликаться на чужие и следить за своими заявками.</p>
    </section>

    <section class="card form-card" aria-labelledby="t">
        <div class="tabs" role="navigation" aria-label="Вход или регистрация">
            <a class="tab on" href="login.php" aria-current="page">Вход</a>
            <a class="tab" href="register.php">Регистрация</a>
        </div>
        <div>
            <h2 class="dsp" id="t">Войди в аккаунт</h2>
            <p class="mu sub">Введи почту и пароль, указанные при регистрации</p>
        </div>
<?php if ($notice): ?>
        <p class="ok" role="status"><?= e($notice) ?></p>
<?php endif; ?>
<?php if (isset($errors['form'])): ?>
        <p class="alert" role="alert"><?= e($errors['form']) ?></p>
<?php endif; ?>
        <form method="post" action="login.php" novalidate>
            <?= csrf_field() ?>
            <?php field('email', 'Email', 'email', 'mail', 'name@example.com', $email, $errors['email'] ?? null, 'email'); ?>
            <?php field('password', 'Пароль', 'password', 'lock', 'Ваш пароль', '', $errors['password'] ?? null, 'current-password'); ?>
            <button class="btn pr wide" type="submit">Войти <?= icon('arrow') ?></button>
        </form>
        <p class="mu center small">Нет аккаунта? <a class="lk" href="register.php">Зарегистрироваться</a></p>
    </section>
</main>
<?php page_footer();
