<?php

/**
 * Template Name: User Adventures
 */

global $wpdb;

if (! function_exists('media_handle_upload')) {
  require_once(ABSPATH . 'wp-admin/includes/image.php');
  require_once(ABSPATH . 'wp-admin/includes/file.php');
  require_once(ABSPATH . 'wp-admin/includes/media.php');
}

// -------------------------
// HANDLE PROFILE UPLOAD
// -------------------------
// Must run before any output (so redirect works)
if (isset($_FILES['profile_image_upload']) && is_user_logged_in()) {

  // Load required WP media libraries
  if (!function_exists('media_handle_upload')) {
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');
  }

  $user_id_upload = get_current_user_id();

  // Process upload
  $attachment_id = media_handle_upload('profile_image_upload', 0);

  if (!is_wp_error($attachment_id)) {
    // Store attachment ID (preferred)
    update_user_meta($user_id_upload, 'profile_image', $attachment_id);

    // Also store URL for backwards compatibility / convenience
    update_user_meta($user_id_upload, 'profile_image_url', wp_get_attachment_url($attachment_id));
  }

  // Redirect to avoid re-submit and to show updated image
  wp_safe_redirect($_SERVER['REQUEST_URI']);
  exit;
}

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
      // Get profile image meta - support attachment ID or legacy URL
      $meta_profile = get_user_meta($user->ID, 'profile_image', true);

      // If meta is empty, maybe we stored a URL previously in profile_image_url
      if (empty($meta_profile)) {
        $meta_profile = get_user_meta($user->ID, 'profile_image_url', true);
      }

      $profile_img_url = '';
      $has_image = false;

      if ($meta_profile) {
        // If numeric -> treat as attachment ID
        if (is_numeric($meta_profile)) {
          $maybe_url = wp_get_attachment_url(intval($meta_profile));
          if ($maybe_url) {
            $profile_img_url = $maybe_url;
            $has_image = true;
          } else {
            // fallback: if stored as ID but not found, leave as default
            $has_image = false;
          }
        } elseif (filter_var($meta_profile, FILTER_VALIDATE_URL)) {
          // stored url
          $profile_img_url = $meta_profile;
          $has_image = true;
        }
      }

      if (!$has_image) {
        $profile_img_url = get_template_directory_uri() . '/images/default-profile-fb.png';
        $has_image = false;
      }
      ?>

      <div class="fb-profile-wrapper relative w-full h-full">

        <?php if (!$has_image): ?>
          <!-- Placeholder (FB-like) -->
          <div id="profile-placeholder" class="fb-placeholder">
            <div class="fb-camera-center">
              <i class="fas fa-camera"></i>
            </div>
          </div>
        <?php endif; ?>

        <!-- ALWAYS present preview -->

        <img id="profile-preview-img"
          src="<?= esc_url($profile_img_url); ?>"
          alt="<?= esc_attr($user->display_name); ?>'s profile picture"
          class="px-4 lg:px-0 w-[720px] max-w-full rounded-[150px] h-[220px] lg:h-[400px] object-cover lg:rounded-[150px] drop-shadow-xl transition-transform duration-150 ease-out">


        <!-- Camera badge (only if logged in as the same user) -->
        <?php if (is_user_logged_in() && get_current_user_id() == $user->ID): ?>
          <div id="profile-camera-badge" class="fb-camera-badge" style="<?= $has_image ? 'display:flex;' : 'display:none;' ?>">
            <i class="fas fa-camera"></i>
          </div>

          <!-- REAL upload field -->
          <form method="post" enctype="multipart/form-data">
            <input type="file" name="profile_image_upload" id="profile_image_upload" class="hidden" accept="image/*">
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

<?php get_footer(); ?>