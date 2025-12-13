    <?php
    /**
     * The front page template file
     *
     * Displays the homepage content when a static page is selected.
     * Learn more: https://codex.wordpress.org/Template_Hierarchy
     */
    get_header();
    ?>
    <?php


    global $wpdb;

    // Total kilograms (for Total stat box)
    $total_kg = $wpdb->get_var("SELECT SUM(kilograms) FROM {$wpdb->prefix}mushrooms");
    $total_kg = $total_kg ? intval($total_kg) : 0;

    // Total kilograms (for Total stat box) — preserve decimals
    $total_kg = floatval( $wpdb->get_var("SELECT SUM(kilograms) FROM {$wpdb->prefix}mushrooms") );

    // Best day (most kilograms on a single start_date) — preserve decimals
    $best_day_kg = floatval( $wpdb->get_var("
      SELECT SUM(kilograms)
      FROM {$wpdb->prefix}mushrooms
      WHERE start_date IS NOT NULL AND start_date != ''
      GROUP BY start_date
      ORDER BY SUM(kilograms) DESC
      LIMIT 1
    ") );


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
      return [
        'location' => $entry->location ?? 'Unknown',
        'date' => $entry->grouped_date,
        'timestamp' => $entry->grouped_date ? strtotime($entry->grouped_date) : time(),
        'total_kg' => floatval($entry->total_kg),
        'photo' => $photos[0] ?? null,
      ];
    }, $grouped_mushrooms);
    ?>

    <!-- Hero Section -->
