<?php
/**
 * Template Name: Личный кабинет пользователя
 */

get_header();

if (!is_user_logged_in()) {
    wp_safe_redirect(home_url('/'));
    exit;
}

$current_user_id = get_current_user_id();
$current_user    = wp_get_current_user();

$unread_messages_count = function_exists('dating_get_total_unread_messages_count')
    ? dating_get_total_unread_messages_count($current_user_id)
    : 0;

$user_profiles = get_posts([
    'post_type'      => 'profile',
    'post_status'    => 'any',
    'author'         => $current_user_id,
    'posts_per_page' => 1,
    'orderby'        => 'date',
    'order'          => 'DESC',
]);

$profile_id = !empty($user_profiles) ? $user_profiles[0]->ID : 0;

/* Выбор кнопки перехода на страницу поиска анкет в соответсвии с целью знакомаства */

$relationship_goal = $profile_id ? get_post_meta($profile_id, 'relationship_goal', true) : '';

$find_match_button_labels = [
    'communication'        => 'Найти собеседника',
    'serious_relationship' => 'Найти пару',
    'family'               => 'Найти вторую половинку',
];

$find_match_button_label = $find_match_button_labels[$relationship_goal] ?? 'Найти вторую половинку';


$unread_messages_count = 0;

if (
    is_user_logged_in() &&
    function_exists('dating_get_total_unread_messages_count')
) {
    $unread_messages_count = dating_get_total_unread_messages_count(get_current_user_id());
}


$status_labels = [
    'active'  => [
        'label' => 'Активна',
        'class' => 'account__status--active',
    ],
    'pending' => [
        'label' => 'На модерации',
        'class' => 'account__status--pending',
    ],
    'blocked' => [
        'label' => 'Заблокирована',
        'class' => 'account__status--blocked',
    ],
    'deleted_by_user' => [
        'label' => 'Удалена пользователем',
        'class' => 'account__status--blocked',
    ],
];

$profile_status = $profile_id
    ? get_post_meta($profile_id, 'profile_status', true)
    : 'pending';

$status_data = $status_labels[$profile_status] ?? [
    'label' => 'Не указан',
    'class' => 'account__status--pending',
];

$name = $profile_id
    ? get_the_title($profile_id)
    : $current_user->display_name;

$country = $profile_id
    ? get_post_meta($profile_id, 'country', true)
    : '';

$city = $profile_id
    ? get_post_meta($profile_id, 'city', true)
    : '';

$phone = $profile_id
    ? get_post_meta($profile_id, 'phone', true)
    : '';

$email = $current_user->user_email;

$profile_photo_url = '';

if ($profile_id && has_post_thumbnail($profile_id)) {
    $profile_photo_url = get_the_post_thumbnail_url($profile_id, 'large');
} else {
    $profile_photo_url = get_template_directory_uri() . '/img/pics/profile-photo.jpg';
}

$profile_permalink = $profile_id ? get_permalink($profile_id) : '#';
?>

