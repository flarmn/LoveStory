
 <?php
/**
 * Plugin Name: Dating Profiles
 * Description: Анкеты и роли для сайта знакомств
 */

register_activation_hook(__FILE__, 'dating_profiles_activate');

function dating_profiles_activate() {
    add_role('dating_user', 'Пользователь с анкетой', [
        'read' => true,
        'upload_files' => true,
    ]);

    add_role('dating_moderator', 'Модератор анкет', [
        'read' => true,
        'edit_profiles' => true,
        'edit_others_profiles' => true,
        'read_private_profiles' => true,
    ]);

    $admin = get_role('administrator');

    if ($admin) {
        $caps = [
            'edit_profile',
            'read_profile',
            'delete_profile',
            'edit_profiles',
            'edit_others_profiles',
            'publish_profiles',
            'read_private_profiles',
            'delete_profiles',
            'delete_others_profiles',
            'delete_private_profiles',
            'delete_published_profiles',
            'edit_private_profiles',
            'edit_published_profiles',
        ];

        foreach ($caps as $cap) {
            $admin->add_cap($cap);
        }
    }
}

add_action('admin_notices', function () {
    echo '<div class="notice notice-success"><p>Dating Profiles plugin работает.</p></div>';
});



add_action('init', 'dating_register_profile_post_type');

function dating_register_profile_post_type() {
    register_post_type('profile', [
        'labels' => [
            'name' => 'Анкеты',
            'singular_name' => 'Анкета',
            'add_new_item' => 'Добавить анкету',
            'edit_item' => 'Редактировать анкету',
        ],
        'public' => true,
        'has_archive' => true,
        'rewrite' => [
        'slug' => 'profile',
    	],
        'menu_icon' => 'dashicons-id',
        'supports' => ['title', 'editor', 'thumbnail', 'author', 'custom-fields'],
        'capability_type' => ['profile', 'profiles'],
        'map_meta_cap' => true,
        'show_in_rest' => true,
    ]);
}

add_action('init', 'dating_register_profile_meta');

function dating_register_profile_meta() {
    $fields = [
        'gender' => 'string',
        'age' => 'integer',
        'city' => 'string',
        'profile_status' => 'string',
    ];

    foreach ($fields as $key => $type) {
        register_post_meta('profile', $key, [
            'type' => $type,
            'single' => true,
            'show_in_rest' => true,
            'sanitize_callback' => 'sanitize_text_field',
            'auth_callback' => function() {
                return current_user_can('edit_profiles');
            },
        ]);
    }
}

function dating_profile_has_required_search_fields($profile_id) {
    $profile_id = absint($profile_id);

    if (!$profile_id || get_post_type($profile_id) !== 'profile') {
        return false;
    }

    $gender = get_post_meta($profile_id, 'gender', true);
    $age = absint(get_post_meta($profile_id, 'age', true));
    $relationship_goal = get_post_meta($profile_id, 'relationship_goal', true);

    return (
        in_array($gender, ['male', 'female'], true) &&
        $age >= 18 &&
        $age <= 99 &&
        in_array($relationship_goal, ['communication', 'serious_relationship', 'family'], true)
    );
}


function dating_mark_profile_pending_after_user_changes($profile_id) {
    $profile_id = absint($profile_id);

    if (!$profile_id || get_post_type($profile_id) !== 'profile') {
        return false;
    }

    $current_status = get_post_meta($profile_id, 'profile_status', true);

    if ($current_status === 'deleted_by_user') {
        return false;
    }

    update_post_meta($profile_id, 'profile_status', 'pending');

    return true;
}



function dating_create_profile_for_user($user_id, $gender = '', $city = '') {
    $profile_id = wp_insert_post([
        'post_type' => 'profile',
        'post_title' => 'Анкета пользователя #' . $user_id,
        'post_status' => 'publish',
        'post_author' => $user_id,
    ]);

    if (!is_wp_error($profile_id)) {
        update_post_meta($profile_id, 'gender', sanitize_text_field($gender));
        update_post_meta($profile_id, 'city', sanitize_text_field($city));
        update_post_meta($profile_id, 'profile_status', 'pending');
    }

    return $profile_id;
}

add_shortcode('dating_register_form', 'dating_register_form_shortcode');

function dating_register_form_shortcode() {
    ob_start();
    ?>
    
    <form method="post">
        <input type="text" name="dating_name" placeholder="Имя">
        <input type="email" name="dating_email" placeholder="Email">
        <input type="password" name="dating_password" placeholder="Пароль">

        <select name="dating_gender">
            <option value="male">Мужчина</option>
            <option value="female">Женщина</option>
        </select>

        <input type="text" name="dating_city" placeholder="Город">

        <button type="submit" name="dating_register">Зарегистрироваться</button>
    </form>

    <?php
    return ob_get_clean();
}


add_shortcode('active_profiles', 'dating_active_profiles_shortcode');

function dating_active_profiles_shortcode() {
    $query = new WP_Query([
        'post_type' => 'profile',
        'post_status' => 'publish',
        'meta_query' => [
            [
                'key' => 'profile_status',
                'value' => 'active',
                'compare' => '=',
            ],
        ],
    ]);

    ob_start();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();

            echo '<h2>' . get_the_title() . '</h2>';
        }
    } else {
        echo 'Нет анкет';
    }

    wp_reset_postdata();

    return ob_get_clean();
}

add_action('save_post_profile', function($post_id) {
    if (wp_is_post_revision($post_id)) return;

    if (!get_post_meta($post_id, 'profile_status', true)) {
        update_post_meta($post_id, 'profile_status', 'active');
    }
});

function dating_delete_profile($profile_id) {
    if (!current_user_can('delete_others_profiles')) {
        return false;
    }

    wp_delete_post($profile_id, true);

    return true;
}



add_shortcode('dating_register_modal', 'dating_register_modal_shortcode');

function dating_register_modal_shortcode() {
    if (is_user_logged_in()) {
        return '';
    }

    ob_start();
    ?>

    <button class="dating-register-button" type="button" data-register-open>
        Зарегистрироваться
    </button>

    <div class="dating-modal" data-register-modal hidden>
       

        <div class="dating-modal__content" role="dialog" aria-modal="true" aria-labelledby="dating-register-title">
            <button class="dating-modal__close" type="button" data-register-close aria-label="Закрыть">
                ×
            </button>

            <h2 class="dating-modal__title" id="dating-register-title">
                Регистрация
            </h2>

            <form class="dating-register-form" method="post">
                <?php wp_nonce_field('dating_register_action', 'dating_register_nonce'); ?>

                <label class="dating-register-form__field">
                    <span>Имя</span>
                    <input type="text" name="dating_name" required>
                </label>

                <label class="dating-register-form__field">
                    <span>Email</span>
                    <input type="email" name="dating_email" required>
                </label>

                <label class="dating-register-form__field">
                    <span>Пароль</span>
                    <input type="password" name="dating_password" required minlength="6">
                </label>

                <label class="dating-register-form__field">
                    <span>Пол</span>
                    <select name="dating_gender" required>
                        <option value="">Выберите пол</option>
                        <option value="male">Мужчина</option>
                        <option value="female">Женщина</option>
                    </select>
                </label>

                <label class="dating-register-form__field">
                    <span>Возраст</span>
                    <input
                        type="number"
                        name="dating_age"
                        min="18"
                        max="99"
                        required
                    >
                </label>

                <label class="dating-register-form__field">
                    <span>Город</span>
                    <input type="text" name="dating_city" required>
                </label>

                <label class="dating-register-form__field">
                    <span>Цель знакомства</span>
                    <select name="dating_relationship_goal" required>
                        <option value="">Выберите цель</option>
                        <option value="communication">Общение</option>
                        <option value="serious_relationship">Серьёзные отношения</option>
                        <option value="family">Создание семьи</option>
                    </select>
                </label>

                <button class="dating-register-form__submit" type="submit" name="dating_register">
                    Создать анкету
                </button>
            </form>
        </div>
         <div class="dating-modal__overlay" data-register-close></div>
    </div>

    <?php
    return ob_get_clean();
}



add_action('init', 'dating_handle_frontend_registration');

function dating_handle_frontend_registration() {
    if (!isset($_POST['dating_register'])) {
        return;
    }

    if (
        !isset($_POST['dating_register_nonce']) ||
        !wp_verify_nonce($_POST['dating_register_nonce'], 'dating_register_action')
    ) {
        wp_die('Ошибка безопасности. Попробуйте ещё раз.');
    }

    $name = isset($_POST['dating_name']) ? sanitize_text_field($_POST['dating_name']) : '';
    $email = isset($_POST['dating_email']) ? sanitize_email($_POST['dating_email']) : '';
    $password = isset($_POST['dating_password']) ? $_POST['dating_password'] : '';
    $gender = isset($_POST['dating_gender']) ? sanitize_text_field($_POST['dating_gender']) : '';
    $age = isset($_POST['dating_age']) ? absint($_POST['dating_age']) : 0;
    $city = isset($_POST['dating_city']) ? sanitize_text_field($_POST['dating_city']) : '';
    $relationship_goal = isset($_POST['dating_relationship_goal'])
    ? sanitize_key($_POST['dating_relationship_goal'])
    : '';


    if (!$name || !$email || !$password || !$gender || !$age || !$city || !$relationship_goal) {
        wp_die('Заполните все поля.');
    }

    if (!is_email($email)) {
        wp_die('Введите корректный email.');
    }

    if (email_exists($email)) {
        wp_die('Пользователь с таким email уже существует.');
    }

    if (!in_array($gender, ['male', 'female'], true)) {
        wp_die('Некорректное значение пола.');
    }

    if ($age < 18 || $age > 99) {
        wp_die('Возраст должен быть от 18 до 99 лет.');
    }

    $allowed_goals = [
    'communication',
    'serious_relationship',
    'family',
    ];

    if (!in_array($relationship_goal, $allowed_goals, true)) {
        wp_die('Некорректная цель знакомства.');
    }


    $user_id = wp_insert_user([
        'user_login' => $email,
        'user_email' => $email,
        'user_pass' => $password,
        'display_name' => $name,
        'role' => 'dating_user',
    ]);

    if (is_wp_error($user_id)) {
        wp_die($user_id->get_error_message());
    }

    update_user_meta($user_id, 'dating_age', $age);

    $profile_id = wp_insert_post([
        'post_type' => 'profile',
        'post_title' => $name,
        'post_status' => 'publish',
        'post_author' => $user_id,
        'post_content' => '',
    ]);

    if (!is_wp_error($profile_id)) {
        update_post_meta($profile_id, 'gender', $gender);
        update_post_meta($profile_id, 'age', $age);
        update_post_meta($profile_id, 'city', $city);
        update_post_meta($profile_id, 'relationship_goal', $relationship_goal);
        update_post_meta($profile_id, 'profile_status', 'active');
    }

    wp_set_current_user($user_id);
wp_set_auth_cookie($user_id);

wp_safe_redirect(home_url('/my-profile/'));
  
    exit;
}

add_action('wp_enqueue_scripts', 'dating_enqueue_assets');

function dating_enqueue_assets() {
    wp_enqueue_script(
        'dating-register-modal',
        plugin_dir_url(__FILE__) . 'assets/js/register-modal.js',
        [],
        '1.0.0',
        true
    );

    wp_enqueue_style(
        'dating-register-modal',
        plugin_dir_url(__FILE__) . 'assets/css/register-modal.css',
        [],
        '1.0.0'
    );
}


/* Запретить обычному пользователю вход в админку */
add_action('admin_init', 'dating_restrict_wp_admin_for_users');

function dating_restrict_wp_admin_for_users() {
    if (
        is_admin() &&
        !current_user_can('administrator') &&
        !current_user_can('dating_moderator') &&
        !(defined('DOING_AJAX') && DOING_AJAX)
    ) {
        wp_safe_redirect(home_url('/'));
        exit;
    }
}

/* Скрыть верхнюю админ-панель для обычных пользователей */
add_action('after_setup_theme', 'dating_hide_admin_bar_for_dating_users');

function dating_hide_admin_bar_for_dating_users() {
    if (
        is_user_logged_in() &&
        !current_user_can('manage_options') &&
        !current_user_can('edit_others_profiles')
    ) {
        show_admin_bar(false);
    }
}


add_action('init', 'dating_handle_profile_field_update');

