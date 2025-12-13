  <?php
  /**
   * Template Name: User Adventures
   */

  get_header();
  global $wpdb;

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

    $hunts = array_map(function($entry) {
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
        'Funnel Chanterelles' => get_template_directory_uri() . '/images/funnel.jpeg',
        'Boletus' => get_template_directory_uri() . '/images/bolete.jpeg',
        'Trumpets' => get_template_directory_uri() . '/images/trumpet.jpeg',
    ];

    ?>
  <!-- Hero Section -->
  <div class="bg-[#F3F3F1] px-6 lg:px-16 pb-12">

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
  $country_data = $country_codes[$country] ?? ['code' => strtoupper(substr($country,0,3)), 'flag' => '🏳️'];
  $country_code = $country_data['code'];
  $country_flag = $country_data['flag'];
?>

<!-- Make stiicky scrolling whole page inn sam posiition -->
<div
    x-data="{ isSticky: false }"
    x-init="window.addEventListener('scroll', () => { isSticky = window.scrollY > 200 })"
    class="relative"
    style="z-index:9"
>
    <div
        :class="isSticky ? 'lg:px-6 fixed top-[100px] lg:top-[130px] transition-all duration-300 w-full' : ''"
        class="lg:px-6 flex justify-between items-center mt-[0px] flex-wrap gap-2 transition-all duration-300 max-w-[1280px] mx-auto"
    >
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

        <!-- Edit Profile Button -->
        <?php if (is_user_logged_in() && get_current_user_id() === $user->ID): ?>
            <a href="<?= home_url('/edit-profile/') ?>" class="px-6 py-3 bg-purple-600 text-white rounded hover:bg-purple-700 transition whitespace-nowrap">
                Edit Profile
            </a>
        <?php endif; ?>
    </div>

    <!-- Spacer to prevent layout shift when sticky -->
    <div :class="isSticky ? 'h-[72px] lg:h-[88px]' : ''"></div>
</div>
    <!-- Image Column -->
    <div class="flex-none lg:flex-1 flex justify-center items-center mt-4 lg:mt-0">
      <div id="tilt-container" class="relative perspective-[1000px] mt-[0px]">

        <?php
        // Prefer a custom user meta image, then WP avatar, then theme fallback
        $profile_img_url = '';
        $user_id = $user->ID;

        $meta = get_user_meta($user_id, 'profile_image', true);

        if (!empty($meta)) {
            if (is_numeric($meta)) {
                $candidate = wp_get_attachment_image_url((int) $meta, 'large');
                if ($candidate) $profile_img_url = $candidate;
            } elseif (is_array($meta) && !empty($meta['url'])) {
                $profile_img_url = $meta['url'];
            } elseif (filter_var($meta, FILTER_VALIDATE_URL)) {
                $profile_img_url = $meta;
            }
        }

        if (!$profile_img_url) {
            $avatar = get_avatar_url($user_id, ['size' => 512]);
            if ($avatar) $profile_img_url = $avatar;
        }

        if (!$profile_img_url) {
            $profile_img_url = get_template_directory_uri() . '/images/main-screen.png';
        }

        // Example country code (could come from user meta, taxonomy, etc.)
        $country_code = get_user_meta($user_id, 'country_code', true) ?: 'SWE';
        ?>

        <!-- Profile Image -->
        <img src="<?= esc_url($profile_img_url); ?>"
            alt="<?= esc_attr($user->display_name); ?>'s profile picture"
            class="w-[720px] h-[400px] object-cover rounded-[150px] drop-shadow-xl transition-transform duration-150 ease-out">

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
<div class="flex flex-col lg:flex-row justify-center items-start pt-8 gap-10">
  <div class="text-center px-6 mb-4 flex flex-col justify-start items-center pt-6 lg:pt-0 max-w-4xl mx-auto">
    <h1 class="text-5xl lg:text-[82px] dark gilroy leading-[54px] lg:leading-[82px]">
      <span class="text-[#2665D6]">
        <?= esc_html($user->display_name); ?>
      </span>
    </h1>

    <!-- ✅ Presentation text here -->
    <p class="dark text-lg my-2 max-w-lg presentation-text">
      <?= wp_kses_post(get_user_meta($user->ID, 'presentation', true)); ?>
    </p>

    <button class="mt-6 bg-[#E9C0E9] px-6 py-4 rounded-full font-medium text-[#1E1E1E] hover:opacity-90">
      View summary
    </button>
  </div>
</div>

  </div>


    <!-- Adventures Image Slider with Portrait-Style Boxes -->
    <div class="text-|#E9EED8] bg-[#1E2330] lg:h-[100vh] flex flex-col h-full" style="padding-top: 78px;">
        <div class="flex justify-center w-full py-16 lg:px-16 px-6">
            <h1 class="lg:text-center text-5xl text-white lg:text-6xl  gilroy leading-[48px] lg:leading-[62px] lg:w-[60%]">
                Latest mushroom <span class="text-[#2665D6]">adventures</span>
            </h1>
        </div>

        <div id="adventure-slider" class="flex overflow-x-auto no-scrollbar space-x-4 snap-x snap-mandatory ml-6 lg:ml-16">

        <?php
        global $wpdb;

        // Use the user ID for the page being viewed
        $current_user_id = isset($user) ? $user->ID : get_current_user_id();

        $adventure_photos = $wpdb->get_results($wpdb->prepare("
            SELECT photo_url, location, start_date
            FROM {$wpdb->prefix}mushrooms
            WHERE user_id = %d
              AND photo_url IS NOT NULL AND photo_url != ''
              AND start_date IS NOT NULL AND start_date != '0000-00-00'
            ORDER BY start_date DESC
            LIMIT 10
        ", $current_user_id));

        $shape_styles = [
            ['class' => 'rounded-[20px]', 'size' => 'w-[306px] h-[220px] lg:w-[426px] lg:h-[340px]'],
            ['class' => 'rounded-[52px]', 'size' => 'w-[140px] h-[220px] lg:w-[260px] lg:h-[340px]']
        ];

        foreach ($adventure_photos as $index => $photo) :
            $style = $shape_styles[$index % count($shape_styles)];
            $class = $style['class'];
            $size = $style['size'];
            $location = !empty($photo->location) ? esc_html($photo->location) : 'Unknown';
        ?>
            <div class="relative flex-shrink-0 <?= esc_attr("$size $class"); ?> overflow-hidden snap-start">
                <img src="<?= esc_url($photo->photo_url); ?>" class="w-full h-full object-cover transition-transform duration-300 hover:scale-105" alt="Adventure Photo">

                <!-- Location Tag -->
                <div class="absolute bottom-4 left-4 bg-white text-[#111827] text-sm px-3 py-2 rounded-full lowercase">
                    #<?= $location ?>
                </div>
            </div>
        <?php endforeach; ?>

        </div>
    </div>


      <!-- divider -->
      <div class=" bg-[#1E2330] flex" style="padding-top: 148px; "></div>
      <!-- diviider ends -->


    <!-- ======================
        Overview Stats Section
    ====================== -->
    <div class="px-6 py-12 bg-[#e8efd6] lg:h-[100vh] flex justify-center" style="padding-top: 128px; padding-bottom: 128px;">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center">

        <!-- Left: Stat Boxes -->
        <div class="order-2 md:order-1 space-y-4 mb-6 lg:px-20">
          <div class="flex space-x-4">
            <div class="bg-[#6E5F40] text-white p-6 rounded-[20px] flex-[2]">
              <div class="flex justify-between items-center">
                <img src="<?= get_template_directory_uri(); ?>/images/basket.svg" class="w-12">
                <div class="flex flex-col items-center gap-2">
                  <div class="text-sm font-medium opacity-80">Total</div>
                  <div class="text-3xl font-bold"><?= rtrim(rtrim(number_format($total_kg,2,'.',''),'0'),'.') ?></div>
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
                  <div class="text-3xl font-bold"><?= rtrim(rtrim(number_format($best_day_kg,2,'.',''),'0'),'.') ?></div>
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

    <!-- ======================
        Mushroom Type Stats Section
    ====================== -->
    <div class="px-6 py-12 bg-[#1E2330] lg:h-[100vh] flex justify-center items-center pt-[128px] lg:pt-[478px] pb-[128px] lg:pb-[478px]" style="padding-bottom: 478px;">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center">

        <div class="space-y-8 mb-6 lg:px-20">
          <h1 class="font-bold gilroy text-white lg:text-6xl leading-[62px] text-5xl">Your foraging adventures sorted by mushroom<br>type</h1>
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
                    <?= rtrim(rtrim(number_format($m->total_kg,2,'.',''),'0'),'.') ?>kg
                  </div>
                </div>
              <?php endforeach; ?>
            </div>

          </div>
        </div>

      </div>
    </div>
    <?php
get_template_part('partials/modal', 'add-adventure');
?>

    <?php get_footer(); ?>
