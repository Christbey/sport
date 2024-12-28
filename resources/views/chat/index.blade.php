<x-app-layout>
    <div class="container mx-auto px-4 py-8 flex-grow">
        <div class="max-w-3xl mx-auto bg-white shadow-md rounded-lg overflow-hidden">
            <!-- Header Section -->
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 text-white p-4 flex justify-between items-center">
                <h1 class="text-2xl font-bold">AI Chat Assistant</h1>
                <button id="clear-chat"
                        class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600 transition-colors">
                    Clear Chat
                </button>
            </div>

            <!-- Rate Limit Information -->
            <div class="p-4 border-b bg-gray-50">
                <div class="flex justify-between items-center">
                    <div class="text-gray-700">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="font-semibold">Weekly Requests:</span>
                            <div class="flex items-center">
                                <span class="text-blue-500 font-bold text-lg">{{ $remainingRequests }}</span>
                                <span class="text-gray-400 mx-1">/</span>
                                <span class="text-gray-500">{{ $userLimit }}</span>
                            </div>
                        </div>
                        @if($remainingRequests < $userLimit)
                            <p class="text-sm text-gray-600">
                                Resets in:
                                @if($daysUntilReset > 0)
                                    <span class="font-semibold text-blue-600">{{ $daysUntilReset }}</span>
                                    {{ Str::plural('day', $daysUntilReset) }}
                                @endif
                                @if($hoursUntilReset > 0)
                                    @if($daysUntilReset > 0)
                                        and
                                    @endif
                                    <span class="font-semibold text-blue-600">{{ $hoursUntilReset }}</span>
                                    {{ Str::plural('hour', $hoursUntilReset) }}
                                @endif
                            </p>
                        @endif
                    </div>
                    @if($remainingRequests === 0)
                        <a href="{{ route('subscription.index') }}"
                           class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition-all text-sm">
                            Upgrade to Pro
                        </a>
                    @endif
                </div>
            </div>

            <!-- Chat Container -->
            <div id="chat-container" class="p-4 space-y-4 h-[500px] overflow-y-auto overflow-x-scroll bg-gray-50">
                @forelse ($conversations as $conversation)
                    <!-- User Message -->
                    <div class="bg-gray-100 p-3 rounded-lg self-end max-w-[75%] mb-2 shadow-sm">
                        <div class="flex items-center gap-2 mb-1">
                            <div class="w-6 h-6 rounded-full bg-gray-200 flex items-center justify-center">
                                <svg class="w-4 h-4 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"></path>
                                </svg>
                            </div>
                            <span class="font-medium text-gray-700">You</span>
                        </div>
                        <p class="text-gray-800 pl-8">{{ $conversation->input }}</p>
                    </div>

                    <!-- AI Response -->
                    <div class="bg-blue-50 p-3 rounded-lg self-start max-w-[75%] shadow-sm">
                        <div class="flex items-center gap-2 mb-1">
                            <div class="w-6 h-6 rounded-full bg-blue-200 flex items-center justify-center">
                                <svg class="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M5.5 13a3.5 3.5 0 01-.369-6.98 4 4 0 117.753-1.977A4.5 4.5 0 1113.5 13H11V9.413l1.293 1.293a1 1 0 001.414-1.414l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13H5.5z"></path>
                                </svg>
                            </div>
                            <span class="font-medium text-blue-700">AI Assistant</span>
                        </div>
                        <div class="prose prose-sm max-w-none pl-8 text-gray-700">
                            {!! $conversation->output !!}
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8 text-gray-500">
                        <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor"
                             viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                        </svg>
                        <p class="text-lg font-medium">No conversations yet</p>
                        <p class="text-sm">Start chatting with the AI assistant below!</p>
                    </div>
                @endforelse

                <!-- Loading Indicator -->
                <div id="loading" class="hidden flex justify-center py-4">
                    <div class="loading-spinner"></div>
                </div>
            </div>

            <!-- Chat Form -->
            <form id="chat-form" method="POST" class="border-t p-4 bg-white">
                <div class="flex items-center gap-2">
                    <input
                            type="text"
                            id="question-input"
                            name="question"
                            placeholder="Ask me anything..."
                            required
                            class="flex-grow p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-50"
                            @if($remainingRequests === 0) disabled @endif
                    >
                    <button
                            type="submit"
                            class="bg-blue-500 text-white px-6 py-3 rounded-lg hover:bg-blue-600 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                            @if($remainingRequests === 0) disabled @endif
                    >
                        <span class="flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                            </svg>
                            Send
                        </span>
                    </button>
                </div>
                @if($remainingRequests === 0)
                    <p class="text-sm text-red-500 mt-2">
                        You've reached your weekly limit. Please wait for the reset or upgrade to Pro.
                    </p>
                @endif
            </form>
        </div>
    </div>

    <style>
        .loading-spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }

        .prose pre {
            background-color: #f8fafc;
            border-radius: 0.5rem;
            padding: 1rem;
            margin: 1rem 0;
            overflow-x: auto;
        }

        .prose code {
            background-color: #f1f5f9;
            padding: 0.2em 0.4em;
            border-radius: 0.25rem;
            font-size: 0.875em;
        }
    </style>
</x-app-layout>