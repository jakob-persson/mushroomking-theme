<x-app-layout>
  <div class="p-10" x-data>
    <button
      type="button"
      class="px-6 py-3 bg-black text-white rounded-xl"
      @click="$store.modal.isOpen = true"
    >
      Open create adventure modal
    </button>

    @include('partials.create-adventure-modal')
  </div>
</x-app-layout>

