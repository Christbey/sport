import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Assign Pusher to the global window object
window.Pusher = Pusher;

// Configure Laravel Echo with Pusher
window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY, // Use Pusher app key from environment variables
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER, // Use Pusher cluster from environment variables
    forceTLS: true, // Ensure secure WebSocket connection
    wsHost: window.location.hostname, // Use the host of the current application
    wsPort: 80, // WebSocket port for HTTP
    wssPort: 443, // WebSocket port for HTTPS
    enabledTransports: ['ws', 'wss'], // Specify allowed transport protocols
});
