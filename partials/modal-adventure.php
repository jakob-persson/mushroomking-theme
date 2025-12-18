<!-- Modal -->



<div x-data="$store.adventureModal" x-show="isOpen" x-cloak class="fixed inset-0 bg-black/50 z-50 flex justify-center items-end w-full" style="height: calc(var(--vh, 1vh) * 100); padding-top: env(safe-area-inset-top);">
 <div
  @click.away="close()"
  class="relative bg-white w-full shadow-2xl overflow-auto flex flex-col"
  style="height: calc(var(--vh, 1vh) * 100); padding-top: env(safe-area-inset-top);"
>
    <!-- Loading overlay -->
    <div x-show="loading" class="absolute inset-0 z-40 flex items-center justify-center bg-white/70">
      <div class="animate-spin rounded-full w-12 h-12 border-4 border-gray-300 border-t-gray-700"></div>
    </div>

    <!-- Modal content -->
    <div id="modal-body" class=" flex-1 overflow-auto relative">
      <div id="adventure-content"></div>
      <div id="photo-view" class="hidden absolute inset-0 flex justify-center items-center bg-white z-50">
    <!-- Bild kommer här dynamiskt -->
    </div>
    </div>

 

  </div>
</div>
