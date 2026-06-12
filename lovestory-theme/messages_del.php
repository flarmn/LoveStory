<?php
/**
 * Template Name: Сообщения
 */

get_header();

if (!is_user_logged_in()) {
    wp_safe_redirect(home_url('/'));
    exit;
}

global $wpdb;

$current_user_id = get_current_user_id();

$current_user = wp_get_current_user();
$current_user_email = $current_user->user_email;
$current_user_nickname = strstr($current_user_email, '@', true);

if (!$current_user_nickname) {
    $current_user_nickname = $current_user->display_name;
}

/* добавляем режим активных / скрытых диалогов */
$messages_view = isset($_GET['view'])
    ? sanitize_key(wp_unslash($_GET['view']))
    : 'active';

if (!in_array($messages_view, ['active', 'hidden'], true)) {
    $messages_view = 'active';
}

/* добавляем режим активных / скрытых диалогов END */

$conversations_table = $wpdb->prefix . 'dating_conversations';
$messages_table = $wpdb->prefix . 'dating_messages';

$active_conversation_id = isset($_GET['conversation_id'])
    ? absint($_GET['conversation_id'])
    : 0;

$hidden_condition = $messages_view === 'hidden'
    ? "(
        (user_one_id = %d AND user_one_hidden = 1)
        OR
        (user_two_id = %d AND user_two_hidden = 1)
    )"
    : "(
        (user_one_id = %d AND user_one_hidden = 0)
        OR
        (user_two_id = %d AND user_two_hidden = 0)
    )";

$conversations = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT *
         FROM {$conversations_table}
         WHERE {$hidden_condition}
         ORDER BY updated_at DESC",
        $current_user_id,
        $current_user_id
    )
);

if (!$active_conversation_id && !empty($conversations)) {
    $active_conversation_id = (int) $conversations[0]->id;
}

$active_conversation = null;
$messages = [];

$is_support_conversation = false;

if ($active_conversation_id && dating_user_can_access_conversation($active_conversation_id, $current_user_id)) {
    $active_conversation = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT *
             FROM {$conversations_table}
             WHERE id = %d
             LIMIT 1",
            $active_conversation_id
        )
    );

    if ($active_conversation) {
        $active_hidden_field = function_exists('dating_get_conversation_hidden_field_for_user')
            ? dating_get_conversation_hidden_field_for_user($active_conversation, $current_user_id)
            : '';

        $is_active_conversation_hidden = $active_hidden_field
            ? !empty($active_conversation->{$active_hidden_field})
            : false;

        if (
            ($messages_view === 'active' && $is_active_conversation_hidden) ||
            ($messages_view === 'hidden' && !$is_active_conversation_hidden)
        ) {
            $active_conversation = null;
            $active_conversation_id = 0;
        }
    }

    if ($active_conversation) {
        $messages = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT *
                 FROM {$messages_table}
                 WHERE conversation_id = %d
                 ORDER BY created_at ASC
                 LIMIT 100",
                $active_conversation_id
            )
        );

        $wpdb->update(
            $messages_table,
            [
                'is_read' => 1,
            ],
            [
                'conversation_id' => $active_conversation_id,
                'recipient_id'    => $current_user_id,
                'is_read'         => 0,
            ],
            [
                '%d',
            ],
            [
                '%d',
                '%d',
                '%d',
            ]
        );
    }
}
if (
    $active_conversation &&
    function_exists('dating_get_support_user_id')
) {
    $support_user_id = (int) dating_get_support_user_id();

    $conversation_user_one = (int) $active_conversation->user_one_id;
    $conversation_user_two = (int) $active_conversation->user_two_id;

    $is_support_conversation =
        $support_user_id > 0 &&
        (
            $conversation_user_one === $support_user_id ||
            $conversation_user_two === $support_user_id
        );
}

function lovestory_get_chat_partner_id($conversation, $current_user_id) {
    if (!$conversation) {
        return 0;
    }

    return ((int) $conversation->user_one_id === (int) $current_user_id)
        ? (int) $conversation->user_two_id
        : (int) $conversation->user_one_id;
}

