import './bootstrap';
import axios from 'axios';

class ChatManager {
    constructor(config) {
        // Cache DOM elements once
        this.elements = {
            chat: document.getElementById('chat-messages'),
            form: document.getElementById('chat-form'),
            input: document.getElementById('question'),
            remainingRequests: document.getElementById('remaining-requests'),
            modal: {
                container: document.getElementById('modalContainer'),
                backdrop: document.getElementById('modalBackdrop'),
                clearBtn: document.getElementById('clearChatBtn'),
                confirmBtn: document.getElementById('confirmClear'),
                cancelBtn: document.getElementById('cancelClear')
            }
        };

        // Configuration
        this.config = {
            userId: config.userId,
            routes: config.routes,
            messageTemplate: this.createMessageTemplate()
        };

        // Set up axios
        axios.defaults.headers.common = {
            'X-CSRF-TOKEN': config.csrfToken,
            'Content-Type': 'application/json'
        };

        this.initializeEventListeners();
        this.initializeChat();
    }


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

    removeLoadingIndicator() {
        const loadingIndicator = document.getElementById('loading-indicator');
        if (loadingIndicator) {
            loadingIndicator.closest('.flex.flex-col').remove();
        }
    }

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

    createMessageTemplate() {
        return {
            user: message => `
                <div class="self-end bg-blue-50 dark:bg-blue-900/50 rounded-2xl p-4 max-w-[80%] shadow-sm">
                    <div class="text-gray-800 dark:text-gray-200">${message.content}</div>
                    <span class="block text-xs text-gray-500 dark:text-gray-400 mt-2 text-right">
                        ${message.time}
                    </span>
                </div>`,
            assistant: message => `
                <div class="self-start bg-white dark:bg-gray-800 rounded-2xl p-4 ${message.isHtml ? 'w-full' : 'max-w-[80%]'} shadow-sm">
                    <div class="${message.isHtml ? '' : 'prose dark:prose-invert prose-sm max-w-none'}">
                        ${message.content}
                    </div>
                    <span class="block text-xs text-gray-500 dark:text-gray-400 mt-2">
                        ${message.time}
                    </span>
                </div>`
        };
    }

    async initializeChat() {
        await this.loadChatHistory();
        this.initializeRealtimeUpdates();
    }

