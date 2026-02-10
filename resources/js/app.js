import Alpine from 'alpinejs'
import focus from '@alpinejs/focus'
import "./landing"

window.Alpine = Alpine
Alpine.plugin(focus)

// ✅ Stores – definiera innan Alpine.start()
Alpine.store('modal', { isOpen: false })

Alpine.store('adventureModal', {
  isOpen: false,
  adventure: null,
  open(payload) {
    this.adventure = payload
    this.isOpen = true
    document.body.classList.add('overflow-hidden')
  },
  close() {
    this.isOpen = false
    this.adventure = null
    document.body.classList.remove('overflow-hidden')
  },
})

Alpine.store('editProfileModal', {
  isOpen: false,
  open() {
    this.isOpen = true
    document.body.classList.add('overflow-hidden')
  },
  close() {
    this.isOpen = false
    document.body.classList.remove('overflow-hidden')
  },
})
Alpine.data('editProfileModal', (cfg) => ({
  view: 'main',
  tempText: '',
  presentationTemp: cfg.presentation || '',
  openOverlay: false,

    // ✅ Lägg dem här (state)
  isSaving: false,
  saveMessage: '',

  isUploadingAvatar: false,
  avatarPreview: cfg.avatarUrl,

  country: cfg.country || '',
  countries: cfg.countries || [],
  filtered: [],
  open: false,

  saveText() {
    if (!this.tempText.trim()) return
    this.presentationTemp = this.tempText
    this.back()
  },

  back() {
    this.view = 'main'
    this.openOverlay = false
  },

  async uploadAvatar(e) {
    const input = e.target
    if (!input.files || !input.files[0]) return

    this.isUploadingAvatar = true
    const file = input.files[0]

    const reader = new FileReader()
    reader.onload = (ev) => { this.avatarPreview = ev.target.result }
    reader.readAsDataURL(file)

    const fd = new FormData()
    fd.append('avatar', file)

    try {
      const res = await fetch(cfg.avatarUploadUrl, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': cfg.csrf },
        body: fd,
        credentials: 'same-origin',
      })

      const json = await res.json()

      if (!res.ok || !json.success) {
        alert(json?.message || 'Avatar upload failed')
        return
      }

      this.avatarPreview = json.url
      window.dispatchEvent(new CustomEvent('profile:avatar-updated', { detail: { url: json.url } }))

    } catch {
      alert('Upload error')
    } finally {
      this.isUploadingAvatar = false
      input.value = ''
    }
  },

  async saveProfile() {
  if (this.isSaving) return
  this.isSaving = true
  this.saveMessage = ''

  try {
    const form = document.getElementById('editProfileForm')
    const fd = new FormData(form)

    const res = await fetch(form.action, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': cfg.csrf,
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: fd,
      credentials: 'same-origin',
    })

    const json = await res.json()

    if (!res.ok || !json.success) {
      // Laravel validation -> 422 med errors
      if (json?.errors) {
        const firstKey = Object.keys(json.errors)[0]
        this.saveMessage = json.errors[firstKey][0] || 'Validation error'
      } else {
        this.saveMessage = json?.message || 'Save failed'
      }
      return
    }

    // ✅ Uppdatera UI direkt
    this.saveMessage = 'Saved ✅'

    // uppdatera presentation i kortet
    this.presentationTemp = json.user.presentation || ''

    // uppdatera country label uppe i modal
    this.country = json.user.country || ''

    // (valfritt) uppdatera namnet i modal header (om du visar det)
    // du kan även dispatcha event till profile page
    window.dispatchEvent(new CustomEvent('profile:updated', { detail: json.user }))

    // Stäng modalen efter kort delay
    setTimeout(() => {
      this.saveMessage = ''
      this.$store.editProfileModal.close()
    }, 500)

  } catch (e) {
    this.saveMessage = 'Network error'
  } finally {
    this.isSaving = false
  }
},

}))




// ✅ Starta Alpine EN gång
Alpine.start()
