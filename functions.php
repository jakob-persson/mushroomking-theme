<?php
// ======================
// Enqueue Styles and Scripts
// ======================

// add_action('template_redirect', function () {
//     if (is_front_page() && !is_user_logged_in()) {
//         wp_redirect(home_url('/login'));
//         exit;
//     }
// });

function jsp_enqueue_assets() {
    wp_enqueue_style('fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css');
    wp_enqueue_style('jsp-style', get_stylesheet_uri());

    wp_enqueue_script(
        'alpine',
        'https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js',
        [],
        null,
        true
    );

    wp_enqueue_script(
        'modal-add-adventure',
        get_stylesheet_directory_uri() . '/assets/js/modal-add-adventure.js',
        ['jquery'],
        filemtime(get_stylesheet_directory() . '/assets/js/modal-add-adventure.js'),
        true
    );

    wp_localize_script('modal-add-adventure', 'ajaxurl', admin_url('admin-ajax.php'));
}
add_action('wp_enqueue_scripts', 'jsp_enqueue_assets');

wp_enqueue_script(
    'modal-summary',
    get_stylesheet_directory_uri() . '/assets/js/modal-summary.js',
    ['jquery'],
    filemtime(get_stylesheet_directory() . '/assets/js/modal-summary.js'),
    true
  );



  add_action('wp_ajax_add_mushroom', 'my_add_mushroom_function');
  add_action('wp_ajax_nopriv_add_mushroom', 'my_add_mushroom_function');

  function my_add_mushroom_function() {
      global $wpdb;

      $user_id = get_current_user_id();
      if (!$user_id) wp_send_json_error('User not logged in');

      $types = json_decode(stripslashes($_POST['types'] ?? '[]'), true) ?: [];
      $start_date = sanitize_text_field($_POST['start_date'] ?? '');
      $location = sanitize_text_field($_POST['location'] ?? '');
      $adventure_text = wp_kses_post($_POST['adventure_text'] ?? '');

      $photo_urls = [];

      if (!empty($_FILES['mushroom_photos']['name'][0])) {
          require_once(ABSPATH . 'wp-admin/includes/file.php');
          require_once(ABSPATH . 'wp-admin/includes/media.php');
          require_once(ABSPATH . 'wp-admin/includes/image.php');

          foreach ($_FILES['mushroom_photos']['name'] as $key => $name) {
              if ($_FILES['mushroom_photos']['error'][$key] === 0) {
                  $file_array = [
                      'name'     => $_FILES['mushroom_photos']['name'][$key],
                      'tmp_name' => $_FILES['mushroom_photos']['tmp_name'][$key]
                  ];
                  if (file_exists($file_array['tmp_name'])) {
                      $attachment_id = media_handle_sideload($file_array, 0);
                      if (!is_wp_error($attachment_id)) {
                          $photo_urls[] = wp_get_attachment_url($attachment_id);
                      } else {
                          error_log('Media upload error: ' . $attachment_id->get_error_message());
                      }
                  } else {
                      error_log('Temp file missing: ' . $file_array['tmp_name']);
                  }
              }
          }
      }

      $inserted = $wpdb->insert(
          "{$wpdb->prefix}mushrooms",
          [
              'user_id'       => $user_id,
              'type'          => wp_json_encode($types),
              'kilograms'     => round(array_sum($types), 2),
              'start_date'    => $start_date,
              'location'      => $location,
              'adventure_text'=> $adventure_text,
              'photo_url'     => wp_json_encode($photo_urls)
          ]
      );

      if ($inserted) {
          wp_send_json_success(['photo_urls' => $photo_urls]);
      } else {
          wp_send_json_error($wpdb->last_error);
      }
  }


