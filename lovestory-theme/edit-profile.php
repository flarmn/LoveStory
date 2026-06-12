<?php
/**
 * Template Name: Редактор анкеты
 */

get_header();

if (!is_user_logged_in()) {
    wp_safe_redirect(home_url('/'));
    exit;
}

$current_user_id = get_current_user_id();

$user_profiles = get_posts([
    'post_type'      => 'profile',
    'post_status'    => 'any',
    'author'         => $current_user_id,
    'posts_per_page' => 1,
    'orderby'        => 'date',
    'order'          => 'DESC',
]);

$profile_id = !empty($user_profiles) ? $user_profiles[0]->ID : 0;

if (!$profile_id) {
    ?>
    <main class="profile-editor-page">
        <section class="profile-editor">
            <div class="profile-editor__container">
                <h1 class="profile-editor__title">Анкета не найдена</h1>
                <p class="profile-editor__description">
                    У вашего аккаунта пока нет связанной анкеты.
                </p>
                <a class="profile-editor__preview-link" href="<?php echo esc_url(home_url('/my-profile/')); ?>">
                    Вернуться в личный кабинет
                </a>
            </div>
        </section>
    </main>
    <?php
    get_footer();
    exit;
}

$profile_status = get_post_meta($profile_id, 'profile_status', true);

if ($profile_status === 'deleted_by_user') {
    wp_safe_redirect(home_url('/'));
    exit;
}

$name = get_the_title($profile_id);

$age = get_post_meta($profile_id, 'age', true);
$country = get_post_meta($profile_id, 'country', true);
$city = get_post_meta($profile_id, 'city', true);
$short_intro = get_post_meta($profile_id, 'short_intro', true);
$about_me = get_post_meta($profile_id, 'about_me', true);
$looking_for = get_post_meta($profile_id, 'looking_for', true);
$relationship_goal = get_post_meta($profile_id, 'relationship_goal', true);
$profile_interests = get_post_meta($profile_id, 'profile_interests', true);
$profile_gallery = get_post_meta($profile_id, 'profile_gallery', true);

if (!is_array($profile_interests)) {
    $profile_interests = $profile_interests
        ? array_map('trim', explode(',', $profile_interests))
        : [];
}

/* Список интересов */

$default_interests = [
    'Путешествия',
    'Музыка',
    'Книги',
    'Природа',
    'Фотография',
    'Кино',
    'Йога',
    'Кулинария',
    'Психология',
    'Спорт',
    'Театр',
    'Искусство',
    'Прогулки',
    'Животные',
    'Саморазвитие',
    'Танцы',
];

$all_interest_options = array_values(array_unique(array_merge($default_interests, $profile_interests)));

if (!is_array($profile_gallery)) {
    $profile_gallery = [];
}

$relationship_goal_labels = [
    'communication' => 'Общение',
    'serious_relationship' => 'Серьёзные отношения',
    'family' => 'Создание семьи',
];

$relationship_goal_label = $relationship_goal_labels[$relationship_goal] ?? 'Создание семьи';

$location_parts = array_filter([$city, $country]);
$location = !empty($location_parts) ? implode(', ', $location_parts) : 'Местоположение не указано';

$profile_photo_url = has_post_thumbnail($profile_id)
    ? get_the_post_thumbnail_url($profile_id, 'large')
    : get_template_directory_uri() . '/img/pics/profile-main.jpg';

$profile_permalink = get_permalink($profile_id);

$about_length = mb_strlen(wp_strip_all_tags($about_me ?: ''), 'UTF-8');
$looking_length = mb_strlen(wp_strip_all_tags($looking_for ?: ''), 'UTF-8');

$max_text_length = 512;
?>

