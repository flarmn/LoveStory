<?php
/**
 * Template for the static About LoveStory page.
 *
 * WordPress automatically uses this file for a page with the slug "about".
 */

get_header();
?>

<main class="about-page" aria-labelledby="about-page-title">
  <section class="about-page__hero" aria-labelledby="about-page-title">
    <div class="about-page__hero-background" aria-hidden="true">
      <span class="about-page__hero-heart about-page__hero-heart--big">♡</span>
      <span class="about-page__hero-heart about-page__hero-heart--one">♥</span>
      <span class="about-page__hero-heart about-page__hero-heart--two">♥</span>
      <span class="about-page__hero-heart about-page__hero-heart--three">♥</span>
    </div>

    <div class="about-page__container">
      <nav class="about-page__breadcrumbs" aria-label="Хлебные крошки">
        <a class="about-page__breadcrumb-link" href="<?php echo esc_url(home_url('/')); ?>">
          Главная
        </a>
        <span class="about-page__breadcrumb-separator" aria-hidden="true">›</span>
        <span class="about-page__breadcrumb-current">О LoveStory</span>
      </nav>

      <div class="about-page__hero-content">
        <p class="about-page__eyebrow">О проекте LoveStory</p>

        <h1 class="about-page__title" id="about-page-title">
          О LoveStory
        </h1>

        <p class="about-page__lead">
          LoveStory — это пространство для искренних знакомств, где начинается ваша история любви.
        </p>
      </div>
    </div>
  </section>

  <section class="about-page__overview" aria-label="Описание проекта LoveStory">
    <div class="about-page__container">
      <div class="about-page__panel">
        <div class="about-page__left-column">
          <article class="about-page__mission" aria-labelledby="about-mission-title">
            <h2 class="about-page__section-title about-page__section-title--line" id="about-mission-title">
              Наша миссия
            </h2>

            <p class="about-page__mission-text">
              Мы создали LoveStory, чтобы помочь людям находить родственные души, строить серьёзные отношения и находить настоящее счастье.
            </p>
          </article>

          <article class="about-page__principles" aria-labelledby="about-principles-title">
            <div class="about-page__principles-content">
              <h2 class="about-page__section-title about-page__section-title--line" id="about-principles-title">
                Наши принципы
              </h2>

              <ul class="about-page__principles-list">
                <li class="about-page__principles-item">Искренность и уважение в каждом общении</li>
                <li class="about-page__principles-item">Безопасность и защита личных данных</li>
                <li class="about-page__principles-item">Честный поиск и реальные цели</li>
                <li class="about-page__principles-item">Современные технологии и удобство</li>
              </ul>
            </div>

            <div class="about-page__principles-visual" aria-hidden="true">
              <!-- span class="about-page__principles-heart">♡</span -->
            </div>
          </article>
        </div>

        <section class="about-page__benefits" aria-labelledby="about-benefits-title">
          <h2 class="about-page__section-title about-page__section-title--center" id="about-benefits-title">
            Почему выбирают LoveStory
          </h2>

          <div class="about-page__benefit-grid">
            <article class="about-page__benefit-card">
              <span class="about-page__benefit-icon" aria-hidden="true">♢</span>
              <h3 class="about-page__benefit-title">Безопасность</h3>
              <p class="about-page__benefit-text">Проверка анкет и защита от фейков</p>
            </article>

            <article class="about-page__benefit-card">
              <span class="about-page__benefit-icon" aria-hidden="true">▣</span>
              <h3 class="about-page__benefit-title">Конфиденциальность</h3>
              <p class="about-page__benefit-text">Ваши данные под надёжной защитой</p>
            </article>

            <article class="about-page__benefit-card">
              <span class="about-page__benefit-icon" aria-hidden="true">♡</span>
              <h3 class="about-page__benefit-title">Настоящие люди</h3>
              <p class="about-page__benefit-text">Реальные анкеты с реальными целями</p>
            </article>

            <article class="about-page__benefit-card">
              <span class="about-page__benefit-icon" aria-hidden="true">☵</span>
              <h3 class="about-page__benefit-title">Удобное общение</h3>
              <p class="about-page__benefit-text">Чат, сообщения и уведомления</p>
            </article>

            <article class="about-page__benefit-card">
              <span class="about-page__benefit-icon" aria-hidden="true">★</span>
              <h3 class="about-page__benefit-title">Расширенные возможности</h3>
              <p class="about-page__benefit-text">Фильтры, поиск и совместимость</p>
            </article>

            <article class="about-page__benefit-card">
              <span class="about-page__benefit-icon" aria-hidden="true">☎</span>
              <h3 class="about-page__benefit-title">Поддержка 24/7</h3>
              <p class="about-page__benefit-text">Мы всегда рядом, чтобы помочь</p>
            </article>
          </div>
        </section>
      </div>

      <section class="about-page__cta" aria-label="Начать знакомство">
        <blockquote class="about-page__quote">
          Каждая история любви начинается с первого шага.<br>
          Сделайте его вместе с LoveStory.
        </blockquote>

        <a class="about-page__cta-button" href="<?php echo esc_url(home_url('/find-match/')); ?>">
          <span aria-hidden="true">♥</span>
          Начать знакомство
          <span aria-hidden="true">›</span>
        </a>
      </section>
    </div>
  </section>
</main>

<?php
get_footer();
