<div
  x-show="open"
  x-cloak
  class="fixed inset-0 z-50"
  @keydown.escape.window="closeModal()"
>
  <!-- backdrop -->
  <div class="absolute inset-0 bg-black/60" @click="closeModal()"></div>

  <!-- modal -->
  <div class="relative max-w-2xl mx-auto mt-16 bg-white rounded-3xl overflow-hidden">
    <div class="p-4 flex justify-between items-center border-b">
      <div class="font-semibold" x-text="adv?.location ?? 'Adventure'"></div>
      <button class="text-sm px-3 py-2 rounded-xl bg-gray-100" @click="closeModal()">Close</button>
    </div>

    <template x-if="adv?.image">
      <img :src="adv.image" class="w-full max-h-[420px] object-cover" alt="">
    </template>

    <div class="p-6 space-y-2">
      <div class="text-sm text-gray-500" x-text="adv?.start_date"></div>
      <div class="text-sm text-gray-500" x-text="adv?.user"></div>
      <p class="text-gray-800 whitespace-pre-line" x-text="adv?.adventure_text"></p>
    </div>
  </div>
</div>