<main class="profile-editor-page">
  <section class="profile-editor" aria-labelledby="profile-editor-title">
    <div class="profile-editor__container">

      <header class="profile-editor__top">
        <div class="profile-editor__heading">
          <p class="profile-editor__eyebrow">Редактор анкеты</p>

          <h1 class="profile-editor__title" id="profile-editor-title">
            Редактирование публичной анкеты
          </h1>

          <p class="profile-editor__description">
            Заполните анкету так, как её увидят другие пользователи сайта.
          </p>
        </div>

        <a class="profile-editor__preview-link" href="<?php echo esc_url($profile_permalink); ?>">
          <span class="profile-editor__preview-icon" aria-hidden="true">◉</span>
          Посмотреть анкету
        </a>
      </header>

      <nav class="profile-editor__breadcrumbs" aria-label="Хлебные крошки">
        <a class="profile-editor__breadcrumb-link" href="<?php echo esc_url(home_url('/')); ?>">
          Главная
        </a>

        <span class="profile-editor__breadcrumb-separator" aria-hidden="true">›</span>

        <a class="profile-editor__breadcrumb-link" href="<?php echo esc_url(home_url('/my-profile/')); ?>">
          Мой профиль
        </a>

        <span class="profile-editor__breadcrumb-separator" aria-hidden="true">›</span>

        <span class="profile-editor__breadcrumb-current">
          Редактор анкеты
        </span>
      </nav>

      <div class="profile-editor__hero">

        <aside class="profile-editor__media" aria-label="Главное фото анкеты">
  <div class="profile-editor__main-photo">
    <img
      class="profile-editor__main-image"
      src="<?php echo esc_url($profile_photo_url); ?>"
      alt="<?php echo esc_attr($name ?: 'Главное фото анкеты'); ?>"
      data-editor-main-photo-preview
    >
  </div>

  <input
    class="profile-editor__photo-input"
    id="profile-editor-main-photo"
    type="file"
    name="profile_photo"
    accept="image/jpeg,image/png,image/webp"
    data-editor-main-photo-input
    hidden
  >

  <button class="profile-editor__photo-button" type="button" data-editor-main-photo-open>
    <span class="profile-editor__photo-icon" aria-hidden="true">▧</span>
    Изменить главное фото
  </button>

  <p class="profile-editor__hint">
    Рекомендуемый размер: от 800×1000px. JPG, PNG или WEBP до 5 МБ.
  </p>

  <p class="profile-editor__photo-message" data-editor-main-photo-message aria-live="polite"></p>
</aside>

        <section class="profile-editor__summary" aria-labelledby="summary-title">
          <div class="profile-editor__section-header">
            <div>
              <p class="profile-editor__section-kicker">Анкета</p>

              <!-- h2 class="profile-editor__summary-title" id="summary-title" -->
              <h2 class="profile-editor__summary-title" id="summary-title" data-editor-main-title>
  <span data-editor-main-name-age>
    <?php echo esc_html($name ?: 'Имя не указано'); ?>
    <?php if ($age) : ?>
      , <?php echo esc_html($age); ?>
    <?php endif; ?>
  </span>

  <span class="profile-editor__verified" aria-label="Профиль подтверждён">✓</span>
</h2>
            </div>

            <button class="profile-editor__edit-button" type="button" data-editor-main-open>
              ✎ Редактировать
            </button>
          </div>

 <!-- редактирование анкеты... -->
          <form class="profile-editor-main-form" data-editor-main-form hidden>
  <div class="profile-editor-main-form__grid">

    <label class="profile-editor-main-form__field">
      <span class="profile-editor-main-form__label">Имя</span>
      <input
        class="profile-editor-main-form__input"
        type="text"
        name="name"
        value="<?php echo esc_attr($name ?: ''); ?>"
        maxlength="80"
        required
      >
    </label>

    <label class="profile-editor-main-form__field">
      <span class="profile-editor-main-form__label">Возраст</span>
      <input
        class="profile-editor-main-form__input"
        type="number"
        name="age"
        value="<?php echo esc_attr($age ?: ''); ?>"
        min="18"
        max="99"
        required
      >
    </label>

    <label class="profile-editor-main-form__field">
      <span class="profile-editor-main-form__label">Страна</span>
      <input
        class="profile-editor-main-form__input"
        type="text"
        name="country"
        value="<?php echo esc_attr($country ?: ''); ?>"
        maxlength="80"
      >
    </label>

    <label class="profile-editor-main-form__field">
      <span class="profile-editor-main-form__label">Город</span>
      <input
        class="profile-editor-main-form__input"
        type="text"
        name="city"
        value="<?php echo esc_attr($city ?: ''); ?>"
        maxlength="80"
      >
    </label>

    <label class="profile-editor-main-form__field profile-editor-main-form__field--full">
      <span class="profile-editor-main-form__label">Цель знакомства</span>

      <select class="profile-editor-main-form__input" name="relationship_goal" required>
        <option value="communication" <?php selected($relationship_goal, 'communication'); ?>>
          Общение
        </option>
        <option value="serious_relationship" <?php selected($relationship_goal, 'serious_relationship'); ?>>
          Серьёзные отношения
        </option>
        <option value="family" <?php selected($relationship_goal ?: 'family', 'family'); ?>>
          Создание семьи
        </option>
      </select>
    </label>

    <label class="profile-editor-main-form__field profile-editor-main-form__field--full">
      <span class="profile-editor-main-form__label">Короткая фраза о себе</span>

      <textarea
        class="profile-editor-main-form__textarea"
        name="short_intro"
        maxlength="160"
        rows="3"
      ><?php echo esc_textarea($short_intro ?: ''); ?></textarea>

      <span class="profile-editor-main-form__counter" data-main-intro-counter>
        <?php echo esc_html(mb_strlen($short_intro ?: '', 'UTF-8')); ?> / 160
      </span>
    </label>

  </div>

  <p class="profile-editor-main-form__message" data-editor-main-message aria-live="polite"></p>

  <div class="profile-editor-main-form__actions">
    <button class="profile-editor-main-form__button profile-editor-main-form__button--secondary" type="button" data-editor-main-cancel>
      Отмена
    </button>

    <button class="profile-editor-main-form__button profile-editor-main-form__button--primary" type="submit">
      Сохранить
    </button>
  </div>
