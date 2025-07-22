<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function termga_add_meta_box() {
    add_meta_box(
        'termga_terms_box',
        'Terms Gate Toggle',
        'termga_meta_box_html',
        ['page', 'post'],
        'side'
    );
}
add_action('add_meta_boxes', 'termga_add_meta_box');

function termga_meta_box_html($post) {
    $enabled = get_post_meta($post->ID, '_termga_enabled', true);
    $selected_form = get_post_meta($post->ID, '_termga_form_id', true);

    $is_premium = function_exists('termga_fs') && termga_fs()->is_plan('premium');
    $limit = $is_premium ? PHP_INT_MAX : 3; // 0 means unlimited

    // Count enabled posts/pages (excluding this one)
    $args = [
        'post_type'   => ['post', 'page'],
        'post_status' => 'any',
        'meta_key'    => '_termga_enabled',
        'meta_value'  => 'checked',
        'fields'      => 'ids',
        'posts_per_page' => -1,
        'exclude'     => [$post->ID],
    ];
    $enabled_posts = get_posts($args);
    $limit_reached = (count($enabled_posts) >= $limit) && $enabled !== 'checked';

    wp_nonce_field('termga_save_meta', 'termga_meta_nonce');

    echo '<label><input type="checkbox" name="termga_enabled" value="checked" ' . checked($enabled, 'checked', false) . ($limit_reached ? ' disabled' : '') . ' /> Require agreement</label><br>';

    // Fetch all agreement forms
    $forms = get_posts([
        'post_type' => 'termga_agreement',
        'post_status' => 'publish',
        'numberposts' => -1,
    ]);
    echo '<select name="termga_form_id" style="margin-top:10px;"' . ($limit_reached ? ' disabled' : '') . '><option value="">Select terms agreement...</option>';
    foreach ($forms as $form) {
        echo '<option value="' . esc_attr($form->ID) . '" ' . selected($selected_form, $form->ID, false) . '>' . esc_html($form->post_title) . '</option>';
    }
    echo '</select>';

    if ($limit_reached) {
        echo '<p style="color:red;margin-top:10px;">You can only enable Terms Gate on 3 pages/posts in the free version.</p>';
    } else {
        // Add JavaScript to toggle select disabled state
        ?>
        <script>
        (function() {
            var checkbox = document.querySelector('input[name="termga_enabled"]');
            var select = document.querySelector('select[name="termga_form_id"]');
            function toggleSelect() {
                select.disabled = !checkbox.checked;
            }
            checkbox.addEventListener('change', toggleSelect);
            // Set initial state
            toggleSelect();
        })();
        </script>
        <?php
    }
}

function termga_save_meta($post_id) {
    // Security checks
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!isset($_POST['termga_meta_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['termga_meta_nonce'])), 'termga_save_meta')) return;
    if (!current_user_can('edit_post', $post_id)) return;

    // Limit: Only allow 3 posts/pages to have the toggle enabled
    $is_premium = function_exists('termga_fs') && termga_fs()->is_plan('premium');
    $limit = $is_premium ? 0 : 3; // 0 means unlimited
    $enabled = isset($_POST['termga_enabled']) && $_POST['termga_enabled'] === 'checked' ? 'checked' : '';

    // Only check when enabling
    if ($enabled === 'checked' && $limit) {
        $args = [
            'post_type'   => ['post', 'page'],
            'post_status' => 'any',
            'meta_key'    => '_termga_enabled',
            'meta_value'  => 'checked',
            'fields'      => 'ids',
            'posts_per_page' => -1,
            'exclude'     => [$post_id], // Exclude current post in case we're updating
        ];
        $enabled_posts = get_posts($args);
        if (count($enabled_posts) >= $limit) {
            // Set an option for the admin notice
            update_option('termga_limit_reached_' . get_current_user_id(), 1);
            // Don't enable for this post
            $enabled = '';
        }
    }
    update_post_meta($post_id, '_termga_enabled', $enabled);

    // Save selected form
    $form_id = isset($_POST['termga_form_id']) ? intval($_POST['termga_form_id']) : '';
    update_post_meta($post_id, '_termga_form_id', $form_id);
}
add_action('save_post', 'termga_save_meta');

// Show admin notice if limit reached
add_action('admin_notices', function() {
    $option = 'termga_limit_reached_' . get_current_user_id();
    if (get_option($option)) {
        delete_option($option);
        echo '<div class="notice notice-error"><p>You can only enable Terms Gate on 3 pages/posts in the free version.</p></div>';
    }
});