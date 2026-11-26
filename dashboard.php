<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

// Проверка сессии на защищённой странице: гость уходит на login.php.
require_login();

page_header('Личный кабинет');
?>
<main class="stage center-stage">
    <section class="stub">
        <h1 class="dsp">Личный кабинет</h1>
        <p class="mu">(будет реализовано позже)</p>
    </section>
</main>
<?php page_footer();
