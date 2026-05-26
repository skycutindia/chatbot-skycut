/* SkyCut Agent PWA — service worker */
const CACHE = 'skycut-agent-v1';
const PRECACHE = [
    '/offline.html',
    '/css/dashboard-theme.css',
    '/js/dashboard-ui.js',
    '/js/agent-pwa.js',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE).then((cache) => cache.addAll(PRECACHE)).then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k)))
        ).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const { request } = event;

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    if (url.pathname.startsWith('/api/') || url.pathname.includes('/inbox/poll')) {
        return;
    }

    if (url.pathname.match(/\.(css|js|woff2?|png|svg|jpg|webp)$/)) {
        event.respondWith(
            caches.match(request).then((cached) =>
                cached || fetch(request).then((response) => {
                    const copy = response.clone();
                    caches.open(CACHE).then((cache) => cache.put(request, copy));
                    return response;
                })
            )
        );
        return;
    }

    if (request.headers.get('accept')?.includes('text/html')) {
        event.respondWith(
            fetch(request).catch(() => caches.match('/offline.html'))
        );
    }
});

self.addEventListener('push', (event) => {
    let data = { title: 'Live chat', body: 'New activity in your inbox' };
    try {
        if (event.data) {
            data = event.data.json();
        }
    } catch (_) {}

    event.waitUntil(
        self.registration.showNotification(data.title || 'Live chat', {
            body: data.body || '',
            icon: '/agent/icon-192.svg',
            badge: '/agent/icon-192.svg',
            data: { url: data.url || '/inbox' },
        })
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const url = event.notification.data?.url || '/inbox';
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((list) => {
            for (const client of list) {
                if ('focus' in client) {
                    client.navigate(url);
                    return client.focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(url);
            }
        })
    );
});
