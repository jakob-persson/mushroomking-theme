
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCbqJQfWrO-8ajEjM9c68d_Vc5QXQnjWb4&libraries=places"></script>
<style>
    .gilroy {
        margin-top: 2px;
    }

    .tooltip {
    position: relative;
    }
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

    .tour-highlight {
    border: 2px solid #34D399; /* green */
    box-shadow: 0 0 10px rgba(52,211,153,0.5);
    border-radius: 8px;
    transition: all 0.3s;
}
</style>
<div 
    x-data="adventureModal()"
    x-show="$store.modal.isOpen"
    x-trap.noscroll="$store.modal.isOpen"
    x-cloak
    class="fixed inset-0 bg-black/40 flex items-center justify-center z-50"
>
    <!-- Hidden file input -->
    <input 
        type="file" 
        multiple 
        class="hidden" 
        x-ref="fileInput" 
        @change="handlePhotos($event)"
    />

    <div class="bg-white w-full max-w-xl lg:rounded-2xl shadow-xl flex flex-col h-full lg:min-h-[80vh] lg:max-h-[90vh] overflow-hidden relative"
         @click.away.self="close()"
    >
        <!-- Header -->
        <div class="flex items-center px-6 py-4 relative border-b border-[#eff0ec]">
            <h1 class="gilroy absolute left-1/2 -translate-x-1/2 font-semibold text-xl lg:text-2xl">Create adventure</h1>
            <button 
               @click="$store.modal.isOpen = false"
                class="ml-auto w-10 h-10 flex items-center justify-center rounded-full bg-[#eff0ec] hover:bg-gray-300 transition dark text-xl"
            >   
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- User info -->
        <?php 
        $current_user = wp_get_current_user();
        $avatar = mk_get_user_avatar($current_user->ID);
        $username = $current_user->display_name;
        ?>
        <!-- User info with date -->
        <div class="px-6 flex items-center space-x-3 my-4">
            <img src="<?= esc_url($avatar); ?>" class="w-12 h-12 rounded-full object-cover aspect-square" />
            <div class="flex flex-col leading-tight">
                <span class="font-semibold text-sm mb-1"><?= esc_html($username); ?></span>
               <span class="text-sm dark flex items-center gap-1">
                <!-- Clickable Location -->
             <button @click="view = 'location'" class="flex items-center gap-1 hover:opacity-70">
                <i class="fas fa-map-marker-alt dark"></i>
                <span>
                    <!-- Only show "Foraging in" if location exists -->
                    <span class="hidden md:inline" x-show="location">
                        Foraging in
                    </span>

                    <span x-text="location || 'No location added'"></span>
                </span>
            </button>
                <!-- Clickable Date -->
                <button @click="view = 'date'" class="flex items-center gap-1 hover:opacity-70">
                    <i class="far fa-calendar dark"></i>
                    <span x-text="new Date(startDate).toLocaleDateString('en-US', { 
                        month: 'short', 
                        day: 'numeric', 
                        year: 'numeric' 
                    })"></span>
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

            <!-- Always-visible placeholder only if no text exists -->
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
                    <!-- Existing Mushroom Buttons -->
                    <template x-for="(m, index) in mushrooms" :key="index">
                        <button
                            @click="selectMushroom(m)"
                            :class="selectedMushroom === m 
                                ? 'bg-[#E9C0E9] dark border-2 border-[#1E2330]' 
                                : 'bg-white border-2 border-[#1E2330] dark'"
                            class="px-2.5 py-2.5 rounded-lg text-xs font-semibold whitespace-nowrap transition"
                            x-text="m"
                        ></button>
                    </template>

                    <!-- NEW Dropdown -->
                   <select
                        class="w-[110px] px-2.5 py-2.5 border-2 border-[#1E2330] rounded-lg text-xs font-semibold bg-white"
                        @change="handleDropdownSelect($event)"
                    >
                        <option value="">More types</option>
                        <template x-for="extra in extraMushrooms" :key="extra">
                            <option :value="extra" x-text="extra"></option>
                        </template>
                    </select>
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

                            <!-- kg INSIDE input -->
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm pointer-events-none">
                                kg
                            </span>
                        </div>
                        <button @click="removeMushroom(input)" class="dark text-lg">✕</button>

                        </div>
                    </template>
                </div>
            </div>

            <!-- Photos Preview -->
            <template x-if="photos.length > 0">
                <div>
                    <h3 class="font-semibold mb-2">Media</h3>

                    <div class="flex gap-2">
                        <template x-for="(img, i) in photos" :key="i">
                            <div class="relative w-[66px] h-[66px]">
                                
                                <!-- Image -->
                                <img 
                                    :src="img" 
                                    class="w-full h-full rounded-[12px] object-cover"
                                />

                                <!-- Remove Button -->
                                <button 
                                    @click="removePhoto(i)"
                                    class="absolute -top-1 -right-1 w-6 h-6 rounded-full 
                                        bg-black/70 text-white flex items-center justify-center 
                                        shadow hover:bg-black cursor-pointer"
                                >
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

                    <button @click="view = 'text'" class="tooltip" data-tip="Add text">
                        <i class="far fa-comment"></i>
                    </button>

                    <button @click.stop="$refs.fileInput.click()" class="tooltip" data-tip="Add photo">
                        <i class="far fa-image"></i>
                    </button>

                    <button @click="view = 'location'" class="tooltip" data-tip="Add location">
                        <i class="fas fa-map-marker-alt"></i>
                    </button>

                    <button @click="view = 'date'" class="tooltip" data-tip="Add date">
                        <i class="far fa-calendar"></i>
                    </button>

                </div>
            </div>
        

        </div>

        <!-- Publish Button -->
        <div class="px-6 pb-6">
            <button 
                @click="!isPublishDisabled() && publish()"
                :disabled="isPublishDisabled()"
                :class="isPublishDisabled()
                    ? 'bg-[#eff0ec] text-gray-500 cursor-not-allowed'
                    : 'bg-[#1E2330] text-white cursor-pointer'"
                class="w-full py-4 rounded-xl transition"
            >
                Publish adventure
            </button>

        </div>

        <!-- Overlays for Text & Location -->
        <div 
            class="absolute inset-0 bg-white z-50  overflow-y-auto"
            x-show="view !== 'main'"
            x-transition
        >
            <div class="flex items-center px-6 py-4 relative border-b border-[#eff0ec]">
            <!-- Back button -->
            <button 
                @click="back()" 
                class="text-sm flex items-center justify-center gap-2 absolute left-6 bg-[#eff0ec] hover:bg-gray-300 w-10 h-10 rounded-full"
            >
                <i class="fas fa-arrow-left"></i>
            </button>

            <!-- Centered title -->
            <h2 class="text-xl lg:text-2xl gilroy w-full text-center" 
                x-text="view === 'text' 
                    ? 'Add description'
                    : view === 'location'
                    ? 'Add location'
                    : 'Set date'">
            </h2>
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
                    <button @click="addText()" class="bg-[#1E2330] text-white px-4 py-2 rounded-xl mt-4 self-start">
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

                <!-- Map container -->
               <div 
                    x-show="locationSelected" 
                    x-ref="mapContainer" 
                    class="w-full h-64 rounded bg-[#eff0ec] mb-3"
                ></div>


                <button @click="saveLocation()" class="bg-[#1E2330] text-white px-4 py-2 rounded-xl self-start">
                    Save location
                </button>
            </div>

            </template>

            <!-- Date Overlay -->
            <template x-if="view === 'date'">
                <div class="flex flex-col flex-1 p-6">
                    <h2 class="text-sm font-semibold mb-2">Set date</h2>
                    <input 
                        type="date" 
                        x-model="tempDate" 
                        class="w-full p-3 bg-gray-100 rounded-2xl"
                    />
                    <button @click="saveDate()" class="bg-[#1E2330 text-white px-4 py-2 rounded-xl mt-4 self-start">
                        Save date
                    </button>
                </div>
            </template>
        </div>
    </div>