</form>
          <!-- p class="profile-editor__location" -->
          <p class="profile-editor__location" data-editor-main-location>
            <span class="profile-editor__location-icon" aria-hidden="true">⌖</span>
            <?php echo esc_html($location); ?>
          </p>

          <div class="profile-editor__goal">
            <span class="profile-editor__goal-label">Цель знакомства</span>

            <!-- div class="profile-editor__goal-badge" -->
            <div class="profile-editor__goal-badge" data-editor-main-goal>
              <span class="profile-editor__goal-icon" aria-hidden="true">♡</span>
              <?php echo esc_html($relationship_goal_label); ?>
            </div>
          </div>
          
          

          <blockquote class="profile-editor__quote">
            <span class="profile-editor__quote-mark" aria-hidden="true">“</span>

            <p class="profile-editor__quote-text" data-editor-main-intro>
              <?php echo esc_html($short_intro ?: 'Добавьте короткую фразу о себе.'); ?>
            </p>

            <span class="profile-editor__quote-mark" aria-hidden="true">”</span>
          </blockquote>
          
         

          <section class="profile-editor__interests" aria-labelledby="interests-title" data-editor-interests-section>
  <div class="profile-editor__section-header profile-editor__section-header--compact">
    <h2 class="profile-editor__section-title" id="interests-title">
      <span class="profile-editor__section-icon" aria-hidden="true">☆</span>
      Интересы
    </h2>

    <button class="profile-editor__edit-button" type="button" data-editor-interests-open>
      ✎ Редактировать
    </button>
  </div>

  <div data-editor-interests-view>
    <?php if (!empty($profile_interests)) : ?>
      <ul class="profile-editor__tag-list">
        <?php foreach ($profile_interests as $interest) : ?>
          <?php if ($interest) : ?>
            <li class="profile-editor__tag">
              <?php echo esc_html($interest); ?>
            </li>
          <?php endif; ?>
        <?php endforeach; ?>
      </ul>
    <?php else : ?>
      <p class="profile-editor__hint">
        Интересы пока не добавлены.
      </p>
    <?php endif; ?>
  </div>

  <form class="profile-editor-interests-form" data-editor-interests-form hidden>
    <fieldset class="profile-editor-interests-form__fieldset">
      <legend class="profile-editor-interests-form__legend">
        Выберите интересы
      </legend>

      <div class="profile-editor-interests-form__grid">
        <?php foreach ($all_interest_options as $interest_option) : ?>
          <?php
          $interest_option = trim($interest_option);

          if (!$interest_option) {
              continue;
          }

          $is_checked = in_array($interest_option, $profile_interests, true);
          ?>

          <label class="profile-editor-interests-form__chip">
            <input
              class="profile-editor-interests-form__checkbox"
              type="checkbox"
              name="interests[]"
              value="<?php echo esc_attr($interest_option); ?>"
              <?php checked($is_checked); ?>
            >

            <span class="profile-editor-interests-form__chip-text">
              <?php echo esc_html($interest_option); ?>
            </span>
          </label>
        <?php endforeach; ?>
      </div>
    </fieldset>

    <div class="profile-editor-interests-form__custom">
      <label class="profile-editor-interests-form__custom-field">
        <span class="profile-editor-interests-form__legend">
          Добавить свой интерес
        </span>

        <input
          class="profile-editor-interests-form__input"
          type="text"
          data-editor-custom-interest-input
          maxlength="30"
          placeholder="Например: шахматы"
        >
      </label>

      <button class="profile-editor-interests-form__add-button" type="button" data-editor-custom-interest-add>
        + Добавить
      </button>
    </div>

    <p class="profile-editor-interests-form__message" data-editor-interests-message aria-live="polite"></p>

    <div class="profile-editor-interests-form__actions">
      <button class="profile-editor-interests-form__button profile-editor-interests-form__button--secondary" type="button" data-editor-interests-cancel>
        Отмена
      </button>

      <button class="profile-editor-interests-form__button profile-editor-interests-form__button--primary" type="submit">
        Сохранить
      </button>
    </div>
  </form>

  <button class="profile-editor__add-interest" type="button" data-editor-interests-open-secondary>
    <span aria-hidden="true">+</span>
    Добавить свой интерес
  </button>
