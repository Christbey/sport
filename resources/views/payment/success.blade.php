<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="text-center">
                        <svg class="mx-auto h-12 w-12 text-green-500" fill="none" stroke="currentColor"
                             viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M5 13l4 4L19 7"></path>
                        </svg>

                        <h2 class="mt-4 text-2xl font-bold text-gray-900">Payment Successful!</h2>
                        <p class="mt-2 text-gray-600">Your payment has been processed successfully.</p>

                        @if($subscription)
                            <div class="mt-4">
                                <p class="text-gray-600">Subscription
                                    Status: {{ ucfirst($subscription->stripe_status) }}</p>
                                @if($subscription->onTrial())
                                    <p class="text-gray-600">Trial
                                        ends: {{ $subscription->trial_ends_at->format('M d, Y') }}</p>
                                @endif
                            </div>
                        @endif

                        <div class="mt-6">
                            <a href="{{ $redirect }}"
                               class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                Return to Subscription Management
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>