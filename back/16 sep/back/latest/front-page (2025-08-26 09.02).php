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
    DATE(start_date) AS grouped_date,
    GROUP_CONCAT(photo_url) AS photos,
    SUM(kilograms) AS total_kg
  FROM {$wpdb->prefix}mushrooms
  WHERE start_date IS NOT NULL AND start_date != ''
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
    'timestamp' => strtotime($entry->grouped_date),
    'total_kg' => floatval($entry->total_kg),
    'photo' => $photos[0] ?? null,
  ];
}, $grouped_mushrooms);
?>

<!-- Hero Section -->
<div class="bg-[#124C12] lg:px-16 pb-12">
  <!-- Top on mobile, centered on desktop -->
  <div class="flex flex-col lg:flex-row justify-start lg:justify-center items-start lg:items-center gap-10 lg:min-h-[88vh]">
    <!-- Text Column (no flex on mobile) -->
    <div class="flex-none lg:flex-1 text-left px-6 mb-4 flex flex-col justify-start pt-6 lg:pt-0">
      <h1 class="text-5xl lg:text-[82px] text-[#CEE027] gilroy leading-[54px] lg:leading-[82px] lg:w-full">
       Here’s how your mushroom season looks in 2025
      </h1>
      <p class="text-white text-lg max-w-lg  my-2">
        Track all your foraging adventures, total harvest weight, best days, and locations — all in one place.
      </p>
      <button class="mt-6 bg-[#E9C0E9] px-6 py-4 rounded-full font-medium text-[#1E1E1E] hover:opacity-90 w-auto self-start">
        View summary
      </button>
    </div>

    <!-- Image Column (no flex on mobile so it doesn't force height) -->
    <div class="flex-none lg:flex-1 flex justify-center items-center mt-4 lg:mt-0">
      <div id="tilt-container" class="relative perspective-[1000px] -mt-[40px]">
        <img src="<?= get_template_directory_uri(); ?>/images/main-screen.png"
             alt="Mushroom dashboard preview"
             class="w-[100%] max-w-none drop-shadow-xl rounded-xl transition-transform duration-150 ease-out">
      </div>
    </div>

  </div>
</div>
<!-- Adventures Image Slider with Portrait-Style Boxes -->
<div class="py-16 text-|#E9EED8] bg-[#F3F3F1] lg:px-16 lg:h-[100vh] flex flex-col px-6" style="padding-top: 148px;">
  <div class="flex justify-center w-full mb-12">
  <h1 class="lg:text-center text-5xl lg:text-6xl dark gilroy leading-[52px] lg:leading-[62px] lg:w-[60%]">
    Latest mushroom <span class="text-[#2665D6]">adventures</span>
  </h1>
</div>

<div id="adventure-slider" class="flex overflow-x-auto no-scrollbar space-x-4 pb-4 snap-x snap-mandatory">

  <?php
$adventure_photos = $wpdb->get_results("
  SELECT photo_url, location, start_date
  FROM {$wpdb->prefix}mushrooms
  WHERE photo_url IS NOT NULL AND photo_url != ''
    AND start_date IS NOT NULL AND start_date != '0000-00-00'
  ORDER BY start_date DESC
  LIMIT 10
");

    $shape_styles = [
      ['class' => 'rounded-[20px]', 'size' => 'w-[426px] h-[340px]'],
      ['class' => 'rounded-[52px]', 'size' => 'w-[260px] h-[340px]'],
      // ['class' => 'rounded-[110px]', 'size' => 'w-[260px] h-[340px]'],
      // ['class' => 'rounded-[52px]', 'size' => 'w-[162px] h-[340px]'],
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
    Overview of the season 2025
        </h1>
        <p class="mt-4 dark text-base leading-relaxed text-white">
            Monitor your foraging trips, total harvest weight, and top locations.
            See which days bring in the biggest hauls and keep improving your skills
            to find more of nature’s hidden treasures.
        </p>
        <button class="bg-[#E9C0E9] px-6 py-4 rounded-full font-medium text-[#1E1E1E] hover:opacity-90">
           View all stats
        </button>
    </div>

</div>
<!-- Stats Section ends -->

</div>
<!-- Modal Overlay -->
<div
  x-show="$store.modal.isOpen"
  x-trap.noscroll="$store.modal.isOpen"
  x-init="$watch('$store.modal.isOpen', value => {
    document.body.style.overflow = value ? 'hidden' : '';
  })"
  class="fixed inset-0 flex items-end bg-black bg-opacity-40 z-50"
  x-cloak
>
  <!-- Modal Container -->
  <div
    @click.away="$store.modal.isOpen = false"
    class="relative bg-white rounded w-full mx-auto shadow-xl transition-transform duration-300 ease-in-out transform translate-y-0 flex flex-col rounded-t-2xl max-h-[80vh] lg:max-h-[94vh]"
    x-data="{ tab: 'chanterelles' }"
  >
  <!-- Close Icon -->
<button
  @click="$store.modal.isOpen = false"
  class="absolute -top-[32px] right-5 text-white hover:text-black text-xl z-50"
  aria-label="Close modal"
>
  <i class="fas fa-times"></i>
</button>

    <!-- Modal Header -->
    <div class="p-6 flex-shrink-0 bg-white z-10 px-6 border-b flex-shrink-0 px-4 lg:px-[2%] rounded-t-2xl">
      <h2 class="text-xl gilroy pt-1">Add adventure</h2>
    </div>
    <!-- Scrollable Content -->
    <div class="px-6 pb-12 overflow-y-auto flex-grow lg:px-[30%]">
       <div class="font-bold text-4xl mt-12 gilroy">
      Nice! Excited to see what you have in your <span class="text-[#2665D6]">report!</span>
     </div>
          <!-- Mushroom type tabs (swipable) -->
    <div class="flex overflow-x-auto no-scrollbar space-x-2 mb-4 mt-12">
        <button @click="tab = 'chanterelles'" :class="tab === 'chanterelles' ? 'bg-[#CEE027] text-[#111827]' : 'bg-[#eff0ec] text-[#111827]'" class="flex-shrink-0 px-3 py-2 rounded-full text-sm font-medium whitespace-nowrap">Chanterelles</button>
        <button @click="tab = 'funnel'" :class="tab === 'funnel' ? 'bg-[#CEE027] text-[#111827]' : 'bg-[#eff0ec] text-[#111827]'" class="flex-shrink-0 px-3 py-2 rounded-full text-sm font-medium whitespace-nowrap">Funnel Chanterelles</button>
        <button @click="tab = 'boletus'" :class="tab === 'boletus' ? 'bg-[#CEE027] text-[#111827]' : 'bg-[#eff0ec] text-[#111827]'" class="flex-shrink-0 px-3 py-2 rounded-full text-sm font-medium whitespace-nowrap">Boletus</button>
        <button @click="tab = 'trumpets'" :class="tab === 'trumpets' ? 'bg-[#CEE027] text-[#111827]' : 'bg-[#eff0ec] text-[#111827]'" class="flex-shrink-0 px-3 py-2 rounded-full text-sm font-medium whitespace-nowrap">Trumpets</button>
      </div>
      <form id="mushroom-form" class="space-y-4">

        <!-- Chanterelles -->
        <div x-show="tab === 'chanterelles'">
          <label class="block text-sm font-regular mb-2">Chanterelles (kg)</label>
          <input name="chanterelles" min="0" class="w-full mb-4 px-4 py-3 rounded focus:outline-none focus:ring-2 focus:ring-black text-sm bg-[#F6F7F5] border border-[#E0E2D9]"
          placeholder="Add amount"/>
        </div>

        <!-- Funnel Chanterelles -->
        <div x-show="tab === 'funnel'">
          <label class="block text-sm font-regular mb-2">Funnel Chanterelles (kg)</label>
          <input name="funnel"  class="w-full mb-4 px-4 py-3 rounded focus:outline-none focus:ring-2 focus:ring-black text-base bg-[#F6F7F5] border border-[#E0E2D9] text-sm"/>
        </div>

        <!-- Boletus -->
        <div x-show="tab === 'boletus'">
          <label class="block text-sm font-regular mb-2">Boletus (kg)</label>
          <input name="boletus" class="w-full mb-4 px-4 py-3 rounded focus:outline-none focus:ring-2 focus:ring-black text-base bg-[#F6F7F5] border border-[#E0E2D9] text-sm"/>
        </div>

        <!-- Trumpets -->
        <div x-show="tab === 'trumpets'">
          <label class="block text-sm font-regular mb-2">Trumpets (kg)</label>
          <input name="trumpets" class="w-full mb-4 px-4 py-3 rounded focus:outline-none focus:ring-2 focus:ring-black text-base bg-[#F6F7F5] border border-[#E0E2D9] text-sm"/>
        </div>

        <!-- Photo Upload -->
        <div>
          <label class="block text-sm font-regular mb-2">Upload Photo (optional)</label>
          <div id="upload_box" class="flex flex-col items-center justify-center border border-dashed border border-[#E0E2D9] rounded-xl p-6  text-center">
            <label for="mushroom_photo" class="cursor-pointer flex flex-col items-center space-y-2">
              <div class="w-16 h-16 flex items-center justify-center bg-[#F3F3F1] rounded-full">
                <i class="fas fa-camera text-2xl text-[#111827]"></i>
              </div>
              <span class="text-gray-700 text-sm">Add a photo</span>
              <span class="text-gray-400 text-xs">(JPEG or PNG)</span>
            </label>
            <input id="mushroom_photo" name="mushroom_photo" type="file" accept="image/*" class="hidden" />
          </div>
          <div id="image_preview" class="hidden mt-4 text-center">
            <img id="preview_img" src="" alt="Preview" class="w-28 h-28 object-cover rounded-md border mx-auto" />
            <button type="button" id="remove_preview" class="text-sm text-red-600 hover:underline mt-2">Remove Photo</button>
          </div>
        </div>
        <!-- Location -->
        <div>
          <label class="block text-sm font-regular mb-2">Location (optional)</label>
          <input name="location" type="text" class="w-full mb-4 px-4 py-3 rounded focus:outline-none focus:ring-2 focus:ring-black text-base bg-[#F6F7F5] border border-[#E0E2D9] text-sm" placeholder="Where did you find them?" />
        </div>
        <!-- Start Date -->
        <div class="w-full">
          <label for="start_date" class="block text-sm font-regular mb-2">Start Date (optional)</label>
          <input
            id="start_date"
            name="start_date"
            type="date"
            class="block w-full mb-4 px-4 py-3 rounded focus:outline-none focus:ring-2 focus:ring-black text-base bg-[#F6F7F5] border border-[#E0E2D9] appearance-none  text-sm "
            value="<?php echo date('Y-m-d'); ?>"
            inputmode="none"
          />
        </div>
      </form>
    </div>
<!-- Saving Overlay -->
<!-- <div id="saving-overlay" class="absolute inset-0 bg-white bg-opacity-80 flex items-center justify-center z-50 hidden"> -->
  <div id="saving-overlay" class="absolute inset-0 bg-white bg-opacity-80 flex items-center justify-center z-50 hidden">
  <div class="flex flex-col items-center">
    <svg id="saving-spinner" class="animate-spin h-12 w-12 text-black" viewBox="0 0 50 50" xmlns="http://www.w3.org/2000/svg">
      <circle
        class="opacity-25"
        cx="25"
        cy="25"
        r="20"
        stroke="currentColor"
        stroke-width="4"
        fill="none"
      />
      <path
        class="opacity-75"
        fill="currentColor"
        d="M25 5a20 20 0 0 1 20 20h-4a16 16 0 0 0-16-16V5z"
      />
    </svg>
    <svg id="saving-check" class="hidden h-10 w-10 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
      <path stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
    </svg>
    <span id="saving-message" class="mt-3 text-sm font-medium">Saving...</span>
  </div>
</div>
    <!-- Fixed Footer with Save button -->
    <div class="px-6 py-3 flex-shrink-0 border-t bg-white lg:px-[30%]">
      <button id="save-button" type="submit" form="mushroom-form" class="w-full bg-[#1E2330] text-white py-2.5 rounded hover:bg-gray-900 transition duration-300 rounded-xl">
        <svg id="save-spinner" class="hidden animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
        </svg>
        <span id="save-label">Save</span>
      </button>

    </div>

  </div>
</div>

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
  <h1 class="font-bold gilroy text-white lg:text-6xl  gilroy leading-[52px] lg:leading-[62px] text-5xl ">Your foraging adventures sorted by mushroom<br>type</h1>
   <p class="text-white text-base leading-relaxed text-white">
            See your harvests broken down by mushroom type. Track how much you’ve gathered of each variety and discover which mushrooms dominate your adventures.
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

  class="pt-12 px-6 bg-white"
>

  <h2 class="text-xl gilroy mb-4 text-[#111827]">Your Mushroom Hunts</h2>

  <!-- Filters -->
  <div class="flex overflow-x-auto no-scrollbar space-x-2 mb-6">
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
  <div class="space-y-4">
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

<script>
  document.getElementById('mushroom-form').addEventListener('submit', async function(e) {
  e.preventDefault();

  const savingOverlay = document.getElementById('saving-overlay');
  const savingSpinner = document.getElementById('saving-spinner');
  const savingCheck = document.getElementById('saving-check');
  const savingMessage = document.getElementById('saving-message');

  savingOverlay.classList.remove('hidden');
  savingSpinner.classList.remove('hidden');
  savingCheck.classList.add('hidden');
  savingMessage.textContent = "Saving...";

  const formData = new FormData(e.target);
  const location = formData.get('location');
  const photoFile = formData.get('mushroom_photo');
  const startDate = formData.get('start_date');

  // Collect all mushrooms into an object
  const mushrooms = {};
  if (formData.get('chanterelles')) mushrooms["Chanterelles"] = parseFloat(formData.get('chanterelles'));
  if (formData.get('funnel')) mushrooms["Funnel Chanterelles"] = parseFloat(formData.get('funnel'));
  if (formData.get('boletus')) mushrooms["Boletus"] = parseFloat(formData.get('boletus'));
  if (formData.get('trumpets')) mushrooms["Trumpets"] = parseFloat(formData.get('trumpets'));

  if (Object.keys(mushrooms).length === 0) {
    alert("Please enter at least one mushroom type and amount.");
    savingOverlay.classList.add('hidden');
    return;
  }

  const mushroomData = new FormData();
  mushroomData.append('action', 'add_mushroom');
  mushroomData.append('types', JSON.stringify(mushrooms)); // send all types in one
  mushroomData.append('start_date', startDate);
  mushroomData.append('location', location);

  if (photoFile && photoFile.size > 0) {
    mushroomData.append('mushroom_photo', photoFile);
  }

  try {
    const response = await fetch("<?= admin_url('admin-ajax.php'); ?>", {
      method: 'POST',
      body: mushroomData
    });

    const result = await response.json();

    if (!response.ok || !result.success) {
      throw new Error(result.data || 'Unknown error occurred.');
    }

    savingSpinner.classList.add('hidden');
    savingCheck.classList.remove('hidden');
    savingMessage.textContent = "Saved!";

    setTimeout(() => {
      Alpine.store('modal').isOpen = false;
      e.target.reset();
      window.location.reload();
    }, 1000);

  } catch (error) {
    console.error(error);
    alert("Error saving mushroom(s): " + error.message);
    savingOverlay.classList.add('hidden');
  }
});


</script>
<script>
  // Alpine modal store
  document.addEventListener('alpine:init', () => {
    Alpine.store('modal', {
      isOpen: false
    });
  });
</script>

<?php get_footer(); ?>
