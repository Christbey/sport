@php use Carbon\Carbon; @endphp
<x-app-layout>
    <!-- Status Messages -->
    @if(session('success'))
        <div class="mt-4 p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400"
             role="alert">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mt-4 p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400"
             role="alert">
            {{ session('error') }}
        </div>
    @endif
    <section class="min-h-screen bg-gradient-to-b from-white to-gray-50 dark:from-gray-900 dark:to-gray-800">
        <div class="py-12 px-4 mx-auto max-w-7xl lg:py-16 lg:px-6">
            <!-- Hero Section -->
            <div class="relative mb-12 p-8 rounded-2xl bg-white dark:bg-gray-800 shadow-lg overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-white dark:from-gray-800 dark:to-gray-900 opacity-50"></div>
                <div class="relative">
                    <h1 class="text-4xl font-extrabold tracking-tight text-gray-900 dark:text-white mb-2">
                        Manage Your Subscription
                    </h1>
                    <p class="text-lg text-gray-600 dark:text-gray-400">
                        Current Status:
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ml-2
                        @if($subscription && $subscription->active())
                            bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300
                        @elseif($subscription && $subscription->canceled())
                            bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300
                        @else
                            bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300
                        @endif
                    ">
                        {{ ucfirst($subscription->stripe_status ?? 'No active subscription') }}
                    </span>
                    </p>
                </div>
            </div>
            <!-- Current Plan Card -->
            <div class="mb-8 rounded-2xl bg-white dark:bg-gray-800 shadow-lg overflow-hidden border border-gray-100 dark:border-gray-700">
                <div class="p-6 sm:p-8">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Current Plan</h2>
                        @if($subscription && $subscription->active() && !$subscription->onGracePeriod())
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                    Active
                </span>
                        @endif
                    </div>

                    <div class="grid md:grid-cols-2 gap-8">
                        <!-- Plan Details -->
                        <div class="space-y-6">
                            @if($subscription)
                                <div class="flex flex-col">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-500 dark:text-gray-400">Plan Name</span>
                                        <span class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ $currentPlan ? $currentPlan->name : 'Unknown Plan' }}
                            </span>
                                    </div>
                                    <div class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                        {{ $currentPlan ? $currentPlan->description : '' }}
                                    </div>
                                </div>

                                <div class="flex flex-col">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-500 dark:text-gray-400">Billing Period</span>
                                        <span class="text-lg font-semibold text-gray-900 dark:text-white">
                                @if($subscription->created_at)
                                                {{ $subscription->created_at->format('M d, Y') }} —
                                                {{ $subscription->onGracePeriod()
                                                    ? $subscription->ends_at->format('M d, Y')
                                                    : $subscription->created_at->addDays(30)->format('M d, Y') }}
                                            @else
                                                Not available
                                            @endif
                            </span>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Status and Actions -->
                        <div class="space-y-6">
                            @if($subscription)
                                <!-- Status Alerts -->
                                @if($subscription->onGracePeriod())
                                    <div class="p-4 rounded-lg bg-yellow-50 dark:bg-yellow-900/50 border border-yellow-200 dark:border-yellow-800">
                                        <div class="flex">
                                            <div class="flex-shrink-0">
                                                <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20"
                                                     fill="currentColor">
                                                    <path fill-rule="evenodd"
                                                          d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                                          clip-rule="evenodd"/>
                                                </svg>
                                            </div>
                                            <div class="ml-3">
                                                <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                                                    Subscription Cancellation
                                                </h3>
                                                <p class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                                                    Your subscription will remain active
                                                    until {{ $subscription->ends_at->format('M d, Y') }}
                                                </p>
                                                <div class="mt-3">
                                                    <form action="{{ route('subscription.resume') }}" method="POST"
                                                          class="inline">
                                                        @csrf
                                                        <button type="submit"
                                                                class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-yellow-700 bg-yellow-100 hover:bg-yellow-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500">
                                                            Resume Subscription
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                <!-- Action Buttons -->
                                <div class="flex flex-col sm:flex-row gap-4">
                                    <a href="{{ route('payment.methods') }}"
                                       class="inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                        </svg>
                                        Manage Payment Methods
                                    </a>

                                    @if($subscription->active() && !$subscription->onGracePeriod())
                                        <form action="{{ route('subscription.cancel') }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit"
                                                    onclick="return confirm('Are you sure you want to cancel your subscription?')"
                                                    class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                                <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor"
                                                     viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                          stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                                Cancel Subscription
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

        </div>
        <!-- Available Plans Section -->
        <!-- Available Plans Section -->
        <div class="relative mt-16 bg-gradient-to-b from-gray-50 to-white dark:from-gray-800 dark:to-gray-900 rounded-3xl shadow-xl overflow-hidden">
            <!-- Background Pattern -->
            <div class="absolute inset-0 opacity-10 dark:opacity-20">
                <svg class="h-full w-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <defs>
                        <pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse">
                            <path d="M 10 0 L 0 0 0 10" fill="none" stroke="currentColor" stroke-width="0.5"/>
                        </pattern>
                    </defs>
                    <rect width="100" height="100" fill="url(#grid)"/>
                </svg>
            </div>

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
        </div>

        <!-- Add necessary styles -->
        <style>
            .prose ul {
                @apply mt-4 space-y-3;
            }

            .prose ul li {
                @apply flex items-start text-gray-600 dark:text-gray-400;
            }

            .prose ul li::before {
                content: "";
                @apply w-5 h-5 mr-2 flex-shrink-0 rounded-full bg-green-100 dark:bg-green-900 flex items-center justify-center;
                background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='%2334D399'%3E%3Cpath fill-rule='evenodd' d='M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z' clip-rule='evenodd'/%3E%3C/svg%3E");
            }
        </style>
    </section>

    {{-- Proration Modal using Alpine.js --}}
    <div x-data="{ showModal: false }" x-cloak>
        <template x-teleport="body">
            <div x-show="showModal"
                 class="fixed inset-0 z-50 overflow-y-auto"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0">

                {{-- Modal Backdrop --}}
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                         x-show="showModal"
                         @click="showModal = false"></div>

                    {{-- Modal Panel --}}
                    <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full"
                         x-show="showModal"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                         x-transition:leave="transition ease-in duration-200"
                         x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                         x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">

                        <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100 dark:bg-yellow-900 sm:mx-0 sm:h-10 sm:w-10">
                                    <svg class="h-6 w-6 text-yellow-600 dark:text-yellow-400"
                                         xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                         stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                </div>
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                                        Confirm Plan Change
                                    </h3>
                                    <div class="mt-2">
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            Changing your plan will result in a prorated charge or credit for the
                                            remainder of your billing cycle.
                                            Would you like to proceed?
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit"
                                    class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm"
                                    @click="$refs.planForm.submit(); showModal = false">
                                Proceed
                            </button>
                            <button type="button"
                                    class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                                    @click="showModal = false">
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</x-app-layout>