<x-app-layout>
    <div class="container mx-auto px-4 py-8 flex-grow">
        <div class="max-w-2xl mx-auto bg-white shadow-md rounded-lg overflow-hidden">
            <div class="bg-blue-500 text-white p-4 flex justify-between items-center">
                <h1 class="text-2xl font-bold">AI Chat Assistant</h1>
                <button id="clear-chat"
                        class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600 transition-colors">
                    Clear Chat
                </button>
            </div>

            <!-- Chat container -->
            <div id="chat-container" class="p-4 space-y-4 h-[500px] overflow-y-auto">
                @foreach ($conversations as $conversation)
                    <div class="bg-gray-100 p-3 rounded-lg self-end max-w-3/4 mb-2">
                        <strong>You:</strong> {{ $conversation->input }}
                    </div>
                    <div class="bg-blue-100 p-3 rounded-lg self-start max-w-3/4 prose break-words overflow-hidden mb-2">
                        <strong>AI:</strong> {!! $conversation->output !!}
                    </div>
                @endforeach
            </div>

            <form id="chat-form" class="border-t p-4 flex">
                <input
                        type="text"
                        id="question-input"
                        name="question"
                        placeholder="Ask me anything..."
                        required
                        class="flex-grow p-2 border rounded-l-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                <button
                        type="submit"
                        class="bg-blue-500 text-white px-4 py-2 rounded-r-lg hover:bg-blue-600 transition-colors"
                >
                    Send
                </button>
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
    </style>
</x-app-layout>