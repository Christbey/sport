<x-app-layout>
    <section class="bg-white dark:bg-gray-900">
        <div class="py-8 px-4 mx-auto max-w-screen-xl lg:py-16 lg:px-6">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold">Choose Your Subscription Plan</h1>
                <p class="mt-4 text-gray-600 dark:text-gray-400">Select the plan that best fits your needs</p>
            </div>

            @php
                // Determine the active subscription's plan slug if the user is subscribed
                $activeSubscription = auth()->user()->activeSubscriptionPlanName();
                $activePlanSlug = $activeSubscription ? Str::slug($activeSubscription->plan->name) : Str::slug($plans->first()->name);
            @endphp

                    <!-- Pricing Card -->
            <div id="pricingTabs"
                 x-data="{
                     activeTab: '{{ $activePlanSlug }}',
                     init() {
                         // Initialize the active tab based on the user's subscription
                         this.activeTab = '{{ $activePlanSlug }}';
                     }
                 }"
                 class="bg-white rounded-lg divide-y divide-gray-200 shadow dark:divide-gray-700 lg:divide-y-0 lg:divide-x lg:grid lg:grid-cols-3 dark:bg-gray-800">

                <!-- Plan Selection and Details -->
                <div class="col-span-2 p-6 lg:p-8">
                    <h3 class="mb-4 text-lg font-medium text-gray-900 dark:text-white">Choose a pricing plan:</h3>

                    <!-- Plan Tabs -->
                    <ul class="grid grid-cols-{{ $plans->count() }} text-sm font-medium text-center text-gray-500 shadow md:rounded-lg dark:divide-gray-600 dark:text-gray-400">
                        @foreach($plans as $plan)
                            @php
                                $planSlug = Str::slug($plan->name);
                                $isFirst = $loop->first;
                                $isLast = $loop->last;
                            @endphp
                            <li class="w-full">
                                <button type="button"
                                        @click="activeTab = '{{ $planSlug }}'"
                                        :class="{
                                            'text-gray-900 bg-gray-100 dark:bg-gray-600 dark:text-white': activeTab === '{{ $planSlug }}',
                                            'bg-white hover:text-gray-700 hover:bg-gray-50 dark:hover:text-white dark:bg-gray-700 dark:hover:bg-gray-600': activeTab !== '{{ $planSlug }}'
                                        }"
                                        class="inline-block p-4 w-full border border-gray-200 dark:border-gray-500 rounded-t-lg md:rounded-none {{ $isFirst ? 'md:rounded-l-lg' : ($isLast ? 'md:rounded-r-lg' : '') }}">
                                    {{ $plan->name }}
                                </button>
                            </li>
                        @endforeach
                    </ul>

                    <!-- Plan Details -->
                    @foreach($plans as $plan)
                        @php
                            $planSlug = Str::slug($plan->name);
                        @endphp
                        <div id="{{ $planSlug }}-content"
                             x-show="activeTab === '{{ $planSlug }}'"
                             class="mt-6">
                            <div class="mb-2 font-medium text-gray-900 dark:text-white">{{ $plan->name }} Details:</div>
                            <p class="text-lg font-light text-gray-500 dark:text-gray-400">
                                Starting at ${{ number_format($plan->price, 2) }}/month
                            </p>
                        </div>
                    @endforeach
                </div>

                <!-- Pricing Details and Subscribe Buttons -->
                <div class="flex p-6 lg:p-8">
                    @foreach($plans as $plan)
                        @php
                            $planSlug = Str::slug($plan->name);
                            $isActivePlan = $activeSubscription && $activeSubscription->plan->id === $plan->id;
                        @endphp
                        <div id="{{ $planSlug }}-plan"
                             x-show="activeTab === '{{ $planSlug }}'"
                             class="self-center w-full">
                            <div class="mb-4 text-2xl font-semibold text-gray-900 dark:text-white">
                                {{ $plan->name }} Plan
                            </div>
                            <div class="font-light text-gray-500 dark:text-gray-400">Monthly Price</div>
                            <div class="mb-4 text-5xl font-extrabold text-gray-900 dark:text-white">
                                ${{ number_format($plan->price, 2) }}
                            </div>

                            @if($isActivePlan)
                                <div class="mb-4 text-center text-green-600 dark:text-green-400">
                                    You are currently subscribed to this plan.
                                </div>
                                <form action="{{ route('subscription.manage') }}" method="GET">
                                    @csrf
                                    <button type="submit"
                                            class="w-full justify-center text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:ring-blue-200 dark:focus:ring-blue-900 font-medium rounded-lg text-sm px-5 py-2.5 text-center mb-4">
                                        Manage Subscription
                                    </button>
                                </form>
                            @else
                                <form action="{{ route('subscription.checkout') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                                    <button type="submit"
                                            class="w-full justify-center text-white bg-primary-600 hover:bg-primary-700 focus:ring-4 focus:ring-blue-200 dark:focus:ring-primary-900 font-medium rounded-lg text-sm px-5 py-2.5 text-center mb-4">
                                        Subscribe Now
                                    </button>
                                </form>
                            @endif

                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-4">
                                Prices shown in {{ strtoupper($plan->currency) }}. Cancel anytime.
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Warning Message -->
            @if (session('warning'))
                <div class="mt-4 p-4 mb-4 text-sm text-yellow-800 rounded-lg bg-yellow-50 dark:bg-gray-800 dark:text-yellow-300"
                     role="alert">
                    {{ session('warning') }}
                </div>
            @endif
        </div>
    </section>
</x-app-layout>