<main class="account-page">
  <section class="account" aria-labelledby="account-title">
    <div class="account__container">

      <header class="account__header">
        <div class="account__heading">
          <p class="account__eyebrow">Личный кабинет</p>

          <h1 class="account__title" id="account-title">
            Мой профиль
          </h1>

          <p class="account__description">
            Эти данные видны только вам, администрации и модераторам сайта.
          </p>
        </div>

        <?php if ($profile_id) : ?>
          <div class="account__status <?php echo esc_attr($status_data['class']); ?>">
            <span class="account__status-label">Статус анкеты:</span>
            <strong class="account__status-value">
              <?php echo esc_html($status_data['label']); ?>
            </strong>
          </div>
        <?php endif; ?>
      </header>

      <?php if ($profile_id) : ?>

        <div class="account__body">

          <aside class="account__photo-panel" aria-labelledby="profile-photo-title">
            <h2 class="account__section-title" id="profile-photo-title">
              Фото профиля
            </h2>

            <div class="account__photo-wrapper">
              <img
                class="account__photo"
                src="<?php echo esc_url($profile_photo_url); ?>"
                alt="Фото пользователя"
                data-profile-photo-preview
              >

              <button class="account__photo-edit" type="button" aria-label="Изменить фото">
                ✦
              </button>
            </div>
            
            <!-- form class="account-photo-form" data-profile-photo-form enctype="multipart/form-data" -->
            <form class="account-photo-form" data-profile-photo-form enctype="multipart/form-data">
  <input
    class="account-photo-form__input"
    id="profile-photo-input"
    type="file"
    name="profile_photo"
    accept="image/jpeg,image/png,image/webp"
    hidden
  >

  <div class="account__photo-actions">
    <label
      class="account__button account__button--primary account-photo-form__upload"
      for="profile-photo-input"
      data-profile-photo-upload-label
    >
      <span class="account-photo-form__button-text">
        Загрузить фото
      </span>

      <span class="account-photo-form__spinner" aria-hidden="true"></span>
    </label>

    <button
      class="account__button account__button--ghost account-photo-form__delete"
      type="button"
      data-profile-photo-delete
    >
      <span class="account-photo-form__button-text">
        Удалить фото
      </span>

      <span class="account-photo-form__spinner" aria-hidden="true"></span>
    </button>
  </div>

  <div
    class="account-photo-form__progress"
    data-profile-photo-progress
    hidden
  >
    <div class="account-photo-form__progress-track">
      <div
        class="account-photo-form__progress-bar"
        data-profile-photo-progress-bar
        style="width: 0%;"
      ></div>
    </div>

    <span class="account-photo-form__progress-text" data-profile-photo-progress-text>
      0%
    </span>
  </div>

  <p class="account-photo-form__message" data-profile-photo-message aria-live="polite"></p>
</form>

            
          </aside>

          <section class="account__info-panel" aria-labelledby="profile-info-title">
            <div class="account__panel-header">
              <h2 class="account__section-title" id="profile-info-title">
                Основная информация
              </h2>

              <button class="account__edit-button" type="button">
                Редактировать
              </button>
            </div>
            
            <div class="account-field-list">
 
<form class="account-field" method="post" data-profile-field-form>
  <?php wp_nonce_field('dating_update_profile_field_action', 'dating_update_profile_field_nonce'); ?>

  <input type="hidden" name="dating_profile_field" value="name">

  <label class="account-field__label" for="profile-name">ФИО</label>

  <input
    class="account-field__input"
    id="profile-name"
    type="text"
    name="dating_profile_value"
    value="<?php echo esc_attr($name ?: ''); ?>"
    disabled
    required
  >

  <button class="account-field__button" type="button" data-profile-edit>
    Изменить
  </button>

  <button
    class="account-field__button account-field__button--save"
    type="submit"
    name="dating_update_profile_field"
    hidden
  >
    Сохранить
  </button>
</form>
  
  <form class="account-field" method="post" data-profile-field-form>
  <?php wp_nonce_field('dating_update_profile_field_action', 'dating_update_profile_field_nonce'); ?>

  <input type="hidden" name="dating_profile_field" value="country">

  <label class="account-field__label" for="profile-country">Страна</label>

  <input
    class="account-field__input"
    id="profile-country"
    type="text"
    name="dating_profile_value"
    value="<?php echo esc_attr($country ?: ''); ?>"
    disabled
  >

  <button class="account-field__button" type="button" data-profile-edit>
    Изменить
  </button>

  <button
    class="account-field__button account-field__button--save"
    type="submit"
    name="dating_update_profile_field"
    hidden
  >
    Сохранить
  </button>
</form>

<form class="account-field" method="post" data-profile-field-form>
  <?php wp_nonce_field('dating_update_profile_field_action', 'dating_update_profile_field_nonce'); ?>

  <input type="hidden" name="dating_profile_field" value="city">

  <label class="account-field__label" for="profile-city">Город</label>

  <input
    class="account-field__input"
    id="profile-city"
    type="text"
    name="dating_profile_value"
    value="<?php echo esc_attr($city ?: ''); ?>"
    disabled
  >

  <button class="account-field__button" type="button" data-profile-edit>
    Изменить
  </button>

  <button
    class="account-field__button account-field__button--save"
    type="submit"
    name="dating_update_profile_field"
    hidden
  >
    Сохранить
  </button>
</form>

