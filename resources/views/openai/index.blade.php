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
            const chatForm = document.getElementById('chat-form');
            const questionInput = document.getElementById('question');
            const chatMessages = document.getElementById('chat-messages');

            chatForm.addEventListener('submit', async function (e) {
                e.preventDefault();

                const question = questionInput.value.trim();
                if (!question) return;

                questionInput.value = ''; // Clear the input

                // Show loading indicator as a placeholder message
                const loadingPlaceholder = document.createElement('div');
                loadingPlaceholder.classList.add('flex', 'flex-col', 'space-y-2');
                loadingPlaceholder.innerHTML = `
                    <div class="self-end bg-blue-100 dark:bg-blue-900 rounded-xl p-3 max-w-[80%]">
                        ${question}
                        <span class="block text-xs text-gray-500 mt-1 text-right">Processing...</span>
                    </div>
                `;
                chatMessages.appendChild(loadingPlaceholder);
                chatMessages.scrollTop = chatMessages.scrollHeight; // Scroll to bottom

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
                        // Remove loading placeholder
                        chatMessages.removeChild(loadingPlaceholder);

                        // Append user message and AI response
                        const newMessage = document.createElement('div');
                        newMessage.classList.add('flex', 'flex-col', 'space-y-2');
                        newMessage.innerHTML = `
                            <div class="self-end bg-blue-100 dark:bg-blue-900 rounded-xl p-3 max-w-[80%]">
                                ${data.input}
                                <span class="block text-xs text-gray-500 mt-1 text-right">${data.timestamp}</span>
                            </div>
                            <div class="self-start bg-gray-100 dark:bg-gray-800 rounded-xl p-3 max-w-[80%]">
                                ${data.output}
                            </div>
                        `;
                        chatMessages.appendChild(newMessage);
                        chatMessages.scrollTop = chatMessages.scrollHeight; // Scroll to bottom
                    } else {
                        console.error('Error:', data.message);
                        alert('Something went wrong.');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Failed to send the message.');
                }
            });
        });
    </script>
</x-app-layout>