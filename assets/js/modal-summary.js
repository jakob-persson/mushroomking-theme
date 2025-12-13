/* ------------------------------------------------------------------
   adventure-summary.js
   - event delegation for page-level edit/cancel buttons
   - Alpine store 'adventureModal' with open(hunt, edit=false)
--------------------------------------------------------------------- */

/* -------------------------
   Page-level click delegation
   (works even if buttons are inserted later)
-------------------------- */
document.addEventListener('click', (e) => {
  const editBtn = e.target.closest && e.target.closest('#page-edit-btn');
  const cancelBtn = e.target.closest && e.target.closest('#page-cancel-edit-btn');

  if (editBtn) {
    e.preventDefault();
    const rawHunt = editBtn.dataset.hunt;
    const safeId = rawHunt ? rawHunt : new URLSearchParams(window.location.search).get('hunt');
    if (!safeId) return console.warn('No hunt id found for page-edit-btn');

    // Open modal *in edit mode*
    Alpine.store('adventureModal').open({ id: safeId }, true);
    return;
  }

  if (cancelBtn) {
    e.preventDefault();
    const rawHunt = cancelBtn.dataset.hunt;
    const safeId = rawHunt ? rawHunt : new URLSearchParams(window.location.search).get('hunt');
    if (!safeId) return console.warn('No hunt id found for page-cancel-edit-btn');

    // Load non-edit view into modal (or just close edit state)
    Alpine.store('adventureModal').open({ id: safeId }, false);
    return;
  }
});


