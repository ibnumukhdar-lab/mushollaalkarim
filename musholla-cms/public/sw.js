/* Service Worker Musholla Al Karim — membuat situs bisa dipasang (PWA) dan
   menerima notifikasi tulisan baru. Ganti CACHE bila ada perubahan besar. */
const CACHE = 'alkarim-shell-v1';
const PRECACHE = [
  '/offline.html',
  '/site.webmanifest',
  '/icons/icon-192.png',
  '/icons/icon-512.png',
];

self.addEventListener('install', (e) => {
  e.waitUntil(
    caches.open(CACHE).then((c) => c.addAll(PRECACHE)).catch(() => {}).then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (e) => {
  e.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (e) => {
  const req = e.request;
  if (req.method !== 'GET') return;

  const url = new URL(req.url);

  // Halaman: selalu ambil dari server dulu (isi musholla sering berubah),
  // kalau tidak ada sambungan baru pakai salinan/ offline.
  if (req.mode === 'navigate') {
    e.respondWith(
      fetch(req)
        .then((res) => {
          if (res && res.ok) {
            const salinan = res.clone();
            caches.open(CACHE).then((c) => c.put(req, salinan)).catch(() => {});
          }
          return res;
        })
        .catch(() => caches.match(req).then((m) => m || caches.match('/offline.html')))
    );
    return;
  }

  // Aset statis milik situs sendiri: cepat dari salinan, diperbarui di latar.
  if (url.origin !== self.location.origin) return;
  if (!/\.(css|js|png|jpg|jpeg|webp|svg|ico|woff2?|ttf)$/i.test(url.pathname)) return;

  e.respondWith(
    caches.match(req).then((dariSalinan) => {
      const segar = fetch(req)
        .then((res) => {
          if (res && res.ok) {
            const salinan = res.clone();
            caches.open(CACHE).then((c) => c.put(req, salinan)).catch(() => {});
          }
          return res;
        })
        .catch(() => dariSalinan);
      return dariSalinan || segar;
    })
  );
});

/* ===== Notifikasi (web push) ===== */
self.addEventListener('push', (e) => {
  let d = {};
  try { d = e.data ? e.data.json() : {}; } catch (err) { d = {}; }

  const judul = d.title || 'Musholla Al Karim';
  const opsi = {
    body: d.body || 'Ada kabar baru dari Musholla Al Karim.',
    tag: d.tag || 'alkarim',
    renotify: true,
    vibrate: d.vibrate || [200, 90, 200],
    icon: d.icon || '/icons/icon-192.png',
    badge: d.badge || '/icons/icon-192.png',
    data: d.data || { url: '/' },
  };
  if (d.image) opsi.image = d.image;

  e.waitUntil(self.registration.showNotification(judul, opsi));
});

self.addEventListener('notificationclick', (e) => {
  e.notification.close();
  const tujuan = (e.notification.data && e.notification.data.url) || '/';

  e.waitUntil(
    self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((daftar) => {
      for (const c of daftar) {
        if (c.url.indexOf(self.location.origin) === 0 && 'focus' in c) {
          c.focus();
          if ('navigate' in c) return c.navigate(tujuan);
          return undefined;
        }
      }
      return self.clients.openWindow(tujuan);
    })
  );
});
