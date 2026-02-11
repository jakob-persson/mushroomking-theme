{{-- resources/views/partials/create-adventure-modal.blade.php --}}

{{-- Ensure Alpine store exists --}}
<script>
document.addEventListener('alpine:init', () => {
  if (!Alpine.store('modal')) {
    Alpine.store('modal', { isOpen: false });
  } else {
    Alpine.store('modal').isOpen = false; // ✅ nolla alltid vid sidload
  }
});
</script>


{{-- Google Places (move key to env in production) --}}
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCbqJQfWrO-8ajEjM9c68d_Vc5QXQnjWb4&libraries=places"></script>

<style>
  .tooltip { position: relative; }
  .tooltip:hover::after {
    content: attr(data-tip);
    position: absolute;
    top: -32px;
    left: 50%;
    transform: translateX(-50%);
    background: #1E2330;
    color: white;
    padding: 6px 10px;
    font-size: 12px;
    border-radius: 6px;
    white-space: nowrap;
    z-index: 50;
  }

  .spinner {
    width: 40px;
    height: 40px;
    border: 4px solid rgba(30,35,48,0.2);
    border-top-color: #1E2330;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
  }
  @keyframes spin { to { transform: rotate(360deg); } }
</style>

<div
  x-data="mkCreateAdventureModal()"
  x-init="init()"
  x-show="$store.modal.isOpen"
  x-trap.noscroll="$store.modal.isOpen"
  x-cloak
  class="fixed inset-0 bg-black/40 flex items-center justify-center z-50"
