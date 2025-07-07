<?php
/*
Plugin Name: Terms Gate
Description: Require users to agree to terms before viewing specific pages.
Version: 1.0
Author: You
*/

define('QTT_PATH', plugin_dir_path(__FILE__));

require_once QTT_PATH . 'admin/meta-box.php';
require_once QTT_PATH . 'public/display-checkbox.php';


add_action('wp_enqueue_scripts', function() {
    // Only load on singular posts/pages where the terms toggle is enabled
    if (is_singular()) {
        global $post;
        if ($post && get_post_meta($post->ID, '_qtt_enabled', true) === 'checked') {
            wp_enqueue_style(
                'qtt-terms-style',
                plugins_url('assets/css/style.css', __FILE__),
                [],
                '1.0'
            );
            wp_enqueue_script(
                'qtt-terms-script',
                plugins_url('assets/js/script.js', __FILE__),
                [],
                '1.0',
                true
            );
        }
    }
});

add_action('init', function() {
    register_post_type('terms_agreement', [
        'labels' => [
            'name' => 'Terms Agreements',
            'singular_name' => 'Terms Agreement',
            'add_new' => 'Add New',
            'add_new_item' => 'Add Terms Agreement',
            'edit_item' => 'Edit Terms Agreement',
            'new_item' => 'New Terms Agreement',
            'view_item' => 'View Terms Agreement',
            'search_items' => 'Search Terms Agreements',
            'not_found' => 'No Terms Agreements found',
            'not_found_in_trash' => 'No Terms Agreements found in Trash',
            'menu_name' => 'Terms Agreements',
        ],
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_rest' => true,
        'supports' => ['title', 'editor'],
        'menu_icon' => 'dashicons-yes',
    ]);
});