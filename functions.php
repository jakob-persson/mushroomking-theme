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

    wp_enqueue_style(
        'jsp-style',
        get_stylesheet_uri(),
        [],
        filemtime(get_stylesheet_directory() . '/style.css')
    );


add_action('wp_enqueue_scripts', 'jsp_enqueue_assets');


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


  add_image_size('feed_card', 900, 900, true);
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


add_action('wp_ajax_upload_profile_avatar', 'upload_profile_avatar');

function upload_profile_avatar() {

    if (!is_user_logged_in()) {
        wp_send_json_error('Not logged in');
    }

    if (empty($_FILES['avatar'])) {
        wp_send_json_error('No file');
    }

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $upload = wp_handle_upload($_FILES['avatar'], ['test_form' => false]);

    if (isset($upload['error'])) {
        wp_send_json_error($upload['error']);
    }

    $filetype = wp_check_filetype($upload['file']);
    $attachment = [
        'post_mime_type' => $filetype['type'],
        'post_title'     => basename($upload['file']),
        'post_status'    => 'inherit'
    ];

    $attach_id = wp_insert_attachment($attachment, $upload['file']);
    $attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
    wp_update_attachment_metadata($attach_id, $attach_data);

    update_user_meta(get_current_user_id(), 'profile_image', $attach_id);

    wp_send_json_success([
        'avatar_id' => $attach_id
    ]);
}


// Feed: hämta en optimerad bild-URL (thumbnail) från attachment ID eller URL
if (!function_exists('mk_image_url_for_size')) {
  function mk_image_url_for_size($value, $size = 'feed_card') {
    if (!$value) return '';

    // 1) attachment-id direkt
    if (is_numeric($value)) {
      $src = wp_get_attachment_image_src((int)$value, $size);
      return $src[0] ?? '';
    }

    // 2) JSON-array (om värdet råkar vara JSON)
    if (is_string($value) && substr(trim($value), 0, 1) === '[') {
      $arr = json_decode($value, true);
      if (json_last_error() === JSON_ERROR_NONE && is_array($arr) && !empty($arr)) {
        return mk_image_url_for_size($arr[0], $size);
      }
    }

    // 3) URL -> försök hitta attachment id i Media Library
    if (is_string($value) && filter_var($value, FILTER_VALIDATE_URL)) {
      $att_id = attachment_url_to_postid($value);
      if ($att_id) {
        $src = wp_get_attachment_image_src((int)$att_id, $size);
        if (!empty($src[0])) return $src[0];
      }
      return $value; // fallback (extern eller ej hittad)
    }

    return '';
  }
}
add_action('after_setup_theme', function () {
  add_image_size('feed_card', 900, 900, true);
});


add_action('wp_ajax_mk_load_more_hunts', 'mk_load_more_hunts');
add_action('wp_ajax_nopriv_mk_load_more_hunts', 'mk_load_more_hunts');

function mk_load_more_hunts() {
  // (Valfritt) om du bara vill tillåta inloggade:
  if (!is_user_logged_in()) {
    wp_send_json_error(['message' => 'Not logged in'], 403);
  }

  // Nonce
  $nonce = isset($_POST['_ajax_nonce']) ? sanitize_text_field($_POST['_ajax_nonce']) : '';
  if (!wp_verify_nonce($nonce, 'mk_load_more_hunts')) {
    wp_send_json_error(['message' => 'Bad nonce'], 403);
  }

  $offset = isset($_POST['offset']) ? max(0, intval($_POST['offset'])) : 0;
  $limit  = isset($_POST['limit']) ? intval($_POST['limit']) : 5;

  // skydda servern
  if ($limit < 1) $limit = 5;
  if ($limit > 20) $limit = 20;

  global $wpdb;

  // Viktigt: prepare med %d funkar här
  $rows = $wpdb->get_results(
    $wpdb->prepare(
      "SELECT * FROM {$wpdb->prefix}mushrooms
       ORDER BY start_date DESC
       LIMIT %d OFFSET %d",
      $limit,
      $offset
    )
  );

  if (!$rows) {
    wp_send_json_success([
      'html' => '',
      'count' => 0,
    ]);
  }

  // Rendera HTML på serversidan (så du slipper duplicera markup i JS)
  ob_start();
  foreach ($rows as $hunt) {
    mk_render_feed_card($hunt);
  }
  $html = ob_get_clean();

  wp_send_json_success([
    'html'  => $html,
    'count' => count($rows),
  ]);
}

/**
 * Renderar exakt samma feed-card markup som på front-page.
 * Du kan justera klasser här och de gäller både initial load och infinite scroll.
 */
