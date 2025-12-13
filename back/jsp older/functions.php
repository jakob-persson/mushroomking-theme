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

// Register the AJAX actions
add_action('wp_ajax_add_mushroom', 'handle_add_mushroom');
add_action('wp_ajax_nopriv_add_mushroom', 'handle_add_mushroom'); // optional if not logged in

function handle_add_mushroom() {
    // Sanitize form inputs
    $type = sanitize_text_field($_POST['type']);
    $kilograms = floatval($_POST['kilograms']);
    $start_date = sanitize_text_field($_POST['start_date']);
    $location = isset($_POST['location']) ? sanitize_text_field($_POST['location']) : '';
    $photo_url = '';

    // Handle photo upload
    if (!empty($_FILES['mushroom_photo']['name'])) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $uploaded = media_handle_upload('mushroom_photo', 0);

        if (!is_wp_error($uploaded)) {
            $photo_url = wp_get_attachment_url($uploaded);
        }
    }

    global $wpdb;
    $wpdb->insert("{$wpdb->prefix}mushrooms", [
        'type' => $type,
        'kilograms' => $kilograms,
        'start_date' => $start_date,
        'location' => $location,
        'created_at' => current_time('mysql'),
        'photo_url' => $photo_url // optional: add this column in DB
    ]);

    wp_send_json_success(['message' => 'Mushroom added successfully.']);
    wp_die();
}

function restrict_front_page_to_logged_in_users() {
    if (is_front_page() && !is_user_logged_in()) {
        wp_redirect(home_url('/login'));
        exit;
    }
}
add_action('template_redirect', 'restrict_front_page_to_logged_in_users');

// function hide_admin_bar_for_non_admins() {
//     if (!current_user_can('administrator') && !is_admin()) {
//         show_admin_bar(false);
//     }
// }
// add_action('after_setup_theme', 'hide_admin_bar_for_non_admins');

// add_action('admin_init', 'redirect_from_admin_panel');
// function redirect_from_admin_panel() {
//     if (!defined('DOING_AJAX') || !DOING_AJAX) {
//         wp_redirect(home_url('/'));
//         exit;
//     }
// }

// add_filter('show_admin_bar', '__return_false');


add_action('login_form_lostpassword', function () {
    // Redirect to your custom page
    wp_redirect(home_url('/forgot-password'));
    exit;
});
