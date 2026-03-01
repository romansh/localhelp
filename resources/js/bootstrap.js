import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Echo + Reverb for real-time broadcasting.
 *
 * Strategy:
 *  - Reverb WebSocket is the primary transport (instant delivery).
 *  - wire:poll.30000ms.visible in map-view acts as a polling fallback:
 *    it runs every 30 s and covers the cases when Reverb is unavailable
 *    or the connection drops.
 *  - window.echoConnected lets other code know whether WS is live.
 */
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;
window.echoConnected = false;

try {
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
        wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
    });

    // Track WebSocket connection state so the rest of the app can detect it.
    const pusherConn = window.Echo.connector.pusher.connection;

    pusherConn.bind('connected', () => {
        window.echoConnected = true;
        console.info('[LocalHelp] Reverb WebSocket connected — real-time active.');
    });

    pusherConn.bind('disconnected', () => {
        window.echoConnected = false;
        console.warn('[LocalHelp] Reverb disconnected — Livewire polling (30 s) takes over.');
    });

    pusherConn.bind('unavailable', () => {
        window.echoConnected = false;
        console.warn('[LocalHelp] Reverb unavailable — Livewire polling (30 s) takes over.');
    });

} catch (e) {
    console.warn('[LocalHelp] Echo init failed — Livewire polling (30 s) takes over.', e.message);
}
