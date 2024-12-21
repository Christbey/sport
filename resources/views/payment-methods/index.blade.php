{{-- payment-methods/index.blade.php --}}
<div class="space-y-4">
    @forelse($paymentMethods as $method)
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 flex justify-between items-center">
            <div>
                <p class="font-medium text-gray-900 dark:text-gray-200">
                    {{ strtoupper($method->card->brand) }} **** **** **** {{ $method->card->last4 }}
                    @if($method->id === $defaultPaymentMethod->id)
                        <span class="ml-2 text-sm text-green-600 dark:text-green-400">(Default)</span>
                    @endif
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Expires {{ $method->card->exp_month }}/{{ $method->card->exp_year }}
                </p>
            </div>
            <div class="flex space-x-2">
                @if($method->id !== $defaultPaymentMethod->id)
                    <form action="{{ route('payment.set-default', $method->id) }}" method="POST" class="inline">
                        @csrf
                        @method('PUT')
                        <button type="submit"
                                class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">
                            Set as Default
                        </button>
                    </form>
                @endif
                <form action="{{ route('payment.remove-method', $method->id) }}" method="POST" class="inline">
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