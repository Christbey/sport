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

// In your resources/js/app.js
document.addEventListener('DOMContentLoaded', function () {
    const dropdownToggles = document.querySelectorAll('[data-collapse-toggle]');

    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function () {
            const targetId = this.getAttribute('data-collapse-toggle');
            const target = document.getElementById(targetId);

            if (target) {
                target.classList.toggle('hidden');
            }
        });
    });
});
// Mobile menu
document.addEventListener('DOMContentLoaded', function () {
    const drawerToggle = document.querySelector('[data-drawer-toggle="drawer-navigation"]');
    const drawer = document.getElementById('drawer-navigation');

    drawerToggle?.addEventListener('click', function () {
        drawer.classList.toggle('-translate-x-full');
    });
});
