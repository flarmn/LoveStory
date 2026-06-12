<?php
/**
 * 404 template.
 *
 * Shows a branded LoveStory page when the requested page does not exist.
 */

get_header();
?>

<style>
  .lovestory-404 {
    position: relative;
    min-height: calc(100vh - 120px);
    padding: clamp(24px, 4vw, 56px) 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    color: #fff7ff;
    background:
      radial-gradient(circle at 50% 18%, rgba(255, 143, 171, 0.26), transparent 34%),
      linear-gradient(135deg, #100d3a 0%, #3d155f 52%, #120824 100%);
  }

  .lovestory-404__container {
    width: min(100%, 1180px);
    display: grid;
    gap: 24px;
    justify-items: center;
  }

  .lovestory-404__visual {
    width: min(100%, 1100px);
    margin: 0;
    border: 1px solid rgba(239, 184, 92, 0.24);
    border-radius: clamp(22px, 3vw, 42px);
    overflow: hidden;
    background: rgba(255, 255, 255, 0.04);
    box-shadow: 0 28px 80px rgba(5, 3, 26, 0.42);
  }

  .lovestory-404__image {
    display: block;
    width: 100%;
    height: auto;
  }

  .lovestory-404__content {
    width: min(100%, 760px);
    display: grid;
    gap: 16px;
    justify-items: center;
    text-align: center;
  }

  .lovestory-404__title,
  .lovestory-404__text {
    position: absolute;
    width: 1px;
    height: 1px;
    margin: -1px;
    padding: 0;
    overflow: hidden;
    clip: rect(0 0 0 0);
    white-space: nowrap;
    border: 0;
  }

  .lovestory-404__actions {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    justify-content: center;
  }

  .lovestory-404__button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 48px;
    padding: 12px 24px;
    border: 1px solid rgba(239, 184, 92, 0.72);
    border-radius: 999px;
    color: #ffc861;
    background: rgba(255, 255, 255, 0.035);
    font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
    font-size: 15px;
    font-weight: 700;
    line-height: 1.2;
    text-decoration: none;
    transition:
      border-color 0.2s ease,
      color 0.2s ease,
      background 0.2s ease,
      box-shadow 0.2s ease,
      transform 0.2s ease;
  }

  .lovestory-404__button:hover,
  .lovestory-404__button:focus-visible {
    color: #1b1235;
    border-color: #ffc861;
    background: linear-gradient(135deg, #ffd36b, #ff8fab);
    box-shadow: 0 12px 30px rgba(255, 143, 171, 0.26);
    outline: none;
    transform: translateY(-1px);
  }

  .lovestory-404__button--secondary {
    color: #fff7ff;
    border-color: rgba(255, 247, 255, 0.28);
  }

  @media (max-width: 768px) {
    .lovestory-404 {
      min-height: calc(100vh - 80px);
      padding: 18px 12px 28px;
    }

    .lovestory-404__container {
      gap: 18px;
    }

    .lovestory-404__visual {
      border-radius: 24px;
    }

    .lovestory-404__actions {
      width: 100%;
    }

    .lovestory-404__button {
      flex: 1 1 220px;
      max-width: 320px;
    }
  }

  @media (max-width: 420px) {
    .lovestory-404 {
      padding-inline: 10px;
    }

    .lovestory-404__visual {
      border-radius: 18px;
    }

    .lovestory-404__actions {
      gap: 10px;
    }

    .lovestory-404__button {
      width: 100%;
      max-width: none;
      min-height: 46px;
      padding-inline: 18px;
      font-size: 14px;
    }
  }

  /*
  @media (max-width: 640px) and (orientation: portrait) {
  .lovestory-404__visual {
    min-height: 52vh;
    overflow: hidden;
  }

  .lovestory-404__image {
    width: auto;
    max-width: none;
    height: 52vh;
    object-fit: cover;
    object-position: center center;
  }
}
  */

  @media (max-width: 640px) and (orientation: portrait) {
  .lovestory-404__visual {
    position: relative;
    min-height: 60vh;
    overflow: hidden;
  }

  .lovestory-404__image {
    position: absolute;
    top: 0;
    left: 50%;
    width: auto;
    max-width: none;
    height: 60vh;
    object-fit: cover;
    object-position: center center;
    transform: translateX(-50%);
  }
}
</style>

<main class="lovestory-404" aria-labelledby="lovestory-404-title">
  <section class="lovestory-404__container" aria-describedby="lovestory-404-text">
    <figure class="lovestory-404__visual">
      <img
        class="lovestory-404__image"
        src="<?php echo esc_url(get_template_directory_uri() . '/img/pics/404_img.png'); ?>"
        alt="Иллюстрация ошибки 404: страница не найдена"
      >
    </figure>

    <div class="lovestory-404__content">
      <h1 class="lovestory-404__title" id="lovestory-404-title">
        Страница не найдена
      </h1>

      <p class="lovestory-404__text" id="lovestory-404-text">
        Мы не смогли найти такую страницу. Похоже, она не существует.
      </p>

      <nav class="lovestory-404__actions" aria-label="Действия на странице 404">
        <a class="lovestory-404__button" href="<?php echo esc_url(home_url('/')); ?>">
          Вернуться на главную ♡
        </a>

        <a class="lovestory-404__button lovestory-404__button--secondary" href="<?php echo esc_url(home_url('/find-match/')); ?>">
          Перейти к поиску анкет
        </a>
      </nav>
    </div>
  </section>
</main>

<?php
get_footer();