function lovestory_get_user_profile_by_author($user_id) {
    $profiles = get_posts([
        'post_type'      => 'profile',
        'post_status'    => 'any',
        'author'         => absint($user_id),
        'posts_per_page' => 1,
    ]);

    return !empty($profiles) ? $profiles[0] : null;
}
?>

<main class="messages-page">
  <section class="messages" aria-labelledby="messages-title" data-messages-root>
    <div class="messages__container">

      <header class="messages__header">
  <div class="messages__header-main">
    <div>
      <p class="messages__eyebrow">LoveStory</p>

      <div class="messages__title-row">
  <h1 class="messages__title" id="messages-title">
    Сообщения
  </h1>

  

  <a class="messages__profile-exit" href="<?php echo esc_url(home_url('/my-profile/')); ?>">
    В профиль
  </a>
</div>

      <p class="messages__description">
        Здесь будут ваши диалоги с пользователями сайта.
      </p>
    </div>

    <div class="messages__current-user" aria-label="Текущий пользователь">
      <span class="messages__current-user-label">
        Вы вошли как
      </span>

      <a
        class="messages__current-user-name"
        href="<?php echo esc_url(home_url('/my-profile/')); ?>"
      >
        <?php echo esc_html($current_user_nickname); ?>
      </a>
    </div>

   

  </div>
</header>

<?php
$mobile_unread_count = function_exists('dating_get_total_unread_messages_count')
    ? dating_get_total_unread_messages_count($current_user_id)
    : 0;
?>

<button
  class="messages__dialogs-toggle"
  type="button"
  aria-label="Открыть диалоги"
  aria-expanded="false"
  data-dialogs-toggle
>
  <span class="messages__dialogs-toggle-icon" aria-hidden="true">💬</span>

  <span
    class="messages__dialogs-toggle-badge"
    data-dialogs-toggle-badge
    <?php echo $mobile_unread_count > 0 ? '' : 'hidden'; ?>
  >
    <?php echo esc_html($mobile_unread_count); ?>
  </span>
</button>

<div class="messages__dialogs-overlay" data-dialogs-overlay></div>

      <div class="messages__layout <?php echo $active_conversation ? 'messages__layout--chat-open' : 'messages__layout--dialogs-open'; ?>">

        <aside class="messages__sidebar" aria-label="Список диалогов">
          <h2 class="messages__sidebar-title">Диалоги</h2>

          <div class="messages__tabs" aria-label="Фильтр диалогов">
  <a
    class="messages__tab <?php echo $messages_view === 'active' ? 'messages__tab--active' : ''; ?>"
    href="<?php echo esc_url(home_url('/messages/')); ?>"
  >
    Активные
  </a>

  <a
    class="messages__tab <?php echo $messages_view === 'hidden' ? 'messages__tab--active' : ''; ?>"
    href="<?php echo esc_url(add_query_arg('view', 'hidden', home_url('/messages/'))); ?>"
  >
    Скрытые
  </a>
</div>

          <?php if (!empty($conversations)) : ?>
            <ul
              class="messages__dialog-list"
              data-dialog-list
              data-messages-view="<?php echo esc_attr($messages_view); ?>"
            >
              <?php foreach ($conversations as $conversation) : ?>
                <?php
                $partner_id = lovestory_get_chat_partner_id($conversation, $current_user_id);
                $partner_user = get_userdata($partner_id);
                $partner_profile = lovestory_get_user_profile_by_author($partner_id);

                $partner_name = $partner_profile
                    ? get_the_title($partner_profile->ID)
                    : ($partner_user ? $partner_user->display_name : 'Пользователь');

                $partner_photo = $partner_profile && has_post_thumbnail($partner_profile->ID)
                    ? get_the_post_thumbnail_url($partner_profile->ID, 'thumbnail')
                    : get_template_directory_uri() . '/img/pics/profile-main.jpg';

                $is_active = (int) $conversation->id === (int) $active_conversation_id;
                $unread_count = dating_get_unread_messages_count($conversation->id, $current_user_id);


                /* покажи последнее сообщение при первичной загрузке */
                $last_message = function_exists('dating_get_conversation_last_message')
    ? dating_get_conversation_last_message($conversation->id)
    : null;

