<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$user = current_user();
$role = user_role($user ? (int) $user['id'] : null);

page_header('Главная');
?>
<div class="art" aria-hidden="true"><?php include __DIR__ . '/hero_art.php'; ?></div>
<main class="stage">
    <section class="home">
        <div class="eyebrow">Сервис взаимопомощи студентов</div>
        <h1 class="dsp">Твои навыки —<br><span style="white-space:nowrap">чья-то</span> <em>помощь.</em></h1>
        <p class="mu lead">Обменивайся знаниями, помогай другим и находи поддержку среди студентов колледжа.</p>
<?php if ($user): ?>
        <p class="hello">Вы вошли как <b><?= e($user['name']) ?></b> · роль: <?= e($role) ?></p>
        <div class="row-btn">
            <a class="btn pr" href="dashboard.php">Личный кабинет <?= icon('arrow') ?></a>
        </div>
<?php else: ?>
        <div class="row-btn">
            <a class="btn pr" href="register.php">Зарегистрироваться <?= icon('arrow') ?></a>
            <a class="btn" href="login.php">Войти в аккаунт</a>
        </div>
<?php endif; ?>
    </section>
</main>
<?php page_footer();