function dating_handle_profile_field_update() {
    if (!isset($_POST['dating_update_profile_field'])) {
        return;
    }

    if (!is_user_logged_in()) {
        wp_safe_redirect(home_url('/'));
        exit;
    }

    if (
        !isset($_POST['dating_update_profile_field_nonce']) ||
        !wp_verify_nonce($_POST['dating_update_profile_field_nonce'], 'dating_update_profile_field_action')
    ) {
        wp_die('Ошибка безопасности. Попробуйте ещё раз.');
    }

    $current_user_id = get_current_user_id();

    $user_profiles = get_posts([
        'post_type'      => 'profile',
        'post_status'    => 'any',
        'author'         => $current_user_id,
        'posts_per_page' => 1,
    ]);

    if (empty($user_profiles)) {
        wp_die('Анкета пользователя не найдена.');
    }

    $profile_id = $user_profiles[0]->ID;

    $field = isset($_POST['dating_profile_field']) 
        ? sanitize_key($_POST['dating_profile_field']) 
        : '';

    $value = isset($_POST['dating_profile_value']) 
        ? sanitize_text_field($_POST['dating_profile_value']) 
        : '';

    $allowed_fields = ['name', 'country', 'city', 'email', 'phone'];

    if (!in_array($field, $allowed_fields, true)) {
        wp_die('Некорректное поле.');
    }

    if ($field === 'name') {
        if (!$value) {
            wp_die('ФИО не может быть пустым.');
        }

        wp_update_post([
            'ID'         => $profile_id,
            'post_title' => $value,
        ]);
    }

    if ($field === 'country') {
        update_post_meta($profile_id, 'country', $value);
    }

    if ($field === 'city') {
        update_post_meta($profile_id, 'city', $value);
    }

    if ($field === 'phone') {
        update_post_meta($profile_id, 'phone', $value);
    }

    if ($field === 'email') {
        if (!is_email($value)) {
            wp_die('Введите корректный E-mail.');
        }

        $existing_user_id = email_exists($value);

        if ($existing_user_id && (int) $existing_user_id !== (int) $current_user_id) {
            wp_die('Пользователь с таким E-mail уже существует.');
        }

        $updated_user = wp_update_user([
            'ID'         => $current_user_id,
            'user_email' => $value,
            'user_login' => $value,
        ]);

        if (is_wp_error($updated_user)) {
            wp_die($updated_user->get_error_message());
        }
    }

    /*
     * Если после любого изменения нужно отправлять профиль на повторную модерацию:
     */
    // update_post_meta($profile_id, 'profile_status', 'pending');

    wp_safe_redirect(add_query_arg([
        'profile_field_updated' => $field,
    ], home_url('/my-profile/')));

    exit;
}


/* ***** */

add_action('wp_ajax_dating_update_profile_field', 'dating_ajax_update_profile_field');

function dating_ajax_update_profile_field() {
    if (!is_user_logged_in()) {
        wp_send_json_error([
            'message' => 'Вы не авторизованы.',
        ], 401);
    }

    if (
        !isset($_POST['nonce']) ||
        !wp_verify_nonce($_POST['nonce'], 'dating_update_profile_field_ajax')
    ) {
        wp_send_json_error([
            'message' => 'Ошибка безопасности. Обновите страницу и попробуйте снова.',
        ], 403);
    }

    $current_user_id = get_current_user_id();

    $user_profiles = get_posts([
        'post_type'      => 'profile',
        'post_status'    => 'any',
        'author'         => $current_user_id,
        'posts_per_page' => 1,
    ]);

    if (empty($user_profiles)) {
        wp_send_json_error([
            'message' => 'Анкета пользователя не найдена.',
        ], 404);
    }

    $profile_id = $user_profiles[0]->ID;

    $field = isset($_POST['field']) ? sanitize_key($_POST['field']) : '';
    $value = isset($_POST['value']) ? sanitize_text_field(wp_unslash($_POST['value'])) : '';

    $allowed_fields = ['name', 'country', 'city', 'email', 'phone'];

    if (!in_array($field, $allowed_fields, true)) {
        wp_send_json_error([
            'message' => 'Некорректное поле.',
        ], 400);
    }

    if ($field === 'name') {
        if (!$value) {
            wp_send_json_error([
                'message' => 'ФИО не может быть пустым.',
            ], 400);
        }

        wp_update_post([
            'ID'         => $profile_id,
            'post_title' => $value,
        ]);
    }

    if ($field === 'country') {
        update_post_meta($profile_id, 'country', $value);
    }

    if ($field === 'city') {
        update_post_meta($profile_id, 'city', $value);
    }

    if ($field === 'phone') {
        update_post_meta($profile_id, 'phone', $value);
    }

    if ($field === 'email') {
        if (!is_email($value)) {
            wp_send_json_error([
                'message' => 'Введите корректный E-mail.',
            ], 400);
        }

        $existing_user_id = email_exists($value);

        if ($existing_user_id && (int) $existing_user_id !== (int) $current_user_id) {
            wp_send_json_error([
                'message' => 'Пользователь с таким E-mail уже существует.',
            ], 400);
        }

        $updated_user = wp_update_user([
            'ID'         => $current_user_id,
            'user_email' => $value,
        ]);

        if (is_wp_error($updated_user)) {
            wp_send_json_error([
                'message' => $updated_user->get_error_message(),
            ], 400);
        }
    }

    wp_send_json_success([
        'message' => 'Сохранено',
        'field'   => $field,
        'value'   => $value,
    ]);
}

/* ****** */



add_action('wp_ajax_dating_update_profile_field_ajax', 'dating_ajax_update_profile_field_ajax');

function dating_ajax_update_profile_field_ajax() {
    if (!is_user_logged_in()) {
        wp_send_json_error([
            'message' => 'Вы не авторизованы.',
        ], 401);
    }

    if (
        !isset($_POST['nonce']) ||
        !wp_verify_nonce($_POST['nonce'], 'dating_update_profile_field_ajax')
    ) {
        wp_send_json_error([
            'message' => 'Ошибка безопасности. Обновите страницу и попробуйте ещё раз.',
        ], 403);
    }

    $current_user_id = get_current_user_id();

    $user_profiles = get_posts([
        'post_type'      => 'profile',
        'post_status'    => 'any',
        'author'         => $current_user_id,
        'posts_per_page' => 1,
    ]);

    if (empty($user_profiles)) {
        wp_send_json_error([
            'message' => 'Анкета пользователя не найдена.',
        ], 404);
    }

    $profile_id = $user_profiles[0]->ID;

    $field = isset($_POST['field'])
        ? sanitize_key(wp_unslash($_POST['field']))
        : '';

    $value = isset($_POST['value'])
        ? sanitize_text_field(wp_unslash($_POST['value']))
        : '';

    $allowed_fields = ['name', 'country', 'city', 'email', 'phone'];

    if (!in_array($field, $allowed_fields, true)) {
        wp_send_json_error([
            'message' => 'Некорректное поле.',
        ], 400);
    }

    if ($field === 'name') {
        if ($value === '') {
            wp_send_json_error([
                'message' => 'ФИО не может быть пустым.',
            ], 400);
        }

        $updated_post_id = wp_update_post([
            'ID'         => $profile_id,
            'post_title' => $value,
        ], true);

        if (is_wp_error($updated_post_id)) {
            wp_send_json_error([
                'message' => $updated_post_id->get_error_message(),
            ], 400);
        }
    }

    if ($field === 'country') {
        update_post_meta($profile_id, 'country', $value);
    }

    if ($field === 'city') {
        update_post_meta($profile_id, 'city', $value);
    }

    if ($field === 'phone') {
        update_post_meta($profile_id, 'phone', $value);
    }

    if ($field === 'email') {
        if (!is_email($value)) {
            wp_send_json_error([
                'message' => 'Введите корректный E-mail.',
            ], 400);
        }

        $existing_user_id = email_exists($value);

        if ($existing_user_id && (int) $existing_user_id !== (int) $current_user_id) {
            wp_send_json_error([
                'message' => 'Пользователь с таким E-mail уже существует.',
            ], 400);
        }

        $updated_user_id = wp_update_user([
            'ID'         => $current_user_id,
            'user_email' => $value,
        ]);

        if (is_wp_error($updated_user_id)) {
            wp_send_json_error([
                'message' => $updated_user_id->get_error_message(),
            ], 400);
        }
    }

    /*
     * На будущее:
     * если после редактирования нужно отправлять профиль на повторную модерацию,
     * можно включить эту строку:
     */
    // update_post_meta($profile_id, 'profile_status', 'pending');

    wp_send_json_success([
        'message' => 'Сохранено.',
        'field'   => $field,
        'value'   => $value,
    ]);
}

/*  Uploading photo to user profile */

add_action('wp_ajax_dating_upload_profile_photo', 'dating_ajax_upload_profile_photo');

function dating_ajax_upload_profile_photo() {
    if (!is_user_logged_in()) {
        wp_send_json_error([
            'message' => 'Вы не авторизованы.',
        ], 401);
    }

    if (
        !isset($_POST['nonce']) ||
        !wp_verify_nonce($_POST['nonce'], 'dating_profile_photo_ajax')
    ) {
        wp_send_json_error([
            'message' => 'Ошибка безопасности. Обновите страницу и попробуйте ещё раз.',
        ], 403);
    }

    if (empty($_FILES['profile_photo'])) {
        wp_send_json_error([
            'message' => 'Файл не был загружен.',
        ], 400);
    }

    $current_user_id = get_current_user_id();

    $user_profiles = get_posts([
        'post_type'      => 'profile',
        'post_status'    => 'any',
        'author'         => $current_user_id,
        'posts_per_page' => 1,
    ]);

    if (empty($user_profiles)) {
        wp_send_json_error([
            'message' => 'Анкета пользователя не найдена.',
        ], 404);
    }

    $profile_id = $user_profiles[0]->ID;

    $file = $_FILES['profile_photo'];

    $allowed_types = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    if (!in_array($file['type'], $allowed_types, true)) {
        wp_send_json_error([
            'message' => 'Можно загрузить только JPG, PNG или WEBP.',
        ], 400);
    }

    $max_size = 5 * 1024 * 1024;

    if ((int) $file['size'] > $max_size) {
        wp_send_json_error([
            'message' => 'Размер файла не должен превышать 5 МБ.',
        ], 400);
    }

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $attachment_id = media_handle_upload('profile_photo', $profile_id);

    if (is_wp_error($attachment_id)) {
        wp_send_json_error([
            'message' => $attachment_id->get_error_message(),
        ], 400);
    }

    $old_thumbnail_id = get_post_thumbnail_id($profile_id);

    set_post_thumbnail($profile_id, $attachment_id);

    dating_mark_profile_pending_after_user_changes($profile_id);

    if ($old_thumbnail_id && (int) $old_thumbnail_id !== (int) $attachment_id) {
        wp_delete_attachment($old_thumbnail_id, true);
    }

    $photo_url = wp_get_attachment_image_url($attachment_id, 'large');

    wp_send_json_success([
        'message' => 'Фото профиля обновлено.',
        'photoUrl' => $photo_url,
    ]);
}

/* Deleting photos from user profile */

add_action('wp_ajax_dating_delete_profile_photo', 'dating_ajax_delete_profile_photo');

function dating_ajax_delete_profile_photo() {
    if (!is_user_logged_in()) {
        wp_send_json_error([
            'message' => 'Вы не авторизованы.',
        ], 401);
    }

    if (
        !isset($_POST['nonce']) ||
        !wp_verify_nonce($_POST['nonce'], 'dating_profile_photo_ajax')
    ) {
        wp_send_json_error([
            'message' => 'Ошибка безопасности. Обновите страницу и попробуйте ещё раз.',
        ], 403);
    }

    $current_user_id = get_current_user_id();

    $user_profiles = get_posts([
        'post_type'      => 'profile',
        'post_status'    => 'any',
        'author'         => $current_user_id,
        'posts_per_page' => 1,
    ]);

    if (empty($user_profiles)) {
        wp_send_json_error([
            'message' => 'Анкета пользователя не найдена.',
        ], 404);
    }

    $profile_id = $user_profiles[0]->ID;

    $thumbnail_id = get_post_thumbnail_id($profile_id);

    if (!$thumbnail_id) {
        wp_send_json_error([
            'message' => 'Фото профиля уже отсутствует.',
        ], 400);
    }

    delete_post_thumbnail($profile_id);

    dating_mark_profile_pending_after_user_changes($profile_id);

    wp_delete_attachment($thumbnail_id, true);

    $default_photo_url = get_template_directory_uri() . '/img/pics/profile-photo.jpg';

    wp_send_json_success([
        'message' => 'Фото профиля удалено.',
        'photoUrl' => $default_photo_url,
    ]);
}


add_action('init', 'dating_handle_frontend_login');

function dating_handle_frontend_login() {
    if (!isset($_POST['dating_login_submit'])) {
        return;
    }

    if (
        !isset($_POST['dating_login_nonce']) ||
        !wp_verify_nonce($_POST['dating_login_nonce'], 'dating_login_action')
    ) {
        wp_die('Ошибка безопасности. Попробуйте ещё раз.');
    }

    $login = isset($_POST['dating_login'])
        ? sanitize_text_field(wp_unslash($_POST['dating_login']))
        : '';

    $password = isset($_POST['dating_password'])
        ? $_POST['dating_password']
        : '';

    $remember = isset($_POST['dating_remember']);

    if (!$login || !$password) {
        wp_die('Введите логин и пароль.');
    }

    $credentials = [
        'user_login'    => $login,
        'user_password' => $password,
        'remember'      => $remember,
    ];

    $user = wp_signon($credentials, false);

    if (is_wp_error($user)) {
        wp_die('Неверный логин или пароль.');
    }

    wp_safe_redirect(home_url('/my-profile/'));
    exit;
}

/* SOFT DELETE USER PROFLE */

add_action('init', 'dating_handle_soft_delete_profile');

