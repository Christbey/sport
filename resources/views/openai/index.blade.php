<!-- resources/views/openai/index.blade.php -->

<x-app-layout>
    @if (session('warning'))
        <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-4" role="alert">
            {{ session('warning') }}
        </div>
    @endif

    <!-- Main Container with Two Columns -->
    <div class="flex flex-col md:flex-row my-4 max-w-7xl mx-auto w-full px-4 min-h-screen md:h-1/2">
        <!-- Left Column: Chat Interface -->
        <div class="md:w-3/4 w-full md:pr-4 flex flex-col md:h-3/4">
            <!-- Chat Container -->
            <div class="flex-1 bg-white dark:bg-gray-800 rounded-lg shadow-lg flex flex-col h-full md:h-1/2">
                <!-- Chat Header -->
                <div class="border-b border-gray-200 dark:border-gray-700 p-4 flex flex-col md:flex-row justify-between items-center bg-white dark:bg-gray-800">
                    <h1 class="text-lg md:text-xl font-semibold text-gray-900 dark:text-white">PickPal AI Chat
                        Assistant</h1>
                    <div class="flex items-center mt-2 md:mt-0">
                        <div id="remaining-requests" class="text-sm mr-4">
                            <span class="font-medium">Requests remaining:</span>
                            <span class="text-green-600 dark:text-green-400">
                                {{ $remainingRequests }}/{{ $maxRequests }}
                            </span>
                        </div>
                        <button
                                id="clearChatBtn"
                                class="inline-flex items-center px-3 py-1.5 text-sm bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors duration-200"
                                aria-label="Clear chat history"
                        >
                            <!-- Clear Icon -->
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                </path>
                            </svg>
                            Clear Chat
                        </button>
                    </div>
                </div>

                <!-- Chat Messages Container -->
                <div id="chat-messages" class="flex-2 overflow-y-scroll p-4 space-y-4" aria-live="polite"
                     aria-atomic="true">
                    <div id="empty-state" class="flex items-center justify-center h-1/2">
                        <div class="text-center p-6 rounded-lg bg-gray-50 dark:bg-gray-700 max-w-md mx-auto">
                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">Welcome to PickPal AI
                                Chat Assistant</h2>
                            <p class="text-gray-600 dark:text-gray-300">
                                Start a conversation by typing your message below. Our AI assistant is here to help
                                answer your questions and provide assistance.
                            </p>
                        </div>
                    </div>
                    <!-- Messages will be dynamically inserted here -->
                </div>

                <!-- Chat Input Form -->
                <div class="border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
                    <form id="chat-form" method="POST" action="{{ route('ask-chatgpt') }}" class="w-full">
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
                                <!-- Send Icon -->
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M13 5l7 7-7 7M5 5l7 7-7 7"/>
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Right Column: Sidebar -->
        <div class="md:w-1/4 w-full md:pl-4 flex flex-col mt-4 md:mt-0">
            <!-- Sidebar Container -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-4 flex flex-col">
                <!-- User Profile Section -->
                <div class="flex items-center mb-6">
                    <img src="{{ auth()->user()->profile_photo_url }}" alt="{{ auth()->user()->name }}"
                         class="w-12 h-12 rounded-full mr-4 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ auth()->user()->name }}</h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ auth()->user()->email }}</p>
                    </div>
                </div>

                <!-- Suggested Questions Section -->
                <div class="mt-6">
                    <h3 class="text-md font-medium text-gray-900 dark:text-white mb-2">Suggested Questions</h3>
                    <ul class="space-y-2">
                        @foreach($suggestedQuestions as $question)
                            <li>
                                <button
                                        type="button"
                                        class="w-full text-left text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors duration-200 focus:outline-none"
                                        onclick="populateQuestion(@json($question))"
                                >
                                    {{ $question }}
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <!-- Additional Sidebar Content (e.g., Promotions) -->
                <div class="mt-6">
                    <h3 class="text-md font-medium text-gray-900 dark:text-white mb-2">Upgrade Your Plan</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">
                        Unlock more features and higher limits by upgrading your subscription.
                    </p>
                    <a href="{{ route('subscription.index') }}"
                       class="w-full inline-flex justify-center items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors duration-200">
                        Upgrade Now
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Container -->
    <div
            id="modalContainer"
            class="fixed inset-0 z-50 hidden"
            aria-labelledby="modal-title"
            role="dialog"
            aria-modal="true"
    >
        <!-- Background Backdrop -->
        <div
                id="modalBackdrop"
                class="fixed inset-0 bg-black/50"
        ></div>

        <!-- Modal Panel -->
        <div class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
            <div class="relative transform overflow-hidden rounded-xl bg-white dark:bg-gray-800 w-full sm:max-w-lg text-left shadow-xl transition-all">
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

    <!-- Styles for Markdown and Code Formatting -->
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
        window.userId = {{ auth()->id() }};
    </script>
    <!-- Initialize ChatManager -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const chatConfig = {
                userId: {{ auth()->id() ?? 'null' }},
                routes: {
                    loadChat: '{{ route("load-chat") }}',
                    clearConversations: '{{ route("clear-conversations") }}'
                },
                csrfToken: document.querySelector('meta[name="csrf-token"]').content,
                maxRequests: {{ $maxRequests }},
                remainingRequests: {{ $remainingRequests }}
            };

            const chatManager = new ChatManager(chatConfig);
        });

        /**
         * Populates the chat input with the selected suggested question.
         * @param {string} question - The question to populate in the chat input.
         */
        function populateQuestion(question) {
            const input = document.getElementById('question');
            input.value = question;
            input.focus();
        }
    </script>
</x-app-layout>
