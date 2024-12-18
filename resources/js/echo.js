import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Assign Pusher to the global window object
window.Pusher = Pusher;

// Configure Laravel Echo with Pusher (managed service)
window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS: true,
    encrypted: true,
});
