<?php
function tg_add_meta_box() {
    add_meta_box(
        'tg_terms_box',
        'Terms Gate Toggle',
        'tg_meta_box_html',
        ['page', 'post'],
        'side'
    );
}
add_action('add_meta_boxes', 'tg_add_meta_box');

function tg_meta_box_html($post) {
    $enabled = get_post_meta($post->ID, '_tg_enabled', true);
    $selected_form = get_post_meta($post->ID, '_tg_form_id', true);

    wp_nonce_field('tg_save_meta', 'tg_meta_nonce');

    echo '<label><input type="checkbox" name="tg_enabled" value="checked" ' . checked($enabled, 'checked', false) . ' /> Require agreement</label><br>';

    // Fetch all agreement forms
    $forms = get_posts([
        'post_type' => 'terms_agreement',
        'post_status' => 'publish',
        'numberposts' => -1,
    ]);
    echo '<select name="tg_form_id" style="margin-top:10px;"><option value="">Select terms agreement...</option>';
    foreach ($forms as $form) {
        echo '<option value="' . esc_attr($form->ID) . '" ' . selected($selected_form, $form->ID, false) . '>' . esc_html($form->post_title) . '</option>';
    }
    echo '</select>';

    // Add JavaScript to toggle select disabled state
    ?>
    <script>
    (function() {
        var checkbox = document.querySelector('input[name="tg_enabled"]');
        var select = document.querySelector('select[name="tg_form_id"]');
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

function tg_save_meta($post_id) {
    // Security checks
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!isset($_POST['tg_meta_nonce']) || !wp_verify_nonce($_POST['tg_meta_nonce'], 'tg_save_meta')) return;
    if (!current_user_can('edit_post', $post_id)) return;

    // Save checkbox
    $enabled = isset($_POST['tg_enabled']) && $_POST['tg_enabled'] === 'checked' ? 'checked' : '';
    update_post_meta($post_id, '_tg_enabled', $enabled);

    // Save selected form
    $form_id = isset($_POST['tg_form_id']) ? intval($_POST['tg_form_id']) : '';
    update_post_meta($post_id, '_tg_form_id', $form_id);
}
add_action('save_post', 'tg_save_meta');