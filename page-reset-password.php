<?php
/* Template Name: Reset Password */

if (is_user_logged_in()) {
    wp_redirect(home_url('/'));
    exit;
}

$error_message = '';
$success_message = '';
$reset_successful = false;

// Get key and login from URL
$rp_key = isset($_GET['key']) ? sanitize_text_field($_GET['key']) : '';
$rp_login = isset($_GET['login']) ? sanitize_user($_GET['login']) : '';

// Validate reset key
if (!empty($_POST['pass1']) && !empty($_POST['pass2'])) {
    $rp_key = sanitize_text_field($_POST['rp_key']);
    $rp_login = sanitize_user($_POST['rp_login']);
    $pass1 = $_POST['pass1'];
    $pass2 = $_POST['pass2'];

    if ($pass1 !== $pass2) {
        $error_message = "Passwords do not match.";
    } elseif (strlen($pass1) < 6) {
        $error_message = "Password must be at least 6 characters.";
    } else {
        $user = check_password_reset_key($rp_key, $rp_login);

        if (is_wp_error($user)) {
            $error_message = $user->get_error_message();
        } else {
            reset_password($user, $pass1);
            $reset_successful = true;
            $success_message = "Your password has been reset. You can now log in.";
        }
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

<div class="min-h-screen flex flex-col items-center justify-center bg-white px-4">
    <h1 class="text-2xl font-bold mb-2">Reset your password</h1>
    <p class="text-gray-500 mb-6 text-center">Enter your new password below.</p>

    <?php if (!empty($error_message)): ?>
        <p class="text-red-600 mb-4 text-center"><?php echo esc_html($error_message); ?></p>
    <?php elseif (!empty($success_message)): ?>
        <p class="text-green-600 mb-4 text-center"><?php echo esc_html($success_message); ?></p>
    <?php endif; ?>

    <?php if (!$reset_successful && !empty($rp_key) && !empty($rp_login)): ?>
        <form method="post" class="w-full max-w-sm">
            <input type="hidden" name="rp_key" value="<?php echo esc_attr($rp_key); ?>">
            <input type="hidden" name="rp_login" value="<?php echo esc_attr($rp_login); ?>">

            <input type="password" name="pass1" required
                placeholder="New password"
                class="w-full mb-4 px-4 py-3 rounded bg-[#F6F7F5] focus:outline-none focus:ring-2 focus:ring-black">

            <input type="password" name="pass2" required
                placeholder="Confirm new password"
                class="w-full mb-4 px-4 py-3 rounded bg-[#F6F7F5] focus:outline-none focus:ring-2 focus:ring-black">

            <button type="submit"
                class="w-full py-3 rounded-lg bg-black text-white hover:bg-gray-800 transition">
                Reset Password
            </button>
        </form>
    <?php elseif (!$reset_successful): ?>
        <p class="text-red-500 text-center">The reset link is invalid or expired.</p>
    <?php endif; ?>

    <div class="mt-4">
        <a href="<?php echo home_url('/login'); ?>" class="text-purple-600 hover:underline text-sm">
            Back to log in
        </a>
    </div>
</div>

<?php
get_footer();
?>
