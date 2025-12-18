<script>
    window.currentUserId = <?= get_current_user_id(); ?>;
    window.currentUserAvatar = "<?= esc_url( get_avatar_url( get_current_user_id() ) ); ?>";
</script>
<div 
   x-data="editAdventureModal()"
    x-show="$store.editAdventureModal.open"
    x-init="
        $watch('$store.editAdventureModal.open', value => {
            if (value) loadFromStore()
        })
    "
    x-trap.noscroll="$store.editAdventureModal.open"
    x-cloak
    class="fixed inset-0 bg-black/40 flex items-center justify-center z-50"
>
    <input type="file" multiple class="hidden" x-ref="fileInput" @change="handlePhotos($event)" />

    <div class="bg-white w-full max-w-xl lg:rounded-2xl shadow-xl flex flex-col h-full lg:min-h-[80vh] lg:max-h-[90vh] overflow-hidden relative"
         @click.away.self="$store.editAdventureModal.open = false"
    >
        <!-- Header -->
        <div class="flex items-center px-6 py-4 relative border-b border-[#eff0ec]">
            <h1 class="gilroy absolute left-1/2 -translate-x-1/2 font-semibold text-xl lg:text-2xl">Edit Adventure</h1>
            <button 
                @click="$store.editAdventureModal.open = false" 
                class="ml-auto w-10 h-10 flex items-center justify-center rounded-full bg-[#eff0ec] hover:bg-gray-300 transition text-xl tooltip"
                data-tip="Close"
            >
                &times;
            </button>
        </div>

        <!-- User info -->
        <div class="px-6 flex items-center space-x-3 my-4">
            <?php $current_user_avatar = mk_get_user_avatar(get_current_user_id()); ?>