function dating_handle_soft_delete_profile() {
    if (!isset($_POST['dating_delete_profile'])) {
        return;
    }

    if (!is_user_logged_in()) {
        wp_safe_redirect(home_url('/'));
        exit;
    }

    if (
        !isset($_POST['dating_delete_profile_nonce']) ||
        !wp_verify_nonce($_POST['dating_delete_profile_nonce'], 'dating_delete_profile_action')
    ) {
        wp_die('Ошибка безопасности. Попробуйте ещё раз.');
    }

    $current_user_id = get_current_user_id();

    $user_profiles = get_posts([
        'post_type'      => 'profile',
        'post_status'    => 'any',
        'author'         => $current_user_id,
        'posts_per_page' => 1,
    ]);

    if (empty($user_profiles)) {
        wp_die('Анкета пользователя не найдена.');
    }

    $profile_id = $user_profiles[0]->ID;

    /*
     * Мягкое удаление анкеты.
     * Физически запись не удаляем.
     */
    update_post_meta($profile_id, 'profile_status', 'deleted_by_user');

    /*
     * Мягкое удаление аккаунта пользователя.
     * Самого wp_user не удаляем.
     */
    update_user_meta($current_user_id, 'account_status', 'deleted_by_user');

    /*
     * Можно дополнительно скрыть запись из публичного статуса WordPress.
     * Но profile_status уже достаточно для нашей логики.
     * Если хочешь, можно оставить publish, чтобы админ видел анкету как опубликованную,
     * но со статусом deleted_by_user.
     */

    wp_logout();

    wp_safe_redirect(add_query_arg('profile_deleted', '1', home_url('/')));
    exit;
}

/* Запретить вход пользователю с удалённым аккаунтом */

add_filter('authenticate', 'dating_prevent_deleted_user_login', 30, 3);

function dating_prevent_deleted_user_login($user, $username, $password) {
    if (is_wp_error($user) || !$user instanceof WP_User) {
        return $user;
    }

    $account_status = get_user_meta($user->ID, 'account_status', true);

    if ($account_status === 'deleted_by_user') {
        return new WP_Error(
            'account_deleted',
            'Этот профиль был удалён пользователем. Вход в аккаунт невозможен.'
        );
    }

    return $user;
}

/* Редактирование анкеты */

add_action('wp_ajax_dating_update_public_profile_main', 'dating_ajax_update_public_profile_main');

function dating_ajax_update_public_profile_main() {
    if (!is_user_logged_in()) {
        wp_send_json_error([
            'message' => 'Вы не авторизованы.',
        ], 401);
    }

    if (
        !isset($_POST['nonce']) ||
        !wp_verify_nonce($_POST['nonce'], 'dating_update_public_profile_main')
    ) {
        wp_send_json_error([
            'message' => 'Ошибка безопасности. Обновите страницу и попробуйте ещё раз.',
        ], 403);
    }

    $current_user_id = get_current_user_id();

    $user_profiles = get_posts([
        'post_type'      => 'profile',
        'post_status'    => 'any',
        'author'         => $current_user_id,
        'posts_per_page' => 1,
    ]);

    if (empty($user_profiles)) {
        wp_send_json_error([
            'message' => 'Анкета пользователя не найдена.',
        ], 404);
    }

    $profile_id = $user_profiles[0]->ID;

    $name = isset($_POST['name'])
        ? sanitize_text_field(wp_unslash($_POST['name']))
        : '';

    $age = isset($_POST['age'])
        ? absint($_POST['age'])
        : 0;

    $country = isset($_POST['country'])
        ? sanitize_text_field(wp_unslash($_POST['country']))
        : '';

    $city = isset($_POST['city'])
        ? sanitize_text_field(wp_unslash($_POST['city']))
        : '';

    $relationship_goal = isset($_POST['relationship_goal'])
        ? sanitize_key(wp_unslash($_POST['relationship_goal']))
        : '';

    $short_intro = isset($_POST['short_intro'])
        ? sanitize_textarea_field(wp_unslash($_POST['short_intro']))
        : '';

    $allowed_goals = [
        'communication',
        'serious_relationship',
        'family',
    ];

    if ($name === '') {
        wp_send_json_error([
            'message' => 'Имя обязательно для заполнения.',
        ], 400);
    }

    if ($age < 18 || $age > 99) {
        wp_send_json_error([
            'message' => 'Возраст должен быть от 18 до 99 лет.',
        ], 400);
    }

    if (mb_strlen($country, 'UTF-8') > 80) {
        wp_send_json_error([
            'message' => 'Название страны слишком длинное.',
        ], 400);
    }

    if (mb_strlen($city, 'UTF-8') > 80) {
        wp_send_json_error([
            'message' => 'Название города слишком длинное.',
        ], 400);
    }

    if (!in_array($relationship_goal, $allowed_goals, true)) {
        wp_send_json_error([
            'message' => 'Выберите корректную цель знакомства.',
        ], 400);
    }

    if (mb_strlen($short_intro, 'UTF-8') > 160) {
        wp_send_json_error([
            'message' => 'Короткая фраза не должна быть длиннее 160 символов.',
        ], 400);
    }

    $updated_post_id = wp_update_post([
        'ID'         => $profile_id,
        'post_title' => $name,
    ], true);

    if (is_wp_error($updated_post_id)) {
        wp_send_json_error([
            'message' => $updated_post_id->get_error_message(),
        ], 400);
    }

    update_post_meta($profile_id, 'age', $age);
    update_post_meta($profile_id, 'country', $country);
    update_post_meta($profile_id, 'city', $city);
    update_post_meta($profile_id, 'relationship_goal', $relationship_goal);
    update_post_meta($profile_id, 'short_intro', $short_intro);
    dating_mark_profile_pending_after_user_changes($profile_id);

    $goal_labels = [
        'communication'          => 'Общение',
        'serious_relationship'   => 'Серьёзные отношения',
        'family'                 => 'Создание семьи',
    ];

    $location_parts = array_filter([$city, $country]);
    $location = !empty($location_parts) ? implode(', ', $location_parts) : 'Местоположение не указано';

    wp_send_json_success([
        'message' => 'Основная информация сохранена.',
        'data'    => [
            'name'                    => $name,
            'age'                     => $age,
            'country'                 => $country,
            'city'                    => $city,
            'location'                => $location,
            'relationship_goal'       => $relationship_goal,
            'relationship_goal_label' => $goal_labels[$relationship_goal],
            'short_intro'             => $short_intro,
            'title'                   => $name . ', ' . $age,
        ],
    ]);
}

/* “О себе” и “Кого ищу" */

add_action('wp_ajax_dating_update_public_profile_text_section', 'dating_ajax_update_public_profile_text_section');

function dating_ajax_update_public_profile_text_section() {
    if (!is_user_logged_in()) {
        wp_send_json_error([
            'message' => 'Вы не авторизованы.',
        ], 401);
    }

    if (
        !isset($_POST['nonce']) ||
        !wp_verify_nonce($_POST['nonce'], 'dating_update_public_profile_text_section')
    ) {
        wp_send_json_error([
            'message' => 'Ошибка безопасности. Обновите страницу и попробуйте ещё раз.',
        ], 403);
    }

    $current_user_id = get_current_user_id();

    $user_profiles = get_posts([
        'post_type'      => 'profile',
        'post_status'    => 'any',
        'author'         => $current_user_id,
        'posts_per_page' => 1,
    ]);

    if (empty($user_profiles)) {
        wp_send_json_error([
            'message' => 'Анкета пользователя не найдена.',
        ], 404);
    }

    $profile_id = $user_profiles[0]->ID;

    $section = isset($_POST['section'])
        ? sanitize_key(wp_unslash($_POST['section']))
        : '';

    $text = isset($_POST['text'])
        ? sanitize_textarea_field(wp_unslash($_POST['text']))
        : '';

    $allowed_sections = [
        'about_me',
        'looking_for',
    ];

    if (!in_array($section, $allowed_sections, true)) {
        wp_send_json_error([
            'message' => 'Некорректный раздел анкеты.',
        ], 400);
    }

    if (mb_strlen($text, 'UTF-8') > 512) {
        wp_send_json_error([
            'message' => 'Текст не должен быть длиннее 512 символов.',
        ], 400);
    }

    update_post_meta($profile_id, $section, $text);
    dating_mark_profile_pending_after_user_changes($profile_id);

    wp_send_json_success([
        'message' => 'Раздел сохранён.',
        'data'    => [
            'section' => $section,
            'text'    => $text,
            'length'  => mb_strlen($text, 'UTF-8'),
        ],
    ]);
}

/* Секция "Интересы" */

add_action('wp_ajax_dating_update_public_profile_interests', 'dating_ajax_update_public_profile_interests');

function dating_ajax_update_public_profile_interests() {
    if (!is_user_logged_in()) {
        wp_send_json_error([
            'message' => 'Вы не авторизованы.',
        ], 401);
    }

    if (
        !isset($_POST['nonce']) ||
        !wp_verify_nonce($_POST['nonce'], 'dating_update_public_profile_interests')
    ) {
        wp_send_json_error([
            'message' => 'Ошибка безопасности. Обновите страницу и попробуйте ещё раз.',
        ], 403);
    }

    $current_user_id = get_current_user_id();

    $user_profiles = get_posts([
        'post_type'      => 'profile',
        'post_status'    => 'any',
        'author'         => $current_user_id,
        'posts_per_page' => 1,
    ]);

    if (empty($user_profiles)) {
        wp_send_json_error([
            'message' => 'Анкета пользователя не найдена.',
        ], 404);
    }

    $profile_id = $user_profiles[0]->ID;

    $interests = isset($_POST['interests']) && is_array($_POST['interests'])
        ? wp_unslash($_POST['interests'])
        : [];

    $clean_interests = [];

    foreach ($interests as $interest) {
        $interest = sanitize_text_field($interest);
        $interest = trim($interest);

        if ($interest === '') {
            continue;
        }

        if (mb_strlen($interest, 'UTF-8') > 30) {
            wp_send_json_error([
                'message' => 'Один интерес не должен быть длиннее 30 символов.',
            ], 400);
        }

        $clean_interests[] = $interest;
    }

    $clean_interests = array_values(array_unique($clean_interests));

    if (count($clean_interests) > 12) {
        wp_send_json_error([
            'message' => 'Можно выбрать не больше 12 интересов.',
        ], 400);
    }

    update_post_meta($profile_id, 'profile_interests', $clean_interests);
    dating_mark_profile_pending_after_user_changes($profile_id);

    /*
     * Позже можно включить повторную модерацию:
     */
    // update_post_meta($profile_id, 'profile_status', 'pending');

    wp_send_json_success([
        'message' => 'Интересы сохранены.',
        'data'    => [
            'interests' => $clean_interests,
        ],
    ]);
}

/* Галлерея фото */

add_action('wp_ajax_dating_upload_public_profile_gallery_photo', 'dating_ajax_upload_public_profile_gallery_photo');

function dating_ajax_upload_public_profile_gallery_photo() {
    if (!is_user_logged_in()) {
        wp_send_json_error([
            'message' => 'Вы не авторизованы.',
        ], 401);
    }

    if (
        !isset($_POST['nonce']) ||
        !wp_verify_nonce($_POST['nonce'], 'dating_public_profile_gallery_ajax')
    ) {
        wp_send_json_error([
            'message' => 'Ошибка безопасности. Обновите страницу и попробуйте ещё раз.',
        ], 403);
    }

    if (empty($_FILES['gallery_photo'])) {
        wp_send_json_error([
            'message' => 'Файл не был загружен.',
        ], 400);
    }

    $slot_index = isset($_POST['slot_index']) ? absint($_POST['slot_index']) : 0;

    if ($slot_index < 0 || $slot_index > 4) {
        wp_send_json_error([
            'message' => 'Некорректный слот галереи.',
        ], 400);
    }

    $current_user_id = get_current_user_id();

    $user_profiles = get_posts([
        'post_type'      => 'profile',
        'post_status'    => 'any',
        'author'         => $current_user_id,
        'posts_per_page' => 1,
    ]);

    if (empty($user_profiles)) {
        wp_send_json_error([
            'message' => 'Анкета пользователя не найдена.',
        ], 404);
    }

    $profile_id = $user_profiles[0]->ID;

    $file = $_FILES['gallery_photo'];

    $allowed_types = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    if (!in_array($file['type'], $allowed_types, true)) {
        wp_send_json_error([
            'message' => 'Можно загрузить только JPG, PNG или WEBP.',
        ], 400);
    }

    $max_size = 5 * 1024 * 1024;

    if ((int) $file['size'] > $max_size) {
        wp_send_json_error([
            'message' => 'Размер файла не должен превышать 5 МБ.',
        ], 400);
    }

    $gallery = get_post_meta($profile_id, 'profile_gallery', true);

    if (!is_array($gallery)) {
        $gallery = [];
    }

    for ($i = 0; $i < 5; $i++) {
        if (!isset($gallery[$i])) {
            $gallery[$i] = 0;
        }
    }

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $old_attachment_id = isset($gallery[$slot_index]) ? (int) $gallery[$slot_index] : 0;

    $attachment_id = media_handle_upload('gallery_photo', $profile_id);

    if (is_wp_error($attachment_id)) {
        wp_send_json_error([
            'message' => $attachment_id->get_error_message(),
        ], 400);
    }

    $gallery[$slot_index] = $attachment_id;

    $gallery = array_slice($gallery, 0, 5);

    update_post_meta($profile_id, 'profile_gallery', $gallery);

    dating_mark_profile_pending_after_user_changes($profile_id);

    if ($old_attachment_id && $old_attachment_id !== (int) $attachment_id) {
        wp_delete_attachment($old_attachment_id, true);
    }

    $photo_url = wp_get_attachment_image_url($attachment_id, 'medium_large');

    wp_send_json_success([
        'message'      => 'Фото галереи сохранено.',
        'slotIndex'    => $slot_index,
        'attachmentId' => $attachment_id,
        'photoUrl'     => $photo_url,
    ]);
}


