{{-- resources/views/layouts/partials/header.blade.php (eller var din header nu ligger) --}}

@php
  $headerAvatarUrl = auth()->check() && auth()->user()->avatar_path
    ? \Illuminate\Support\Facades\Storage::url(auth()->user()->avatar_path)
    : 'https://www.gravatar.com/avatar/' . md5(strtolower(trim(auth()->user()->email ?? ''))) . '?s=96&d=mp';
@endphp

<header
  x-data="{ isSticky:false }"
  x-init="window.addEventListener('scroll', () => { isSticky = window.scrollY > 200 })"
  class="relative z-50"
>
  <div :class="isSticky ? 'fixed top-0 left-0 right-0 z-50' : ''" class="transition-all duration-300 lg:pt-4">
    <div class="max-w-[1340px] mx-auto px-6">
      <div class="flex items-center justify-between rounded-full bg-white py-3 lg:py-4 pl-8 pr-6 w-full">

        <!-- Left: Logo + Nav -->
        <div class="flex items-center">
          <div class="w-[28px] lg:w-[62px] lg:border-r lg:border-[#111827] lg:pr-4">
            <a href="{{ url('/') }}">
              <img src="{{ asset('images/mk-logo2.png') }}" alt="Logo">
            </a>
          </div>

          <span class="hidden lg:block ml-4 text-sm font-medium">Beta</span>

          <nav class="hidden lg:flex space-x-10 ml-16 text-sm">
            <a href="{{ url('/how-it-works') }}" class="hover:text-[#124C12] transition">How it work's</a>
            <a href="{{ url('/get-started') }}" class="hover:text-[#124C12] transition">Get started</a>
          </nav>
        </div>

        <!-- Right -->
        <div class="flex items-center space-x-2.5 lg:space-x-6">

          @auth
            <!-- Add adventure -->
            <button
              type="button"
              @click="$store?.modal && ($store.modal.isOpen = true)"
              class="lg:flex items-center gap-2 bg-[#eff0ec] text-[#111827] px-4 py-4 rounded-xl font-medium text-sm"
            >
              <span class="hidden lg:flex">Add adventure</span>
            </button>

            <!-- Insights -->
            <a
              href="{{ url('/insights') }}"
              class="hover:opacity-90 hidden lg:flex items-center gap-2 bg-[#CEE027] text-[#111827] px-5 py-4 rounded-xl font-medium text-sm"
            >
              <span>Insights</span>
            </a>

            <!-- Avatar dropdown -->
            <div x-data="{ open:false }" class="relative w-10 h-10">
              <button @click="open = !open" class="relative w-10 h-10 focus:outline-none" type="button">
                <img
                  data-avatar
                  id="header-avatar"
                  src="{{ $headerAvatarUrl }}"
                  class="w-10 h-10 rounded-full object-cover"
                  alt="Avatar"
                >
                <span class="absolute -bottom-1 -right-1 w-5 h-5 bg-[#CEE027] rounded-full flex items-center justify-center text-[#111827] shadow-sm">
                  <span class="text-[10px]" :class="open ? 'rotate-180' : ''" style="display:inline-block; transition:transform .2s;">▾</span>
                </span>
              </button>

              <div
                x-show="open"
                @click.outside="open = false"
                x-transition
                class="absolute right-0 mt-3 w-64 bg-white rounded-xl shadow-lg z-50 overflow-hidden"
              >
                <div class="flex items-center p-4 border-b">
                  <img
                    data-avatar
                    src="{{ $headerAvatarUrl }}"
                    class="w-12 h-12 rounded-full object-cover"
                    alt="Avatar"
                  >
                  <div class="ml-3">
                    <p class="font-semibold text-gray-800">{{ auth()->user()->name }}</p>
                    <a href="{{ url('/u/' . auth()->user()->slug) }}" class="text-sm text-purple-600 hover:underline">View Profile</a>
                  </div>
                </div>

                <div class="py-2">
                  <a href="{{ url('/settings') }}" class="block px-4 py-2 hover:bg-gray-100">Settings</a>

                  <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full text-left px-4 py-2 hover:bg-gray-100">Logout</button>
                  </form>
                </div>
              </div>
            </div>

          @endauth

          @guest
            <!-- Guest: Create + Login -->
            <a
              href="{{ route('register.flow.show') }}"
              class="hover:opacity-90 items-center gap-2 bg-[#eff0ec] text-[#111827] px-4 py-4 rounded-xl font-medium text-xs lg:text-sm"
            >
              <span>Create Account</span>
            </a>

            <a
              href="{{ route('login') }}"
              class="hover:opacity-90 lg:flex items-center gap-2 bg-[#111827] text-white px-4 py-4 rounded-full font-medium text-xs lg:text-sm"
            >
              <span>Log in</span>
            </a>
          @endguest

          <!-- Mobile hamburger (basic) -->
          <div x-data="{ open:false }" class="lg:hidden relative">
            <button @click="open = !open" class="px-2 rounded-md relative z-50" type="button">
              <span x-show="!open">☰</span>
              <span x-show="open" x-cloak>✕</span>
            </button>

            <div
              x-show="open"
              x-cloak
              x-transition
              class="fixed inset-0 bg-white z-40 flex flex-col p-8"
            >
              <div class="absolute left-8 top-[44px] text-sm font-regular">Menu</div>

              <nav class="flex flex-col space-y-8 text-2xl font-bold mt-20">
                <a href="{{ url('/how-it-works') }}" class="hover:text-[#124C12] transition">How it work's</a>
                <a href="{{ url('/get-started') }}" class="hover:text-[#124C12] transition">Get started</a>
              </nav>

              <div class="mt-auto space-y-4">
                @auth
                  <a href="{{ url('/insights') }}" class="block text-center bg-[#CEE027] text-[#111827] py-4 rounded-xl font-semibold">Insights</a>
                @endauth

                @guest
                  <a href="{{ route('login') }}" class="block text-center bg-[#eff0ec] text-[#111827] py-4 rounded-xl">Log in</a>
                  <a href="{{ route('register.flow.show') }}" class="block text-center bg-[#111827] text-white py-4 rounded-xl">Sign up free</a>
                @endguest
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</header>

{{-- ✅ Live-uppdatera header-avatar när man laddar upp ny profilbild (utan reload) --}}
<script>
  window.addEventListener('profile:avatar-updated', (e) => {
    const url = e.detail?.url
    if (!url) return

    const busted = url + (url.includes('?') ? '&' : '?') + 't=' + Date.now()
    document.querySelectorAll('[data-avatar]').forEach(img => {
      img.setAttribute('src', busted)
    })
  })
</script>
