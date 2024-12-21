<div class="md:w-1/4 w-full md:pl-4 flex flex-col mt-4 md:mt-0">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-4 flex flex-col">
        <div class="flex items-center mb-6">
            <img
                    src="{{ auth()->user()->profile_photo_url }}"
                    alt="{{ auth()->user()->name }}"
                    class="w-12 h-12 rounded-full mr-4"
            >
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ auth()->user()->name }}</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ auth()->user()->email }}</p>
            </div>
        </div>

        <div class="mt-6">
            <h3 class="text-md font-medium text-gray-900 dark:text-white mb-2">Suggested Questions</h3>
            <ul class="space-y-2">
                @foreach($suggestedQuestions as $question)
                    <li>
                        <button
                                type="button"
                                class="w-full text-left px-3 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md transition-colors duration-200"
                                onclick="populateQuestion(`{{ $question }}`)"
                        >
                            {{ $question }}
                        </button>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="mt-auto pt-6">
            <h3 class="text-md font-medium text-gray-900 dark:text-white mb-2">Upgrade Your Plan</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-4">
                Unlock more features and higher limits by upgrading your subscription.
            </p>
            <a
                    href="{{ route('subscription.index') }}"
                    class="block w-full px-4 py-2 bg-blue-600 text-white text-center rounded-md hover:bg-blue-700 transition-colors duration-200"
            >
                Upgrade Now
            </a>
        </div>
    </div>
</div>

<script>
    window.populateQuestion = function (question) {
        const input = document.getElementById('question');
        if (input) {
            input.value = question;
            input.focus();
            // Optional: Scroll input into view
            input.scrollIntoView({behavior: 'smooth', block: 'center'});
        }
    };
</script>