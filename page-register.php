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
$step = isset($_POST['step']) ? (string) $_POST['step'] : '1';

// Reserved usernames
$reserved_slugs = ['login','register','insights','forgot-password','wp-admin','wp-login.php'];

// Countries list
$countries = [
  "Afghanistan","Albania","Algeria","Andorra","Angola","Antigua and Barbuda","Argentina","Armenia",
  "Australia","Austria","Azerbaijan","Bahamas","Bahrain","Bangladesh","Barbados","Belarus","Belgium",
  "Belize","Benin","Bhutan","Bolivia","Bosnia and Herzegovina","Botswana","Brazil","Brunei","Bulgaria",
  "Burkina Faso","Burundi","Cabo Verde","Cambodia","Cameroon","Canada","Central African Republic","Chad",
  "Chile","China","Colombia","Comoros","Congo (Congo-Brazzaville)","Costa Rica","Croatia","Cuba","Cyprus",
  "Czechia","Denmark","Djibouti","Dominica","Dominican Republic","Ecuador","Egypt","El Salvador","Equatorial Guinea",
  "Eritrea","Estonia","Eswatini","Ethiopia","Fiji","Finland","France","Gabon","Gambia","Georgia","Germany",
  "Ghana","Greece","Grenada","Guatemala","Guinea","Guinea-Bissau","Guyana","Haiti","Holy See","Honduras",
  "Hungary","Iceland","India","Indonesia","Iran","Iraq","Ireland","Israel","Italy","Jamaica","Japan","Jordan",
  "Kazakhstan","Kenya","Kiribati","Kuwait","Kyrgyzstan","Laos","Latvia","Lebanon","Lesotho","Liberia","Libya",
  "Liechtenstein","Lithuania","Luxembourg","Madagascar","Malawi","Malaysia","Maldives","Mali","Malta","Marshall Islands",
  "Mauritania","Mauritius","Mexico","Micronesia","Moldova","Monaco","Mongolia","Montenegro","Morocco","Mozambique",
  "Myanmar","Namibia","Nauru","Nepal","Netherlands","New Zealand","Nicaragua","Niger","Nigeria","North Korea",
  "North Macedonia","Norway","Oman","Pakistan","Palau","Palestine State","Panama","Papua New Guinea","Paraguay",
  "Peru","Philippines","Poland","Portugal","Qatar","Romania","Russia","Rwanda","Saint Kitts and Nevis","Saint Lucia",
  "Saint Vincent and the Grenadines","Samoa","San Marino","Sao Tome and Principe","Saudi Arabia","Senegal","Serbia",
  "Seychelles","Sierra Leone","Singapore","Slovakia","Slovenia","Solomon Islands","Somalia","South Africa","South Korea",
  "South Sudan","Spain","Sri Lanka","Sudan","Suriname","Sweden","Switzerland","Syria","Tajikistan","Tanzania","Thailand",
  "Timor-Leste","Togo","Tonga","Trinidad and Tobago","Tunisia","Turkey","Turkmenistan","Tuvalu","Uganda","Ukraine",
  "United Arab Emirates","United Kingdom","United States of America","Uruguay","Uzbekistan","Vanuatu","Venezuela","Vietnam",
  "Yemen","Zambia","Zimbabwe"
];

