<!-- resources/views/subscriptions/success.blade.php -->
<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                <h2 class="text-2xl font-semibold mb-4">Subscription Status</h2>

                @if($subscription)
                    <div class="space-y-4">
                        <p>Status: <span class="font-semibold">{{ $subscription->stripe_status }}</span></p>
                        <p>Plan: <span class="font-semibold">{{ $subscription->stripe_price }}</span></p>
                        @if($subscription->cancelled())
                            <p>Subscription will end on: {{ $subscription->ends_at->format('F j, Y') }}</p>
                        @endif
                    </div>
                @else
                    <p>No active subscription found.</p>
                @endif

                <div class="mt-6">
                    <a href="{{ route('subscription.index') }}"
                       class="inline-block bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-600">
                        Back to Plans
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>