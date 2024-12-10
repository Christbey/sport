<!-- resources/views/subscriptions/index.blade.php -->
<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold">Choose Your Subscription Plan</h1>
                <p class="mt-4 text-gray-600">Select the plan that best fits your needs</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach ($plans as $plan)
                    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                        <div class="px-6 py-8">
                            <h2 class="text-2xl font-bold text-center">{{ $plan->name }}</h2>
                            <div class="mt-4 text-center">
                                <span class="text-4xl font-bold">${{ number_format($plan->price / 100, 2) }}</span>
                                <span class="text-gray-600">/{{ $plan->interval }}</span>
                            </div>

                            @if($plan->description)
                                <p class="mt-4 text-gray-600 text-center">{{ $plan->description }}</p>
                            @endif

                            <div class="mt-8">
                                <form action="{{ route('subscription.checkout') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                                    <button type="submit"
                                            class="w-full bg-blue-500 text-white rounded-md py-2 px-4 hover:bg-blue-600 transition duration-150">
                                        Subscribe to {{ $plan->name }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>