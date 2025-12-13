<?php
/**
 * Template Name: Mushroom Stats
 *
 * A dedicated page to show detailed mushroom statistics.
 */

get_header();
global $wpdb;

// --- USER SELECTION ---
$current_user = wp_get_current_user();
$current_user_id = $current_user->ID;

// --- Stats Queries ---

// Total kilograms
$total_kg = floatval($wpdb->get_var($wpdb->prepare(
    "SELECT SUM(kilograms) FROM {$wpdb->prefix}mushrooms WHERE user_id = %d",
    $current_user_id
)));

// Best day
$best_day_kg = floatval($wpdb->get_var($wpdb->prepare("
    SELECT SUM(kilograms)
    FROM {$wpdb->prefix}mushrooms
    WHERE user_id = %d AND start_date IS NOT NULL AND start_date != '0000-00-00'
    GROUP BY start_date
    ORDER BY SUM(kilograms) DESC
    LIMIT 1
", $current_user_id)));

// Adventures count
$total_rounds = intval($wpdb->get_var($wpdb->prepare("
    SELECT COUNT(DISTINCT DATE(start_date))
    FROM {$wpdb->prefix}mushrooms
    WHERE user_id = %d AND start_date IS NOT NULL AND start_date != '0000-00-00'
", $current_user_id)));

// Locations
$total_locations = intval($wpdb->get_var($wpdb->prepare("
    SELECT COUNT(DISTINCT location)
    FROM {$wpdb->prefix}mushrooms
    WHERE user_id = %d AND location IS NOT NULL AND location != ''
", $current_user_id)));

// --- Mushrooms data ---
$results = $wpdb->get_results($wpdb->prepare("
    SELECT * FROM {$wpdb->prefix}mushrooms
    WHERE user_id = %d
", $current_user_id));

// Totals by type
$totals_by_type = [];
$kg_per_month_by_type = [];
foreach ($results as $row) {
    $raw = isset($row->types) ? $row->types : (isset($row->type) ? $row->type : '');
    $types = json_decode($raw, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($types)) {
        foreach ($types as $type => $kg) {
            $totals_by_type[$type] = ($totals_by_type[$type] ?? 0) + floatval($kg);
            $year_month = date('Y-m', strtotime($row->start_date));
            $kg_per_month_by_type[$type][$year_month] = ($kg_per_month_by_type[$type][$year_month] ?? 0) + floatval($kg);
        }
    }
}

// All months for chart x-axis
$all_months = [];
foreach ($results as $row) {
    $all_months[] = date('Y-m', strtotime($row->start_date));
}
$all_months = array_unique($all_months);
sort($all_months);

// Prepare data for JS
$kg_by_type_json = json_encode($kg_per_month_by_type);
$months_json = json_encode($all_months); // YYYY-MM

// --- Grouped Mushrooms for Adventures ---
$grouped_mushrooms = $wpdb->get_results($wpdb->prepare("
    SELECT
      DATE(start_date) AS grouped_date,
      location,
      GROUP_CONCAT(photo_url) AS photos,
      SUM(kilograms) AS total_kg
    FROM {$wpdb->prefix}mushrooms
    WHERE user_id = %d
    GROUP BY grouped_date, location
    ORDER BY grouped_date DESC
", $current_user_id));

// --- Totals per location ---
$totals_by_location = $wpdb->get_results($wpdb->prepare("
    SELECT location, SUM(kilograms) AS total_kg
    FROM {$wpdb->prefix}mushrooms
    WHERE user_id = %d AND location IS NOT NULL AND location != ''
    GROUP BY location
    ORDER BY total_kg DESC
", $current_user_id));

// --- Totals per location per type ---
$totals_by_location_by_type = [];

foreach ($results as $row) {
    $raw = isset($row->types) ? $row->types : (isset($row->type) ? $row->type : '');
    $types = json_decode($raw, true);

    if (json_last_error() === JSON_ERROR_NONE && is_array($types)) {
        foreach ($types as $type => $kg) {
            $totals_by_location_by_type[$type][$row->location] =
                ($totals_by_location_by_type[$type][$row->location] ?? 0) + floatval($kg);
        }
    }
}

$totals_by_location_by_type_json = json_encode($totals_by_location_by_type);

$location_labels = array_map(fn($row) => $row->location, $totals_by_location);
$location_values = array_map(fn($row) => floatval($row->total_kg), $totals_by_location);

$location_labels_json = json_encode($location_labels);
$location_values_json = json_encode($location_values);

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

  <!-- HEADER BAR -->
<div class="flex w-full bg-[#1E2330] h-[40px] items-center relative">
  <!-- Logo left -->
  <div class="absolute left-4 flex items-center h-full">
  <a href="<?php echo home_url(); ?>">
    <img src="<?= get_template_directory_uri(); ?>/images/mk-logo3.png" alt="Logo" class="h-[14px] lg:h-[14px]">
    </a>
  </div>

  <?php
    $current_user = wp_get_current_user();
    $welcome_name = $current_user->display_name ?: 'Explorer';
  ?>
  <!-- Desktop welcome text -->
  <div class="hidden lg:flex mx-auto text-white text-sm">
  <div class="flex items-center gap-2">
  <span class="inline-flex items-center translate-y-[1px]">👋</span>
  <span>Welcome back, <?= esc_html($welcome_name) ?></span>
</div>
  </div>

<!-- MOBILE avatar dropdown (Facebook-style, centered + slightly smaller) -->
<div class="absolute right-4 flex items-center h-full lg:hidden" x-data="{ open: false }">
  <div class="relative flex items-center justify-center h-full">
    <!-- Avatar button -->
    <button type="button" class="relative w-7 h-7 flex items-center justify-center" @click="open = !open">
      <!-- Avatar -->
      <img src="<?= get_template_directory_uri(); ?>/images/mk.jpg"
           alt="Profile Image"
           class="rounded-full w-7 h-7 border border-white object-cover">
      <!-- Chevron badge -->
      <div class="absolute -bottom-1 -right-1 bg-[#1E2330] rounded-full p-[1.5px] border border-white flex items-center justify-center">
        <svg xmlns="http://www.w3.org/2000/svg"
             class="w-2.5 h-2.5 text-white"
             fill="none"
             viewBox="0 0 24 24"
             stroke="currentColor"
             stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
        </svg>
      </div>
    </button>

    <!-- Dropdown -->
    <div x-show="open"
         @click.outside="open = false"
         x-transition
         class="absolute top-full right-0 mt-2 w-40 bg-white border rounded-lg shadow-lg text-gray-700 text-sm z-50">
      <ul class="py-1">
        <li><a href="/profile" class="block px-4 py-2 hover:bg-gray-100">Profile</a></li>
        <li><a href="/settings" class="block px-4 py-2 hover:bg-gray-100">Settings</a></li>
        <li>
          <form method="POST" action="/logout">
            <button type="submit" class="w-full text-left px-4 py-2 hover:bg-gray-100">Logout</button>
          </form>
        </li>
      </ul>
    </div>
  </div>
</div>

</div>


  <div class="fixed top-[40px] left-0 right-0 bottom-0 flex bg-[#F1F0EE] rounded-t-xl w-full z-10">
  <!-- SIDEBAR -->
  <aside class="hidden lg:flex w-64 bg-[#ECECE9] border-r border-gray-200 flex flex-col rounded-tl-xl sticky top-[40px] h-[calc(100vh-40px)] z-40">
     <div class="p-4  flex items-center gap-2 text-xs font-medium relative" x-data="{ open: false }">
      <img src="<?= get_template_directory_uri(); ?>/images/mk.jpg" alt="Profile Image" class="rounded-full w-8 h-8 border border-white">
      <div class="flex items-center gap-1 cursor-pointer select-none" @click="open = !open">
          <span><?= esc_html($welcome_name) ?></span>
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
      </div>
      <div x-show="open" @click.outside="open = false" x-transition class="absolute top-full right-0 mt-2 w-40 bg-white border rounded-lg shadow-lg text-gray-700 text-sm z-50">
          <ul class="py-1">
              <li><a href="/profile" class="block px-4 py-2 hover:bg-gray-100">Profile</a></li>
              <li><a href="/settings" class="block px-4 py-2 hover:bg-gray-100">Settings</a></li>
              <li><form method="POST" action="/logout"><button type="submit" class="w-full text-left px-4 py-2 hover:bg-gray-100">Logout</button></form></li>
          </ul>
      </div>
  </div>

  <nav class="flex-1 p-4 space-y-2 text-sm">
      <!-- Insights with submenu -->
      <div x-data="{ open: true }">
          <button @click="open = !open" class="flex items-center justify-between w-full px-3 py-2 rounded-lg hover:bg-white">
              <span class="flex items-center gap-2">
                  <i class="fas fa-chart-line w-4 dark"></i>
                  Insights
              </span>
              <svg xmlns="http://www.w3.org/2000/svg" :class="{'rotate-180': open}" class="w-4 h-4 transform transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
          </button>
          <div x-show="open" x-transition class="ml-5 pl-3 mt-1 space-y-1 border-l border-[#d1d1ce]">
              <a href="#adventures" class="block px-2 py-1 rounded hover:bg-gray-100">Adventures</a>
              <a href="#harvests" class="block px-2 py-1 rounded hover:bg-gray-100">Harvests</a>
              <a href="#harvests" class="block px-2 py-1 rounded hover:bg-gray-100">Locations</a>
          </div>
      </div>

         <!-- Insights with submenu -->
      <div x-data="{ open: true }">
          <button @click="open = !open" class="flex items-center justify-between w-full px-3 py-2 rounded-lg hover:bg-white">
              <span class="flex items-center gap-2">
                 <i class="fas fa-clapperboard w-4 dark"></i>
                  Studio
              </span>
              <svg xmlns="http://www.w3.org/2000/svg" :class="{'rotate-180': open}" class="w-4 h-4 transform transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
          </button>
          <div x-show="open" x-transition class="ml-5 pl-3 mt-1 space-y-1 border-l border-[#d1d1ce]">
              <a href="#adventures" class="block px-2 py-1 rounded hover:bg-gray-100">Create Reel</a>
          </div>
      </div>
  </nav>


  <div class="p-4 border-t">
      <button
         @click="$store.modal.isOpen = true"
        class="w-full px-4 py-2 bg-[#5D18A2] text-white rounded-lg flex items-center gap-2 hover:opacity-80">
      <img class="w-4 h-4" src="<?= get_template_directory_uri(); ?>/images/add3.svg" alt="Add Icon">
      Add adventure</button>
  </div>
  </aside>

  <!-- Sticky Header -->
  <div class="fixed top-[40px] left-0 lg:left-[218px] right-0 z-30 bg-[#eff0ec] border-b h-[65px] flex items-center px-8 rounded-t-xl">
      <div class="text-base font-semibold text-[#111827]">Overview</div>
  <div class="ml-auto flex items-center gap-2 relative">
  <!-- Timeframe button -->
<div class="relative">
  <button id="timeframeBtn" class="bg-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-gray-100 flex items-center gap-2">
      <!-- Font Awesome Calendar Icon -->
      <i class="fas fa-calendar-alt text-gray-600"></i>
      Lifetime
      <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path d="M19 9l-7 7-7-7" />
      </svg>
  </button>

  <div id="timeframeMenu" class="hidden absolute right-0 mt-2 w-40 bg-white border border-gray-300 rounded-lg shadow-lg">
    <ul class="text-sm text-gray-700">
        <li><button class="block w-full text-left px-4 py-2 hover:bg-gray-100">Lifetime</button></li>
        <li><button class="block w-full text-left px-4 py-2 hover:bg-gray-100">1 Month</button></li>
        <li><button class="block w-full text-left px-4 py-2 hover:bg-gray-100">3 Months</button></li>
        <li><button class="block w-full text-left px-4 py-2 hover:bg-gray-100">6 Months</button></li>
        <li><button class="block w-full text-left px-4 py-2 hover:bg-gray-100">12 Months</button></li>
    </ul>
  </div>
</div>

      <!-- Settings button -->
      <button id="settingsBtn" class="bg-white p-2 rounded-xl hover:bg-gray-100 flex items-center justify-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.105c1.518-.878 3.356.96 2.478 2.478a1.724 1.724 0 001.106 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.106 2.573c.878 1.518-.96 3.356-2.478 2.478a1.724 1.724 0 00-2.573 1.106c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.106c-1.518.878-3.356-.96-2.478-2.478a1.724 1.724 0 00-1.106-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.106-2.573c-.878-1.518.96-3.356 2.478-2.478.967.56 2.147.124 2.573-1.106z" />
              <circle cx="12" cy="12" r="3" fill="currentColor" />
          </svg>
      </button>
  </div>

  </div>

  <script>
  document.addEventListener("DOMContentLoaded", function () {
      const btn = document.getElementById("timeframeBtn");
      const menu = document.getElementById("timeframeMenu");
      btn.addEventListener("click", () => menu.classList.toggle("hidden"));
      document.addEventListener("click", (e) => {
          if (!btn.contains(e.target) && !menu.contains(e.target)) menu.classList.add("hidden");
      });
  });
  </script>

  <main class="w-full overflow-y-auto h-full mt-[60px]">
  <div class="p-8" style="padding-bottom: 120px">

  <!-- OVERVIEW STATS -->
<!-- OVERVIEW STATS (mobile-friendly & properly aligned) -->
<div class="bg-white rounded-3xl p-5 sm:p-6 shadow-sm w-full max-w-8xl mx-auto mb-6">
  <div class="flex items-center gap-2 mb-5">
    <h2 class="text-base sm:text-lg font-semibold text-[#111827]">Overview <?= date('Y') ?></h2>
  </div>

  <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 sm:gap-6 text-xs sm:text-sm text-gray-700">
    <!-- Total Kilograms -->
    <div class="flex items-center gap-2 bg-[#F9F9F8] rounded-2xl p-3 sm:p-4">
      <div class="bg-[#eff0ec] rounded-full p-2.5 flex items-center justify-center">
        <img src="<?= get_template_directory_uri(); ?>/images/basket2.svg" class="w-5 h-5 sm:w-6 sm:h-6" alt="Total Kilograms">
      </div>
      <div>
        <div class="text-gray-900 font-semibold text-sm sm:text-base">
          <?= rtrim(rtrim(number_format($total_kg, 2, '.', ''), '0'), '.') ?>
        </div>
        <div class="text-gray-600 text-[11px] sm:text-xs">Kilograms</div>
      </div>
    </div>

    <!-- Adventures -->
    <div class="flex items-center gap-2 bg-[#F9F9F8] rounded-2xl p-3 sm:p-4">
      <div class="bg-[#eff0ec] rounded-full p-2.5 flex items-center justify-center">
        <img src="<?= get_template_directory_uri(); ?>/images/mountain.svg" class="w-5 h-5 sm:w-6 sm:h-6" alt="Adventures">
      </div>
      <div>
        <div class="text-gray-900 font-semibold text-sm sm:text-base">
          <?= $total_rounds ?>
        </div>
        <div class="text-gray-600 text-[11px] sm:text-xs">Adventures</div>
      </div>
    </div>

    <!-- Locations -->
    <div class="flex items-center gap-2 bg-[#F9F9F8] rounded-2xl p-3 sm:p-4">
      <div class="bg-[#eff0ec] rounded-full p-2.5 flex items-center justify-center">
        <img src="<?= get_template_directory_uri(); ?>/images/locations2.svg" class="w-5 h-5 sm:w-6 sm:h-6" alt="Locations">
      </div>
      <div>
        <div class="text-gray-900 font-semibold text-sm sm:text-base">
          <?= $total_locations ?>
        </div>
        <div class="text-gray-600 text-[11px] sm:text-xs">Locations</div>
      </div>
    </div>

    <!-- Best Day -->
    <div class="flex items-center gap-2 bg-[#F9F9F8] rounded-2xl p-3 sm:p-4">
      <div class="bg-[#eff0ec] rounded-full p-2.5 flex items-center justify-center">
        <img src="<?= get_template_directory_uri(); ?>/images/trophy3.svg" class="w-5 h-5 sm:w-6 sm:h-6" alt="Best Day">
      </div>
      <div>
        <div class="text-gray-900 font-semibold text-sm sm:text-base">
          <?= rtrim(rtrim(number_format($best_day_kg, 2, '.', ''), '0'), '.') ?>
        </div>
        <div class="text-gray-600 text-[11px] sm:text-xs">Best Day (kg)</div>
      </div>
    </div>
  </div>
</div>


  <!-- MUSHROOM ADVENTURES -->
  <div id="adventuresContainer" class="bg-white rounded-3xl p-6 shadow-sm w-full max-w-8xl mx-auto mb-6">
      <div class="flex items-center justify-between mb-6">
        <h2 class="text-lg font-semibold">Mushroom adventures</h2>

        <!-- Filter Button -->
        <button id="adventureFilterBtn"
                class="flex items-center gap-2 px-4 py-2 text-sm font-se bg-white border border-[#d1d1ce] rounded-xl shadow-sm hover:bg-gray-100">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L15 12.414V19a1 1 0 01-.553.894l-4 2A1 1 0 019 21v-8.586L3.293 6.707A1 1 0 013 6V4z" />
            </svg>
            Filter
        </button>
    </div>

      <div class="flex overflow-x-auto no-scrollbar space-x-2 mb-6" id="adventureFilters"></div>
      <div class="space-y-4" id="adventureList"></div>
      <div class="text-center mt-6"><button id="loadMoreHunts" class="px-6 py-3 bg-[#eff0ec] dark rounded-full text-sm font-semibold">Load More</button></div>
  </div>

  <!-- MONTHLY HARVEST -->
  <div class="bg-white rounded-3xl p-6 shadow-sm w-full max-w-8xl mx-auto mb-6">
       <div class="flex items-center justify-between mb-6">
        <h2 class="text-lg font-semibold">Monthly harvest</h2>

        <!-- Filter Button -->
        <button id="adventureFilterBtn"
                class="flex items-center gap-2 px-4 py-2 text-sm font-regular bg-white border border-[#d1d1ce] rounded-xl shadow-sm hover:bg-gray-100">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L15 12.414V19a1 1 0 01-.553.894l-4 2A1 1 0 019 21v-8.586L3.293 6.707A1 1 0 013 6V4z" />
            </svg>
            Filter
        </button>
    </div>
      <div class="mb-6">
      <div class="flex overflow-x-auto no-scrollbar space-x-2" id="harvestFilters">
          <button data-type="all" class="flex-shrink-0 px-4 py-2 text-sm font-regular whitespace-nowrap border-b-2 border-[#111827] text-[#111827]">
              All
          </button>
          <?php foreach(array_keys($totals_by_type) as $type): ?>
          <button data-type="<?= esc_attr($type) ?>" class="flex-shrink-0 px-4 py-2 text-sm font-regular whitespace-nowrap bg-white text-[#111827]">
              <?= esc_html($type) ?>
          </button>
          <?php endforeach; ?>
      </div>
  </div>
      <canvas id="mushroomChart" height="100"></canvas>
  </div>

  <!-- LOCATION HARVEST -->
  <div class="bg-white rounded-3xl p-6 shadow-sm w-full max-w-8xl mx-auto mb-6">
       <div class="flex items-center justify-between mb-6">
        <h2 class="text-lg font-semibold">Harvest by location</h2>
        <!-- Filter Button -->
        <button id="adventureFilterBtn"
                class="flex items-center gap-2 px-4 py-2 text-sm font-regular bg-white border border-[#d1d1ce] rounded-xl shadow-sm hover:bg-gray-100">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L15 12.414V19a1 1 0 01-.553.894l-4 2A1 1 0 019 21v-8.586L3.293 6.707A1 1 0 013 6V4z" />
            </svg>
            Filter
        </button>
    </div>
    <div class="mb-6">
  <div class="flex overflow-x-auto no-scrollbar space-x-2" id="locationFilters">
      <button data-type="all"
              class="flex-shrink-0 px-4 py-2 text-sm font-semibold whitespace-nowrap border-b-2 border-[#111827] text-[#111827]">
          All
      </button>
      <?php foreach(array_keys($totals_by_type) as $type): ?>
      <button data-type="<?= esc_attr($type) ?>"
              class="flex-shrink-0 px-4 py-2 text-sm font-regular whitespace-nowrap bg-white text-[#111827]">
          <?= esc_html($type) ?>
      </button>
      <?php endforeach; ?>
  </div>
</div>

    <canvas id="locationChart" height="100"></canvas>
  </div>
  </div>
  </main>

<!-- MOBILE BOTTOM NAV -->
<nav class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 flex justify-center items-center h-16 md:hidden z-50">
  <button
     @click="$store.modal.isOpen = true"
     class="flex items-center gap-2 px-6 py-2 bg-[#5D18A2] text-white rounded-full shadow-lg hover:opacity-90">
    <img class="w-4 h-4" src="<?= get_template_directory_uri(); ?>/images/add3.svg" alt="Add Icon">
    Add adventure
  </button>
</nav>


  </div> <!-- end main wrapper -->
  <?php
  // Load reusable Add Adventure modal
  get_template_part('partials/modal', 'add-adventure');
  ?>
  <!-- SCRIPTS -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
  document.addEventListener("DOMContentLoaded", function () {
      const months = <?= $months_json ?>;
      const dataByType = <?= $kg_by_type_json ?>;
      const monthsLabels = months.map(m=>{
          const [y,mo]=m.split('-'); const d=new Date(y,mo-1);
          return d.toLocaleString('default',{month:'short',year:'numeric'});
      });

      const ctx = document.getElementById('mushroomChart').getContext('2d');
  const chart = new Chart(ctx,{
      type:'line',
      data:{
          labels: monthsLabels,
          datasets:[{
              label:'Kilograms',
              data:months.map(()=>0),
              borderColor:'rgba(124, 58, 237, 0.8)',
              backgroundColor:'rgba(124, 58, 237, 0)',
              tension:0.4,
              fill:true,
              pointBackgroundColor:'rgba(124, 58, 237, 1)',
              pointRadius:4
          }]
      },
      options:{
          responsive:true,
          plugins:{legend:{display:false}},
          scales:{   // 👈 LOOK HERE
              y:{
                  ticks:{
                      color:'#6B7280',
                      stepSize:100   // 👈 THIS controls the spacing
                  },
                  grid:{drawBorder:false}
              },
              x:{
                  ticks:{color:'#6B7280'},
                  grid:{display:false}
              }
          }
      }
  });


      // --- Monthly Harvest Filters ---
      const harvestFilterContainer = document.getElementById('harvestFilters');
      let activeType = 'all';

      function updateHarvestFilterUI() {
          harvestFilterContainer.querySelectorAll('button').forEach(btn => {
              if (btn.dataset.type === activeType) {
                  btn.className = "flex-shrink-0 px-4 py-2 text-sm font-semibold whitespace-nowrap border-b-2 border-[#111827] text-[#111827]";
              } else {
                  btn.className = "flex-shrink-0 px-4 py-2 text-sm font-regular whitespace-nowrap bg-white text-[#111827]";
              }
          });
      }

      function updateHarvestChart() {
          chart.data.datasets[0].data = months.map(m =>
              activeType === 'all'
                  ? Object.values(dataByType).reduce((sum, t) => sum + (t[m] || 0), 0)
                  : (dataByType[activeType]?.[m] || 0)
          );
          chart.update();
      }

      harvestFilterContainer.querySelectorAll('button').forEach(btn => {
          btn.addEventListener('click', () => {
              activeType = btn.dataset.type;
              updateHarvestFilterUI();
              updateHarvestChart();
          });
      });

      updateHarvestChart();
      updateHarvestFilterUI();

     // --- Mushroom Adventures ---
    const hunts = <?= json_encode($hunts) ?>;
    let filter = 'all', visibleCount = 5; // <-- start with 5 instead of 10
    const filters = ['all','top10','recent','heavy'];
    const filterContainer = document.getElementById('adventureFilters');
    const listContainer = document.getElementById('adventureList');
    const loadMoreBtn = document.getElementById('loadMoreHunts');

    function renderFilters(){
    filterContainer.innerHTML='';
    filters.forEach(f=>{
        const btn=document.createElement('button');
        btn.textContent=f==='all'?'All':f==='top10'?'Top 10':f==='recent'?'Last 30 Days':'Over 30kg';
        btn.className = `flex-shrink-0 px-4 py-2 text-sm whitespace-nowrap
${filter===f
    ? 'border-b-2 border-[#111827] text-[#111827] font-bold'
    : 'bg-white text-[#111827] font-regular'}`;
        btn.addEventListener('click',()=>{filter=f; visibleCount=5; renderHunts(); renderFilters();});
        filterContainer.appendChild(btn);
    });
}

function getFilteredHunts(){
    const now=Date.now()/1000;
    let result=[];
    if(filter==='top10') result=hunts.slice().sort((a,b)=>b.total_kg-a.total_kg).slice(0,10);
    else if(filter==='recent') result=hunts.filter(h=>h.timestamp>=now-30*24*3600); // <-- 30 days
    else if(filter==='heavy') result=hunts.filter(h=>h.total_kg>30);
    else result=hunts;
    return result.slice(0,visibleCount);
}

      function renderHunts(){
          const items=getFilteredHunts();
          listContainer.innerHTML='';
          if(items.length===0){listContainer.innerHTML='<div class="text-center text-gray-400 text-sm mt-4">No hunts match this filter.</div>';return;}
          items.forEach(h=>{
              const div=document.createElement('div');
              div.className='flex items-center justify-between bg-white border border-[#F4F4F1] rounded-2xl p-4';
              const maxKg = Math.max(...hunts.map(h => h.total_kg));
              const percentage = Math.round((h.total_kg / maxKg) * 100);

            div.innerHTML = `
            <div class="flex items-center gap-4">
                ${h.photo
                ? `<img src="${h.photo}" class="w-12 h-12 rounded-md object-cover"/>`
                : `<div class="w-12 h-12 bg-gray-200 rounded-md flex items-center justify-center text-gray-500">🍄</div>`
                }
                <div class="text-sm font-regular flex flex-col">
                <div><strong>${h.location}</strong></div>
                <div class="text-sm">${new Date(h.timestamp * 1000).toLocaleDateString()}</div>
                </div>
            </div>

           <div class="flex flex-row items-end justify-end w-[130px] text-sm">
                <div><span class="font-medium">${h.total_kg}</span> kg</div>
                <div class="w-[60px] bg-[#eff0ec] rounded-full h-5 mt-1 ml-2">
                    <div class="bg-[#5D18A2] h-5 rounded-full transition-all" style="width: ${percentage}%;"></div>
                </div>
            </div>
            `;

              listContainer.appendChild(div);
          });
      }

      loadMoreBtn.addEventListener('click',()=>{visibleCount+=5;renderHunts();});
      renderFilters();
      renderHunts();
  });

  // --- Harvest by Location ---
  // --- Harvest by Location ---
const locationLabelsAll = <?= $location_labels_json ?>;
const locationValuesAll = <?= $location_values_json ?>;
const locationDataByType = <?= $totals_by_location_by_type_json ?>;

const ctxLoc = document.getElementById('locationChart').getContext('2d');
const locationChart = new Chart(ctxLoc, {
  type: 'bar',
  data: {
    labels: locationLabelsAll,
    datasets: [{
      label: 'Total Kilograms',
      data: locationValuesAll,
      backgroundColor: 'rgba(124, 58, 237, 0.7)',
      borderRadius: 8
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: {
      y: { beginAtZero: true, ticks: { color: '#6B7280' }, grid: { drawBorder: false }},
      x: { ticks: { color: '#6B7280' }, grid: { display: false }}
    }
  }
});

// --- Filter buttons for location chart ---
const locFilterContainer = document.getElementById('locationFilters');
let activeLocType = 'all';

function updateLocationFilterUI() {
    locFilterContainer.querySelectorAll('button').forEach(btn => {
        if (btn.dataset.type === activeLocType) {
            btn.className = "flex-shrink-0 px-4 py-2 text-sm font-semibold whitespace-nowrap border-b-2 border-[#111827] text-[#111827]";
        } else {
            btn.className = "flex-shrink-0 px-4 py-2 text-sm font-regular whitespace-nowrap bg-white text-[#111827]";
        }
    });
}

function updateLocationChart() {
if (activeLocType === 'all') {
    locationChart.data.labels = locationLabelsAll;
    locationChart.data.datasets[0].data = locationValuesAll;
} else {
    const dataForType = locationDataByType[activeLocType] || {};
    locationChart.data.labels = locationLabelsAll;
    locationChart.data.datasets[0].data = locationLabelsAll.map(loc =>
        dataForType[loc] ? dataForType[loc] : 0
    );
}

    locationChart.update();
}

locFilterContainer.querySelectorAll('button').forEach(btn => {
    btn.addEventListener('click', () => {
        activeLocType = btn.dataset.type;
        updateLocationFilterUI();
        updateLocationChart();
    });
});

updateLocationChart();
updateLocationFilterUI();




  </script>


  <?php get_footer(); ?>