<div class="bg-[#124C12] lg:px-16 pb-12">
  <!-- Layout wrapper -->
  <div class="flex flex-col lg:flex-row justify-start lg:justify-center items-start lg:items-center gap-10 lg:min-h-[88vh]">

    <!-- Text Column -->
    <div class="flex-none lg:flex-1 text-left px-6 mb-4 flex flex-col justify-start pt-6 lg:pt-0">
      <h1 class="text-5xl lg:text-[82px] text-[#CEE027] gilroy leading-[54px] lg:leading-[82px] lg:w-full">
      Track, Share & Explore Your Mushroom Season
      </h1>
      <p class="text-white text-lg max-w-lg my-2">
        Track all your foraging adventures, total harvest weight, best days, and locations — all in one place.
      </p>
      <a href="mk/register" class="mt-6 bg-[#E9C0E9] px-6 py-4 rounded-full font-medium text-[#1E1E1E] hover:opacity-90 w-auto self-start">
        Create account
      </a>
    </div>

          <!-- Image Collage Column -->
          <div class="flex-none lg:flex-1 flex justify-center items-center mt-4 lg:mt-0 hidden lg:flex">
            <div class="relative w-full max-w-lg h-[500px] [perspective:1200px]">

              <?php
              global $wpdb;
              $adventure_photos = $wpdb->get_results("
                SELECT photo_url, location
                FROM {$wpdb->prefix}mushrooms
                WHERE photo_url IS NOT NULL AND photo_url != ''
                ORDER BY start_date DESC
                LIMIT 3
              ");
              ?>
              <!-- DEEP / LEANING single image card (drop-in) -->
              <div class="relative w-full max-w-[760px] h-[520px] [perspective:1400px] [transform-style:preserve-3d]">

<?php if (!empty($adventure_photos[0])): ?>
  <!-- Top-right card -->
  <div
    class="absolute top-0 right-0 w-[260px] h-[160px] lg:w-[310px] lg:h-[200px]
           rounded-[48px] overflow-hidden
           shadow-[0_40px_80px_rgba(0,0,0,0.15)]
           transform-gpu
           [transform-origin:18%_50%]
           [transform:perspective(1400px)_translateZ(12px)_rotateY(0deg)_rotateZ(-4deg)_skewX(-4deg)]">
    <img src="<?= esc_url($adventure_photos[0]->photo_url); ?>"
         alt="Adventure"
         class="block w-[120%] h-[120%] object-cover
                [transform:translateX(-2%)_translateY(-2%)_scale(1)]">
    <div class="absolute bottom-4 right-4 bg-[#F9D9F9] text-[#111827] text-sm px-3 py-1 rounded-full lowercase shadow-md transform rotate-[1deg]">
      #<?= esc_html($adventure_photos[0]->location ?? 'unknown') ?>
    </div>
  </div>
<?php endif; ?>

<?php if (!empty($adventure_photos[1])): ?>
  <!-- Middle-left card (front) -->
  <div
    class="z-[3] absolute left-0 top-32 w-[260px] h-[160px] lg:w-[310px] lg:h-[200px]
           rounded-[48px] overflow-hidden
           shadow-[0_40px_80px_rgba(0,0,0,0.25)]
           transform-gpu
           [transform-origin:18%_50%]
           [transform:perspective(1400px)_translateZ(12px)_rotateY(0deg)_rotateZ(-4deg)_skewX(-4deg)]">
    <img src="<?= esc_url($adventure_photos[1]->photo_url); ?>"
         alt="Adventure"
         class="block w-[120%] h-[120%] object-cover
                [transform:translateX(-1%)_translateY(-0%)_scale(1)]">
    <div class="absolute bottom-4 left-4 bg-[#F9D9F9] text-[#111827] text-sm px-3 py-1 rounded-full lowercase shadow-md transform rotate-[-1deg]">
      #<?= esc_html($adventure_photos[1]->location ?? 'unknown') ?>
    </div>
  </div>
<?php endif; ?>

<?php if (!empty($adventure_photos[2])): ?>
  <!-- Bottom-right card -->
  <div
    class="absolute bottom-8 right-0 w-[260px] h-[160px] lg:w-[310px] lg:h-[200px]
           rounded-[48px] overflow-hidden
           shadow-[0_40px_80px_rgba(0,0,0,0.2)]
           transform-gpu
           [transform-origin:18%_50%]
           [transform:perspective(1400px)_translateZ(12px)_rotateY(0deg)_rotateZ(-4deg)_skewX(-4deg)]">
    <img src="<?= esc_url($adventure_photos[2]->photo_url); ?>"
         alt="Adventure"
         class="block w-[120%] h-[120%] object-cover
                [transform:translateX(-2%)_translateY(-1%)_scale(1)]">
    <div class="absolute bottom-4 left-4 bg-[#F9D9F9] text-[#111827] text-sm px-3 py-1 rounded-full lowercase shadow-md transform rotate-[0deg]">
      #<?= esc_html($adventure_photos[2]->location ?? 'unknown') ?>
    </div>
  </div>
<?php endif; ?>

</div>
            </div>
          </div>

  </div>
</div>


    <!-- Adventures Image Slider with Portrait-Style Boxes -->
    <div class="text-[#E9EED8] bg-[#F3F3F1] min-h-screen flex flex-col" style="padding-top: 78px;">

      <div class="flex justify-center w-full  py-16 lg:px-16 px-6">
      <h1 class="lg:text-center text-5xl lg:text-6xl dark gilroy leading-[48px] lg:leading-[62px] lg:w-[60%]">
        Latest mushroom <span class="text-[#2665D6]">adventures</span>
      </h1>
    </div>


        <div id="adventure-slider" class="flex overflow-x-auto no-scrollbar space-x-4 snap-x snap-mandatory ml-6 lg:ml-16">
        <?php
        $adventure_photos = $wpdb->get_results("
        SELECT m.photo_url, m.location, m.start_date, u.display_name
        FROM {$wpdb->prefix}mushrooms m
        INNER JOIN {$wpdb->prefix}users u ON m.user_id = u.ID
        WHERE m.photo_url IS NOT NULL AND m.photo_url != ''
          AND m.start_date IS NOT NULL AND m.start_date != '0000-00-00'
        ORDER BY m.start_date DESC
        LIMIT 10
      ");
        ?>

        <?php
          $shape_styles = [
            ['class' => 'rounded-[20px]', 'size' => 'w-[306px] h-[220px] lg:w-[426px] lg:h-[340px]'],
            ['class' => 'rounded-[52px]', 'size' => 'w-[140px] h-[220px] lg:w-[260px] lg:h-[340px]']
          ];



          foreach ($adventure_photos as $index => $photo) :
            $style = $shape_styles[$index % count($shape_styles)];
            $class = $style['class']; // contains rounded corner class
            $size = $style['size'];
            $location = !empty($photo->location) ? esc_html($photo->location) : 'Unknown';
            $username = !empty($photo->display_name) ? esc_html($photo->display_name) : 'Anonymous';
            $slug = strtolower(str_replace(' ', '-', $username));
            $profile_url = home_url("/$slug");
          ?>
            <div class="relative flex-shrink-0 <?= esc_attr("$size"); ?> snap-start group [perspective:1000px]">
              <div class="relative w-full h-full transition-transform duration-700 [transform-style:preserve-3d] group-hover:[transform:rotateY(180deg)] <?= esc_attr($class); ?>">

                <!-- FRONT (Image + tag) -->
                <div class="absolute inset-0 overflow-hidden [backface-visibility:hidden] <?= esc_attr($class); ?>">
                  <img src="<?= esc_url($photo->photo_url); ?>"
                       class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105 <?= esc_attr($class); ?>"
                       alt="Adventure Photo">
                  <div class="absolute bottom-4 left-4 bg-white text-[#111827] text-sm px-3 py-2 rounded-full lowercase shadow">
                    #<?= $username ?>
                  </div>
                </div>
                <!-- BACK (Profile Link) -->
               <div class="absolute inset-0 flex items-center justify-center bg-[#1E2330] text-white text-center p-4 [transform:rotateY(180deg)] [backface-visibility:hidden] <?= esc_attr($class); ?>">
               <a href="<?= esc_url($profile_url); ?>"
                  class="flex flex-row items-center justify-center gap-1 px-6 py-3
                          bg-[#fff] text-[#1E2330] text-xs rounded-full font-regular
                          hover:bg-[#E9C0E9] hover:text-white transition">
                  <i class="fas fa-link text-sm"></i>
                  <span><?= $username ?>’s profile</span>
                </a>
                </div>

              </div>
            </div>
          <?php endforeach; ?>
      </div>
    </div>

    <!-- divider -->
    <div class="hidden lg:flex bg-[#F3F3F1] flex" style="padding-top: 148px; "></div>
    <!-- diviider ends -->


    <div class="px-6 py-12 bg-[#e8efd6] lg:h-[100vh] flex justify-center" style="padding-top: 128px;
        padding-bottom: 128px;">
    <!-- Stats + Text Section -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center">
      <div class="order-2 md:order-1 space-y-4 mb-6 lg:px-20">
        <!-- First Row -->
        <div class="flex space-x-4">
        <!-- Large Total Box -->
    <div class="bg-[#6E5F40] text-white p-6 rounded-[20px] flex-[2]">
        <div class="flex justify-between items-center">
            <img src="<?= get_template_directory_uri(); ?>/images/basket.svg" class="w-12">
            <div class="flex flex-col items-center gap-2"> <!-- added flex + gap -->
                <div class="text-sm font-medium opacity-80">Total</div>
                <div class="text-3xl font-bold">
                    <?= rtrim(rtrim(number_format($total_kg, 2, '.', ''), '0'), '.') ?>
                </div>
                <div class="text-sm opacity-80">Kilogram</div>
            </div>
        </div>
    </div>


            <!-- Small Rounds Box -->
            <div class="bg-[#E9C0E9] text-black p-6 rounded-[20px] flex-1 text-center ">
                <div class="flex flex-col gap-1">
                    <img class="w-12 mx-auto mb-2" src="<?= get_template_directory_uri(); ?>/images/mountain.svg">
                    <div class="text-3xl font-bold"><?= $total_rounds ?></div>
                    <div class="text-sm font-medium opacity-80">Adventures</div>
                </div>
            </div>
        </div>

        <!-- Second Row -->
        <div class="flex space-x-4">
            <!-- Small Locations Box -->
            <div class="bg-[#C637DF] text-white p-6 rounded-[20px] flex-1 text-center">
                <img class="w-12 mx-auto mb-2" src="<?= get_template_directory_uri(); ?>/images/locations.svg">
                <div class="text-3xl font-bold"><?= $total_locations ?></div>
                <div class="text-sm font-medium opacity-80">Locations</div>
            </div>

            <!-- Large Best of the Day Box -->
            <?php
            $best_day_kg = 0;
            if (!empty($hunts)) {
                $sorted_hunts = $hunts;
                usort($sorted_hunts, function($a, $b) {
                    return $b['total_kg'] <=> $a['total_kg'];
                });
                $best_day_kg = $sorted_hunts[0]['total_kg'];
            }
            ?>
          <div class="bg-[#D2E823] text-white p-6 rounded-[20px] flex-[2]">
        <div class="flex justify-between items-center">
            <img src="<?= get_template_directory_uri(); ?>/images/trophy3.svg" class="w-12">
            <div class="flex flex-col items-center justify-center gap-2 dark"> <!-- added flex + gap -->
                <div class="text-sm font-medium opacity-80">Best of the day</div>
                <div class="text-3xl font-bold">
                    <?= rtrim(rtrim(number_format($best_day_kg, 2, '.', ''), '0'), '.') ?>
                </div>
                <div class="text-sm opacity-80">Kilogram</div>
            </div>
        </div>
    </div>
        </div>
    </div>


        <!-- Right Side: Text Block -->
        <div class="order-1 md:order-2 lg:px-16 lg:py-16">
        <h1 class="font-bold gilroy dark lg:text-6xl dark gilroy leading-[52px] lg:leading-[62px] text-5xl ">
        Mushroom Season 2025 – Community Overview
            </h1>
            <p class="mt-4 dark text-base leading-relaxed text-white">
            See the total harvest, best spots, and most memorable adventures from mushroom pickers everywhere. Discover when the biggest hauls happened and celebrate the season together.s
            </p>
            <button class="bg-[#E9C0E9] mt-6 px-6 py-4 rounded-full font-medium text-[#1E1E1E] hover:opacity-90">
              View all stats
            </button>
        </div>

    </div>
    <!-- Stats Section ends -->

    </div>
    <?php
    // Load reusable Add Adventure modal
    get_template_part('partials/modal', 'add-adventure');
    ?>


      </div>
    </div>

    <?php
    $results = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}mushrooms");

    $totals_by_type = [];
    $max_kg = 0;

    foreach ($results as $row) {
        // Use 'types' if it exists, otherwise fall back to 'type'
        $raw = isset($row->types) ? $row->types : (isset($row->type) ? $row->type : '');

        // Try decoding as JSON
        $types = json_decode($raw, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($types)) {
            // New format: associative array: type => kg
            foreach ($types as $type => $kg) {
                if (!isset($totals_by_type[$type])) {
                    $totals_by_type[$type] = 0;
                }
                $totals_by_type[$type] += floatval($kg);
                if ($totals_by_type[$type] > $max_kg) {
                    $max_kg = $totals_by_type[$type];
                }
            }
        } elseif (is_string($raw) && strpos($raw, ':') !== false) {
            // Old format: "Shiitake:2, Oyster:3"
            $pairs = explode(',', $raw);
            foreach ($pairs as $pair) {
                list($type, $kg) = array_map('trim', explode(':', $pair));
                if (!isset($totals_by_type[$type])) {
                    $totals_by_type[$type] = 0;
                }
                $totals_by_type[$type] += floatval($kg);
                if ($totals_by_type[$type] > $max_kg) {
                    $max_kg = $totals_by_type[$type];
                }
            }
        }
    }

    // Convert to array of objects for your HTML loop
    $mushroom_types = [];
    foreach ($totals_by_type as $type => $total_kg) {
        $mushroom_types[] = (object) [
            'type' => $type,
            'total_kg' => $total_kg
        ];
    }
    ?>

    <!-- Mushroom Type Stats -->
    <div class="px-6 py-12 bg-[#1E2330] lg:h-[100vh] flex justify-center items-center pt-[128px] lg:pt-[478px] pb-[128px] lg:pb-[478px]" style="
        padding-bottom: 478px;">
    <!-- Stats + Text Section -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center">
    <div class="space-y-8 mb-6 lg:px-20">
      <h1 class="font-bold gilroy text-white lg:text-6xl  gilroy leading-[52px] lg:leading-[62px] text-5xl ">Mushroom adventures sorted by type</h1>
      <p class="text-white text-base leading-relaxed text-white">
      Discover which mushrooms took center stage this season. Explore a full breakdown of the total harvest by type, gathered by mushroom foragers from across the entire community. See which varieties dominated the forests and fields, and how much was collected throughout the season.
            </p>
            <button class="bg-[#E9C0E9] px-6 py-4 rounded-full font-medium text-[#1E1E1E] hover:opacity-90">
              View all stats
            </button>
    </div>

    <div class="lg:px-16 lg:py-16 lg:px-20">
    <!-- Stats + Text Section -->
    <div class="bg-white rounded-[30px] p-8 w-full max-w-2xl">
      <!-- Heading -->
      <h2 class="text-sm font-bold mb-6">By mushroom type</h2>

      <!-- Table Header -->
      <div class="flex items-center justify-between text-xs font-regular mb-4">
        <span>Type / Sort</span>
        <span class="text-right" style="margin-right: 18px">Weight</span>
      </div>

      <?php
      // 🔑 Add the mapping here (before the foreach loop)
      $mushroom_icons = [
        'Chanterelles' => get_template_directory_uri() . '/images/chant.jpeg',
        'Funnel Chanterelles' => get_template_directory_uri() . '/images/funnel.jpeg',
        'Boletus' => get_template_directory_uri() . '/images/bolete.jpeg',
        'Trumpets' => get_template_directory_uri() . '/images/trumpet.jpeg',
      ];
      ?>

      <!-- List -->
      <div class="space-y-4">
        <?php foreach ($mushroom_types as $m):
          // pick correct icon or fallback
          $icon = isset($mushroom_icons[$m->type])
            ? $mushroom_icons[$m->type]
            : get_template_directory_uri() . '/assets/img/mushrooms/default.jpg';
        ?>
          <div class="flex items-center justify-between bg-[#F0F2EF] rounded-2xl p-4">

            <!-- Icon + Sort -->
            <div class="flex items-center space-x-3">
              <img
                src="<?= esc_url($icon) ?>"
                alt="<?= esc_attr($m->type) ?> icon"
                class="w-12 h-12 rounded-full object-cover"
              >
              <span class="font-regular text-sm text-[#1E2330]">
                <?= esc_html($m->type) ?>
              </span>
            </div>

            <!-- Weight -->
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
    <!-- stats ends -->

    <div
      x-data="(() => {
      const allHunts = <?= htmlspecialchars(json_encode($hunts), ENT_QUOTES, 'UTF-8') ?>;
      return {
        filter: 'all',
        hunts: allHunts,
        visibleCount: 10,

        get filteredHunts() {
          const now = Date.now() / 1000;
          let result = [];

          if (this.filter === 'top10') {
            result = this.hunts
              .slice()
              .sort((a, b) => b.total_kg - a.total_kg)
              .slice(0, 10);
          } else if (this.filter === 'recent') {
            result = this.hunts.filter(h => h.timestamp >= now - 7 * 24 * 3600);
          } else if (this.filter === 'heavy') {
            result = this.hunts.filter(h => h.total_kg > 30);
          } else {
            result = this.hunts;
          }

          return result.slice(0, this.visibleCount);
        },

        loadMore() {
          this.visibleCount += 10;
        },

        get hasMore() {
          return this.filteredHunts.length < this.getAllFiltered().length;
        },

        getAllFiltered() {
          const now = Date.now() / 1000;

          if (this.filter === 'top10') {
            return this.hunts
              .slice()
              .sort((a, b) => b.total_kg - a.total_kg)
              .slice(0, 10);
          } else if (this.filter === 'recent') {
            return this.hunts.filter(h => h.timestamp >= now - 7 * 24 * 3600);
          } else if (this.filter === 'heavy') {
            return this.hunts.filter(h => h.total_kg > 30);
          } else {
            return this.hunts;
          }
        }
      };
    })()"

      class="flex flex-col px-6 py-12 bg-[#F3F3F1] lg:px-16 items-center  h-auto inline-block"
    >

      <h1 class="font-bold gilroy dark lg:text-6xl gilroy leading-[52px] lg:leading-[62px] text-5xl">Latest Mushroom Hunts</h1>

      <!-- Filters -->
      <div class="flex overflow-x-auto no-scrollbar space-x-2 mt-6 mb-2">
        <template x-for="btn in ['all', 'top10', 'recent', 'heavy']" :key="btn">
          <button
            :class="filter === btn ? 'bg-[#CEE027] text-[#111827]' : 'bg-white border-[#F4F4F1] border text-[#111827]'"
            class="flex-shrink-0 px-4 py-2 rounded-full text-sm font-regular whitespace-nowrap"
            @click="filter = btn"
            x-text="btn === 'all' ? 'All' : btn === 'top10' ? 'Top 10' : btn === 'recent' ? 'Last 7 Days' : 'Over 30kg'"
          ></button>
        </template>
      </div>

      <!-- Hunt List -->
      <div class="space-y-4 w-full lg:px-64">
        <template x-for="hunt in filteredHunts" :key="hunt.timestamp + hunt.location">
          <div class="flex items-center justify-between bg-white border border-[#F4F4F1] rounded-2xl p-4">
            <div class="flex items-center gap-4">
              <template x-if="hunt.photo">
                <img :src="hunt.photo" alt="Mushroom Photo" class="w-12 h-12 rounded-md object-cover" />
              </template>
              <template x-if="!hunt.photo">
                <div class="w-12 h-12 bg-gray-200 rounded-md flex items-center justify-center text-gray-500">🍄</div>
              </template>
              <div class="text-sm font-regular flex flex-col">
                <div><strong x-text="hunt.location"></strong></div>
                <div x-text="new Date(hunt.timestamp * 1000).toLocaleDateString()"></div>
              </div>
            </div>
            <div class="text-xs text-gray-500" x-text="hunt.total_kg + ' kg total'"></div>
          </div>
        </template>

        <template x-if="filteredHunts.length === 0">
          <div class="text-center text-gray-400 text-sm mt-4">No hunts match this filter.</div>
        </template>
      </div>
        <!-- Load More Button -->
        <div x-show="hasMore" class="text-center mt-6">
          <button
            @click="loadMore"
            class="px-6 py-3 bg-[#5D18A2] text-white rounded-full text-sm font-semibold"
          >
            Load More
          </button>
        </div>
    </div>




    <?php get_footer(); ?>
