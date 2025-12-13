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

?><!DOCTYPE html>
<html <?php language_attributes(); ?> class="no-js no-svg">
<head>
  <meta charset="<?php bloginfo( 'charset' ); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
  <link rel="icon" type="image/svg+xml" href="<?php echo esc_url( get_template_directory_uri() ); ?>images/favicon-32x32.png">
  <script src="https://cdn.tailwindcss.com"></script>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <?php wp_head(); ?>
</head>

<style type="text/css">


</style>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const photoInput = document.getElementById('mushroom_photo');
    const previewImg = document.getElementById('preview_img');
    const previewContainer = document.getElementById('image_preview');
    const removeButton = document.getElementById('remove_preview');

    // Show preview when image is selected
    photoInput.addEventListener('change', function (event) {
      const file = event.target.files[0];
      if (file && file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = function (e) {
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
    removeButton.addEventListener('click', function () {
      photoInput.value = ''; // Clear file input
      previewImg.src = '';
      previewContainer.classList.add('hidden');
    });
  });
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const fileInput = document.getElementById('mushroom_photo');
  const uploadBox = document.getElementById('upload_box');
  const previewContainer = document.getElementById('image_preview');
  const previewImage = document.getElementById('preview_img');
  const removeButton = document.getElementById('remove_preview');

  fileInput.addEventListener('change', function () {
    const file = this.files[0];
    if (file && file.type.startsWith('image/')) {
      const reader = new FileReader();
      reader.onload = function (e) {
        previewImage.src = e.target.result;
        uploadBox.style.display = 'none';
        previewContainer.style.display = 'block';
      };
      reader.readAsDataURL(file);
    }
  });

  removeButton.addEventListener('click', function () {
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

  let targetX = 0, targetY = 0;
  let currentX = 0, currentY = 0;
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


<?php
if (is_page_template('page-login.php')) {
    $extra_classes = '';
} elseif (is_page_template('page-stats.php')) {
    $extra_classes = 'bg-[#1E2330]'; // Tailwind red background (or use 'bg-[#ff0000]' for custom red)
} else {
    $extra_classes = 'bg-[#124C12]'; // Default background
}
?>
<body <?php body_class("template-login $extra_classes"); ?> x-data>
<!-- Header -->
<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

<?php if (
  !is_page_template('page-login.php')
  && !is_page('forgot-password')
  && !is_page('insights')
): ?>

<!-- ✅ Sticky Header Wrapper -->
<div
  x-data="{ isSticky: false }"
  x-init="window.addEventListener('scroll', () => { isSticky = window.scrollY > 200 })"
  class=" relative z-10"
>

  <!-- ✅ Sticky Header -->
  <div :class="isSticky ? 'fixed top-0 left-0 right-0' : ''" class="transition-all duration-300 lg:px-16 lg:pt-4">
    <div class="p-4">
      <div class="flex items-center justify-between rounded-full px-4 bg-white py-3 lg:py-4 mb-6 lg:mb-0 pl-8">
       <div class="flex items-center">
          <div class="w-[52px] lg:w-[62px] lg:border-r lg:border-[#111827] pr-4">
            <img src="<?php echo get_template_directory_uri(); ?>/images/mk-logo2.png" alt="Logo">
          </div>
          <span class="hidden lg:block ml-4 text-sm dark font-medium">Track your stats</span>
          <!-- ✅ Desktop Menu -->
            <nav class="hidden lg:flex space-x-10 ml-16 text-sm  dark">
              <a href="/adventures" class="hover:text-[#124C12] transition">Adventures</a>
              <a href="/overview" class="hover:text-[#124C12] transition">Overview</a>
              <a href="mk/insights" class="hover:text-[#124C12] transition">Insights</a>
            </nav>
        </div>


        <div class="flex items-center space-x-4 lg:space-x-6">
          <!-- Desktop button -->
        <button
          @click="$store.modal.isOpen = true"
          class="hidden lg:flex items-center gap-2 bg-[#eff0ec] text-[#111827] px-5 py-4 rounded-xl font-medium text-sm"
        >
          <img class="w-4 h-4" src="<?= get_template_directory_uri(); ?>/images/add2.svg" alt="Add Icon">
          <span>Add adventure</span>
        </button>

        <!-- Mobile button -->
        <button
          @click="$store.modal.isOpen = true"
          class="flex lg:hidden items-center gap-2 bg-[#eff0ec] text-[#111827] px-4 py-3 rounded-xl font-medium text-sm mr-2"
        >
          <img class="w-4 h-4" src="<?= get_template_directory_uri(); ?>/images/add2.svg" alt="Add Icon">
          <span>Add</span>
        </button>

          <div x-data="{ open: false }" class="relative w-10 h-10">
            <img
              src="<?= get_template_directory_uri(); ?>/images/mk.jpg"
              alt="Profile Image"
              class="rounded-full w-10 h-10 border border-[#CEE027]"
            >
            <button
              @click="open = !open"
              class="absolute -bottom-1 -right-1 w-5 h-5 bg-[#CEE027] rounded-full flex items-center justify-center text-[#111827] shadow-sm"
            >
              <i class="fas fa-chevron-down text-[10px]"></i>
            </button>

            <div
              x-show="open"
              @click.outside="open = false"
              x-transition
              class="absolute right-0 mt-2 w-48 bg-white border rounded-md shadow-lg z-50"
            >
              <a href="/profile" class="flex items-center px-4 py-2 text-gray-800 hover:bg-gray-100">
                <i class="fas fa-user mr-2"></i> Profile
              </a>
              <a href="/settings" class="flex items-center px-4 py-2 text-gray-800 hover:bg-gray-100">
                <i class="fas fa-cog mr-2"></i> Settings
              </a>
                <a href="<?php echo wp_logout_url(home_url('/login')); ?>">
                Logout
            </a>
            </div>
          </div>

          <button class="dark bg-white pr-4 rounded-full">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- ✅ Spacer to prevent layout shift when sticky -->
  <div :class="isSticky ? 'h-[100px]' : ''"></div>
<?php endif; ?>
</div>


