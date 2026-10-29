// Pages are never cached (they can hold private data). Only static files are, plus an offline page.
const V = 'bv2', ASSETS = ['/assets/style.css', '/assets/app.js', '/assets/icon-192.png', '/offline'];
self.addEventListener('install', e => e.waitUntil(caches.open(V).then(c => c.addAll(ASSETS)).then(() => self.skipWaiting())));
self.addEventListener('activate', e => e.waitUntil(caches.keys().then(k => Promise.all(k.filter(x => x !== V).map(x => caches.delete(x)))).then(() => self.clients.claim())));
self.addEventListener('fetch', e => {
  const r = e.request, u = new URL(r.url);
  if (r.method !== 'GET' || u.origin !== location.origin) return;
  if (r.mode === 'navigate') { e.respondWith(fetch(r).catch(() => caches.match('/offline'))); return; }
  if (u.pathname.startsWith('/assets/')) e.respondWith(fetch(r).then(res => { const c = res.clone(); caches.open(V).then(x => x.put(r, c)); return res; }).catch(() => caches.match(r)));
});
