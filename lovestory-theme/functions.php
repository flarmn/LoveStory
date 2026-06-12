<?php
/**
 * LoveStory theme functions.
 */

if (!defined('ABSPATH')) {
    exit;
}

function lovestory_theme_setup() {
    load_theme_textdomain('lovestory', get_template_directory() . '/languages');

    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', array('search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script'));
    add_theme_support('custom-logo', array(
        'height'      => 80,
        'width'       => 180,
        'flex-height' => true,
        'flex-width'  => true,
    ));

    register_nav_menus(array(
        'primary' => __('Главное меню', 'lovestory'),
        'footer'  => __('Меню в подвале', 'lovestory'),
    ));
}
add_action('after_setup_theme', 'lovestory_theme_setup');

function lovestory_enqueue_assets() {
    wp_enqueue_style('lovestory-style', get_stylesheet_uri(), array(), wp_get_theme()->get('Version'));
    wp_enqueue_style(
    'lovestory-mobile',
    get_template_directory_uri() . '/mobile.css',
    ['lovestory-style'],
    '1.0.0'
);
}
add_action('wp_enqueue_scripts', 'lovestory_enqueue_assets');


/* **** */
add_action('wp_enqueue_scripts', 'lovestory_enqueue_profile_ajax_script');

function lovestory_enqueue_profile_ajax_script() {
    if (!is_page_template('user-profile.php')) {
        return;
    }

    wp_enqueue_script(
        'lovestory-profile-ajax',
        get_template_directory_uri() . '/js/profile-ajax.js',
        [],
        '1.0.2',
        true
    );

/*
    wp_localize_script('lovestory-profile-ajax', 'LoveStoryProfileAjax', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('dating_update_profile_field_ajax'),
    ]);
    */
    
    wp_localize_script('lovestory-profile-ajax', 'LoveStoryProfileAjax', [
    'ajaxUrl'    => admin_url('admin-ajax.php'),
    'fieldNonce' => wp_create_nonce('dating_update_profile_field_ajax'),
    'photoNonce' => wp_create_nonce('dating_profile_photo_ajax'),
]);
}

add_action('wp_enqueue_scripts', 'lovestory_enqueue_auth_modals');

function lovestory_enqueue_auth_modals() {
    wp_enqueue_script(
        'lovestory-auth-modals',
        get_template_directory_uri() . '/js/auth-modals.js',
        [],
        '1.0.0',
        true
    );
}

/* Редактирование анкеты */

add_action('wp_enqueue_scripts', 'lovestory_enqueue_edit_profile_scripts');

function lovestory_enqueue_edit_profile_scripts() {
    if (!is_page_template('edit-profile.php')) {
        return;
    }

    wp_enqueue_script(
        'lovestory-edit-profile',
        get_template_directory_uri() . '/js/edit-profile.js',
        [],
        '1.0.0',
        true
    );

  wp_localize_script('lovestory-edit-profile', 'LoveStoryEditProfile', [
    'ajaxUrl'          => admin_url('admin-ajax.php'),
    'mainNonce'        => wp_create_nonce('dating_update_public_profile_main'),
    'textSectionNonce' => wp_create_nonce('dating_update_public_profile_text_section'),
    'interestsNonce'   => wp_create_nonce('dating_update_public_profile_interests'),
    'mainPhotoNonce'   => wp_create_nonce('dating_profile_photo_ajax'),
    'galleryNonce'     => wp_create_nonce('dating_public_profile_gallery_ajax'),
]);
}

/* подключаем JS для сообщений */

add_action('wp_enqueue_scripts', 'lovestory_enqueue_messages_scripts');

function lovestory_enqueue_messages_scripts() {
    if (!is_page('messages') && !is_page_template('messages.php')) {
        return;
    }

    wp_enqueue_script(
        'lovestory-messages',
        get_template_directory_uri() . '/js/messages.js',
        [],
        '1.0.3',
        true
    );

    wp_localize_script('lovestory-messages', 'LoveStoryMessages', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('dating_messages_ajax'),
        'messagesUrl' => home_url('/messages/'),
    ]);
}

/* JS для шапки с индикатором сообщений */
add_action('wp_enqueue_scripts', 'lovestory_enqueue_header_messages_scripts');

function lovestory_enqueue_header_messages_scripts() {
    if (!is_user_logged_in()) {
        return;
    }

    wp_enqueue_script(
        'lovestory-header-messages',
        get_template_directory_uri() . '/js/header-messages.js',
        [],
        '1.0.0',
        true
    );

    wp_localize_script('lovestory-header-messages', 'LoveStoryHeaderMessages', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('dating_header_messages_ajax'),
    ]);
}

/* Подключение мобильного меню */
add_action('wp_enqueue_scripts', 'lovestory_enqueue_site_header_scripts');

function lovestory_enqueue_site_header_scripts() {
    wp_enqueue_script(
        'lovestory-site-header',
        get_template_directory_uri() . '/js/site-header.js',
        [],
        '1.0.0',
        true
    );
}

add_filter('body_class', 'lovestory_messages_body_class');

function lovestory_messages_body_class($classes) {
    if (is_page('messages') || is_page_template('messages.php')) {
        $classes[] = 'is-messages-page';
    }

    return $classes;
}