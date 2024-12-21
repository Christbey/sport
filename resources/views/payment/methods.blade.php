<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                    <h1 class="text-2xl font-bold mb-6">Manage Payment Methods</h1>

                    @if(session('success'))
                        <div class="bg-green-100 dark:bg-green-800 border border-green-400 text-green-700 dark:text-green-300 px-4 py-3 rounded relative mb-4"
                             role="alert">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="bg-red-100 dark:bg-red-800 border border-red-400 text-red-700 dark:text-red-300 px-4 py-3 rounded relative mb-4"
                             role="alert">
                            {{ session('error') }}
                        </div>
                    @endif

                    <div class="space-y-4">
                        @forelse($paymentMethods as $method)
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 flex justify-between items-center">
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-gray-200">
                                        {{ strtoupper($method->brand) }} **** **** **** {{ $method->last4 }}
                                        @if($method->id === $defaultPaymentMethod->id)
                                            <span class="ml-2 text-sm text-green-600 dark:text-green-400">(Default)</span>
                                        @endif
                                    </p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        Expires {{ $method->exp_month }}/{{ $method->exp_year }}
                                    </p>
                                </div>
                                <div class="flex space-x-2">
                                    @if($method->id !== $defaultPaymentMethod->id)
                                        <form action="{{ route('payment.set-default', $method->id) }}" method="POST"
                                              class="inline">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit"
                                                    class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">
                                                Set as Default
                                            </button>
                                        </form>
                                    @endif
                                    <form action="{{ route('payment.remove-method', $method->id) }}" method="POST"
                                          class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                onclick="return confirm('Are you sure you want to remove this payment method?')"
                                                class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 ml-2">
                                            Remove
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <p class="text-gray-500 dark:text-gray-400">No payment methods found.</p>
                        @endforelse
                    </div>
                    <div class='mt-6'>
                        <a href="{{ route('payment.create') }}"
                           class='inline-block bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600'>
                            Add New Payment Method
                        </a>
                    </div>
                    <div class="mt-6">
                        <a href="{{ route('subscription.manage') }}"
                           class="inline-block bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600">
                            Back to Subscription
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>