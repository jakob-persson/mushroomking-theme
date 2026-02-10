{{-- resources/views/auth/create-account.blade.php --}}
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="csrf-token" content="{{ csrf_token() }}">

  {{-- Tailwind (om du kör Vite + Tailwind redan, ta bort CDN och använd @vite istället) --}}
  <script src="https://cdn.tailwindcss.com"></script>

  {{-- FontAwesome (för eye-icon om du vill ha exakt samma) --}}
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>

  <style>
    input:focus, textarea:focus { outline:none; border:2px solid #1E2330 !important; background-color:#F6F7F5; color:#111827; }
    .code-box { appearance:none; -moz-appearance:textfield; -webkit-appearance:none; }
    .code-box::-webkit-outer-spin-button, .code-box::-webkit-inner-spin-button { -webkit-appearance:none; margin:0; }
    .code-box:focus { border-color:#1E2330 !important; outline:none; }
    button:disabled { background-color:#E0E2D9 !important; cursor:not-allowed; color:#888; }
  </style>
</head>

<body class="bg-white">

  {{-- LOGO (samma placering) --}}
  <div class="absolute top-6 left-6 z-50">
    <a href="{{ url('/') }}">
      {{-- Byt till din riktiga asset --}}
      <img src="{{ asset('images/mk-logo2.png') }}" alt="MK Logo" class="h-10 w-auto">
    </a>
  </div>

  <div class="min-h-screen grid grid-cols-1 md:grid-cols-2">
    {{-- LEFT --}}
    <div class="flex items-center justify-center bg-white px-6 py-12 md:py-24">
      <div class="w-full max-w-md space-y-4">

        @if($reg_closed)
          <div class="p-4 bg-yellow-100 text-yellow-800 rounded text-center">
            🚨 Registration is closed. Max 3 test accounts allowed.
          </div>
        @endif

        {{-- Errors --}}
        @if ($errors->any())
          <div class="p-3 bg-red-100 text-red-700 rounded">
            {{ $errors->first() }}
          </div>
        @endif

        <h2 class="text-4xl font-bold text-center mb-2">
          @php
            $headings = [
              '1'   => ['title' => 'Your Journey Begins Here', 'desc' => 'Enter a valid email to receive a verification code.'],
              '1.5' => ['title' => 'Check Your Email', 'desc' => 'We’ve sent you a 6-digit code. Enter it below to continue.'],
              '2'   => ['title' => 'Choose Password', 'desc' => 'Create a password for your account.'],
              '3'   => ['title' => 'Complete Your Profile', 'desc' => 'Add your name, country and gender.'],
            ];
            $h = $headings[$step] ?? ['title' => 'Create an Account', 'desc' => 'Join the Mushroom community'];
          @endphp
          {{ $h['title'] }}
        </h2>

        <p class="text-center text-gray-500 mb-6">
          {{ $h['desc'] }}
        </p>

        {{-- STEP 1 --}}
        @if($step === '1')
          <form method="POST" action="{{ route('register.flow.send_code') }}" class="space-y-4">
            @csrf

            <input
              type="email"
              name="email"
              required
              placeholder="Email address"
              class="w-full px-4 py-3 rounded-xl bg-[#F6F7F5] border {{ $reg_closed ? 'opacity-60 cursor-not-allowed' : '' }}"
              value="{{ old('email') }}"
              {{ $reg_closed ? 'disabled' : '' }}
            >

            <button
              type="submit"
              class="w-full bg-[#1E2330] text-white py-3 rounded-xl hover:bg-gray-900"
              {{ $reg_closed ? 'disabled' : '' }}
            >
              Continue →
            </button>

            <div class="text-center mt-4 text-sm">
              Already have an account?
              <span class="text-purple-600">
                <a href="{{ route('login') }}">Log in</a>
              </span>
            </div>
          </form>
        @endif

        {{-- STEP 1.5 --}}
        @if($step === '1.5')
          <form method="POST" action="{{ route('register.flow.verify_code') }}" class="space-y-4">
            @csrf

            <label class="block mb-2 text-sm mt-3 text-center">Verification Code</label>

            <div id="code-inputs" class="flex space-x-2 mb-4 justify-center">
              @for($i=0;$i<6;$i++)
                <input type="tel" pattern="[0-9]*" inputmode="numeric" maxlength="1" autocomplete="one-time-code"
                       class="code-box w-12 h-12 text-center text-xl border rounded-xl bg-[#F6F7F5]" />
              @endfor
            </div>

            <input type="hidden" name="verification_code" id="verification_code_final">

            <button type="submit" class="w-full bg-[#1E2330] text-white py-3 rounded-xl hover:bg-gray-900">
              Verify & Continue →
            </button>
          </form>

          {{-- Resend (matchar WP UI) --}}
         <form method="POST" action="{{ route('register.flow.resend_code') }}" class="mt-2">
          @csrf
          <button type="submit" class="w-full text-center text-purple-600 hover:underline mt-2">
            Resend Code
          </button>
        </form>


          <p id="resend-timer" class="text-center text-sm text-gray-600 mt-2">
            Code expires in 10:00
          </p>
        @endif

        {{-- STEP 2 --}}
        @if($step === '2')
          <form method="POST" action="{{ route('register.flow.password') }}" class="space-y-4">
            @csrf

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

            <button type="submit"
              class="px-6 py-3 rounded-xl bg-[#1E2330] text-white w-full mt-6">
              Continue →
            </button>
          </form>
        @endif

        {{-- STEP 3 --}}
        @if($step === '3')
          <form method="POST" action="{{ route('register.flow.finish') }}" class="space-y-4">
            @csrf

            <label class="block text-sm mb-2">Name</label>
            <input
              type="text"
              name="name"
              required
              placeholder="Name"
              autocorrect="off"
              autocapitalize="off"
              spellcheck="false"
              autocomplete="off"
              class="w-full px-4 py-3 rounded-xl bg-[#F6F7F5] border"
              value="{{ old('name') }}"
            >

            <label class="block text-sm mb-2 mt-4">Country</label>
            <div class="relative w-full">
              <input
                type="text"
                name="country"
                autocomplete="off"
                id="country-input"
                required
                placeholder="Start typing your country..."
                class="w-full px-4 py-3 rounded-xl bg-[#F6F7F5] border"
                value="{{ old('country') }}"
              >
              <ul id="country-list" class="absolute left-0 top-full border rounded-xl bg-white max-h-40 overflow-y-auto mt-1 hidden z-50 w-full"></ul>
            </div>

            <label class="block text-sm mb-2 mt-4">Gender</label>
            <div class="relative w-full">
              <select
                name="gender"
                id="gender-select"
                required
                class="w-full px-4 py-3 rounded-xl bg-[#F6F7F5] border appearance-none"
              >
                <option value="" disabled {{ old('gender') ? '' : 'selected' }}>Select your gender</option>
                <option value="Male"   {{ old('gender')==='Male'?'selected':'' }}>Male</option>
                <option value="Female" {{ old('gender')==='Female'?'selected':'' }}>Female</option>
                <option value="Other"  {{ old('gender')==='Other'?'selected':'' }}>Other</option>
              </select>

              <svg xmlns="http://www.w3.org/2000/svg"
                class="w-4 h-4 absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none"
                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </div>

            <button
              id="finish-btn"
              type="submit"
              class="px-6 py-3 rounded-xl bg-[#1E2330] text-white mt-6 w-full"
            >
              Finish & Create Account
            </button>
          </form>
        @endif

      </div>
    </div>

    {{-- RIGHT --}}
    <div class="hidden md:flex items-center justify-center bg-[#EDC1D9]">
      <img src="{{ asset('images/main-screen.png') }}" alt="Illustration" class="w-[86%]">
    </div>
  </div>

  <script>
    document.addEventListener("DOMContentLoaded", () => {
      // Countries autocomplete (step 3)
      const countries = @json($countries);

      const countryInput = document.getElementById("country-input");
      const countryList  = document.getElementById("country-list");
      const genderSelect = document.getElementById("gender-select");
      const finishBtn    = document.getElementById("finish-btn");

      function updateFinishBtn() {
        if (!finishBtn || !countryInput || !genderSelect) return;
        finishBtn.disabled = !(countryInput.value.trim() && genderSelect.value.trim());
      }

      if (countryInput && countryList && genderSelect) {
        countryInput.addEventListener("input", () => {
          const val = countryInput.value.toLowerCase();
          countryList.innerHTML = "";
          if (!val) { countryList.classList.add("hidden"); updateFinishBtn(); return; }

          const matches = countries.filter(c => c.toLowerCase().includes(val));
          matches.forEach(c => {
            const li = document.createElement("li");
            li.textContent = c;
            li.className = "px-4 py-2 cursor-pointer hover:bg-gray-200";
            li.addEventListener("click", () => {
              countryInput.value = c;
              countryList.classList.add("hidden");
              updateFinishBtn();
            });
            countryList.appendChild(li);
          });

          countryList.classList.toggle("hidden", matches.length === 0);
          updateFinishBtn();
        });

        genderSelect.addEventListener("change", updateFinishBtn);
        document.addEventListener("click", e => {
          if (!countryInput.contains(e.target) && !countryList.contains(e.target)) {
            countryList.classList.add("hidden");
          }
        });

        updateFinishBtn();
      }

      // Code inputs (step 1.5)
      const codeInputs = document.querySelectorAll("#code-inputs input");
      const codeHidden = document.getElementById("verification_code_final");

      function updateHidden() {
        if (!codeHidden) return;
        codeHidden.value = Array.from(codeInputs).map(i => i.value).join('');
      }

      function focusNext(idx) { if (idx < codeInputs.length - 1) codeInputs[idx + 1].focus(); }
      function focusPrev(idx) { if (idx > 0) codeInputs[idx - 1].focus(); }

      codeInputs.forEach((input, idx) => {
        input.addEventListener("input", () => {
          input.value = input.value.replace(/\D/g,'').slice(0,1);
          if (input.value) focusNext(idx);
          updateHidden();
        });
        input.addEventListener("keydown", e => {
          if (e.key === "Backspace" && !input.value) focusPrev(idx);
        });
      });

      if (codeInputs[0]) {
        codeInputs[0].addEventListener("paste", e => {
          e.preventDefault();
          const pasteData = e.clipboardData.getData('text').replace(/\D/g,'').slice(0, codeInputs.length);
          pasteData.split('').forEach((char,i)=>codeInputs[i].value=char);
          codeInputs[Math.min(pasteData.length, codeInputs.length-1)].focus();
          updateHidden();
        });
      }

      // Timer (step 1.5)
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

      // Toggle password (step 2)
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
</body>
</html>
