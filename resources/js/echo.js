import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// If you're still using Echo for other real-time events, keep this block.
// Otherwise, you can remove it entirely.
window.Pusher = Pusher;
window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: null,
    wssPort: 443,
    forceTLS: true,
    encrypted: true,
    enabledTransports: ['wss'],
    disableStats: true,
    auth: {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            Accept: 'application/json',
        },
    },
    authEndpoint: '/broadcasting/auth',
});

/**
 * Submit a normal (non-streaming) request to your Laravel backend.
 * The server should return the entire response at once.
 */
async function fetchChatResponse(question) {
    const chatContainer = document.getElementById('chat-container');
    const questionInput = document.getElementById('question-input');

    // Create AI response element with loading indicator
    const aiResponseEl = document.createElement('div');
    aiResponseEl.classList.add(
        'bg-blue-100',
        'p-3',
        'rounded-lg',
        'self-start',
        'max-w-[80%]',
        'ai-response',
        'mb-2',
        'prose'
    );

    // Add loading spinner
    const loadingSpinner = document.createElement('div');
    loadingSpinner.classList.add('loading-spinner');
    aiResponseEl.appendChild(loadingSpinner);

    // Add loading text
    const loadingText = document.createElement('span');
    loadingText.textContent = 'AI is thinking...';
    aiResponseEl.appendChild(loadingText);

    chatContainer.appendChild(aiResponseEl);
    chatContainer.scrollTop = chatContainer.scrollHeight;

    try {
        const response = await fetch('/chat', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: JSON.stringify({question}),
        });

        if (!response.ok) {
            throw new Error(`Request failed. Status: ${response.status}`);
        }

        const data = await response.json();

        // Remove loading spinner and text
        aiResponseEl.innerHTML = data.answerHtml ?? '(No response from AI)';

        if (questionInput) {
            questionInput.value = '';
        }
    } catch (error) {
        console.error('Chat request error:', error);
        aiResponseEl.textContent = `An error occurred: ${error.message}`;
        aiResponseEl.classList.remove('bg-blue-100');
        aiResponseEl.classList.add('bg-red-100', 'text-red-800');
    }
}

/**
 * Initialize form submission for a normal chat request.
 */
document.addEventListener('DOMContentLoaded', function () {
    const chatForm = document.getElementById('chat-form');
    const chatContainer = document.getElementById('chat-container');
    const questionInput = document.getElementById('question-input');
    const clearChatButton = document.getElementById('clear-chat');

    chatForm.addEventListener('submit', async function (e) {
        e.preventDefault();
        const question = questionInput.value.trim();
        if (!question) return;

        // Clear input immediately
        questionInput.value = '';

        // Add user's question to the chat
        appendMessage('You', question, 'bg-gray-100');

        // Show loading spinner
        const loadingSpinner = document.createElement('div');
        loadingSpinner.classList.add('loading-spinner', 'mx-auto', 'my-4');
        chatContainer.appendChild(loadingSpinner);

        // Scroll to bottom to show the spinner
        chatContainer.scrollTop = chatContainer.scrollHeight;

        try {
            const response = await fetch('/chat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({question}),
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            // Remove loading spinner
            loadingSpinner.remove();

            if (data.answerHtml) {
                // Add AI's response to the chat
                appendMessage('AI', data.answerHtml, 'bg-blue-100');
            } else {
                throw new Error('Invalid response format');
            }
        } catch (error) {
            console.error('Error:', error);
            loadingSpinner.remove();
            appendMessage('Error', 'Failed to get response: ' + error.message, 'bg-red-100');
        }

        // Scroll to bottom after adding new message
        chatContainer.scrollTop = chatContainer.scrollHeight;
    });

    function appendMessage(sender, content, bgClass) {
        const messageDiv = document.createElement('div');
        messageDiv.classList.add(bgClass, 'p-3', 'rounded-lg', 'max-w-3/4', 'mb-2');
        messageDiv.innerHTML = `<strong>${sender}:</strong> ${content}`;
        chatContainer.appendChild(messageDiv);
    }

    // Clear chat functionality (unchanged)
    clearChatButton.addEventListener('click', function () {
        if (confirm('Are you sure you want to clear all conversations?')) {
            fetch('/clear-conversations', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json'
                },
            })
                .then(response => response.json())
                .then(data => {
                    console.log(data.message);
                    chatContainer.innerHTML = '';
                })
                .catch(error => console.error('Error:', error));
        }
    });
});