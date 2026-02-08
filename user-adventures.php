<?php
/**
 * Template Name: User Adventures
 */

global $wpdb;

/**
 * Stoppa cache på profilsidor (hjälper enormt om du har cache-plugin / servercache).
 * OBS: gör detta innan output.
 */
if (!defined('DONOTCACHEPAGE')) define('DONOTCACHEPAGE', true);
if (!defined('DONOTCACHEDB')) define('DONOTCACHEDB', true);
if (!defined('DONOTCACHEOBJECT')) define('DONOTCACHEOBJECT', true);

/**
 * Hämta profilens "ägare" tidigt (innan handlers) så vi kan:
 *  - veta vilken user page det är
 *  - kontrollera behörighet
 *  - redirecta till rätt URL efter save
 */
$username = get_query_var('mk_user');

if (!$username) {
  wp_safe_redirect(home_url('/login'));
  exit;
}

$user = get_user_by('slug', $username);
if (!$user) {
  wp_safe_redirect(home_url('/login'));
  exit;
}

$profile_user_id = (int) $user->ID;
$is_owner = is_user_logged_in() && ((int) get_current_user_id() === $profile_user_id);

/**
 * Säker helper: redirecta till samma profilsida men med cache-buster.
 */
function mk_redirect_self_with_ts() {
  $url = (is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

  // ta bort gamla flaggor om de finns
  $url = remove_query_arg(['profile_updated', '_ts'], $url);

  // lägg på nya
  $url = add_query_arg([
    'profile_updated' => '1',
    '_ts' => time(),
  ], $url);

  wp_safe_redirect($url);
  exit;
}

/**
 * =========================================
 * HANDLE EDIT PROFILE SAVE (från din modal)
 * =========================================
 * Kräver att:
 *  - man är inloggad
 *  - man editerar sin egen sida
 *  - nonce är giltig
 */
if (
  $is_owner &&
  isset($_POST['edit_profile_nonce']) &&
  wp_verify_nonce($_POST['edit_profile_nonce'], 'edit_profile_action')
) {
  $user_id = get_current_user_id();

  // display name
  if (isset($_POST['display_name'])) {
    $display_name = sanitize_text_field($_POST['display_name']);
    if ($display_name !== '') {
      $res = wp_update_user([
        'ID' => $user_id,
        'display_name' => $display_name,
      ]);
      if (is_wp_error($res)) {
        // valfritt: logga felet
        error_log('wp_update_user display_name error: ' . $res->get_error_message());
      }
    }
  }

  // email
  if (isset($_POST['email'])) {
    $email = sanitize_email($_POST['email']);
    if ($email !== '') {
      $res = wp_update_user([
        'ID' => $user_id,
        'user_email' => $email,
      ]);
      if (is_wp_error($res)) {
        error_log('wp_update_user email error: ' . $res->get_error_message());
      }
    }
  }

  // country meta
  if (isset($_POST['country'])) {
    update_user_meta($user_id, 'country', sanitize_text_field($_POST['country']));
  }

  // presentation meta
  if (isset($_POST['presentation'])) {
    update_user_meta($user_id, 'presentation', sanitize_textarea_field($_POST['presentation']));
  }

  // ✅ rensa cache så sidan inte visar gamla värden
  clean_user_cache($user_id);
  wp_cache_delete($user_id, 'users');
  wp_cache_delete($user_id, 'user_meta');
  wp_set_current_user($user_id);

  // ✅ redirecta tillbaka till exakt samma profilsida + cache-buster
  mk_redirect_self_with_ts();
}

/**
 * =========================
 * HANDLE PROFILE IMAGE UPLOAD
 * =========================
 * (Din befintliga) — men uppdaterad med cache-buster och owner-check
 */
if ($is_owner && isset($_FILES['profile_image_upload']) && !empty($_FILES['profile_image_upload']['name'])) {

  if (!function_exists('media_handle_upload')) {
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');
  }

  $attachment_id = media_handle_upload('profile_image_upload', 0);

  if (!is_wp_error($attachment_id)) {
    update_user_meta($profile_user_id, 'profile_image', $attachment_id);
    update_user_meta($profile_user_id, 'profile_image_url', wp_get_attachment_url($attachment_id));
  } else {
    error_log('profile_image_upload error: ' . $attachment_id->get_error_message());
  }

  clean_user_cache($profile_user_id);
  wp_cache_delete($profile_user_id, 'users');
  wp_cache_delete($profile_user_id, 'user_meta');

  mk_redirect_self_with_ts();
}

// Först NU börjar output
get_header();

$username = get_query_var('mk_user');

if (!$username) {
  // Not a user page, maybe redirect to /login or /insights
  wp_redirect(home_url('/login'));
  exit;
}

$user = get_user_by('slug', $username);

if (!$user) {
  wp_redirect(home_url('/login')); // or show 404
  exit;
}

$current_user_id = $user->ID;

// ======================
// Fetch Stats
// ======================
// Total kilograms
$total_kg = floatval($wpdb->get_var($wpdb->prepare("
    SELECT SUM(kilograms)
    FROM {$wpdb->prefix}mushrooms
    WHERE user_id = %d
", $current_user_id)));

// Total adventures (distinct start_date)
$total_rounds = intval($wpdb->get_var($wpdb->prepare("
    SELECT COUNT(DISTINCT DATE(start_date))
    FROM {$wpdb->prefix}mushrooms
    WHERE user_id = %d AND start_date IS NOT NULL AND start_date != '0000-00-00'
", $current_user_id)));

// Total unique locations
$total_locations = intval($wpdb->get_var($wpdb->prepare("
    SELECT COUNT(DISTINCT location)
    FROM {$wpdb->prefix}mushrooms
    WHERE user_id = %d AND location IS NOT NULL AND location != ''
", $current_user_id)));

// Best day (most kg on a single date)
$best_day_kg = floatval($wpdb->get_var($wpdb->prepare("
    SELECT SUM(kilograms)
    FROM {$wpdb->prefix}mushrooms
    WHERE user_id = %d AND start_date IS NOT NULL AND start_date != '0000-00-00'
    GROUP BY start_date
    ORDER BY SUM(kilograms) DESC
    LIMIT 1
", $current_user_id)));

// Fetch adventures
$grouped_mushrooms = $wpdb->get_results($wpdb->prepare("
    SELECT DATE(start_date) AS grouped_date, location, GROUP_CONCAT(photo_url) AS photos, SUM(kilograms) AS total_kg
    FROM {$wpdb->prefix}mushrooms
    WHERE user_id = %d
    GROUP BY grouped_date, location
    ORDER BY grouped_date DESC
", $current_user_id));

$hunts = array_map(function ($entry) {
  $photos = array_filter(explode(',', $entry->photos));
  return [
    'location' => $entry->location ?? 'Unknown',
    'date' => $entry->grouped_date,
    'timestamp' => $entry->grouped_date ? strtotime($entry->grouped_date) : time(),
    'total_kg' => floatval($entry->total_kg),
    'photo' => $photos[0] ?? null,
  ];
}, $grouped_mushrooms);

// ======================
// Mushroom Types Stats
// ======================
$results = $wpdb->get_results($wpdb->prepare("
    SELECT *
    FROM {$wpdb->prefix}mushrooms
    WHERE user_id = %d
", $current_user_id));

$totals_by_type = [];
$max_kg = 0;

foreach ($results as $row) {
  $raw = isset($row->types) ? $row->types : (isset($row->type) ? $row->type : '');
  $types = json_decode($raw, true);

  if (json_last_error() === JSON_ERROR_NONE && is_array($types)) {
    foreach ($types as $type => $kg) {
      if (!isset($totals_by_type[$type])) $totals_by_type[$type] = 0;
      $totals_by_type[$type] += floatval($kg);
      if ($totals_by_type[$type] > $max_kg) $max_kg = $totals_by_type[$type];
    }
  } elseif (is_string($raw) && strpos($raw, ':') !== false) {
    $pairs = explode(',', $raw);
    foreach ($pairs as $pair) {
      list($type, $kg) = array_map('trim', explode(':', $pair));
      if (!isset($totals_by_type[$type])) $totals_by_type[$type] = 0;
      $totals_by_type[$type] += floatval($kg);
      if ($totals_by_type[$type] > $max_kg) $max_kg = $totals_by_type[$type];
    }
  }
}

$mushroom_types = [];
foreach ($totals_by_type as $type => $total_kg_type) {
  $mushroom_types[] = (object) [
    'type' => $type,
    'total_kg' => $total_kg_type
  ];
}

// Mushroom icons
$mushroom_icons = [
  'Chanterelles' => get_template_directory_uri() . '/images/chant.jpeg',
  'Funnel chanterelles' => get_template_directory_uri() . '/images/funnel.jpeg',
  'Boletus' => get_template_directory_uri() . '/images/bolete.jpeg',
  'Trumpets' => get_template_directory_uri() . '/images/trumpet.jpeg',
];
?>
<!-- Hero Section -->
<div class="bg-[#F3F3F1] pb-12">

  <?php
  // Get user ID (depending on context)
  $user_id = $user->ID ?? get_current_user_id();

  // Fetch saved country from user meta
  $country = get_user_meta($user_id, 'country', true);

  // Map countries to codes and emoji flags
  $country_codes = [
    'United States'   => ['code' => 'USA', 'flag' => '🇺🇸'],
    'Canada'          => ['code' => 'CAN', 'flag' => '🇨🇦'],
    'United Kingdom'  => ['code' => 'UK',  'flag' => '🇬🇧'],
    'Germany'         => ['code' => 'DE',  'flag' => '🇩🇪'],
    'France'          => ['code' => 'FR',  'flag' => '🇫🇷'],
    'India'           => ['code' => 'IN',  'flag' => '🇮🇳'],
    'Australia'       => ['code' => 'AUS', 'flag' => '🇦🇺'],
    'Other'           => ['code' => 'OTH', 'flag' => '🏳️'],
    'Sweden'          => ['code' => 'SWE', 'flag' => '🇸🇪'],
  ];

  // Pick code and flag for this user
  $country_data = $country_codes[$country] ?? ['code' => strtoupper(substr($country, 0, 3)), 'flag' => '🏳️'];
  $country_code = $country_data['code'];
  $country_flag = $country_data['flag'];
  ?>

  <!-- Make stiicky scrolling whole page inn sam posiition -->
  <div
    x-data="{
        isSticky: false,
        originalWidth: null,
        isMobile: window.innerWidth < 768
    }"
    x-init="
        const el = $refs.sticky;
        originalWidth = el.offsetWidth;

        window.addEventListener('resize', () => {
            originalWidth = el.offsetWidth;
            isMobile = window.innerWidth < 768;
        });

        window.addEventListener('scroll', () => {
            isSticky = window.scrollY > 200;
        });
    "
    class="relative z-[9]">
    <div
      x-ref="sticky"
      :style="isSticky
            ? `position:fixed;
               top:${isMobile ? '92px' : '100px'};
               width:${originalWidth}px;
               left:50%;
               transform:translateX(-50%);`
            : ''"
      class="-mt-[10px] lg:mt-4">

      <!-- Inner container that keeps width consistent -->
      <div class="section-wrapper mx-auto px-4 flex justify-between items-center mt-0 flex-wrap gap-2">
        <div class="flex items-center space-x-2">
          <!-- Country Circle -->
          <div class="bg-[#2665D6] text-white text-sm font-regular px-3 py-3 rounded-full flex items-center justify-center space-x-1">
            <span><?= esc_html($country_flag) ?></span>
            <span><?= esc_html($country_code) ?></span>
          </div>

          <!-- Username Badge -->
          <div class="bg-white text-[#1E1E1E] text-sm regular px-4 py-3 rounded-full">
            <?= esc_html($user->display_name); ?>
          </div>
        </div>
      </div>
      <!-- Edit Profile Button -->
      <!-- <?php if (is_user_logged_in() && get_current_user_id() === $user->ID): ?>
            <a href="<?= home_url('/edit-profile/') ?>" class="px-6 py-3 bg-purple-600 text-white rounded hover:bg-purple-700 transition whitespace-nowrap">
                Edit Profile
            </a>
        <?php endif; ?> -->
    </div>


    <!-- Spacer to prevent layout shift when sticky -->
    <div :class="isSticky ? 'h-[72px] lg:h-[88px]' : ''"></div>
  </div>
  <!-- Large profile image -->
  <div class="flex-none lg:flex-1 flex justify-center items-center mt-4 lg:mt-0">
    <div id="tilt-container" class="relative perspective-[1000px] mt-[0px]">
        
   <?php
      $meta_profile = get_user_meta($user->ID, 'profile_image', true);
      if (empty($meta_profile)) {
        $meta_profile = get_user_meta($user->ID, 'profile_image_url', true);
      }

      $profile_img_url = '';
      $has_image = false;

      if ($meta_profile) {
        if (is_numeric($meta_profile)) {
          $maybe_url = wp_get_attachment_url((int)$meta_profile);
          if ($maybe_url) {
            $profile_img_url = $maybe_url;
            $has_image = true;
          }
        } elseif (filter_var($meta_profile, FILTER_VALIDATE_URL)) {
          $profile_img_url = $meta_profile;
          $has_image = true;
        }
      }

      // fallback image (om du vill ha en riktig default-bild istället för “no image”)
      $fallback_img = get_template_directory_uri() . '/images/default-profile-fb.png';

      // alltid ha en src
      $profile_img_url = $has_image ? $profile_img_url : $fallback_img;

      // ✅ debug efter att $has_image är satt
      $debug_no_image   = isset($_GET['debug']) && $_GET['debug'] === '1';
      $show_placeholder = !$has_image || $debug_no_image;
      ?>



     <div class="fb-profile-wrapper relative w-full h-full">

        <?php if ($show_placeholder): ?>
          <!-- Placeholder -->
          <div
            id="profile-placeholder"
            class="absolute inset-0 flex items-center justify-center rounded-[150px] bg-white/70 border border-black/10 z-10">
            <div class="flex flex-col items-center gap-2 text-[#111827]/70">
              <i class="fas fa-image text-3xl"></i>
              <span class="text-sm font-medium">Add profile image</span>
            </div>
          </div>
        <?php endif; ?>

        <!-- Profile image (alltid i DOM) -->
        <img
          src="<?= esc_url($profile_img_url); ?>"
          alt="<?= esc_attr($user->display_name); ?> profile picture"
          class="px-4 lg:px-0 w-[720px] max-w-full rounded-[150px] h-[220px] lg:h-[400px] object-cover drop-shadow-xl transition-opacity duration-150
            <?= ($show_placeholder && $debug_no_image) ? 'opacity-0' : 'opacity-100' ?>"
        >

        <?php if (is_user_logged_in() && get_current_user_id() == $user->ID): ?>
          <!-- Kamera-badge -->
          <div
            id="profile-camera-badge"
            class="fb-camera-badge"
            style="<?= $has_image && !$debug_no_image ? 'display:flex;' : 'display:none;' ?>">
            <i class="fas fa-camera"></i>
          </div>

          <!-- Upload -->
          <form method="post" enctype="multipart/form-data">
            <input
              type="file"
              name="profile_image_upload"
              id="profile_image_upload"
              class="hidden"
              accept="image/*">
          </form>
        <?php endif; ?>

      </div>



      <!-- Badge Overlay -->
      <!-- <div class="absolute bottom-[100px] -right-[140px] flex items-center space-x-2">
          <div class="bg-[#6B3E00] text-white text-sm regular px-3 py-3 rounded-full shadow-md">
            <?= esc_html($country_code); ?>
          </div>
          <div class="bg-white text-[#1E1E1E] text-sm regular px-4 py-3 rounded-full shadow-md">
            <?= esc_html($user->display_name); ?>
          </div>
        </div> -->
    </div>
  </div>

  <!-- Text Column -->
  <div class="flex flex-col lg:flex-row justify-center items-start pt-4 gap-10">
    <div class="text-center px-4 sm:px-6 mb-4 flex flex-col justify-start items-center pt-2 lg:pt-0 max-w-full max-w-3xl lg:max-w-4xl mx-auto">
      <h1 class="text-5xl lg:text-[82px] dark gilroy leading-[42px] lg:leading-[82px]">
        <span class="text-[#2665D6]">
          <?= esc_html($user->display_name); ?>
        </span>
      </h1>

      <!-- ✅ Presentation text -->
      <p class="dark text-lg my-2 max-w-xl presentation-text">
        <?= wp_kses_post(get_user_meta($user->ID, 'presentation', true)); ?>
      </p>

      <button class="mt-6 bg-[#E9C0E9] px-6 py-4 rounded-full font-medium text-[#1E1E1E] hover:opacity-90">
        View summary
      </button>
    </div>
  </div>


</div>

<div class="text-[#E9EED8] bg-[#F3F3F1] pb-[78px] lg:pb-[0] lg:min-h-screen flex flex-col" style="padding-top: 78px;">

  <div class="flex justify-center w-full py-16 lg:px-16 px-6">
    <h1 class="lg:text-center text-5xl lg:text-6xl dark gilroy leading-[48px] lg:leading-[62px] lg:w-[60%]">
      Latest mushroom <span class="text-[#2665D6]">adventures</span>
    </h1>
  </div>

  <div id="adventure-slider" class="flex overflow-x-auto no-scrollbar space-x-4 snap-x snap-mandatory ml-6 lg:ml-16">

    <?php
    // Use the profile user displayed
    $current_user_id = isset($user) ? $user->ID : get_current_user_id();

    // Fetch the latest 10 adventures
    $adventures = $wpdb->get_results($wpdb->prepare("
    SELECT id, photo_url, location, start_date
    FROM {$wpdb->prefix}mushrooms
    WHERE user_id = %d
      AND start_date IS NOT NULL AND start_date != '0000-00-00'
    ORDER BY start_date DESC
    LIMIT 10
", $current_user_id));

    // Styles for the adventure boxes
    $shape_styles = [
      ['class' => 'rounded-[20px]', 'size' => 'w-[306px] h-[220px] lg:w-[426px] lg:h-[340px]'],
      ['class' => 'rounded-[20px] lg:rounded-[52px]', 'size' => 'w-[306px] h-[220px] lg:w-[260px] lg:h-[340px]']
    ];

    if (!$adventures) {
      echo '<div class="text-white text-xl px-4">Inga äventyr hittades.</div>';
    } else {
      foreach ($adventures as $index => $adventure) :
        $style = $shape_styles[$index % count($shape_styles)];
        $class = $style['class'];
        $size = $style['size'];

        // Handle single or multiple images
        $photos = json_decode($adventure->photo_url, true);
        if (!$photos || !is_array($photos)) {
          $photos = !empty($adventure->photo_url) ? [$adventure->photo_url] : [];
        }
        $photo_url = $photos[0] ?? get_template_directory_uri() . '/images/default-adventure.jpg';
        $location = !empty($adventure->location) ? esc_html($adventure->location) : 'Unknown';
    ?>
        <div
          class="relative flex-shrink-0 <?= esc_attr("$size $class"); ?> overflow-hidden snap-start cursor-pointer group"
          @click="$store.adventureModal.open({ id: <?= intval($adventure->id); ?> })">
          <img src="<?= esc_url($photo_url); ?>"
            class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
            alt="Adventure Photo" />

          <div class="absolute bottom-4 left-4 bg-white text-[#111827] text-sm px-3 py-2 rounded-full lowercase">
            #<?= $location ?>
          </div>
        </div>
    <?php
      endforeach;
    }
    ?>
  </div>
</div>

<?php get_template_part('partials/modal', 'adventure'); ?>





<!-- ======================
        Overview Stats Section
    ====================== -->
<div class="py-12 bg-[#e8efd6] lg:h-[100vh] flex justify-center" style="padding-top: 128px; padding-bottom: 128px;">
  <div class="section-wrapper flex">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center">

      <!-- Left: Stat Boxes -->
      <div class="order-2 md:order-1 space-y-4 mb-6 lg:px-20">
        <div class="flex space-x-4">
          <div class="bg-[#6E5F40] text-white p-6 rounded-[20px] flex-[2]">
            <div class="flex justify-between items-center">
              <img src="<?= get_template_directory_uri(); ?>/images/basket.svg" class="w-12">
              <div class="flex flex-col items-center gap-2">
                <div class="text-sm font-medium opacity-80">Total</div>
                <div class="text-3xl font-bold"><?= rtrim(rtrim(number_format($total_kg, 2, '.', ''), '0'), '.') ?></div>
                <div class="text-sm opacity-80">Kilogram</div>
              </div>
            </div>
          </div>

          <div class="bg-[#E9C0E9] text-black p-6 rounded-[20px] flex-1 text-center">
            <div class="flex flex-col gap-1">
              <img class="w-12 mx-auto mb-2" src="<?= get_template_directory_uri(); ?>/images/mountain.svg">
              <div class="text-3xl font-bold"><?= $total_rounds ?></div>
              <div class="text-sm font-medium opacity-80">Adventures</div>
            </div>
          </div>
        </div>

        <div class="flex space-x-4">
          <div class="bg-[#C637DF] text-white p-6 rounded-[20px] flex-1 text-center">
            <img class="w-12 mx-auto mb-2" src="<?= get_template_directory_uri(); ?>/images/locations.svg">
            <div class="text-3xl font-bold"><?= $total_locations ?></div>
            <div class="text-sm font-medium opacity-80">Locations</div>
          </div>

          <div class="bg-[#D2E823] text-white p-6 rounded-[20px] flex-[2]">
            <div class="flex justify-between items-center">
              <img src="<?= get_template_directory_uri(); ?>/images/trophy3.svg" class="w-12">
              <div class="flex flex-col items-center justify-center gap-2 dark">
                <div class="text-sm font-medium opacity-80">Best of the day</div>
                <div class="text-3xl font-bold"><?= rtrim(rtrim(number_format($best_day_kg, 2, '.', ''), '0'), '.') ?></div>
                <div class="text-sm opacity-80">Kilogram</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Right: Text -->
      <div class="order-1 md:order-2 lg:px-16 lg:py-16">
        <h1 class="font-bold gilroy lg:text-6xl leading-[62px] text-5xl">Overview of the season 2025</h1>
        <p class="mt-4 text-base leading-relaxed dark">
          Monitor your foraging trips, total harvest weight, and top locations.
          See which days bring in the biggest hauls and keep improving your skills
          to find more of nature’s hidden treasures.
        </p>
        <button class="bg-[#E9C0E9] mt-6 px-6 py-4 rounded-full font-medium text-[#1E1E1E] hover:opacity-90">View all stats</button>
      </div>

    </div>
  </div>
</div>

<!-- ======================
        Mushroom Type Stats Section
    ====================== -->
<div class="py-12 bg-[#1E2330] lg:h-[100vh] flex justify-center items-center pt-[128px] lg:pt-[478px] pb-[128px] lg:pb-[478px]">
  <div class="section-wrapper flex">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center">

      <div class="space-y-8 mb-6 lg:px-20">
        <h1 class="font-bold gilroy text-white lg:text-6xl leading-[52px] text-5xl">Your foraging adventures sorted by mushroom<br>type</h1>
        <p class="text-white text-base leading-relaxed">
          See your harvests broken down by mushroom type. Track how much you’ve gathered of each variety and discover which mushrooms dominate your adventures.
        </p>
        <button class="bg-[#E9C0E9] px-6 py-4 rounded-full font-medium text-[#1E1E1E] hover:opacity-90">View all stats</button>
      </div>

      <div class="lg:px-16 lg:py-16 lg:px-20">
        <div class="bg-white rounded-[30px] p-8 w-full max-w-2xl">
          <h2 class="text-sm font-bold mb-6">By mushroom type</h2>
          <div class="flex items-center justify-between text-xs font-regular mb-4">
            <span>Type / Sort</span>
            <span class="text-right" style="margin-right: 18px">Weight</span>
          </div>

          <div class="space-y-4">
            <?php foreach ($mushroom_types as $m):
              $icon = isset($mushroom_icons[$m->type])
                ? $mushroom_icons[$m->type]
                : get_template_directory_uri() . '/assets/img/mushrooms/default.jpg';
            ?>
              <div class="flex items-center justify-between bg-[#F0F2EF] rounded-2xl p-4">
                <div class="flex items-center space-x-3">
                  <img src="<?= esc_url($icon) ?>" alt="<?= esc_attr($m->type) ?> icon" class="w-12 h-12 rounded-full object-cover">
                  <span class="font-regular text-sm text-[#1E2330]"><?= esc_html($m->type) ?></span>
                </div>
                <div class="text-right text-sm font-medium text-[#1E2330]">
                  <?= rtrim(rtrim(number_format($m->total_kg, 2, '.', ''), '0'), '.') ?>kg
                </div>
              </div>
            <?php endforeach; ?>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>
<?php
get_template_part('partials/modal', 'add-adventure');
?>
<script>
  document.addEventListener("DOMContentLoaded", () => {

    const wrapper = document.querySelector(".fb-profile-wrapper");
    const input = document.getElementById("profile_image_upload");
    const placeholder = document.getElementById("profile-placeholder");
    const previewWrap = document.getElementById("profile-preview");
    const previewImg = document.getElementById("profile-preview-img");
    const badge = document.getElementById("profile-camera-badge");

    if (!wrapper || !input) return;

    wrapper.addEventListener("click", () => {
      input.click();
    });

    input.addEventListener("change", () => {
      const file = input.files[0];
      if (!file) return;

      const reader = new FileReader();
      reader.onload = e => {
        previewImg.src = e.target.result;
        previewWrap.style.display = "flex";

        if (badge) badge.style.display = "flex";
        if (placeholder) placeholder.style.display = "none";

        // auto-submit
        input.form.submit();
      };
      reader.readAsDataURL(file);
    });

  });
</script>

<style>
  .mk-pulse {
    animation: mkPulse 1.2s ease-in-out infinite;
    box-shadow: 0 0 0 0 rgba(206, 224, 39, 0.8);
  }
  @keyframes mkPulse {
    0%   { box-shadow: 0 0 0 0 rgba(206, 224, 39, 0.75); transform: scale(1); }
    70%  { box-shadow: 0 0 0 16px rgba(206, 224, 39, 0); transform: scale(1.02); }
    100% { box-shadow: 0 0 0 0 rgba(206, 224, 39, 0); transform: scale(1); }
  }
</style>

<script>
document.addEventListener('alpine:initialized', () => {
  const params = new URLSearchParams(window.location.search);
  if (params.get('edit') !== '1') return;

  // Extra säkerhet: öppna bara om man är ägaren (PHP sätter true/false)
  const isOwner = <?php echo $is_owner ? 'true' : 'false'; ?>;
  if (!isOwner) return;

  // 1) Öppna edit-profile modal
  const store = Alpine.store('editProfileModal');
  if (store) store.isOpen = true;

  // 2) Highlighta profilbild/kamera så användaren fattar vad som ska göras
  const badge = document.getElementById('profile-camera-badge');
  const wrapper = document.querySelector('.fb-profile-wrapper');

  if (badge) badge.classList.add('mk-pulse');

  // 4) Städa URL så den inte triggas igen vid refresh
  params.delete('edit');
  const newUrl =
    window.location.pathname +
    (params.toString() ? `?${params}` : '') +
    window.location.hash;

  window.history.replaceState({}, '', newUrl);

  // (valfritt) ta bort pulse efter några sekunder
  setTimeout(() => {
    if (badge) badge.classList.remove('mk-pulse');
  }, 6000);
});
</script>


<?php get_footer(); ?>