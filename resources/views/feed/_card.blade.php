@php
  $cover = $adv->coverPhoto;

  $feedImage  = $cover ? asset('storage/' . $cover->feed_path) : null; // ✅ feed 1080
  $modalImage = $cover ? asset('storage/' . $cover->path) : null;      // ✅ modal ratio

  $isOwner = auth()->check() && auth()->id() === $adv->user_id;

  $totalKg = array_sum($adv->types ?? []);
  $totalKgFormatted = $totalKg > 0
    ? rtrim(rtrim(number_format($totalKg, 1, '.', ''), '0'), '.')
    : null;

  $date = optional($adv->start_date)->format('Y-m-d');

  $avatar = $adv->user?->avatar_url ?? null;
  $name   = $adv->user?->name ?? 'Anonymous';

  $payload = [
    'id' => $adv->id,
    'location' => $adv->location,
    'start_date' => $date,
    'adventure_text' => $adv->adventure_text,
    'image' => $modalImage, // ✅ modal
    'total_kg' => $totalKg,
    'user' => [
      'name' => $name,
      'slug' => $adv->user?->slug,
    ],
  ];

  $editPayload = [
    'id' => $adv->id,
    'location' => $adv->location,
    'start_date' => $date,
    'adventure_text' => $adv->adventure_text,
    'types' => $adv->types ?? [],
    'total_kg' => $totalKg,
    'photos' => ($adv->photos ?? collect())->map(fn($p) => [
      'id' => $p->id,
      'url' => asset('storage/' . $p->path), // ✅ modal ratio i edit
      'sort' => $p->sort,
    ])->values(),
  ];
@endphp

<div
  class="relative bg-white rounded-[34px] overflow-hidden shadow-sm cursor-pointer hover:opacity-95 transition"
  x-data="{ menuOpen: false, deleting: false }"
  @click="openModal(@js($payload))"
>
  {{-- IMAGE (full bleed) --}}
  <div class="relative aspect-square bg-gray-200">
    @if($feedImage)
  <img src="{{ $feedImage }}" loading="lazy" class="absolute inset-0 w-full h-full object-cover" alt="">
    @else
      <div class="absolute inset-0 flex items-center justify-center text-gray-500 text-sm">
        No image
      </div>
    @endif

    {{-- TOP BAR --}}
    <div class="absolute top-4 left-4 right-4 flex items-center justify-between pointer-events-none z-10">
      <div class="flex items-center gap-3 min-w-0">
        @if($avatar)
          <img src="{{ $avatar }}" class="w-10 h-10 rounded-full object-cover shadow-sm shrink-0" alt="">
        @else
          <div class="w-10 h-10 rounded-full bg-white/20 backdrop-blur shrink-0"></div>
        @endif

        <div class="text-white text-lg font-medium truncate drop-shadow">
          {{ $name }}
        </div>
      </div>

      @if($isOwner)
        <div class="relative pointer-events-auto" @click.stop>
          <button
            type="button"
            class="w-10 h-10 rounded-full flex items-center justify-center text-white text-2xl leading-none hover:bg-white/10 transition"
            @click.stop="menuOpen = !menuOpen"
            aria-label="Options"
          >
            ⋯
          </button>

          <div
            x-show="menuOpen"
            x-transition
            x-cloak
            @click.away="menuOpen = false"
            class="absolute right-0 mt-2 w-56 bg-white rounded-2xl shadow-lg border border-black/5 overflow-hidden z-30"
          >
            <button
              type="button"
              class="w-full text-left px-4 py-3 text-sm hover:bg-[#EFF0EC] transition"
              data-edit-payload='@json($editPayload)'
              @click.stop="
                menuOpen = false;
                const raw = $el.getAttribute('data-edit-payload');
                window.dispatchEvent(new CustomEvent('adventure:edit', { detail: JSON.parse(raw) }));
              "
            >
              Edit adventure
            </button>

            <button
              type="button"
              class="w-full text-left px-4 py-3 text-sm hover:bg-[#EFF0EC] transition"
              :disabled="deleting"
              @click.stop="
                if (deleting) return;
                if (!confirm('Delete this adventure?')) return;

                deleting = true;

                const csrf = document.querySelector('meta[name=csrf-token]')?.content;

                fetch('{{ route('adventures.destroy', $adv) }}', {
                  method: 'DELETE',
                  headers: {
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                  }
                })
                .then(async (res) => {
                  const text = await res.text();
                  let json = null;
                  try { json = JSON.parse(text); } catch(e) {}
                  if (!res.ok || !json?.success) throw new Error(json?.message || text || 'Could not delete.');
                  $root.remove();
                })
                .catch((err) => {
                  alert(err.message);
                  deleting = false;
                  menuOpen = false;
                });
              "
            >
              <span class="text-red-600 font-semibold">Delete adventure</span>
            </button>
          </div>
        </div>
      @else
        <div class="w-10 h-10"></div>
      @endif
    </div>

    {{-- BOTTOM OVERLAY --}}
    <div class="absolute inset-x-0 bottom-0 p-6 pointer-events-none z-10">
      <div class="absolute inset-x-0 bottom-0 h-52 bg-gradient-to-t from-black/75 to-transparent"></div>

      <div class="relative text-white">
        <div class="text-4xl font-extrabold tracking-tight leading-[1.05] drop-shadow gilroy">
          {{ $adv->location ?? 'Unknown' }}
        </div>

        <div class="mt-2 text-lg opacity-95 drop-shadow">
          {{ $date ?: '—' }}
          @if($totalKgFormatted) • {{ $totalKgFormatted }}kg @endif
        </div>

        @if(!empty($adv->adventure_text))
          <div class="mt-4 text-lg opacity-95 drop-shadow line-clamp-2">
            {{ $adv->adventure_text }}
          </div>
        @endif
      </div>
    </div>
  </div>
</div>

