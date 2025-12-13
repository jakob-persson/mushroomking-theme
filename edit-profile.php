<?php
/**
 * Template Name: Edit Profile
 */

if (!is_user_logged_in()) {
    wp_redirect(home_url('/login'));
    exit;
}

$current_user = wp_get_current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Security: verify nonce
    if ( ! isset( $_POST['edit_profile_nonce'] ) || ! wp_verify_nonce( $_POST['edit_profile_nonce'], 'edit_profile_action' ) ) {
        wp_die( 'Security check failed', 'Error', array( 'response' => 403 ) );
    }

    $user_id      = $current_user->ID;
    $display_name = sanitize_text_field( $_POST['display_name'] );
    $email        = sanitize_email( $_POST['email'] );
    $presentation = wp_kses_post( $_POST['presentation'] );
    $country      = sanitize_text_field( $_POST['country'] );

    // Prepare update args
    $update_args = [ 'ID' => $user_id ];

    // Update display name and user_nicename (slug) if changed
    if ( $display_name && $display_name !== $current_user->display_name ) {
        $update_args['display_name']  = $display_name;
        // sanitize_title produces a safe slug
        $update_args['user_nicename'] = sanitize_title( $display_name );
    }

    // Update email if changed
    if ( $email && $email !== $current_user->user_email ) {
        $update_args['user_email'] = $email;
    }

    // Run update if anything changed
    if ( count( $update_args ) > 1 ) {
        $result = wp_update_user( $update_args );
        if ( is_wp_error( $result ) ) {
            wp_die( 'Could not update user: ' . esc_html( $result->get_error_message() ) );
        }
    }

    // Update meta fields
    update_user_meta( $user_id, 'presentation', $presentation );
    update_user_meta( $user_id, 'country', $country );

    // Upload profile image (store attachment ID for flexibility)
    if ( ! empty( $_FILES['profile_image']['name'] ) ) {
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
        require_once( ABSPATH . 'wp-admin/includes/media.php' );
        require_once( ABSPATH . 'wp-admin/includes/image.php' );

        $attachment_id = media_handle_upload( 'profile_image', 0 );
        if ( ! is_wp_error( $attachment_id ) ) {
            // store the attachment ID (your retrieval code already supports numeric IDs)
            update_user_meta( $user_id, 'profile_image', $attachment_id );
        }
    }

    // Redirect to user's slug (user_nicename)
    $updated_user = get_userdata( $user_id );
    $slug = $updated_user->user_nicename ? $updated_user->user_nicename : sanitize_title( $updated_user->display_name );

    wp_safe_redirect( home_url( "/$slug" ) );
    exit;

}

get_header();
?>
<script>
jQuery(document).ready(function($){
    if ( typeof tinymce !== 'undefined' ) {
        tinymce.init({
            selector: '#presentation_editor',
            menubar: false,
            plugins: 'lists link paste',
            toolbar: 'bold italic underline | bullist numlist | link | undo redo'
        });
        $('#presentation_editor').css('visibility', 'visible');
    }
});
</script>

<div class="min-h-screen grid grid-cols-1 md:grid-cols-2">
    <div class="flex items-center justify-center bg-white px-6 py-12 md:py-24">
        <form method="post" enctype="multipart/form-data" class="w-full max-w-md space-y-4">
        <?php wp_nonce_field('edit_profile_action','edit_profile_nonce'); ?>

            <h2 class="text-4xl font-bold text-center mb-2">Edit Your Profile</h2>

            <!-- Display Name -->
            <input type="text" name="display_name" required
                value="<?= esc_attr($current_user->display_name) ?>"
                placeholder="Display Name"
                class="w-full px-4 py-3 rounded-xl bg-[#F6F7F5] border">

            <!-- Email -->
            <input type="email" name="email" required
                value="<?= esc_attr($current_user->user_email) ?>"
                placeholder="Email address"
                class="w-full px-4 py-3 rounded-xl bg-[#F6F7F5] border">

            <!-- Country -->
            <select name="country" required class="w-full px-4 py-3 rounded-xl bg-[#F6F7F5] border">
                <option value="">Select your country</option>
                <?php
                $countries = ['United States','Canada','United Kingdom','Germany','France','India','Australia','Sweden','Norway','Finland','Other'];
                $user_country = get_user_meta($current_user->ID, 'country', true);
                foreach ($countries as $c) {
                    $selected = $user_country === $c ? 'selected' : '';
                    echo "<option value='".esc_attr($c)."' $selected>".esc_html($c)."</option>";
                }
                ?>
            </select>

            <!-- Profile Image -->
            <div>
                <label for="profile_image" class="block text-sm text-gray-600 mb-1">Upload Profile Image</label>
                <input type="file" name="profile_image" id="profile_image" accept="image/*"
                    class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4
                           file:rounded-full file:border-0 file:text-sm file:font-semibold
                           file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100">
            </div>

            <!-- Presentation -->
         <label class="block text-sm font-medium mt-4 mb-1">Presentation</label>
            <div style="z-index: 10;"> <!-- Added wrapper div -->
            <?php
            wp_editor(
                get_user_meta($current_user->ID, 'presentation', true),
                'presentation_editor',
                [
                    'textarea_name' => 'presentation',
                    'media_buttons' => false,
                    'textarea_rows' => 8,
                    'teeny' => true,
                    'tinymce' => [
                        'plugins' => 'lists link paste',
                        'toolbar1' => 'bold italic underline | bullist numlist | link | undo redo',
                    ],
                    'quicktags' => false,
                ]
            );
            ?>
            </div>


            <button type="submit"
                class="w-full bg-[#1E2330] text-white py-3 rounded-xl hover:bg-gray-900 transition duration-300">
                Save Changes
            </button>
        </form>
    </div>
</div>
<?php get_footer(); ?>
