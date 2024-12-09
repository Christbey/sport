<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-3 gap-6">
                @foreach($plans as $plan)
                    <div class="bg-white p-6 shadow-lg rounded-lg">
                        <h2 class="text-xl font-bold">{{ $plan->name }}</h2>
                        <p class="text-lg">${{ number_format($plan->price, 2) }}/month</p>
                        <form action="{{ route('subscription.checkout') }}" method="POST">
                            @csrf
                            <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                            <button type="submit" class="mt-4 bg-blue-500 text-white py-2 px-4 rounded">
                                Choose Plan
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
