@props([
    'userId',
    'maxRequests',
    'remainingRequests'
])



@push('scripts')
    <script>
        window.chatConfig = {
            userId: {{ $userId ?? 'null' }},
            routes: {
                loadChat: '{{ route("load-chat") }}',
                clearConversations: '{{ route("clear-conversations") }}'
            },
            csrfToken: document.querySelector('meta[name="csrf-token"]').content,
            maxRequests: {{ $maxRequests }},
            remainingRequests: {{ $remainingRequests }}
        };
    </script>
    @vite('resources/js/app.js')
@endpush