</section>
</section>
    

      </div>

      <section class="profile-editor__gallery" aria-labelledby="gallery-title">
        <div class="profile-editor__section-header">
          <div>
            <h2 class="profile-editor__section-title" id="gallery-title">
              <span class="profile-editor__section-icon" aria-hidden="true">▧</span>
              Фотографии
            </h2>

            <p class="profile-editor__hint">
              Можно загрузить до 5 фотографий. Чтобы добавить новую, используйте свободный слот или замените уже загруженную.
            </p>
          </div>

          <div class="profile-editor__order-actions">
            <button class="profile-editor__edit-button" type="button" data-gallery-order-open>
              ⟡ Изменить порядок
            </button>

            <button class="profile-editor__save-order" type="button">
              ✓ Сохранить порядок
            </button>

            <button class="profile-editor__cancel-order" type="button">
              × Отмена
            </button>
          </div>
        </div>

        <div class="profile-editor__gallery-grid">

          <?php for ($i = 0; $i < 5; $i++) : ?>
  <?php
  $image_id = isset($profile_gallery[$i]) ? (int) $profile_gallery[$i] : 0;
  $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'medium_large') : '';
  ?>

  <article
  class="profile-editor__gallery-slot <?php echo $image_url ? 'profile-editor__gallery-slot--filled' : 'profile-editor__gallery-slot--empty'; ?>"
  data-gallery-slot
  data-slot-index="<?php echo esc_attr($i); ?>"
  <?php if ($image_id) : ?>
    data-attachment-id="<?php echo esc_attr($image_id); ?>"
  <?php endif; ?>
>
    <input
      class="profile-editor__gallery-input"
      type="file"
      accept="image/jpeg,image/png,image/webp"
      data-gallery-input
      hidden
    >

    <?php if ($image_url) : ?>
      <button class="profile-editor__drag-handle" type="button" aria-label="Переместить фото">
        ⋮⋮
      </button>

      <img
        class="profile-editor__gallery-image"
        src="<?php echo esc_url($image_url); ?>"
        alt="<?php echo esc_attr('Дополнительное фото ' . ($i + 1)); ?>"
        data-gallery-image
      >

      <div class="profile-editor__slot-actions">
        <button class="profile-editor__slot-button" type="button" data-gallery-replace>
          Заменить
        </button>

        <button class="profile-editor__slot-button profile-editor__slot-button--danger" type="button" data-gallery-delete>
          Удалить
        </button>
      </div>
    <?php else : ?>
      <button class="profile-editor__upload-slot" type="button" data-gallery-upload>
        <span class="profile-editor__upload-icon" aria-hidden="true">⇧</span>
        <span>Загрузить фото</span>
      </button>
    <?php endif; ?>
  </article>
