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
/* Template Name: Mushroom Dashboard */

get_header();

global $wpdb;

// Total kilograms (for Total stat box)
$total_kg = $wpdb->get_var("SELECT SUM(kilograms) FROM {$wpdb->prefix}mushrooms");
$total_kg = $total_kg ? intval($total_kg) : 0;

// Best day (most kilograms on a single start_date)
$best_day_kg = $wpdb->get_var("
  SELECT SUM(kilograms)
  FROM {$wpdb->prefix}mushrooms
  WHERE start_date IS NOT NULL
  GROUP BY start_date
  ORDER BY SUM(kilograms) DESC
  LIMIT 1
");
$best_day_kg = $best_day_kg ? intval($best_day_kg) : 0;

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


<div class="px-6 mb-4">
  <h1 class="text-[54px] text-[#CEE027] mt-6 mb-12 gilroy leading-[58px] w-[90%]"  style="line-height: 52px">Hi, here's your mushroom stats.</h1>
</div>


<!-- Adventures Image Slider with Portrait-Style Boxes -->
<div class="mt-8 text-|#E9EED8]">
 <div class="px-6 mb-4 font-medium text-white">Recent adventures</div>
<div id="adventure-slider" class="flex overflow-x-auto no-scrollbar space-x-4 pb-4 snap-x snap-mandatory ml-6">

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
      ['class' => 'rounded-[20px]', 'size' => 'w-[330px] h-[220px]'],      // slim
      ['class' => 'rounded-[32px]', 'size' => 'w-[140px] h-[220px]'],      // soft square
      ['class' => 'rounded-[24px]', 'size' => 'w-[160px] h-[220px]'],      // shorter rectangle
      ['class' => 'rounded-[52px]', 'size' => 'w-[162px] h-[220px]'],        // perfect circle
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
        <div class="absolute bottom-4 left-4 bg-[#E8EFD6] text-[#111827] text-sm px-3 py-2 rounded-full">
          #<?= $location ?>
        </div>
      </div>
    <?php endforeach; ?>

  </div>
</div>



<div class="px-6 my-4">


 <!-- Stats Section -->
<div class="space-y-4 mb-6">
  <div class="flex fflex-col">
  <div class="my-2 font-regular font-medium text-white">Overview</div>
  <div class="my-2 underline ml-auto text-sm text-white">View all</div>
</div>
  <!-- First Row: Total + Rounds -->
  <div class="flex justify-between space-x-4">
    <!-- Total Kilograms -->
    <div class="bg-[#E8EFD6] text-white p-6 rounded-[30px] flex-1">
      <div class="flex flex-row">
        <div class="w-14 my-auto">
          <img src="<?= get_template_directory_uri(); ?>/images/graph3.svg">
        </div>
      <div class="ml-auto flex gap-1 flex-col text-center dark">
        <div class="text-sm font-medium">Total</div>
        <div class="text-3xl font-medium"><?= $total_kg ?></div>
        <div class="text-sm">Kilogram</div>
      </div>
    </div>
  </div>

    <!-- Rounds -->
<div class="bg-[#FF6C02] text-white p-6 rounded-[30px] flex-1 max-w-[120px] text-center flex gap-1 flex-col">
  <!-- Adventure icon -->
  <div class="flex justify-center">
    <i class="fas fa-hiking text-xl mb-1 dark"></i>
  </div>
  <div class="text-3xl font-medium dark"><?= $total_rounds ?></div>
  <div class="text-sm font-medium dark">Adventures</div>
</div>

  </div>

  <!-- Second Row: Locations + Best -->
  <div class="flex justify-between space-x-4">
   <!-- Locations -->
    <div class="bg-[#C637DF] text-white p-6 rounded-[30px] flex-1 max-w-[120px] text-center flex gap-1 flex-col">
      <!-- Location icon -->
      <div class="flex justify-center">
         <img class="w-[60%]" src="<?= get_template_directory_uri(); ?>/images/locations.svg">
      </div>
      <div class="text-3xl font-medium"><?= $total_locations ?></div>
      <div class="text-sm font-medium">Locations</div>
    </div>


      <!-- Best -->
      <div class="bg-[#02ACC4] text-white p-6 rounded-[30px] flex-1">
        <div class="flex text-center">
        <div class="my-auto">
          <img src="<?= get_template_directory_uri(); ?>/images/trophy2.svg" class="w-12">
        </div>
          <div class="flex flex-col gap-1 text-center ml-auto ">
            <div class="text-sm font-medium">
              Best of the day
            </div>
            <div class="text-3xl font-medium"><?= $best_day_kg ?></div>
            <div class="text-sm">Kilogram</div>
          </div>

      </div>
      </div>
  </div>
</div>


  <!-- Add Mushrooms Button -->
<div class="fixed bottom-4 w-full px-4">
  <button @click="$store.modal.isOpen = true" class="fixed bg-[#CEE027] dark text-[#111827] w-[72px] h-[72px] py-3 rounded-full font-medium text-lg right-5 bottom-8 flex justify-center items-center  shadow-xl">
    <img class="rounded-full w-6 h-6" src="<?= get_template_directory_uri(); ?>/images/add2.svg">
  </button>
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
    class="relative bg-white rounded-t-2xl w-full max-w-md mx-auto shadow-xl transition-transform duration-300 ease-in-out transform translate-y-0 flex flex-col"
    x-data="{ tab: 'chanterelles' }"
    style="max-height: 80vh;"
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
    <div class="p-6 flex-shrink-0">
      <h2 class="text-xl mb-4 gilroy">Add Mushrooms</h2>
    </div>

    <!-- Scrollable Content -->
    <div class="px-6 pb-12 overflow-y-auto flex-grow">
          <!-- Mushroom type tabs (swipable) -->
    <div class="flex overflow-x-auto no-scrollbar space-x-2 mb-4 mt-2">
        <button @click="tab = 'chanterelles'" :class="tab === 'chanterelles' ? 'bg-[#CEE027] text-[#111827]' : 'bg-[#F3F3F1] text-[#111827]'" class="flex-shrink-0 px-3 py-2 rounded-full text-sm font-semibold whitespace-nowrap">Chanterelles</button>
        <button @click="tab = 'funnel'" :class="tab === 'funnel' ? 'bg-[#CEE027] text-[#111827]' : 'bg-[#F3F3F1] text-[#111827]'" class="flex-shrink-0 px-3 py-2 rounded-full text-sm font-semibold whitespace-nowrap">Funnel Chanterelles</button>
        <button @click="tab = 'boletus'" :class="tab === 'boletus' ? 'bg-[#CEE027] text-[#111827]' : 'bg-[#F3F3F1] text-[#111827]'" class="flex-shrink-0 px-3 py-2 rounded-full text-sm font-semibold whitespace-nowrap">Boletus</button>
        <button @click="tab = 'trumpets'" :class="tab === 'trumpets' ? 'bg-[#CEE027] text-[#111827]' : 'bg-[#F3F3F1] text-[#111827]'" class="flex-shrink-0 px-3 py-2 rounded-full text-sm font-semibold whitespace-nowrap">Trumpets</button>
      </div>
      <form id="mushroom-form" class="space-y-4">

        <!-- Chanterelles -->
        <div x-show="tab === 'chanterelles'">
          <label class="block text-sm font-medium mb-2">Chanterelles (kg)</label>
          <input name="chanterelles" type="number" step="0.01" min="0" class="w-full border p-3 rounded" />
        </div>

        <!-- Funnel Chanterelles -->
        <div x-show="tab === 'funnel'">
          <label class="block text-sm font-medium mb-2">Funnel Chanterelles (kg)</label>
          <input name="funnel" type="number" step="0.01" min="0" class="w-full border p-3 rounded" />
        </div>

        <!-- Boletus -->
        <div x-show="tab === 'boletus'">
          <label class="block text-sm font-medium mb-2">Boletus (kg)</label>
          <input name="boletus" type="number" step="0.01" min="0" class="w-full border p-3 rounded" />
        </div>

        <!-- Trumpets -->
        <div x-show="tab === 'trumpets'">
          <label class="block text-sm font-medium mb-2">Trumpets (kg)</label>
          <input name="trumpets" type="number" step="0.01" min="0" class="w-full border p-3 rounded" />
        </div>

        <!-- Photo Upload -->
        <div>
          <label class="block text-sm font-medium mb-2">Upload Photo (optional)</label>
          <div id="upload_box" class="flex flex-col items-center justify-center border-2 border-dashed border-gray-300 rounded-xl p-6 bg-gray-50 text-center">
            <label for="mushroom_photo" class="cursor-pointer flex flex-col items-center space-y-2">
              <div class="w-16 h-16 flex items-center justify-center bg-gray-200 rounded-full">
                <i class="fas fa-camera text-2xl text-gray-600"></i>
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
          <label class="block text-sm font-medium mb-2">Location (optional)</label>
          <input name="location" type="text" class="w-full border p-3 rounded" placeholder="Where did you find them?" />
        </div>

        <!-- Start Date -->
        <div>
          <label class="block text-sm font-medium mb-2">Start Date (optional)</label>
          <input name="start_date" type="date" class="w-full border p-3 rounded" />
        </div>

      </form>
    </div>

    <!-- Fixed Footer with Save button -->
    <div class="p-6 flex-shrink-0 border-t bg-white">
      <button type="submit" form="mushroom-form" class="bg-black text-white w-full py-3 rounded-full">Save</button>
    </div>

  </div>
</div>

  </div>
</div>
<!-- Mushroom Type Stats -->
<div class="bg-[#CEE027] p-6 py-12 mt-12 ">
  <h2 class="text-xl gilroy mb-4 dark">By mushroom type</h2>
  <div class="space-y-4">
    <?php foreach ($mushroom_types as $m):
      $percentage = $max_kg > 0 ? ($m->total_kg / $max_kg) * 100 : 0;
    ?>
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-2">
          <!-- Optional emoji/icons -->
          <span class="dark"><?= esc_html($m->type) ?></span>
        </div>
        <div class="dark font-medium"><?= intval($m->total_kg) ?> kg</div>
      </div>
   <!--  <div class="w-full h-3 bg-gray-200 rounded-full overflow-hidden">
        <div class="h-full bg-[#E9C0EA] rounded-full transition-all duration-300" style="width: <?= $percentage ?>%;"></div>
      </div> -->
    <?php endforeach; ?>
  </div>
</div>


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

  const formData = new FormData(e.target);
  const location = formData.get('location');
  const photoFile = formData.get('mushroom_photo');
  const startDate = formData.get('start_date');

const entries = [
  { type: 'Chanterelles', value: formData.get('chanterelles') },
  { type: 'Funnel Chanterelles', value: formData.get('funnel') },
  { type: 'Boletus', value: formData.get('boletus') },
  { type: 'Trumpets', value: formData.get('trumpets') } // ← Add this line
];

  for (const entry of entries) {
    const kg = parseFloat(entry.value);
    if (!isNaN(kg) && kg > 0) {
      const mushroomData = new FormData();
      mushroomData.append('action', 'add_mushroom');
      mushroomData.append('type', entry.type);
      mushroomData.append('kilograms', kg);
      mushroomData.append('start_date', startDate);
      mushroomData.append('location', location);
      if (photoFile && photoFile.size > 0) {
        mushroomData.append('mushroom_photo', photoFile);
      }

      await fetch("<?= admin_url('admin-ajax.php'); ?>", {
        method: 'POST',
        body: mushroomData
      });
    }
  }

  alert("Mushroom(s) added!");
  e.target.reset();
  location.reload();
});


</script>
<script>
  document.addEventListener('alpine:init', () => {
    Alpine.store('modal', {
      isOpen: false
    });
  });
</script>




<?php get_footer(); ?>