<img src="<?= esc_url($current_user_avatar); ?>" class="w-12 h-12 rounded-full object-cover">


            <div class="flex flex-col leading-tight">
                <span class="font-semibold text-sm mb-1" x-text="$store.editAdventureModal.adventure.username"></span>
                <span class="text-sm flex items-center gap-3">
                    <button @click="view = 'location'" class="flex items-center gap-1 hover:opacity-70">
                        <i class="fas fa-map-marker-alt"></i>
                        <span x-text="$store.editAdventureModal.adventure.location || 'No location added'"></span>
                    </button>
                    <button @click="view = 'date'" class="flex items-center gap-1 hover:opacity-70">
                        <i class="far fa-calendar"></i>
                        <span x-text="new Date($store.editAdventureModal.adventure.date).toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'})"></span>
                    </button>
                </span>
            </div>
        </div>

        <!-- Main Content -->
        <div class="px-6 overflow-y-auto flex-grow space-y-7 pb-10">
            <!-- Text Blocks -->
            <template x-for="block in content" :key="block.id">
                <div class="rounded-[14px] text-sm cursor-pointer" x-text="block.text" style="margin-top:10px" @click="editText(block)"></div>
            </template>
            <template x-if="content.length === 0">
                <div class="rounded-[14px] text-sm text-gray-400 cursor-pointer" style="margin-top:10px" @click="editText()">
                    Say something about your adventure...
                </div>
            </template>

            <!-- Mushroom Selection -->
            <div>
                <h2 class="font-semibold text-base mb-3">Choose mushroom type</h2>
                <div class="flex gap-2 overflow-x-auto no-scrollbar items-center">
                    <template x-for="(m,index) in mushrooms" :key="index">
                        <button @click="selectMushroom(m)" :class="selectedMushroom===m ? 'bg-[#E9C0E9] dark border-2 border-[#1E2330]' : 'bg-white border-2 border-[#1E2330] dark'" class="px-2.5 py-2.5 rounded-lg text-xs font-semibold whitespace-nowrap transition" x-text="m"></button>
                    </template>

                    <div class="relative inline-block">
                        <select class="w-[89px] px-2.5 py-2.5 pr-8 border-2 border-[#1E2330] rounded-lg text-xs font-semibold bg-white appearance-none" @change="handleDropdownSelect($event)">
                            <option value="">More</option>
                            <template x-for="extra in extraMushrooms" :key="extra">
                                <option :value="extra" x-text="extra"></option>
                            </template>
                        </select>
                        <i class="fa-solid fa-chevron-down absolute right-2 top-1/2 -translate-y-1/2 pointer-events-none text-[#1E2330] text-xs"></i>
                    </div>
                </div>

                <!-- Mushroom Amounts -->
                <div class="space-y-3 mt-4">
                    <template x-for="input in mushroomInputs" :key="input.type">
                        <div class="flex items-center space-x-2">
                            <span class="w-32 text-sm font-medium" x-text="input.type"></span>
                            <div class="relative flex-1">
                                <input type="text" x-model="input.amount" class="w-full p-3 pr-10 bg-[#eff0ec] rounded-[14px] no-spinner" placeholder="Amount" />
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm pointer-events-none">kg</span>
                            </div>
                            <button @click="removeMushroom(input)" class="dark text-lg">✕</button>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Photos Preview -->
            <template x-if="photos.length>0">
                <div>
                    <h3 class="font-semibold mb-2">Media</h3>
                    <div class="flex gap-2">
                        <template x-for="(img,i) in photos" :key="i">
                            <div class="relative w-[66px] h-[66px]">
                                <img :src="img" class="w-full h-full rounded-[12px] object-cover" />
                                <button @click="removePhoto(i)" class="absolute -top-1 -right-1 w-6 h-6 rounded-full bg-black/70 text-white flex items-center justify-center shadow hover:bg-black cursor-pointer">
                                    <i class="fas fa-times text-xs"></i>
                                </button>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>

        <!-- Add Content Box -->
        <div class="px-6 pb-3">
            <div class="w-full border border-[#eff0ec] rounded-[14px] px-4 py-4 flex items-center justify-between bg-white">
                <span class="text-sm font-semibold dark">Add content</span>
                <div class="flex space-x-5 text-[22px]">
                    <button @click="view='text'" class="tooltip" data-tip="Add text"><i class="far fa-comment"></i></button>
                    <button @click.stop="$refs.fileInput.click()" class="tooltip" data-tip="Add photo"><i class="far fa-image"></i></button>
                    <button @click="view='location'" class="tooltip" data-tip="Add location"><i class="fas fa-map-marker-alt"></i></button>
                    <button @click="view='date'" class="tooltip" data-tip="Add date"><i class="far fa-calendar"></i></button>
                </div>
            </div>
        </div>

        <!-- Save Button -->
        <div class="px-6 pb-6">
            <button @click="!isPublishDisabled() && !isPublishing && publish()" :disabled="isPublishDisabled() || isPublishing" :class="(isPublishDisabled() || isPublishing)?'bg-[#eff0ec] text-gray-500 cursor-not-allowed':'bg-[#1E2330] text-white cursor-pointer'" class="w-full py-4 rounded-xl transition">
                Save Changes
            </button>
        </div>

        <!-- Overlays for Text / Location / Date -->
        <div class="absolute inset-0 bg-white z-50 overflow-y-auto" x-show="view!=='main'" x-transition>
            <div class="flex items-center px-6 py-4 relative border-b border-[#eff0ec]">
                <button @click="back()" class="text-sm flex items-center justify-center gap-2 absolute left-6 bg-[#eff0ec] hover:bg-gray-300 w-10 h-10 rounded-full">
                    <i class="fas fa-arrow-left"></i>
                </button>
                <h2 class="text-xl lg:text-2xl gilroy w-full text-center" x-text="view==='text'?'Add description':view==='location'?'Add location':'Set date'"></h2>
            </div>

            <!-- Text Overlay -->
            <template x-if="view==='text'">
                <div class="flex flex-col flex-1 p-6">
                    <h2 class="text-sm font-semibold mb-2">Say something about your adventure</h2>
                    <textarea x-model="tempText" class="w-full h-52 p-3 bg-[#eff0ec] rounded-2xl" placeholder="Your adventure story..."></textarea>
                    <button @click="addText()" class="bg-[#1E2330] text-white px-4 py-2 rounded-xl mt-4 self-start">Add text</button>
                </div>
            </template>

            <!-- Location Overlay -->
            <template x-if="view==='location'">
                <div class="flex flex-col flex-1 p-6">
                    <h2 class="text-sm font-semibold mb-2">Add location</h2>
                    <input type="text" x-ref="locationInput" x-model="tempLocation" class="w-full p-3 bg-[#eff0ec] rounded-2xl mb-3" placeholder="Search location..." />
                    <div x-show="locationSelected" x-ref="mapContainer" class="w-full h-64 rounded bg-[#eff0ec] mb-3"></div>
                    <button @click="saveLocation()" class="bg-[#1E2330] text-white px-4 py-2 rounded-xl self-start">Save location</button>
                </div>
            </template>

            <!-- Date Overlay -->
            <template x-if="view==='date'">
                <div class="flex flex-col flex-1 p-6">
                    <h2 class="text-sm font-semibold mb-2">Set date</h2>
                    <input type="date" x-model="tempDate" class="w-full p-3 bg-[#eff0ec] rounded-2xl" />
                    <button @click="saveDate()" class="bg-[#1E2330] text-white px-4 py-2 rounded-xl mt-4 self-start">Save date</button>
                </div>
            </template>
        </div>

        <!-- Publishing Overlay -->
        <div x-show="isPublishing || published" x-transition.opacity class="absolute inset-0 z-[60] bg-white/70 flex items-center justify-center">
            <div class="flex flex-col items-center gap-4">
                <template x-if="isPublishing"><div class="spinner"></div></template>
                <template x-if="published"><i class="fas fa-check-circle text-[#124C12] text-5xl"></i></template>
                <p class="text-sm font-semibold text-[#1E2330]">
                    <span x-show="isPublishing">Saving adventure…</span>
                    <span x-show="published">Adventure saved</span>
                </p>
            </div>
        </div>
    </div>