add_action('wp_ajax_dating_delete_public_profile_gallery_photo', 'dating_ajax_delete_public_profile_gallery_photo');

function dating_ajax_delete_public_profile_gallery_photo() {
    if (!is_user_logged_in()) {
        wp_send_json_error([
            'message' => 'Вы не авторизованы.',
        ], 401);
    }

    if (
        !isset($_POST['nonce']) ||
        !wp_verify_nonce($_POST['nonce'], 'dating_public_profile_gallery_ajax')
    ) {
        wp_send_json_error([
            'message' => 'Ошибка безопасности. Обновите страницу и попробуйте ещё раз.',
        ], 403);
    }

    $slot_index = isset($_POST['slot_index']) ? absint($_POST['slot_index']) : 0;

    if ($slot_index < 0 || $slot_index > 4) {
        wp_send_json_error([
            'message' => 'Некорректный слот галереи.',
        ], 400);
    }

    $current_user_id = get_current_user_id();

    $user_profiles = get_posts([
        'post_type'      => 'profile',
        'post_status'    => 'any',
        'author'         => $current_user_id,
        'posts_per_page' => 1,
    ]);

    if (empty($user_profiles)) {
        wp_send_json_error([
            'message' => 'Анкета пользователя не найдена.',
        ], 404);
    }

    $profile_id = $user_profiles[0]->ID;

    $gallery = get_post_meta($profile_id, 'profile_gallery', true);

    if (!is_array($gallery)) {
        $gallery = [];
    }

    for ($i = 0; $i < 5; $i++) {
        if (!isset($gallery[$i])) {
            $gallery[$i] = 0;
        }
    }

    $attachment_id = isset($gallery[$slot_index]) ? (int) $gallery[$slot_index] : 0;

    if (!$attachment_id) {
        wp_send_json_error([
            'message' => 'В этом слоте нет фото.',
        ], 400);
    }

    $gallery[$slot_index] = 0;

    update_post_meta($profile_id, 'profile_gallery', array_slice($gallery, 0, 5));

    dating_mark_profile_pending_after_user_changes($profile_id);

    wp_delete_attachment($attachment_id, true);

    wp_send_json_success([
        'message'   => 'Фото удалено.',
        'slotIndex' => $slot_index,
    ]);
}

/* Редактирование порядка фото в галлерее */
add_action('wp_ajax_dating_reorder_public_profile_gallery', 'dating_ajax_reorder_public_profile_gallery');

function dating_ajax_reorder_public_profile_gallery() {
    if (!is_user_logged_in()) {
        wp_send_json_error([
            'message' => 'Вы не авторизованы.',
        ], 401);
    }

    if (
        !isset($_POST['nonce']) ||
        !wp_verify_nonce($_POST['nonce'], 'dating_public_profile_gallery_ajax')
    ) {
        wp_send_json_error([
            'message' => 'Ошибка безопасности. Обновите страницу и попробуйте ещё раз.',
        ], 403);
    }

    $current_user_id = get_current_user_id();

    $user_profiles = get_posts([
        'post_type'      => 'profile',
        'post_status'    => 'any',
        'author'         => $current_user_id,
        'posts_per_page' => 1,
    ]);

    if (empty($user_profiles)) {
        wp_send_json_error([
            'message' => 'Анкета пользователя не найдена.',
        ], 404);
    }

    $profile_id = $user_profiles[0]->ID;

    $gallery = get_post_meta($profile_id, 'profile_gallery', true);

    if (!is_array($gallery)) {
        $gallery = [];
    }

    $current_gallery = [];

    foreach ($gallery as $attachment_id) {
        $attachment_id = (int) $attachment_id;

        if ($attachment_id > 0) {
            $current_gallery[] = $attachment_id;
        }
    }

    $new_order = isset($_POST['order']) && is_array($_POST['order'])
        ? array_map('absint', wp_unslash($_POST['order']))
        : [];

    $new_order = array_values(array_filter($new_order));

    if (empty($new_order)) {
        wp_send_json_error([
            'message' => 'Порядок фотографий не передан.',
        ], 400);
    }

    if (count($new_order) > 5) {
        wp_send_json_error([
            'message' => 'В галерее может быть не больше 5 фотографий.',
        ], 400);
    }

    /*
     * Проверяем, что пользователь не подставил чужие attachment ID.
     * Новый порядок должен состоять только из фото текущей галереи.
     */
    foreach ($new_order as $attachment_id) {
        if (!in_array($attachment_id, $current_gallery, true)) {
            wp_send_json_error([
                'message' => 'Некорректный список фотографий.',
            ], 400);
        }
    }

    /*
     * Если в галерее были пустые слоты, после сортировки
     * заполненные фото идут сначала, пустые слоты остаются в конце.
     */
    $normalized_gallery = $new_order;

    while (count($normalized_gallery) < 5) {
        $normalized_gallery[] = 0;
    }

    update_post_meta($profile_id, 'profile_gallery', array_slice($normalized_gallery, 0, 5));

    wp_send_json_success([
        'message' => 'Порядок фотографий сохранён.',
        'data'    => [
            'gallery' => array_slice($normalized_gallery, 0, 5),
        ],
    ]);
}

/* Колонки для админки */

add_filter('manage_profile_posts_columns', 'dating_profile_admin_columns');

function dating_profile_admin_columns($columns) {
    $new_columns = [];

    foreach ($columns as $key => $label) {
        $new_columns[$key] = $label;

        if ($key === 'title') {
            $new_columns['profile_status'] = 'Статус';
            $new_columns['profile_gender'] = 'Пол';
            $new_columns['profile_age'] = 'Возраст';
            $new_columns['profile_city'] = 'Город';
            $new_columns['profile_goal'] = 'Цель';
            $new_columns['profile_actions'] = 'Действия';
        }
    }

    return $new_columns;
}

/* Заполняем колонки админки данными */
add_action('manage_profile_posts_custom_column', 'dating_profile_admin_column_content', 10, 2);

function dating_profile_admin_column_content($column, $post_id) {
    $status = get_post_meta($post_id, 'profile_status', true);
    $gender = get_post_meta($post_id, 'gender', true);
    $age = get_post_meta($post_id, 'age', true);
    $city = get_post_meta($post_id, 'city', true);
    $goal = get_post_meta($post_id, 'relationship_goal', true);

    $status_labels = [
        'active'          => 'Активна',
        'pending'         => 'На модерации',
        'blocked'         => 'Заблокирована',
        'deleted_by_user' => 'Удалена пользователем',
    ];

    $gender_labels = [
        'male'   => 'Мужчина',
        'female' => 'Женщина',
    ];

    $goal_labels = [
        'communication'        => 'Общение',
        'serious_relationship' => 'Серьёзные отношения',
        'family'               => 'Создание семьи',
    ];

    if ($column === 'profile_status') {
        $label = $status_labels[$status] ?? 'Не указан';

        echo '<span class="dating-admin-status dating-admin-status--' . esc_attr($status ?: 'empty') . '">';
        echo esc_html($label);
        echo '</span>';
    }

    if ($column === 'profile_gender') {
        echo esc_html($gender_labels[$gender] ?? '—');
    }

    if ($column === 'profile_age') {
        echo $age ? esc_html($age) : '—';
    }

    if ($column === 'profile_city') {
        echo $city ? esc_html($city) : '—';
    }

    if ($column === 'profile_goal') {
        echo esc_html($goal_labels[$goal] ?? '—');
    }

    if ($column === 'profile_actions') {
        dating_render_profile_admin_actions($post_id, $status);
    }
}

/* Быстрые действия для админки */

function dating_render_profile_admin_actions($post_id, $current_status) {
    if (!current_user_can('edit_post', $post_id)) {
        echo '—';
        return;
    }

    if ($current_status === 'deleted_by_user') {
        echo '<span style="color:#777;">Только просмотр</span>';
        return;
    }

    $actions = [
        'active'  => 'Активировать',
        'pending' => 'На модерацию',
        'blocked' => 'Заблокировать',
    ];

    $links = [];

    foreach ($actions as $status => $label) {
        if ($status === $current_status) {
            continue;
        }

        $url = wp_nonce_url(
            add_query_arg([
                'action'         => 'dating_change_profile_status',
                'profile_id'     => $post_id,
                'profile_status' => $status,
            ], admin_url('admin-post.php')),
            'dating_change_profile_status_' . $post_id
        );

        $links[] = '<a href="' . esc_url($url) . '">' . esc_html($label) . '</a>';
    }

    echo implode('<br>', $links);
}

/* Обработчик смены статуса в админке */
add_action('admin_post_dating_change_profile_status', 'dating_handle_admin_change_profile_status');

function dating_handle_admin_change_profile_status() {
    if (
        !isset($_GET['profile_id']) ||
        !isset($_GET['profile_status'])
    ) {
        wp_die('Недостаточно данных.');
    }

    $profile_id = absint($_GET['profile_id']);
    $new_status = sanitize_key(wp_unslash($_GET['profile_status']));

    if (!$profile_id || get_post_type($profile_id) !== 'profile') {
        wp_die('Анкета не найдена.');
    }

    if (!current_user_can('edit_post', $profile_id)) {
        wp_die('У вас нет прав для изменения этой анкеты.');
    }

    if (
        !isset($_GET['_wpnonce']) ||
        !wp_verify_nonce($_GET['_wpnonce'], 'dating_change_profile_status_' . $profile_id)
    ) {
        wp_die('Ошибка безопасности.');
    }

    $allowed_statuses = [
        'active',
        'pending',
        'blocked',
    ];

    if (!in_array($new_status, $allowed_statuses, true)) {
        wp_die('Некорректный статус.');
    }

    $current_status = get_post_meta($profile_id, 'profile_status', true);

    if ($current_status === 'deleted_by_user') {
        wp_die('Анкета удалена пользователем. Быстрое изменение статуса запрещено.');
    }

    update_post_meta($profile_id, 'profile_status', $new_status);

    wp_safe_redirect(add_query_arg([
        'post_type' => 'profile',
        'dating_status_changed' => '1',
    ], admin_url('edit.php')));

    exit;
}

/* Сообщение после смены статуса в админке */
add_action('admin_notices', 'dating_profile_admin_status_notice');

function dating_profile_admin_status_notice() {
    if (
        !isset($_GET['post_type']) ||
        $_GET['post_type'] !== 'profile' ||
        !isset($_GET['dating_status_changed'])
    ) {
        return;
    }

    echo '<div class="notice notice-success is-dismissible">';
    echo '<p>Статус анкеты обновлён.</p>';
    echo '</div>';
}

/* Статусы в админке */

add_action('admin_head-edit.php', 'dating_profile_admin_columns_styles');

function dating_profile_admin_columns_styles() {
    $screen = get_current_screen();

    if (!$screen || $screen->post_type !== 'profile') {
        return;
    }
    ?>
    <style>
        .column-profile_status,
        .column-profile_gender,
        .column-profile_age,
        .column-profile_city,
        .column-profile_goal,
        .column-profile_actions {
            width: 120px;
        }

        .dating-admin-status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 999px;
            font-size: 12px;
            line-height: 1.3;
            white-space: nowrap;
            background: #f0f0f1;
            color: #1d2327;
        }

        .dating-admin-status--active {
            background: #d1f7d6;
            color: #135e1f;
        }

        .dating-admin-status--pending {
            background: #fff3cd;
            color: #7a5b00;
        }

        .dating-admin-status--blocked {
            background: #f8d7da;
            color: #842029;
        }

        .dating-admin-status--deleted_by_user {
            background: #e2e3e5;
            color: #41464b;
        }
    </style>
    <?php
}

/* Фильтр статуса */
add_action('restrict_manage_posts', 'dating_profile_admin_status_filter');

