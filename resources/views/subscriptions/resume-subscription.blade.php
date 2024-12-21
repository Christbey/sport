{{-- resume-subscription.blade.php --}}
<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-2xl font-bold mb-6">Resume Subscription</h2>

                    @if(session('error'))
                        <div class="bg-red-100 dark:bg-red-800 border border-red-400 text-red-700 dark:text-red-300 px-4 py-3 rounded relative mb-4"
                             role="alert">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if($subscription && $subscription->canceled() && $subscription->onGracePeriod())
                        <div class="bg-yellow-100 dark:bg-yellow-800 border border-yellow-400 text-yellow-700 dark:text-yellow-300 px-4 py-3 rounded relative mb-6">
                            <p>Your subscription will end on {{ $subscription->ends_at->format('M d, Y') }}.</p>
                            <p class="mt-2">Resuming now will reactivate your subscription and continue billing on your
                                regular cycle.</p>
                        </div>

                        <form action="{{ route('subscription.resume') }}" method="POST" class="space-y-6">
                            @csrf

                            <div>
                                <label for="billing_cycle_anchor"
                                       class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Billing Cycle
                                </label>
                                <select name="billing_cycle_anchor" id="billing_cycle_anchor"
                                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="unchanged">Continue Previous Cycle</option>
                                    <option value="now">Start New Cycle Now</option>
                                </select>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    Choose whether to continue your previous billing cycle or start a new one.
                                </p>
                            </div>

                            <div>
                                <label for="proration_behavior"
                                       class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Proration Handling
                                </label>
                                <select name="proration_behavior" id="proration_behavior"
                                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="create_prorations">Prorate Charges</option>
                                    <option value="none">No Proration</option>
                                </select>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    Choose how to handle partial period charges.
                                </p>
                            </div>

                            @if($subscription && $subscription->canceled() && $subscription->onGracePeriod())
                                <a href="{{ route('subscription.resume.show') }}"
                                   class="inline-block bg-yellow-500 text-white px-4 py-2 rounded-lg hover:bg-yellow-600">
                                    Resume Subscription
                                </a>
                            @endif
                        </form>
                    @else
                        <div class="bg-red-100 dark:bg-red-800 border border-red-400 text-red-700 dark:text-red-300 px-4 py-3 rounded relative">
                            <p>No canceled subscription eligible for resumption was found.</p>
                            <p class="mt-2">Please check your subscription status in the dashboard.</p>
                        </div>

                        <div class="mt-6">
                            <a href="{{ route('subscription.manage') }}"
                               class="inline-block bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600">
                                Return to Dashboard
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
