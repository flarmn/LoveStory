<?php
/**
 * Template Name: Поиск анкет
 */

get_header();

if (!is_user_logged_in()) {
    wp_safe_redirect(home_url('/'));
    exit;
}

$current_user_id = get_current_user_id();

$current_user_profiles = get_posts([
    'post_type'      => 'profile',
    'post_status'    => 'any',
    'author'         => $current_user_id,
    'posts_per_page' => 1,
]);

$current_profile_id = !empty($current_user_profiles) ? $current_user_profiles[0]->ID : 0;

$current_gender = $current_profile_id ? get_post_meta($current_profile_id, 'gender', true) : '';
$current_age    = $current_profile_id ? get_post_meta($current_profile_id, 'age', true) : '';
$current_goal   = $current_profile_id ? get_post_meta($current_profile_id, 'relationship_goal', true) : '';
$current_status = $current_profile_id ? get_post_meta($current_profile_id, 'profile_status', true) : '';

$profile_is_ready_for_search =
    $current_profile_id &&
    $current_status === 'active' &&
    function_exists('dating_profile_has_required_search_fields') &&
    dating_profile_has_required_search_fields($current_profile_id);

if (!$profile_is_ready_for_search) {
    ?>
    <main class="match-page">
      <section class="match-search" aria-labelledby="match-search-title">
        <div class="match-search__container">
          <div class="match-search__empty match-search__empty--large">
            <p class="match-search__eyebrow">Поиск анкет</p>

            <h1 class="match-search__empty-title" id="match-search-title">
              Сначала заполните анкету
            </h1>

            <p class="match-search__empty-text">
              Чтобы поиск работал точнее, укажите в анкете пол, возраст и цель знакомства.
              После этого мы сможем подобрать подходящие анкеты.
            </p>

            <a
              class="match-card__button match-search__empty-button"
              href="<?php echo esc_url(home_url('/edit-profile/')); ?>"
            >
              Редактировать анкету
            </a>
          </div>
        </div>
      </section>
    </main>
    <?php
    get_footer();
    exit;
}

$target_gender = '';

if ($current_gender === 'male') {
    $target_gender = 'female';
} elseif ($current_gender === 'female') {
    $target_gender = 'male';
}

$goal_labels = [
    'communication'        => 'Общение',
    'serious_relationship' => 'Серьёзные отношения',
    'family'               => 'Создание семьи',
];

$page_titles = [
    'communication'        => 'Найти собеседника',
    'serious_relationship' => 'Найти пару',
    'family'               => 'Найти вторую половинку',
];

$age_from = isset($_GET['age_from']) ? absint($_GET['age_from']) : 18;
$age_to   = isset($_GET['age_to']) ? absint($_GET['age_to']) : 99;

$country = isset($_GET['country']) ? sanitize_text_field(wp_unslash($_GET['country'])) : '';
$city    = isset($_GET['city']) ? sanitize_text_field(wp_unslash($_GET['city'])) : '';

$relationship_goal = isset($_GET['relationship_goal'])
    ? sanitize_key(wp_unslash($_GET['relationship_goal']))
    : $current_goal;

$interest = isset($_GET['interest'])
    ? sanitize_text_field(wp_unslash($_GET['interest']))
    : '';

$with_photo = isset($_GET['with_photo']) && $_GET['with_photo'] === '1';

$sort = isset($_GET['sort'])
    ? sanitize_key(wp_unslash($_GET['sort']))
    : 'newest';

if (!isset($goal_labels[$relationship_goal])) {
    $relationship_goal = 'family';
}

if ($age_from < 18) {
    $age_from = 18;
}

if ($age_to > 99 || $age_to < 18) {
    $age_to = 99;
}

if ($age_from > $age_to) {
    $age_from = 18;
    $age_to = 99;
}

$paged = max(1, get_query_var('paged') ? get_query_var('paged') : get_query_var('page'));

$meta_query = [
    'relation' => 'AND',
    [
        'key'   => 'profile_status',
        'value' => 'active',
    ],
    [
        'key'     => 'age',
        'value'   => [$age_from, $age_to],
        'type'    => 'NUMERIC',
        'compare' => 'BETWEEN',
    ],
];

if ($target_gender) {
    $meta_query[] = [
        'key'   => 'gender',
        'value' => $target_gender,
    ];
}

if ($relationship_goal) {
    $meta_query[] = [
        'key'   => 'relationship_goal',
        'value' => $relationship_goal,
    ];
}

if ($country) {
    $meta_query[] = [
        'key'     => 'country',
        'value'   => $country,
        'compare' => '=',
    ];
}

if ($city) {
    $meta_query[] = [
        'key'     => 'city',
        'value'   => $city,
        'compare' => '=',
    ];
}

