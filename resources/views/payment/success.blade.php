<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                    <div class="text-center">
                        <svg class="mx-auto h-12 w-12 text-green-500" fill="none" stroke="currentColor"
                             viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M5 13l4 4L19 7"></path>
                        </svg>

                        <h2 class="mt-4 text-2xl font-bold text-gray-900 dark:text-white">
                            {{ $subscription ? 'Payment Successful!' : 'Payment Added Successfully!' }}
                        </h2>
                        <p class="mt-2 text-gray-600 dark:text-gray-400">
                            {{ $subscription ? 'Your subscription details are updated.' : 'Your payment method has been added successfully.' }}
                        </p>

                        @if($subscription)
                            <div class="mt-4">
                                <p class="text-gray-600 dark:text-gray-400">
                                    Subscription Status: {{ ucfirst($subscription->stripe_status) }}
                                </p>
                                @if($subscription->onTrial())
                                    <p class="text-gray-600 dark:text-gray-400">
                                        Trial ends: {{ $subscription->trial_ends_at->format('M d, Y') }}
                                    </p>
                                @endif
                            </div>
                        @endif

                        <div class="mt-6">
                            <a href="{{ $redirect }}"
                               class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600">
                                {{ $subscription ? 'Return to Subscription Management' : 'Manage Your Account' }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