function dating_profile_admin_status_filter() {
    global $typenow;

    if ($typenow !== 'profile') {
        return;
    }

    $current_status = isset($_GET['profile_status_filter'])
        ? sanitize_key(wp_unslash($_GET['profile_status_filter']))
        : '';

    $current_gender = isset($_GET['profile_gender_filter'])
        ? sanitize_key(wp_unslash($_GET['profile_gender_filter']))
        : '';

    $current_city = isset($_GET['profile_city_filter'])
        ? sanitize_text_field(wp_unslash($_GET['profile_city_filter']))
        : '';

    $current_age_from = (
    isset($_GET['profile_age_from_filter']) &&
    $_GET['profile_age_from_filter'] !== ''
)
    ? absint($_GET['profile_age_from_filter'])
    : '';

$current_age_to = (
    isset($_GET['profile_age_to_filter']) &&
    $_GET['profile_age_to_filter'] !== ''
)
    ? absint($_GET['profile_age_to_filter'])
    : '';
        
        $current_goal = isset($_GET['profile_goal_filter'])
    ? sanitize_key(wp_unslash($_GET['profile_goal_filter']))
    : '';

    $statuses = [
        ''                => 'Все статусы',
        'active'          => 'Активные',
        'pending'         => 'На модерации',
        'blocked'         => 'Заблокированные',
        'deleted_by_user' => 'Удалённые пользователем',
    ];

    $genders = [
        ''       => 'Все',
        'male'   => 'Мужчины',
        'female' => 'Женщины',
    ];
    
    $goals = [
    ''                     => 'Все цели',
    'communication'        => 'Общение',
    'serious_relationship' => 'Серьёзные отношения',
    'family'               => 'Создание семьи',
];
    ?>

    <select name="profile_status_filter">
        <?php foreach ($statuses as $status_key => $status_label) : ?>
            <option value="<?php echo esc_attr($status_key); ?>" <?php selected($current_status, $status_key); ?>>
                <?php echo esc_html($status_label); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <select name="profile_gender_filter">
        <?php foreach ($genders as $gender_key => $gender_label) : ?>
            <option value="<?php echo esc_attr($gender_key); ?>" <?php selected($current_gender, $gender_key); ?>>
                <?php echo esc_html($gender_label); ?>
            </option>
        <?php endforeach; ?>
    </select>
    
    
    <select name="profile_goal_filter">
    <?php foreach ($goals as $goal_key => $goal_label) : ?>
        <option value="<?php echo esc_attr($goal_key); ?>" <?php selected($current_goal, $goal_key); ?>>
            <?php echo esc_html($goal_label); ?>
        </option>
    <?php endforeach; ?>
</select>

    <input
        type="text"
        name="profile_city_filter"
        value="<?php echo esc_attr($current_city); ?>"
        placeholder="Город"
        style="max-width: 130px;"
    >

    <input
        type="number"
        name="profile_age_from_filter"
        value="<?php echo esc_attr($current_age_from); ?>"
        placeholder="Возраст от"
        min="18"
        max="99"
        style="max-width: 105px;"
    >

    <input
        type="number"
        name="profile_age_to_filter"
        value="<?php echo esc_attr($current_age_to); ?>"
        placeholder="Возраст до"
        min="18"
        max="99"
        style="max-width: 105px;"
    >

    <?php
    
   $reset_url = admin_url('edit.php?post_type=profile');
?>

<a
    href="<?php echo esc_url($reset_url); ?>"
    class="button"
    style="margin-left: 6px;"
>
    Сбросить фильтры
</a>

<?php
}


add_action('pre_get_posts', 'dating_profile_admin_filter_query');

function dating_profile_admin_filter_query($query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }

    $screen = get_current_screen();

    if (!$screen || $screen->post_type !== 'profile') {
        return;
    }

    $meta_query = [];

    $status_filter = isset($_GET['profile_status_filter'])
        ? sanitize_key(wp_unslash($_GET['profile_status_filter']))
        : '';

    $allowed_statuses = [
        'active',
        'pending',
        'blocked',
        'deleted_by_user',
    ];

    if ($status_filter && in_array($status_filter, $allowed_statuses, true)) {
        $meta_query[] = [
            'key'   => 'profile_status',
            'value' => $status_filter,
        ];
    }

    $gender_filter = isset($_GET['profile_gender_filter'])
        ? sanitize_key(wp_unslash($_GET['profile_gender_filter']))
        : '';

    $allowed_genders = [
        'male',
        'female',
    ];

    if ($gender_filter && in_array($gender_filter, $allowed_genders, true)) {
        $meta_query[] = [
            'key'   => 'gender',
            'value' => $gender_filter,
        ];
    }
    
    $goal_filter = isset($_GET['profile_goal_filter'])
    ? sanitize_key(wp_unslash($_GET['profile_goal_filter']))
    : '';

$allowed_goals = [
    'communication',
    'serious_relationship',
    'family',
];

if ($goal_filter && in_array($goal_filter, $allowed_goals, true)) {
    $meta_query[] = [
        'key'   => 'relationship_goal',
        'value' => $goal_filter,
    ];
}

    $city_filter = isset($_GET['profile_city_filter'])
        ? sanitize_text_field(wp_unslash($_GET['profile_city_filter']))
        : '';

    if ($city_filter !== '') {
        $meta_query[] = [
            'key'     => 'city',
            'value'   => $city_filter,
            'compare' => 'LIKE',
        ];
    }

    $age_from_filter = isset($_GET['profile_age_from_filter']) && $_GET['profile_age_from_filter'] !== ''
        ? absint($_GET['profile_age_from_filter'])
        : 0;

    $age_to_filter = isset($_GET['profile_age_to_filter']) && $_GET['profile_age_to_filter'] !== ''
        ? absint($_GET['profile_age_to_filter'])
        : 0;

    if ($age_from_filter && $age_to_filter && $age_from_filter <= $age_to_filter) {
        $meta_query[] = [
            'key'     => 'age',
            'value'   => [$age_from_filter, $age_to_filter],
            'type'    => 'NUMERIC',
            'compare' => 'BETWEEN',
        ];
    } elseif ($age_from_filter) {
        $meta_query[] = [
            'key'     => 'age',
            'value'   => $age_from_filter,
            'type'    => 'NUMERIC',
            'compare' => '>=',
        ];
    } elseif ($age_to_filter) {
        $meta_query[] = [
            'key'     => 'age',
            'value'   => $age_to_filter,
            'type'    => 'NUMERIC',
            'compare' => '<=',
        ];
    }

    if (!empty($meta_query)) {
        $meta_query['relation'] = 'AND';
        $query->set('meta_query', $meta_query);
    }
}

/* Массовые действия в админке */
add_filter('bulk_actions-edit-profile', 'dating_profile_register_bulk_actions');

function dating_profile_register_bulk_actions($bulk_actions) {
    $bulk_actions['dating_bulk_activate_profiles'] = 'Активировать выбранные';
    $bulk_actions['dating_bulk_pending_profiles'] = 'Отправить на модерацию';
    $bulk_actions['dating_bulk_block_profiles'] = 'Заблокировать выбранные';

    return $bulk_actions;
}

/* Обработчик массовых действия */
add_filter('handle_bulk_actions-edit-profile', 'dating_profile_handle_bulk_actions', 10, 3);

function dating_profile_handle_bulk_actions($redirect_to, $doaction, $post_ids) {
    $allowed_actions = [
        'dating_bulk_activate_profiles' => 'active',
        'dating_bulk_pending_profiles'  => 'pending',
        'dating_bulk_block_profiles'    => 'blocked',
    ];

    if (!isset($allowed_actions[$doaction])) {
        return $redirect_to;
    }

    $new_status = $allowed_actions[$doaction];
    $changed_count = 0;
    $skipped_count = 0;

    foreach ($post_ids as $post_id) {
        $post_id = absint($post_id);

        if (!$post_id || get_post_type($post_id) !== 'profile') {
            $skipped_count++;
            continue;
        }

        if (!current_user_can('edit_post', $post_id)) {
            $skipped_count++;
            continue;
        }

        $current_status = get_post_meta($post_id, 'profile_status', true);

        /*
         * Не меняем анкеты, которые пользователь удалил сам.
         * Это важно для нашей логики soft delete.
         */
        if ($current_status === 'deleted_by_user') {
            $skipped_count++;
            continue;
        }

        update_post_meta($post_id, 'profile_status', $new_status);
        $changed_count++;
    }

    $redirect_to = add_query_arg([
        'dating_bulk_status_changed' => $changed_count,
        'dating_bulk_status_skipped' => $skipped_count,
        'dating_bulk_new_status'     => $new_status,
    ], $redirect_to);

    return $redirect_to;
}

/* Уведомление после массового действия */
add_action('admin_notices', 'dating_profile_bulk_actions_notice');

function dating_profile_bulk_actions_notice() {
    if (
        !isset($_GET['post_type']) ||
        $_GET['post_type'] !== 'profile' ||
        !isset($_GET['dating_bulk_status_changed'])
    ) {
        return;
    }

    $changed_count = absint($_GET['dating_bulk_status_changed']);
    $skipped_count = isset($_GET['dating_bulk_status_skipped'])
        ? absint($_GET['dating_bulk_status_skipped'])
        : 0;

    $new_status = isset($_GET['dating_bulk_new_status'])
        ? sanitize_key(wp_unslash($_GET['dating_bulk_new_status']))
        : '';

    $status_labels = [
        'active'  => 'активный',
        'pending' => 'на модерации',
        'blocked' => 'заблокированный',
    ];

    $status_label = $status_labels[$new_status] ?? 'обновлённый';

    echo '<div class="notice notice-success is-dismissible">';
    echo '<p>';

    echo 'Обновлено анкет: ' . esc_html($changed_count) . '. ';
    echo 'Новый статус: ' . esc_html($status_label) . '.';

    if ($skipped_count > 0) {
        echo ' Пропущено анкет: ' . esc_html($skipped_count) . '.';
    }

    echo '</p>';
    echo '</div>';
}

/* Делаем колонки сортируемыми */
add_filter('manage_edit-profile_sortable_columns', 'dating_profile_admin_sortable_columns');

function dating_profile_admin_sortable_columns($columns) {
    $columns['profile_status'] = 'profile_status';
    $columns['profile_gender'] = 'profile_gender';
    $columns['profile_age']    = 'profile_age';
    $columns['profile_city']   = 'profile_city';
    $columns['profile_goal']   = 'profile_goal';

    return $columns;
}

/* Подключаем сортировку к запросу */
add_action('pre_get_posts', 'dating_profile_admin_sorting_query');

function dating_profile_admin_sorting_query($query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }

    $screen = get_current_screen();

    if (!$screen || $screen->post_type !== 'profile') {
        return;
    }

    $orderby = $query->get('orderby');
    
    if ($orderby === 'profile_gender') {
    $query->set('meta_key', 'gender');
    $query->set('orderby', 'meta_value');
	}
    

    if ($orderby === 'profile_age') {
        $query->set('meta_key', 'age');
        $query->set('orderby', 'meta_value_num');
    }

    if ($orderby === 'profile_city') {
        $query->set('meta_key', 'city');
        $query->set('orderby', 'meta_value');
    }

    if ($orderby === 'profile_status') {
        $query->set('meta_key', 'profile_status');
        $query->set('orderby', 'meta_value');
    }

    if ($orderby === 'profile_goal') {
        $query->set('meta_key', 'relationship_goal');
        $query->set('orderby', 'meta_value');
    }
}

/* Функция выдачи прав */

function dating_get_profile_admin_caps() {
    return [
        'read',

        'edit_profile',
        'read_profile',
        'delete_profile',

        'edit_profiles',
        'edit_others_profiles',
        'edit_published_profiles',
        'edit_private_profiles',

        'publish_profiles',
        'read_private_profiles',

        'delete_profiles',
        'delete_others_profiles',
        'delete_published_profiles',
        'delete_private_profiles',
    ];
}

function dating_get_profile_moderator_caps() {
    return [
        'read',
        'upload_files',

        'edit_profile',
        'read_profile',

        'edit_profiles',
        'edit_others_profiles',
        'edit_published_profiles',
        'edit_private_profiles',

        'publish_profiles',
        'read_private_profiles',
    ];
}

/* Создаём роль dating_moderator */
function dating_setup_profile_roles_and_caps() {
    $admin_caps = dating_get_profile_admin_caps();

    $admin_role = get_role('administrator');

    if ($admin_role) {
        foreach ($admin_caps as $cap) {
            $admin_role->add_cap($cap);
        }
    }

    $moderator_caps = dating_get_profile_moderator_caps();

    $moderator_role = get_role('dating_moderator');

    if (!$moderator_role) {
        add_role(
            'dating_moderator',
            'Модератор анкет',
            []
        );

        $moderator_role = get_role('dating_moderator');
    }

    if ($moderator_role) {
        foreach ($moderator_caps as $cap) {
            $moderator_role->add_cap($cap);
        }
    }
}

/* Безопасная настройка ролей через admin_init */
add_action('admin_init', 'dating_maybe_setup_profile_roles_and_caps');

function dating_maybe_setup_profile_roles_and_caps() {
    $version = '1.0.0';

    if (get_option('dating_profile_roles_version') === $version) {
        return;
    }

    dating_setup_profile_roles_and_caps();

    update_option('dating_profile_roles_version', $version);
}


/* Ограничиваем физическое удаление для модератора */

add_filter('map_meta_cap', 'dating_prevent_moderator_profile_delete', 10, 4);