if ($interest) {
    $meta_query[] = [
        'key'     => 'profile_interests',
        'value'   => $interest,
        'compare' => 'LIKE',
    ];
}

if ($with_photo) {
    $meta_query[] = [
        'key'     => '_thumbnail_id',
        'compare' => 'EXISTS',
    ];
}

$query_args = [
    'post_type'      => 'profile',
    'post_status'    => 'publish',
    'posts_per_page' => 8,
    'paged'          => $paged,
    'post__not_in'   => $current_profile_id ? [$current_profile_id] : [],
    'meta_query'     => $meta_query,
];

if ($sort === 'age_asc') {
    $query_args['meta_key'] = 'age';
    $query_args['orderby']  = 'meta_value_num';
    $query_args['order']    = 'ASC';
} elseif ($sort === 'age_desc') {
    $query_args['meta_key'] = 'age';
    $query_args['orderby']  = 'meta_value_num';
    $query_args['order']    = 'DESC';
} else {
    $query_args['orderby'] = 'date';
    $query_args['order']   = 'DESC';
}

$profiles_query = new WP_Query($query_args);

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

$default_countries = [
    'Россия',
    'Беларусь',
    'Украина',
    'Молдова',
    'Армения',
    'Азербайджан',
    'Грузия',
    'Казахстан',
    'Киргизия',
    'Таджикистан',
    'Туркменистан',
    'Узбекистан',

    'Австрия',
    'Албания',
    'Андорра',
    'Бельгия',
    'Болгария',
    'Босния и Герцеговина',
    'Ватикан',
    'Великобритания',
    'Венгрия',
    'Германия',
    'Греция',
    'Дания',
    'Ирландия',
    'Исландия',
    'Испания',
    'Италия',
    'Кипр',
    'Латвия',
    'Литва',
    'Лихтенштейн',
    'Люксембург',
    'Мальта',
    'Монако',
    'Нидерланды',
    'Норвегия',
    'Польша',
    'Португалия',
    'Румыния',
    'Северная Македония',
    'Сербия',
    'Словакия',
    'Словения',
    'Финляндия',
    'Франция',
    'Хорватия',
    'Черногория',
    'Чехия',
    'Швейцария',
    'Швеция',
    'Эстония',

    'США',
    'Канада',
    'Мексика',
    'Аргентина',
    'Боливия',
    'Бразилия',
    'Венесуэла',
    'Гайана',
    'Колумбия',
    'Парагвай',
    'Перу',
    'Суринам',
    'Уругвай',
    'Чили',
    'Эквадор',

    'Добавить страну',
];

$page_title = $page_titles[$relationship_goal] ?? 'Найти вторую половинку';

$reset_url = home_url('/find-match/');
?>

