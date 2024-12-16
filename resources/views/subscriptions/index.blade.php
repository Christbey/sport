<x-app-layout>
    <section class="bg-white dark:bg-gray-900">
        <div class="py-8 px-4 mx-auto max-w-screen-xl lg:py-16 lg:px-6">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold">Choose Your Subscription Plan</h1>
                <p class="mt-4 text-gray-600 dark:text-gray-400">Select the plan that best fits your needs</p>
            </div>

            <!-- Pricing Card -->
            <div id="pricingTabs"
                 x-data="{
                     activeTab: 'standard',
                     init() {
                         // Show initial content
                         this.$nextTick(() => {
                             this.activeTab = 'standard';
                         });
                     }
                 }"
                 class="bg-white rounded-lg divide-y divide-gray-200 shadow dark:divide-gray-700 lg:divide-y-0 lg:divide-x lg:grid lg:grid-cols-3 dark:bg-gray-800">

                <div class="col-span-2 p-6 lg:p-8">
                    <h3 class="mb-4 text-lg font-medium text-gray-900 dark:text-white">Choose a pricing plan:</h3>

                    <!-- Plan Tabs -->
                    <ul class="grid grid-cols-2 text-sm font-medium text-center text-gray-500 shadow md:rounded-lg md:grid-cols-{{ $plans->count() }} dark:divide-gray-600 dark:text-gray-400">
                        @foreach($plans as $plan)
                            <li class="w-full">
                                <button type="button"
                                        @click="activeTab = '{{ Str::slug($plan->name) }}'"
                                        :class="{
                                            'text-gray-900 bg-gray-100 dark:bg-gray-600 dark:text-white': activeTab === '{{ Str::slug($plan->name) }}',
                                            'bg-white hover:text-gray-700 hover:bg-gray-50 dark:hover:text-white dark:bg-gray-700 dark:hover:bg-gray-600': activeTab !== '{{ Str::slug($plan->name) }}'
                                        }"
                                        class="inline-block p-4 w-full border border-gray-200 dark:border-gray-500"
                                        :class="{{ $loop->first ? '{"md:rounded-l-lg": true}' : ($loop->last ? '{"md:rounded-r-lg": true}' : '{}') }}">
                                    {{ $plan->name }}
                                </button>
                            </li>
                        @endforeach
                    </ul>

                    <!-- Plan Details -->
                    @foreach($plans as $plan)
                        <div id="{{ Str::slug($plan->name) }}-content"
                             x-show="activeTab === '{{ Str::slug($plan->name) }}'"
                             class="mt-6">
                            <div class="mb-2 font-medium text-gray-900 dark:text-white">{{ $plan->name }} details:</div>
                            <p class="text-lg font-light text-gray-500 dark:text-gray-400">
                                Starting at ${{ $plan->price }}/month
                            </p>
                        </div>
                    @endforeach
                </div>

                <!-- Pricing Details -->
                <div class="flex p-6 lg:p-8">
                    @foreach($plans as $plan)
                        <div id="{{ Str::slug($plan->name) }}-plan"
                             x-show="activeTab === '{{ Str::slug($plan->name) }}'"
                             class="self-center w-full">
                            <div class="mb-4 text-2xl font-semibold text-gray-900 dark:text-white">
                                {{ $plan->name }} plan
                            </div>
                            <div class="font-light text-gray-500 dark:text-gray-400">Monthly price</div>
                            <div class="mb-4 text-5xl font-extrabold text-gray-900 dark:text-white">
                                ${{ $plan->price }}
                            </div>

                            <form action="{{ route('subscription.checkout') }}" method="POST">
                                @csrf
                                <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                                <button type="submit"
                                        class="w-full justify-center text-white bg-primary-600 hover:bg-primary-700 focus:ring-4 focus:ring-blue-200 dark:focus:ring-primary-900 font-medium rounded-lg text-sm px-5 py-2.5 text-center mb-4">
                                    Subscribe Now
                                </button>
                            </form>

                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-4">
                                Prices shown in {{ strtoupper($plan->currency) }}. Cancel anytime.
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>

            @if (session('warning'))
                <div class="mt-4 p-4 mb-4 text-sm text-yellow-800 rounded-lg bg-yellow-50 dark:bg-gray-800 dark:text-yellow-300"
                     role="alert">
                    {{ session('warning') }}
                </div>
            @endif
        </div>
    </section>
</x-app-layout>