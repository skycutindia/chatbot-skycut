(function () {
    'use strict';

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const pollUrl = document.querySelector('meta[name="agent-poll-url"]')?.content || '/inbox/poll';
    const subscribeUrl = document.querySelector('meta[name="agent-push-subscribe-url"]')?.content;
    let deferredInstallPrompt = null;
    let lastAwaiting = null;

    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/agent-sw.js', { scope: '/' }).catch(() => {});
        });
    }

    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredInstallPrompt = e;
        showInstallBanner();
    });

    function showInstallBanner() {
        if (localStorage.getItem('agent-pwa-install-dismissed') === '1') return;
        const bar = document.getElementById('agent-pwa-install');
        if (bar) bar.hidden = false;
    }

    document.getElementById('agent-pwa-install-btn')?.addEventListener('click', async () => {
        if (!deferredInstallPrompt) return;
        deferredInstallPrompt.prompt();
        await deferredInstallPrompt.userChoice;
        deferredInstallPrompt = null;
        document.getElementById('agent-pwa-install')?.setAttribute('hidden', '');
    });

    document.getElementById('agent-pwa-install-dismiss')?.addEventListener('click', () => {
        localStorage.setItem('agent-pwa-install-dismissed', '1');
        document.getElementById('agent-pwa-install')?.setAttribute('hidden', '');
    });

    document.getElementById('agent-enable-notify')?.addEventListener('click', async () => {
        if (!('Notification' in window)) return;
        const permission = await Notification.requestPermission();
        if (permission === 'granted') {
            localStorage.setItem('agent-notify-enabled', '1');
            updateNotifyButton(true);
            subscribePush().catch(() => {});
            showLocalNotification('Notifications enabled', 'You will be alerted for new live chats.');
        }
    });

    function updateNotifyButton(enabled) {
        const btn = document.getElementById('agent-enable-notify');
        if (!btn) return;
        btn.textContent = enabled ? 'Notifications on' : 'Enable notifications';
        btn.disabled = enabled && Notification.permission === 'granted';
    }

    if (Notification?.permission === 'granted' && localStorage.getItem('agent-notify-enabled') === '1') {
        updateNotifyButton(true);
        subscribePush().catch(() => {});
    }

    async function subscribePush() {
        if (!subscribeUrl || !('serviceWorker' in navigator) || !('PushManager' in window)) {
            return;
        }

        const registration = await navigator.serviceWorker.ready;
        let subscription = await registration.pushManager.getSubscription();

        if (!subscription) {
            try {
                subscription = await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: urlBase64ToUint8Array(getVapidKey()),
                });
            } catch (_) {
                return;
            }
        }

        const json = subscription.toJSON();
        await fetch(subscribeUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                endpoint: json.endpoint,
                keys: json.keys,
            }),
        });
    }

    function getVapidKey() {
        return document.querySelector('meta[name="vapid-public-key"]')?.content || '';
    }

    function urlBase64ToUint8Array(base64String) {
        if (!base64String) return new Uint8Array();
        const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
        const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        const raw = atob(base64);
        const arr = new Uint8Array(raw.length);
        for (let i = 0; i < raw.length; i++) arr[i] = raw.charCodeAt(i);
        return arr;
    }

    function showLocalNotification(title, body, url) {
        if (Notification.permission !== 'granted') return;
        if (document.visibilityState === 'visible') return;
        const n = new Notification(title, { body, icon: '/agent/icon-192.svg' });
        n.onclick = () => {
            window.focus();
            if (url) window.location.href = url;
            n.close();
        };
    }

    async function pollInbox() {
        if (localStorage.getItem('agent-notify-enabled') !== '1') return;
        if (Notification.permission !== 'granted') return;

        try {
            const res = await fetch(pollUrl, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!res.ok) return;
            const data = await res.json();
            const awaiting = data.awaiting ?? 0;

            if (lastAwaiting !== null && awaiting > lastAwaiting) {
                showLocalNotification(
                    'Live chat queue',
                    `${awaiting} conversation(s) awaiting an agent`,
                    '/inbox'
                );
            }

            lastAwaiting = awaiting;
        } catch (_) {}
    }

    setInterval(pollInbox, 30000);
    pollInbox();
})();
