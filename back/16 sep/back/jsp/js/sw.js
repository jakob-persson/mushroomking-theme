
const CACHE_NAME = 'portfolio-v1';

// Install step: cache your images and basic assets
self.addEventListener('install', event => {
  const urlsToCache = [
    '/',
    '/index.html',
    '/main.css',
    '/images/jsp.png',
    '/images/jake.webp',
    // we'll inject more image URLs programmatically later
  ];
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => cache.addAll(urlsToCache))
  );
});

// Fetch step: respond with cached asset or fetch from network
self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request).then(cached => {
      return cached || fetch(event.request);
    })
  );
});
