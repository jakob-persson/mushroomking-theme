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
    <script>
    document.addEventListener('alpine:init', () => {
        Alpine.store('editAdventureModal', {
            adventure: {},
            open: false,
            close() { this.open = false; },
        });
    });
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
    $initial_limit = 5;

    $hunts = $wpdb->get_results(
      $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}mushrooms
        ORDER BY start_date DESC
        LIMIT %d OFFSET %d",
        $initial_limit,
        0
      )
    );

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
                    class="w-16 h-16 rounded-full object-cover shadow-md shrink-0">

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
   <!-- MIDDLE COLUMN (FEED) -->
        <div
          id="feed"
          class="col-span-12 lg:col-span-6 lg:overflow-y-scroll lg:h-screen pt-10 lg:pl-6 lg:pr-4 space-y-8 pb-44"
          data-offset="<?= esc_attr($initial_limit); ?>"
          data-limit="5"
        >
          <?php foreach ($hunts as $hunt) { mk_render_feed_card($hunt); } ?>

          <!-- Sentinel: när den syns -> ladda mer -->
          <div id="feed-sentinel" class="h-10"></div>

          <!-- Loader text -->
          <div id="feed-loader" class="hidden text-center text-sm text-gray-500 py-6">
            Loading more…
          </div>
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
            <div class="bg-white rounded-3xl p-2 space-y-4">
                <?php if (!empty($all_users)) : ?>
                    <ul class="space-y-1">
                        <?php foreach ($all_users as $user) :
                            $avatar = mk_get_user_avatar($user->ID);
                        ?>
                      <?php
                          $profile_url = home_url('/' . $user->user_nicename);
                        ?>
                        <li>
                          <a
                            href="<?= esc_url($profile_url); ?>"
                            class="flex items-center gap-2 p-2 rounded-xl hover:bg-[#eff0ec] transition"
                          >
                            <img
                              src="<?= esc_url($avatar); ?>"
                              alt="<?= esc_attr($user->display_name); ?>"
                              class="w-10 h-10 rounded-full object-cover shadow-sm"
                            >
                            <span class="text-sm font-medium"><?= esc_html($user->display_name); ?></span>
                          </a>
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
  <div class="section-wrapper flex flex-col lg:flex-row justify-center items-center gap-10 lg:min-h-[88vh] overflow-hidden">

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

    <!-- RIGHT VISUAL (SLIDER) -->
<div class="relative flex justify-center lg:justify-end pt-16 lg:pt-0 z-1 w-full lg:flex-1 lg:px-8 mb-8 lg:mb-0">
  <div
    x-data="slider()"
    x-init="init()"
    x-ref="wrapper"

    @mouseenter="pause()"
    @mouseleave="resume()"

    @pointerdown.prevent="startDrag($event)"
    @pointermove.prevent="onDrag($event)"
    @pointerup="endDrag()"
    @pointercancel="endDrag()"
    @pointerleave="endDrag()"

    :class="isDragging ? 'cursor-grabbing' : 'cursor-grab'"
    :style="isMobile
      ? 'touch-action: pan-x; user-select: none;'
      : 'touch-action: none; user-select: none;'"

    class="relative w-full aspect-square lg:aspect-auto lg:w-[540px] lg:h-[540px] 2xl:w-[620px] 2xl:h-[620px] overflow-hidden lg:overflow-visible -mt-6"
  >
    <!-- TRACK -->
    <div
      class="absolute inset-0 transition-transform ease-out flex lg:flex-col"
      :class="isMoving ? 'duration-[2000ms]' : 'duration-0'"
      :style="transformStyle"
    >
      <template x-for="(src, i) in slides" :key="i">
        <div
          data-slide
          class="flex-shrink-0 w-full h-full aspect-square lg:aspect-square"
          :style="isMobile
            ? `margin-right: ${gap}px`
            : `margin-bottom: ${gap}px`"
        >
          <img
            :src="src"
            alt=""
            class="w-full h-full object-cover rounded-2xl"
          />
        </div>
      </template>
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
 <?php get_template_part('partials/modal', 'edit-adventure'); ?>
 <?php get_template_part('partials/modal', 'adventure'); ?>
<?php get_footer(); ?>
<script>
function deleteAdventure(adventureId) {
    fetch("<?= admin_url('admin-ajax.php'); ?>", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded",
        },
        body: new URLSearchParams({
            action: "mk_delete_adventure",
            adventure_id: adventureId,
            _ajax_nonce: "<?= wp_create_nonce('mk_delete_adventure'); ?>"
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // Simple reload (or you can remove the card via Alpine later)
            location.reload();
        } else {
            alert(data.data || "Could not delete adventure.");
        }
    })
    .catch(() => {
        alert("Something went wrong.");
    });
}
</script>
<script>
document.addEventListener("DOMContentLoaded", () => {
  const feed = document.getElementById("feed");
  const sentinel = document.getElementById("feed-sentinel");
  const loader = document.getElementById("feed-loader");
  if (!feed || !sentinel) return;

  let offset = parseInt(feed.dataset.offset || "5", 10);
  const limit = parseInt(feed.dataset.limit || "5", 10);
  let loading = false;
  let done = false;

  const ajaxUrl = "<?= esc_js(admin_url('admin-ajax.php')); ?>";
  const nonce = "<?= esc_js(wp_create_nonce('mk_load_more_hunts')); ?>";

  // ✅ Avgör om feed verkligen är en scroll-container (desktop) eller inte (mobile)
  function isScrollable(el) {
    const s = window.getComputedStyle(el);
    const canScroll = /(auto|scroll)/.test(s.overflowY);
    return canScroll && el.scrollHeight > el.clientHeight + 2;
  }

  async function loadMore() {
    if (loading || done) return;
    loading = true;
    loader?.classList.remove("hidden");

    try {
      const body = new URLSearchParams({
        action: "mk_load_more_hunts",
        _ajax_nonce: nonce,
        offset: String(offset),
        limit: String(limit),
      });

      const res = await fetch(ajaxUrl, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
        body,
        credentials: "same-origin", // ✅ bra praxis för WP
      });

      if (!res.ok) throw new Error("HTTP " + res.status);

      const data = await res.json();
      if (!data?.success) { done = true; return; }

      const html = data.data.html || "";
      const count = parseInt(data.data.count || "0", 10);
      if (!html || count === 0) { done = true; return; }

      sentinel.insertAdjacentHTML("beforebegin", html);
      offset += count;
      feed.dataset.offset = String(offset);

    } catch (e) {
      // om det felar: sluta trigga för att inte spamma
      done = true;
      // console.log("loadMore failed", e);
    } finally {
      loading = false;
      loader?.classList.add("hidden");
    }
  }

  const rootEl = isScrollable(feed) ? feed : null; // ✅ DESKTOP: feed / MOBILE: viewport

  const observer = new IntersectionObserver((entries) => {
    if (entries[0]?.isIntersecting) loadMore();
  }, {
    root: rootEl,
    rootMargin: "600px 0px",
    threshold: 0,
  });

  observer.observe(sentinel);
  window.addEventListener("scroll", () => {
    if (done || loading) return;
    const rect = sentinel.getBoundingClientRect();
    if (rect.top < window.innerHeight + 600) loadMore();
  }, { passive: true });
});

</script>


