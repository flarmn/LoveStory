<?php
/**
 * Template for a single public dating profile.
 */

get_header();

$goal_labels = [
    'communication'          => 'Общение',
    'serious_relationship'  => 'Серьёзные отношения',
    'family'                 => 'Создание семьи',
];

$gender_labels = [
    'male'   => 'Мужчина',
    'female' => 'Женщина',
];

function lovestory_normalize_list_meta($value) {
    if (empty($value)) {
        return [];
    }

    if (is_array($value)) {
        return array_values(array_filter(array_map('trim', $value)));
    }

    if (is_string($value)) {
        $items = explode(',', $value);
        return array_values(array_filter(array_map('trim', $items)));
    }

    return [];
}

function lovestory_normalize_gallery_meta($value) {
    if (empty($value)) {
        return [];
    }

    if (is_array($value)) {
        return array_values(array_filter(array_map('absint', $value)));
    }

    if (is_string($value)) {
        $items = explode(',', $value);
        return array_values(array_filter(array_map('absint', $items)));
    }

    return [];
}
?>

<main class="profile-page">
    <section class="public-profile" aria-labelledby="public-profile-title">
        <div class="public-profile__container">

            <?php if (have_posts()) : ?>
                <?php while (have_posts()) : the_post(); ?>
                    <?php
                    $profile_id     = get_the_ID();
                    $profile_status = get_post_meta($profile_id, 'profile_status', true);

                    $can_view_hidden_profile =
                        current_user_can('manage_options') ||
                        current_user_can('edit_others_profiles');

                    $is_profile_hidden =
                        $profile_status !== 'active' &&
                        !$can_view_hidden_profile;

                    if ($is_profile_hidden) :
                        ?>

                        <article class="profile profile--unavailable">
                            <h1 class="profile__title">Анкета недоступна</h1>

                            <div class="profile__content">
                                <p>Эта анкета сейчас недоступна для просмотра.</p>
                            </div>
                        </article>

                        <?php
                        continue;
                    endif;

                    $name = get_the_title($profile_id);
                    $age = get_post_meta($profile_id, 'age', true);
                    $gender = get_post_meta($profile_id, 'gender', true);
                    $country = get_post_meta($profile_id, 'country', true);
                    $city = get_post_meta($profile_id, 'city', true);
                    $short_intro = get_post_meta($profile_id, 'short_intro', true);
                    $about_me = get_post_meta($profile_id, 'about_me', true);
                    $looking_for = get_post_meta($profile_id, 'looking_for', true);
                    $relationship_goal = get_post_meta($profile_id, 'relationship_goal', true);
                    $interests = lovestory_normalize_list_meta(get_post_meta($profile_id, 'profile_interests', true));
                    $gallery_ids = lovestory_normalize_gallery_meta(get_post_meta($profile_id, 'profile_gallery', true));

                    $goal_label = $goal_labels[$relationship_goal] ?? 'Создание семьи';
                    $gender_label = $gender_labels[$gender] ?? '';

                    $title_parts = [$name];
                    if ($age) {
                        $title_parts[] = absint($age);
                    }
                    $profile_title = implode(', ', array_filter($title_parts));

                    $location_parts = array_filter([$city, $country]);
                    $location = $location_parts ? implode(', ', $location_parts) : 'Местоположение не указано';

                    $main_photo_url = has_post_thumbnail($profile_id)
                        ? get_the_post_thumbnail_url($profile_id, 'large')
                        : get_template_directory_uri() . '/img/pics/profile-main.jpg';

                    $fallback_gallery = [
                        get_template_directory_uri() . '/img/pics/gallery-1.jpg',
                        get_template_directory_uri() . '/img/pics/gallery-2.jpg',
                        get_template_directory_uri() . '/img/pics/gallery-3.jpg',
                        get_template_directory_uri() . '/img/pics/gallery-4.jpg',
                        get_template_directory_uri() . '/img/pics/gallery-5.jpg',
                    ];
                    ?>

                    <nav class="public-profile__breadcrumbs" aria-label="Хлебные крошки">
                        <a class="public-profile__breadcrumb-link" href="<?php echo esc_url(home_url('/')); ?>">Главная</a>
                        <span class="public-profile__breadcrumb-separator" aria-hidden="true">›</span>
                        <a class="public-profile__breadcrumb-link" href="<?php echo esc_url(get_post_type_archive_link('profile') ?: home_url('/profile/')); ?>">Анкеты</a>
                        <span class="public-profile__breadcrumb-separator" aria-hidden="true">›</span>
                        <span class="public-profile__breadcrumb-current"><?php echo esc_html($profile_title); ?></span>
                    </nav>

                    <div class="public-profile__hero">

                        <aside class="public-profile__media" aria-label="Фото пользователя">
                            <div class="public-profile__main-photo">
                                <img
                                    class="public-profile__main-image"
                                    src="<?php echo esc_url($main_photo_url); ?>"
                                    alt="<?php echo esc_attr($profile_title); ?>"
                                >
                            </div>

                            <!-- a class="public-profile__message-button" href="#">
                                <span class="public-profile__message-icon" aria-hidden="true">♡</span>
                                <span>Написать</span>
                            </a -->

                            <?php
