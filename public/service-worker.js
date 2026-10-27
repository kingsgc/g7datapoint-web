// Local development build: service worker intentionally disabled.
// Any previously registered service worker that loads this file will
// unregister itself and stop controlling pages.
self.addEventListener('install', function () {
  self.skipWaiting();
});
self.addEventListener('activate', function (event) {
  event.waitUntil(
    self.registration
      .unregister()
      .then(function () {
        return self.clients.matchAll({ type: 'window' });
      })
      .then(function (clients) {
        clients.forEach(function (client) {
          if (client && typeof client.navigate === 'function') {
            try { client.navigate(client.url); } catch (e) { /* ignore */ }
          }
        });
      })
  );
});