<form class="account-field" method="post" data-profile-field-form>
  <?php wp_nonce_field('dating_update_profile_field_action', 'dating_update_profile_field_nonce'); ?>

  <input type="hidden" name="dating_profile_field" value="email">

  <label class="account-field__label" for="profile-email">E-mail</label>

  <input
    class="account-field__input"
    id="profile-email"
    type="email"
    name="dating_profile_value"
    value="<?php echo esc_attr($email ?: ''); ?>"
    disabled
    required
  >

  <button class="account-field__button" type="button" data-profile-edit>
    Изменить
  </button>

  <button
    class="account-field__button account-field__button--save"
    type="submit"
    name="dating_update_profile_field"
    hidden
  >
    Сохранить
  </button>
</form>

<form class="account-field" method="post" data-profile-field-form>
  <?php wp_nonce_field('dating_update_profile_field_action', 'dating_update_profile_field_nonce'); ?>

  <input type="hidden" name="dating_profile_field" value="phone">

  <label class="account-field__label" for="profile-phone">Телефон</label>

  <input
    class="account-field__input"
    id="profile-phone"
    type="text"
    name="dating_profile_value"
    value="<?php echo esc_attr($phone ?: ''); ?>"
    disabled
  >

  <button class="account-field__button" type="button" data-profile-edit>
    Изменить
  </button>

  <button
    class="account-field__button account-field__button--save"
    type="submit"
    name="dating_update_profile_field"
    hidden
  >
    Сохранить
  </button>
</form>


</div>	

            <div class="account__notice" role="note">
              <span class="account__notice-icon">ⓘ</span>

              <p class="account__notice-text">
                После редактирования данные могут быть отправлены на повторную модерацию.
              </p>
            </div>
          </section>

        </div>

        <section class="account__actions" aria-label="Действия с профилем">
        
        <a
	  class="account__button account__button--primary"
	  href="<?php echo esc_url(home_url('/find-match/')); ?>"
	>
	  <?php echo esc_html($find_match_button_label); ?>
	</a>
        
          <!-- button class="account__button account__button--primary" type="button">
            Редактировать профиль
          </button -->

          <a
  class="account__button account__button--secondary account__button--messages"
  href="<?php echo esc_url(home_url('/messages/')); ?>"
>
  <span class="account__button-text">
    Сообщения
  </span>

  <?php if (!empty($unread_messages_count)) : ?>
    <span class="account__button-badge">
      <?php echo esc_html($unread_messages_count); ?> новых
    </span>
  <?php endif; ?>
</a>

          <a
		  class="account__button account__button--secondary"
		  href="<?php echo esc_url(home_url('/edit-profile/')); ?>"
		>
		  Редактировать анкету
		</a>

          <a class="account__button account__button--secondary" href="<?php echo esc_url($profile_permalink); ?>">
            Предпросмотр анкеты
          </a>
          
           <a class="account__button account__button--logout"
	    href="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>"
	  >Выйти из профиля</a>
          
          <form class="account-delete-form" method="post" data-profile-delete-form>
  <?php wp_nonce_field('dating_delete_profile_action', 'dating_delete_profile_nonce'); ?>

  <button
    class="account__button account__button--danger"
    type="submit"
    name="dating_delete_profile"
  >
    Удалить профиль
  </button>

  
</form>
          
        </section>

      <?php else : ?>

        <section class="account__empty" aria-label="Профиль не найден">
          <h2 class="account__section-title">
            У вас пока нет анкеты
          </h2>

          <p class="account__description">
            Зарегистрированный пользователь найден, но связанная анкета профиля ещё не создана.
          </p>

          <button class="account__button account__button--primary" type="button">
            Создать анкету
          </button>
        </section>

      <?php endif; ?>

      <footer class="account__footer">
  <form class="account-support-form" method="post">
    <?php wp_nonce_field('dating_start_support_conversation_action', 'dating_start_support_conversation_nonce'); ?>

    <button
      class="account__admin-link account-support-form__button"
      type="submit"
      name="dating_start_support_conversation"
      value="1"
    >
      Написать администрации
    </button>
  </form>
</footer>

    </div>
  </section>
</main>

<?php get_footer(); ?>
