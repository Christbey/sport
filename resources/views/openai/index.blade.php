<x-app-layout>

    <div class="container mx-auto p-6 mt-10 max-w-xl bg-white shadow-md rounded-lg">
        <h1 class="text-2xl font-bold text-center mb-6">Chat with GPT</h1>

        <div x-data="{ loading: false }">
            <form @submit="loading = true" id="chat-form" method="POST" action="{{ route('ask-chatgpt') }}"
                  class="space-y-4">            @csrf
                <div>
                    <label for="question" class="block text-sm font-medium text-gray-700 mb-2">Ask a question:</label>
                    <input type="text" name="question" id="question"
                           class="w-full p-3 border rounded-lg focus:outline-none focus:ring focus:border-blue-300"
                           placeholder="What are the predictions for week 14?" required autofocus>

                </div>
                <button type="submit"
                        class="w-full py-3 px-4 bg-blue-600 text-white rounded-lg hover:bg-blue-500 transition"
                        :disabled="loading">
                    <span x-show="!loading">Submit</span>
                    <span x-show="loading">Loading...</span>
                </button>
            </form>
        </div>

        @if (isset($response))
            <div class="mt-8 p-4 bg-blue-100 rounded-lg">
                <h4 class="text-lg font-semibold mb-2">Response:</h4>
                <p class="text-gray-800">{{ $response }}</p>
            </div>
        @endif
    </div>

</x-app-layout>