// ======================
// AJAX: Update Mushroom Entry (Multi-photos)
// ======================
add_action('wp_ajax_update_mushroom', 'my_update_mushroom_function');
function my_update_mushroom_function() {
    global $wpdb;

    $user_id = get_current_user_id();
    if (!$user_id) wp_send_json_error('User not logged in');

    $adventure_id = intval($_POST['adventure_id'] ?? 0);
    $hunt = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mushrooms WHERE id=%d", $adventure_id));
    if (!$hunt) wp_send_json_error('Adventure not found');
    if ($hunt->user_id != $user_id) wp_send_json_error('Not authorized');

    $types = $_POST['types'] ?? [];
$types = array_map('floatval', $types);
    $types = array_map('floatval', $types);

    $start_date = sanitize_text_field($_POST['start_date'] ?? '');
    $location = sanitize_text_field($_POST['location'] ?? '');
    $adventure_text = wp_kses_post($_POST['adventure_text'] ?? '');

    // Hämta befintliga bilder
    $existing_photos = json_decode($hunt->photo_url, true) ?: [];

    // Lägg till nya uppladdade bilder
    if (!empty($_FILES['mushroom_photos']['name'][0])) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        foreach ($_FILES['mushroom_photos']['name'] as $key => $name) {
            if ($_FILES['mushroom_photos']['error'][$key] === 0) {
                $file_array = [
                    'name'     => $_FILES['mushroom_photos']['name'][$key],
                    'tmp_name' => $_FILES['mushroom_photos']['tmp_name'][$key]
                ];
                $attachment_id = media_handle_sideload($file_array, 0);
                if (!is_wp_error($attachment_id)) {
                    $existing_photos[] = wp_get_attachment_url($attachment_id);
                }
            }
        }
    }

    $updated = $wpdb->update(
        "{$wpdb->prefix}mushrooms",
        [
            'type'          => wp_json_encode($types),
            'kilograms'     => round(array_sum($types), 2),
            'start_date'    => $start_date,
            'location'      => $location,
            'adventure_text'=> $adventure_text,
            'photo_url'     => wp_json_encode($existing_photos)
        ],
        ['id' => $adventure_id]
    );

    if ($updated !== false) wp_send_json_success(['photo_urls' => $existing_photos]);
    wp_send_json_error($wpdb->last_error);
}




// ======================
// Redirect Lost Password Form
// ======================
add_action('login_form_lostpassword', function () {
    wp_redirect(home_url('/forgot-password'));
    exit;
});


// ======================
// Hide admin toolbar on front-end
// ======================
add_filter('show_admin_bar', function($show) {
    return is_admin() ? $show : false;
});


// ======================
// Helper: Username → slug
// ======================
function mk_user_slug($username) {
    $slug = strtolower($username);
    $slug = preg_replace('/\s+/', '-', $slug);
    $slug = preg_replace('/[^a-z0-9_-]/', '', $slug);
    return $slug;
}


// ======================
// User Pages (/username)
// ======================
function mk_add_user_rewrite() {
    $reserved_slugs = ['login','register','insights','forgot-password','wp-admin','wp-login.php'];
    $pages = get_pages();
    foreach($pages as $page) {
        $reserved_slugs[] = strtolower($page->post_name);
    }

    $reserved_regex = implode('|', array_map('preg_quote', $reserved_slugs));

    add_rewrite_rule(
        '^(?!' . $reserved_regex . ')([a-z0-9_-]+)/?$',
        'index.php?pagename=user-adventures&mk_user=$matches[1]',
        'top'
    );
}
add_action('init', 'mk_add_user_rewrite');

function mk_add_user_query_var($vars) {
    $vars[] = 'mk_user';
    return $vars;
}
add_filter('query_vars', 'mk_add_user_query_var');

function mk_user_template_redirect() {
    $slug = get_query_var('mk_user');
    if ( !$slug ) return;

    // Try to get user by nicename (user_nicename = slug)
    $found_user = get_user_by('slug', $slug);

    if ( $found_user ) {
        // pass the user object into the template
        set_query_var('current_user_obj', $found_user);
        $template = locate_template('user-adventures.php');
        if ( $template ) {
            load_template( $template );
            exit;
        }
    }

    // not found -> 404
    global $wp_query;
    $wp_query->set_404();
    status_header(404);
    get_template_part(404);
    exit;
}

add_action('template_redirect', 'mk_user_template_redirect');


// ======================
// Flush rewrite rules on theme switch
// ======================
add_action('after_switch_theme', function() {
    flush_rewrite_rules();
});


// Get user avatar: prefer uploaded profile_image, fallback to Gravatar
function mk_get_user_avatar($user_id) {
    $profile_image = get_user_meta($user_id, 'profile_image', true);

    // If attachment ID, get URL
    if ($profile_image && is_numeric($profile_image)) {
        $avatar_url = wp_get_attachment_url($profile_image);
    } elseif ($profile_image && filter_var($profile_image, FILTER_VALIDATE_URL)) {
        // If URL stored directly
        $avatar_url = $profile_image;
    } else {
        // fallback to WP avatar
        $avatar_url = get_avatar_url($user_id);
    }

    return $avatar_url;
}


