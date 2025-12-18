
<?php
/**
 * Template Name: Adventure Summary
 */
get_header();

global $wpdb;

$hunt_id = isset($_GET['hunt']) ? sanitize_text_field($_GET['hunt']) : '';
$current_user_id = get_current_user_id();

if (!$hunt_id) {
    echo "<main class='p-8'><p>Ingen adventure angiven.</p></main>";
    get_footer();
    exit;
}

// Hämta äventyr
$hunt = $wpdb->get_row(
  $wpdb->prepare("
      SELECT *
      FROM {$wpdb->prefix}mushrooms
      WHERE id = %d
      LIMIT 1
  ", intval($hunt_id))
);

if (!$hunt) {
    echo "<main class='p-8'><p>Adventure hittades inte.</p></main>";
    get_footer();
    exit;
}

// Kontrollera om inloggad användare är ägaren
$can_edit = $current_user_id && ($hunt->user_id == $current_user_id);
$is_editing = isset($_GET['edit']) && $can_edit;

// -------------------------------------------
// FOTO MÅSTE SKAPAS TIDIGT!
// -------------------------------------------
$photo_raw = $hunt->photo_url;
$photo_urls = json_decode($photo_raw, true);

if (!is_array($photo_urls)) {
    $photo_urls = !empty($photo_raw) ? [$photo_raw] : [];
}

// FÖRSTA BILDEN
$main_photo = !empty($photo_urls) ? $photo_urls[0] : '';


// -------------------------------------------
// PARSE TYPES
// -------------------------------------------
function mk_parse_types($types_raw, $fallback_type = null, $fallback_kg = null) {
    $result = [];
    $max = 0;
    if (!empty($types_raw)) {
        $trimmed = trim($types_raw);
        if (str_starts_with($trimmed, '{') && str_ends_with($trimmed, '}')) {
            $decoded = json_decode($trimmed, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                foreach ($decoded as $k => $v) {
                    $result[$k] = floatval($v);
                    $max = max($max, floatval($v));
                }
                return [$result, $max];
            }
        }
    }
    if (!empty($fallback_type)) {
        $decoded = json_decode($fallback_type, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            foreach ($decoded as $k => $v) {
                $result[$k] = floatval($v);
                $max = max($max, floatval($v));
            }
        } else {
            $result[$fallback_type] = floatval($fallback_kg);
            $max = floatval($fallback_kg);
        }
    }
    return [$result, $max];
}

list($totals_by_type, $max_kg) = mk_parse_types($hunt->types ?? '', $hunt->type ?? null, $hunt->kilograms ?? null);
arsort($totals_by_type);

// TOTAL VIKT
$total_weight = array_sum($totals_by_type);

// Mushroom icons
$mushroom_icons = [
  'Chanterelles' => home_url('/wp-content/themes/jsp/images/chant.jpeg'),
  'Funnel Chanterelles' => home_url('/wp-content/themes/jsp/images/funnel.jpeg'),
  'Boletus' => home_url('/wp-content/themes/jsp/images/bolete.jpeg'),
  'Trumpets' => home_url('/wp-content/themes/jsp/images/trumpet.jpeg'),
];
$default_icon = home_url('/wp-content/themes/jsp/images/default.jpg');

$user_data = get_userdata($hunt->user_id ?? 0);


?>

<style>
.fb-arrow {
    background: rgba(0,0,0,0.4);
    color: white;
    width: 40px;
    height: 40px;
    border-radius: 9999px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    cursor: pointer;
    transition: background 0.2s;
}
.fb-arrow:hover {
    background: rgba(0,0,0,0.6);
}

@supports not (aspect-ratio: 1 / 1) {
  .aspect-square {
    position: relative;
    padding-top: 100%;
  }
}
</style>

<main class="w-full min-h-screen bg-[#ECECE9] flex justify-center">

<div class="w-full max-w-[1600px] flex flex-col lg:flex-row">
    <!-- LEFT SIDE - BIG MAIN PHOTO -->
          <div class="w-full lg:w-3/4 flex justify-center order-2 lg:order-1">
             <?php
                // MAIN PHOTO / SLIDESHOW - replace previous block with this
                $photos_json = htmlspecialchars( json_encode(array_values($photo_urls)), ENT_QUOTES, 'UTF-8' );
                $first_photo = $main_photo ?: ( !empty($photo_urls) ? $photo_urls[0] : '' );
                ?>
                 <?php if (!empty($photo_urls)): ?>
            <div
            x-data="{
                photos: <?= $photos_json ?>,
                index: 0,
                next() { this.index = (this.index + 1) % this.photos.length },
                prev() { this.index = (this.index - 1 + this.photos.length) % this.photos.length },
                onKey(e) {
                    if (e.key === 'ArrowLeft') this.prev();
                    if (e.key === 'ArrowRight') this.next();
                }
            }"
            x-init="$el.addEventListener('keydown', (e) => onKey(e));"
            x-cloak
            tabindex="0"
            class="w-full lg:h-screen h-auto flex items-center justify-center select-none relative outline-none group"
        >
            <!-- CLOSE BUTTON -->
            <button
                @click="$store.adventureModal.close()"
                class="absolute top-4 left-4 w-10 h-10 flex items-center justify-center 
                    bg-[#1E2330] hover:bg-black/70 rounded-full text-white shadow-lg z-50 hidden lg:block"
                aria-label="Close"
            >
                <i class="fas fa-times text-lg"></i>
            </button>

            <!-- MAIN IMAGE -->
            <div class="w-full h-full flex items-center justify-center overflow-hidden">
                <img
                    src="<?= esc_url($first_photo) ?>"
                    x-bind:src="photos[index]"
                    alt="Adventure photo"
                    class="max-h-[100vh] max-w-full object-contain transition-all duration-300"
                    loading="lazy"
                >
            </div>

            <!-- LEFT ARROW -->
            <button
                @click="prev()"
                class="fb-arrow absolute left-4 top-1/2 -translate-y-1/2 opacity-0 group-hover:opacity-100 transition-opacity duration-300"
                style="-webkit-backface-visibility: hidden;"
                aria-label="Previous photo"
            >
                <i class="fas fa-chevron-left text-xl"></i>
            </button>

            <!-- RIGHT ARROW -->
            <button
                @click="next()"
                class="fb-arrow absolute right-4 top-1/2 -translate-y-1/2 opacity-0 group-hover:opacity-100 transition-opacity duration-300"
                style="-webkit-backface-visibility: hidden;"
                aria-label="Next photo"
            >
                <i class="fas fa-chevron-right text-xl"></i>
            </button>

            <!-- DOTS -->
            <div class="absolute bottom-4 flex gap-2">
                <button
                    x-for="(p,i) in photos" :key="i"
                    @click="index = i"
                    class="w-3 h-3 rounded-full focus:outline-none"
                    :class="index === i ? 'bg-white' : 'bg-white/40'"
                    :aria-label="`Go to photo ${i+1}`"
                ></button>
            </div>
        </div>


              <?php endif; ?>
        </div>
      

         <!-- RIGHT SIDE -->
              <div class="w-full lg:w-[32%] bg-white px-8 pt-8 pb-4 lg:pb-0
                lg:sticky lg:bottom-0
                order-1 lg:order-2">

                <!-- MOBILE BACK BUTTON -->
            <div class="lg:hidden fixed top-0 left-0 z-[9999]
                        mt-[calc(env(safe-area-inset-top)+0px)]        
                        w-full  flex items-center bg-white
                        dark">    
                <button
                    @click="$store.adventureModal.close()"
                    class="
                         ml-3 w-10 h-10 flex items-center justify-center
                        dark"
                >
                    <i class="fas fa-chevron-left text-lg"></i>
                </button>
            </div>
            <!-- HEADER -->
            <div class="flex space-between w-full pt-6 lg:pt-0"> 
              <?php 
                $avatar = mk_get_user_avatar($hunt->user_id);
                $username = get_userdata($hunt->user_id)->display_name;
                ?>

                <div class="flex items-center justify-between w-full mb-4">

                    <!-- LEFT: AVATAR + USERNAME -->
                    <div class="flex items-center gap-3">
                        <img src="<?= esc_url($avatar) ?>"
                            class="w-12 h-12 rounded-full border border-gray-300 object-cover">

                        <div>
                            <div class="font-semibold text-sm dark">
                                <?= esc_html($username) ?>
                            </div>
                            <div class="text-xs text-gray-500">
                                <?= esc_html(date('d M Y', strtotime($hunt->start_date))) ?>
                            </div>
                        </div>
                    </div>

            <!-- RIGHT: DROPDOWN MENU -->
            <div class="relative" x-data="{ menuOpen:false }">
                <button @click="menuOpen = !menuOpen"
                        class="w-10 h-10  rounded-full flex items-center justify-center">
                    <i class="fas fa-ellipsis-h text-xl"></i>
                </button>

                <div x-show="menuOpen"
                    @click.away="menuOpen = false"
                    x-transition
                    class="absolute right-0 top-12 bg-white rounded-xl shadow-lg w-40 overflow-hidden z-50 border">

                    <ul class="text-sm text-gray-800">  
                        <li>
                        <?php
                            list($totals_by_type, $max_kg) = mk_parse_types($hunt->types ?? '', $hunt->type ?? null, $hunt->kilograms ?? null);
                            arsort($totals_by_type);

                            $adventure_data = [
                                'id' => (int) $hunt->id,
                                'username' => $username,
                                'location' => $hunt->location,
                                'date' => $hunt->start_date,
                                'adventure_text' => $hunt->adventure_text,
                                'types' => $totals_by_type, // ✅ skicka korrekt array
                                'photos' => json_decode($hunt->photo_url ?? '[]', true),
                                'kilograms' => floatval($hunt->kilograms ?? 0),
                            ];

                            $adventure_json = wp_json_encode($adventure_data, JSON_HEX_APOS | JSON_HEX_QUOT);
                            $adventure_json_esc = htmlspecialchars($adventure_json, ENT_QUOTES, 'UTF-8');
                            ?>
                            <button
                                @click="
                                    $store.editAdventureModal.adventure = JSON.parse('<?= $adventure_json_esc ?>');
                                    $store.editAdventureModal.open = true;
                                    $store.editAdventureModal.loadFromStore();
                                "
                                class="block w-full text-left px-4 py-3 hover:bg-gray-100">
                                Edit Adventure
                            </button>
                        </li>   
                    </ul>
                </div>
            </div>
        </div>
    </div>

    
        <!-- ADVENTURE TEXT -->
        <div class="mt-4 leading-relaxed text-gray-800 text-[15px]">
            <h1 class="leading-tight dark text- font-semibold mb-2 text-3xl gilroy">
                <?= esc_html($hunt->location) ?>
            </h1>
            <span class="text-base"><?= wp_kses_post($hunt->adventure_text) ?></span>
        </div>

        <!-- MUSHROOM LIST -->
        <div class="mt-10">
            <div class="dark font-semibold text-sm mb-3">Mushrooms</div>
                <div class="space-y-4">
                    <?php foreach($totals_by_type as $type => $kg): ?>
                        <div class="flex items-center justify-between bg-white p-4 rounded-xl bg-[#ECECE9]">

                            <div class="flex items-center gap-3">
                                <span class="font-semibold text-sm"><?= esc_html($type) ?></span>      
                            </div>
                            <span class="text-sm font-semibold">
                                <?= rtrim(rtrim(number_format($kg,2,'.',''),'0'),'.') ?> kg
                            </span>
                    
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- <div class="flex justify-between pt-2 text-gray-600 text-sm px-4">
                    <span class="text-xs">Total weight</span>
                    <span><?= $total_weight ?> kg</span>
                </div> -->
        </div>
    </div>
</div>
</main>


<?php get_footer(); ?>
