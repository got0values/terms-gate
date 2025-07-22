<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Set cookie and send X-Robots-Tag header before output
add_action('template_redirect', function() {
    if (!is_singular()) return;
    global $post;
    if (!isset($post->ID)) return;
    $enabled = get_post_meta($post->ID, '_termga_enabled', true);
    $form_id = get_post_meta($post->ID, '_termga_form_id', true);
    if ($enabled !== 'checked' || !$form_id) return;
    $cookie_name = 'termga_agree_' . $post->ID;
    if (
        (isset($_COOKIE[$cookie_name]) && $_COOKIE[$cookie_name] === '1') ||
        (isset($_POST['termga_agree'], $_POST['termga_terms_agree_nonce']) && $_POST['termga_agree'] === '1' && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['termga_terms_agree_nonce'])), 'termga_terms_agree'))
    ) {
        if (isset($_POST['termga_agree']) && $_POST['termga_agree'] === '1') {
            setcookie($cookie_name, '1', time() + DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN);
        }
    }
    // Send X-Robots-Tag header if not agreed
    if (!isset($_COOKIE[$cookie_name]) || $_COOKIE[$cookie_name] !== '1') {
        header('X-Robots-Tag: noindex, nofollow', true);
    }
});

function termga_check_terms_consent($content) {
    if (!is_singular()) {
        return $content;
    }

    global $post;
    $enabled = get_post_meta($post->ID, '_termga_enabled', true);
    $form_id = get_post_meta($post->ID, '_termga_form_id', true);

    if ($enabled !== 'checked' || !$form_id) {
        return $content;
    }

    $cookie_name = 'termga_agree_' . $post->ID;

    // If user has agreed (via cookie or POST), allow normal content
    if (
        (isset($_COOKIE[$cookie_name]) && $_COOKIE[$cookie_name] === '1') ||
        (isset($_POST['termga_agree'], $_POST['termga_terms_agree_nonce']) && $_POST['termga_agree'] === '1' && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['termga_terms_agree_nonce'])), 'termga_terms_agree'))
    ) {
        return $content;
    }

    // Get the form content safely (prevent infinite loop)
    remove_filter('the_content', 'termga_check_terms_consent');
    $form_post = get_post($form_id);
    $form_content = $form_post ? apply_filters('the_content', $form_post->post_content) : '';
    add_filter('the_content', 'termga_check_terms_consent');

    // Output noindex meta tag for bots
    add_action('wp_head', function() {
        echo '<meta name="robots" content="noindex, nofollow" />' . "\n";
    }, 1);

    $action = isset($_SERVER['REQUEST_URI']) ? esc_url(esc_url_raw(wp_unslash($_SERVER['REQUEST_URI']))) : '';
    ob_start();
    ?>
    <form method="post" action="<?php echo esc_url($action); ?>" class="terms-gate-form">
        <?php wp_nonce_field('termga_terms_agree', 'termga_terms_agree_nonce'); ?>
        <?php echo wp_kses_post($form_content); ?>
        <div style="display:flex;align-items:center;justify-content:center;gap:1.5rem;">
          <label><input type="checkbox" name="termga_agree" value="1" required> I agree</label>
          <button type="submit">Continue</button>
        </div>
    </form>
    <?php
    return ob_get_clean();
}
add_filter('the_content', 'termga_check_terms_consent');