function dating_prevent_moderator_profile_delete($caps, $cap, $user_id, $args) {
    if (!in_array($cap, ['delete_post', 'delete_profile'], true)) {
        return $caps;
    }

    $post_id = isset($args[0]) ? absint($args[0]) : 0;

    if (!$post_id || get_post_type($post_id) !== 'profile') {
        return $caps;
    }

    $user = get_userdata($user_id);

    if (!$user) {
        return $caps;
    }

    if (in_array('administrator', (array) $user->roles, true)) {
        return $caps;
    }

    if (in_array('dating_moderator', (array) $user->roles, true)) {
        return ['do_not_allow'];
    }

    return $caps;
}

/* Добавляем создание таблиц чата */
function dating_create_chat_tables() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    $conversations_table = $wpdb->prefix . 'dating_conversations';
    $messages_table = $wpdb->prefix . 'dating_messages';
    $blocks_table = $wpdb->prefix . 'dating_user_blocks';

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $sql_conversations = "CREATE TABLE {$conversations_table} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_one_id BIGINT(20) UNSIGNED NOT NULL,
        user_two_id BIGINT(20) UNSIGNED NOT NULL,
        user_one_hidden TINYINT(1) NOT NULL DEFAULT 0,
		user_two_hidden TINYINT(1) NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY users_pair (user_one_id, user_two_id),
        KEY user_one_id (user_one_id),
        KEY user_two_id (user_two_id),
        KEY updated_at (updated_at)
    ) {$charset_collate};";

    $sql_messages = "CREATE TABLE {$messages_table} (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    conversation_id BIGINT(20) UNSIGNED NOT NULL,
    sender_id BIGINT(20) UNSIGNED NOT NULL,
    recipient_id BIGINT(20) UNSIGNED NOT NULL,
    message_text TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    is_deleted TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    edited_at DATETIME NULL DEFAULT NULL,
    deleted_at DATETIME NULL DEFAULT NULL,
    PRIMARY KEY  (id),
    KEY conversation_id (conversation_id),
    KEY sender_id (sender_id),
    KEY recipient_id (recipient_id),
    KEY is_read (is_read),
    KEY is_deleted (is_deleted),
    KEY created_at (created_at)
) {$charset_collate};";

$sql_blocks = "CREATE TABLE {$blocks_table} (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    blocker_id BIGINT(20) UNSIGNED NOT NULL,
    blocked_id BIGINT(20) UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY  (id),
    UNIQUE KEY blocker_blocked (blocker_id, blocked_id),
    KEY blocker_id (blocker_id),
    KEY blocked_id (blocked_id),
    KEY created_at (created_at)
) {$charset_collate};";

    dbDelta($sql_conversations);
    dbDelta($sql_messages);
    dbDelta($sql_blocks);
}

/* Запускаем создание таблиц через версию */
add_action('admin_init', 'dating_maybe_create_chat_tables');

function dating_maybe_create_chat_tables() {
    $version = '1.0.3';

    if (get_option('dating_chat_tables_version') === $version) {
        return;
    }

    dating_create_chat_tables();

    update_option('dating_chat_tables_version', $version);
}

/* Функция получения или создания диалога */
function dating_get_or_create_conversation($current_user_id, $other_user_id) {
    global $wpdb;

    $current_user_id = absint($current_user_id);
    $other_user_id = absint($other_user_id);

    if (!$current_user_id || !$other_user_id) {
        return 0;
    }

    if ($current_user_id === $other_user_id) {
        return 0;
    }

    $user_one_id = min($current_user_id, $other_user_id);
    $user_two_id = max($current_user_id, $other_user_id);

    $table = $wpdb->prefix . 'dating_conversations';

    $conversation_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT id FROM {$table}
             WHERE user_one_id = %d
             AND user_two_id = %d
             LIMIT 1",
            $user_one_id,
            $user_two_id
        )
    );

    if ($conversation_id) {
        return absint($conversation_id);
    }

    $now = current_time('mysql');

    $inserted = $wpdb->insert(
        $table,
        [
            'user_one_id' => $user_one_id,
            'user_two_id' => $user_two_id,
            'created_at'  => $now,
            'updated_at'  => $now,
        ],
        [
            '%d',
            '%d',
            '%s',
            '%s',
        ]
    );

    if (!$inserted) {
        return 0;
    }

    return absint($wpdb->insert_id);
}

/* Проверка, что пользователь участник диалога */
function dating_user_can_access_conversation($conversation_id, $user_id) {
    global $wpdb;

    $conversation_id = absint($conversation_id);
    $user_id = absint($user_id);

    if (!$conversation_id || !$user_id) {
        return false;
    }

    $table = $wpdb->prefix . 'dating_conversations';

    $exists = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT id FROM {$table}
             WHERE id = %d
             AND (user_one_id = %d OR user_two_id = %d)
             LIMIT 1",
            $conversation_id,
            $user_id,
            $user_id
        )
    );

    return (bool) $exists;
}

function dating_user_has_blocked_user($blocker_id, $blocked_id) {
    global $wpdb;

    $blocker_id = absint($blocker_id);
    $blocked_id = absint($blocked_id);

    if (!$blocker_id || !$blocked_id || $blocker_id === $blocked_id) {
        return false;
    }

    $blocks_table = $wpdb->prefix . 'dating_user_blocks';

    $exists = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT id
             FROM {$blocks_table}
             WHERE blocker_id = %d
               AND blocked_id = %d
             LIMIT 1",
            $blocker_id,
            $blocked_id
        )
    );

    return !empty($exists);
}

function dating_users_have_block_between($user_one_id, $user_two_id) {
    return (
        dating_user_has_blocked_user($user_one_id, $user_two_id) ||
        dating_user_has_blocked_user($user_two_id, $user_one_id)
    );
}

function dating_block_user($blocker_id, $blocked_id) {
    global $wpdb;

    $blocker_id = absint($blocker_id);
    $blocked_id = absint($blocked_id);

    if (!$blocker_id || !$blocked_id || $blocker_id === $blocked_id) {
        return false;
    }

    if (!get_user_by('id', $blocked_id)) {
        return false;
    }

    $blocks_table = $wpdb->prefix . 'dating_user_blocks';

    $result = $wpdb->replace(
        $blocks_table,
        [
            'blocker_id' => $blocker_id,
            'blocked_id' => $blocked_id,
            'created_at' => current_time('mysql'),
        ],
        [
            '%d',
            '%d',
            '%s',
        ]
    );

    return $result !== false;
}

function dating_unblock_user($blocker_id, $blocked_id) {
    global $wpdb;

    $blocker_id = absint($blocker_id);
    $blocked_id = absint($blocked_id);

    if (!$blocker_id || !$blocked_id || $blocker_id === $blocked_id) {
        return false;
    }

    $blocks_table = $wpdb->prefix . 'dating_user_blocks';

    $deleted = $wpdb->delete(
        $blocks_table,
        [
            'blocker_id' => $blocker_id,
            'blocked_id' => $blocked_id,
        ],
        [
            '%d',
            '%d',
        ]
    );

    return $deleted !== false;
}


/* Обработчик кнопки "Написать" */

add_action('init', 'dating_handle_start_conversation');

function dating_handle_start_conversation() {
    if (!isset($_POST['dating_start_conversation'])) {
        return;
    }

    if (!is_user_logged_in()) {
        wp_safe_redirect(home_url('/'));
        exit;
    }

    if (
        !isset($_POST['dating_start_conversation_nonce']) ||
        !wp_verify_nonce($_POST['dating_start_conversation_nonce'], 'dating_start_conversation_action')
    ) {
        wp_die('Ошибка безопасности. Обновите страницу и попробуйте ещё раз.');
    }

    $current_user_id = get_current_user_id();

    $profile_id = isset($_POST['profile_id'])
        ? absint($_POST['profile_id'])
        : 0;

    if (!$profile_id || get_post_type($profile_id) !== 'profile') {
        wp_die('Анкета не найдена.');
    }

    $profile_status = get_post_meta($profile_id, 'profile_status', true);

    if ($profile_status !== 'active') {
        wp_die('Эта анкета сейчас недоступна.');
    }

    $profile_author_id = (int) get_post_field('post_author', $profile_id);

    if (!$profile_author_id) {
        wp_die('Автор анкеты не найден.');
    }

    if ($profile_author_id === $current_user_id) {
        wp_safe_redirect(get_permalink($profile_id));
        exit;
    }

    $conversation_id = dating_get_or_create_conversation($current_user_id, $profile_author_id);

    if (!$conversation_id) {
        wp_die('Не удалось создать диалог. Попробуйте ещё раз.');
    }

    wp_safe_redirect(add_query_arg([
        'conversation_id' => $conversation_id,
    ], home_url('/messages/')));

    exit;
}

/* PHP: AJAX-обработчик отправки сообщения */

add_action('wp_ajax_dating_send_message', 'dating_ajax_send_message');

function dating_ajax_send_message() {
    if (!is_user_logged_in()) {
        wp_send_json_error([
            'message' => 'Вы не авторизованы.',
        ], 401);
    }

    if (
        !isset($_POST['nonce']) ||
        !wp_verify_nonce($_POST['nonce'], 'dating_messages_ajax')
    ) {
        wp_send_json_error([
            'message' => 'Ошибка безопасности. Обновите страницу и попробуйте ещё раз.',
        ], 403);
    }

    global $wpdb;

    $current_user_id = get_current_user_id();

    $conversations_table = $wpdb->prefix . 'dating_conversations';
    $messages_table = $wpdb->prefix . 'dating_messages';

    $conversation_id = isset($_POST['conversation_id'])
        ? absint($_POST['conversation_id'])
        : 0;

    $message_text = isset($_POST['message_text'])
        ? sanitize_textarea_field(wp_unslash($_POST['message_text']))
        : '';

    $message_text = trim($message_text);

    if (!$conversation_id) {
        wp_send_json_error([
            'message' => 'Диалог не найден.',
        ], 400);
    }

    if (!dating_user_can_access_conversation($conversation_id, $current_user_id)) {
        wp_send_json_error([
            'message' => 'У вас нет доступа к этому диалогу.',
        ], 403);
    }

    if ($message_text === '') {
        wp_send_json_error([
            'message' => 'Сообщение не может быть пустым.',
        ], 400);
    }

    if (mb_strlen($message_text, 'UTF-8') > 1000) {
        wp_send_json_error([
            'message' => 'Сообщение не должно быть длиннее 1000 символов.',
        ], 400);
    }

    $conversation = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT *
             FROM {$conversations_table}
             WHERE id = %d
             LIMIT 1",
            $conversation_id
        )
    );

    if (!$conversation) {
        wp_send_json_error([
            'message' => 'Диалог не найден.',
        ], 404);
    }

    $recipient_id = ((int) $conversation->user_one_id === (int) $current_user_id)
        ? (int) $conversation->user_two_id
        : (int) $conversation->user_one_id;

    if (!$recipient_id) {
        wp_send_json_error([
            'message' => 'Получатель не найден.',
        ], 400);
    }

    if (
        function_exists('dating_users_have_block_between') &&
        dating_users_have_block_between($current_user_id, $recipient_id)
    ) {
        wp_send_json_error([
            'message' => 'Отправка сообщений недоступна: один из пользователей заблокировал другого.',
        ], 403);
    }

    /*
     * Антиспам: не чаще одного сообщения в 3 секунды
     * и запрет на одинаковое сообщение подряд в одном диалоге.
     */
    $last_message = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT *
             FROM {$messages_table}
             WHERE conversation_id = %d
               AND sender_id = %d
             ORDER BY id DESC
             LIMIT 1",
            $conversation_id,
            $current_user_id
        )
    );

    if ($last_message) {
        $last_message_time = strtotime($last_message->created_at);
        $current_time = current_time('timestamp');

        if ($last_message_time && ($current_time - $last_message_time) < 3) {
            wp_send_json_error([
                'message' => 'Пожалуйста, не отправляйте сообщения так часто.',
            ], 429);
        }

        if (trim((string) $last_message->message_text) === $message_text) {
            wp_send_json_error([
                'message' => 'Вы уже отправили такое сообщение.',
            ], 429);
        }
    }

    $now = current_time('mysql');

    $inserted = $wpdb->insert(
        $messages_table,
        [
            'conversation_id' => $conversation_id,
            'sender_id'       => $current_user_id,
            'recipient_id'    => $recipient_id,
            'message_text'    => $message_text,
            'is_read'         => 0,
            'created_at'      => $now,
        ],
        [
            '%d',
            '%d',
            '%d',
            '%s',
            '%d',
            '%s',
        ]
    );

    if (!$inserted) {
        wp_send_json_error([
            'message' => 'Не удалось отправить сообщение.',
        ], 500);
    }

    $message_id = absint($wpdb->insert_id);

    $recipient_hidden_field = dating_get_conversation_hidden_field_for_user($conversation, $recipient_id);

    $conversation_update_data = [
        'updated_at' => $now,
    ];

    $conversation_update_format = [
        '%s',
    ];

    if ($recipient_hidden_field) {
        $conversation_update_data[$recipient_hidden_field] = 0;
        $conversation_update_format[] = '%d';
    }

    $wpdb->update(
        $conversations_table,
        $conversation_update_data,
        [
            'id' => $conversation_id,
        ],
        $conversation_update_format,
        [
            '%d',
        ]
    );

    wp_send_json_success([
        'message' => 'Сообщение отправлено.',
        'data'    => [
            'id'           => $message_id,
            'message_text' => $message_text,
            'created_at'   => $now,
            'time_label'   => mysql2date('d.m.Y H:i', $now),
        ],
    ]);
}

