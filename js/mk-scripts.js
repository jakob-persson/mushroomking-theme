
document.addEventListener('alpine:init', () => {
  Alpine.store('adventureModal', {
    isOpen: false,
    url: '',
    hunt: null,
    loading: false,
    copied: false,

    open(hunt) {
      this.hunt = hunt;
      const safeId = encodeURIComponent(hunt.id);
      this.url = `${window.location.origin}/mk/adventure-summary/?hunt=${safeId}`;
      this.loading = true;
      this.isOpen = true;
      this.copied = false;

      const newUrl = `${window.location.pathname}?hunt=${safeId}`;
      window.history.pushState({ huntId: safeId }, '', newUrl);
    },

    onLoad() {
      this.loading = false;
    },

    close() {
      this.isOpen = false;
      this.hunt = null;
      this.url = '';
      this.loading = false;
      this.copied = false;
      window.history.pushState({}, '', window.location.pathname);
    }
  });

  // 🧭 Lyssna på tillbaka-knappen
  window.addEventListener('popstate', (event) => {
    const huntParam = new URLSearchParams(window.location.search).get('hunt');
    const store = Alpine.store('adventureModal');

    if (huntParam) {
      store.url = `${window.location.origin}/mk/adventure-summary/?hunt=${huntParam}`;
      store.isOpen = true;
      store.loading = true;
    } else {
      store.close();
    }
  });

  // 🚀 NY DEL: öppna modal automatiskt om sidan laddas med ?hunt=...
  const initialHunt = new URLSearchParams(window.location.search).get('hunt');
  if (initialHunt) {
    const store = Alpine.store('adventureModal');
    store.url = `${window.location.origin}/mk/adventure-summary/?hunt=${initialHunt}`;
    store.isOpen = true;
    store.loading = true;
  }
});
