// resources/js/chat.js
import './bootstrap';
import ChatManager from './ChatManager';

// Initialize ChatManager when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    if (!window.chatConfig) {
        console.error('Chat configuration not found');
        return;
    }

    const chatManager = new ChatManager(window.chatConfig);
});

// Export the question population utility
window.populateQuestion = function (question) {
    const input = document.getElementById('question');
    if (input) {
        input.value = question;
        input.focus();
    }
};