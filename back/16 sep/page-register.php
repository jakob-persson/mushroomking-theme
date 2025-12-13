<?php
/* Template Name: Register Page */

if (!session_id()) {
    session_start();
}

if (is_user_logged_in()) {
    wp_redirect(home_url('/insights'));
    exit;
}

$error = '';
$step  = isset($_POST['step']) ? $_POST['step'] : 1;
$reserved_slugs = ['login','register','insights','forgot-password','wp-admin','wp-login.php'];

/**
 * STEP 1 – send verification code
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step == 1) {
    $email    = sanitize_email($_POST['email']);
    $username = sanitize_user($_POST['username']);
    $password = $_POST['password'];

    if (!is_email($email)) {
        $error = 'Invalid email address.';
    } elseif (email_exists($email) || username_exists($username)) {
        $error = 'Username or email already exists.';
    } else {
        $code = rand(100000, 999999);
        $_SESSION['verify_email']    = $email;
        $_SESSION['verify_code']     = $code;
        $_SESSION['verify_username'] = $username;
        $_SESSION['verify_password'] = $password;
        $_SESSION['verify_time']     = time();

        wp_mail($email, 'Your Verification Code', 'Your verification code is: ' . $code . "\n\nCode valid for 10 minutes.");

        $step = '1.5';
    }
}

/**
 * STEP 1.5 – resend code
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend_code'])) {
    if (!empty($_SESSION['verify_email'])) {
        $code = rand(100000, 999999);
        $_SESSION['verify_code'] = $code;
        $_SESSION['verify_time'] = time();

        wp_mail($_SESSION['verify_email'], 'Your New Verification Code', 'Your new verification code is: ' . $code . "\n\nCode valid for 10 minutes.");

        $error = 'A new code has been sent to your email.';
        $step  = '1.5';
    } else {
        $step  = 1;
        $error = 'Session expired, please start again.';
    }
}

/**
 * STEP 1.5 → 1.6 – user enters code
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step == '1.6') {
    $entered_code = sanitize_text_field($_POST['verification_code']);
    $is_expired   = (time() - ($_SESSION['verify_time'] ?? 0)) > 600; // 10 min

    if ($is_expired) {
        $error = 'Your verification code has expired. Please request a new one.';
        $step = '1.5';
    } elseif (isset($_SESSION['verify_code']) && $entered_code == $_SESSION['verify_code']) {
        $step = 2;
    } else {
        $error = 'Invalid verification code.';
        $step = '1.5';
    }
}

/**
 * STEP 2 – final registration
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step == 2 && isset($_POST['final_submit'])) {
    $username     = sanitize_user($_SESSION['verify_username']);
    $email        = sanitize_email($_SESSION['verify_email']);
    $password     = $_SESSION['verify_password'];
    $country      = sanitize_text_field($_POST['country']);
    $presentation = wp_kses_post($_POST['presentation']);

    if (in_array(strtolower($username), $reserved_slugs)) {
        $username .= rand(10,99);
    }

    if (!username_exists($username) && !email_exists($email)) {
        $user_id = wp_create_user($username, $password, $email);

        if (!is_wp_error($user_id)) {
            wp_update_user([
                'ID'           => $user_id,
                'display_name' => $username,
            ]);

            if (!empty($country)) update_user_meta($user_id, 'country', $country);
            if (!empty($presentation)) update_user_meta($user_id, 'presentation', $presentation);

            if (!empty($_FILES['profile_image']['name'])) {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                require_once(ABSPATH . 'wp-admin/includes/media.php');
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                $attachment_id = media_handle_upload('profile_image', 0);
                if (!is_wp_error($attachment_id)) {
                    update_user_meta($user_id, 'profile_image', wp_get_attachment_url($attachment_id));
                }
            }

            unset($_SESSION['verify_code'], $_SESSION['verify_email'], $_SESSION['verify_username'], $_SESSION['verify_password'], $_SESSION['verify_time']);

            wp_set_current_user($user_id);
            wp_set_auth_cookie($user_id);

            $slug = strtolower(str_replace(' ', '-', $username));
            wp_redirect(home_url('/' . $slug));
            exit;
        } else {
            $error = $user_id->get_error_message();
        }
    } else {
        $error = 'Username or email already exists.';
    }
}

get_header();
wp_enqueue_editor();
?>

<!-- Top Left Logo -->
<div class="absolute top-6 left-6 z-50">
  <a href="<?php echo home_url(); ?>">
    <img src="<?= get_template_directory_uri(); ?>/images/mk-logo2.png" alt="MK Logo" class="h-10 w-auto">
  </a>
</div>

<div class="min-h-screen grid grid-cols-1 md:grid-cols-2">
  <!-- Left Side -->
  <div class="flex items-center justify-center bg-white px-6 py-12 md:py-24">
    <form method="post" enctype="multipart/form-data" class="w-full max-w-md space-y-4">
      <h2 class="text-4xl font-bold text-center mb-2">Create an account</h2>
      <p class="text-center text-gray-500 mb-6">Join the Mushroom community</p>

      <?php if ($error): ?>
        <div class="p-3 bg-red-100 text-red-700 rounded"><?php echo esc_html($error); ?></div>
      <?php endif; ?>

      <?php if ($step == 1): ?>
        <!-- STEP 1 -->
        <input type="hidden" name="step" value="1">

        <input type="text" name="username" required placeholder="Username"
          class="w-full px-4 py-3 rounded-xl bg-[#F6F7F5] border">

        <input type="email" name="email" required placeholder="Email address"
          class="w-full px-4 py-3 rounded-xl bg-[#F6F7F5] border">

        <input type="password" name="password" required placeholder="Password"
          class="w-full px-4 py-3 rounded-xl bg-[#F6F7F5] border">

        <button type="submit"
          class="w-full bg-[#1E2330] text-white py-3 rounded-xl hover:bg-gray-900">
          Send Verification Code →
        </button>

      <?php elseif ($step == '1.5'): ?>
        <!-- STEP 1.5 -->
        <input type="hidden" name="step" value="1.6">

        <input type="email" name="email" value="<?php echo esc_attr($_SESSION['verify_email']); ?>" readonly
          class="w-full px-4 py-3 rounded-xl bg-gray-100 border">

        <input type="text" name="verification_code" required placeholder="Enter code from email"
          class="w-full px-4 py-3 rounded-xl bg-[#F6F7F5] border">

        <button type="submit"
          class="w-full bg-[#1E2330] text-white py-3 rounded-xl hover:bg-gray-900">
          Verify & Continue →
        </button>

        <!-- Countdown Timer -->
        <p id="timer" class="text-center text-sm text-gray-600 mt-2"></p>

        <!-- Resend button -->
        <div class="text-center mt-4">
          <button type="submit" name="resend_code" value="1"
            class="text-sm text-purple-600 hover:underline bg-transparent border-0">
            Resend Code
          </button>
        </div>

        <script>
        document.addEventListener("DOMContentLoaded", function() {
            var createdAt = <?php echo json_encode($_SESSION['verify_time'] ?? time()); ?>;
            var expireIn = 600; // 10 minutes
            function updateTimer() {
                var now = Math.floor(Date.now() / 1000);
                var remaining = (createdAt + expireIn) - now;
                var timerEl = document.getElementById("timer");
                if (remaining > 0) {
                    var mins = Math.floor(remaining / 60);
                    var secs = remaining % 60;
                    timerEl.textContent = "Code expires in " + mins + "m " + secs + "s";
                } else {
                    timerEl.textContent = "Code expired. Please click 'Resend Code'.";
                }
            }
            setInterval(updateTimer, 1000);
            updateTimer();
        });
        </script>

      <?php elseif ($step == 2): ?>
        <!-- STEP 2 -->
        <input type="hidden" name="step" value="2">
        <input type="hidden" name="final_submit" value="1">

        <select name="country" required
          class="w-full px-4 py-3 rounded-xl bg-[#F6F7F5] border">
          <option value="">Select your country</option>
          <option>United States</option>
          <option>Canada</option>
          <option>United Kingdom</option>
          <option>Germany</option>
          <option>France</option>
          <option>India</option>
          <option>Australia</option>
          <option>Sweden</option>
          <option>Norway</option>
          <option>Finland</option>
          <option>Other</option>
        </select>

        <div>
          <label for="presentation" class="block text-sm text-gray-600 mb-1">Presentation</label>
          <?php
          $content   = isset($_POST['presentation']) ? wp_kses_post($_POST['presentation']) : '';
          wp_editor($content, 'presentation', [
              'textarea_name' => 'presentation',
              'editor_height' => 200,
              'media_buttons' => false,
              'teeny'         => true,
              'quicktags'     => true,
          ]);
          ?>
        </div>

        <div>
          <label for="profile_image" class="block text-sm text-gray-600 mb-1">Upload Profile Image</label>
          <input type="file" name="profile_image" id="profile_image" accept="image/*"
            class="w-full text-sm text-gray-500">
        </div>

        <button type="submit"
          class="w-full bg-[#1E2330] text-white py-3 rounded-xl hover:bg-gray-900">
          Finish & Sign up
        </button>
      <?php endif; ?>
    </form>
  </div>

  <!-- Right Side Image -->
  <div class="hidden md:flex items-center justify-center bg-[#EDC1D9]">
    <img src="<?= get_template_directory_uri(); ?>/images/main-screen.png" alt="Illustration" class="w-[86%]">
  </div>
</div>
