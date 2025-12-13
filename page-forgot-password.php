<?php
/* Template Name: Forgot Password */

if (is_user_logged_in()) {
    wp_redirect(home_url('/'));
    exit;
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_login'])) {
    $user_login = trim($_POST['user_login']);

    if (empty($user_login)) {
        $error_message = 'Please enter your email or username.';
    } else {
        $user = get_user_by('login', $user_login);
        if (!$user && is_email($user_login)) {
            $user = get_user_by('email', $user_login);
        }

        if (!$user) {
            $error_message = 'No user found with that username or email.';
        } else {
            $result = retrieve_password();
            if (is_wp_error($result)) {
                $error_message = $result->get_error_message();
            } else {
                $success_message = 'If your email is in our system, you’ll receive a reset link shortly.';
            }
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
    <h1 class="text-2xl font-bold mb-2">Forgot password?</h1>
    <p class="text-gray-500 mb-6 text-center">
        Enter your email or username and we'll send you an email to reset your password.
    </p>

    <?php if (!empty($error_message)): ?>
        <p class="text-red-600 mb-4 text-center"><?php echo esc_html($error_message); ?></p>
    <?php elseif (!empty($success_message)): ?>
        <p class="text-green-600 mb-4 text-center"><?php echo esc_html($success_message); ?></p>
    <?php endif; ?>

    <form method="post" class="w-full max-w-sm">
        <input type="text" name="user_login" required
            placeholder="Email or username"
            class="w-full mb-4 px-4 py-3 rounded bg-[#F6F7F5] focus:outline-none focus:ring-2 focus:ring-black">

        <button type="submit"
            class="w-full py-3 rounded-lg bg-black text-white hover:bg-gray-800 transition">
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