/*--------------------------------------------------------------
 STEP 1 — Send Verification Code
--------------------------------------------------------------*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step == 1 && isset($_POST['send_code'])) {
    $email = sanitize_email($_POST['email']);

    if (!is_email($email)) {
        $error = 'Invalid email address.';
    } elseif (email_exists($email)) {
        $error = 'Email already exists.';
    } else {
        $code = rand(100000, 999999);
        $_SESSION['verify_email'] = $email;
        $_SESSION['verify_code']  = $code;
        $_SESSION['verify_time']  = time();

        wp_mail(
            $email,
            'Your Verification Code',
            'Your verification code is: ' . $code . "\n\nThis code is valid for 10 minutes."
        );

        $step = '1.5';
    }
}

/*--------------------------------------------------------------
 STEP 1.5 — Resend Code
--------------------------------------------------------------*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend_code'])) {
    if (!empty($_SESSION['verify_email'])) {
        $code = rand(100000, 999999);
        $_SESSION['verify_code'] = $code;
        $_SESSION['verify_time'] = time();

        wp_mail(
            $_SESSION['verify_email'],
            'Your New Verification Code',
            'Your new verification code is: ' . $code . "\n\nThis code is valid for 10 minutes."
        );

        $error = 'A new code has been sent.';
        $step  = '1.5';
    } else {
        $error = 'Your session expired. Please start again.';
        $step  = '1';
    }
}

/*--------------------------------------------------------------
 STEP 1.5 → 2 — Verify Code
--------------------------------------------------------------*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_code']) && $step == '1.5') {
    $entered_code = sanitize_text_field($_POST['verification_code']);
    $is_expired   = (time() - ($_SESSION['verify_time'] ?? 0)) > 600;

    if ($is_expired) {
        $error = 'Your verification code has expired. Request a new one.';
        $step = '1.5';
    } elseif ($entered_code == $_SESSION['verify_code']) {
        $step = 2;
    } else {
        $error = 'Incorrect verification code.';
        $step  = '1.5';
    }
}

/*--------------------------------------------------------------
 STEP 2 — Save PASSWORD ONLY, move to STEP 3
--------------------------------------------------------------*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['continue_to_step3']) && $step == 2) {
    $_SESSION['reg_password'] = $_POST['password'];
    $step = 3;
}

/*--------------------------------------------------------------
 STEP 3 — FINAL REGISTRATION + USERNAME + COUNTRY + GENDER
--------------------------------------------------------------*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finish_register']) && $step == 3) {

    $username = sanitize_user($_POST['username']);
    $password = $_SESSION['reg_password'] ?? '';
    $email    = sanitize_email($_SESSION['verify_email']);
    $country  = sanitize_text_field($_POST['country']);
    $gender   = sanitize_text_field($_POST['gender']);

    // Limit to 7 users
    $user_count = count_users();
    if ($user_count['total_users'] >= 7) {
        $error = 'Registration is closed. Max 7 test accounts allowed.';
        $step  = 1;
    } else {
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

                update_user_meta($user_id, 'country', $country);
                update_user_meta($user_id, 'gender', $gender);

                unset($_SESSION['verify_code'], $_SESSION['verify_email'], $_SESSION['verify_time'], $_SESSION['reg_password']);

                wp_set_current_user($user_id);
                wp_set_auth_cookie($user_id);

                $slug = strtolower(str_replace(' ', '-', $username));
               wp_redirect( home_url('/' . $slug . '/?edit=1') );
                exit;
            } else {
                $error = $user_id->get_error_message();
            }
        } else {
            $error = 'Username or email already exists.';
        }
    }
}

// Step headings
$step_headings = [
  '1'   => ['title' => 'Your Journey Begins Here', 'desc' => 'Enter a valid email to receive a verification code.'],
  '1.5' => ['title' => 'Check Your Email', 'desc' => 'We’ve sent you a 6-digit code. Enter it below to continue.'],
  '2'   => ['title' => 'Choose Password', 'desc' => 'Create a password for your account.'],
  '3'   => ['title' => 'Complete Your Profile', 'desc' => 'Add your name, country and gender.'],

];

$current_heading = $step_headings[$step] ?? ['title'=>'Create an Account','desc'=>'Join the Mushroom community'];

get_header();
?>

<style>
input:focus, textarea:focus { outline:none; border:2px solid #1E2330 !important; background-color:#F6F7F5; color:#111827; }
.code-box { appearance:none; -moz-appearance:textfield; -webkit-appearance:none; }
.code-box::-webkit-outer-spin-button, .code-box::-webkit-inner-spin-button { -webkit-appearance:none; margin:0; }
.code-box:focus { border-color:#1E2330 !important; outline:none; }
button:disabled { background-color:#E0E2D9 !important; cursor:not-allowed; color:#888; }
</style>

<div class="absolute top-6 left-6 z-50">
  <a href="<?php echo home_url(); ?>">
    <img src="<?= get_template_directory_uri(); ?>/images/mk-logo2.png" alt="MK Logo" class="h-10 w-auto">
  </a>
</div>

<div class="min-h-screen grid grid-cols-1 md:grid-cols-2">
  <div class="flex items-center justify-center bg-white px-6 py-12 md:py-24">
    <div class="w-full max-w-md space-y-4">

    <?php if (count_users()['total_users'] >= 7): ?>
      <div class="p-4 bg-yellow-100 text-yellow-800 rounded text-center">
          🚨 Registration is closed — 7 test accounts already created.
      </div>
    <?php else: ?>
      <form method="post" class="space-y-4">
        <?php if ($error): ?>
          <div class="p-3 bg-red-100 text-red-700 rounded"><?php echo esc_html($error); ?></div>
        <?php endif; ?>

        <h2 class="text-4xl font-bold text-center mb-2">
          <?php echo esc_html($current_heading['title']); ?>
        </h2>
        <p class="text-center text-gray-500 mb-6">
          <?php echo esc_html($current_heading['desc']); ?>
        </p>

        <?php
        $old_country = $_POST['country'] ?? '';
        $old_gender  = $_POST['gender'] ?? '';
        ?>

        <?php if ($step == 1): ?>
          <input type="hidden" name="step" value="1">
          <input type="email" name="email" required placeholder="Email address" class="w-full px-4 py-3 rounded-xl bg-[#F6F7F5] border" value="<?= esc_attr($_POST['email'] ?? '') ?>">
          <button type="submit" name="send_code" class="w-full bg-[#1E2330] text-white py-3 rounded-xl hover:bg-gray-900">Continue →</button>
          
          <div class="text-center mt-4 text-sm">
            Already have an account? <span class="text-purple-600"><a href="/mk/login">Log in</a></span>
          </div>

       <?php elseif ($step == '1.5'): ?>
            <input type="hidden" name="step" value="1.5">
            <label class="block mb-2 text-sm mt-3 text-center">Verification Code</label>

            <div id="code-inputs" class="flex space-x-2 mb-4 justify-center">
                <?php for ($i=0;$i<6;$i++): ?>
                    <input type="tel" pattern="[0-9]*" inputmode="numeric" maxlength="1" autocomplete="one-time-code"
                          class="code-box w-12 h-12 text-center text-xl border rounded-xl bg-[#F6F7F5]" />
                <?php endfor; ?>
            </div>

            <input type="hidden" name="verification_code" id="verification_code_final">

            <button type="submit" name="verify_code" class="w-full bg-[#1E2330] text-white py-3 rounded-xl hover:bg-gray-900">Verify & Continue →</button>

            <button type="submit" name="resend_code" class="w-full text-center text-purple-600 hover:underline mt-2">Resend Code</button>

            <p id="resend-timer" class="text-center text-sm text-gray-600 mt-2">
                Code expires in 10:00
            </p>

        <?php elseif ($step == 2): ?>
          <input type="hidden" name="step" value="2">
          
          <!-- PASSWORD ONLY -->
                <div class="relative w-full">
                <input 
                    type="password" 
                    name="password" 
                    required 
                    id="password-field"
                    placeholder="Password"
                    class="w-full px-4 py-3 rounded-xl bg-[#F6F7F5] border pr-12"
                >

               <i id="togglePassword" class="fa-regular fa-eye absolute right-4 top-1/2 -translate-y-1/2 text-gray-600 cursor-pointer"></i>

                </div>

          <button type="submit" name="continue_to_step3"
                  class="px-6 py-3 rounded-xl bg-[#1E2330] text-white w-full mt-6">Continue →</button>

        <?php elseif ($step == 3): ?>
          <input type="hidden" name="step" value="3">

          <!-- USERNAME MOVED HERE -->
          <label class="block text-sm mb-2">Name</label>
            <input 
                type="text" 
                name="username" 
                required 
                placeholder="Name"
                autocorrect="off"
                autocapitalize="off"
                spellcheck="false"
                autocomplete="off"
                class="w-full px-4 py-3 rounded-xl bg-[#F6F7F5] border"
                value="<?= esc_attr($_SESSION['reg_username'] ?? '') ?>"

            >

          <label class="block text-sm mb-2 mt-4">Country</label>
          <div class="relative w-full">
            <input type="text" name="country" autocomplete="off" id="country-input" required placeholder="Start typing your country..." class="w-full px-4 py-3 rounded-xl bg-[#F6F7F5] border" value="<?= esc_attr($old_country) ?>">
            <ul id="country-list" class="absolute left-0 top-full border rounded-xl bg-white max-h-40 overflow-y-auto mt-1 hidden z-50 w-full"></ul>
          </div>

          <label class="block text-sm mb-2 mt-4">Gender</label>

          <div x-data="{ open:false }" class="relative w-full">
              <select 
                  name="gender" 
                  id="gender-select"
                  x-on:focus="open = true"
                  x-on:blur="open = false"
                  required
                  class="w-full px-4 py-3 rounded-xl bg-[#F6F7F5] border appearance-none"
              >
                  <option value="" disabled <?= $old_gender==''?'selected':'' ?>>Select your gender</option>
                  <option value="Male" <?= $old_gender=='Male'?'selected':'' ?>>Male</option>
                  <option value="Female" <?= $old_gender=='Female'?'selected':'' ?>>Female</option>
                  <option value="Other" <?= $old_gender=='Other'?'selected':'' ?>>Other</option>
              </select>

              <svg xmlns="http://www.w3.org/2000/svg" 
                  :class="{ 'rotate-180': open }"
                  class="w-4 h-4 transform transition-transform absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none"
                  fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
          </div>

          <button type="submit" name="finish_register"
                  class="px-6 py-3 rounded-xl bg-[#1E2330] text-white mt-6 w-full">
              Finish & Create Account
          </button>

        <?php endif; ?>
      </form>
    <?php endif; ?>
    </div>
  </div>

  <div class="hidden md:flex items-center justify-center bg-[#EDC1D9]">
    <img src="<?= get_template_directory_uri(); ?>/images/main-screen.png" alt="Illustration" class="w-[86%]">
  </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
  const countryInput = document.getElementById("country-input");
  const countryList = document.getElementById("country-list");
  const genderSelect = document.getElementById("gender-select");
  const finishBtn = document.getElementById("finish-btn");

  function updateFinishBtn() {
      if (!finishBtn) return;
      finishBtn.disabled = !(countryInput.value.trim() && genderSelect.value.trim());
  }

  if(countryInput && countryList && genderSelect){
      countryInput.addEventListener("input", () => {
          const val = countryInput.value.toLowerCase();
          countryList.innerHTML = "";
          if (!val) { countryList.classList.add("hidden"); updateFinishBtn(); return; }
          const matches = <?php echo json_encode($countries); ?>.filter(c => c.toLowerCase().includes(val));
          matches.forEach(c => {
              const li = document.createElement("li");
              li.textContent = c;
              li.className = "px-4 py-2 cursor-pointer hover:bg-gray-200";
              li.addEventListener("click", () => { countryInput.value = c; countryList.classList.add("hidden"); updateFinishBtn(); });
              countryList.appendChild(li);
          });
          countryList.classList.toggle("hidden", matches.length===0);
          updateFinishBtn();
      });
      genderSelect.addEventListener("change", updateFinishBtn);
      document.addEventListener("click", e => { if(!countryInput.contains(e.target) && !countryList.contains(e.target)) countryList.classList.add("hidden"); });
      updateFinishBtn();
  }

  // Verification code inputs
  const codeInputs = document.querySelectorAll("#code-inputs input");
  const codeHidden = document.getElementById("verification_code_final");

  function updateHidden() {
      codeHidden.value = Array.from(codeInputs).map(i => i.value).join('');
  }

  function focusNext(idx) { if(idx < codeInputs.length-1) codeInputs[idx+1].focus(); }
  function focusPrev(idx) { if(idx > 0) codeInputs[idx-1].focus(); }

  codeInputs.forEach((input, idx) => {
      input.addEventListener("input", () => {
          input.value = input.value.replace(/\D/g,'').slice(0,1);
          if(input.value) focusNext(idx);
          updateHidden();
      });
      input.addEventListener("keydown", e => {
          if(e.key==="Backspace" && !input.value) focusPrev(idx);
      });
  });

  codeInputs[0]?.addEventListener("paste", e => {
      e.preventDefault();
      const pasteData = e.clipboardData.getData('text').replace(/\D/g,'').slice(0, codeInputs.length);
      pasteData.split('').forEach((char,i)=>codeInputs[i].value=char);
      codeInputs[Math.min(pasteData.length,codeInputs.length-1)].focus();
      updateHidden();
  });
});
</script>

<script>
document.addEventListener("DOMContentLoaded", () => {
  const timerEl = document.getElementById("resend-timer");
  if (timerEl) {
      let remaining = 10 * 60;
      function updateResendTimer() {
          if (remaining <= 0) {
              timerEl.textContent = "Your verification code has expired.";
              return;
          }
          const minutes = Math.floor(remaining / 60);
          const seconds = remaining % 60;
          timerEl.textContent = `Code expires in ${minutes}:${seconds.toString().padStart(2,'0')}`;
          remaining--;
          setTimeout(updateResendTimer, 1000);
      }
      updateResendTimer();
  }
});
</script>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const pwd = document.getElementById("password-field");
    const toggle = document.getElementById("togglePassword");

    if (pwd && toggle) {
        toggle.addEventListener("click", () => {
            const isHidden = pwd.type === "password";
            pwd.type = isHidden ? "text" : "password";

            toggle.classList.toggle("fa-eye");
            toggle.classList.toggle("fa-eye-slash");
        });
    }
});
</script>




<?php get_footer(); ?>
