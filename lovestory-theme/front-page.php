<?php
/**
 * Front page template.
 */
get_header();
?>



<header class="header">
<?php if (isset($_GET['profile_deleted']) && $_GET['profile_deleted'] === '1') : ?>
  <div class="site-notice site-notice--success" data-site-notice>
    <p class="site-notice__text">
      Ваш профиль и анкета удалены. Они больше не отображаются на сайте.
    </p>

    <button
      class="site-notice__close"
      type="button"
      data-site-notice-close
      aria-label="Закрыть уведомление"
    >
      ×
    </button>
  </div>
<?php endif; ?>
    <div class="container header__inner">
      <a class="logo header__logo" href="<?php echo esc_url(home_url('/')); ?>" aria-label="LoveStory — на главную">
        <span class="logo__icon" aria-hidden="true"></span>
        <span class="logo__text">LoveStory</span>
      </a>

      <nav class="nav header__nav header-nav" aria-label="Основная навигация" data-mobile-menu>
  <button
    class="header-nav__toggle"
    type="button"
    aria-expanded="false"
    aria-label="Открыть меню"
    data-mobile-menu-toggle
  >
    <span class="header-nav__toggle-line"></span>
    <span class="header-nav__toggle-line"></span>
    <span class="header-nav__toggle-line"></span>
  </button>

  <div class="header-nav__panel" data-mobile-menu-panel>
    <ul class="nav__list header-nav__list">
      <li class="nav__item"><a class="nav__link" href="#hero">Главная</a></li>
      <li class="nav__item"><a class="nav__link" href="#about">О нас</a></li>
      <li class="nav__item"><a class="nav__link" href="#features">Возможности</a></li>
      <li class="nav__item"><a class="nav__link" href="#stories">Истории</a></li>
      <li class="nav__item"><a class="nav__link" href="#pricing">Цены</a></li>
      <li class="nav__item"><a class="nav__link" href="#contacts">Контакты</a></li>
    </ul>
  </div>
</nav>


      <?php
    /* Вывод колличества полученых сообщений */
$current_user_id = get_current_user_id();

$header_unread_messages_count = 0;

  if (is_user_logged_in() && function_exists('dating_get_total_unread_messages_count')) {
      $header_unread_messages_count = dating_get_total_unread_messages_count($current_user_id);
  }
  ?>



      
       <div class="header__actions">
  <?php if (is_user_logged_in()) : ?>

    <a
        class="site-header__messages"
        href="<?php echo esc_url(home_url('/messages/')); ?>"
        title="<?php echo $header_unread_messages_count > 0 ? esc_attr('Новые сообщения') : esc_attr('Сообщения'); ?>"
        aria-label="<?php echo $header_unread_messages_count > 0 ? esc_attr('Новые сообщения') : esc_attr('Сообщения'); ?>"
        data-header-messages-link
    >
        <span class="site-header__messages-icon" aria-hidden="true">✉</span>

        <span
            class="site-header__messages-badge"
            data-header-messages-badge
            <?php echo $header_unread_messages_count > 0 ? '' : 'hidden'; ?>
        >
            <?php echo esc_html($header_unread_messages_count); ?>
        </span>
    </a>

    <?php
    $current_user = wp_get_current_user();
    $user_email = $current_user->user_email;
    $user_nickname = strstr($user_email, '@', true);

    if (!$user_nickname) {
        $user_nickname = $current_user->display_name;
    }
    ?>

    <a class="header-user" href="<?php echo esc_url(home_url('/my-profile/')); ?>">
        <span class="header-user__icon" aria-hidden="true">👤</span>

        <span class="header-user__name">
            <?php echo esc_html($user_nickname); ?>
        </span>
    </a>

  <?php else : ?>

    <?php echo do_shortcode('[dating_register_modal]'); ?>

    <button class="header__login" type="button" data-login-open>
        Войти
    </button>

  <?php endif; ?>
