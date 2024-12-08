<x-app-layout>
    <!-- Main container with gradient background -->
    <div class="min-h-screen bg-gradient-to-b from-gray-50 to-gray-100 py-8">
        <div class="container mx-auto px-4">
            <!-- Chat interface card -->
            <div class="max-w-2xl mx-auto bg-white rounded-xl shadow-lg overflow-hidden">
                <!-- Header -->
                <div class="bg-blue-600 p-6">
                    <h1 class="text-2xl font-bold text-white text-center flex items-center justify-center gap-2">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                        </svg>
                        Chat with GPT
                    </h1>
                </div>

                <!-- Chat form -->
                <div class="p-6" x-data="{ loading: false }">
                    <form @submit="loading = true" id="chat-form" method="POST" action="{{ route('ask-chatgpt') }}"
                          class="space-y-4">
                        @csrf
                        <div>
                            <label for="question" class="block text-sm font-medium text-gray-700 mb-2">Ask a
                                question:</label>
                            <input
                                    type="text"
                                    name="question"
                                    id="question"
                                    value="{{ old('question', request()->input('question', $question ?? '')) }}"
                                    {{-- Retain the user's last query --}}
                                    class="w-full p-3 border rounded-lg focus:outline-none focus:ring focus:border-blue-300"
                                    placeholder="What are the predictions for week 14?"
                                    required
                                    autofocus>
                        </div>


                        <button type="submit"
                                class="w-full flex items-center justify-center px-4 py-3 border border-transparent
                                       text-base font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700
                                       focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500
                                       transition duration-150 ease-in-out disabled:opacity-50"
                                :disabled="loading">
                            <span x-show="!loading">Send Message</span>
                            <div x-show="loading" class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                          d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                Processing...
                            </div>
                        </button>
                    </form>
                </div>

                <!-- Response section -->
                @if (isset($error))
                    <div class="border-t border-gray-200">
                        <div class="p-6 space-y-4">
                            <div class="flex items-center gap-2 text-red-600">
                                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor"
                                     viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M18.364 5.636l-6.364 6.364m0 0l-6.364-6.364m6.364 6.364v12"></path>
                                </svg>
                                <h4 class="text-lg font-semibold">Error:</h4>
                            </div>
                            <div class="prose prose-red max-w-none bg-red-50 rounded-lg p-4 shadow-inner">
                                {!! $error !!}
                            </div>
                        </div>
                    </div>
                @elseif (isset($response))
                    <div class="border-t border-gray-200">
                        <div class="p-6 space-y-4">
                            <div class="flex items-center gap-2 text-gray-800">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor"
                                     viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <h4 class="text-lg font-semibold">Response:</h4>
                            </div>
                            <div class="prose prose-blue max-w-none bg-gray-50 rounded-lg p-4 shadow-inner">
                                {!! $response !!}
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Flowbite JS -->
</x-app-layout>
