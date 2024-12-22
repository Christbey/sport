import './bootstrap';
import 'flowbite';
import 'alpinejs';

// Make ChatManager available globally

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


// Make functions available globally
window.initializeUniqueId = initializeUniqueId;
window.populateQuestion = function (question) {
    const input = document.getElementById('question');
    if (input) {
        input.value = question;
        input.focus();
    }
};
