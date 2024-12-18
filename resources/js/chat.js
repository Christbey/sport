import './bootstrap';
import axios from 'axios';

class ChatManager {
    constructor(config) {
        this.cacheDOM();
        this.config = {
            userId: config.userId,
            routes: config.routes,
            messageTemplate: this.createMessageTemplate(),
        };
        this.setupAxios(config.csrfToken);
        this.initializeEventListeners();
        this.initializeChat();
    }

    // Cache all necessary DOM elements
    cacheDOM() {
        const getEl = id => document.getElementById(id);
        this.elements = {
            chat: getEl('chat-messages'),
            form: getEl('chat-form'),
            input: getEl('question'),
            remainingRequests: getEl('remaining-requests'),
            modal: {
                container: getEl('modalContainer'),
                backdrop: getEl('modalBackdrop'),
                clearBtn: getEl('clearChatBtn'),
                confirmBtn: getEl('confirmClear'),
                cancelBtn: getEl('cancelClear'),
            },
        };
    }

    // Setup Axios defaults
    setupAxios(csrfToken) {
        axios.defaults.headers.common = {
            'X-CSRF-TOKEN': csrfToken,
            'Content-Type': 'application/json',
        };
    }

    // Create message templates for user and assistant
    createMessageTemplate() {
        const escape = this.escapeHtml;
        return {
            user: ({content, time}) => `
                <div class="self-end bg-blue-50 dark:bg-blue-900/50 rounded-2xl p-4 max-w-[80%] shadow-sm">
                    <div class="text-gray-800 dark:text-gray-200">${escape(content)}</div>
                    <span class="block text-xs text-gray-500 dark:text-gray-400 mt-2 text-right">${time}</span>
                </div>`,
            assistant: ({content, time, isHtml}) => `
                <div class="self-start bg-white dark:bg-gray-800 rounded-2xl p-4 ${isHtml ? 'w-full' : 'max-w-[80%]'} shadow-sm">
                    <div class="${isHtml ? '' : 'prose dark:prose-invert prose-sm max-w-none'}">
                        ${isHtml ? content : escape(content)}
                    </div>
                    <span class="block text-xs text-gray-500 dark:text-gray-400 mt-2">${time}</span>
                </div>`,
        };
    }