<?php endfor; ?>

        </div>
        <p class="profile-editor-gallery-message" data-gallery-message aria-live="polite"></p>
      </section>

      <div class="profile-editor__details">

	<!--  Секция о себе -->
        <section class="profile-editor__card" aria-labelledby="about-title" data-editor-text-section="about_me">
  <div class="profile-editor__section-header profile-editor__section-header--compact">
    <h2 class="profile-editor__section-title" id="about-title">
      <span class="profile-editor__section-icon" aria-hidden="true">♙</span>
      О себе
    </h2>

    <button class="profile-editor__edit-button" type="button" data-editor-text-open>
      ✎ Редактировать
    </button>
  </div>

  <p class="profile-editor__text" data-editor-text-view>
    <?php echo esc_html($about_me ?: 'Расскажите немного о себе.'); ?>
  </p>

  <form class="profile-editor-text-form" data-editor-text-form hidden>
    <textarea
      class="profile-editor-text-form__textarea"
      name="text"
      maxlength="512"
      rows="6"
    ><?php echo esc_textarea($about_me ?: ''); ?></textarea>

    <div class="profile-editor-text-form__meta">
      <p class="profile-editor-text-form__message" data-editor-text-message aria-live="polite"></p>

      <span class="profile-editor-text-form__counter" data-editor-text-counter>
        <?php echo esc_html($about_length); ?> / 512
      </span>
    </div>

    <div class="profile-editor-text-form__actions">
      <button class="profile-editor-text-form__button profile-editor-text-form__button--secondary" type="button" data-editor-text-cancel>
        Отмена
      </button>

      <button class="profile-editor-text-form__button profile-editor-text-form__button--primary" type="submit">
        Сохранить
      </button>
    </div>
  </form>

  <p class="profile-editor__counter" data-editor-text-static-counter>
    <?php echo esc_html($about_length); ?> / <?php echo esc_html($max_text_length); ?>
  </p>
</section>
        
	<!--  Секция "Кого ищу" -->
        <section class="profile-editor__card" aria-labelledby="looking-title" data-editor-text-section="looking_for">
  <div class="profile-editor__section-header profile-editor__section-header--compact">
    <h2 class="profile-editor__section-title" id="looking-title">
      <span class="profile-editor__section-icon" aria-hidden="true">♡</span>
      Кого ищу
    </h2>

    <button class="profile-editor__edit-button" type="button" data-editor-text-open>
      ✎ Редактировать
    </button>
  </div>

  <p class="profile-editor__text" data-editor-text-view>
    <?php echo esc_html($looking_for ?: 'Опишите, какого человека вы хотите встретить.'); ?>
  </p>

  <form class="profile-editor-text-form" data-editor-text-form hidden>
    <textarea
      class="profile-editor-text-form__textarea"
      name="text"
      maxlength="512"
      rows="6"
    ><?php echo esc_textarea($looking_for ?: ''); ?></textarea>

    <div class="profile-editor-text-form__meta">
      <p class="profile-editor-text-form__message" data-editor-text-message aria-live="polite"></p>

      <span class="profile-editor-text-form__counter" data-editor-text-counter>
        <?php echo esc_html($looking_length); ?> / 512
      </span>
    </div>

    <div class="profile-editor-text-form__actions">
      <button class="profile-editor-text-form__button profile-editor-text-form__button--secondary" type="button" data-editor-text-cancel>
        Отмена
      </button>

      <button class="profile-editor-text-form__button profile-editor-text-form__button--primary" type="submit">
        Сохранить
      </button>
    </div>
  </form>

  <p class="profile-editor__counter" data-editor-text-static-counter>
    <?php echo esc_html($looking_length); ?> / <?php echo esc_html($max_text_length); ?>
  </p>
</section>

      </div>

      <footer class="profile-editor__footer">
        <p class="profile-editor__moderation">
          <span class="profile-editor__moderation-icon" aria-hidden="true">♢</span>
          Профиль подтверждён и прошёл модерацию
          <span class="profile-editor__moderation-check" aria-hidden="true">✓</span>
        </p>

        <div class="profile-editor__footer-actions">
          <a
            class="profile-editor__footer-button profile-editor__footer-button--secondary"
            href="<?php echo esc_url(home_url('/my-profile/')); ?>"
          >
            Вернуться в профиль
          </a>

          <a
            class="profile-editor__footer-button profile-editor__footer-button--primary"
            href="<?php echo esc_url($profile_permalink); ?>"
          >
            Посмотреть анкету
          </a>
        </div>
      </footer>

    </div>
  </section>
</main>

<?php get_footer(); ?>
