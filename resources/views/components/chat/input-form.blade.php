@props(['remainingRequests'])

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