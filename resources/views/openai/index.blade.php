{{-- resources/views/openai/index.blade.php --}}
<x-app-layout>
    <div class="grid-cols-1 md:flex max-w-6xl mx-auto md:my-12">
        <x-chat-interface
                :remaining-requests="$remainingRequests"
                :max-requests="$maxRequests"
        />
        <x-chat-sidebar
                :suggested-questions="$suggestedQuestions"
        />
    </div>

    <x-chat-clear/>


    <script>
        window.chatConfig = {
            userId: {{ auth()->id() ?? 'null' }},
            routes: {
                loadChat: '{{ route("load-chat") }}',
                clearConversations: '{{ route("clear-conversations") }}'
            },
            csrfToken: document.querySelector('meta[name="csrf-token"]').content,
            maxRequests: {{ $maxRequests }},
            remainingRequests: {{ $remainingRequests }}
        };
    </script>
</x-app-layout>