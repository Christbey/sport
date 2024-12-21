import './bootstrap';
import './echo';
import 'flowbite';
import ChatManager from './ChatManager';
import process from 'process';
import 'alpinejs';

// Make ChatManager available globally
window.ChatManager = ChatManager;

// Initialize unique ID function
function initializeUniqueId() {
    let uniqueId = localStorage.getItem('unique_id');
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

// Initialize chat if configuration exists
function initializeChat() {
    if (window.chatConfig) {
        const chatManager = new ChatManager(window.chatConfig);
    }
}

// Call initializations when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    initializeUniqueId();
    initializeChat();
});

// Make functions available globally
window.initializeUniqueId = initializeUniqueId;
window.populateQuestion = function (question) {
    const input = document.getElementById('question');
    if (input) {
        input.value = question;
        input.focus();
    }
};

window.process = process;