</div>

<script>
function editAdventureModal() {
    return {
        open: false,
        view: 'main',
        mushrooms: ["Chanterelles", "Funnel chanterelles", "Boletus", "Trumpets"],
        extraMushrooms: ["Morel","Shiitake","Porcini","Enoki","Puffball","Hedgehog","Oyster mushroom","King oyster","Maitake"],
        mushroomInputs: [],
        selectedMushroom: null,
        photos: [],
        content: [],
        tempText: '',
        tempLocation: '',
        location: '',
        locationSelected: false,
        startDate: new Date().toISOString().split('T')[0],
        tempDate: new Date().toISOString().split('T')[0],
        isPublishing: false,
        published: false,
        editingBlock: null,
        adventure: { id: null, avatar: '', username: '', location: '', date: '' },

        // --- Strip HTML tags ---
        stripHTML(text) {
            const tmp = document.createElement("DIV");
            tmp.innerHTML = text;
            return tmp.textContent || tmp.innerText || "";
        },

        // --- Load adventure from server ---
        async loadAdventure(adventureId) {
            try {
                const res = await fetch(ajaxurl, { 
                    method: "POST", 
                    body: new URLSearchParams({ action: "get_adventure", adventure_id: adventureId })
                });
                const data = await res.json();
                if (!data.success) throw new Error(data.data || "Failed to load adventure");

                const adv = data.data;
                this.loadFromData(adv);
                this.open = true;

            } catch (err) {
                console.error(err);
                alert("Failed to load adventure: " + err.message);
            }
        },

        // --- Load adventure from store ---
        loadFromStore() {
            const adv = this.$store.editAdventureModal.adventure;
            this.loadFromData(adv);
            this.adventure.avatar = window.currentUserAvatar || '/wp-content/themes/ditt-tema/images/default-avatar.png';
        },

        // --- Common loader ---
        loadFromData(adv) {
            // --- Text ---
            this.content = adv.adventure_text
                ? adv.adventure_text.split('\n').filter(t => t.trim() !== '').map(t => ({ id: Date.now() + Math.random(), text: this.stripHTML(t) }))
                : [];

            // --- Mushrooms ---
            let mushrooms = adv.mushrooms || adv.types || adv.type || {};
            if (typeof mushrooms === 'string') {
                try { mushrooms = JSON.parse(mushrooms); } catch { mushrooms = {}; }
            }
            this.mushroomInputs = [];
            Object.entries(mushrooms).forEach(([type, amount]) => {
                this.mushroomInputs.push({ type, amount: amount?.toString() ?? '' });
            });
            this.selectedMushroom = this.mushroomInputs.length ? this.mushroomInputs[0].type : null;

            // --- Photos ---
            this.photos = Array.isArray(adv.photos) ? adv.photos : [];

            // --- Location & Date ---
            this.location = adv.location || '';
            this.tempLocation = this.location;
            this.startDate = adv.date || adv.start_date || new Date().toISOString().split('T')[0];
            this.tempDate = this.startDate;

            // --- Adventure object ---
            this.adventure = {
            id: adv.id || adv.adventure_id || null,
            avatar: adv.avatar || window.currentUserAvatar || '/wp-content/themes/ditt-tema/images/default-avatar.png',
            username: adv.username || '',
            location: this.location,
            date: this.startDate
        };


            // --- Fetch avatar if needed ---
            if (adv.user_id) {
                fetch(ajaxurl, {
                    method: "POST",
                    body: new URLSearchParams({ action: "get_user_avatar", user_id: adv.user_id })
                }).then(r => r.json()).then(res => {
                    this.adventure.avatar = res.success ? res.data.avatar : '/wp-content/themes/ditt-tema/images/default-avatar.png';
                }).catch(() => {
                    this.adventure.avatar = '/wp-content/themes/ditt-tema/images/default-avatar.png';
                });
            }
        },

        back() { this.view = 'main'; },
        close() { this.open = false; },

        // --- Text editing ---
        editText(block=null) {
            this.tempText = block ? block.text : '';
            this.editingBlock = block;
            this.view = 'text';
        },
        addText() {
            if (!this.tempText.trim()) return;
            if (this.editingBlock) {
                this.editingBlock.text = this.tempText;
            } else {
                this.content.push({ id: Date.now() + Math.random(), text: this.tempText });
            }
            this.tempText = '';
            this.editingBlock = null;
            this.view = 'main';
        },

        // --- Location / Date ---
        saveLocation() { this.location = this.tempLocation; this.view = 'main'; },
        saveDate() { this.startDate = this.tempDate || new Date().toISOString().split('T')[0]; this.view = 'main'; },

        // --- Mushrooms ---
        selectMushroom(type) {
            this.selectedMushroom = type;
            if (!this.mushroomInputs.some(i => i.type === type)) this.mushroomInputs.push({ type, amount: '' });
        },
        removeMushroom(input) {
            this.mushroomInputs = this.mushroomInputs.filter(i => i !== input);
            if (this.selectedMushroom === input.type) this.selectedMushroom = this.mushroomInputs.length ? this.mushroomInputs[0].type : null;
        },
        handleDropdownSelect(e) {
            const s = e.target.value;
            if (!s) return;
            this.selectMushroom(s);
            e.target.value = '';
        },

        // --- Photos ---
        handlePhotos(e) { [...e.target.files].forEach(f => this.photos.push(URL.createObjectURL(f))); },
        removePhoto(i) { this.photos.splice(i, 1); },

        // --- Validation ---
        needsMushroom() { return !this.mushroomInputs.some(i => i.amount && i.amount.trim() !== ''); },
        isPublishDisabled() { return !(this.location && this.location.trim() && !this.needsMushroom()); },

        // --- Publish / Save ---
        async publish() {
            if (this.isPublishing) return;
            this.isPublishing = true;
            this.published = false;

            try {
                if (!this.adventure.id) throw new Error("Adventure ID is missing");

                if (this.editingBlock) {
                    this.editingBlock.text = this.tempText;
                    this.editingBlock = null;
                }

                if (this.needsMushroom()) throw new Error("Add at least one mushroom with amount.");

                const mushrooms = {};
                this.mushroomInputs.forEach(i => {
                    if (i.amount) {
                        const val = parseFloat(i.amount.replace(',', '.'));
                        if (!isNaN(val)) mushrooms[i.type] = val;
                    }
                });

                const formData = new FormData();
                formData.append('action', 'update_mushroom');
                formData.append('adventure_id', this.adventure.id);
                formData.append('location', this.location || '');
                formData.append('start_date', this.startDate || new Date().toISOString().split('T')[0]);
                formData.append('adventure_text', this.content.map(c => c.text).join("\n"));

                Object.entries(mushrooms).forEach(([type, amount]) => {
                    formData.append(`types[${type}]`, amount);
                });

                this.photos.forEach(url => formData.append('existing_photos[]', url));

                if (this.$refs.fileInput?.files?.length) {
                    Array.from(this.$refs.fileInput.files).forEach(f => formData.append('mushroom_photos[]', f));
                }

                const res = await fetch(ajaxurl, { method: "POST", body: formData });
                const result = await res.json();
                if (!result.success) throw new Error(result.data || "Save failed");

                // --- Update store ---
                this.$store.editAdventureModal.adventure = {
                    ...this.$store.editAdventureModal.adventure,
                    id: this.adventure.id,
                    adventure_text: this.content.map(c => c.text).join("\n"),
                    mushrooms,
                    photos: this.photos,
                    location: this.location,
                    date: this.startDate
                };

                this.isPublishing = false;
                this.published = true;

                setTimeout(() => {
                this.$refs.fileInput.value = '';
                this.published = false;
                this.view = 'main';

                this.$store.editAdventureModal.open = false;

                window.location.reload();

            }, 1200);


            } catch (err) {
                this.isPublishing = false;
                this.published = false;
                alert("Error saving adventure: " + err.message);
            }
        }
    }
}
</script>


