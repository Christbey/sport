<section class="flex items-center justify-center p-6">
    <div class="max-w-screen-xl text-center">
        <h1 class="mb-4 text-3xl font-bold tracking-tight leading-none text-gray-900 sm:text-4xl md:text-5xl">
            Welcome to Picksports!
        </h1>
        <p class="mb-6 text-base text-gray-500 sm:text-lg md:text-xl max-w-3xl mx-auto">
            Elevate your sports experience with Picksports. Whether you're joining the fun or competing against
            your colleagues, Picksports offers an exciting way to enjoy every game.
        </p>
        <div class="flex flex-col sm:flex-row justify-center gap-4">
            <a href="{{ route('pickem.schedule') }}"
               class="inline-flex items-center justify-center py-2.5 px-5 text-base font-medium text-gray-800 bg-primary-700 rounded-lg hover:bg-primary-800 focus:ring-4 focus:ring-primary-300">
                Submit Picks
                <svg class="ml-2 w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                          d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z"
                          clip-rule="evenodd"></path>
                </svg>
            </a>
            <a href="{{ route('pickem.leaderboard') }}"
               class="inline-flex items-center justify-center py-2.5 px-5 text-base font-medium text-gray-900 bg-white rounded-lg border border-gray-300 hover:bg-gray-100 focus:ring-4 focus:ring-gray-100">
                <svg class="w-5 h-5 text-gray-800 mr-2" fill="none" stroke="currentColor" stroke-width="2"
                     viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M3 15v4m6-6v6m6-4v4m6-6v6M3 11l6-5 6 5 5.5-5.5"></path>
                </svg>
                Leaderboard
            </a>
            <a href="{{ route('billing.portal') }}" class="text-gray-600 hover:text-gray-900">
                Manage Subscription
            </a>
        </div>
    </div>
</section>