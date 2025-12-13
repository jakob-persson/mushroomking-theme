<?php
// ======================
// Enqueue Styles and Scripts
// ======================

add_action('template_redirect', function () {
    if (is_front_page() && !is_user_logged_in()) {
        wp_redirect(home_url('/login'));
        exit;
    }
});

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


// ======================
// AJAX: Add Mushroom Entry
// ======================
add_action('wp_ajax_add_mushroom', 'my_add_mushroom_function');
add_action('wp_ajax_nopriv_add_mushroom', 'my_add_mushroom_function');

function my_add_mushroom_function() {
    global $wpdb;

    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error('User not logged in');
    }

    $types = json_decode(stripslashes($_POST['types']), true);
    $start_date = sanitize_text_field($_POST['start_date']);
    $location = sanitize_text_field($_POST['location'] ?? '');

    $photo_url = '';
    if (!empty($_FILES['mushroom_photo']['name'])) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $attachment_id = media_handle_upload('mushroom_photo', 0);
        if (!is_wp_error($attachment_id)) {
            $photo_url = wp_get_attachment_url($attachment_id);
        }
    }

    $inserted = $wpdb->insert(
        "{$wpdb->prefix}mushrooms",
        [
            'user_id'   => $user_id,
            'type'      => wp_json_encode($types),
            'kilograms' => array_sum($types),
            'start_date'=> $start_date,
            'location'  => $location,
            'photo_url' => $photo_url
        ]
    );

    if ($inserted) {
        wp_send_json_success(['photo_url' => $photo_url]);
    } else {
        wp_send_json_error($wpdb->last_error);
    }
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
    if (!$slug) return;

    // Find user by matching slug
    $users = get_users(['search' => '*', 'search_columns' => ['user_login']]);
    $found_user = null;
    foreach ($users as $user) {
        if (mk_user_slug($user->user_login) === $slug) {
            $found_user = $user;
            break;
        }
    }

    if ($found_user) {
        set_query_var('current_user_obj', $found_user);
        $template = locate_template('user-adventures.php');
        if ($template) load_template($template);
        exit;
    }

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
