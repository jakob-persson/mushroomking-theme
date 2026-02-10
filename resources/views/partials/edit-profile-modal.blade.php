{{-- resources/views/profile/partials/edit-profile-modal.blade.php --}}

@php
  // Förväntas skickas in från profile.show:
  // $user, $avatarUrl, $presentationClean, $userCountry
@endphp

<style>
  /* Samma feel som din WP-modal */
  input { color:#1E2330 !important; background-color:#eff0ec!important; }

  #profileAvatarPreview{ width:8rem;height:8rem;object-fit:cover;border-radius:9999px;display:block; }
  .avatar-container{ width:8rem;height:8rem;position:relative; }

  .avatar-camera-btn{
    position:absolute;bottom:.25rem;right:.25rem;width:2rem;height:2rem;border-radius:9999px;
    background-color:#1E2330;color:#fff;display:flex;align-items:center;justify-content:center;
    font-size:.75rem;opacity:.9;transition:opacity .2s;
  }
  .avatar-camera-btn:hover{ opacity:1; }

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

  /* When input/textarea has focus or value */
  .edit .relative input:focus + label,
  .edit .relative input:not(:placeholder-shown) + label,
  .edit .relative textarea:focus + label,
  .edit .relative textarea:not(:placeholder-shown) + label {
    top: 0.42rem;
    font-size: 0.65rem;
    color: #1E2330;
    background: #eff0ec;
    padding: 0 0;
  }

  /* Remove all glow/border shadows on focus */
  .edit input:focus,
  .edit textarea:focus {
    border-color: #1E2330 !important;
    outline: none !important;
    box-shadow: none !important;
    -webkit-box-shadow: none !important;
    -moz-box-shadow: none !important;
  }

  #editProfileSaveBtn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }
</style>

<div
  x-data="editProfileModal({
    presentation: @js($presentationClean),
    country: @js($userCountry),
    avatarUrl: @js($avatarUrl),
    avatarUploadUrl: @js(route('profileModal.avatar')),
    csrf: @js(csrf_token()),
    countries: @js(config('countries.list')),
  })"
  x-show="$store.editProfileModal.isOpen"
  x-transition.opacity
  x-cloak
  @keydown.escape.window="$store.editProfileModal.close()"
  class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 edit"
