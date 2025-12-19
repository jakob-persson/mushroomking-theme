<?php
if ( ! is_user_logged_in() ) return;

// Handle form submission **before output**
if ( isset($_POST['edit_profile_nonce']) && wp_verify_nonce($_POST['edit_profile_nonce'], 'edit_profile_action') ) {
    
    $user_id = get_current_user_id();

    // Update user data
    if ( isset($_POST['display_name']) ) {
        wp_update_user([
            'ID' => $user_id,
            'display_name' => sanitize_text_field($_POST['display_name']),
        ]);
    }

    if ( isset($_POST['email']) ) {
        wp_update_user([
            'ID' => $user_id,
            'user_email' => sanitize_email($_POST['email']),
        ]);
    }

    if ( isset($_POST['country']) ) {
        update_user_meta($user_id, 'country', sanitize_text_field($_POST['country']));
    }

    if ( isset($_POST['presentation']) ) {
    update_user_meta($user_id, 'presentation', sanitize_textarea_field($_POST['presentation']));
}

    // Optionally handle file upload here

    // **Redirect to the same page** to avoid POST resubmission
    wp_redirect( get_permalink() );
    exit;
}

// Now load user data for the form
$current_user = wp_get_current_user();
$presentation = get_user_meta(get_current_user_id(), 'presentation', true);

// Ta bort <p> och andra HTML-taggar
$presentation_clean = wp_strip_all_tags($presentation);
?>

<style>

input {
  color: #1E2330 !important;
  background-color: #eff0ec!important
}
</style>


<div
   x-data="{
    view: 'main',
    tempText: '',
    presentationTemp: `<?= esc_js($presentation_clean); ?>`,
    openOverlay: false,
    saveText() {
        if(!this.tempText.trim()) return;
        this.presentationTemp = this.tempText;
        this.back();
    },
    back() {
        this.view = 'main';
        this.openOverlay = false;
    }
}"
  
  x-show="$store.editProfileModal.isOpen"
  x-transition.opacity
  x-cloak
  @keydown.escape.window="$store.editProfileModal.isOpen = false"
  class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
