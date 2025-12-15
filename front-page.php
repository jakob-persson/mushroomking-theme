    <?php
    /**
     * The front page template file
     *
     * Displays the homepage content when a static page is selected.
     * Learn more: https://codex.wordpress.org/Template_Hierarchy
     */
    get_header();
    ?>
    <script>
      window.wpHuntId = <?= json_encode(get_query_var('hunt')); ?>;
    </script>



    <?php
    global $wpdb;

    // Total kilograms (for Total stat box)
    $total_kg = $wpdb->get_var("SELECT SUM(kilograms) FROM {$wpdb->prefix}mushrooms");
    $total_kg = $total_kg ? intval($total_kg) : 0;

    // Total kilograms (for Total stat box) — preserve decimals
    $total_kg = floatval( $wpdb->get_var("SELECT SUM(kilograms) FROM {$wpdb->prefix}mushrooms") );

    $best_day_kg = 0.0;

    $best_day_kg_query = $wpdb->get_var("
      SELECT SUM(kilograms)
      FROM {$wpdb->prefix}mushrooms
      WHERE start_date IS NOT NULL AND start_date != '' AND start_date != '0000-00-00'
      GROUP BY start_date
      ORDER BY SUM(kilograms) DESC
      LIMIT 1
    ");

    if ($best_day_kg_query !== null) {
        $best_day_kg = floatval($best_day_kg_query);
    }



    $total_rounds = $wpdb->get_var("
      SELECT COUNT(DISTINCT DATE(start_date))
      FROM {$wpdb->prefix}mushrooms
      WHERE start_date IS NOT NULL AND start_date != ''
    ");
    $total_rounds = $total_rounds ? intval($total_rounds) : 0;
    $total_rounds = $wpdb->get_var("
      SELECT COUNT(DISTINCT DATE(start_date))
      FROM {$wpdb->prefix}mushrooms
      WHERE start_date IS NOT NULL AND start_date != '0000-00-00'
    ");
    $total_rounds = $total_rounds ? intval($total_rounds) : 0;


    // Total unique locations
    $total_locations = $wpdb->get_var("
      SELECT COUNT(DISTINCT location)
      FROM {$wpdb->prefix}mushrooms
      WHERE location IS NOT NULL AND location != ''
    ");
    $total_locations = $total_locations ? intval($total_locations) : 0;

    // Get kilograms by mushroom type
    $mushroom_types = $wpdb->get_results("
      SELECT type, SUM(kilograms) as total_kg
      FROM {$wpdb->prefix}mushrooms
      GROUP BY type
      ORDER BY total_kg DESC
    ");

    $max_kg = 0;
    foreach ($mushroom_types as $m) {
      if ($m->total_kg > $max_kg) {
        $max_kg = $m->total_kg;
      }
    }

    $grouped_mushrooms = $wpdb->get_results("
      SELECT
        CASE
          WHEN start_date IS NULL OR start_date = '' OR start_date = '0000-00-00' THEN NULL
          ELSE DATE(start_date)
        END AS grouped_date,
        ANY_VALUE(location) AS location,
        GROUP_CONCAT(photo_url) AS photos,
        SUM(kilograms) AS total_kg
      FROM {$wpdb->prefix}mushrooms
      GROUP BY grouped_date
      ORDER BY grouped_date DESC
    ");
    ?>

    <?php
    $grouped_mushrooms = $wpdb->get_results("
      SELECT
        DATE(start_date) AS grouped_date,
        location,
        GROUP_CONCAT(photo_url) AS photos,
        SUM(kilograms) AS total_kg
      FROM {$wpdb->prefix}mushrooms
      WHERE 1=1
      -- WHERE start_date IS NOT NULL AND start_date != '' AND location IS NOT NULL AND location != ''
      GROUP BY grouped_date, location
      ORDER BY grouped_date DESC
    ");

    $hunts = array_map(function($entry) {
      $photos = array_filter(explode(',', $entry->photos));
      $username = $entry->display_name ?? 'Anonymous';
      return [
          'id'        => md5($entry->grouped_date . $entry->location),
          'photo'     => $photos[0] ?? null,
          'location'  => $entry->location ?? 'Unknown',
          'date'      => $entry->grouped_date,
          'timestamp' => $entry->grouped_date ? strtotime($entry->grouped_date) : time(),
          'username'  => $username,
          'user_id'   => $entry->user_id ?? 0,
          'total_kg'  => floatval($entry->total_kg ?? 0),
          'type'      => $entry->type ?? '{}',
          'types'     => $entry->types ?? '{}',
          'kilograms' => floatval($entry->total_kg ?? 0),
      ];
  }, $grouped_mushrooms);
    ?>


    <?php
    // Hjälpfunktion för att alltid hämta första bilden
    function get_first_photo($raw) {
        if (!$raw) return null;

        // Om det är en JSON-array
        if (str_starts_with($raw, '[')) {
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && !empty($decoded)) {
                return $decoded[0];
            }
        }

        // Om kommaseparerad lista
        $photos = array_filter(explode(',', $raw));
        return $photos[0] ?? null;
    }

    // Hämta de senaste tre bilderna
    $adventure_photos = $wpdb->get_results("
      SELECT photo_url, location
      FROM {$wpdb->prefix}mushrooms
      WHERE photo_url IS NOT NULL AND photo_url != ''
      ORDER BY start_date DESC
      LIMIT 3
    ");
    ?>

    <?php if ( is_user_logged_in() ) : ?>

    <?php
    global $wpdb;

    // fetch adventures
    $hunts = $wpdb->get_results("
        SELECT * FROM {$wpdb->prefix}mushrooms
        ORDER BY start_date DESC
    ");
    ?>

<section class="min-h-screen bg-[#EFF0EC] w-full">

<div class="section-wrapper grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-10 w-full mx-auto">

        <!-- LEFT COLUMN (USER PROFILE BOX) -->
    <div class="hidden lg:block col-span-12 lg:col-span-3 lg:sticky lg:top-0 h-fit pt-10">

        <?php 
            $current_user = wp_get_current_user();
            $avatar = mk_get_user_avatar($current_user->ID);
            $username = $current_user->display_name;

            // ✅ Fetch SAME description as user profile page
            $presentation = get_user_meta($current_user->ID, 'presentation', true);
        ?>

        <div class="bg-white rounded-3xl p-6 shadow-sm w-full">
            
            <!-- Avatar + Name -->
            <div class="flex items-center gap-4 mb-4">
                <img src="<?= esc_url($avatar); ?>" 
                    class="w-16 h-16 rounded-full object-cover shadow-md">

                <div>
                    <div class="text-lg font-semibold dark leading-tight">
                        <?= esc_html($username); ?>
                    </div>
                    <?php 
                    $country = get_user_meta($current_user->ID, 'country', true);
                    ?>

                    <div class="text-sm text-gray-500">
                        Forager<?= $country ? ' • ' . esc_html($country) : ''; ?>
                    </div>
                </div>
            </div>

            <!-- Description from Profile Page -->
            <div class="text-sm text-gray-700 leading-relaxed">
                <?= $presentation ? wp_kses_post($presentation) : 'No description added yet.'; ?>
            </div>

            <!-- Edit profile button -->
            <!-- <a href="<?= esc_url( home_url('/edit-profile/') ); ?>"
              class="inline-block mt-4 text-sm text-[#124C12] font-medium hover:underline">
                Edit profile
            </a> -->
        </div>

    </div>

    <!-- MIDDLE COLUMN (FEED) -->
    <div class="col-span-12 lg:col-span-6 overflow-y-scroll h-screen pt-10 px-4 lg:pl-6 lg:pr-4 space-y-8">

    <?php foreach ( $hunts as $hunt ) : ?>
        <?php 
            // Ensure integer user ID
            $user_id = intval($hunt->user_id);
            $user = get_user_by('id', $user_id);
            $username = $user ? $user->display_name : 'Unknown';
            $avatar = mk_get_user_avatar($user_id);

            // Get photos as array
            $photos = json_decode($hunt->photo_url, true);
            if (!$photos || !is_array($photos)) {
                $photos = [$hunt->photo_url ?: get_template_directory_uri() . "/images/placeholder.png"];
            }

            // Only use the first photo for the feed
            $first_photo = $photos[0];

            // Format weight
            $weight = rtrim(rtrim(number_format((float)$hunt->kilograms, 2, ',', ''), '0'), ',');
            
            // Prepare the object for the modal
            $hunt_object = [
                'id'             => intval($hunt->id),
                'photos'         => $photos, // send all photos for the modal
                'photo'          => $first_photo, // first photo for feed
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
            class="relative bg-white rounded-[30px] overflow-hidden shadow-sm cursor-pointer ml-[1px]"
            style="width: calc(100% - 11px); aspect-ratio: 1 / 1;"
            @click='$store.adventureModal.open(<?= json_encode($hunt_object, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'
        >
            <img src="<?= esc_url($first_photo); ?>" class="absolute inset-0 w-full h-full object-cover">

            <div class="absolute top-0 left-0 right-0 h-36 bg-gradient-to-b from-black/60 to-transparent"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-black/50 via-black/10 to-transparent"></div>

            <div class="absolute top-4 left-4 flex items-center gap-3 z-20">
                <img src="<?= esc_url($avatar); ?>" class="w-10 h-10 rounded-full shadow-md object-cover">
                <div class="text-white drop-shadow text-sm"><?= esc_html($username); ?></div>
            </div>

            <div class="absolute bottom-4 lg:bottom-6 left-6 right-6 rounded-[30px] pb-6 flex justify-between items-start z-30">
                <div class="max-w-[100%] flex flex-col">
                    <div class="text-white text-2xl gilroy" style="line-height:18px"><?= esc_html($hunt->location); ?></div>
                    <div class="text-white">
                        <span class="font-regular text-xs"><?= esc_html($hunt->start_date); ?> • <?= esc_html($weight); ?>kg</span>
                    </div>
                    <div class="text-white font-regular text-sm leading-snug mt-1"><?= esc_html(wp_trim_words($hunt->adventure_text, 15)); ?></div>
                </div>
            </div>
        </div>

    <?php endforeach; ?>

</div>


        <!-- RIGHT COLUMN (USER LIST) -->
           <div class="hidden lg:block lg:col-span-3 lg:sticky lg:top-0 h-fit pt-10">
            <h3 class="text-sm  mb-4">Explore foragers</h3>
            <div class="bg-white rounded-3xl p-4 space-y-4">
            <?php
            // Hämta alla användare utom den inloggade
            $current_user_id = get_current_user_id();
            $all_users = get_users([
                'orderby' => 'display_name',
                'order'   => 'ASC',
                'exclude' => [$current_user_id], // <-- Exclude logged-in user
            ]);
            ?>
            <div class="bg-white rounded-3xl p-4 space-y-4">
                <?php if (!empty($all_users)) : ?>
                    <ul class="space-y-3">
                        <?php foreach ($all_users as $user) :
                            $avatar = mk_get_user_avatar($user->ID);
                        ?>
                        <li class="flex items-center gap-3">
                            <img src="<?= esc_url($avatar); ?>" 
                                alt="<?= esc_attr($user->display_name); ?>" 
                                class="w-10 h-10 rounded-full object-cover shadow-sm">
                            <span class="text-sm font-medium"><?= esc_html($user->display_name); ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-gray-500 text-sm">No users found.</p>
                <?php endif; ?>
            </div>

            </div>
        </div>

    </div>
</section>

<?php endif; ?>




<?php if ( !is_user_logged_in() ) : ?>

<!-- Hero Section -->
<div class="bg-[#124C12] pb-12 overflow-hidden">
  <div class="section-wrapper flex flex-col lg:flex-row justify-center items-center gap-10 lg:min-h-[88vh]">

    <!-- Text Column -->
    <div class="flex-none lg:flex-1 text-left mb-4 flex flex-col justify-start pt-6 lg:pt-0">
      <h1 class="text-5xl lg:text-[82px] text-[#CEE027] gilroy leading-[54px] lg:leading-[82px] lg:w-full">
        Track, Share & Explore<br> Your Mushroom Season
      </h1>
      <p class="text-white text-lg max-w-lg my-2">
        Track all your foraging adventures, total harvest weight, best days, and locations — all in one place.
      </p>
      <?php if ( is_user_logged_in() ) :
        $current_user = wp_get_current_user();
        $profile_url = home_url( $current_user->user_nicename );
      ?>
      <a href="<?php echo esc_url($profile_url); ?>"
         class="mt-6 bg-[#E9C0E9] px-6 py-4 rounded-full font-medium text-[#1E1E1E] hover:opacity-90 w-auto self-start">
          View Profile
      </a>
      <?php else : ?>
      <a href="/mk/register"
         class="mt-6 bg-[#E9C0E9] px-6 py-4 rounded-full font-medium text-[#1E1E1E] hover:opacity-90 w-auto self-start">
          Create Account
      </a>
      <?php endif; ?>
    </div>

    <!-- Image Collage Column -->
    <div class="flex-none w-full lg:flex-1 flex justify-center items-center mt-8 lg:mt-0 px-4">
      <div class="relative w-full max-w-[400px] md:max-w-[560px] h-[400px] lg:h-[480px] [perspective:1200px] mx-auto
                  scale-[0.78]  lg:scale-100 origin-top">

        <!-- Collage wrapper -->
        <div class="relative w-full h-full [transform-style:preserve-3d] mx-auto">

          <?php if (!empty($adventure_photos[0])): ?>
            <?php $first_photo = get_first_photo($adventure_photos[0]->photo_url); ?>
            <!-- Top-right card -->
            <div class="absolute top-0 right-0 w-[240px] sm:w-[270px] md:w-[300px] lg:w-[310px]
                        h-[150px] sm:h-[170px] md:h-[190px] lg:h-[200px]
                        rounded-[36px] overflow-hidden
                        shadow-[0_40px_80px_rgba(0,0,0,0.15)]
                        transform-gpu
                        [transform-origin:18%_50%]
                        [transform:perspective(1400px)_translateZ(12px)_rotateY(0deg)_rotateZ(-4deg)_skewX(-4deg)]">
              <img src="<?= esc_url($first_photo); ?>"
                   alt="Adventure"
                   class="block w-full h-full object-cover">
              <div class="absolute bottom-3 right-3 bg-[#F9D9F9] text-[#111827] text-xs sm:text-sm px-3 py-1 rounded-full lowercase shadow-md">
                #<?= esc_html($adventure_photos[0]->location ?? 'unknown') ?>
              </div>
            </div>
          <?php endif; ?>

          <?php if (!empty($adventure_photos[1])): ?>
            <?php $first_photo = get_first_photo($adventure_photos[1]->photo_url); ?>
            <!-- Middle-left card -->
            <div class="z-[3] absolute left-0 top-28 w-[240px] sm:w-[270px] md:w-[300px] lg:w-[310px]
                        h-[150px] sm:h-[170px] md:h-[190px] lg:h-[200px]
                        rounded-[36px] overflow-hidden
                        shadow-[0_40px_80px_rgba(0,0,0,0.25)]
                        transform-gpu
                        [transform-origin:18%_50%]
                        [transform:perspective(1400px)_translateZ(12px)_rotateY(0deg)_rotateZ(-4deg)_skewX(-4deg)]">
              <img src="<?= esc_url($first_photo); ?>"
                   alt="Adventure"
                   class="block w-full h-full object-cover">
              <div class="absolute bottom-3 left-3 bg-[#F9D9F9] text-[#111827] text-xs sm:text-sm px-3 py-1 rounded-full lowercase shadow-md">
                #<?= esc_html($adventure_photos[1]->location ?? 'unknown') ?>
              </div>
            </div>
          <?php endif; ?>

          <?php if (!empty($adventure_photos[2])): ?>
            <?php $first_photo = get_first_photo($adventure_photos[2]->photo_url); ?>
            <!-- Bottom-right card -->
            <div class="absolute bottom-4 right-0 w-[240px] sm:w-[270px] md:w-[300px] lg:w-[310px]
                        h-[150px] sm:h-[170px] md:h-[190px] lg:h-[200px]
                        rounded-[36px] overflow-hidden
                        shadow-[0_40px_80px_rgba(0,0,0,0.2)]
                        transform-gpu
                        [transform-origin:18%_50%]
                        [transform:perspective(1400px)_translateZ(12px)_rotateY(0deg)_rotateZ(-4deg)_skewX(-4deg)]">
              <img src="<?= esc_url($first_photo); ?>"
                   alt="Adventure"
                   class="block w-full h-full object-cover">
              <div class="absolute bottom-3 left-3 bg-[#F9D9F9] text-[#111827] text-xs sm:text-sm px-3 py-1 rounded-full lowercase shadow-md">
                #<?= esc_html($adventure_photos[2]->location ?? 'unknown') ?>
              </div>
            </div>
          <?php endif; ?>

        </div>
      </div>
    </div>

  </div>
</div>

<?php endif; ?>



<?php if ( !is_user_logged_in() ) : ?>
<section class="bg-[#F5F5F3] py-20">
  <div class="section-wrapper mx-auto grid lg:grid-cols-2 gap-12 items-center">

    <!-- left -->
    <div class="flex flex-col">
    <h1 class="text-5xl lg:text-[82px] dark gilroy leading-[54px] lg:leading-[82px] lg:w-full">
        How to get<br>started<br>in 3 simple<br> steps
      </h1>
      <p class="text-[#1E2330]/70 text-lg max-w-md my-2">
      Join, log, and explore — your season starts with a free account.
      </p>
      <a href="mk/register" class="mt-6 bg-[#1E2330] px-6 py-4 rounded-full font-medium text-white hover:opacity-90 w-auto self-start">
        Create account
      </a>
    </div>

<!-- Right -->
<div class="flex flex-col sm:flex-row gap-6 justify-center">
  <!-- card 1 -->
  <div class="bg-[#DAB6E4] rounded-2xl p-8 w-full sm:w-1/3 flex flex-col items-start">
    <div class="bg-white text-[#1E2330] font-bold w-10 h-10 flex items-center justify-center rounded-full mb-4">
      1
    </div>
    <h3 class="font-semibold text-lg mb-2 leading-[24px]">Create a free account</h3>
    <p class="text-[#1E1E1E]/70 text-sm">
      To get started, simply create your free account to begin your foraging journey.
    </p>
  </div>

  <!-- card 2 -->
  <div class="bg-[#DAB6E4] rounded-2xl p-8 w-full sm:w-1/3 flex flex-col items-start">
    <div class="bg-white text-[#1E1E1E] font-bold w-10 h-10 flex items-center justify-center rounded-full mb-4">
      2
    </div>
    <h3 class="font-semibold text-lg mb-2 leading-[24px]">Start adding adventures</h3>
    <p class="text-[#1E1E1E]/70 text-sm">
      Log your foraging trips, add photos, locations, and notes about your mushroom finds.
    </p>
  </div>

  <!-- card 3 -->
  <div class="bg-[#DAB6E4] rounded-2xl p-8 w-full sm:w-1/3 flex flex-col items-start">
    <div class="bg-white text-[#1E1E1E] font-bold w-10 h-10 flex items-center justify-center rounded-full mb-4">
      3
    </div>
    <h3 class="font-semibold text-lg mb-2 leading-[24px]">Explore your stats</h3>
    <p class="text-[#1E1E1E]/70 text-sm">
      See insights from your season — top harvests, total weight, and your favorite locations.
    </p>
  </div>
</div>

  </div>
</section>
<?php endif; ?>

<?php if ( is_user_logged_in() ) : ?>
    <?php
    // Load reusable Add Adventure modal
    get_template_part('partials/modal', 'add-adventure');
    ?>
      </div>
    </div>

    <?php endif; ?>
    <?php get_template_part('partials/modal-summary-adventure');
 ?>
 <?php get_template_part('partials/modal', 'adventure'); ?>
<?php get_footer(); ?>