>
  <!-- Hidden file input (for NEW uploads only) -->
  <input
    type="file"
    multiple
    class="hidden"
    x-ref="fileInput"
    @change="handlePhotos($event)"
    accept="image/*"
  />

  <div
    class="bg-white w-full max-w-xl lg:rounded-2xl shadow-xl flex flex-col h-full lg:min-h-[80vh] lg:max-h-[90vh] overflow-hidden relative"
    @click.away.self="close()"
  >
    <!-- Header -->
    <div class="flex items-center px-6 py-4 relative border-b border-[#eff0ec]">
      <h1 class="gilroy absolute left-1/2 -translate-x-1/2 font-semibold text-xl lg:text-2xl">
        <span x-text="editingId ? 'Edit adventure' : 'Create adventure'"></span>
      </h1>

      <button
        @click="close()"
        class="ml-auto w-10 h-10 flex items-center justify-center rounded-full bg-[#eff0ec] hover:bg-gray-300 transition dark text-xl"
        type="button"
      >
        ✕
      </button>
    </div>

    <!-- User info -->
    <div class="px-6 flex items-center space-x-3 my-4">
      <div class="w-12 h-12 rounded-full bg-gray-200 object-cover aspect-square"></div>

      <div class="flex flex-col leading-tight">
        <span class="font-semibold text-sm mb-1">{{ auth()->user()->name }}</span>

        <span class="text-sm dark flex items-center gap-3">
          <!-- Clickable Location -->
          <button @click="view = 'location'" class="flex items-center gap-1 hover:opacity-70" type="button">
            <span>📍</span>
            <span x-text="location || 'No location added'"></span>
          </button>

          <!-- Clickable Date -->
          <button @click="view = 'date'" class="flex items-center gap-1 hover:opacity-70" type="button">
            <span>📅</span>
            <span x-text="new Date(startDate).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })"></span>
          </button>
        </span>
      </div>
    </div>

    <!-- Main Content -->
    <div class="px-6 overflow-y-auto flex-grow space-y-7 pb-10">
      <!-- Text Blocks -->
      <template x-for="block in content" :key="block.id">
        <div
          class="rounded-[14px] text-sm cursor-pointer"
          x-text="block.text"
          style="margin-top:10px"
          @click="editText(block)"
        ></div>
      </template>

      <template x-if="content.length === 0">
        <div
          class="rounded-[14px] text-sm text-gray-400 cursor-pointer"
          style="margin-top:10px"
          @click="editText()"
        >
          Say something about your mushroom adventure...
        </div>
      </template>

      <!-- Mushroom Selection -->
      <div>
        <h2 class="font-semibold text-base mb-3">Choose mushroom type</h2>

        <div class="flex gap-2 overflow-x-auto no-scrollbar items-center">
          <template x-for="(m, index) in mushrooms" :key="index">
            <button
              type="button"
              @click="selectMushroom(m)"
              :class="selectedMushroom === m
                ? 'bg-[#E9C0E9] dark border-2 border-[#1E2330]'
                : 'bg-white border-2 border-[#1E2330] dark'"
              class="px-2.5 py-2.5 rounded-lg text-xs font-semibold whitespace-nowrap transition"
              x-text="m"
            ></button>
          </template>

          <div class="relative inline-block">
            <select
              class="w-[110px] px-2.5 py-2.5 pr-8 border-2 border-[#1E2330] rounded-lg text-xs font-semibold bg-white appearance-none"
              @change="handleDropdownSelect($event)"
            >
              <option value="">More</option>
              <template x-for="extra in extraMushrooms" :key="extra">
                <option :value="extra" x-text="extra"></option>
              </template>
            </select>
            <span class="absolute right-2 top-1/2 -translate-y-1/2 pointer-events-none text-[#1E2330] text-xs">▾</span>
          </div>
        </div>

        <!-- Mushroom Amounts -->
        <div class="space-y-3 mt-4">
          <template x-for="input in mushroomInputs" :key="input.type">
            <div class="flex items-center space-x-2">
              <span class="w-32 text-sm font-medium" x-text="input.type"></span>

              <div class="relative flex-1">
                <input
                  type="text"
                  x-model="input.amount"
                  class="w-full p-3 pr-10 bg-[#eff0ec] rounded-[14px] no-spinner"
                  placeholder="Amount"
                />
                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm pointer-events-none">
                  kg
                </span>
              </div>

              <button type="button" @click="removeMushroom(input)" class="dark text-lg">✕</button>
            </div>
          </template>
        </div>
      </div>

      <!-- Photos Preview -->
      <div x-show="existingPhotos.length > 0 || photos.length > 0">
        <h3 class="font-semibold mb-2">Media</h3>

        <!-- Existing photos (from DB) -->
        <template x-if="existingPhotos.length > 0">
          <div class="flex gap-2 flex-wrap mb-2">
            <template x-for="(p, i) in existingPhotos" :key="p.id">
              <div class="relative w-[66px] h-[66px]">
                <img :src="p.url" class="w-full h-full rounded-[12px] object-cover" />
                <button
                  type="button"
                  @click="removeExistingPhoto(i)"
                  class="absolute -top-1 -right-1 w-6 h-6 rounded-full bg-black/70 text-white flex items-center justify-center shadow hover:bg-black cursor-pointer"
                >
                  ✕
                </button>
              </div>
            </template>
          </div>
        </template>

        <!-- New uploads (blob previews) -->
        <template x-if="photos.length > 0">
          <div class="flex gap-2 flex-wrap">
            <template x-for="(img, i) in photos" :key="i">
              <div class="relative w-[66px] h-[66px]">
                <img :src="img" class="w-full h-full rounded-[12px] object-cover" />
                <button
                  type="button"
                  @click="removeNewPhoto(i)"
                  class="absolute -top-1 -right-1 w-6 h-6 rounded-full bg-black/70 text-white flex items-center justify-center shadow hover:bg-black cursor-pointer"
                >
                  ✕
                </button>
              </div>
            </template>
          </div>
        </template>
      </div>
    </div>

    <!-- Add Content Box -->
    <div class="px-6 pb-3">
      <div class="w-full border border-[#eff0ec] rounded-[14px] px-4 py-4 flex items-center justify-between bg-white">
        <span class="text-sm font-semibold dark">Add content</span>
        <div class="flex space-x-5 text-[22px]">
          <button type="button" @click="view = 'text'" class="tooltip" data-tip="Add text">💬</button>
          <button type="button" @click.stop="$refs.fileInput.click()" class="tooltip" data-tip="Add photo">🖼️</button>
          <button type="button" @click="view = 'location'" class="tooltip" data-tip="Add location">📍</button>
          <button type="button" @click="view = 'date'" class="tooltip" data-tip="Add date">📅</button>
        </div>
      </div>
    </div>

    <!-- Publish / Save Button -->
    <div class="px-6 pb-6">
      <button
        type="button"
        @click="!isPublishDisabled() && !isPublishing && publish()"
        :disabled="isPublishDisabled() || isPublishing"
        :class="(isPublishDisabled() || isPublishing)
          ? 'bg-[#eff0ec] text-gray-500 cursor-not-allowed'
          : 'bg-[#1E2330] text-white cursor-pointer'"
        class="w-full py-4 rounded-xl transition"
      >
        <span x-text="editingId ? 'Save changes' : 'Publish adventure'"></span>
      </button>
    </div>

    <!-- Overlays -->
    <div
      class="absolute inset-0 bg-white z-50 overflow-y-auto"
      x-show="view !== 'main'"
      x-transition
    >
      <div class="flex items-center px-6 py-4 relative border-b border-[#eff0ec]">
        <button
          type="button"
          @click="back()"
          class="text-sm flex items-center justify-center gap-2 absolute left-6 bg-[#eff0ec] hover:bg-gray-300 w-10 h-10 rounded-full"
        >
          ←
        </button>

        <h2 class="text-xl lg:text-2xl gilroy w-full text-center"
          x-text="view === 'text'
            ? 'Add description'
            : view === 'location'
            ? 'Add location'
            : 'Set date'"
        ></h2>
      </div>

      <!-- Text Overlay -->
      <template x-if="view === 'text'">
        <div class="flex flex-col flex-1 p-6">
          <h2 class="text-sm font-semibold mb-2">Say something about your mushroom adventure</h2>
          <textarea
            x-model="tempText"
            class="w-full h-52 p-3 bg-[#eff0ec] rounded-2xl"
            placeholder="Your adventure story..."
          ></textarea>
          <button type="button" @click="addText()" class="bg-[#1E2330] text-white px-4 py-2 rounded-xl mt-4 self-start">
            Add text
          </button>
        </div>
      </template>

      <!-- Location Overlay -->
      <template x-if="view === 'location'">
        <div class="flex flex-col flex-1 p-6">
          <h2 class="text-sm font-semibold mb-2">Add location</h2>

          <input
            type="text"
            x-ref="locationInput"
            x-model="tempLocation"
            class="w-full p-3 bg-[#eff0ec] rounded-2xl mb-3"
            placeholder="Search location..."
          />

          <div
            x-show="locationSelected"
            x-ref="mapContainer"
            class="w-full h-64 rounded bg-[#eff0ec] mb-3"
          ></div>

          <button type="button" @click="saveLocation()" class="bg-[#1E2330] text-white px-4 py-2 rounded-xl self-start">
            Save location
          </button>
        </div>
      </template>

      <!-- Date Overlay -->
      <template x-if="view === 'date'">
        <div class="flex flex-col flex-1 p-6">
          <h2 class="text-sm font-semibold mb-2">Set date</h2>
          <input type="date" x-model="tempDate" class="w-full p-3 bg-gray-100 rounded-2xl" />
          <button type="button" @click="saveDate()" class="bg-[#1E2330] text-white px-4 py-2 rounded-xl mt-4 self-start">
            Save date
          </button>
        </div>
      </template>
    </div>

    <!-- Publishing Overlay -->
    <div
      x-show="isPublishing || published"
      x-transition.opacity
      class="absolute inset-0 z-[60] bg-white/70 flex items-center justify-center"
    >
      <div class="flex flex-col items-center gap-4">
        <template x-if="isPublishing"><div class="spinner"></div></template>
        <template x-if="published"><div class="text-5xl">✅</div></template>

        <p class="text-sm font-semibold text-[#1E2330]">
          <span x-show="isPublishing" x-text="editingId ? 'Saving changes…' : 'Publishing adventure…'"></span>
          <span x-show="published" x-text="editingId ? 'Changes saved' : 'Adventure published'"></span>
        </p>
      </div>
    </div>
  </div>