$current_user_id = get_current_user_id();
$profile_author_id = (int) get_post_field('post_author', get_the_ID());
$is_own_profile = is_user_logged_in() && $current_user_id === $profile_author_id;
?>

<?php if (is_user_logged_in() && !$is_own_profile) : ?>
  <form
  class="public-profile__message-form"
  action="<?php echo esc_url(get_permalink()); ?>"
  method="post"
>
  <?php wp_nonce_field('dating_start_conversation_action', 'dating_start_conversation_nonce'); ?>

  <input type="hidden" name="dating_start_conversation" value="1">
  <input type="hidden" name="profile_id" value="<?php echo esc_attr(get_the_ID()); ?>">

  <button class="public-profile__message-button" type="submit">
    <span aria-hidden="true">♡</span>
    Написать
  </button>
</form>
<?php elseif (!is_user_logged_in()) : ?>
  <a class="public-profile__message-button" href="<?php echo esc_url(home_url('/')); ?>">
    <span aria-hidden="true">♡</span>
    Войти, чтобы написать
  </a>
<?php endif; ?>


                        </aside>

                        <header class="public-profile__summary">
                            <p class="public-profile__eyebrow">Анкета</p>

                            <h1 class="public-profile__title" id="public-profile-title">
                                <?php echo esc_html($profile_title); ?>

                                <?php if ($profile_status === 'active') : ?>
                                    <span class="public-profile__verified" aria-label="Профиль подтверждён">✓</span>
                                <?php endif; ?>
                            </h1>

                            <p class="public-profile__location">
                                <span class="public-profile__location-icon" aria-hidden="true">⌖</span>
                                <?php echo esc_html($location); ?>
                            </p>

                            <div class="public-profile__goal">
                                <span class="public-profile__goal-label">Цель знакомства</span>

                                <div class="public-profile__goal-badge">
                                    <span class="public-profile__goal-icon" aria-hidden="true">♡</span>
                                    <?php echo esc_html($goal_label); ?>
                                </div>
                            </div>

                            <blockquote class="public-profile__quote">
                                <span class="public-profile__quote-mark" aria-hidden="true">“</span>
                                <p class="public-profile__quote-text">
                                    <?php echo esc_html($short_intro ?: 'Верю в искренность, доброту и настоящие чувства.'); ?>
                                </p>
                                <span class="public-profile__quote-mark" aria-hidden="true">”</span>
                            </blockquote>

                            <?php if (!empty($interests)) : ?>
                                <section class="public-profile__interests" aria-labelledby="interests-title">
                                    <h2 class="public-profile__section-title" id="interests-title">
                                        <span class="public-profile__section-icon" aria-hidden="true">☆</span>
                                        Интересы
                                    </h2>

                                    <ul class="public-profile__tag-list">
                                        <?php foreach ($interests as $interest) : ?>
                                            <li class="public-profile__tag"><?php echo esc_html($interest); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </section>
                            <?php endif; ?>
                        </header>

                    </div>

                    <section class="public-profile__gallery" aria-labelledby="gallery-title">
                        <div class="public-profile__gallery-header">
                            <h2 class="public-profile__section-title" id="gallery-title">
                                <span class="public-profile__section-icon" aria-hidden="true">▧</span>
                                Фотографии
                            </h2>
                        </div>

                        <button
                            class="public-profile__gallery-button public-profile__gallery-button--prev"
                            type="button"
                            aria-label="Предыдущее фото"
                        >
                            ‹
                        </button>

                        <div class="public-profile__gallery-list">
                            <?php if (!empty($gallery_ids)) : ?>
                                <?php foreach (array_slice($gallery_ids, 0, 5) as $attachment_id) : ?>
                                    <?php
                                    $gallery_full_url = wp_get_attachment_image_url($attachment_id, 'large');
                                    $gallery_thumb_url = wp_get_attachment_image_url($attachment_id, 'medium_large');

                                    if (!$gallery_thumb_url) {
                                        continue;
                                    }
                                    ?>

                                    <a class="public-profile__gallery-item" href="<?php echo esc_url($gallery_full_url ?: $gallery_thumb_url); ?>">
                                        <img src="<?php echo esc_url($gallery_thumb_url); ?>" alt="<?php echo esc_attr('Фото анкеты ' . $profile_title); ?>">
                                    </a>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <?php foreach ($fallback_gallery as $index => $fallback_url) : ?>
                                    <a class="public-profile__gallery-item" href="<?php echo esc_url($fallback_url); ?>">
                                        <img src="<?php echo esc_url($fallback_url); ?>" alt="<?php echo esc_attr('Фото анкеты ' . ($index + 1)); ?>">
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <button
                            class="public-profile__gallery-button public-profile__gallery-button--next"
                            type="button"
                            aria-label="Следующее фото"
                        >
                            ›
                        </button>
                    </section>

                    <div class="public-profile__details">
                        <section class="public-profile__card" aria-labelledby="about-title">
                            <h2 class="public-profile__section-title" id="about-title">
                                <span class="public-profile__section-icon" aria-hidden="true">♙</span>
                                О себе
                            </h2>

                            <p class="public-profile__text">
                                <?php echo esc_html($about_me ?: 'Пользователь пока не заполнил раздел «О себе».'); ?>
                            </p>
                        </section>

                        <section class="public-profile__card" aria-labelledby="looking-title">
                            <h2 class="public-profile__section-title" id="looking-title">
                                <span class="public-profile__section-icon" aria-hidden="true">♡</span>
                                Кого ищу
                            </h2>

                            <p class="public-profile__text">
                                <?php echo esc_html($looking_for ?: 'Пользователь пока не заполнил раздел «Кого ищу».'); ?>
                            </p>
                        </section>
                    </div>

                    <footer class="public-profile__footer">
                        <p class="public-profile__moderation">
                            <span class="public-profile__moderation-icon" aria-hidden="true">♢</span>
                            Профиль подтверждён и прошёл модерацию
                            <span class="public-profile__moderation-check" aria-hidden="true">✓</span>
                        </p>

                        <?php if ($can_view_hidden_profile) : ?>
                            <p class="public-profile__moderation">
                                Служебный статус: <?php echo esc_html($profile_status ?: 'не указан'); ?>
                            </p>
                        <?php endif; ?>
                    </footer>

                <?php endwhile; ?>
            <?php else : ?>
                <article class="profile profile--unavailable">
                    <h1 class="profile__title">Анкета не найдена</h1>
                    <div class="profile__content">
                        <p>Запрошенная анкета не найдена.</p>
                    </div>
                </article>
            <?php endif; ?>

        </div>
    </section>
</main>

<?php get_footer(); ?>
