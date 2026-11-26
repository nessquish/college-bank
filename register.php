<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

require_guest();

$errors = [];
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? null)) {
        $errors['form'] = 'Форма устарела. Обновите страницу и попробуйте ещё раз.';
    } else {
        $name     = trim((string) ($_POST['name'] ?? ''));
        $email    = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');

        // --- серверная проверка всех входных данных ---
        if ($name === '') {
            $errors['name'] = 'Введите имя';
        } elseif (str_len($name) < 2 || str_len($name) > 50) {
            $errors['name'] = 'Имя должно содержать от 2 до 50 символов';
        } elseif (preg_match('/[\x00-\x1F\x7F]/', $name)) {
            $errors['name'] = 'Имя содержит недопустимые символы';
        }

        if ($email === '') {
            $errors['email'] = 'Введите адрес электронной почты';
        } elseif (strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Введите корректный адрес электронной почты';
        }

        if ($password === '') {
            $errors['password'] = 'Введите пароль';
        } elseif (str_len($password) < 8) {
            $errors['password'] = 'Пароль должен содержать не менее 8 символов';
        } elseif (strlen($password) > 72) {
            $errors['password'] = 'Пароль слишком длинный (не более 72 байт)';
        }

        // --- запрет повторной регистрации одной почты ---
        if (!$errors) {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email');
            $stmt->execute([':email' => $email]);
            if ($stmt->fetch()) {
                $errors['email'] = 'Пользователь с такой электронной почтой уже существует';
            }
        }

        if (!$errors) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare(
                'INSERT INTO users (name, email, password_hash)
                 VALUES (:name, :email, :password_hash)'
            );
            try {
                $stmt->execute([
                    ':name'          => $name,
                    ':email'         => $email,
                    ':password_hash' => $hash,
                ]);
                flash_set('Регистрация завершена. Теперь войдите в аккаунт');
                redirect('login.php');
            } catch (PDOException $ex) {
                // гонка: такая почта уже появилась (UNIQUE-индекс)
                $errors['email'] = 'Пользователь с такой электронной почтой уже существует';
            }
        }
    }
}

page_header('Регистрация', 'auth');
?>
<main class="auth">
    <section class="hero">
        <h1 class="dsp">Присоединяйся к <em>сообществу</em></h1>
        <p class="mu lead">Публикуй предложения, откликайся на чужие и находи поддержку среди студентов колледжа.</p>
    </section>

    <section class="card form-card" aria-labelledby="t">
        <div class="tabs" role="navigation" aria-label="Вход или регистрация">
            <a class="tab" href="login.php">Вход</a>
            <a class="tab on" href="register.php" aria-current="page">Регистрация</a>
        </div>
        <div>
            <h2 class="dsp" id="t">Создай аккаунт</h2>
            <p class="mu sub">Это займёт меньше минуты</p>
        </div>
<?php if (isset($errors['form'])): ?>
        <p class="alert" role="alert"><?= e($errors['form']) ?></p>
<?php endif; ?>
        <form method="post" action="register.php" novalidate>
            <?= csrf_field() ?>
            <?php field('name', 'Имя', 'text', 'user', 'Как вас зовут', $name, $errors['name'] ?? null, 'name'); ?>
            <?php field('email', 'Email', 'email', 'mail', 'name@example.com', $email, $errors['email'] ?? null, 'email'); ?>
            <?php field('password', 'Пароль', 'password', 'lock', 'Минимум 8 символов', '', $errors['password'] ?? null, 'new-password'); ?>
            <button class="btn pr wide" type="submit">Зарегистрироваться <?= icon('arrow') ?></button>
        </form>
        <p class="mu center small">Уже есть аккаунт? <a class="lk" href="login.php">Войти</a></p>
    </section>
</main>
<?php page_footer();
