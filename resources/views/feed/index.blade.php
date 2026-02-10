<x-app-layout>
  <x-slot name="bodyClass">bg-[#EFF0EC]</x-slot>

  <div x-data="adventureModal()" class="min-h-screen bg-[#EFF0EC]">
    <div class="max-w-7xl mx-auto px-6">
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-10">

        {{-- LEFT BAR --}}
        <aside class="hidden lg:block lg:col-span-3 lg:sticky lg:top-0 h-fit pt-10">
          <div class="bg-white rounded-3xl p-6 shadow-sm w-full">
            <div class="flex items-center gap-4 mb-4">
              <div class="w-16 h-16 rounded-full bg-gray-200 shadow-md shrink-0"></div>
              <div>
                <div class="text-lg font-semibold leading-tight">
                  {{ auth()->user()->name }}
                </div>
                <div class="text-sm text-gray-500">
                  Forager
                  @php $country = auth()->user()->country ?? null; @endphp
                  @if($country) • {{ $country }} @endif
                </div>
              </div>
            </div>

            <div class="text-sm text-gray-700 leading-relaxed">
              @php $presentation = auth()->user()->presentation ?? null; @endphp
              {!! $presentation ? nl2br(e($presentation)) : 'No description added yet.' !!}
            </div>
          </div>
        </aside>

        {{-- FEED --}}
        <main
          id="feed"
          class="col-span-12 lg:col-span-6 pt-10 lg:pl-6 lg:pr-4 space-y-8 pb-44
                 lg:overflow-y-scroll lg:h-screen"
        >
          @foreach($adventures as $adv)
            @include('feed._card', ['adv' => $adv])
          @endforeach

          <div id="feed-sentinel" class="h-10"></div>
          <div id="feed-loader" class="hidden text-center text-sm text-gray-500 py-6">
            Loading more…
          </div>
        </main>

        {{-- RIGHT BAR --}}
        <aside class="hidden lg:block lg:col-span-3 lg:sticky lg:top-0 h-fit pt-10">
          <h3 class="text-sm mb-4">Explore foragers</h3>

          <div class="bg-white rounded-3xl p-4 shadow-sm">
            @if(!empty($exploreUsers) && count($exploreUsers))
              <ul class="space-y-1">
                @foreach($exploreUsers as $u)
                  <li>
                    <a
                      href="{{ route('profile.show', $u->slug) }}"
                      class="flex items-center gap-2 p-2 rounded-xl hover:bg-[#eff0ec] transition"
                    >
                      <div class="w-10 h-10 rounded-full bg-gray-200 shadow-sm"></div>
                      <span class="text-sm font-medium">{{ $u->name }}</span>
                    </a>
                  </li>
                @endforeach
              </ul>
            @else
              <p class="text-gray-500 text-sm">No users found.</p>
            @endif
          </div>
        </aside>

      </div>
    </div>

    {{-- behåll dina modaler --}}
    @include('partials.adventure-modal')         {{-- view modal --}}
    @include('partials.create-adventure-modal')  {{-- create/edit modal --}}
  </div>
</x-app-layout>

<script>
document.addEventListener('alpine:init', () => {
  Alpine.data('adventureModal', () => ({
    open: false,
    adv: null,

    openModal(payload) {
      this.adv = payload;
      this.open = true;
      document.body.classList.add('overflow-hidden');
    },

    closeModal() {
      this.open = false;
      this.adv = null;
      document.body.classList.remove('overflow-hidden');
    },
  }));
});
</script>