/* AJAX-обработчик получения новых сообщений */

add_action('wp_ajax_dating_fetch_messages', 'dating_ajax_fetch_messages');

function dating_ajax_fetch_messages() {
    if (!is_user_logged_in()) {
        wp_send_json_error([
            'message' => 'Вы не авторизованы.',
        ], 401);
    }

    if (
        !isset($_POST['nonce']) ||
        !wp_verify_nonce($_POST['nonce'], 'dating_messages_ajax')
    ) {
        wp_send_json_error([
            'message' => 'Ошибка безопасности. Обновите страницу и попробуйте ещё раз.',
        ], 403);
    }

    global $wpdb;

    $current_user_id = get_current_user_id();

    $conversation_id = isset($_POST['conversation_id'])
        ? absint($_POST['conversation_id'])
        : 0;

    $last_message_id = isset($_POST['last_message_id'])
        ? absint($_POST['last_message_id'])
        : 0;

    if (!$conversation_id) {
        wp_send_json_error([
            'message' => 'Диалог не найден.',
        ], 400);
    }

    if (!dating_user_can_access_conversation($conversation_id, $current_user_id)) {
        wp_send_json_error([
            'message' => 'У вас нет доступа к этому диалогу.',
        ], 403);
    }

    $messages_table = $wpdb->prefix . 'dating_messages';

   $messages = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT *
         FROM (
             SELECT *
             FROM {$messages_table}
             WHERE conversation_id = %d
             ORDER BY id DESC
             LIMIT 100
         ) AS recent_messages
         ORDER BY id ASC",
        $conversation_id
    )
);

    if (!empty($messages)) {
        $message_ids_to_mark_read = [];

        foreach ($messages as $message) {
            if ((int) $message->recipient_id === (int) $current_user_id) {
                $message_ids_to_mark_read[] = (int) $message->id;
            }
        }

        if (!empty($message_ids_to_mark_read)) {
            $ids_placeholder = implode(',', array_fill(0, count($message_ids_to_mark_read), '%d'));

            $wpdb->query(
                $wpdb->prepare(
                    "UPDATE {$messages_table}
                     SET is_read = 1
                     WHERE id IN ($ids_placeholder)",
                    $message_ids_to_mark_read
                )
            );
        }
    }

    $response_messages = [];

    foreach ($messages as $message) {
        $is_own_message = (int) $message->sender_id === (int) $current_user_id;

        $is_deleted_message = !empty($message->is_deleted);
        $is_edited_message = !$is_deleted_message && !empty($message->edited_at);

        $response_messages[] = [
            'id'             => (int) $message->id,
            'message_text'   => $is_deleted_message ? 'Сообщение удалено' : $message->message_text,
            'created_at'     => $message->created_at,
            'time_label'     => mysql2date('d.m.Y H:i', $message->created_at),
            'is_own'         => $is_own_message,
            'is_deleted'     => $is_deleted_message,
            'is_edited'      => $is_edited_message,
            'edited_label'   => $is_edited_message ? 'изменено' : '',
        ];
    }

    wp_send_json_success([
        'messages' => $response_messages,
    ]);
}

/* функция подсчёта непрочитанных сообщений */
function dating_get_unread_messages_count($conversation_id, $user_id) {
    global $wpdb;

    $conversation_id = absint($conversation_id);
    $user_id = absint($user_id);

    if (!$conversation_id || !$user_id) {
        return 0;
    }

    $messages_table = $wpdb->prefix . 'dating_messages';

    return (int) $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*)
             FROM {$messages_table}
             WHERE conversation_id = %d
             AND recipient_id = %d
             AND is_read = 0",
            $conversation_id,
            $user_id
        )
    );
}

/* функции общего подсчёта для профиля */

function dating_get_total_unread_messages_count($user_id) {
    global $wpdb;

    $user_id = absint($user_id);

    if (!$user_id) {
        return 0;
    }

    $messages_table = $wpdb->prefix . 'dating_messages';

    return (int) $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*)
             FROM {$messages_table}
             WHERE recipient_id = %d
             AND is_read = 0",
            $user_id
        )
    );
}

/* AJAX-обработчик общего счётчика сообщений */
add_action('wp_ajax_dating_get_unread_messages_total', 'dating_ajax_get_unread_messages_total');

function dating_ajax_get_unread_messages_total() {
    if (!is_user_logged_in()) {
        wp_send_json_error([
            'message' => 'Вы не авторизованы.',
        ], 401);
    }

    if (
        !isset($_POST['nonce']) ||
        !wp_verify_nonce($_POST['nonce'], 'dating_header_messages_ajax')
    ) {
        wp_send_json_error([
            'message' => 'Ошибка безопасности.',
        ], 403);
    }

    $current_user_id = get_current_user_id();

    $count = function_exists('dating_get_total_unread_messages_count')
        ? dating_get_total_unread_messages_count($current_user_id)
        : 0;

    wp_send_json_success([
        'count' => $count,
    ]);
}

/* Функция определения hidden-поля текущего пользователя */
function dating_get_conversation_hidden_field_for_user($conversation, $user_id) {
    $user_id = absint($user_id);

    if (!$conversation || !$user_id) {
        return '';
    }

    if ((int) $conversation->user_one_id === $user_id) {
        return 'user_one_hidden';
    }

    if ((int) $conversation->user_two_id === $user_id) {
        return 'user_two_hidden';
    }

    return '';
}

/* AJAX: скрыть диалог */
add_action('wp_ajax_dating_hide_conversation', 'dating_ajax_hide_conversation');

function dating_ajax_hide_conversation() {
    if (!is_user_logged_in()) {
        wp_send_json_error([
            'message' => 'Вы не авторизованы.',
        ], 401);
    }

    if (
        !isset($_POST['nonce']) ||
        !wp_verify_nonce($_POST['nonce'], 'dating_messages_ajax')
    ) {
        wp_send_json_error([
            'message' => 'Ошибка безопасности.',
        ], 403);
    }

    global $wpdb;

    $current_user_id = get_current_user_id();

    $conversation_id = isset($_POST['conversation_id'])
        ? absint($_POST['conversation_id'])
        : 0;

    if (!$conversation_id || !dating_user_can_access_conversation($conversation_id, $current_user_id)) {
        wp_send_json_error([
            'message' => 'Диалог не найден.',
        ], 404);
    }

    $conversations_table = $wpdb->prefix . 'dating_conversations';

    $conversation = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT *
             FROM {$conversations_table}
             WHERE id = %d
             LIMIT 1",
            $conversation_id
        )
    );

    $hidden_field = dating_get_conversation_hidden_field_for_user($conversation, $current_user_id);

    if (!$hidden_field) {
        wp_send_json_error([
            'message' => 'Не удалось определить участника диалога.',
        ], 400);
    }

    $updated = $wpdb->update(
        $conversations_table,
        [
            $hidden_field => 1,
        ],
        [
            'id' => $conversation_id,
        ],
        [
            '%d',
        ],
        [
            '%d',
        ]
    );

    if ($updated === false) {
        wp_send_json_error([
            'message' => 'Не удалось скрыть диалог.',
        ], 500);
    }

    wp_send_json_success([
        'message' => 'Диалог скрыт.',
    ]);
}


/* AJAX: вернуть диалог */
add_action('wp_ajax_dating_restore_conversation', 'dating_ajax_restore_conversation');

function dating_ajax_restore_conversation() {
    if (!is_user_logged_in()) {
        wp_send_json_error([
            'message' => 'Вы не авторизованы.',
        ], 401);
    }

    if (
        !isset($_POST['nonce']) ||
        !wp_verify_nonce($_POST['nonce'], 'dating_messages_ajax')
    ) {
        wp_send_json_error([
            'message' => 'Ошибка безопасности.',
        ], 403);
    }

    global $wpdb;

    $current_user_id = get_current_user_id();

    $conversation_id = isset($_POST['conversation_id'])
        ? absint($_POST['conversation_id'])
        : 0;

    if (!$conversation_id || !dating_user_can_access_conversation($conversation_id, $current_user_id)) {
        wp_send_json_error([
            'message' => 'Диалог не найден.',
        ], 404);
    }

    $conversations_table = $wpdb->prefix . 'dating_conversations';

    $conversation = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT *
             FROM {$conversations_table}
             WHERE id = %d
             LIMIT 1",
            $conversation_id
        )
    );

    $hidden_field = dating_get_conversation_hidden_field_for_user($conversation, $current_user_id);

    if (!$hidden_field) {
        wp_send_json_error([
            'message' => 'Не удалось определить участника диалога.',
        ], 400);
    }

    $updated = $wpdb->update(
        $conversations_table,
        [
            $hidden_field => 0,
        ],
        [
            'id' => $conversation_id,
        ],
        [
            '%d',
        ],
        [
            '%d',
        ]
    );

    if ($updated === false) {
        wp_send_json_error([
            'message' => 'Не удалось вернуть диалог.',
        ], 500);
    }

    wp_send_json_success([
        'message' => 'Диалог возвращён.',
    ]);
}


/* AJAX-обработчик списка диалогов */
add_action('wp_ajax_dating_fetch_conversations_list', 'dating_ajax_fetch_conversations_list');

function dating_ajax_fetch_conversations_list() {
    if (!is_user_logged_in()) {
        wp_send_json_error([
            'message' => 'Вы не авторизованы.',
        ], 401);
    }

    if (
        !isset($_POST['nonce']) ||
        !wp_verify_nonce($_POST['nonce'], 'dating_messages_ajax')
    ) {
        wp_send_json_error([
            'message' => 'Ошибка безопасности.',
        ], 403);
    }

    global $wpdb;

    $current_user_id = get_current_user_id();

    $messages_view = isset($_POST['view'])
        ? sanitize_key(wp_unslash($_POST['view']))
        : 'active';

    if (!in_array($messages_view, ['active', 'hidden'], true)) {
        $messages_view = 'active';
    }

    $active_conversation_id = isset($_POST['active_conversation_id'])
        ? absint($_POST['active_conversation_id'])
        : 0;

    $conversations_table = $wpdb->prefix . 'dating_conversations';

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

    $items = [];

    foreach ($conversations as $conversation) {
        $partner_id = ((int) $conversation->user_one_id === (int) $current_user_id)
            ? (int) $conversation->user_two_id
            : (int) $conversation->user_one_id;

        $partner_user = get_userdata($partner_id);

        $partner_profiles = get_posts([
            'post_type'      => 'profile',
            'post_status'    => 'any',
            'author'         => $partner_id,
            'posts_per_page' => 1,
        ]);

        $partner_profile = !empty($partner_profiles) ? $partner_profiles[0] : null;

        $partner_name = $partner_profile
            ? get_the_title($partner_profile->ID)
            : ($partner_user ? $partner_user->display_name : 'Пользователь');

        $partner_photo = $partner_profile && has_post_thumbnail($partner_profile->ID)
            ? get_the_post_thumbnail_url($partner_profile->ID, 'thumbnail')
            : get_template_directory_uri() . '/img/pics/profile-main.jpg';

        $unread_count = function_exists('dating_get_unread_messages_count')
            ? dating_get_unread_messages_count($conversation->id, $current_user_id)
            : 0;

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

        $items[] = [
            'id'           => (int) $conversation->id,
            'partner_name' => $partner_name,
            'partner_photo'=> $partner_photo,
            'updated_at'   => $conversation->updated_at,
            'date_label'   => mysql2date('d.m.Y H:i', $conversation->updated_at),
            'unread_count' => $unread_count,
            'last_message_preview' => $last_message_preview,
            'is_active'    => (int) $conversation->id === (int) $active_conversation_id,
            'url'          => add_query_arg([
                'conversation_id' => $conversation->id,
                'view'            => $messages_view === 'hidden' ? 'hidden' : null,
            ], home_url('/messages/')),
            'action_type'  => $messages_view === 'hidden' ? 'restore' : 'hide',
            'action_label' => $messages_view === 'hidden' ? 'Вернуть' : 'Скрыть',
        ];
    }

    wp_send_json_success([
        'items' => $items,
        'view'  => $messages_view,
    ]);
}


/* helper-функцию */
function dating_get_conversation_last_message($conversation_id) {
    global $wpdb;

    $conversation_id = absint($conversation_id);

    if (!$conversation_id) {
        return null;
    }

    $messages_table = $wpdb->prefix . 'dating_messages';

    return $wpdb->get_row(
        $wpdb->prepare(
            "SELECT *
             FROM {$messages_table}
             WHERE conversation_id = %d
             ORDER BY id DESC
             LIMIT 1",
            $conversation_id
        )
    );
}    

    /* редактирование своего сообщения */
    add_action('wp_ajax_dating_edit_message', 'dating_ajax_edit_message');

