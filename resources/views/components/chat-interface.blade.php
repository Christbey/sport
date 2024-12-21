<div class="w-full md:w-3/4 md:pr-4 h-full md:h-dvh">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg flex flex-col h-full">
        <!-- Header -->
        <div class="p-4 flex flex-col md:flex-row justify-between items-center border-b border-gray-200 dark:border-gray-700">
            <h1 class="text-xl font-semibold text-gray-900 dark:text-white">PickPal AI Chat Assistant</h1>

            <div class="flex items-center gap-4 mt-2 md:mt-0">
                <div id="remaining-requests" class="text-sm">
                    <span class="font-medium">Requests remaining:</span>
                    <span class="text-green-600 dark:text-green-400">{{ $remainingRequests }}/{{ $maxRequests }}</span>
                </div>

                <button id="clearChatBtn"
                        class="inline-flex items-center gap-2 px-3 py-1.5 text-sm bg-red-600 hover:bg-red-700 text-white rounded-md">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Clear Chat
                </button>
            </div>
        </div>

        <!-- Messages -->
        <div id="chat-messages" class="flex-1 overflow-y-auto p-4 space-y-4">
            <div id="empty-state" class="flex items-center justify-center h-1/2">
                <div class="text-center p-6 bg-gray-50 dark:bg-gray-700 rounded-lg max-w-md">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">Welcome to PickPal AI Chat
                        Assistant</h2>
                    <p class="text-gray-600 dark:text-gray-300">Start a conversation by typing your message below. Our
                        AI assistant is here to help answer your questions.</p>
                </div>
            </div>
        </div>

        <!-- Input -->
        <form id="chat-form" method="POST" action="{{ route('ask-chatgpt') }}"
              class="p-4 border-t border-gray-200 dark:border-gray-700">
            @csrf
            <div class="relative">
                <input
                        type="text"
                        name="question"
                        id="question"
                        placeholder="Type your message..."
                        class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-gray-600 rounded-lg"
                        required
                        autofocus
                >
                <button type="submit"
                        class="absolute right-2 top-1/2 -translate-y-1/2 p-2 text-blue-600 dark:text-blue-400 hover:text-blue-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M13 5l7 7-7 7M5 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>
        </form>
    </div>
</div>