<x-app-layout class=" bg-gray-50 dark:bg-gray-900">
    <main class="container mx-auto px-4 h-screen md:h-[550px] flex flex-col overflow-hidden max-w-5xl">
        <!-- Chat Container -->
        <div class="flex-1 bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden flex flex-col my-3 md:max-w-2xl lg:max-w-3xl mx-auto w-full">

            <!-- Chat Header -->
            <div class="border-b border-gray-200 dark:border-gray-700 p-4 flex justify-between items-center bg-white dark:bg-gray-800">
                <h1 class="text-lg font-semibold text-gray-900 dark:text-white">PickPal AI Chat Assistant</h1>
                <button
                        id="clearChatBtn"
                        class="inline-flex items-center px-3 py-1.5 text-sm bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors duration-200"
                        aria-label="Clear chat history"
                >
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Clear Chat
                </button>
            </div>

            <!-- Chat Messages Container -->
            <div
                    class="flex-1 overflow-y-auto p-4 space-y-4 min-h-0"
                    id="chat-messages"
                    aria-live="polite"
                    aria-atomic="true"
            >
                <!-- Messages will be dynamically inserted here -->
            </div>

            <!-- Chat Input Form -->
            <div class="border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
                <form id="chat-form" method="POST" action="{{ route('ask-chatgpt') }}" class="max-w-4xl mx-auto">
                    @csrf
                    <div class="relative flex items-center">
                        <input
                                type="text"
                                name="question"
                                id="question"
                                placeholder="Type your message..."
                                class="w-full px-4 py-3 text-gray-700 dark:text-gray-200 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 ease-in-out"
                                required
                                autofocus
                                aria-label="Type your message"
                        >
                        <button
                                type="submit"
                                class="absolute right-2 p-2 text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 transition-colors duration-200"
                                aria-label="Send message"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M13 5l7 7-7 7M5 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <!-- Modal Container -->
    <div
            id="modalContainer"
            class="relative z-50 hidden"
            aria-labelledby="modal-title"
            role="dialog"
            aria-modal="true"
    >
        <!-- Background backdrop -->
        <div
                id="modalBackdrop"
                class="fixed inset-0 bg-black/50"
        ></div>

        <!-- Modal panel -->
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-full items-end sm:items-center justify-center p-0 text-center ">
                <div class="relative transform overflow-hidden rounded-t-xl sm:rounded-lg bg-white dark:bg-gray-800 w-full sm:max-w-lg text-left shadow-xl transition-all">
                    <div class="p-6">
                        <h3 id="modal-title" class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                            Clear Chat History
                        </h3>
                        <p class="text-gray-700 dark:text-gray-300 mb-6">
                            Are you sure you want to clear all chat messages? This action cannot be undone.
                        </p>
                        <div class="flex justify-end space-x-4">
                            <button
                                    id="cancelClear"
                                    type="button"
                                    class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200"
                            >
                                Cancel
                            </button>
                            <button
                                    id="confirmClear"
                                    type="button"
                                    class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors duration-200"
                            >
                                Clear History
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Styles for markdown and code formatting -->
    <style>
        .prose pre {
            @apply bg-slate-800 text-slate-200 p-4 rounded-lg overflow-x-auto;
        }

        .prose code {
            @apply bg-slate-800/50 text-slate-200 px-1.5 py-0.5 rounded text-sm;
        }

        .prose pre code {
            @apply bg-transparent p-0;
        }

        .dark .prose {
            @apply text-slate-200;
        }

        .dark .prose pre {
            @apply bg-slate-900;
        }

        .dark .prose code {
            @apply bg-slate-900/50;
        }

        .dark .prose a {
            @apply text-blue-400;
        }

        .message-table {
            @apply w-full border-collapse;
        }

        .message-table th,
        .message-table td {
            @apply p-3 border border-gray-200 dark:border-gray-700;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const chatConfig = {
                userId: {{ auth()->id() ?? 'null' }},
                routes: {
                    loadChat: '{{ route("load-chat") }}',
                    clearConversations: '{{ route("clear-conversations") }}'
                },
                csrfToken: document.querySelector('meta[name="csrf-token"]').content
            };

            const chatManager = new ChatManager(chatConfig);
        });
    </script>
</x-app-layout>