    // Initialize event listeners
    initializeEventListeners() {
        this.elements.form.addEventListener('submit', this.handleSubmit.bind(this));

        const {modal} = this.elements;
        modal.clearBtn.addEventListener('click', () => this.toggleModal(true));
        [modal.backdrop, modal.cancelBtn].forEach(btn =>
            btn.addEventListener('click', () => this.toggleModal(false))
        );
        modal.confirmBtn.addEventListener('click', () => this.clearConversations());

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') this.toggleModal(false);
        });
    }

    // Toggle modal visibility
    toggleModal(show) {
        this.elements.modal.container.classList.toggle('hidden', !show);
        document.body.style.overflow = show ? 'hidden' : '';
    }

    // Initialize chat by loading history and setting up real-time updates
    async initializeChat() {
        await this.loadChatHistory();
        this.initializeRealtimeUpdates();
    }

    // Handle form submission
    async handleSubmit(e) {
        e.preventDefault();
        const question = this.elements.input.value.trim();
        if (!question) return;

        this.elements.input.value = '';
        this.renderMessage({type: 'user', content: question});
        this.addLoadingIndicator();

        try {
            const {data} = await axios.post(this.elements.form.action, {question});
            this.removeLoadingIndicator();

            if (data.response) {
                this.renderMessage({type: 'assistant', content: data.response});
                this.updateRequestCounter(data);
            }
        } catch (error) {
            this.handleError(error);
        }
    }

    // Render a message in the chat
    renderMessage({type, content, time = new Date().toLocaleString()}) {
        const isHtml = typeof content === 'string' && content.trim().startsWith('<') && content.trim().endsWith('>');
        const messageHtml = this.config.messageTemplate[type]({content, time, isHtml});

        const wrapper = document.createElement('div');
        wrapper.className = `flex flex-col ${type === 'user' ? 'items-end' : 'items-start'} space-y-2`;
        wrapper.innerHTML = messageHtml;

        this.elements.chat.appendChild(wrapper);
        this.elements.chat.scrollTop = this.elements.chat.scrollHeight;
    }

    // Add loading indicator
    addLoadingIndicator() {
        const loadingHtml = `
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
        const wrapper = document.createElement('div');
        wrapper.className = 'flex flex-col items-start space-y-2';
        wrapper.innerHTML = loadingHtml;
        this.elements.chat.appendChild(wrapper);
        this.elements.chat.scrollTop = this.elements.chat.scrollHeight;
    }

    // Remove loading indicator
    removeLoadingIndicator() {
        const loadingIndicator = document.getElementById('loading-indicator');
        if (loadingIndicator) {
            loadingIndicator.closest('.flex.flex-col')?.remove();
        }
    }

    // Clear all conversations
    async clearConversations() {
        try {
            const {data} = await axios.post(this.config.routes.clearConversations);
            if (data.status === 'success') {
                this.elements.chat.innerHTML = '';
                this.toggleModal(false);
            }
        } catch (error) {
            console.error('Error clearing conversations:', error);
        }
    }

    // Update the request counter display
    updateRequestCounter({remaining_requests, max_requests, seconds_until_reset}) {
        const {remainingRequests} = this.elements;
        if (!remainingRequests) return;

        const percentage = (remaining_requests / max_requests) * 100;
        const colorClass = this.getStatusColorClass(percentage);
        const resetMinutes = Math.ceil(seconds_until_reset / 60);

        remainingRequests.innerHTML = `
            <span class="font-medium">Requests remaining:</span> 
            <span class="${colorClass}">${remaining_requests}/${max_requests}</span>
            ${remaining_requests < max_requests * 0.2 || remaining_requests === 0
            ? `<span class="block text-sm mt-1 text-gray-600 dark:text-gray-400">
                      Resets in ${resetMinutes} minute${resetMinutes !== 1 ? 's' : ''}
                  </span>`
            : ''}
        `;
    }

    // Determine color class based on percentage
    getStatusColorClass(percentage) {
        if (percentage === 0) return 'text-red-600 dark:text-red-400 font-bold';
        if (percentage <= 20) return 'text-orange-600 dark:text-orange-400 font-medium';
        if (percentage <= 50) return 'text-yellow-600 dark:text-yellow-400';
        return 'text-green-600 dark:text-green-400';
    }

    // Load chat history with optimized batch rendering
    async loadChatHistory() {
        try {
            const {data} = await axios.get(this.config.routes.loadChat);
            const {chat_history: messages} = data;

            if (!messages?.length) return;

            const batchSize = this.calculateBatchSize();
            const fragment = document.createDocumentFragment();

            for (let i = 0; i < messages.length; i += batchSize) {
                const batch = messages.slice(i, i + batchSize);
                batch.forEach(message => {
                    const isUser = message.user_id === this.config.userId;
                    const content = isUser ? message.input : message.output;
                    const time = new Date(message.created_at).toLocaleString();
                    const isHtml = typeof content === 'string' && content.trim().startsWith('<') && content.trim().endsWith('>');

                    const messageHtml = this.config.messageTemplate[isUser ? 'user' : 'assistant']({
                        content,
                        time,
                        isHtml,
                    });

                    const wrapper = document.createElement('div');
                    wrapper.className = `flex flex-col ${isUser ? 'items-end' : 'items-start'} space-y-2`;
                    wrapper.innerHTML = messageHtml;
                    fragment.appendChild(wrapper);
                });

                this.elements.chat.appendChild(fragment.cloneNode(true));
                fragment.innerHTML = ''; // Clear fragment
                await this.sleep(0); // Yield to main thread
            }

            this.elements.chat.scrollTop = this.elements.chat.scrollHeight;
        } catch (error) {
            console.error('Error loading chat history:', error);
        }
    }

    // Calculate batch size based on view height
    calculateBatchSize() {
        const viewHeight = this.elements.chat.clientHeight;
        const approxMessageHeight = 100; // Estimated height per message in px
        return Math.ceil(viewHeight / approxMessageHeight) * 2;
    }

    // Utility sleep function
    sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    // Escape HTML to prevent XSS
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Handle errors during message submission
    handleError(error) {
        this.removeLoadingIndicator();

        if (error.response) {
            const {status, data} = error.response;
            if (status === 403) {
                window.location.href = '/subscriptions';
                return;
            }

            if (status === 429) {
                this.renderMessage({
                    type: 'assistant',
                    content: `Error: ${data.error || 'Rate limit exceeded.'}`,
                });
                this.updateRequestCounter(data);
                return;
            }
        }

        this.renderMessage({
            type: 'assistant',
            content: 'Error: Unable to process your request.',
        });
    }

    // Initialize real-time updates using Echo
    initializeRealtimeUpdates() {
        Echo.private(`chat.${this.config.userId}`).listen('MessageSent', ({user_id, input, output, created_at}) => {
            const type = user_id === this.config.userId ? 'user' : 'assistant';
            const content = type === 'user' ? input : output;
            const time = new Date(created_at).toLocaleString();

            this.renderMessage({type, content, time});
        });
    }
}

export default ChatManager;