// ======================
// Flush rewrite rules on theme switch
// ======================
add_action('after_switch_theme', function() {
    flush_rewrite_rules();
});

// ======================
// Add Open Graph tags for user profile pages AND front page
// ======================
add_action('wp_head', function() {
    global $post;

    // Check if it's a user profile page (/username/)
    $slug = get_query_var('mk_user');
    if ($slug) {
        $user = get_user_by('slug', $slug);
        if (!$user) return;

        $user_id = $user->ID;
        $profile_url = home_url("/" . $user->user_nicename . "/");

        // Profile image
        $meta = get_user_meta($user_id, 'profile_image', true);
        $profile_img_url = '';
        if (!empty($meta)) {
            if (is_numeric($meta)) {
                $candidate = wp_get_attachment_image_url((int)$meta, 'large');
                if ($candidate) $profile_img_url = $candidate;
            } elseif (is_array($meta) && !empty($meta['url'])) {
                $profile_img_url = $meta['url'];
            } elseif (filter_var($meta, FILTER_VALIDATE_URL)) {
                $profile_img_url = $meta;
            }
        }
        if (!$profile_img_url) {
            $profile_img_url = get_avatar_url($user_id, ['size' => 512]);
        }
        if (!$profile_img_url) {
            $profile_img_url = get_template_directory_uri() . '/images/main-screen.png';
        }

        $description = get_user_meta($user_id, 'presentation', true);
        if (empty($description)) {
            $description = "Check out " . esc_html($user->display_name) . "'s foraging adventures on Mushroom King.";
        }




        ?>
        <!-- Open Graph Meta for User Profile -->
        <meta property="og:type" content="profile">
        <meta property="og:url" content="<?= esc_url($profile_url) ?>">
        <meta property="og:title" content="<?= esc_attr($user->display_name) ?>’s Foraging Adventures">
        <meta property="og:description" content="<?= esc_attr(wp_strip_all_tags($description)) ?>">
        <meta property="og:image" content="<?= esc_url($profile_img_url) ?>">
        <meta property="og:site_name" content="<?= get_bloginfo('name') ?>">
        <?php
        return;
    }

    // If it's the front page
    if (is_front_page()) {
        $front_url = home_url('/');
        $front_title = get_bloginfo('name') . " – Track, Share & Explore Your Mushroom Season";
        $front_desc = "Track all your foraging adventures, total harvest weight, best days, and locations — all in one place.";
        $front_img = get_template_directory_uri() . '/images/main-screen.png'; // fallback image

        ?>
        <!-- Open Graph Meta for Front Page -->
        <meta property="og:type" content="website">
        <meta property="og:url" content="<?= esc_url($front_url) ?>">
        <meta property="og:title" content="<?= esc_attr($front_title) ?>">
        <meta property="og:description" content="<?= esc_attr($front_desc) ?>">
        <meta property="og:image" content="<?= get_template_directory_uri(); ?>/images/mk-sharer-page.png">
        <meta property="og:site_name" content="<?= get_bloginfo('name') ?>">
        <?php
    }
});

// ======================
// Customize Password Reset Email Link
// ======================
add_filter('retrieve_password_message', function($message, $key, $user_login, $user_data) {
    $site_name = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

    $reset_url = home_url('/reset-password');
    $reset_url = add_query_arg([
        'key'   => $key,
        'login' => rawurlencode($user_login)
    ], $reset_url);

    // HTML message with clickable link
    $message  = "<p>Hi <strong>" . esc_html($user_login) . "</strong>,</p>";
    $message .= "<p>Someone has requested a password reset for your account on <strong>{$site_name}</strong>.</p>";
    $message .= "<p><a href=\"" . esc_url($reset_url) . "\" style=\"background:#2271b1;color:#fff;padding:10px 15px;text-decoration:none;border-radius:4px;\">Click here to reset your password</a></p>";
    $message .= "<p>If you did not request this, please ignore this email.</p>";

    return $message;
}, 10, 4);
wp_enqueue_style('theme-custom', get_template_directory_uri() . '/style.css', array('tailwind'), '1.0');