function dating_ajax_edit_message() {
    if (!is_user_logged_in()) {
        wp_send_json_error([
            'message' => 'Вы не авторизованы.',
        ], 401);
    }

    if (
        !isset($_POST['nonce']) ||
        !wp_verify_nonce($_POST['nonce'], 'dating_messages_ajax')
    ) {
        wp_send_json_error([
            'message' => 'Ошибка безопасности. Обновите страницу и попробуйте ещё раз.',
        ], 403);
    }

    global $wpdb;

    $current_user_id = get_current_user_id();

    $message_id = isset($_POST['message_id'])
        ? absint($_POST['message_id'])
        : 0;

    $message_text = isset($_POST['message_text'])
        ? sanitize_textarea_field(wp_unslash($_POST['message_text']))
        : '';

    $message_text = trim($message_text);

    if (!$message_id) {
        wp_send_json_error([
            'message' => 'Сообщение не найдено.',
        ], 400);
    }

    if ($message_text === '') {
        wp_send_json_error([
            'message' => 'Сообщение не может быть пустым.',
        ], 400);
    }

    if (mb_strlen($message_text, 'UTF-8') > 1000) {
        wp_send_json_error([
            'message' => 'Сообщение не должно быть длиннее 1000 символов.',
        ], 400);
    }

    $messages_table = $wpdb->prefix . 'dating_messages';

    $message = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT *
             FROM {$messages_table}
             WHERE id = %d
             LIMIT 1",
            $message_id
        )
    );

    if (!$message) {
        wp_send_json_error([
            'message' => 'Сообщение не найдено.',
        ], 404);
    }

    if ((int) $message->sender_id !== (int) $current_user_id) {
        wp_send_json_error([
            'message' => 'Вы можете редактировать только свои сообщения.',
        ], 403);
    }

    if (!empty($message->is_deleted)) {
        wp_send_json_error([
            'message' => 'Удалённое сообщение нельзя редактировать.',
        ], 400);
    }

    if (!dating_user_can_access_conversation((int) $message->conversation_id, $current_user_id)) {
        wp_send_json_error([
            'message' => 'У вас нет доступа к этому диалогу.',
        ], 403);
    }

    $now = current_time('mysql');

    $updated = $wpdb->update(
        $messages_table,
        [
            'message_text' => $message_text,
            'edited_at'    => $now,
        ],
        [
            'id' => $message_id,
        ],
        [
            '%s',
            '%s',
        ],
        [
            '%d',
        ]
    );

    if ($updated === false) {
        wp_send_json_error([
            'message' => 'Не удалось изменить сообщение.',
        ], 500);
    }

    wp_send_json_success([
        'message' => 'Сообщение изменено.',
        'data'    => [
            'id'           => $message_id,
            'message_text' => $message_text,
            'edited_at'    => $now,
            'edited_label' => 'изменено',
        ],
    ]);
}


/* мягкое удаление своего сообщения */
add_action('wp_ajax_dating_delete_message', 'dating_ajax_delete_message');

function dating_ajax_delete_message() {
    if (!is_user_logged_in()) {
        wp_send_json_error([
            'message' => 'Вы не авторизованы.',
        ], 401);
    }

    if (
        !isset($_POST['nonce']) ||
        !wp_verify_nonce($_POST['nonce'], 'dating_messages_ajax')
    ) {
        wp_send_json_error([
            'message' => 'Ошибка безопасности. Обновите страницу и попробуйте ещё раз.',
        ], 403);
    }

    global $wpdb;

    $current_user_id = get_current_user_id();

    $message_id = isset($_POST['message_id'])
        ? absint($_POST['message_id'])
        : 0;

    if (!$message_id) {
        wp_send_json_error([
            'message' => 'Сообщение не найдено.',
        ], 400);
    }

    $messages_table = $wpdb->prefix . 'dating_messages';

    $message = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT *
             FROM {$messages_table}
             WHERE id = %d
             LIMIT 1",
            $message_id
        )
    );

    if (!$message) {
        wp_send_json_error([
            'message' => 'Сообщение не найдено.',
        ], 404);
    }

    if ((int) $message->sender_id !== (int) $current_user_id) {
        wp_send_json_error([
            'message' => 'Вы можете удалить только свои сообщения.',
        ], 403);
    }

    if (!dating_user_can_access_conversation((int) $message->conversation_id, $current_user_id)) {
        wp_send_json_error([
            'message' => 'У вас нет доступа к этому диалогу.',
        ], 403);
    }

    if (!empty($message->is_deleted)) {
        wp_send_json_success([
            'message' => 'Сообщение уже удалено.',
            'data'    => [
                'id' => $message_id,
            ],
        ]);
    }

    $now = current_time('mysql');

    $updated = $wpdb->update(
        $messages_table,
        [
            'is_deleted' => 1,
            'deleted_at' => $now,
        ],
        [
            'id' => $message_id,
        ],
        [
            '%d',
            '%s',
        ],
        [
            '%d',
        ]
    );

    if ($updated === false) {
        wp_send_json_error([
            'message' => 'Не удалось удалить сообщение.',
        ], 500);
    }

    wp_send_json_success([
        'message' => 'Сообщение удалено.',
        'data'    => [
            'id'           => $message_id,
            'deleted_text' => 'Сообщение удалено',
            'deleted_at'   => $now,
        ],
    ]);
}


/* Обработчик отправки письма админитсрации */
add_action('init', 'dating_handle_contact_admin_message');

function dating_handle_contact_admin_message() {
    if (!isset($_POST['dating_contact_admin_submit'])) {
        return;
    }

    if (!is_user_logged_in()) {
        wp_safe_redirect(home_url('/'));
        exit;
    }

    if (
        !isset($_POST['dating_contact_admin_nonce']) ||
        !wp_verify_nonce($_POST['dating_contact_admin_nonce'], 'dating_contact_admin_action')
    ) {
        wp_die('Ошибка безопасности. Попробуйте ещё раз.');
    }

    $current_user_id = get_current_user_id();
    $current_user = wp_get_current_user();

    $subject = isset($_POST['dating_contact_admin_subject'])
        ? sanitize_text_field(wp_unslash($_POST['dating_contact_admin_subject']))
        : '';

    $message = isset($_POST['dating_contact_admin_message'])
        ? sanitize_textarea_field(wp_unslash($_POST['dating_contact_admin_message']))
        : '';

    $profile_id = isset($_POST['dating_contact_admin_profile_id'])
        ? absint($_POST['dating_contact_admin_profile_id'])
        : 0;

    if ($subject === '' || $message === '') {
        wp_safe_redirect(add_query_arg('admin_message_error', '1', home_url('/my-profile/#account-admin-contact')));
        exit;
    }

    if (mb_strlen($subject, 'UTF-8') > 120) {
        wp_safe_redirect(add_query_arg('admin_message_error', '1', home_url('/my-profile/#account-admin-contact')));
        exit;
    }

    if (mb_strlen($message, 'UTF-8') > 2000) {
        wp_safe_redirect(add_query_arg('admin_message_error', '1', home_url('/my-profile/#account-admin-contact')));
        exit;
    }

    $admin_email = get_option('admin_email');

    if (!is_email($admin_email)) {
        wp_safe_redirect(add_query_arg('admin_message_error', '1', home_url('/my-profile/#account-admin-contact')));
        exit;
    }

    $profile_link = $profile_id ? get_permalink($profile_id) : '';
    $user_email = $current_user->user_email;
    $user_name = $current_user->display_name ?: $current_user->user_login;

    $mail_subject = '[LoveStory] Сообщение администрации: ' . $subject;

    $mail_body = "Пользователь отправил сообщение администрации сайта.\n\n";
    $mail_body .= "Имя пользователя: {$user_name}\n";
    $mail_body .= "ID пользователя: {$current_user_id}\n";
    $mail_body .= "Email пользователя: {$user_email}\n";

    if ($profile_link) {
        $mail_body .= "Анкета пользователя: {$profile_link}\n";
    }

    $mail_body .= "\nТема:\n{$subject}\n\n";
    $mail_body .= "Сообщение:\n{$message}\n";

    $headers = [
        'Content-Type: text/plain; charset=UTF-8',
    ];

    if (is_email($user_email)) {
        $headers[] = 'Reply-To: ' . $user_name . ' <' . $user_email . '>';
    }

    $sent = wp_mail(
        $admin_email,
        $mail_subject,
        $mail_body,
        $headers
    );

    if (!$sent) {
        wp_safe_redirect(add_query_arg('admin_message_error', '1', home_url('/my-profile/#account-admin-contact')));
        exit;
    }

    wp_safe_redirect(add_query_arg('admin_message_sent', '1', home_url('/my-profile/#account-admin-contact')));
    exit;
}


/* Аккаунт для связи с администрацией */
function dating_get_support_user_id() {
    return 35;
}

add_action('init', 'dating_handle_start_support_conversation');

function dating_handle_start_support_conversation() {
    if (!isset($_POST['dating_start_support_conversation'])) {
        return;
    }

    if (!is_user_logged_in()) {
        wp_safe_redirect(home_url('/'));
        exit;
    }

    if (
        !isset($_POST['dating_start_support_conversation_nonce']) ||
        !wp_verify_nonce($_POST['dating_start_support_conversation_nonce'], 'dating_start_support_conversation_action')
    ) {
        wp_die('Ошибка безопасности. Попробуйте ещё раз.');
    }

    $current_user_id = get_current_user_id();
    $support_user_id = function_exists('dating_get_support_user_id')
        ? (int) dating_get_support_user_id()
        : 0;

    if (!$support_user_id || !get_user_by('id', $support_user_id)) {
        wp_die('Аккаунт администрации не найден.');
    }

    if ((int) $current_user_id === (int) $support_user_id) {
        wp_safe_redirect(home_url('/messages/'));
        exit;
    }

    if (!function_exists('dating_get_or_create_conversation')) {
        wp_die('Система сообщений недоступна.');
    }

    $conversation_id = dating_get_or_create_conversation($current_user_id, $support_user_id);

    if (!$conversation_id) {
        wp_die('Не удалось открыть диалог с администрацией.');
    }

    wp_safe_redirect(add_query_arg([
        'conversation_id' => $conversation_id,
        'support'         => '1',
    ], home_url('/messages/')));

    exit;
}


/* AJAX - обработчик блокировки */
add_action('wp_ajax_dating_block_user', 'dating_ajax_block_user');

function dating_ajax_block_user() {
    if (!is_user_logged_in()) {
        wp_send_json_error([
            'message' => 'Вы не авторизованы.',
        ], 401);
    }

    if (
        !isset($_POST['nonce']) ||
        !wp_verify_nonce($_POST['nonce'], 'dating_messages_ajax')
    ) {
        wp_send_json_error([
            'message' => 'Ошибка безопасности. Обновите страницу и попробуйте ещё раз.',
        ], 403);
    }

    $current_user_id = get_current_user_id();

    $blocked_user_id = isset($_POST['blocked_user_id'])
        ? absint($_POST['blocked_user_id'])
        : 0;

    if (!$blocked_user_id) {
        wp_send_json_error([
            'message' => 'Пользователь не найден.',
        ], 400);
    }

    if ((int) $blocked_user_id === (int) $current_user_id) {
        wp_send_json_error([
            'message' => 'Нельзя заблокировать самого себя.',
        ], 400);
    }

    if (function_exists('dating_get_support_user_id')) {
        $support_user_id = (int) dating_get_support_user_id();

        if ($support_user_id && (int) $blocked_user_id === $support_user_id) {
            wp_send_json_error([
                'message' => 'Нельзя заблокировать администрацию сайта.',
            ], 400);
        }
    }

    $blocked = dating_block_user($current_user_id, $blocked_user_id);

    if (!$blocked) {
        wp_send_json_error([
            'message' => 'Не удалось заблокировать пользователя.',
        ], 500);
    }

    wp_send_json_success([
        'message' => 'Пользователь заблокирован.',
        'data'    => [
            'blocked_user_id' => $blocked_user_id,
        ],
    ]);
}


/* AJAX-обработчик разблокировки */
add_action('wp_ajax_dating_unblock_user', 'dating_ajax_unblock_user');

function dating_ajax_unblock_user() {
    if (!is_user_logged_in()) {
        wp_send_json_error([
            'message' => 'Вы не авторизованы.',
        ], 401);
    }

    if (
        !isset($_POST['nonce']) ||
        !wp_verify_nonce($_POST['nonce'], 'dating_messages_ajax')
    ) {
        wp_send_json_error([
            'message' => 'Ошибка безопасности. Обновите страницу и попробуйте ещё раз.',
        ], 403);
    }

    $current_user_id = get_current_user_id();

    $blocked_user_id = isset($_POST['blocked_user_id'])
        ? absint($_POST['blocked_user_id'])
        : 0;

    if (!$blocked_user_id) {
        wp_send_json_error([
            'message' => 'Пользователь не найден.',
        ], 400);
    }

    $unblocked = dating_unblock_user($current_user_id, $blocked_user_id);

    if (!$unblocked) {
        wp_send_json_error([
            'message' => 'Не удалось разблокировать пользователя.',
        ], 500);
    }

    wp_send_json_success([
        'message' => 'Пользователь разблокирован.',
        'data'    => [
            'blocked_user_id' => $blocked_user_id,
        ],
    ]);
}