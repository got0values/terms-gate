<?php
function qtt_check_terms_consent() {
    if (!is_singular()) {
        return;
    }

    // if (is_admin() || current_user_can('edit_posts')) {
    //     return; // Don't block admins/editors
    // }

    global $post;
    $enabled = get_post_meta($post->ID, '_qtt_enabled', true);
    $form_id = get_post_meta($post->ID, '_qtt_form_id', true);

    if ($enabled !== 'checked' || !$form_id) {
        return;
    }

    // Get the form content
    $form_post = get_post($form_id);
    $form_content = apply_filters('the_content', $form_post->post_content);

    $cookie_name = 'qtt_agree_' . $post->ID;

    // If user has agreed (via cookie or POST), allow access
    if (
        (isset($_COOKIE[$cookie_name]) && $_COOKIE[$cookie_name] === '1') ||
        (isset($_POST['qtt_agree']) && $_POST['qtt_agree'] === '1')
    ) {
        if (isset($_POST['qtt_agree']) && $_POST['qtt_agree'] === '1') {
            setcookie($cookie_name, '1', time() + DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN);
        }
        return;
    }

    // Show agreement form and exit
    $action = esc_url($_SERVER['REQUEST_URI']);
    echo '<form method="post" action="' . $action . '" class="terms-gate-form">';
    echo $form_content;
    echo '<label><input type="checkbox" name="qtt_agree" value="1" required> I agree</label><br><br>';
    echo '<button type="submit">Continue</button>';
    echo '</form>';
    exit;
}
add_action('template_redirect', 'qtt_check_terms_consent');