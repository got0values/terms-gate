<?php
/*
Plugin Name: Terms Gate
Description: Require users to agree to terms before viewing specific pages.
Version: 1.0
Author: You
*/

define('TG_PATH', plugin_dir_path(__FILE__));

require_once TG_PATH . 'admin/meta-box.php';
require_once TG_PATH . 'public/display-checkbox.php';


add_action('wp_enqueue_scripts', function() {
    // Only load on singular posts/pages where the terms toggle is enabled
    if (is_singular()) {
        global $post;
        if ($post && get_post_meta($post->ID, '_tg_enabled', true) === 'checked') {
            wp_enqueue_style(
                'tg-terms-style',
                plugins_url('assets/css/style.css', __FILE__),
                [],
                '1.0'
            );
            wp_enqueue_script(
                'tg-terms-script',
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
        'show_in_menu' => false, // Hide from main menu
        'show_in_rest' => true,
        'supports' => ['title', 'editor'],
        'menu_icon' => 'dashicons-yes',
    ]);
});

// Add custom admin menu for Terms Gate
add_action('admin_menu', function() {
    // Top-level menu
    add_menu_page(
        'Terms Gate',
        'Terms Gate',
        'manage_options',
        'terms-gate-admin',
        'tg_admin_page_html',
        'dashicons-yes',
        25
    );

    // Submenu for Terms Agreements (uses default post type UI)
    add_submenu_page(
        'terms-gate-admin',
        'Terms Agreements',
        'Terms Agreements',
        'manage_options',
        'edit.php?post_type=terms_agreement'
    );
});

// The callback function for the Terms Gate admin page
function tg_admin_page_html() {
    ?>
    <div class="wrap">
        <h1>Terms Gate</h1>
        <p>Welcome to the Terms Gate admin page. Here you can manage your terms agreements and plugin settings.</p>
        <p>
            <a href="<?php echo admin_url('edit.php?post_type=terms_agreement'); ?>" class="button button-primary">Manage Terms Agreements</a>
        </p>
        <!-- Add more settings or information here as needed -->
    </div>
    <?php
}