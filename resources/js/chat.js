import './bootstrap';
import axios from 'axios';

class ChatManager {
    constructor(config) {
        this.userId = config.userId;
        this.routes = config.routes;
        this.csrfToken = config.csrfToken;

        // Configure axios defaults
        axios.defaults.headers.common['X-CSRF-TOKEN'] = this.csrfToken;
        axios.defaults.headers.common['Content-Type'] = 'application/json';

        // Element references
        this.chatMessages = document.getElementById('chat-messages');
        this.chatForm = document.getElementById('chat-form');
        this.questionInput = document.getElementById('question');

        // Modal elements
        this.modalContainer = document.getElementById('modalContainer');
        this.modalBackdrop = document.getElementById('modalBackdrop');
        this.clearChatBtn = document.getElementById('clearChatBtn');
        this.confirmClear = document.getElementById('confirmClear');
        this.cancelClear = document.getElementById('cancelClear');

        this.initializeEventListeners();
        this.initializeChat();
    }

    async initializeChat() {
        await this.loadChatHistory();
        this.initializeRealtimeUpdates();
    }

    initializeEventListeners() {
        // Chat form submission
        this.chatForm.addEventListener('submit', (e) => this.handleSubmit(e));

        // Modal event listeners
        this.clearChatBtn.addEventListener('click', () => this.showModal());
        this.modalBackdrop.addEventListener('click', () => this.hideModal());
        this.cancelClear.addEventListener('click', () => this.hideModal());
        this.confirmClear.addEventListener('click', () => this.clearConversations());

        // Close modal on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !this.modalContainer.classList.contains('hidden')) {
                this.hideModal();
            }
        });
    }

    showModal() {
        this.modalContainer.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    hideModal() {
        this.modalContainer.classList.add('hidden');
        document.body.style.overflow = '';
    }

    async loadChatHistory() {
        try {
            const {data} = await axios.get(this.routes.loadChat);

            if (data.chat_history) {
                data.chat_history.forEach((message) => {
                    this.renderMessage(
                        message.user_id,
                        message.input,
                        message.output,
                        message.created_at
                    );
                });
            }
        } catch (error) {
            console.error('Error loading chat history:', error.message);
        }
    }

    renderMessage(userId, input, output, createdAt = new Date().toISOString()) {
        const isCurrentUser = userId === this.userId;
        const alignment = isCurrentUser ? 'items-end' : 'items-start';

        let userMessageHtml = input ? `
            <div class="self-end bg-blue-50 dark:bg-blue-900/50 rounded-2xl p-4 max-w-[80%] shadow-sm">
                <div class="text-gray-800 dark:text-gray-200">
                    ${this.escapeHtml(input)}
                </div>
                <span class="block text-xs text-gray-500 dark:text-gray-400 mt-2 text-right">
                    ${new Date(createdAt).toLocaleString()}
                </span>
            </div>
        ` : '';

        let assistantMessageHtml = '';
        if (output) {
            const trimmedOutput = output.trim();
            const isHtml = trimmedOutput.startsWith('<') && trimmedOutput.endsWith('>');
            const messageClass = isHtml ? 'w-full' : 'max-w-[80%]';

            assistantMessageHtml = `
                <div class="self-start bg-white dark:bg-gray-800 rounded-2xl p-4 ${messageClass} shadow-sm">
                    <div class="${isHtml ? '' : 'prose dark:prose-invert prose-sm max-w-none'}">
                        ${isHtml ? trimmedOutput : this.escapeHtml(output)}
                    </div>
                    <span class="block text-xs text-gray-500 dark:text-gray-400 mt-2">
                        ${new Date(createdAt).toLocaleString()}
                    </span>
                </div>
            `;
        }

        const messageHtml = `
            <div class="flex flex-col ${alignment} space-y-2">
                ${userMessageHtml}
                ${assistantMessageHtml}
            </div>
        `;

        this.appendToChat(messageHtml);
    }

    appendToChat(html) {
        this.chatMessages.insertAdjacentHTML('beforeend', html);
        this.chatMessages.scrollTop = this.chatMessages.scrollHeight;
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    async clearConversations() {
        try {
            const {data} = await axios.post(this.routes.clearConversations);

            if (data.status === 'success') {
                this.chatMessages.innerHTML = '';
                this.hideModal();
            }
        } catch (error) {
            console.error('Error clearing conversations:', error);
        }
    }

    async handleSubmit(e) {
        e.preventDefault();

        const question = this.questionInput.value.trim();
        if (!question) return;

        this.questionInput.value = '';

        // Show user message
        this.renderMessage(this.userId, question, '', new Date().toISOString());

        // Add the loading indicator
        this.addLoadingIndicator();

        try {
            const {data} = await axios.post(this.chatForm.action, {question});

            // Remove the loading indicator
            this.removeLoadingIndicator();

            if (data.response) {
                // Show assistant's response
                this.renderMessage(null, '', data.response, new Date().toISOString());
            } else {
                throw new Error(data.error || 'An error occurred.');
            }
        } catch (error) {
            console.error('Error:', error.message);
            this.removeLoadingIndicator();
            this.renderMessage(null, '', 'Error: Unable to process your request.', new Date().toISOString());
        }
    }

    addLoadingIndicator() {
        const loadingIndicatorHtml = `
            <div class="self-start bg-white dark:bg-gray-800 rounded-2xl p-4 max-w-[80%] shadow-sm" id="loading-indicator">
                <div class="flex items-center space-x-2">
                    <div class="flex space-x-1">
                        <div class="w-2 h-2 bg-blue-500 rounded-full animate-bounce [animation-delay:-0.3s]"></div>
                        <div class="w-2 h-2 bg-blue-500 rounded-full animate-bounce [animation-delay:-0.15s]"></div>
                        <div class="w-2 h-2 bg-blue-500 rounded-full animate-bounce"></div>
                    </div>
                    <span class="text-sm text-gray-500 dark:text-gray-400">Assistant is typing...</span>
                </div>
            </div>
        `;
        this.appendToChat(loadingIndicatorHtml);
    }

    removeLoadingIndicator() {
        const loadingIndicator = document.getElementById('loading-indicator');
        if (loadingIndicator) {
            loadingIndicator.remove();
        }
    }

    initializeRealtimeUpdates() {
        Echo.private(`chat.${this.userId}`).listen('MessageSent', (event) => {
            this.renderMessage(event.user_id, event.input, event.output, event.created_at);
        });
    }
}

// Initialize the chat when the DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    const chatConfig = {
        userId: window.userId, // Make sure to define this in your blade template
        routes: {
            loadChat: window.routes.loadChat, // Define these routes in your blade template
            clearConversations: window.routes.clearConversations
        },
        csrfToken: document.querySelector('meta[name="csrf-token"]').content
    };

    const chatManager = new ChatManager(chatConfig);
});

export default ChatManager;