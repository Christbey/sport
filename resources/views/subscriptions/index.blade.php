<x-app-layout>
    <div class="relative p-8 lg:p-12">
        <!-- Section Header -->
        <div class="text-center max-w-2xl mx-auto mb-12">
            <h2 class="text-3xl sm:text-4xl font-extrabold text-gray-900 dark:text-white mb-4">
                Available Plans
            </h2>
            <div class="h-1 w-24 bg-blue-600 mx-auto mb-4 rounded-full"></div>
            <p class="text-lg text-gray-600 dark:text-gray-400">
                Choose the perfect plan for your needs
            </p>
        </div>

        <!-- Plans Grid -->
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach($plans as $plan)
                <div class="relative group">
                    <!-- Card -->
                    <div class="h-full flex flex-col bg-white dark:bg-gray-800 rounded-2xl shadow-lg transition-all duration-200
                        {{ $subscription && $subscription->stripe_price === $plan->stripe_price_id
                            ? 'border-2 border-blue-500 ring-2 ring-blue-500 ring-opacity-50'
                            : 'border border-gray-200 dark:border-gray-700 hover:border-blue-300 dark:hover:border-blue-700' }}
                        transform hover:-translate-y-1 hover:shadow-xl">

                        <!-- Current Plan Badge -->
                        @if($subscription && $subscription->stripe_price === $plan->stripe_price_id)
                            <div class="absolute -top-4 inset-x-0 flex justify-center">
                                <span class="inline-flex items-center px-4 py-1 rounded-full text-sm font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 shadow-md">
                                    <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                              d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                              clip-rule="evenodd"/>
                                    </svg>
                                    Current Plan
                                </span>
                            </div>
                        @endif

                        <!-- Plan Content -->
                        <div class="p-6 flex-1 flex flex-col">
                            <!-- Plan Header -->
                            <div class="text-center mb-6">
                                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                                    {{ $plan->name }}
                                </h3>
                                <div class="flex items-baseline justify-center mb-3">
                                    <span class="text-4xl font-extrabold text-gray-900 dark:text-white">
                                        ${{ number_format($plan->price, 2) }}
                                    </span>
                                    <span class="text-gray-500 dark:text-gray-400 ml-1">/month</span>
                                </div>
                                <p class="text-gray-600 dark:text-gray-400">
                                    {{ $plan->description }}
                                </p>
                            </div>

                            <!-- Features List -->
                            <div class="mb-8 flex-1">
                                <div class="prose prose-sm dark:prose-invert max-w-none">
                                    {!! $plan->features !!}
                                </div>
                            </div>

                            <!-- Action Button -->
                            @if(!($subscription && $subscription->stripe_price === $plan->stripe_price_id))
                                <div class="mt-auto">
                                    <a href="{{ route('subscription.change-plan.show', ['plan_id' => $plan->id]) }}"
                                       class="block w-full text-center px-6 py-3 rounded-lg text-white transition-all duration-200
                                           {{ $subscription
                                               ? 'bg-blue-600 hover:bg-blue-700 shadow-blue-500/30'
                                               : 'bg-green-600 hover:bg-green-700 shadow-green-500/30' }}
                                           shadow-lg hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-offset-2
                                           {{ $subscription ? 'focus:ring-blue-500' : 'focus:ring-green-500' }}">
                                        <span class="flex items-center justify-center">
                                            @if($subscription)
                                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor"
                                                     viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                          stroke-width="2"
                                                          d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                                </svg>
                                                Switch Plan
                                            @else
                                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor"
                                                     viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                          stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                                </svg>
                                                Get Started
                                            @endif
                                        </span>
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

</x-app-layout>