$last_message_preview = '';

if ($last_message) {
    $last_message_text = wp_strip_all_tags($last_message->message_text);
    $last_message_text = wp_html_excerpt($last_message_text, 70, '...');

    $last_message_preview = ((int) $last_message->sender_id === (int) $current_user_id)
        ? 'Вы: ' . $last_message_text
        : $last_message_text;
}
                ?>

                <li class="messages__dialog-item">
                  <a
  class="messages__dialog-link <?php echo $is_active ? 'messages__dialog-link--active' : ''; ?> <?php echo $unread_count > 0 ? 'messages__dialog-link--unread' : ''; ?>"
  href="<?php echo esc_url(add_query_arg([
    'conversation_id' => $conversation->id,
    'view'            => $messages_view === 'hidden' ? 'hidden' : null,
], home_url('/messages/'))); ?>"
>
                    <img
                      class="messages__dialog-avatar"
                      src="<?php echo esc_url($partner_photo); ?>"
                      alt="<?php echo esc_attr($partner_name); ?>"
                    >

                    <span class="messages__dialog-info">
                      <span class="messages__dialog-name">
                        <?php echo esc_html($partner_name); ?>
                      </span>

                      <span class="messages__dialog-date">
                        <?php echo esc_html(mysql2date('d.m.Y H:i', $conversation->updated_at)); ?>
                      </span>

                      <?php if ($last_message_preview) : ?>
  <span class="messages__dialog-preview">
    <?php echo esc_html($last_message_preview); ?>
  </span>
<?php else : ?>
  <span class="messages__dialog-preview messages__dialog-preview--empty">
    Диалог создан
  </span>
<?php endif; ?>

                      <?php if ($unread_count > 0) : ?>
  <span class="messages__dialog-unread">
    <?php echo esc_html($unread_count); ?> новых
  </span>
<?php endif; ?>
                    </span>
                  </a>
                  <button
  class="messages__dialog-action"
  type="button"
  data-conversation-action="<?php echo $messages_view === 'hidden' ? 'restore' : 'hide'; ?>"
  data-conversation-id="<?php echo esc_attr($conversation->id); ?>"
>
  <?php echo $messages_view === 'hidden' ? 'Вернуть' : 'Скрыть'; ?>
</button>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php else : ?>
            <p class="messages__empty-text">
              У вас пока нет диалогов.
            </p>
          <?php endif; ?>
        </aside>

        <section
  class="messages__chat"
  aria-label="Окно переписки"
  data-conversation-id="<?php echo esc_attr($active_conversation_id); ?>"