</div>
<script>
function adventureModal() {
    return {
        open: true,
        view: "main",
        mushrooms: ["Chanterelles", "Funnel chanterelles", "Boletus", "Trumpets"],
        extraMushrooms: [
            "Morel", "Shiitake", "Porcini", "Enoki",
            "Puffball", "Hedgehog", "Oyster mushroom",
            "King oyster", "Maitake",
        ],
        mushroomInputs: [],
        locationSelected: false,

        selectedMushroom: null,
        tempText: "",
        tempLocation: "",
        photos: [],
        content: [],
        location: "",
        startDate: new Date().toISOString().split('T')[0],
        tempDate: new Date().toISOString().split('T')[0],

        editingBlock: null,
        map: null,
        marker: null,

        removePhoto(index) {
            this.photos.splice(index, 1);
        },

        needsLocation() {
                return !this.location || this.location.trim() === "";
            },

            needsMushroom() {
                return !this.mushroomInputs.some(i => i.amount && i.amount.trim() !== "");
            },


        isPublishDisabled() {
            const hasLocation = this.location && this.location.trim() !== "";
            const hasMushroom = this.mushroomInputs.some(i => i.amount && i.amount.trim() !== "");
            return !(hasLocation && hasMushroom);
        },

        init() {
            this.$watch('view', async value => {
                if (value === 'location') {
                    await this.$nextTick();

                    if (this.$refs.locationInput && window.google) {
                        const autocomplete = new google.maps.places.Autocomplete(this.$refs.locationInput, {
                            types: ['(cities)'],
                        });

                    


                        // Kör när användaren väljer ett förslag
                       autocomplete.addListener('place_changed', () => {
                        const place = autocomplete.getPlace();
                        if (!place.geometry?.location) return;

                        // Get city and country
                        const cityComp = place.address_components.find(c =>
                            c.types.includes('locality') || c.types.includes('sublocality')
                        );
                        const countryComp = place.address_components.find(c =>
                            c.types.includes('country')
                        );
                        const city = cityComp ? cityComp.long_name : '';
                        const country = countryComp ? countryComp.long_name : '';
                        this.tempLocation = city && country ? `${city}, ${country}` : city || country;

                        // Mark map as visible
                        this.locationSelected = true;

                        // Create or update the map
                        if (!this.map) {
                            this.map = new google.maps.Map(this.$refs.mapContainer, {
                                center: place.geometry.location,
                                zoom: 12,
                                disableDefaultUI: true,  // remove all controls
                                clickableIcons: false     // optional: disable POI clicks
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

                    }
                }
            });
        },

        back() { this.view = "main"; },
        close() { this.open = false; },

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

        removeMushroom(input) { this.mushroomInputs = this.mushroomInputs.filter(i => i !== input); },

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
                this.content.push({ id: Date.now(), text: this.tempText });
            }
            this.tempText = "";
            this.editingBlock = null;
            this.view = "main";
        },

        saveDate() {
            this.startDate = this.tempDate || new Date().toISOString().split('T')[0];
            this.view = 'main';
        },

        saveLocation() {
            this.location = this.tempLocation;
            this.tempLocation = "";
            this.view = "main";
        },

        handlePhotos(event) {
            [...event.target.files].forEach(file => {
                this.photos.push(URL.createObjectURL(file));
            });
            this.view = "main";
        },




        handleDropdownSelect(event) {
            const selected = event.target.value;
            if (!selected) return;
            this.selectMushroom(selected);
            event.target.value = "";
        },

        

        async publish() {
            if (this.mushroomInputs.length === 0 || !this.mushroomInputs.some(i => i.amount)) {
                alert("Please add at least one mushroom type with amount.");
                return;
            }

            const mushrooms = {};
            this.mushroomInputs.forEach(input => {
                if (input.amount) {
                    mushrooms[input.type] = parseFloat(input.amount.toString().replace(',', '.'));
                }
            });

            const formData = new FormData();
            formData.append("action", window.isEditing ? "update_mushroom" : "add_mushroom");
            if (window.isEditing) formData.append("adventure_id", window.editAdventureId);

            formData.append("types", JSON.stringify(mushrooms));
            formData.append("location", this.location || "");
            formData.append("adventure_text", this.content.map(c => c.text).join("\n"));
            formData.append("start_date", this.startDate || "");

            if (this.$refs.fileInput.files) {
                Array.from(this.$refs.fileInput.files).forEach(file => {
                    formData.append("mushroom_photos[]", file);
                });
            }

            try {
                const res = await fetch(ajaxurl, { method: "POST", body: formData });
                const result = await res.json();
                if (!result.success) throw new Error(result.data || "Save failed");

                alert("Adventure saved!");
                this.close();
                this.photos = [];
                this.mushroomInputs = [];
                this.content = [];
                this.location = "";
                this.$refs.fileInput.value = "";
                window.isEditing = false;
                window.editAdventureId = null;
                location.reload();
            } catch (err) {
                alert("Error saving adventure: " + err.message);
            }
        }
    }
}
</script>

