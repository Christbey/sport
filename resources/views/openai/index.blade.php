<x-app-layout class="overflow-hidden">
    <div class="flex-1 overflow-y-auto p-4 space-y-3" id="chat-messages">
        @foreach($conversations as $conversation)
            <div class="flex flex-col space-y-2">
                <div class="self-end bg-blue-100 dark:bg-blue-900 rounded-xl p-3 max-w-[80%]">
                    {{ $conversation->input }}
                    <span class="block text-xs text-gray-500 mt-1 text-right">
                        {{ $conversation->created_at->diffForHumans() }}
                    </span>
                </div>
                @if($conversation->output)
                    <div class="self-start bg-gray-100 dark:bg-gray-800 rounded-xl p-3 max-w-[80%]">
                        {!! $conversation->output !!}
                    </div>
                @endif
            </div>
        @endforeach
    </div>


    <div class="border-t border-gray-200 dark:border-gray-600 p-3 bg-white dark:bg-gray-700">
        <form id="chat-form" method="POST" class="space-y-0">
            @csrf
            <div class="relative">
                <input
                        type="text"
                        name="question"
                        id="question"
                        placeholder="Type your message..."
                        class="w-full p-3 pr-12 text-sm border-2 border-blue-200 dark:border-blue-800 dark:bg-gray-600 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-300"
                        required
                        autofocus
                >
                <button
                        type="submit"
                        class="absolute right-1 top-1/2 -translate-y-1/2 bg-blue-600 text-white p-2 rounded-full hover:bg-blue-700 dark:bg-blue-800 dark:hover:bg-blue-700 transition"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M13 5l7 7-7 7M5 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const chatMessages = document.getElementById('chat-messages');

            const eventSource = new EventSource('{{ route('stream-conversations-sse') }}');

            eventSource.onmessage = function (event) {
                const conversations = JSON.parse(event.data);

                // Clear the chat messages
                chatMessages.innerHTML = '';

                conversations.forEach(conversation => {
                    const messageHtml = `
                <div class="flex flex-col ${conversation.user_id === {{ auth()->id() }} ? 'items-end' : 'items-start'}">
                    <div class="self-end bg-blue-100 dark:bg-blue-900 rounded-xl p-3 max-w-[80%]">
                        ${conversation.input}
                        <span class="block text-xs text-gray-500 mt-1 text-right">
                            ${new Date(conversation.created_at).toLocaleString()}
                        </span>
                    </div>
                    <div class="self-start bg-gray-100 dark:bg-gray-800 rounded-xl p-3 max-w-[80%]">
                        ${conversation.output ?? 'Waiting for response...'}
                    </div>
                </div>`;
                    chatMessages.insertAdjacentHTML('beforeend', messageHtml);
                });

                chatMessages.scrollTop = chatMessages.scrollHeight;
            };

            eventSource.onerror = function (error) {
                console.error('SSE error:', error);
                eventSource.close();
            };
        });
        document.addEventListener('DOMContentLoaded', function () {
            const chatMessages = document.getElementById('chat-messages');
            const chatForm = document.getElementById('chat-form');
            const questionInput = document.getElementById('question');

            // Function to fetch conversations
            async function fetchConversations() {
                try {
                    const response = await fetch('{{ route('stream-conversations') }}');
                    const data = await response.json();

                    if (response.ok) {
                        // Clear existing messages
                        chatMessages.innerHTML = '';

                        // Append each conversation
                        data.conversations.forEach(conversation => {
                            const messageHtml = `
                                <div class="flex flex-col ${conversation.user_id === {{ auth()->id() }} ? 'items-end' : 'items-start'}">
                                    <div class="self-end bg-blue-100 dark:bg-blue-900 rounded-xl p-3 max-w-[80%]">
                                        ${conversation.input}
                                        <span class="block text-xs text-gray-500 mt-1 text-right">
                                            ${new Date(conversation.created_at).toLocaleString()}
                                        </span>
                                    </div>
                                    <div class="self-start bg-gray-100 dark:bg-gray-800 rounded-xl p-3 max-w-[80%]">
                                        ${conversation.output ?? 'Waiting for response...'}
                                    </div>
                                </div>`;
                            chatMessages.insertAdjacentHTML('beforeend', messageHtml);
                        });

                        // Scroll to the bottom of the chat
                        chatMessages.scrollTop = chatMessages.scrollHeight;
                    } else {
                        console.error('Failed to fetch conversations:', data.message);
                    }
                } catch (error) {
                    console.error('Error fetching conversations:', error);
                }
            }

            // Long Polling to fetch conversations every 5 seconds
            setInterval(fetchConversations, 5000);

            // Handle form submission
            chatForm.addEventListener('submit', async function (e) {
                e.preventDefault();

                const question = questionInput.value.trim();
                if (!question) return;

                questionInput.value = ''; // Clear input

                // Show a temporary "loading" message
                const loadingHtml = `
                    <div class="flex flex-col items-end">
                        <div class="self-end bg-blue-100 dark:bg-blue-900 rounded-xl p-3 max-w-[80%]">
                            ${question}
                            <span class="block text-xs text-gray-500 mt-1 text-right">Processing...</span>
                        </div>
                    </div>`;
                chatMessages.insertAdjacentHTML('beforeend', loadingHtml);
                chatMessages.scrollTop = chatMessages.scrollHeight;

                try {
                    const response = await fetch('{{ route('ask-chatgpt') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body: JSON.stringify({question}),
                    });

                    const data = await response.json();

                    if (response.ok) {
                        fetchConversations(); // Fetch latest conversations
                    } else {
                        console.error('Error submitting question:', data.message);
                        alert('An error occurred while submitting your message.');
                    }
                } catch (error) {
                    console.error('Error submitting question:', error);
                }
            });

            // Initial fetch of conversations
            fetchConversations();
        });
    </script>
</x-app-layout>