>

  <div
    @click.outside="$store.editProfileModal.isOpen = false"
    class="bg-white w-full max-w-xl lg:rounded-2xl shadow-xl flex flex-col h-full lg:min-h-[80vh] lg:max-h-[90vh] overflow-hidden relative"
  >
    <!-- Modal Header -->
        <div class="flex items-center px-6 py-4 relative border-b border-[#eff0ec]">
        <h1 class="gilroy absolute left-1/2 -translate-x-1/2 font-semibold text-xl lg:text-2xl">
            Edit Profile
        </h1>

        <button
            type="button"
            @click="$store.editProfileModal.isOpen = false"
            class="ml-auto w-10 h-10 flex items-center justify-center rounded-full bg-[#eff0ec] hover:bg-gray-300 transition text-xl"
        >
            &times;
        </button>
        </div>

    <!-- User info -->
        <div class="px-6 flex items-center space-x-3 my-4">
        <?php
            $user_id = get_current_user_id();
            $current_user_avatar = mk_get_user_avatar($user_id);
            $user_country = get_user_meta($user_id, 'country', true);
        ?>

        <div class="relative group">
            <img
                id="profileAvatarPreview"
                src="<?= esc_url($current_user_avatar); ?>"
                class="w-32 h-32 rounded-full object-cover border"
                alt="Profile avatar"
            >

            <!-- Camera icon -->
        <button
            type="button"
            onclick="document.getElementById('avatarFileInput').click()"
            class="absolute bottom-2 right-1 w-8 h-8 rounded-full
                bg-[#1E2330] text-white flex items-center justify-center
                text-sm opacity-90 group-hover:opacity-100 transition"
            title="Change profile image"
        >
            <i class="fas fa-camera"></i>
        </button>

        </div>
        <input
        type="file"
        id="avatarFileInput"
        accept="image/*"
        class="hidden"
        onchange="uploadAvatar(this)"
    >

        <div class="flex flex-col leading-tight">
            <span class="font-semibold text-sm mb-1">
            <?= esc_html(wp_get_current_user()->display_name); ?>
            </span>

            <span class="text-sm flex items-center gap-1 text-gray-600">
            <i class="fas fa-map-marker-alt"></i>
            <span>
                <?= $user_country ? esc_html($user_country) : 'No country selected'; ?>
            </span>
            </span>
        </div>
        </div>
    
        
        <!-- Main Content: Presentation -->
        <div class="px-6 overflow-y-auto flex-grow space-y-7">
                <div
                class="rounded-[14px] text-sm cursor-pointer"
                style="margin-top:10px"
                @click="tempText = presentationTemp; view='text'; openOverlay = true;"
            >
                <span x-text="presentationTemp || 'Say something about yourself...'"></span>
        </div>

       


     <!-- Main Content: Form -->
    <div class="">

    <form id="editProfileForm" method="post" enctype="multipart/form-data" class="space-y-4">
        <input type="hidden" name="presentation" x-model="presentationTemp">
      <?php wp_nonce_field('edit_profile_action','edit_profile_nonce'); ?>


      <div class="relative" x-data="{ hasValue: true }">
    <input
        type="text"
        name="display_name"
        required
        value="<?= esc_attr($current_user->display_name) ?>"
        @input="hasValue = $event.target.value.length > 0"
        @focus="hasValue = true"
        class="peer w-full px-4 pt-6 pb-2 rounded-xl bg-[#eff0ec] border
               focus:outline-none focus:ring-2 focus:ring-[#1E2330]"
    />

    <label
        :class="hasValue
            ? 'top-2 text-xs translate-y-0'
            : 'top-1/2 -translate-y-1/2 text-sm'"
        class="absolute left-4 text-gray-400 transition-all pointer-events-none">
            Display Name
        </label>
    </div>


<div class="relative" x-data="{ hasValue: true }">
    <input
        type="email"
        name="email"
        required
        value="<?= esc_attr($current_user->user_email) ?>"
        @input="hasValue = $event.target.value.length > 0"
        @focus="hasValue = true"
        class="peer w-full px-4 pt-6 pb-2 rounded-xl bg-[#eff0ec] border
               focus:outline-none focus:ring-2 focus:ring-[#1E2330]"
    />

    <label
        :class="hasValue
            ? 'top-2 text-xs translate-y-0'
            : 'top-1/2 -translate-y-1/2 text-sm'"
        class="absolute left-4 text-gray-400 transition-all pointer-events-none">
        Email address
        </label>
    </div>


    <div class="relative" x-data="{ hasValue: <?= $user_country ? 'true' : 'false' ?> }">
    <select
        name="country"
        required
        @change="hasValue = $event.target.value !== ''"
        @focus="hasValue = true"
        class="peer w-full px-4 pt-6 pb-2 rounded-xl bg-[#eff0ec] border
               appearance-none focus:outline-none focus:ring-2 focus:ring-[#1E2330]">
        
        <option value="" disabled <?= !$user_country ? 'selected' : '' ?>></option>

        <?php
        $countries = ['United States','Canada','United Kingdom','Germany','France','India','Australia','Sweden','Norway','Finland','Other'];
        foreach ($countries as $c) {
            $selected = $user_country === $c ? 'selected' : '';
            echo "<option value='".esc_attr($c)."' $selected>".esc_html($c)."</option>";
        }
        ?>
    </select>

    <!-- Floating Label -->
    <label
        :class="hasValue
            ? 'top-2 text-xs translate-y-0'
            : 'top-1/2 -translate-y-1/2 text-sm'"
        class="absolute left-4 text-gray-400 transition-all pointer-events-none">
        Country
    </label>

    <!-- Font Awesome chevron -->
    <span class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-gray-500">
        <i class="fas fa-chevron-down"></i>
    </span>
</div>



    

        <!-- Presentation Overlay -->
        <div class="absolute inset-0 bg-white z-50 overflow-y-auto"
        x-show="openOverlay"
        x-transition
        @click.stop 
        >
        <div class="flex items-center px-6 py-4 relative border-b border-[#eff0ec']">
          <button type="button" @click="back()" 
            class="text-sm flex items-center justify-center gap-2 absolute left-6 bg-[#eff0ec] hover:bg-gray-300 w-10 h-10 rounded-full">
            <i class="fas fa-arrow-left"></i>
        </button>
        <h2 class="text-xl lg:text-2xl gilroy w-full text-center" x-text="view==='text'?'Edit your presentation':''"></h2>
                </div>

        <template x-if="view==='text'">
            <div class="flex flex-col flex-1 p-6">
                <textarea x-model="tempText" class="w-full h-52 p-3 bg-[#eff0ec] rounded-2xl" placeholder="Your presentation..."></textarea>
                <button @click="saveText()" class="bg-[#1E2330] text-white px-4 py-2 rounded-xl mt-4 self-start">Save</button>
            </div>
        </template>

        <!-- Location Overlay -->
        <template x-if="view==='location'">
            <!-- ...din location overlay kod här... -->
        </template>

        <!-- Date Overlay -->
        <template x-if="view==='date'">
            <!-- ...din date overlay kod här... -->
        </template>
    </div>
    </form>
  </div>
</div>
<!-- Footer with Save button -->
<div class="px-6 py-4 border-t border-gray-200 bg-white">
    <button type="submit" form="editProfileForm"
        class="w-full bg-[#1E2330] text-white py-3 rounded-xl hover:bg-gray-900 transition">
        Save Changes
    </button>
</div>
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('editProfile', () => ({
        view: 'main',
        tempText: '',
        presentationTemp: `<?= esc_js($presentation_clean); ?>`,
        openOverlay: false,
        saveText() {
            if (!this.tempText.trim()) return;
            this.presentationTemp = this.tempText; // uppdatera temporär text
            this.back(); // gå tillbaka till main-view
        },
        back() {
            this.view = 'main';
            this.openOverlay = false;
        }
    }));
});
</script>
<script>
function uploadAvatar(input) {
    if (!input.files || !input.files[0]) return;

    const file = input.files[0];

    // 🔹 Instant preview
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('profileAvatarPreview').src = e.target.result;
    };
    reader.readAsDataURL(file);

    // 🔹 Upload via AJAX
    const formData = new FormData();
    formData.append('action', 'upload_profile_avatar');
    formData.append('avatar', file);

    fetch('<?= admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    })
    .then(r => r.json())
    .then(res => {
        if (!res.success) {
            alert(res.data || 'Avatar upload failed');
        }
    })
    .catch(() => {
        alert('Upload error');
    });
}
</script>


