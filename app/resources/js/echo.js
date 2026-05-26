import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const key = import.meta.env.VITE_REVERB_APP_KEY;

if (key) {
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key,
        wsHost: import.meta.env.VITE_REVERB_HOST ?? window.location.hostname,
        wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
        wssPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'http') === 'https',
        enabledTransports: ['ws', 'wss'],
        authEndpoint: '/broadcasting/auth',
        auth: {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
            },
        },
    });
}

export function onOrganizationEvent(orgId, event, callback) {
    if (!window.Echo || !orgId) {
        return null;
    }

    const channel = window.Echo.private(`organization.${orgId}`);

    channel.listen(`.${event}`, callback);

    return channel;
}

export function onConversationEvent(conversationId, event, callback) {
    if (!window.Echo || !conversationId) {
        return null;
    }

    const channel = window.Echo.private(`conversation.${conversationId}`);

    channel.listen(`.${event}`, callback);

    return channel;
}
