<?php


global $wpdb;
$adventure_id = get_query_var('adventure_id');

$hunt = $wpdb->get_row(
    $wpdb->prepare("SELECT * FROM {$wpdb->prefix}mushrooms WHERE id = %d", $adventure_id)
);

if (!$hunt) {
    // 404 if adventure not found
    global $wp_query;
    $wp_query->set_404();
    status_header(404);
    get_template_part(404);
    exit;
}

// Fetch user
$user = get_userdata($hunt->user_id);

// OG meta
$title = "Foraging Adventure by " . $user->display_name;
$description = "Collected " . $hunt->kilograms . " kg of mushrooms at " . esc_html($hunt->location);
$image = $hunt->photo_url ?: get_template_directory_uri() . '/images/default-hunt.png';
$url = home_url("/adventure/$adventure_id/");
?>
<!-- Open Graph Meta -->
<meta property="og:type" content="article">
<meta property="og:url" content="<?= esc_url($url) ?>">
<meta property="og:title" content="<?= esc_attr($title) ?>">
<meta property="og:description" content="<?= esc_attr($description) ?>">
<meta property="og:image" content="<?= esc_url($image) ?>">

<div id="adventure-summary-wrapper">
    <h1><?= esc_html($title) ?></h1>
    <p><?= esc_html($description) ?></p>
    <?php if($image): ?>
        <img src="<?= esc_url($image) ?>" alt="Adventure photo">
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Optional: Auto-open the modal if you have one
    if (typeof openAdventureModal === 'function') {
        openAdventureModal(<?= intval($adventure_id) ?>);
    }
});
</script>
