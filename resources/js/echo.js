import Echo from 'laravel-echo';

import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wssPort: 443,
    forceTLS: true,
    encrypted: true,
    enabledTransports: ['ws', 'wss'],
    auth: {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    }
});

// Add connection debugging
window.Echo.connector.pusher.connection.bind('connecting', () => {
    console.log('Attempting to connect to Reverb...');
    console.log('Connection config:', {
        key: import.meta.env.VITE_REVERB_APP_KEY,
        host: import.meta.env.VITE_REVERB_HOST,
        port: import.meta.env.VITE_REVERB_PORT
    });
});

window.Echo.connector.pusher.connection.bind('connected', () => {
    console.log('Successfully connected to Reverb');
});

window.Echo.connector.pusher.connection.bind('error', (error) => {
    console.error('Reverb connection error:', error);
});

window.Echo.connector.pusher.connection.bind('disconnected', () => {
    console.log('Disconnected from Reverb');
});

