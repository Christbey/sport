import './bootstrap';
import 'flowbite'; // Import Flowbite JS
import ChatManager from './chat'; // Note: .js extension is not needed with Vite
import process from 'process';
import 'alpinejs';

window.ChatManager = ChatManager; // Make it available globally

// In app.js

// Function to initialize unique ID
function initializeUniqueId() {
    // Check for unique ID in localStorage first
    let uniqueId = localStorage.getItem('unique_id');

    // If not in localStorage, check response headers
    if (!uniqueId) {
        const headers = document.getElementsByTagName('meta');
        for (let header of headers) {
            if (header.getAttribute('name') === 'x-unique-id') {
                uniqueId = header.getAttribute('content');
                if (uniqueId) {
                    localStorage.setItem('unique_id', uniqueId);
                    break;
                }
            }
        }
    }
}

// Call it when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    initializeUniqueId();
});

// If you're using Alpine.js or need it globally
window.initializeUniqueId = initializeUniqueId;

window.process = process;

