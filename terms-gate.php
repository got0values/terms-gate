<?php
/*
Plugin Name: Terms Gate
Description: Require users to agree to terms before viewing specific pages.
Version: 1.0
Author: Hook Labs
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( function_exists( 'tg_fs' ) ) {
    tg_fs()->set_basename( true, __FILE__ );
} 
else {

  if ( ! function_exists( 'tg_fs' ) ) {
      // Create a helper function for easy SDK access.
      function tg_fs() {
          global $tg_fs;

          if ( ! isset( $tg_fs ) ) {
              // Include Freemius SDK.
              require_once dirname( __FILE__ ) . '/vendor/freemius/start.php';
              $tg_fs = fs_dynamic_init( array(
                  'id'                  => '19755',
                  'slug'                => 'terms-gate',
                  'type'                => 'plugin',
                  'public_key'          => 'pk_9f86febc1ca1399f11342380f6de0',
                  'is_premium'          => true,
                  'premium_suffix'      => 'Premium',
                  // If your plugin is a serviceware, set this option to false.
                  'has_premium_version' => true,
                  'has_addons'          => false,
                  'has_paid_plans'      => true,
                  'menu'                => array(
                      'slug'           => 'terms-gate-admin',
                      'contact'        => false,
                      'support'        => false,
                  ),
              ) );
          }

          return $tg_fs;
      }

      // Init Freemius.
      tg_fs();
      // Signal that SDK was initiated.
      do_action( 'tg_fs_loaded' );
  }

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
          'show_in_rest' => false,
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
      $enabled_count = count(get_posts([
          'post_type'   => ['post', 'page'],
          'post_status' => 'any',
          'meta_key'    => '_tg_enabled',
          'meta_value'  => 'checked',
          'fields'      => 'ids',
          'posts_per_page' => -1,
      ]));

      // Check if premium is active
      $is_premium = function_exists('tg_fs') && tg_fs()->is_plan('premium');

      // Set limit: 3 for free, unlimited for premium
      $limit = $is_premium ? 0 : 3; // 0 means unlimited

      ?>
      <div class="wrap">
        <h1>Terms Gate <?php echo $is_premium ? "(Premium)" : "(Free)" ?></h1>
        <p>Welcome to the Terms Gate admin page. Here you can manage your terms agreements and plugin settings.</p>
        <p>
            <strong>Enabled pages/posts:</strong>
            <?php echo esc_html($enabled_count); ?>
            <?php if ($limit): ?>
                out of <?php echo esc_html($limit); ?>
            <?php else: ?>
                out of (unlimited)
            <?php endif; ?>
        </p>
          <p>
              <a href="<?php echo admin_url('edit.php?post_type=terms_agreement'); ?>" class="button button-primary">Manage Terms Agreements</a>
          </p>
          <?php if ($is_premium): ?>
          <p>
              <a href="<?php echo admin_url('admin.php?page=terms-gate-admin-account'); ?>" class="button button-primary">Account</a>
          </p>
          <?php endif; ?>
      </div>
      <?php
  }

}