    initializeEventListeners() {
        // Use event delegation for chat-related events
        this.elements.form.addEventListener('submit', e => this.handleSubmit(e));

        // Modal events
        const modal = this.elements.modal;
        modal.clearBtn.addEventListener('click', () => this.toggleModal(true));
        modal.backdrop.addEventListener('click', () => this.toggleModal(false));
        modal.cancelBtn.addEventListener('click', () => this.toggleModal(false));
        modal.confirmBtn.addEventListener('click', () => this.clearConversations());

        // Escape key handler
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') this.toggleModal(false);
        });
    }

    toggleModal(show) {
        this.elements.modal.container.classList.toggle('hidden', !show);
        document.body.style.overflow = show ? 'hidden' : '';
    }

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

    handleError(error) {
        this.removeLoadingIndicator();

        if (error.response?.status === 403) {
            window.location.href = '/subscriptions';
            return;
        }

        if (error.response?.status === 429) {
            this.renderMessage({
                type: 'assistant',
                content: `Error: ${error.response.data.error || 'Rate limit exceeded.'}`
            });
            this.updateRequestCounter(error.response.data);
        } else {
            this.renderMessage({
                type: 'assistant',
                content: 'Error: Unable to process your request.'
            });
        }
    }

    renderMessage({type, content, time = new Date().toLocaleString()}) {
        const isHtml = content?.trim().startsWith('<') && content?.trim().endsWith('>');
        const messageHtml = this.config.messageTemplate[type]({
            content: isHtml ? content : this.escapeHtml(content),
            time,
            isHtml
        });

        const wrapper = document.createElement('div');
        wrapper.className = `flex flex-col ${type === 'user' ? 'items-end' : 'items-start'} space-y-2`;
        wrapper.innerHTML = messageHtml;

        this.elements.chat.appendChild(wrapper);
        this.elements.chat.scrollTop = this.elements.chat.scrollHeight;
    }

    updateRequestCounter({remaining_requests, max_requests, seconds_until_reset}) {
        if (!this.elements.remainingRequests) return;

        const percentage = (remaining_requests / max_requests) * 100;
        const colorClass = this.getStatusColorClass(percentage);

        this.elements.remainingRequests.innerHTML = this.createCounterHTML(
            remaining_requests,
            max_requests,
            seconds_until_reset,
            colorClass
        );
    }

    getStatusColorClass(percentage) {
        if (percentage === 0) return 'text-red-600 dark:text-red-400 font-bold';
        if (percentage <= 20) return 'text-orange-600 dark:text-orange-400 font-medium';
        if (percentage <= 50) return 'text-yellow-600 dark:text-yellow-400';
        return 'text-green-600 dark:text-green-400';
    }

    createCounterHTML(remaining, max, resetTime, colorClass) {
        let html = `
            <span class="font-medium">Requests remaining:</span> 
            <span class="${colorClass}">${remaining}/${max}</span>`;

        if (remaining < max * 0.2 || remaining === 0) {
            const resetMinutes = Math.ceil(resetTime / 60);
            html += `
                <span class="block text-sm mt-1 text-gray-600 dark:text-gray-400">
                    Resets in ${resetMinutes} minute${resetMinutes !== 1 ? 's' : ''}
                </span>`;
        }

        return html;
    }

    async loadChatHistory() {
        try {
            const {data} = await axios.get(this.config.routes.loadChat);
            if (!data.chat_history?.length) return;

            // Pre-calculate view height once
            const viewHeight = this.elements.chat.clientHeight;
            const batchSize = Math.ceil(viewHeight / 100) * 2; // Approximate messages visible plus buffer

            // Create fragment for batch operations
            const fragment = document.createDocumentFragment();
            let pendingScrollUpdate = false;

            const renderBatch = async (messages, startIndex) => {
                const endIndex = Math.min(startIndex + batchSize, messages.length);
                const batch = messages.slice(startIndex, endIndex);

                batch.forEach(message => {
                    const isUser = message.user_id === this.config.userId;
                    const wrapper = document.createElement('div');
                    wrapper.className = `flex flex-col ${isUser ? 'items-end' : 'items-start'} space-y-2`;

                    wrapper.innerHTML = this.config.messageTemplate[isUser ? 'user' : 'assistant']({
                        content: isUser ? message.input : message.output,
                        time: new Date(message.created_at).toLocaleString(),
                        isHtml: message.output?.trim().startsWith('<') && message.output?.trim().endsWith('>')
                    });

                    fragment.appendChild(wrapper);
                });

                if (!pendingScrollUpdate) {
                    pendingScrollUpdate = true;
                    requestAnimationFrame(() => {
                        this.elements.chat.appendChild(fragment);
                        this.elements.chat.scrollTop = this.elements.chat.scrollHeight;
                        pendingScrollUpdate = false;
                    });
                }

                if (endIndex < messages.length) {
                    await new Promise(resolve => setTimeout(resolve, 0)); // Yield to main thread
                    await renderBatch(messages, endIndex);
                }
            };

            // Start rendering from the first batch
            await renderBatch(data.chat_history, 0);

        } catch (error) {
            console.error('Error loading chat history:', error);
        }
    }

    // Other utility methods remain the same but are simplified
    escapeHtml = text => {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    };

    initializeRealtimeUpdates() {
        Echo.private(`chat.${this.config.userId}`).listen('MessageSent', event => {
            this.renderMessage({
                type: event.user_id === this.config.userId ? 'user' : 'assistant',
                content: event.user_id === this.config.userId ? event.input : event.output,
                time: new Date(event.created_at).toLocaleString()
            });
        });
    }
}

export default ChatManager;