</div>

   
    

    </div>
  </header>

  <main class="main">
    <section class="hero" id="hero" aria-labelledby="hero-title">
      <div class="container hero__inner">
        <div class="hero__content">
          <h1 class="hero__title" id="hero-title">Место, где рождаются <span class="hero__title-accent">чувства</span></h1>
          <span class="divider divider--left" aria-hidden="true"></span>
          <p class="hero__text">
            Мы помогаем людям находить особенные встречи и создавать незабываемые истории любви.
          </p>

          <div class="hero__actions">
            <a class="button" href="#">Начать свою историю ♡</a>
            <a class="hero__video-link" href="#" aria-label="Смотреть видео о LoveStory">▶ Смотреть видео</a>
          </div>
        </div>

        <section class="features hero__features" id="features" aria-labelledby="features-title">
          <h2 class="visually-hidden" id="features-title">Возможности сервиса</h2>

          <article class="features__item feature-card">
            <span class="feature-card__icon feature-card__icon--match" aria-hidden="true"></span>
            <h3 class="feature-card__title">Идеальные совпадения</h3>
            <p class="feature-card__text">Умные алгоритмы помогают находить людей, с которыми вам по пути.</p>
          </article>

          <article class="features__item feature-card">
            <span class="feature-card__icon feature-card__icon--security" aria-hidden="true"></span>
            <h3 class="feature-card__title">Безопасность и комфорт</h3>
            <p class="feature-card__text">Мы заботимся о вашей безопасности и создаём доверительную атмосферу.</p>
          </article>

          <article class="features__item feature-card">
            <span class="feature-card__icon feature-card__icon--communication" aria-hidden="true"></span>
            <h3 class="feature-card__title">Лёгкое общение</h3>
            <p class="feature-card__text">Удобные инструменты для общения и сближения без границ.</p>
          </article>

          <article class="features__item feature-card">
            <span class="feature-card__icon feature-card__icon--moments" aria-hidden="true"></span>
            <h3 class="feature-card__title">Особенные моменты</h3>
            <p class="feature-card__text">Идеи для свиданий и подарков, чтобы каждый момент был незабываемым.</p>
          </article>
        </section>
      </div>
    </section>

    <section class="stories section" id="stories" aria-labelledby="stories-title">
      <div class="container">
        <h2 class="section__title" id="stories-title">Истории, которые вдохновляют</h2>

        <div class="stories__list">
          <article class="story-card">
            <img class="story-card__image" src="<?php echo esc_url(get_template_directory_uri() . '/img/pics/our_fate.png'); ?>"  alt="Счастливая пара Анна и Сергей">
            <div class="story-card__content">
              <h3 class="story-card__title">«Мы встретились здесь и поняли, что это судьба»</h3>
              <p class="story-card__author">Анна и Сергей ♡</p>
            </div>
          </article>

          <article class="story-card">
            <img class="story-card__image" src="<?php echo esc_url(get_template_directory_uri() . '/img/pics/real_happiness.png'); ?>" alt="Счастливая пара Мария и Игорь">
            <div class="story-card__content">
              <h3 class="story-card__title">«Спасибо за шанс на настоящее счастье»</h3>
              <p class="story-card__author">Мария и Игорь ♡</p>
            </div>
          </article>

          <article class="story-card">
            <img class="story-card__image" src="<?php echo esc_url(get_template_directory_uri() . '/img/pics/Story_to_retell.png'); ?>" alt="Счастливая пара Ольга и Дмитрий">
            <div class="story-card__content">
              <h3 class="story-card__title">«История, о которой мы будем рассказывать детям»</h3>
              <p class="story-card__author">Ольга и Дмитрий ♡</p>
            </div>
          </article>
        </div>
      </div>
    </section>

    <section class="about" id="about" aria-labelledby="about-title">
      <div class="container about__inner">
        <div class="about__media" aria-hidden="true"></div>

        <div class="about__content">
          <p class="about__eyebrow">Наш сервис ♡</p>
          <h2 class="about__title" id="about-title">Соединяем сердца и создаём истории</h2>
          <p class="about__text">
            LoveStory — это больше, чем знакомства. Это пространство для искренних чувств, тёплого общения и незабываемых моментов.
          </p>

          <ul class="about__list">
            <li class="about__item">Персональные рекомендации</li>
            <li class="about__item">Проверенные профили</li>
            <li class="about__item">Поддержка 24/7</li>
          </ul>

          <a class="button" href="#">Узнать больше →</a>
        </div>
      </div>
    </section>

    <section class="cta" id="pricing" aria-labelledby="cta-title">
      <div class="container">
        <div class="cta__box">
          <h2 class="cta__title" id="cta-title">Начни свою историю уже сегодня</h2>
          <span class="divider divider--center" aria-hidden="true"></span>
          <p class="cta__text">Не откладывай счастье на потом — твоя история может начаться прямо сейчас.</p>
          <a class="button" href="#">Присоединиться ♡</a>
        </div>
      </div>
    </section>
  </main>


<?php if (!is_user_logged_in()) : ?>
    <div class="login-modal" data-login-modal hidden>
        <div class="login-modal__overlay" data-login-close></div>

        <div class="login-modal__content" role="dialog" aria-modal="true" aria-labelledby="login-modal-title">
            <button class="login-modal__close" type="button" data-login-close aria-label="Закрыть">
                ×
            </button>

            <h2 class="login-modal__title" id="login-modal-title">
                Вход
            </h2>

            <form class="login-form" method="post">
                <?php wp_nonce_field('dating_login_action', 'dating_login_nonce'); ?>

                <label class="login-form__field">
                    <span class="login-form__label">Логин</span>
                    <input
                        class="login-form__input"
                        type="text"
                        name="dating_login"
                        autocomplete="username"
                        required
                    >
                </label>

                <label class="login-form__field">
                    <span class="login-form__label">Пароль</span>
                    <input
                        class="login-form__input"
                        type="password"
                        name="dating_password"
                        autocomplete="current-password"
                        required
                    >
                </label>

                <label class="login-form__remember">
                    <input
                        class="login-form__checkbox"
                        type="checkbox"
                        name="dating_remember"
                        value="1"
                    >

                    <span>Запомнить меня на этом компьютере</span>
                </label>

                <button class="login-form__submit" type="submit" name="dating_login_submit">
                    Войти
                </button>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php get_footer(); ?>
