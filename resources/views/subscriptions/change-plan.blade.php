<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h2 class="text-2xl font-bold mb-4">Change Subscription Plan</h2>

                    <div class="mb-8">
                        <h3 class="text-lg font-semibold mb-2">Current Plan</h3>
                        <div class="p-4 bg-gray-50 rounded-lg">
                            <div class="font-medium">{{ $currentPlan->name }}</div>
                            <div class="text-gray-600">${{ number_format($currentPlan->price, 2) }}/month</div>
                        </div>
                    </div>

                    <div class="mb-8">
                        <h3 class="text-lg font-semibold mb-2">New Plan</h3>
                        <div class="p-4 bg-blue-50 rounded-lg">
                            <div class="font-medium">{{ $newPlan->name }}</div>
                            <div class="text-gray-600">${{ number_format($newPlan->price, 2) }}/month</div>
                        </div>
                    </div>

                    <div class="mb-8">
                        <div class="text-sm text-gray-600">
                            <p>Your subscription will be updated immediately. You may be charged a prorated amount for
                                the remainder of the current billing period.</p>
                        </div>
                    </div>

                    <div class="flex space-x-4">
                        <form action="{{ route('subscription.change-plan') }}" method="POST">
                            @csrf
                            <input type="hidden" name="plan_id" value="{{ $newPlan->id }}">
                            <button type="submit"
                                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                Confirm Change
                            </button>
                        </form>

                        <a href="{{ route('subscription.manage') }}"
                           class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                            Cancel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>