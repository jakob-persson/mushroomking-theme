<?php
/**
 * Reusable Summary Modal for Mushroom Adventures
 */
?>


<div
  x-show="$store.summaryModal.isOpen"
  x-trap.noscroll="$store.summaryModal.isOpen"
  x-init="$watch('$store.summaryModal.isOpen', value => {
    document.body.style.overflow = value ? 'hidden' : '';
  })"
  class="fixed inset-0 flex items-end bg-black bg-opacity-40 z-50"
  x-cloak
>
  <!-- Modal Container -->
  <div
    @click.away="$store.summaryModal.isOpen = false"
    class="relative bg-white w-full mx-auto shadow-xl transition-transform duration-300 ease-in-out transform translate-y-0 flex flex-col rounded-t-2xl flex-grow max-h-[95dvh]"
  >
    <!-- Close Icon -->
    <button
      @click="$store.summaryModal.isOpen = false"
      class="absolute -top-[32px] right-5 text-white hover:text-black text-xl z-50"
      aria-label="Close modal"
    >
      <i class="fas fa-times"></i>
    </button>

    <!-- Modal Header -->
    <div class="p-6 flex-shrink-0 bg-white z-10 border-b rounded-t-2xl gilroy">
      Mushroom adventure
    </div>

    <!-- Scrollable Content -->
    <div class="px-6 pb-12 overflow-y-auto flex-grow lg:px-[30%] space-y-2">
    <h2 class="text-4xl gilroy font-bold" x-text="$store.summaryModal.hunt.location"></h2>
    <p class="text-sm text-gray-500 mt-1" x-text="new Date($store.summaryModal.hunt.timestamp * 1000).toLocaleDateString()"></p>
      <!-- Image -->
      <template x-if="$store.summaryModal.hunt.photo">
        <img :src="$store.summaryModal.hunt.photo" alt="Adventure photo" class="w-full rounded-2xl object-cover mt-6" />
      </template>

      <!-- Total weight -->
      <div class="flex justify-between border-t border-gray-100 pt-4">
        <span class="text-gray-600">Total weight</span>
        <span class="font-semibold text-[#1E2330]" x-text="$store.summaryModal.hunt.total_kg + ' kg'"></span>
      </div>

      <!-- Mushroom types -->
      <template x-if="$store.summaryModal.hunt.types">
        <div class="border-t border-gray-100 pt-4">
          <h3 class="text-sm font-semibold mb-2">Mushroom types</h3>
          <template x-for="(kg, type) in $store.summaryModal.hunt.types" :key="type">
            <div class="flex justify-between text-sm">
              <span x-text="type"></span>
              <span x-text="kg + ' kg'"></span>
            </div>
          </template>
        </div>
      </template>

      <!-- Optional note -->
      <template x-if="$store.summaryModal.hunt.notes">
        <div class="border-t border-gray-100 pt-4">
          <h3 class="text-sm font-semibold mb-2">Notes</h3>
          <p class="text-gray-700 text-sm" x-text="$store.summaryModal.hunt.notes"></p>
        </div>
      </template>
    </div>

    <!-- Fixed Footer -->
    <div class="px-6 py-4 border-t bg-white lg:px-[30%]">
      <button
        @click="$store.summaryModal.isOpen = false"
        class="w-full bg-[#1E2330] text-white py-2.5 rounded-xl hover:opacity-80 transition"
      >
        Close
      </button>
    </div>
  </div>
</div>
