<x-guest-layout>
    <!-- Top Left Logo -->
    <div class="absolute top-6 left-6 z-50">
        <a href="{{ url('/') }}">
            {{-- Byt till din logo --}}
            <img src="{{ asset('images/mk-logo2.png') }}" alt="MK Logo" class="h-8 w-auto">
        </a>
    </div>

    <div class="min-h-screen grid grid-cols-1 md:grid-cols-2">
        <!-- Left Side: Login Form -->
        <div class="flex md:items-center justify-center bg-white px-6 py-12 mt-[100px] md:mt-0">
            <div class="w-full max-w-md">
                <!-- Session Status -->
                <x-auth-session-status class="mb-4" :status="session('status')" />

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <h2 class="text-4xl font-bold mb-2 text-center text-gray-900">Welcome back</h2>
                    <p class="text-center text-gray-500 mb-6">Log in to your Mushroom page</p>

                    {{-- Email --}}
                    <div class="mb-4">
                        {{-- (valfritt) label: dölj om du vill matcha WP exakt --}}
                        {{-- <x-input-label for="email" :value="__('Email')" class="sr-only" /> --}}
                        <input
                            id="email"
                            name="email"
                            type="email"
                            value="{{ old('email') }}"
                            required
                            autofocus
                            autocomplete="username"
                            placeholder="Email or username"
                            class="w-full px-4 py-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-black text-base bg-[#F6F7F5]"
                        />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    {{-- Password --}}
                    <div class="mb-4">
                        <input
                            id="password"
                            name="password"
                            type="password"
                            required
                            autocomplete="current-password"
                            placeholder="Password"
                            class="w-full px-4 py-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-black text-base bg-[#F6F7F5]"
                        />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    {{-- Remember Me --}}
                    <div class="mb-4 flex items-center">
                        <input id="remember_me" type="checkbox" name="remember" class="mr-2">
                        <label for="remember_me" class="text-gray-500 text-sm">Remember Me</label>
                    </div>

                    <button
                        type="submit"
                        class="w-full bg-[#1E2330] text-white py-3 rounded-xl hover:bg-gray-900 transition duration-300"
                    >
                        Sign in
                    </button>

                    <div class="text-center mt-4">
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="text-sm text-purple-600 hover:underline">
                                Forgot password?
                            </a>
                        @endif
                    </div>

                    <div class="text-center mt-4 text-sm text-gray-700">
                        Don't have an account?
                        <span class="text-purple-600">
                            <a href="{{ route('register') }}">Sign up</a>
                        </span>
                    </div>
                </form>
            </div>
        </div>

        <!-- Right Side -->
        <div class="hidden md:flex items-center justify-center bg-[#EDC1D9]">
            {{-- Byt till din bild --}}
            <img src="{{ asset('images/main-screen.png') }}" alt="Preview" class="w-[86%]">
        </div>
    </div>
</x-guest-layout>