function mk_enable_frontend_editor() {
    if ( is_page_template('edit-profile.php') ) {
        // Enqueue TinyMCE editor scripts and styles
        wp_enqueue_editor();

        // Optional: media uploader if you use images in editor
        wp_enqueue_media();

        // jQuery is required by TinyMCE
        wp_enqueue_script('jquery');
    }
}
add_action('wp_enqueue_scripts', 'mk_enable_frontend_editor');



add_action('wp_ajax_update_mushroom', 'my_update_mushroom_function');
if (!function_exists('my_update_mushroom_function')) {
    add_action('wp_ajax_update_mushroom', 'my_update_mushroom_function');
    function my_update_mushroom_function() {
        global $wpdb;

        $user_id = get_current_user_id();
        if (!$user_id) wp_send_json_error('User not logged in');

        $adventure_id = intval($_POST['adventure_id'] ?? 0);
        if (!$adventure_id) wp_send_json_error('Adventure ID missing');

        $hunt = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mushrooms WHERE id=%d", $adventure_id));
        if (!$hunt) wp_send_json_error('Adventure not found');
        if ($hunt->user_id != $user_id) wp_send_json_error('Not authorized');

        $location = sanitize_text_field($_POST['location'] ?? '');
        $start_date = sanitize_text_field($_POST['start_date'] ?? '');
        $adventure_text = wp_kses_post($_POST['adventure_text'] ?? '');

        $types = [];
        if (!empty($_POST['types']) && is_array($_POST['types'])) {
            foreach ($_POST['types'] as $type => $amount) {
                $types[$type] = floatval($amount);
            }
        }

        $existing_photos = [];
        if (!empty($_POST['existing_photos']) && is_array($_POST['existing_photos'])) {
            $existing_photos = $_POST['existing_photos'];
        }

        if (!empty($_FILES['mushroom_photos']['name'][0])) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');

            foreach ($_FILES['mushroom_photos']['name'] as $key => $name) {
                if ($_FILES['mushroom_photos']['error'][$key] === 0) {
                    $file_array = [
                        'name' => $_FILES['mushroom_photos']['name'][$key],
                        'tmp_name' => $_FILES['mushroom_photos']['tmp_name'][$key]
                    ];
                    $attachment_id = media_handle_sideload($file_array, 0);
                    if (!is_wp_error($attachment_id)) {
                        $existing_photos[] = wp_get_attachment_url($attachment_id);
                    }
                }
            }
        }

        $updated = $wpdb->update(
            "{$wpdb->prefix}mushrooms",
            [
                'location' => $location,
                'start_date' => $start_date,
                'adventure_text' => $adventure_text,
                'types' => wp_json_encode($types),
                'photo_url' => wp_json_encode($existing_photos),
                'kilograms' => round(array_sum($types), 2)
            ],
            ['id' => $adventure_id],
            ['%s','%s','%s','%s','%s','%f'],
            ['%d']
        );

        if ($updated === false) {
            wp_send_json_error($wpdb->last_error);
        } else {
            wp_send_json_success([
                'adventure_id' => $adventure_id,
                'location' => $location,
                'start_date' => $start_date,
                'adventure_text' => $adventure_text,
                'types' => $types,
                'photos' => $existing_photos
            ]);
        }
    }
}

add_action('wp_ajax_mk_delete_adventure', 'mk_delete_adventure');

function mk_delete_adventure() {
    check_ajax_referer('mk_delete_adventure');

    if (!is_user_logged_in()) {
        wp_send_json_error('Not authorized');
    }

    global $wpdb;

    $adventure_id = intval($_POST['adventure_id']);
    $user_id = get_current_user_id();

    // Make sure user owns this adventure
    $owner_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT user_id FROM {$wpdb->prefix}mushrooms WHERE id = %d",
            $adventure_id
        )
    );

    if (intval($owner_id) !== $user_id) {
        wp_send_json_error('Permission denied');
    }

    // Delete adventure
    $deleted = $wpdb->delete(
        "{$wpdb->prefix}mushrooms",
        ['id' => $adventure_id],
        ['%d']
    );

    if ($deleted) {
        wp_send_json_success();
    } else {
        wp_send_json_error('Delete failed');
    }
}


