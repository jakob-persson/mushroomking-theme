<?php
function jsp_enqueue_assets() {
    // Load FontAwesome CSS
    wp_enqueue_style('fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css');

    // Load your theme stylesheet
    wp_enqueue_style('jsp-style', get_stylesheet_uri());

    // Load AlpineJS script
    wp_enqueue_script('alpine', 'https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js', [], null, true);
}
add_action('wp_enqueue_scripts', 'jsp_enqueue_assets');


// ======================
// AJAX: Add Mushroom Entry
// ======================
add_action('wp_ajax_add_mushroom', 'my_add_mushroom_function');
add_action('wp_ajax_nopriv_add_mushroom', 'my_add_mushroom_function');

function my_add_mushroom_function() {
    global $wpdb;

    // Decode mushrooms from JSON
    $types = json_decode(stripslashes($_POST['types']), true);
    $start_date = sanitize_text_field($_POST['start_date']);
    $location = sanitize_text_field($_POST['location'] ?? '');

    // Handle image upload if exists
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

    // Insert into DB as JSON for easy stats parsing
    $inserted = $wpdb->insert(
        "{$wpdb->prefix}mushrooms",
        [
            'type'      => wp_json_encode($types), // Store as JSON instead of string
            'kilograms'  => array_sum($types),
            'start_date' => $start_date,
            'location'   => $location,
            'photo_url'  => $photo_url
        ]
    );

    if ($inserted) {
        wp_send_json_success(['photo_url' => $photo_url]);
    } else {
        wp_send_json_error($wpdb->last_error);
    }
}


// ======================
// Restrict Front Page to Logged-In Users
// ======================
function restrict_front_page_to_logged_in_users() {
    if (is_front_page() && !is_user_logged_in()) {
        wp_redirect(home_url('/login'));
        exit;
    }
}
add_action('template_redirect', 'restrict_front_page_to_logged_in_users');

// ======================
// Redirect Lost Password Form
// ======================
add_action('login_form_lostpassword', function () {
    wp_redirect(home_url('/forgot-password'));
    exit;
});

// Hide admin toolbar on the front-end only
add_filter('show_admin_bar', function($show) {
    return is_admin() ? $show : false;
});
