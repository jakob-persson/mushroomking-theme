<?php
/* Template Name: Forgot Password */

if (is_user_logged_in()) {
    wp_redirect(home_url('/'));
    exit;
}

get_header();
?>
<!-- Top Left Logo -->
<div class="absolute top-6 left-6 z-50">
  <a href="<?php echo home_url(); ?>">
    <img src="<?= get_template_directory_uri(); ?>/images/mk-logo2.png" alt="MK Logo" class="h-8 w-auto">
  </a>
</div>

<div class="min-h-screen flex flex-col items-center justify-center bg-white px-4">
    <h1 class="text-2xl font-bold mb-2">Forgot password?</h1>
    <p class="text-gray-500 mb-6 text-center">
        Enter your email or username and we'll send you an email to reset your password.
    </p>

    <form method="post" action="<?php echo esc_url(site_url('wp-login.php?action=lostpassword', 'login_post')); ?>" class="w-full max-w-sm">
        <input type="text" name="user_login" required
            placeholder="Email or username"
            class="w-full mb-4 px-4 py-3 rounded bg-[#F6F7F5] focus:outline-none focus:ring-2 focus:ring-black">

        <button type="submit"
            class="w-full py-3 rounded-lg bg-[#E1E3DC] text-gray-600 cursor-not-allowed"
            disabled>
            Send Reset Email
        </button>
    </form>

    <div class="mt-4">
        <a href="<?php echo home_url('/login'); ?>" class="text-purple-600 hover:underline text-sm">
            Back to log in
        </a>
    </div>
</div>

<?php
get_footer();
?>