function mk_render_feed_card($hunt) {
    $user_id = intval($hunt->user_id ?? 0);
    $user = $user_id ? get_user_by('id', $user_id) : null;

    $username = $user ? $user->display_name : 'Unknown';
    $avatar   = mk_get_user_avatar($user_id);

    $profile_url = $user
    ? home_url('/' . $user->user_nicename)
    : '#';

  $user_id = intval($hunt->user_id);
  $user    = get_user_by('id', $user_id);
  $username = $user ? $user->display_name : 'Unknown';
  $avatar  = function_exists('mk_get_user_avatar') ? mk_get_user_avatar($user_id) : get_avatar_url($user_id);

  // Foto-array
  $photos = json_decode($hunt->photo_url, true);
  if (!$photos || !is_array($photos)) {
    $photos = [$hunt->photo_url ?: get_template_directory_uri() . "/images/placeholder.png"];
  }

  $first_photo = $photos[0] ?? null;

  // Thumbnail för feed-kort
  $feed_photo = function_exists('mk_image_url_for_size')
    ? mk_image_url_for_size($first_photo, 'feed_card')
    : ($first_photo ?: get_template_directory_uri() . "/images/placeholder.png");

  if (!$feed_photo) {
    $feed_photo = get_template_directory_uri() . "/images/placeholder.png";
  }

  $weight = rtrim(rtrim(number_format((float)$hunt->kilograms, 2, ',', ''), '0'), ',');

  // Objekt för modalen – behåll originalbilder i "photos"
  $hunt_object = [
    'id'             => intval($hunt->id),
    'photos'         => $photos,
    'photo'          => $first_photo,
    'location'       => $hunt->location,
    'date'           => $hunt->start_date,
    'username'       => $username,
    'user_id'        => $hunt->user_id,
    'total_kg'       => floatval($hunt->kilograms ?? 0),
    'type'           => $hunt->type ?? '{}',
    'adventure_text' => $hunt->adventure_text ?? '',
  ];
  ?>
  <div
    class="relative bg-white rounded-[30px] overflow-hidden shadow-sm cursor-pointer ml-[1px] feed-card z-1"
    style="width: calc(100% - 11px); aspect-ratio: 1 / 1;"
    @click='$store.adventureModal.open(<?= json_encode($hunt_object, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'
  >
    <img
      src="<?= esc_url($feed_photo); ?>"
      loading="lazy"
      decoding="async"
      class="absolute inset-0 w-full h-full object-cover"
      alt=""
    >

    <div class="absolute top-0 left-0 right-0 h-36 bg-gradient-to-b from-black/60 to-transparent"></div>
    <div class="absolute inset-0 bg-gradient-to-t from-black/50 via-black/10 to-transparent"></div>
    <?php $is_owner = is_user_logged_in() && ((int)get_current_user_id() === (int)$hunt->user_id);
        ?>

        <!-- Actions (3 dots) -->
        <div class="absolute top-5 right-4" x-data="{ open: false }" @click.stop>
        <!-- More button -->
        <button
            @click.stop="open = !open"
            :class="open ? 'bg-white dark shadow-md text-black' : 'bg-transparent text-white'"
            class="w-10 h-10 rounded-full flex items-center justify-center transition"
            type="button"
        >
            <i class="fas fa-ellipsis-h text-xl"></i>
        </button>

        <!-- Dropdown -->
        <div
            x-show="open"
            x-transition
            @click.away="open = false"
            class="absolute right-0 mt-2 w-72 bg-white shadow-lg rounded-lg z-50 p-4"
        >
            <?php if ($is_owner): ?>
            <!-- Edit -->
            <button
                type="button"
                data-hunt='<?= htmlspecialchars(json_encode($hunt_object), ENT_QUOTES, 'UTF-8') ?>'
                @click.stop="
                $store.editAdventureModal.adventure = JSON.parse($el.dataset.hunt);
                open = false;
                $nextTick(() => $store.editAdventureModal.open = true);
                "
                class="flex items-center gap-2 w-full text-left px-3 py-2 text-sm dark hover:bg-[#eff0ec] rounded-md"
            >
                <i class="fas fa-pen w-4"></i>
                <span>Edit adventure</span>
            </button>

            <!-- Delete -->
            <button
                type="button"
                @click.stop="
                open = false;
                if (confirm('Are you sure you want to delete this adventure? This cannot be undone.')) {
                    deleteAdventure(<?= intval($hunt->id); ?>);
                }
                "
                class="flex items-center gap-2 w-full text-left px-3 py-2 text-sm dark hover:bg-[#eff0ec] rounded-md"
            >
                <i class="fas fa-trash-alt w-4"></i>
                <span>Delete</span>
            </button>
            <?php else: ?>
            <div class="text-sm text-gray-500">No actions available.</div>
            <?php endif; ?>
        </div>
        </div>


      <a
        href="<?= esc_url($profile_url); ?>"
        class="absolute top-4 left-4 flex items-center gap-3 z-1 w-[50%] hover:opacity-90 transition"
        @click.stop
        >
        <img src="<?= esc_url($avatar); ?>" class="w-10 h-10 rounded-full object-cover">
        <span class="text-white text-sm"><?= esc_html($username); ?></span>
        </a>


    <div class="absolute bottom-4 lg:bottom-6 left-6 right-6 rounded-[30px] pb-6 flex justify-between items-start z-1">
      <div class="max-w-[100%] flex flex-col">
        <div class="text-white text-2xl gilroy" style="line-height:18px"><?= esc_html($hunt->location); ?></div>
        <div class="text-white">
          <span class="font-regular text-xs"><?= esc_html($hunt->start_date); ?> • <?= esc_html($weight); ?>kg</span>
        </div>
        <div class="text-white font-regular text-sm leading-snug mt-1">
          <?= esc_html(wp_trim_words($hunt->adventure_text, 15)); ?>
        </div>
      </div>
    </div>
  </div>
  <?php
}
