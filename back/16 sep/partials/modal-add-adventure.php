<?php
/**
 * Reusable Add Mushroom Adventure Modal
 */
?>



<!-- Modal Overlay -->
<!-- Modal Overlay -->
<div
  x-show="$store.modal.isOpen"
  x-trap.noscroll="$store.modal.isOpen"
  x-init="$watch('$store.modal.isOpen', value => {
    document.body.style.overflow = value ? 'hidden' : '';
  })"
  class="fixed inset-0 flex items-end bg-black bg-opacity-40 z-50"
  x-cloak
>
  <!-- Modal Container -->
  <div
    @click.away="$store.modal.isOpen = false"
    class="relative bg-white w-full mx-auto shadow-xl transition-transform duration-300 ease-in-out transform translate-y-0 flex flex-col rounded-t-2xl flex-grow max-h-[95dvh]"
    x-data="{ tab: 'chanterelles' }"
  >

    <!-- Close Icon -->
    <button
      @click="$store.modal.isOpen = false"
      class="absolute -top-[32px] right-5 text-white hover:text-black text-xl z-50"
      aria-label="Close modal"
    >
      <i class="fas fa-times"></i>
    </button>

    <!-- Modal Header -->
    <div class="p-6 flex-shrink-0 bg-white z-10 px-6 border-b flex-shrink-0 px-4 lg:px-[2%] rounded-t-2xl">
      <h2 class="text-xl gilroy pt-1">Add adventure</h2>
    </div>

    <!-- Scrollable Content -->
    <div class="px-6 pb-12 overflow-y-auto flex-grow lg:px-[30%]">
      <div class="font-bold text-4xl mt-12 gilroy">
        Great! Go ahead and share your latest <span class="text-[#2665D6]">adventure!</span>
      </div>

      <!-- Tabs -->
      <div class="flex overflow-x-auto no-scrollbar space-x-2 mb-4 mt-12">
        <button @click="tab = 'chanterelles'" :class="tab === 'chanterelles' ? 'bg-[#CEE027] text-[#111827]' : 'bg-[#eff0ec] text-[#111827]'" class="flex-shrink-0 px-3 py-2 rounded-full text-sm font-medium whitespace-nowrap">Chanterelles</button>
        <button @click="tab = 'funnel'" :class="tab === 'funnel' ? 'bg-[#CEE027] text-[#111827]' : 'bg-[#eff0ec] text-[#111827]'" class="flex-shrink-0 px-3 py-2 rounded-full text-sm font-medium whitespace-nowrap">Funnel Chanterelles</button>
        <button @click="tab = 'boletus'" :class="tab === 'boletus' ? 'bg-[#CEE027] text-[#111827]' : 'bg-[#eff0ec] text-[#111827]'" class="flex-shrink-0 px-3 py-2 rounded-full text-sm font-medium whitespace-nowrap">Boletus</button>
        <button @click="tab = 'trumpets'" :class="tab === 'trumpets' ? 'bg-[#CEE027] text-[#111827]' : 'bg-[#eff0ec] text-[#111827]'" class="flex-shrink-0 px-3 py-2 rounded-full text-sm font-medium whitespace-nowrap">Trumpets</button>
      </div>

      <!-- Form -->
      <form id="mushroom-form" class="space-y-4">
        <!-- Chanterelles -->
        <div x-show="tab === 'chanterelles'">
          <label class="block text-sm font-regular mb-2">Chanterelles (kg)</label>
          <input name="chanterelles" min="0" class="w-full mb-4 px-4 py-3 rounded text-sm bg-[#F6F7F5] border border-[#E0E2D9]
         focus:outline-none focus:ring-1 focus:ring-[#1E2330] focus:border-[#1E2330]
         focus-visible:outline-none" placeholder="Add amount"/>
        </div>

        <!-- Funnel -->
        <div x-show="tab === 'funnel'">
          <label class="block text-sm font-regular mb-2">Funnel Chanterelles (kg)</label>
          <input name="funnel" class="w-full mb-4 px-4 py-3 rounded text-sm bg-[#F6F7F5] border border-[#E0E2D9]
         focus:outline-none focus:ring-1 focus:ring-[#1E2330] focus:border-[#1E2330]
         focus-visible:outline-none"/>
        </div>

        <!-- Boletus -->
        <div x-show="tab === 'boletus'">
          <label class="block text-sm font-regular mb-2">Boletus (kg)</label>
          <input name="boletus" class="w-full mb-4 px-4 py-3 rounded text-sm bg-[#F6F7F5] border border-[#E0E2D9]
         focus:outline-none focus:ring-1 focus:ring-[#1E2330] focus:border-[#1E2330]
         focus-visible:outline-none"/>
        </div>

        <!-- Trumpets -->
        <div x-show="tab === 'trumpets'">
          <label class="block text-sm font-regular mb-2">Trumpets (kg)</label>
          <input name="trumpets" class="w-full mb-4 px-4 py-3 rounded text-sm bg-[#F6F7F5] border border-[#E0E2D9]
         focus:outline-none focus:ring-1 focus:ring-[#1E2330] focus:border-[#1E2330]
         focus-visible:outline-none"/>
        </div>

        <!-- Photo Upload -->
        <div>
          <label class="block text-sm font-regular mb-2">Upload Photo (optional)</label>
          <div id="upload_box" class="flex flex-col items-center justify-center border border-dashed border-[#E0E2D9] rounded-xl p-6 text-center">
            <label for="mushroom_photo" class="cursor-pointer flex flex-col items-center space-y-2">
              <div class="w-16 h-16 flex items-center justify-center bg-[#F3F3F1] rounded-full">
                <i class="fas fa-camera text-2xl text-[#111827]"></i>
              </div>
              <span class="text-gray-700 text-sm">Add a photo</span>
              <span class="text-gray-400 text-xs">(JPEG or PNG)</span>
            </label>
            <input id="mushroom_photo" name="mushroom_photo" type="file" accept="image/*" class="hidden" />
          </div>
          <div id="image_preview" class="hidden mt-4 text-center">
            <img id="preview_img" src="" alt="Preview" class="w-28 h-28 object-cover rounded-md border mx-auto" />
            <button type="button" id="remove_preview" class="text-sm text-red-600 hover:underline mt-2">Remove Photo</button>
          </div>
        </div>

        <!-- Location -->
        <div>
          <label class="block text-sm font-regular mb-2">Location</label>
          <input name="location" type="text" class="w-full mb-4 px-4 py-3 rounded text-sm bg-[#F6F7F5] border border-[#E0E2D9]
         focus:outline-none focus:ring-1 focus:ring-[#1E2330] focus:border-[#1E2330]
         focus-visible:outline-none" placeholder="Where did you find them?" />
        </div>

        <!-- Start Date -->
        <div>
          <label for="start_date" class="block text-sm font-regular mb-2">Foraging Date</label>
          <input id="start_date" name="start_date" type="date" class="w-full mb-4 px-4 py-3 rounded text-sm bg-[#F6F7F5] border border-[#E0E2D9]
         focus:outline-none focus:ring-1 focus:ring-[#1E2330] focus:border-[#1E2330]
         focus-visible:outline-none" value="<?php echo date('Y-m-d'); ?>" />
        </div>
      </form>
    </div>

    <!-- Saving Overlay -->
    <div id="saving-overlay" class="absolute inset-0 bg-white bg-opacity-80 flex items-center justify-center z-50 hidden">
      <div class="flex flex-col items-center">
        <svg id="saving-spinner" class="animate-spin h-12 w-12 text-black" viewBox="0 0 50 50" xmlns="http://www.w3.org/2000/svg">
          <circle class="opacity-25" cx="25" cy="25" r="20" stroke="currentColor" stroke-width="4" fill="none" />
          <path class="opacity-75" fill="currentColor" d="M25 5a20 20 0 0 1 20 20h-4a16 16 0 0 0-16-16V5z"/>
        </svg>
        <svg id="saving-check" class="hidden h-10 w-10 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <path stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
        </svg>
        <span id="saving-message" class="mt-3 text-sm font-medium">Saving...</span>
      </div>
    </div>

    <!-- Fixed Footer -->
    <div class="px-6 py-3 flex-shrink-0 border-t bg-white lg:px-[30%]">
      <button id="save-button" type="submit" form="mushroom-form" class="w-full bg-[#1E2330] text-white py-2.5 rounded hover:bg-gray-900 transition duration-300 rounded-xl hover:opacity-80">
        <svg id="save-spinner" class="hidden animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
        </svg>
        <span id="save-label">Publish adventure</span>
      </button>
    </div>
  </div>
</div>