>
  <div
    @click.outside="$store.editProfileModal.close()"
    class="bg-white w-full max-w-xl lg:rounded-2xl shadow-xl flex flex-col h-full lg:min-h-[80vh] lg:max-h-[90vh] overflow-hidden relative"
  >
    {{-- Header --}}
    <div class="flex items-center px-6 py-4 relative border-b border-[#eff0ec]">
      <h1 class="absolute left-1/2 -translate-x-1/2 font-semibold text-xl lg:text-2xl">
        Edit Profile
      </h1>

      <button
        type="button"
        @click="$store.editProfileModal.close()"
        class="ml-auto w-10 h-10 flex items-center justify-center rounded-full bg-[#eff0ec] hover:bg-gray-300 transition text-xl"
        aria-label="Close"
      >
        &times;
      </button>
    </div>

    {{-- User info --}}
    <div class="px-6 flex items-center space-x-3 my-4">
      <div class="avatar-container">
        <img id="profileAvatarPreview" :src="avatarPreview" alt="Profile avatar">

        <button
          type="button"
          class="avatar-camera-btn"
          title="Change profile image"
          @click="$refs.avatarFileInput.click()"
        >
          📷
        </button>
      </div>

      <input
        type="file"
        x-ref="avatarFileInput"
        accept="image/*"
        class="hidden"
        @change="uploadAvatar($event)"
      >

      <div class="flex flex-col leading-tight">
        <span class="font-semibold text-sm mb-1">{{ $user->name }}</span>

        <span class="text-sm flex items-center gap-1 text-gray-600">
          <span>📍</span>
          <span x-text="country || 'No country selected'"></span>
        </span>
      </div>
    </div>

    {{-- Main content --}}
    <div class="px-6 overflow-y-auto flex-grow space-y-7 pb-2">

      {{-- Presentation card --}}
      <div
        class="rounded-[14px] text-sm cursor-pointer"
        style="margin-top:10px"
        @click="tempText = presentationTemp; view='text'; openOverlay = true;"
      >
        <span x-text="presentationTemp || 'Say something about yourself...'"></span>
      </div>

      {{-- Form --}}
      <form
        id="editProfileForm"
        method="post"
        action="{{ route('profileModal.update') }}"
        class="space-y-4"
        x-on:submit.prevent="saveProfile()"
      >
        @csrf

        <input type="hidden" name="presentation" x-model="presentationTemp">

        <div class="relative">
          <input
            type="text"
            name="display_name"
            required
            placeholder=" "
            value="{{ old('display_name', $user->name) }}"
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
            value="{{ old('email', $user->email) }}"
            class="peer w-full"
          />
          <label>Email address</label>
        </div>

        {{-- Country autocomplete --}}
        <div class="relative mt-4">
          <input
            type="text"
            name="country"
            placeholder=" "
            autocomplete="off"
            x-model="country"
            @focus="filtered = countries; open = true"
            @input="filtered = countries.filter(c => c.toLowerCase().includes((country||'').toLowerCase())); open = filtered.length>0"
            class="peer w-full px-4 py-3 rounded-xl bg-[#eff0ec] border focus:border-[#1E2330] focus:outline-none"
          />
          <label>Country</label>

          <ul
            x-show="open"
            @click.away="open = false"
            class="absolute left-0 top-full z-50 w-full max-h-40 overflow-y-auto bg-white border rounded-xl mt-1 shadow"
          >
            <template x-for="c in filtered" :key="c">
              <li
                @click="country = c; open = false"
                class="px-4 py-2 hover:bg-gray-200 cursor-pointer"
                x-text="c"
              ></li>
            </template>
            <li x-show="filtered.length === 0" class="px-4 py-2 text-gray-400 cursor-default">
              No results
            </li>
          </ul>
        </div>
      </form>

      {{-- Presentation overlay --}}
      <div
        class="absolute inset-0 bg-white z-50 overflow-y-auto"
        x-show="openOverlay"
        x-transition
        @click.stop
      >
        <div class="flex items-center px-6 py-4 relative border-b border-[#eff0ec]">
          <button
            type="button"
            @click="back()"
            class="text-sm flex items-center justify-center gap-2 absolute left-6 bg-[#eff0ec] hover:bg-gray-300 w-10 h-10 rounded-full"
            aria-label="Back"
          >
            ←
          </button>

          <h2 class="text-xl lg:text-2xl w-full text-center" x-text="view==='text' ? 'Edit your presentation' : ''"></h2>
        </div>

        <template x-if="view==='text'">
          <div class="flex flex-col flex-1 p-6">
            <textarea
              x-model="tempText"
              class="w-full h-52 p-3 bg-[#eff0ec] rounded-2xl"
              placeholder="Your presentation..."
            ></textarea>

            <button
              type="button"
              @click="saveText()"
              class="bg-[#1E2330] text-white px-4 py-2 rounded-xl mt-4 self-start"
            >
              Save
            </button>
          </div>
        </template>
      </div>

    </div>

    {{-- Footer --}}
    <div class="px-6 py-4 border-t border-gray-200 bg-white">
     <button
        id="editProfileSaveBtn"
        type="submit"
        form="editProfileForm"
        class="w-full bg-[#1E2330] text-white py-3 rounded-xl hover:bg-gray-900 transition"
        :disabled="isUploadingAvatar || isSaving"
      >
        <span x-show="!isSaving">Save Changes</span>
        <span x-show="isSaving">Saving…</span>
      </button>

      <p class="text-xs text-center mt-2" x-text="saveMessage"></p>

    </div>
  </div>
</div>