>
          <?php if ($active_conversation) : ?>
            <?php
            $partner_id = lovestory_get_chat_partner_id($active_conversation, $current_user_id);
            $partner_user = get_userdata($partner_id);
            $partner_profile = lovestory_get_user_profile_by_author($partner_id);

            $partner_name = $partner_profile
                ? get_the_title($partner_profile->ID)
                : ($partner_user ? $partner_user->display_name : 'Пользователь');

            $partner_profile_url = $partner_profile
                ? get_permalink($partner_profile->ID)
                : '#';
            ?>

            <header class="messages__chat-header">
              <a class="messages__back-link" href="<?php echo esc_url(home_url('/messages/')); ?>">
                ← К диалогам
              </a>
              <div>
                <p class="messages__chat-kicker">Диалог</p>
                <h2 class="messages__chat-title">
                  <?php echo esc_html($partner_name); ?>
                </h2>

                 <?php if ($is_support_conversation) : ?>
                  <div class="messages__support-notice" role="note">
                    Администрация LoveStory обычно отвечает на обращения в течение 24 часов.
                  </div>
                <?php endif; ?>
              </div>



              <?php if ($partner_profile) : ?>
                <a class="messages__profile-link" href="<?php echo esc_url($partner_profile_url); ?>">
                  Открыть анкету
                </a>
              <?php endif; ?>
            </header>

            <div class="messages__chat-body" data-messages-body>
              <?php if (!empty($messages)) : ?>
             <?php foreach ($messages as $message) : ?>
  <?php
  $is_own_message = (int) $message->sender_id === (int) $current_user_id;
  $is_deleted_message = !empty($message->is_deleted);
  $is_edited_message = !$is_deleted_message && !empty($message->edited_at);
  ?>

  <article
    class="messages__bubble <?php echo $is_own_message ? 'messages__bubble--own' : 'messages__bubble--partner'; ?> <?php echo $is_deleted_message ? 'messages__bubble--deleted' : ''; ?>"
    data-message-id="<?php echo esc_attr($message->id); ?>"
    <?php echo $is_own_message ? 'data-own-message="1"' : ''; ?>
    <?php echo $is_deleted_message ? 'data-message-deleted="1"' : ''; ?>
  >
    <p class="messages__bubble-text" data-message-text>
      <?php echo esc_html($is_deleted_message ? 'Сообщение удалено' : $message->message_text); ?>
    </p>

    <div class="messages__bubble-meta">
      <time class="messages__bubble-time" datetime="<?php echo esc_attr($message->created_at); ?>">
        <?php echo esc_html(mysql2date('d.m.Y H:i', $message->created_at)); ?>
      </time>

      <?php if ($is_edited_message) : ?>
        <span class="messages__bubble-edited" data-message-edited>
          изменено
        </span>
      <?php endif; ?>
    </div>

    <?php if ($is_own_message && !$is_deleted_message) : ?>
      <div class="messages__bubble-actions" data-message-actions>
        <button
          class="messages__bubble-action"
          type="button"
          data-message-edit-trigger
        >
          Изменить
        </button>

        <button
          class="messages__bubble-action messages__bubble-action--delete"
          type="button"
          data-message-delete-trigger
        >
          Удалить
        </button>
      </div>
    <?php endif; ?>
  </article>
<?php endforeach; ?>
              <?php else : ?>
                <p class="messages__empty-text">
                  Диалог создан. Напишите первое сообщение.
                </p>
              <?php endif; ?>
            </div>

            <form class="messages__form" method="post" data-message-form>
  <textarea
    class="messages__textarea"
    name="message_text"
    rows="3"
    maxlength="1000"
    placeholder="Напишите сообщение..."
  ></textarea>

  <p class="messages__form-message" data-message-form-message aria-live="polite"></p>

  <button class="messages__submit" type="submit">
    Отправить
  </button>
</form>

          <?php else : ?>
            <div class="messages__empty-state">
              <h2 class="messages__empty-title">
                Выберите диалог
              </h2>

              <p class="messages__empty-text">
                Когда вы начнёте переписку, она появится здесь.
              </p>
            </div>
          <?php endif; ?>
        </section>

      </div>

    </div>
  </section>


  <!-- action sheet -->
<div class="messages-action-sheet__overlay" hidden data-message-actions-overlay></div>

<div class="messages-action-sheet" hidden data-message-actions-sheet>
  <div class="messages-action-sheet__panel">
    <button
      class="messages-action-sheet__button"
      type="button"
      data-message-actions-edit
    >
      Изменить
    </button>

    <button
      class="messages-action-sheet__button messages-action-sheet__button--danger"
      type="button"
      data-message-actions-delete
    >
      Удалить
    </button>

    <button
      class="messages-action-sheet__button messages-action-sheet__button--cancel"
      type="button"
      data-message-actions-close
    >
      Отмена
    </button>
  </div>
</div>

</main>

<?php get_footer(); ?>
