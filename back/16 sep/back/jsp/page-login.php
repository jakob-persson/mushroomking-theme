<?php
if (is_user_logged_in()) {
   wp_redirect(home_url('/'));
    exit;
}

$login_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $creds = [
        'user_login'    => sanitize_text_field($_POST['username'] ?? ''),
        'user_password' => $_POST['password'] ?? '',
        'remember'      => isset($_POST['remember']),
    ];

    $user = wp_signon($creds, false);

    if (is_wp_error($user)) {
        $login_error = $user->get_error_message();
    } else {
        wp_redirect(home_url());
        exit;
    }
}

get_header();
?>
<!-- Top Left Logo -->
<div class="absolute top-6 left-6 z-50">
  <a href="<?php echo home_url(); ?>">
    <img src="<?= get_template_directory_uri(); ?>/images/mk-logo2.png" alt="MK Logo" class="h-8 w-auto">
  </a>
</div>
<div class="min-h-screen grid grid-cols-1 md:grid-cols-2">
  <!-- Left Side: Login Form -->
<div class="flex md:items-center justify-center bg-white px-6 py-12 mt-[100px] md:mt-0">

    <form method="post" class="w-full max-w-md">
      <h2 class="text-4xl font-bold mb-2 text-center dark strong">Welcome back</h2>
      <p class="text-center text-gray-500 mb-6">Log in to your Mushroom page</p>

      <?php if ($login_error): ?>
        <div class="mb-4 p-3 bg-red-100 text-red-700 rounded"><?php echo esc_html($login_error); ?></div>
      <?php endif; ?>

     <input type="text" id="username" name="username" required
       placeholder="Email or username"
       class="w-full mb-4 px-4 py-3 rounded focus:outline-none focus:ring-2 focus:ring-black text-base bg-[#F6F7F5]">

<input type="password" id="password" name="password" required
       placeholder="Password"
       class="w-full mb-4 px-4 py-3 rounded focus:outline-none focus:ring-2 focus:ring-black text-base bg-[#F6F7F5]">

      <div class="mb-4 flex items-center">
        <input type="checkbox" id="remember" name="remember" class="mr-2">
        <label for="remember" class="text-gray-500 text-sm">Remember Me</label>
      </div>

      <button type="submit"
              class="w-full bg-[#1E2330] text-white py-3 rounded hover:bg-gray-900 transition duration-300 rounded-xl">
        Sign in
      </button>


<div class="text-center mt-4">
  <a href="<?php echo wp_lostpassword_url(); ?>" class="text-sm text-purple-600 hover:underline">Forgot password?</a>
</div>


  <!--     <div class="my-6 text-center text-gray-500">OR</div>


      <div class="space-y-3">
        <button type="button" class="w-full flex items-center justify-center border border-gray-300 py-3 rounded hover:bg-gray-50">
          <img src="https://www.svgrepo.com/show/475656/google-color.svg" class="w-5 h-5 mr-2" alt="Google">
          Continue with Google
        </button>

        <button type="button" class="w-full flex items-center justify-center border border-gray-300 py-3 rounded hover:bg-gray-50">
          <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24"><path d="..."/></svg>
          Continue with
              </button>
      </div> -->
    </form>
  </div>
<!-- Right Side: You can put an image or brand area -->
<div class="hidden md:flex items-center justify-center bg-[#EDC1D9]">
  <img src="<?= get_template_directory_uri(); ?>/images/login-screen.png" alt="Logo" class="w-[86%]">
</div>
</div>
