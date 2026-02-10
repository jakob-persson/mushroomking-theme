{{-- resources/views/profile/show.blade.php --}}

<x-app-layout>

    <x-slot name="bodyClass">
        bg-[#EFF0EC]
    </x-slot>
    @php
        $isOwner = auth()->check() && auth()->id() === $user->id;

        $avatarUrl = $user->avatar_path
            ? \Illuminate\Support\Facades\Storage::url($user->avatar_path)
            : asset('images/avatar-fallback.png');

        $presentationClean = strip_tags($user->presentation ?? '');
        $userCountry = $user->country ?? '';
    @endphp

    <div class="max-w-2xl mx-auto py-10 px-6">
        <div class="bg-white rounded-3xl p-6 shadow-sm">

            <div class="flex items-start justify-between gap-4">
                <div class="flex items-center gap-4">
                    {{-- ✅ Avatar --}}
                    <img
                        id="profile-avatar"
                        src="{{ $avatarUrl }}"
                        alt="Avatar"
                        class="w-14 h-14 rounded-full object-cover bg-[#eff0ec]"
                    />

                   <div>
                    <div id="profile-name" class="text-xl font-semibold">
                        {{ $user->name }}
                    </div>

                    {{-- ✅ Country --}}
                    <div id="profile-country" class="text-sm text-gray-500 flex items-center gap-1">
                        <span>📍</span>
                        <span>
                            {{ $user->country ?: 'No country selected' }}
                        </span>
                    </div>

                    <div class="text-sm text-gray-400">
                        slug: {{ $user->slug }}
                    </div>
                </div>

                </div>

                @if($isOwner)
                    <button
                        id="edit-profile-btn"
                        type="button"
                        class="bg-[#eff0ec] hover:bg-gray-300 px-4 py-2 rounded-xl text-sm"
                    >
                        Edit profile
                    </button>
                @endif
            </div>

            <div id="profile-presentation" class="mt-4">
                {!! nl2br(e($user->presentation ?? 'No presentation yet.')) !!}
            </div>

        </div>
    </div>

    @if($isOwner)
        @include('partials.edit-profile-modal', [
          'avatarUrl' => $avatarUrl,
          'presentationClean' => $presentationClean,
          'userCountry' => $userCountry,
          'user' => $user,
        ])

        <script>
          (function bindEditProfileModal() {
            const btn = document.getElementById('edit-profile-btn');
            if (btn) {
              btn.addEventListener('click', () => {
                (function openWhenReady(){
                  if (!window.Alpine || typeof Alpine.store !== 'function') return setTimeout(openWhenReady, 10);
                  Alpine.store('editProfileModal').open();
                })();
              });
            }

            const params = new URLSearchParams(window.location.search);
            if (params.get('edit') === '1') {
              (function openWhenReady(){
                if (!window.Alpine || typeof Alpine.store !== 'function') return setTimeout(openWhenReady, 10);
                Alpine.store('editProfileModal').open();
              })();
            }
          })();
        </script>

        {{-- ✅ Live update after save --}}
        <script>
         window.addEventListener('profile:updated', (e) => {
            const u = e.detail || {}

            const nameEl = document.getElementById('profile-name')
            if (nameEl && u.name) nameEl.textContent = u.name

            const presEl = document.getElementById('profile-presentation')
            if (presEl) presEl.innerHTML = (u.presentation ? u.presentation.replace(/\n/g, '<br>') : 'No presentation yet.')

            const countryEl = document.getElementById('profile-country')
            if (countryEl) {
                countryEl.innerHTML = `<span>📍</span><span>${u.country || 'No country selected'}</span>`
            }
            })


          // ✅ Live update avatar after upload
          window.addEventListener('profile:avatar-updated', (e) => {
            const url = e.detail?.url
            if (!url) return

            // cache-bust så du alltid ser nya bilden
            const busted = url + (url.includes('?') ? '&' : '?') + 't=' + Date.now()

            const img = document.getElementById('profile-avatar')
            if (img) img.src = busted
          })
        </script>
    @endif
</x-app-layout>
