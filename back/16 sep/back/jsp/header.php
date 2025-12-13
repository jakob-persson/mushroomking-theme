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


<?php
$extra_classes = is_page_template('page-login.php') ? '' : 'bg-[#124C12]';
?>
<body <?php body_class("template-login $extra_classes"); ?> x-data>
<!-- Header -->
<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<?php if (!is_page_template('page-login.php') && !is_page('forgot-password')): ?>

<!-- ✅ Sticky Header Wrapper -->
<div
  x-data="{ isSticky: false }"
  x-init="window.addEventListener('scroll', () => { isSticky = window.scrollY > 200 })"
  class=" relative z-10"
>

  <!-- ✅ Sticky Header -->
  <div :class="isSticky ? 'fixed top-0 left-0 right-0' : ''" class="transition-all duration-300">
    <div class="p-4">
      <div class="flex items-center justify-between rounded-full px-4 bg-white py-2 mb-6 pl-8">
        <div class="w-[52px] b[#111827] pr-4">
          <img src="<?php echo get_template_directory_uri(); ?>/images/mk-logo2.png" alt="Logo">
        </div>

        <div class="flex items-center space-x-1">
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

          <button class="text-black bg-white p-4 rounded-full">
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


