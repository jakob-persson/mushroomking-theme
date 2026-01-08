    <?php
    /**
     * The header for our theme
     *
     * This is the template that displays all of the <head> section and everything up until <div id="content">
     *
     * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
     *
     * @package WordPress
     * @subpackage Twenty_Seventeen
     * @since 1.0
     * @version 1.0
     */

    ?>
    <!DOCTYPE html>
    <html <?php language_attributes(); ?> class="no-js no-svg">

    <head>
      <meta charset="<?php bloginfo('charset'); ?>">
      <link rel="icon" type="image/svg+xml" href="<?php echo esc_url(get_template_directory_uri()); ?>/images/favicon.svg">
      <script src="https://cdn.tailwindcss.com"></script>
      <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
      <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
      <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

      <!-- <script src="https://cdn.tiny.cloud/1/2dc6eoughgw3zqdzbstj8cso24vdek5p8ennhvcm4sfi7y0h/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script> -->

      <?php wp_head(); ?>
    </head>
    <script>
      document.addEventListener('alpine:init', () => {
        Alpine.store('editAdventureModal', {
          adventure: {},
          open: false,
          close() {
            this.open = false;
          },
          openModal(data) {
            this.adventure = data;
            this.open = true;
          }
        });
      });
    </script>



    <script>
      document.addEventListener("DOMContentLoaded", function() {
        tinymce.init({
          selector: '#adventure_text',
          menubar: false,
          toolbar: 'bold italic underline | bullist numlist | link | undo redo',
          placeholder: 'Write about your mushroom adventure...',
          height: 200
        });
      });
    </script>
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        const photoInput = document.getElementById('mushroom_photo');
        const previewImg = document.getElementById('preview_img');
        const previewContainer = document.getElementById('image_preview');
        const removeButton = document.getElementById('remove_preview');

        // Show preview when image is selected
        photoInput.addEventListener('change', function(event) {
          const file = event.target.files[0];
          if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
              previewImg.src = e.target.result;
              previewContainer.classList.remove('hidden');
            };
            reader.readAsDataURL(file);
          } else {
            previewContainer.classList.add('hidden');
            previewImg.src = '';
          }
        });

        // Remove preview and clear input
        removeButton.addEventListener('click', function() {
          photoInput.value = ''; // Clear file input
          previewImg.src = '';
          previewContainer.classList.add('hidden');
        });
      });
    </script>

    <script>
      document.addEventListener('DOMContentLoaded', function() {
        const fileInput = document.getElementById('mushroom_photo');
        const uploadBox = document.getElementById('upload_box');
        const previewContainer = document.getElementById('image_preview');
        const previewImage = document.getElementById('preview_img');
        const removeButton = document.getElementById('remove_preview');

        fileInput.addEventListener('change', function() {
          const file = this.files[0];
          if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
              previewImage.src = e.target.result;
              uploadBox.style.display = 'none';
              previewContainer.style.display = 'block';
            };
            reader.readAsDataURL(file);
          }
        });

        removeButton.addEventListener('click', function() {
          fileInput.value = '';
          previewImage.src = '';
          uploadBox.style.display = 'flex';
          previewContainer.style.display = 'none';
        });
      });
    </script>
    <script>
      document.addEventListener("DOMContentLoaded", () => {
        const container = document.getElementById("tilt-container");
        const image = container.querySelector("img");

        let targetX = 0,
          targetY = 0;
        let currentX = 0,
          currentY = 0;
        let isHovering = false;

        const animate = () => {
          // Smooth easing toward target position
          currentX += (targetX - currentX) * 0.1;
          currentY += (targetY - currentY) * 0.1;

          if (isHovering) {
            image.style.transform = `rotateX(${currentY}deg) rotateY(${currentX}deg) scale(1.05)`;
          } else {
            image.style.transform = `rotateX(${currentY}deg) rotateY(${currentX}deg) scale(1)`;
          }

          requestAnimationFrame(animate);
        };

        container.addEventListener("mousemove", (e) => {
          const rect = container.getBoundingClientRect();
          const x = e.clientX - rect.left;
          const y = e.clientY - rect.top;

          // Tilt range: max ±15 degrees
          targetX = ((x / rect.width) - 0.5) * -30;
          targetY = ((y / rect.height) - 0.5) * 30;
        });

        container.addEventListener("mouseenter", () => {
          isHovering = true;
        });

        container.addEventListener("mouseleave", () => {
          isHovering = false;
          targetX = 0;
          targetY = 0;
        });

        animate();
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
    <script>
      document.addEventListener('alpine:init', () => {
        Alpine.store('editProfileModal', {
          isOpen: false
        });
      });
    </script>
    <?php
    // Check if current page uses the adventure summary template
    $is_adventure_template = function_exists('is_page_template') ? is_page_template('page-adventure-summary.php') : false;

    // Optional: check for ?hunt= query param
    $has_hunt_param = isset($_GET['hunt']) && !empty($_GET['hunt']);
    ?>
    <?php
    // Default extra classes
    $extra_classes = '';

    // Front page AND user logged in → add custom class
    if (is_front_page() && is_user_logged_in()) {
      $extra_classes .= ' logged-in-frontpage bg-[#EFF0EC]';
    }
    // Page templates / other conditions
    elseif (is_page_template('page-login.php')) {
      $extra_classes .= '';
    } elseif (is_page_template('page-register.php')) {
      $extra_classes .= ' bg-[#EDEDED]';
    } elseif (is_page_template('page-stats.php')) {
      $extra_classes .= ' bg-[#1E2330]';
    } elseif (is_page_template('edit-profile.php')) {
      $extra_classes .= ' bg-[#F9F9F9]';
    } elseif ($is_adventure_template || $has_hunt_param) {
      $extra_classes .= ' bg-[#ffffff]';
    } elseif (get_query_var('current_user_obj')) {
      $extra_classes .= ' bg-[#F3F3F1]';
    } else {
      $extra_classes .= ' bg-[#124C12]';
    }
    ?>

    <body <?php body_class("template-login $extra_classes"); ?> x-data>
      <!-- Header -->

      <?php if (
        !is_page_template('page-login.php')
        && !is_page_template('page-register.php')
        && !is_page('forgot-password')
        && !is_page('insights')
        && !is_page('edit-profile')
        && !is_page_template('page-adventure-summary.php')
      ): ?>

        <!-- ✅ Sticky Header Wrapper -->
        <div
          x-data="{ isSticky: false }"
          x-init="window.addEventListener('scroll', () => { isSticky = window.scrollY > 200 })"
          class=" relative z-10">


          <!-- ✅ Sticky Header -->
          <div :class="isSticky ? 'fixed top-0 left-0 right-0 z-50' : 'z-50'"
            class="transition-all duration-300 lg:pt-4">
            <div class="section-wrapper">
              <div class="flex items-center justify-between rounded-full bg-white py-3 lg:py-4 lg:mb-0 lg:mb-0 pl-8 mx-auto w-full pr-6">

                <!-- Left Side: Logo + Nav -->
                <div class="flex items-center">
                  <div class="w-[28px] lg:w-[62px] lg:border-r lg:border-[#111827] lg:pr-4">
                    <a href="<?php echo home_url(); ?>">
                      <img src="<?php echo get_template_directory_uri(); ?>/images/mk-logo2.png" alt="Logo">
                    </a>
                  </div>
                  <span class="hidden lg:block ml-4 text-sm font-medium">Beta</span>

                  <!-- ✅ Desktop Menu -->
                  <nav class="hidden lg:flex space-x-10 ml-16 text-sm">
                    <a href="<?php echo esc_url(home_url('/how-it-works')); ?>" class="hover:text-[#124C12] transition">How it work's</a>
                    <a href="<?php echo esc_url(home_url('/how-it-works')); ?>" class="hover:text-[#124C12] transition">Get started</a>
                  </nav>
                </div>


                <div class="flex items-center space-x-2.5 lg:space-x-4 lg:space-x-6">
                  <!-- Desktop Find Foragers -->
                  <?php if (is_user_logged_in()): ?>
                    <div x-data="userSearch()" class="relative max-w-5xl mx-auto hidden lg:block">
                      <input
                        type="text"
                        placeholder="Find foragers"
                        x-model="searchQuery"
                        @focus="isFocused = true"
                        @blur="setTimeout(() => isFocused = false, 200)"
                        class="w-full px-4 py-3 bg-white border border-[#eff0ec] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#1E2330] focus:border-[#1E2330]"
                        style="background: white!important" />

                      <ul
                        x-show="shouldShowList()"
                        x-transition
                        class="absolute left-0 w-full mt-2 bg-white space-y-3 max-h-[300px] overflow-y-auto border border-gray-200 rounded-md shadow-lg z-50 p-4">
                        <?php
                        $users = get_users(['fields' => ['ID', 'user_login', 'display_name']]);

                        // Sort alphabetically by display_name
                        usort($users, function ($a, $b) {
                          return strcasecmp($a->display_name, $b->display_name);
                        });

                        foreach ($users as $user):
                          $user_obj = get_userdata($user->ID); // Ensure we have WP_User
                          if (!$user_obj) continue;

                          $user_id = $user_obj->ID;
                          $display_name = esc_html($user_obj->display_name);
                          $slug = $user_obj->user_nicename;
                          $profile_url = home_url("/$slug");

                          // ✅ Use uploaded profile image if available, fallback to Gravatar
                          $avatar = mk_get_user_avatar($user_id);


                        ?>
                          <li x-show="matches('<?php echo strtolower($display_name); ?>')" class="flex items-center gap-2 hover:bg-gray-100 rounded-md">
                            <img src="<?php echo esc_url($avatar); ?>" class="w-10 h-10 rounded-full object-cover" alt="<?php echo $display_name; ?>" />
                            <a href="<?php echo esc_url($profile_url); ?>" class="dark regular text-sm hover:underline"><?php echo $display_name; ?></a>
                          </li>
                        <?php endforeach; ?>
                      </ul>
                    </div>


                    <!-- Mobile Search Icon and Modal -->
                    <div class="lg:hidden" x-data="mobileSearch()">
                      <button @click="open = true" class="p-3 rounded-full bg-[#eff0ec]">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                      </button>

                      <!-- Fullscreen Modal -->
                      <div x-show="open" x-transition class="fixed inset-0 bg-white flex flex-col" style="z-index:9999;">

                        <!-- Header: back button + search -->
                        <div class="flex items-center p-3 space-x-3 border-b">
                          <button @click="open = false" class="p-1">
                            <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                            </svg>
                          </button>
                          <input
                            type="text"
                            placeholder="Find foragers"
                            x-model="searchQuery"
                            @keydown.escape="open = false"
                            class="flex-1 bg-gray-100 rounded-full px-4 py-2 focus:outline-none" />
                        </div>

                        <!-- Suggestions Title -->
                        <div class="px-4 py-2 text-gray-500 font-semibold text-sm">Förslag</div>

                        <!-- Filtered Users List -->
                        <ul class="flex-1 overflow-y-auto divide-y divide-gray-100">
                          <template x-for="user in filteredUsers" :key="user.url">
                            <li class="flex items-center px-4 py-3 hover:bg-gray-100">
                              <!-- Avatar -->
                              <img :src="user.avatar" class="w-10 h-10 rounded-full object-cover" :alt="user.display_name" />

                              <!-- User Info -->
                              <div class="ml-3">
                                <a :href="user.url" class="block text-gray-900 font-medium" x-text="user.display_name"></a>
                                <p class="text-gray-500 text-sm" x-text="user.subtitle"></p>
                              </div>
                            </li>
                          </template>
                        </ul>
                      </div>
                    </div>
                  <?php endif; ?>


                  <!-- AlpineJS Component -->
                  <script>
                    function mobileSearch() {
                      let users = <?php
                                  echo json_encode(array_map(function ($user) {
                                    $user_obj = get_userdata($user->ID); // ✅ ensure WP_User object
                                    if (!$user_obj) return null;

                                    // ✅ Use helper function for avatar
                                    $avatar = mk_get_user_avatar($user_obj->ID);

                                    $display_name = esc_html($user_obj->display_name);
                                    $slug = $user_obj->user_nicename; // ✅ correct slug
                                    return [
                                      "display_name" => $display_name,
                                      "avatar" => $avatar,
                                      "url" => home_url("/$slug")
                                    ];
                                  }, get_users(["fields" => ["ID"]])));
                                  ?>;

                      // Remove any nulls if user_obj was invalid
                      users = users.filter(u => u !== null);

                      // Sort users alphabetically
                      users.sort((a, b) => a.display_name.toLowerCase().localeCompare(b.display_name.toLowerCase()));

                      return {
                        open: false,
                        searchQuery: "",
                        users: users,
                        get filteredUsers() {
                          if (this.searchQuery.length === 0) return this.users;
                          return this.users.filter(u =>
                            u.display_name.toLowerCase().startsWith(this.searchQuery.toLowerCase())
                          );
                        }
                      }
                    }
                  </script>



                  <!-- Desktop button -->
                  <?php if (is_user_logged_in()): ?>
                    <!-- Logged-in user: Show Add Adventure button -->
                    <button
                      @click="$store.modal.isOpen = true"
                      class="lg:flex items-center gap-2 bg-[#eff0ec] text-[#111827] px-4 py-4 rounded-xl font-medium text-sm">
                      <svg class="w-4 w-4" version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
                        viewBox="0 0 24 24" style="enable-background:new 0 0 24 24;" xml:space="preserve">
                        <style type="text/css">
                          .st0 {
                            fill: #111827;
                          }
                        </style>
                        <path class="st0" d="M10.5,20c0,0.4,0.2,0.8,0.4,1.1c0.3,0.3,0.7,0.4,1.1,0.4s0.8-0.2,1.1-0.4c0.3-0.3,0.4-0.7,0.4-1.1v-6.5H20
                    c0.4,0,0.8-0.2,1.1-0.4c0.3-0.3,0.4-0.7,0.4-1.1s-0.2-0.8-0.4-1.1c-0.3-0.3-0.7-0.4-1.1-0.4h-6.5V4c0-0.4-0.2-0.8-0.4-1.1
                    c-0.3-0.3-0.7-0.4-1.1-0.4s-0.8,0.2-1.1,0.4c-0.3,0.3-0.4,0.7-0.4,1.1v6.5H4c-0.4,0-0.8,0.2-1.1,0.4c-0.3,0.3-0.4,0.7-0.4,1.1
                    s0.2,0.8,0.4,1.1c0.3,0.3,0.7,0.4,1.1,0.4h6.5V20z" />
                      </svg>

                      <span class="hidden lg:flex">Add adventure</span>
                    </button>
                  <?php else: ?>
                    <!-- Guest user: Show Create Account button -->
                    <a
                      href="mk/register"
                      class="hover:opacity-90 items-center gap-2 bg-[#eff0ec] text-[#111827] px-4 py-4 rounded-xl font-medium text-xs lg:text-sm">
                      <span>Create Account</span>
                    </a>
                  <?php endif; ?>

                  <?php if (is_user_logged_in()): ?>
                    <!-- Logged in: Show Stats button -->
                    <a
                      href="<?php echo esc_url(home_url('/insights')); ?>"
                      class="hover:opacity-90 hidden lg:flex items-center gap-2 bg-[#CEE027] text-[#111827] px-5 py-4 rounded-xl font-medium text-sm">
                      <i class="fas fa-chart-line text-sm"></i>
                      <span>Insights</span>
                    </a>
                  <?php else: ?>
                    <!-- Not logged in: Show Login button -->
                    <a
                      href="mk/login"
                      class="hover:opacity-90 lg:flex items-center gap-2 bg-[#111827] text-white px-4 py-4 rounded-full font-medium text-xs lg:text-sm">
                      <span>Log in</span>
                    </a>
                  <?php endif; ?>
                  <!-- Mobile button -->

                  <?php if (is_user_logged_in()):
                    $user_id = get_current_user_id();
                    $user = get_userdata($user_id);
                    $display_name = esc_html($user->display_name);
                    $user_slug = $user->user_nicename;
                    $profile_url = home_url("/$user_slug");

                    // Get uploaded profile image URL
                    $custom_avatar_id = get_user_meta($user_id, 'profile_image', true);
                    $avatar_url = $custom_avatar_id ? wp_get_attachment_url($custom_avatar_id) : get_avatar_url($user_id);
                  ?>
                    <div x-data="{ open: false }" class="relative w-10 h-10">
                      <!-- Avatar + Chevron Toggle -->
                      <button @click="open = !open" class="relative w-10 h-10 focus:outline-none">
                        <!-- Avatar -->
                        <?php
                        $avatar_url = mk_get_user_avatar($user_id);
                        ?>
                        <img src="<?php echo esc_url($avatar_url); ?>" class="w-10 h-10 rounded-full object-cover">


                        <!-- Yellow chevron badge -->
                        <span
                          class="absolute -bottom-1 -right-1 w-5 h-5 bg-[#CEE027] rounded-full flex items-center justify-center text-[#111827] shadow-sm">
                          <i class="fas fa-chevron-down text-[10px] transition-transform duration-200"
                            :class="open ? 'rotate-180' : ''"></i>
                        </span>
                      </button>

                      <!-- Dropdown -->
                      <div x-show="open" @click.outside="open = false" x-transition
                        class="absolute right-0 mt-8 w-64 bg-white rounded-xl shadow-lg z-50 overflow-hidden
                              sm:right-0 sm:mt-3 sm:w-64
                              max-sm:fixed max-sm:top-[72px] max-sm:right-4 max-sm:w-[90%]">

                        <!-- User Info -->
                        <div class="flex items-center p-4 border-b">
                          <img src="<?php echo esc_url($avatar_url); ?>" class="w-12 h-12 rounded-full object-cover">
                          <div class="ml-3">
                            <p class="font-semibold text-gray-800"><?php echo esc_html($user->display_name); ?></p>
                            <a href="<?= esc_url($profile_url); ?>" class="text-sm text-purple-600 hover:underline">View Profile</a>
                          </div>
                        </div>

                        <!-- Menu Items -->
                        <div class="py-2">
                          <a href="/settings" class="block px-4 py-2 dark hover:bg-gray-100">Settings</a>
                          <?php if (is_user_logged_in() && get_current_user_id() === $user->ID): ?>
                            <button
                              @click="$store.editProfileModal.isOpen = true"
                              class="px-4 py-2 rounded-xl bg-black text-white">
                              Edit Profile
                            </button>
                          <?php endif; ?>
                          <a href="<?php echo wp_logout_url(home_url('/')); ?>"
                            class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Logout</a>
                        </div>
                      </div>
                    </div>


                  <?php endif; ?>


                  <!-- ✅ Mobile Hamburger -->
                  <div x-data="{ open: false }" class="lg:hidden relative">
                    <!-- Toggle Button -->
                    <button
                      @click="open = !open"
                      class="px-2 rounded-md relative z-50"
                      :class="open ? 'bg-[#CEE027] fixed' : ''">
                      <!-- Hamburger -->
                      <svg x-show="!open" x-cloak class="w-6 h-6 text-[#111827]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                      </svg>
                      <!-- Close -->
                      <svg x-show="open" x-cloak class="w-6 h-6 text-[#111827]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                      </svg>
                    </button>

                    <!-- ✅ Fullscreen Menu -->
                    <div
                      x-show="open"
                      x-cloak
                      x-transition:enter="transition ease-out duration-300"
                      x-transition:enter-start="opacity-0 translate-x-full"
                      x-transition:enter-end="opacity-100 translate-x-0"
                      x-transition:leave="transition ease-in duration-200"
                      x-transition:leave-start="opacity-100 translate-x-0"
                      x-transition:leave-end="opacity-0 translate-x-full"
                      class="fixed inset-0 bg-white z-40 flex flex-col p-8">

                      <div class="absolute left-8 top-[44px] text-sm font-regular">Menu</div>

                      <!-- Navigation -->
                      <nav class="flex flex-col space-y-8 text-2xl font-bold mt-20">
                        <a href="" class="hover:text-[#124C12] transition">How it work's</a>
                        <a href="" class="hover:text-[#124C12] transition">Get started</a>

                      </nav>

                      <!-- Auth Buttons -->
                      <div class="mt-auto space-y-4">
                        <?php if (is_user_logged_in()): ?>
                          <a href="mk/insights" class="block text-center bg-[#CEE027] text-[#111827] py-4 rounded-xl font-semibold">Insights</a>

                        <?php else: ?>
                          <a href="/mk/login" class="block text-center bg-[#eff0ec] text-[#111827] py-4 rounded-xl">Log in</a>
                          <a href="/mk/register" class="block text-center bg-[#111827] text-white py-4 rounded-xl">Sign up free</a>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>



        </div>
        </div>
        </div>
        </div>

        <!-- ✅ Spacer to prevent layout shift when sticky -->
        <div :class="isSticky ? 'h-[100px]' : ''"></div>
      <?php endif; ?>
      </div>
      <script>
        function userSearch() {
          return {
            searchQuery: '',
            isFocused: false,
            shouldShowList() {
              return this.isFocused;
            },
            matches(name) {
              // Match only from the start of the name (case-insensitive)
              return name.toLowerCase().startsWith(this.searchQuery.toLowerCase());
            }
          }
        }
      </script>