<main class="match-page">
  <section class="match-search" aria-labelledby="match-search-title">
    <div class="match-search__container">

      <header class="match-search__hero">
        <div class="match-search__hero-content">
          <nav class="match-search__breadcrumbs" aria-label="Хлебные крошки">
            <a class="match-search__breadcrumb-link" href="<?php echo esc_url(home_url('/')); ?>">
              Главная
            </a>

            <span class="match-search__breadcrumb-separator" aria-hidden="true">›</span>

            <a class="match-search__breadcrumb-link" href="<?php echo esc_url(home_url('/my-profile/')); ?>">
              Мой профиль
            </a>

            <span class="match-search__breadcrumb-separator" aria-hidden="true">›</span>

            <span class="match-search__breadcrumb-current">
              Поиск анкет
            </span>
          </nav>

          <p class="match-search__eyebrow">Поиск анкет</p>

          <h1 class="match-search__title" id="match-search-title">
            <?php echo esc_html($page_title); ?>
          </h1>

          <p class="match-search__description">
            Мы поможем найти людей, которые близки вам по целям, интересам
            и взглядам на отношения.
          </p>
        </div>

        <div class="match-search__hero-visual" aria-hidden="true">
          <!-- div class="match-search__heart-glow"></div -->
        </div>
      </header>

      <section class="match-search__panel" aria-labelledby="match-filters-title">
        <div class="match-search__panel-header">
          <h2 class="match-search__panel-title" id="match-filters-title">
            Параметры поиска
          </h2>

          <a class="match-search__reset" href="<?php echo esc_url($reset_url); ?>">
            <span aria-hidden="true">↻</span>
            Сбросить всё
          </a>
        </div>

        <form class="match-filter" id="match-filter-form" action="<?php echo esc_url(home_url('/find-match/')); ?>" method="get">
          <div class="match-filter__grid">

            <label class="match-filter__field">
              <span class="match-filter__label">Возраст от</span>
              <select class="match-filter__control" name="age_from">
                <?php foreach ([18, 21, 25, 30, 35, 40, 45, 50] as $age_option) : ?>
                  <option value="<?php echo esc_attr($age_option); ?>" <?php selected($age_from, $age_option); ?>>
                    <?php echo esc_html($age_option); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </label>

            <label class="match-filter__field">
              <span class="match-filter__label">Возраст до</span>
              <select class="match-filter__control" name="age_to">
                <?php foreach ([25, 30, 35, 40, 45, 50, 60, 70, 99] as $age_option) : ?>
                  <option value="<?php echo esc_attr($age_option); ?>" <?php selected($age_to, $age_option); ?>>
                    <?php echo esc_html($age_option === 99 ? '99' : $age_option); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </label>

            <label class="match-filter__field">
              <span class="match-filter__label">Страна</span>

              <select class="match-filter__control" name="country">
                <option value="">Любая страна</option>

                <?php foreach ($default_countries as $country_option) : ?>
                  <option value="<?php echo esc_attr($country_option); ?>" <?php selected($country, $country_option); ?>>
                    <?php echo esc_html($country_option); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </label>

            <label class="match-filter__field">
              <span class="match-filter__label">Город</span>
              <input
                class="match-filter__control"
                type="text"
                name="city"
                value="<?php echo esc_attr($city); ?>"
                placeholder="Любой город"
              >
            </label>

            <label class="match-filter__field">
              <span class="match-filter__label">Цель знакомства</span>
              <select class="match-filter__control" name="relationship_goal">
                <?php foreach ($goal_labels as $goal_key => $goal_label) : ?>
                  <option value="<?php echo esc_attr($goal_key); ?>" <?php selected($relationship_goal, $goal_key); ?>>
                    <?php echo esc_html($goal_label); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </label>

            <label class="match-filter__field match-filter__field--wide">
              <span class="match-filter__label">Интересы</span>
              <select class="match-filter__control" name="interest">
                <option value="">Любые интересы</option>

                <?php foreach ($default_interests as $interest_option) : ?>
                  <option value="<?php echo esc_attr($interest_option); ?>" <?php selected($interest, $interest_option); ?>>
                    <?php echo esc_html($interest_option); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </label>

            <label class="match-filter__checkbox">
              <input
                class="match-filter__checkbox-input"
                type="checkbox"
                name="with_photo"
                value="1"
                <?php checked($with_photo); ?>
              >
              <span class="match-filter__checkbox-box" aria-hidden="true"></span>
              <span class="match-filter__checkbox-text">Только с фото</span>
            </label>

            <button class="match-filter__submit" type="submit">
              Найти анкеты
            </button>

          </div>
        </form>
      </section>

      <section class="match-search__results" aria-labelledby="match-results-title">
        <div class="match-search__results-header">
          <h2 class="match-search__results-title" id="match-results-title">
            Найдено анкет: <span><?php echo esc_html($profiles_query->found_posts); ?></span>
          </h2>

          <form class="match-search__sort" action="<?php echo esc_url(home_url('/find-match/')); ?>" method="get">
            <input type="hidden" name="age_from" value="<?php echo esc_attr($age_from); ?>">
            <input type="hidden" name="age_to" value="<?php echo esc_attr($age_to); ?>">
            <input type="hidden" name="country" value="<?php echo esc_attr($country); ?>">
            <input type="hidden" name="city" value="<?php echo esc_attr($city); ?>">
            <input type="hidden" name="relationship_goal" value="<?php echo esc_attr($relationship_goal); ?>">
            <input type="hidden" name="interest" value="<?php echo esc_attr($interest); ?>">

            <?php if ($with_photo) : ?>
              <input type="hidden" name="with_photo" value="1">
            <?php endif; ?>

            <label class="match-search__sort-label" for="match-sort">
              Сортировка:
            </label>

            <select class="match-search__sort-control" id="match-sort" name="sort" onchange="this.form.submit()">
              <option value="newest" <?php selected($sort, 'newest'); ?>>
                По дате регистрации
              </option>
              <option value="age_asc" <?php selected($sort, 'age_asc'); ?>>
                Возраст: по возрастанию
              </option>
              <option value="age_desc" <?php selected($sort, 'age_desc'); ?>>
                Возраст: по убыванию
              </option>
            </select>
          </form>
        </div>

        <?php if ($profiles_query->have_posts()) : ?>
          <div class="match-search__grid">

            <?php while ($profiles_query->have_posts()) : ?>
              <?php
              $profiles_query->the_post();

              $profile_id = get_the_ID();

              $profile_age = get_post_meta($profile_id, 'age', true);
              $profile_country = get_post_meta($profile_id, 'country', true);
              $profile_city = get_post_meta($profile_id, 'city', true);
              $profile_goal = get_post_meta($profile_id, 'relationship_goal', true);
              $profile_interests = get_post_meta($profile_id, 'profile_interests', true);

              if (!is_array($profile_interests)) {
                  $profile_interests = $profile_interests
                      ? array_map('trim', explode(',', $profile_interests))
                      : [];
              }

              $profile_interests = array_slice(array_filter($profile_interests), 0, 3);

              $profile_location_parts = array_filter([$profile_city, $profile_country]);
              $profile_location = !empty($profile_location_parts)
                  ? implode(', ', $profile_location_parts)
                  : 'Местоположение не указано';

              $profile_photo_url = has_post_thumbnail($profile_id)
                  ? get_the_post_thumbnail_url($profile_id, 'medium_large')
                  : get_template_directory_uri() . '/img/pics/profile-main.jpg';

              $profile_goal_label = $goal_labels[$profile_goal] ?? 'Не указана';
              ?>

              <article class="match-card">
                <a class="match-card__photo-link" href="<?php the_permalink(); ?>">
                  <img
                    class="match-card__photo"
                    src="<?php echo esc_url($profile_photo_url); ?>"
                    alt="<?php echo esc_attr(get_the_title()); ?>"
                  >

                  <?php if ($profile_age) : ?>
                    <span class="match-card__age">
                      <?php echo esc_html($profile_age); ?>
                    </span>
                  <?php endif; ?>

                  <span class="match-card__favorite" aria-hidden="true">♡</span>
                </a>

                <div class="match-card__content">
                  <h3 class="match-card__title">
                    <a href="<?php the_permalink(); ?>">
                      <?php echo esc_html(get_the_title()); ?>
                      <?php if ($profile_age) : ?>
                        , <?php echo esc_html($profile_age); ?>
                      <?php endif; ?>
                    </a>
                  </h3>

                  <p class="match-card__location">
                    <span aria-hidden="true">⌖</span>
                    <?php echo esc_html($profile_location); ?>
                  </p>

                  <p class="match-card__goal">
                    Цель: <?php echo esc_html($profile_goal_label); ?>
                  </p>

                  <?php if (!empty($profile_interests)) : ?>
                    <ul class="match-card__tags" aria-label="Интересы">
                      <?php foreach ($profile_interests as $profile_interest) : ?>
                        <li class="match-card__tag">
                          <?php echo esc_html($profile_interest); ?>
                        </li>
                      <?php endforeach; ?>
                    </ul>
                  <?php endif; ?>

                  <a class="match-card__button" href="<?php the_permalink(); ?>">
                    Открыть анкету
                  </a>
                </div>
              </article>

            <?php endwhile; ?>

          </div>

          <?php
          $pagination_links = paginate_links([
              'total'     => $profiles_query->max_num_pages,
              'current'   => $paged,
              'type'      => 'array',
              'prev_text' => '‹',
              'next_text' => '›',
              'add_args'  => [
                  'age_from'          => $age_from,
                  'age_to'            => $age_to,
                  'country'           => $country,
                  'city'              => $city,
                  'relationship_goal' => $relationship_goal,
                  'interest'          => $interest,
                  'with_photo'        => $with_photo ? '1' : null,
                  'sort'              => $sort,
              ],
          ]);
          ?>

          <?php if (!empty($pagination_links)) : ?>
            <nav class="match-pagination" aria-label="Пагинация результатов поиска">
              <?php foreach ($pagination_links as $link) : ?>
                <?php
                $link = str_replace('page-numbers', 'match-pagination__button', $link);
                $link = str_replace('current', 'match-pagination__button--active', $link);
                echo wp_kses_post($link);
                ?>
              <?php endforeach; ?>
            </nav>
          <?php endif; ?>

          <?php
          $shown_from = (($paged - 1) * $profiles_query->query_vars['posts_per_page']) + 1;
          $shown_to = min($paged * $profiles_query->query_vars['posts_per_page'], $profiles_query->found_posts);
          ?>

          <p class="match-search__count-note">
            Показано <?php echo esc_html($shown_from); ?>–<?php echo esc_html($shown_to); ?>
            из <?php echo esc_html($profiles_query->found_posts); ?> анкет
          </p>

        <?php else : ?>
          <div class="match-search__empty">
            <h3 class="match-search__empty-title">
              Подходящие анкеты не найдены
            </h3>

            <p class="match-search__empty-text">
              Попробуйте изменить возраст, город, цель знакомства или интересы.
            </p>
          </div>
        <?php endif; ?>

        <?php wp_reset_postdata(); ?>
      </section>

    </div>
  </section>
</main>

<?php get_footer(); ?>
