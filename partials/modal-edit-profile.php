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

#profileAvatarPreview {
    width: 8rem;           /* 32 * 0.25rem */
    height: 8rem;          /* Make sure it's square */
    object-fit: cover;     /* Crop image to cover the box */
    border-radius: 9999px; /* Fully circular */
    display: block;
}

.avatar-container {
    width: 8rem;              /* 128px */
    height: 8rem;             /* same as width */
    position: relative;       /* so absolute child buttons are relative to it */
}

.avatar-camera-btn {
    position: absolute;
    bottom: 0.25rem;  /* 4px from bottom */
    right: 0.25rem;   /* 4px from right */
    width: 2rem;      /* 32px */
    height: 2rem;     /* 32px */
    border-radius: 9999px;
    background-color: #1E2330;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    opacity: 0.9;
    transition: opacity 0.2s;
}

.avatar-camera-btn:hover {
    opacity: 1;
}
/* Modal-specific inputs */
.edit input,
.edit select,
.edit textarea {
    box-sizing: border-box;
    font-family: inherit;
    font-size: 16px;
    line-height: 1.2;
    min-height: 3.5rem;
    padding: 1rem 1rem 0.2rem 1rem;
    border-radius: 0.75rem;
    border: 1px solid #ccc;
    background-color: #eff0ec;
    color: #1E2330;
    outline: none;
    box-shadow: none;
}

/* Floating labels */
.edit .relative label {
    position: absolute;
    left: 1rem;
    top: 1rem;
    font-size: 16px;
    color: #888;
    pointer-events: none;
    transition: all 0.2s ease;
}

/* When input/select/textarea has focus or value */
.edit .relative input:focus + label,
.edit .relative input:not(:placeholder-shown) + label,
.edit .relative select:focus + label,
.edit .relative select:not([value=""]) + label,
.edit .relative textarea:focus + label {
    top: 0.42rem;
    font-size: 0.65rem;
    color: #1E2330;
    background: #eff0ec;
    padding: 0 0;
}

/* Remove all glow/border shadows on focus */
.edit input:focus,
.edit select:focus,
.edit textarea:focus {
    border-color: #1E2330 !important;
    outline: none !important;
    box-shadow: none !important;
    -webkit-box-shadow: none !important;
    -moz-box-shadow: none !important;
}

/* Remove default browser arrows on select */
.edit select {
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
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
  class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 edit"
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

    <div class="avatar-container">
    <img id="profileAvatarPreview" src="<?= esc_url($current_user_avatar); ?>" alt="Profile avatar">

    <button
        type="button"
        onclick="document.getElementById('avatarFileInput').click()"
        class="avatar-camera-btn"
        title="Change profile image"
    >
        <i class="fas fa-camera"></i>
        </button>
    </div>

    <input type="file" id="avatarFileInput" accept="image/*" class="hidden" onchange="uploadAvatar(this)">


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


     <div class="relative">
    <input
        type="text"
        name="display_name"
        required
        placeholder=" "
        value="<?= esc_attr($current_user->display_name) ?>"
        class="peer w-full"
    />
    <label>Display Name</label>
</div>

<div class="relative mt-4">
    <input
        type="email"
        name="email"
        required
        placeholder=" "
        value="<?= esc_attr($current_user->user_email) ?>"
        class="peer w-full"
    />
    <label>Email address</label>
</div>

<div class="relative mt-4" x-data="{
        country: '<?= esc_js($user_country); ?>',
        countries: ['Afghanistan','Albania','Algeria','Andorra','Angola','Argentina','Armenia','Australia','Austria','Azerbaijan','Bahamas','Bahrain','Bangladesh','Barbados','Belarus','Belgium','Belize','Benin','Bhutan','Bolivia','Bosnia and Herzegovina','Botswana','Brazil','Brunei','Bulgaria','Burkina Faso','Burundi','Cabo Verde','Cambodia','Cameroon','Canada','Central African Republic','Chad','Chile','China','Colombia','Comoros','Congo (Congo-Brazzaville)','Costa Rica','Croatia','Cuba','Cyprus','Czechia','Denmark','Djibouti','Dominica','Dominican Republic','Ecuador','Egypt','El Salvador','Equatorial Guinea','Eritrea','Estonia','Eswatini','Ethiopia','Fiji','Finland','France','Gabon','Gambia','Georgia','Germany','Ghana','Greece','Grenada','Guatemala','Guinea','Guinea-Bissau','Guyana','Haiti','Holy See','Honduras','Hungary','Iceland','India','Indonesia','Iran','Iraq','Ireland','Israel','Italy','Jamaica','Japan','Jordan','Kazakhstan','Kenya','Kiribati','Kuwait','Kyrgyzstan','Laos','Latvia','Lebanon','Lesotho','Liberia','Libya','Liechtenstein','Lithuania','Luxembourg','Madagascar','Malawi','Malaysia','Maldives','Mali','Malta','Marshall Islands','Mauritania','Mauritius','Mexico','Micronesia','Moldova','Monaco','Mongolia','Montenegro','Morocco','Mozambique','Myanmar','Namibia','Nauru','Nepal','Netherlands','New Zealand','Nicaragua','Niger','Nigeria','North Korea','North Macedonia','Norway','Oman','Pakistan','Palau','Palestine State','Panama','Papua New Guinea','Paraguay','Peru','Philippines','Poland','Portugal','Qatar','Romania','Russia','Rwanda','Saint Kitts and Nevis','Saint Lucia','Saint Vincent and the Grenadines','Samoa','San Marino','Sao Tome and Principe','Saudi Arabia','Senegal','Serbia','Seychelles','Sierra Leone','Singapore','Slovakia','Slovenia','Solomon Islands','Somalia','South Africa','South Korea','South Sudan','Spain','Sri Lanka','Sudan','Suriname','Sweden','Switzerland','Syria','Tajikistan','Tanzania','Thailand','Timor-Leste','Togo','Tonga','Trinidad and Tobago','Tunisia','Turkey','Turkmenistan','Tuvalu','Uganda','Ukraine','United Arab Emirates','United Kingdom','United States of America','Uruguay','Uzbekistan','Vanuatu','Venezuela','Vietnam','Yemen','Zambia','Zimbabwe'],
        filtered: [],
        open: false,
        cleared: false,
        selectCountry(c) { this.country = c; this.open = false; },
        clearOnFocus() { if(!this.cleared) { this.country = ''; this.cleared = true; } }
    }">
    
    <input 
        type="text" 
        name="country" 
        placeholder="Start typing your country..." 
        autocomplete="off"
        x-model="country"
        @focus="clearOnFocus(); filtered = countries; open = true"
        @input="filtered = countries.filter(c => c.toLowerCase().includes(country.toLowerCase())); open = filtered.length>0"
        class="peer w-full px-4 py-3 rounded-xl bg-[#eff0ec] border focus:border-[#1E2330] focus:outline-none"
    >
    <label class="absolute left-4 top-1 text-gray-400 text-sm pointer-events-none transition-all peer-placeholder-shown:top-3 peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-500">
        Country
    </label>

    <ul 
        x-show="open" 
        @click.away="open = false" 
        class="absolute left-0 top-full z-50 w-full max-h-40 overflow-y-auto bg-white border rounded-xl mt-1 shadow"
    >
        <template x-for="c in filtered" :key="c">
            <li 
                @click="selectCountry(c)" 
                class="px-4 py-2 hover:bg-gray-200 cursor-pointer"
                x-text="c"
            ></li>
        </template>
        <li x-show="filtered.length === 0" class="px-4 py-2 text-gray-400 cursor-default">No results</li>
    </ul>
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