</div>

<script>
function mkCreateAdventureModal() {
  return {
    view: "main",

    mushrooms: ["Chanterelles", "Funnel chanterelles", "Boletus", "Trumpets"],
    extraMushrooms: [
      "Morel","Shiitake","Porcini","Enoki",
      "Puffball","Hedgehog","Oyster mushroom",
      "King oyster","Maitake",
    ],

    mushroomInputs: [],
    selectedMushroom: null,

    locationSelected: false,
    tempLocation: "",
    location: "",

    // NEW uploads previews (blob urls)
    photos: [],

    // ✅ EDIT MODE STATE
    editingId: null,
    existingPhotos: [],     // [{id,url,sort}]
    removedPhotoIds: [],    // [id,id]

    content: [],
    tempText: "",
    editingBlock: null,

    startDate: new Date().toISOString().split('T')[0],
    tempDate: new Date().toISOString().split('T')[0],

    isPublishing: false,
    published: false,

    map: null,
    marker: null,

    init() {

  // ✅ DEBUG – bekräfta att modalen laddas
  console.log("✅ create/edit modal mounted");

  // ✅ LYSSNA på EDIT från feed card
  window.addEventListener('adventure:edit', (e) => {
    console.log("✅ adventure:edit received", e.detail);
    this.openEditModal(e.detail);
  });

  // ✅ LYSSNA på CREATE (om du har create-knapp någonstans)
  window.addEventListener('adventure:create', () => {
    this.openCreateModal();
  });

  // ==========================
  // din befintliga Google Places watcher
  // ==========================

  this.$watch('view', async (value) => {
    if (value !== 'location') return;

    await this.$nextTick();

    if (!this.$refs.locationInput || !window.google) return;

    const autocomplete = new google.maps.places.Autocomplete(this.$refs.locationInput, {
      types: ['(cities)'],
    });

    autocomplete.addListener('place_changed', () => {
      const place = autocomplete.getPlace();
      if (!place.geometry?.location) return;

      const comps = place.address_components || [];
      const cityComp = comps.find(c =>
        c.types.includes('locality') || c.types.includes('sublocality')
      );
      const countryComp = comps.find(c =>
        c.types.includes('country')
      );

      const city = cityComp ? cityComp.long_name : '';
      const country = countryComp ? countryComp.long_name : '';

      this.tempLocation =
        (city && country) ? `${city}, ${country}` : (city || country);

      this.locationSelected = true;

      if (!this.map) {
        this.map = new google.maps.Map(this.$refs.mapContainer, {
          center: place.geometry.location,
          zoom: 12,
          disableDefaultUI: true,
          clickableIcons: false
        });

        this.marker = new google.maps.Marker({
          position: place.geometry.location,
          map: this.map
        });

      } else {

        this.map.setCenter(place.geometry.location);
        this.map.setZoom(12);
        this.marker.setPosition(place.geometry.location);

      }
    });
  });

},


    // ----- Openers -----
    openCreateModal() {
      this.resetAll();
      this.$store.modal.isOpen = true;
    },

    openEditModal(payload) {
      this.resetAll();

      this.editingId = payload.id;

      this.location = payload.location || "";
      this.tempLocation = this.location || "";

      this.startDate = payload.start_date || new Date().toISOString().split('T')[0];
      this.tempDate = this.startDate;

      // text blocks
      this.content = [];
      if (payload.adventure_text) {
        payload.adventure_text
          .split("\n")
          .map(s => s.trim())
          .filter(Boolean)
          .forEach((t) => {
            this.content.push({ id: Date.now() + Math.random(), text: t });
          });
      }

      // types -> inputs
      this.mushroomInputs = [];
      const types = payload.types || {};
      Object.entries(types).forEach(([type, amount]) => {
        this.mushroomInputs.push({ type, amount: String(amount ?? "") });
      });

      // existing photos
      this.existingPhotos = Array.isArray(payload.photos) ? payload.photos.slice() : [];
      this.existingPhotos.sort((a,b) => (a.sort ?? 0) - (b.sort ?? 0));
      this.removedPhotoIds = [];

      this.$store.modal.isOpen = true;
    },

    // ----- Navigation -----
    back() { this.view = "main"; },

    close() {
      this.view = "main";
      this.$store.modal.isOpen = false;
      // don't reset immediately if you want to keep state when closing accidentally.
      // If you prefer full reset on close, uncomment:
      // this.resetAll();
    },

    resetAll() {
      this.view = "main";

      this.locationSelected = false;
      this.tempLocation = "";
      this.location = "";

      this.photos = [];
      this.existingPhotos = [];
      this.removedPhotoIds = [];
      this.editingId = null;

      this.content = [];
      this.tempText = "";
      this.editingBlock = null;

      this.mushroomInputs = [];
      this.selectedMushroom = null;

      this.startDate = new Date().toISOString().split('T')[0];
      this.tempDate = this.startDate;

      this.isPublishing = false;
      this.published = false;

      if (this.$refs.fileInput) this.$refs.fileInput.value = "";
    },

    // ----- Photos -----
    removeExistingPhoto(index) {
      const p = this.existingPhotos[index];
      if (!p) return;
      this.removedPhotoIds.push(p.id);
      this.existingPhotos.splice(index, 1);
    },

    removeNewPhoto(index) {
      this.photos.splice(index, 1);
      this.removeFileAt(index);
    },

    removeFileAt(index) {
      const input = this.$refs.fileInput;
      if (!input?.files?.length) return;

      const dt = new DataTransfer();
      Array.from(input.files).forEach((file, i) => {
        if (i !== index) dt.items.add(file);
      });
      input.files = dt.files;
    },

    handlePhotos(event) {
      const files = Array.from(event.target.files || []);
      files.forEach(file => this.photos.push(URL.createObjectURL(file)));
      this.view = "main";
    },

    // ----- Validation -----
    isPublishDisabled() {
      const hasLocation = this.location && this.location.trim() !== "";
      const hasMushroom = this.mushroomInputs.some(i => i.amount && i.amount.trim() !== "");
      return !(hasLocation && hasMushroom);
    },

    // ----- Types -----
    selectMushroom(m) {
      if (this.selectedMushroom) {
        this.mushroomInputs = this.mushroomInputs.filter(
          i => !(i.type === this.selectedMushroom && (!i.amount || i.amount.trim() === ""))
        );
      }
      this.selectedMushroom = m;

      if (!this.mushroomInputs.some(i => i.type === m)) {
        this.mushroomInputs.push({ type: m, amount: "" });
      }
    },

    handleDropdownSelect(event) {
      const selected = event.target.value;
      if (!selected) return;
      this.selectMushroom(selected);
      event.target.value = "";
    },

    removeMushroom(input) {
      this.mushroomInputs = this.mushroomInputs.filter(i => i !== input);
    },

    // ----- Text -----
    editText(block = null) {
      if (block) {
        this.tempText = block.text;
        this.editingBlock = block;
      } else {
        this.tempText = "";
        this.editingBlock = null;
      }
      this.view = 'text';
    },

    addText() {
      if (!this.tempText.trim()) return;

      if (this.editingBlock) {
        this.editingBlock.text = this.tempText;
      } else {
        this.content.push({ id: Date.now() + Math.random(), text: this.tempText });
      }

      this.tempText = "";
      this.editingBlock = null;
      this.view = "main";
    },

    // ----- Date/Location -----
    saveDate() {
      this.startDate = this.tempDate || new Date().toISOString().split('T')[0];
      this.view = 'main';
    },

    saveLocation() {
      this.location = this.tempLocation;
      this.tempLocation = "";
      this.view = "main";
    },

    // ----- Create/Update -----
    async publish() {
      if (this.isPublishing) return;

      this.isPublishing = true;
      this.published = false;

      try {
        // Build mushrooms object
        const mushrooms = {};
        this.mushroomInputs.forEach(input => {
          if (input.amount && input.amount.toString().trim() !== "") {
            mushrooms[input.type] = parseFloat(input.amount.toString().replace(',', '.'));
          }
        });

        if (Object.keys(mushrooms).length === 0) {
          throw new Error("Please add at least one mushroom type with amount.");
        }

        const formData = new FormData();
        formData.append("location", this.location || "");
        formData.append("start_date", this.startDate || "");
        formData.append("adventure_text", this.content.map(c => c.text).join("\n"));
        formData.append("types", JSON.stringify(mushrooms));

        const isEdit = !!this.editingId;
        if (isEdit) {
          formData.append("remove_photo_ids", JSON.stringify(this.removedPhotoIds || []));
        }

        // NEW uploads
        if (this.$refs.fileInput?.files?.length) {
          Array.from(this.$refs.fileInput.files).forEach(file => {
            formData.append("photos[]", file);
          });
        }

        const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

        const url = isEdit ? `/adventures/${this.editingId}` : `/adventures`;
        const method = isEdit ? "PUT" : "POST";

        const res = await fetch(url, {
          method,
          headers: {
            "X-CSRF-TOKEN": csrf,
            "X-Requested-With": "XMLHttpRequest",
            "Accept": "application/json",
          },
          body: formData,
        });

       const raw = await res.text();
        let result = null;

        try {
          result = JSON.parse(raw);
        } catch (e) {
          console.error("Non-JSON response:", raw);
          throw new Error(`Server returned non-JSON (status ${res.status}). Check console + storage/logs/laravel.log`);
        }

        if (!res.ok || !result?.success) {
          throw new Error(result?.message || `Save failed (status ${res.status})`);
        }


        this.isPublishing = false;
        this.published = true;

        setTimeout(() => {
          this.close();
          this.resetAll();

          // (valfritt) uppdatera feed
          window.location.reload();
        }, 900);

      } catch (err) {
        this.isPublishing = false;
        this.published = false;
        alert("Error saving adventure: " + err.message);
      }
    }
  }
}
</script>
