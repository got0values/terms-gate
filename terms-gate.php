<?php
/*
Plugin Name: Terms Gate
Description: Require users to agree to terms before viewing specific pages.
Version: 1.0
Author: Hook Labs
Author URI: https://hooklabs.dev/
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: terms-gate
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( function_exists( 'termga_fs' ) ) {
    termga_fs()->set_basename( true, __FILE__ );
} 
else {

  if ( ! function_exists( 'termga_fs' ) ) {
      // Create a helper function for easy SDK access.
      function termga_fs() {
          global $termga_fs;

          if ( ! isset( $termga_fs ) ) {
              // Include Freemius SDK.
              require_once dirname( __FILE__ ) . '/vendor/freemius/start.php';
              $termga_fs = fs_dynamic_init( array(
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
                  'is_org_compliant'    => true,
                  'menu'                => array(
                      'slug'           => 'termga-admin',
                      'contact'        => false,
                      'support'        => false,
                  ),
              ) );
          }

          return $termga_fs;
      }

      // Init Freemius.
      termga_fs();
      // Signal that SDK was initiated.
      do_action( 'termga_fs_loaded' );
  }

  define('TERMGA_PATH', plugin_dir_path(__FILE__));

  require_once TERMGA_PATH . 'admin/meta-box.php';
  require_once TERMGA_PATH . 'public/display-checkbox.php';

  add_action('wp_enqueue_scripts', function() {
      // Only load on singular posts/pages where the terms toggle is enabled
      if (is_singular()) {
          global $post;
          if ($post && get_post_meta($post->ID, '_termga_enabled', true) === 'checked') {
              wp_enqueue_style(
                  'termga-terms-style',
                  plugins_url('assets/css/style.css', __FILE__),
                  [],
                  '1.0'
              );
              wp_enqueue_script(
                  'termga-terms-script',
                  plugins_url('assets/js/script.js', __FILE__),
                  [],
                  '1.0',
                  true
              );
          }
      }
  });

  add_action('init', function() {
      register_post_type('termga_agreement', [
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
          'termga-admin',
          'termga_admin_page_html',
          'dashicons-yes',
          25
      );

      // Submenu for Terms Agreements (uses default post type UI)
      add_submenu_page(
          'termga-admin',
          'Terms Agreements',
          'Terms Agreements',
          'manage_options',
          'edit.php?post_type=termga_agreement'
      );

      $is_premium = function_exists('termga_fs') && termga_fs()->is_plan('premium');
        if ($is_premium) {
            add_submenu_page(
                'termga-admin',
                'Bulk Update',
                'Bulk Update',
                'manage_options',
                'termga-bulk-update',
                'termga_bulk_update_page_html',
            );
        }
  });

  // The callback function for the Terms Gate admin page
  function termga_admin_page_html() {
      $enabled_count = count(get_posts([
          'post_type'   => ['post', 'page'],
          'post_status' => 'any',
          'meta_key'    => '_termga_enabled',
          'meta_value'  => 'checked',
          'fields'      => 'ids',
          'posts_per_page' => -1,
      ]));

      // Check if premium is active
      $is_premium = function_exists('termga_fs') && termga_fs()->is_plan('premium');

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
            <a href="<?php echo esc_url(admin_url('edit.php?post_type=termga_agreement')); ?>" class="button button-primary">Manage Terms Agreements</a>
        </p>
        <?php if ($is_premium): ?>
        <p>
          <a href="<?php echo esc_url(admin_url('admin.php?page=termga-bulk-update')); ?>" class="button button-primary">Bulk Update</a>
        </p>
        <p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=termga-admin-account')); ?>" class="button button-primary">Account</a>
        </p>
        <?php endif; ?>
      </div>
      <?php
  }

  function termga_bulk_update_page_html() {
      // Check if premium is active
      $is_premium = function_exists('termga_fs') && termga_fs()->is_plan('premium');
      if (!$is_premium) {
          wp_die('You must have a premium license to access this page.');
      }

      // Handle bulk assignment
      if (
          isset($_POST['termga_bulk_assign_nonce']) &&
          wp_verify_nonce(
            sanitize_text_field(wp_unslash($_POST['termga_bulk_assign_nonce'])), 'termga_bulk_assign'
          )
      ) {
          $types = [];
          if (!empty($_POST['termga_bulk_update_posts'])) $types[] = 'post';
          if (!empty($_POST['termga_bulk_update_pages'])) $types[] = 'page';

          // Bulk Unassign
          if (isset($_POST['termga_bulk_unassign'])) {
              if ($types) {
                  $args = [
                      'post_type'      => $types,
                      'post_status'    => 'any',
                      'posts_per_page' => -1,
                      'fields'         => 'ids',
                  ];
                  $ids = get_posts($args);
                  foreach ($ids as $id) {
                      delete_post_meta($id, '_termga_form_id');
                      delete_post_meta($id, '_termga_enabled');
                  }
                  echo '<div class="notice notice-success is-dismissible"><p>Bulk unassign complete! All selected posts/pages no longer require a Terms Agreement.</p></div>';
              } else {
                  echo '<div class="notice notice-error is-dismissible"><p>Please select at least one content type to unassign.</p></div>';
              }
          }
          // Bulk Assign (existing code)
          elseif (isset($_POST['termga_bulk_terms_id'])) {
            $terms_id = intval($_POST['termga_bulk_terms_id']);
            if ($terms_id && $types) {
                $args = [
                    'post_type'      => $types,
                    'post_status'    => 'any',
                    'posts_per_page' => -1,
                    'fields'         => 'ids',
                ];
                $ids = get_posts($args);
                foreach ($ids as $id) {
                    update_post_meta($id, '_termga_form_id', $terms_id);
                    update_post_meta($id, '_termga_enabled', 'checked');
                }
                echo '<div class="notice notice-success is-dismissible"><p>Bulk assignment complete! All selected posts/pages now require the chosen Terms Agreement.</p></div>';
            } else {
              if (!$terms_id) {
                echo '<div class="notice notice-error is-dismissible"><p>Please select a Terms Agreement.</p></div>';
              }
              else if (!$types) {
                echo '<div class="notice notice-error is-dismissible"><p>Please select at least one content type.</p></div>';
              }
            }
          }
      }
      ?>
      <div class="wrap">
          <h1>Bulk Update</h1>
          <br>
          <form method="post">
            <label for="termga_bulk_terms_id">Select Terms Agreement:</label>
            <select name="termga_bulk_terms_id" id="termga_bulk_terms_id">
                <option value="">-- Select --</option>
                <?php
                $forms = get_posts([
                    'post_type' => 'termga_agreement',
                    'post_status' => 'publish',
                    'numberposts' => -1,
                ]);
                foreach ($forms as $form) {
                    echo '<option value="' . esc_attr($form->ID) . '">' . esc_html($form->post_title) . '</option>';
                }
                ?>
            </select>
            <br>
            <label><input type="checkbox" name="termga_bulk_update_posts" value="1"> Update all <strong>Posts</strong></label><br>
            <label><input type="checkbox" name="termga_bulk_update_pages" value="1"> Update all <strong>Pages</strong></label><br><br>
            <div style="display: flex; gap: 10px;">
              <?php submit_button('Bulk Assign'); ?>
              <input type="submit" name="termga_bulk_unassign" class="button button-secondary" value="Bulk Unassign" />
            </div>
            <?php wp_nonce_field('termga_bulk_assign', 'termga_bulk_assign_nonce'); ?>
        </form>
        <style>
          form p.submit {
            margin: 0;
            padding: 0;
          }
        </style>
      </div>
      <?php
  }
}