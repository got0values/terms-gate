<?php
function tg_check_terms_consent($content) {
    if (!is_singular()) {
        return $content;
    }

    global $post;
    $enabled = get_post_meta($post->ID, '_tg_enabled', true);
    $form_id = get_post_meta($post->ID, '_tg_form_id', true);

    if ($enabled !== 'checked' || !$form_id) {
        return $content;
    }

    $cookie_name = 'tg_agree_' . $post->ID;

    // If user has agreed (via cookie or POST), allow normal content
    if (
        (isset($_COOKIE[$cookie_name]) && $_COOKIE[$cookie_name] === '1') ||
        (isset($_POST['tg_agree'], $_POST['tg_terms_agree_nonce']) && $_POST['tg_agree'] === '1' && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['tg_terms_agree_nonce'])), 'tg_terms_agree'))
    ) {
        if (isset($_POST['tg_agree']) && $_POST['tg_agree'] === '1') {
            setcookie($cookie_name, '1', time() + DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN);
        }
        return $content;
    }

    // Get the form content safely (prevent infinite loop)
    remove_filter('the_content', 'tg_check_terms_consent');
    $form_post = get_post($form_id);
    $form_content = $form_post ? apply_filters('the_content', $form_post->post_content) : '';
    add_filter('the_content', 'tg_check_terms_consent');

    // Output noindex meta tag for bots
    add_action('wp_head', function() {
        echo '<meta name="robots" content="noindex, nofollow" />' . "\n";
    }, 1);

    // Optionally, add X-Robots-Tag HTTP header
    add_action('send_headers', function() {
        header('X-Robots-Tag: noindex, nofollow', true);
    }, 1);

    $action = isset($_SERVER['REQUEST_URI']) ? esc_url(esc_url_raw(wp_unslash($_SERVER['REQUEST_URI']))) : '';
    ob_start();
    ?>
    <form method="post" action="<?php echo esc_url($action); ?>" class="terms-gate-form">
        <?php wp_nonce_field('tg_terms_agree', 'tg_terms_agree_nonce'); ?>
        <?php echo wp_kses_post($form_content); ?>
        <div style="display:flex;align-items:center;justify-content:center;gap:1.5rem;">
          <label><input type="checkbox" name="tg_agree" value="1" required> I agree</label>
          <button type="submit">Continue</button>
        </div>
    </form>
    <?php
    return ob_get_clean();
}
add_filter('the_content', 'tg_check_terms_consent');