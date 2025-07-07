<?php
function qtt_add_meta_box() {
    add_meta_box(
        'qtt_terms_box',
        'Terms Gate Toggle',
        'qtt_meta_box_html',
        ['page', 'post'],
        'side'
    );
}
add_action('add_meta_boxes', 'qtt_add_meta_box');

function qtt_meta_box_html($post) {
    $enabled = get_post_meta($post->ID, '_qtt_enabled', true);
    $selected_form = get_post_meta($post->ID, '_qtt_form_id', true);

    wp_nonce_field('qtt_save_meta', 'qtt_meta_nonce');

    echo '<label><input type="checkbox" name="qtt_enabled" value="checked" ' . checked($enabled, 'checked', false) . ' /> Require agreement</label><br>';

    // Fetch all agreement forms
    $forms = get_posts([
        'post_type' => 'terms_agreement',
        'post_status' => 'publish',
        'numberposts' => -1,
    ]);
    echo '<select name="qtt_form_id"><option value="">Select agreement form...</option>';
    foreach ($forms as $form) {
        echo '<option value="' . esc_attr($form->ID) . '" ' . selected($selected_form, $form->ID, false) . '>' . esc_html($form->post_title) . '</option>';
    }
    echo '</select>';
}

function qtt_save_meta($post_id) {
    // Security checks
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!isset($_POST['qtt_meta_nonce']) || !wp_verify_nonce($_POST['qtt_meta_nonce'], 'qtt_save_meta')) return;
    if (!current_user_can('edit_post', $post_id)) return;

    // Save checkbox
    $enabled = isset($_POST['qtt_enabled']) && $_POST['qtt_enabled'] === 'checked' ? 'checked' : '';
    update_post_meta($post_id, '_qtt_enabled', $enabled);

    // Save selected form
    $form_id = isset($_POST['qtt_form_id']) ? intval($_POST['qtt_form_id']) : '';
    update_post_meta($post_id, '_qtt_form_id', $form_id);
}
add_action('save_post', 'qtt_save_meta');