/* -----------------------------------------------------------
   Alpine store
------------------------------------------------------------ */
document.addEventListener('alpine:init', () => {
  Alpine.store('adventureModal', {
    isOpen: false,
    url: '',
    hunt: null,
    loading: false,
    contentLoaded: false,
    saved: false,
    photoOpen: false,
    isEditing: false,

    /**
     * Open modal and load the adventure content.
     * @param {Object} hunt - { id: ... } or similar
     * @param {Boolean} edit - whether to load edit mode (add &edit=1)
     */
    open(hunt, edit = false) {
      const store = this;
      store.hunt = (typeof hunt === 'object' && hunt !== null) ? { ...hunt } : { id: hunt };
      const safeId = encodeURIComponent(store.hunt.id);

      store.isOpen = true;
      store.loading = true;
      store.contentLoaded = false;

      const adventureContent = document.getElementById('adventure-content');
      if (!adventureContent) {
        console.error('Missing #adventure-content element');
        store.loading = false;
        return;
      }

      // shared loader
      const loadUrl = (url) => {
        store.loading = true;

        fetch(url)
          .then(res => res.text())
          .then(html => {
            adventureContent.innerHTML = html;
            store.loading = false;
            store.contentLoaded = true;

            // Edit-mode detection
            store.isEditing = html.includes('id="adventure-form"');

            // Init TinyMCE if textarea exists
            const textarea = adventureContent.querySelector('#adventure_text');
            if (textarea && typeof tinymce !== 'undefined') {
              if (tinymce.get('adventure_text')) {
                tinymce.get('adventure_text').remove();
              }
              tinymce.init({
                target: textarea,
                menubar: false,
                toolbar: "bold italic underline | bullist numlist | link | undo redo",
                placeholder: "Write your adventure...",
                height: 200,
              });
            }

            // ---- Bind UI handlers that live inside the injected content ----

            // In-page Edit (button inside the loaded template)
            const inPageEdit = adventureContent.querySelector('#edit-adventure');
            if (inPageEdit) {
              inPageEdit.addEventListener('click', (ev) => {
                ev.preventDefault();
                loadUrl(`${window.location.origin}/mk/adventure-summary/?hunt=${safeId}&edit=1`);
                store.isEditing = true;
                window.history.pushState({}, '', `?hunt=${safeId}&edit=1`);
              });
            }

            // In-page Cancel (button inside the loaded template)
            const inPageCancel = adventureContent.querySelector('#cancel-edit');
            if (inPageCancel) {
              inPageCancel.addEventListener('click', (ev) => {
                ev.preventDefault();
                loadUrl(`${window.location.origin}/mk/adventure-summary/?hunt=${safeId}`);
                store.isEditing = false;
                window.history.pushState({}, '', `?hunt=${safeId}`);
              });
            }

            // Modal dropdown edit / cancel (if present in DOM)
            const modalEditBtn = document.getElementById('modal-edit-btn');
            if (modalEditBtn) {
              // remove previously attached listeners by cloning (safest)
              const newBtn = modalEditBtn.cloneNode(true);
              modalEditBtn.parentNode.replaceChild(newBtn, modalEditBtn);
              newBtn.addEventListener('click', (ev) => {
                ev.preventDefault();
                loadUrl(`${window.location.origin}/mk/adventure-summary/?hunt=${safeId}&edit=1`);
                store.isEditing = true;
                window.history.pushState({}, '', `?hunt=${safeId}&edit=1`);
              });
            }

            const modalCancelBtn = document.getElementById('modal-cancel-edit-btn');
            if (modalCancelBtn) {
              const newBtn = modalCancelBtn.cloneNode(true);
              modalCancelBtn.parentNode.replaceChild(newBtn, modalCancelBtn);
              newBtn.addEventListener('click', (ev) => {
                ev.preventDefault();
                loadUrl(`${window.location.origin}/mk/adventure-summary/?hunt=${safeId}`);
                store.isEditing = false;
                window.history.pushState({}, '', `?hunt=${safeId}`);
              });
            }

            // If the page-level dropdown buttons exist inside the loaded content (rare),
            // attach them too (this is defensive — the page-level buttons are usually outside).
            const pageEditBtnInside = adventureContent.querySelector('#page-edit-btn');
            if (pageEditBtnInside) {
              pageEditBtnInside.addEventListener('click', (ev) => {
                ev.preventDefault();
                loadUrl(`${window.location.origin}/mk/adventure-summary/?hunt=${safeId}&edit=1`);
                store.isEditing = true;
                window.history.pushState({}, '', `?hunt=${safeId}&edit=1`);
              });
            }
            const pageCancelBtnInside = adventureContent.querySelector('#page-cancel-edit-btn');
            if (pageCancelBtnInside) {
              pageCancelBtnInside.addEventListener('click', (ev) => {
                ev.preventDefault();
                loadUrl(`${window.location.origin}/mk/adventure-summary/?hunt=${safeId}`);
                store.isEditing = false;
                window.history.pushState({}, '', `?hunt=${safeId}`);
              });
            }

          })
          .catch(err => {
            console.error('Failed to load adventure content', err);
            store.loading = false;
            alert('Kunde inte ladda äventyrsdata.');
          });
      }; // end loadUrl

      // initial request - include edit param if requested
      const url = `${window.location.origin}/mk/adventure-summary/?hunt=${safeId}` + (edit ? '&edit=1' : '');
      loadUrl(url);

      // update visible URL
      const prettyUrl = `${window.location.pathname}?hunt=${safeId}` + (edit ? '&edit=1' : '');
      window.history.pushState({ huntId: safeId, edit: !!edit }, '', prettyUrl);
    }, // end open

    close() {
      this.isOpen = false;
      this.hunt = null;
      this.url = '';
      this.loading = false;
      this.contentLoaded = false;
      this.photoOpen = false;
      this.isEditing = false;

      const adventureContent = document.getElementById('adventure-content');
      const photoView = document.getElementById('photo-view');

      if (adventureContent) adventureContent.innerHTML = '';
      if (photoView) {
        photoView.innerHTML = '';
        photoView.classList.add('hidden');
      }

      if (typeof tinymce !== 'undefined' && tinymce.get('adventure_text')) {
        tinymce.get('adventure_text').remove();
      }

      window.history.pushState({}, '', window.location.pathname);
    },

    saveAdventure() {
      if (!this.contentLoaded) return alert("Vänta tills innehållet är helt laddat");

      const container = document.getElementById('adventure-content');
      const form = container ? container.querySelector('#adventure-form') : null;
      if (!form) return alert("Formulär inte hittat");

      if (window.tinymce) {
        const editor = tinymce.get('adventure_text');
        if (editor) editor.save();
      }

      const formData = new FormData(form);
      formData.append('action', 'update_mushroom');

      fetch(ajaxurl, { method: 'POST', body: formData })
        .then(res => res.json())
        .then(json => {
          if (json.success) {
            this.saved = true;
            setTimeout(() => this.saved = false, 1500);

            const safeId = encodeURIComponent(this.hunt.id);
            this.reloadContent(`${window.location.origin}/mk/adventure-summary/?hunt=${safeId}`);
          } else {
            alert('Fel vid sparning: ' + (json.data || 'Okänt fel'));
          }
        })
        .catch(err => {
          console.error(err);
          alert('Något gick fel vid sparning');
        });
    },

    reloadContent(url) {
      const store = this;
      const container = document.getElementById('adventure-content');
      if (!container) return;

      store.loading = true;

      fetch(url)
        .then(res => res.text())
        .then(html => {
          container.innerHTML = html;
          store.loading = false;
          store.contentLoaded = true;

          // Re-init TinyMCE if necessary
          const textarea = container.querySelector('#adventure_text');
          if (textarea && typeof tinymce !== 'undefined') {
            if (tinymce.get('adventure_text')) tinymce.get('adventure_text').remove();
            tinymce.init({
              target: textarea,
              menubar: false,
              toolbar: "bold italic underline | bullist numlist | link | undo redo",
              placeholder: "Write your adventure...",
              height: 200,
            });
          }
        })
        .catch(err => {
          console.error(err);
          store.loading = false;
          alert('Kunde inte uppdatera innehållet.');
        });
    },

    openPhoto(url) {
      this.photoOpen = true;

      const adventureContent = document.getElementById('adventure-content');
      const photoView = document.getElementById('photo-view');
      const header = document.getElementById('modal-header');

      if (adventureContent) adventureContent.classList.add('hidden');
      if (header) header.classList.add('hidden');

      if (!photoView) return;
      photoView.classList.remove('hidden');
      photoView.classList.add('flex');
      photoView.innerHTML = `
        <img src="${url}"
             class="max-h-[90vh] max-w-[90vw] object-contain rounded-xl shadow-2xl transition-opacity duration-200 opacity-0"
             onload="this.classList.remove('opacity-0')">
        <div class="absolute top-4 right-4 text-black text-3xl cursor-pointer"
             onclick="Alpine.store('adventureModal').closePhoto()">✕</div>
      `;
    },

    closePhoto() {
      this.photoOpen = false;

      const adventureContent = document.getElementById('adventure-content');
      const photoView = document.getElementById('photo-view');
      const header = document.getElementById('modal-header');

      if (photoView) {
        photoView.classList.add('hidden');
        photoView.classList.remove('flex');
        photoView.innerHTML = '';
      }

      if (adventureContent) adventureContent.classList.remove('hidden');
      if (header) header.classList.remove('hidden');
    },

  }); // end Alpine.store

  /* ------------------------
     popstate (back button)
  ------------------------- */
  window.addEventListener('popstate', () => {
    const huntParam = new URLSearchParams(window.location.search).get('hunt');
    const editParam = new URLSearchParams(window.location.search).get('edit');
    const store = Alpine.store('adventureModal');

    if (huntParam) {
      store.open({ id: huntParam }, !!editParam);
    } else {
      store.close();
    }
  });

  /* ------------------------
     Auto-open on initial load if ?hunt=
  ------------------------- */
  const initialHunt = new URLSearchParams(window.location.search).get('hunt');
  const initialEdit = new URLSearchParams(window.location.search).get('edit');
  if (initialHunt) {
    Alpine.store('adventureModal').open({ id: initialHunt }, !!initialEdit);
  }

}); // end alpine:init
